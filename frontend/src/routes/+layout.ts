import { loadLocale } from '#lib/i18n.ts';

// SPA only: no server rendering, token lives in localStorage.
export const ssr = false;

export async function load() {
	// ponytail: German only until the user's locale preference is exposed by the API.
	await loadLocale('de');
}
