import { get, writable } from 'svelte/store';

export const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1';
// Laravel origin: serves images under /img and the not-yet-migrated Blade screens.
export const BACKEND_URL = new URL(API_URL).origin;

const KEY = 'nouron.token';

// ponytail: token in localStorage per ADR 0006 spike; XSS-readable — revisit (e.g. Capacitor secure storage) before beta.
export const token = writable<string | null>(localStorage.getItem(KEY));
token.subscribe((t) => (t ? localStorage.setItem(KEY, t) : localStorage.removeItem(KEY)));

export async function login(login: string, password: string): Promise<void> {
	const res = await fetch(`${API_URL}/auth/login`, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
		body: JSON.stringify({ login, password })
	});
	const body = await res.json();
	if (!res.ok) throw new Error(body.message ?? `HTTP ${res.status}`);
	token.set(body.token);
}

/** Validation errors per field, as Laravel returns them with a 422. */
export class FormError extends Error {
	constructor(public errors: Record<string, string[]>) {
		super(Object.values(errors)[0]?.[0] ?? '');
	}
}

/** Register a player (Blade rules incl. invite code); logs in on success. */
export async function register(data: Record<string, string>): Promise<void> {
	const res = await fetch(`${API_URL}/auth/register`, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
		body: JSON.stringify(data)
	});
	const body = await res.json().catch(() => ({}));
	if (res.status === 422) throw new FormError(body.errors ?? {});
	if (!res.ok) throw new Error(body.message ?? `HTTP ${res.status}`);
	token.set(body.token);
}

/** Revoke the token server-side, then forget it locally (even if the server call fails). */
export async function logout(): Promise<void> {
	await fetch(`${API_URL}/auth/logout`, {
		method: 'POST',
		headers: { Accept: 'application/json', Authorization: `Bearer ${get(token)}` }
	}).catch(() => {});
	token.set(null);
}

/** Error carrying the API's machine code (`error`) next to the player text (`message`). */
export class ApiError extends Error {
	constructor(
		public status: number,
		public code: string | undefined,
		message: string
	) {
		super(message);
	}
}

/**
 * JSON request with the bearer token. 401 logs out. Other non-2xx throw ApiError,
 * except when `allowError` is set: then the body (e.g. a 422 {success:false,error,message}) is returned.
 */
export async function api<T>(
	path: string,
	{ method = 'GET', body, allowError = false }: { method?: string; body?: unknown; allowError?: boolean } = {}
): Promise<T> {
	const res = await fetch(`${API_URL}${path}`, {
		method,
		headers: {
			Accept: 'application/json',
			Authorization: `Bearer ${get(token)}`,
			...(body === undefined ? {} : { 'Content-Type': 'application/json' })
		},
		body: body === undefined ? undefined : JSON.stringify(body)
	});
	if (res.status === 401) token.set(null);
	const json = await res.json().catch(() => ({}));
	if (!res.ok && !allowError) throw new ApiError(res.status, json.error, json.message ?? `HTTP ${res.status}`);
	return json as T;
}
