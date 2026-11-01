import { loadLocale } from '#lib/i18n.ts';

// SPA only: no server rendering, token lives in localStorage.
export const ssr = false;

export async function load() {
	await loadLocale();
}
