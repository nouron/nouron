import { API_URL } from '#lib/stores/auth.ts';

// lang/{lang}/*.php from Laravel (GET /api/v1/locale/{lang}) — the backend stays the single source.
let dict: Record<string, unknown> = {};

const KEY = 'nouron.locale';
/** Language of the loaded texts: the player's last known setting (also before login), else German. */
export const locale = localStorage.getItem(KEY) ?? 'de';

export async function loadLocale(lang = locale): Promise<void> {
	const res = await fetch(`${API_URL}/locale/${lang}`, { headers: { Accept: 'application/json' } });
	if (res.ok) dict = await res.json();
}

/** Switch to the player's language: texts are read once per render, so reload the app. */
export function useLocale(lang: string): void {
	if (lang === locale) return;
	localStorage.setItem(KEY, lang);
	location.reload();
}

/** Laravel-style lookup: t('techtree.status_built'), placeholders as `:name`. Unknown keys return the key. */
export function t(key: string, replace: Record<string, string | number> = {}): string {
	const value = key.split('.').reduce<unknown>((node, part) => (node as Record<string, unknown>)?.[part], dict);
	let text = typeof value === 'string' ? value : key;
	for (const [name, v] of Object.entries(replace)) text = text.replaceAll(`:${name}`, String(v));
	return text;
}
