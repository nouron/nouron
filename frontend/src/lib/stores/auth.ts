import { writable } from 'svelte/store';

export const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1';

const KEY = 'nouron.token';

// ponytail: token in localStorage per ADR 0006 spike; XSS-readable — revisit (e.g. Capacitor secure storage) before beta.
export const token = writable<string | null>(localStorage.getItem(KEY));
token.subscribe((t) => (t ? localStorage.setItem(KEY, t) : localStorage.removeItem(KEY)));

export async function login(login: string, password: string): Promise<void> {
	const res = await fetch(`${API_URL}/auth/login`, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
		body: JSON.stringify({ login, password }),
	});
	const body = await res.json();
	if (!res.ok) throw new Error(body.message ?? `HTTP ${res.status}`);
	token.set(body.token);
}

export async function api<T>(path: string, t: string | null): Promise<T> {
	const res = await fetch(`${API_URL}${path}`, {
		headers: { Accept: 'application/json', Authorization: `Bearer ${t}` },
	});
	if (res.status === 401) token.set(null);
	if (!res.ok) throw new Error(`HTTP ${res.status}`);
	return res.json();
}
