import { API_URL } from '#lib/stores/auth.ts';

// lang/{lang}/*.php from Laravel (GET /api/v1/locale/{lang}) — the backend stays the single source.
let dict: Record<string, unknown> = {};

export async function loadLocale(lang = 'de'): Promise<void> {
	const res = await fetch(`${API_URL}/locale/${lang}`, { headers: { Accept: 'application/json' } });
	if (res.ok) dict = await res.json();
}

/** Laravel-style lookup: t('techtree.status_built'), placeholders as `:name`. Unknown keys return the key. */
export function t(key: string, replace: Record<string, string | number> = {}): string {
	const value = key.split('.').reduce<unknown>((node, part) => (node as Record<string, unknown>)?.[part], dict);
	let text = typeof value === 'string' ? value : key;
	for (const [name, v] of Object.entries(replace)) text = text.replaceAll(`:${name}`, String(v));
	return text;
}
