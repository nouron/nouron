import { BACKEND_URL } from '#lib/stores/auth.ts';

// Screens already migrated to Svelte; everything else still lives in Blade (ADR 0006 coexistence).
const MIGRATED = ['/techtree', '/colony', '/advisors', '/command-center', '/hangar'];
// Blade paths whose Svelte route has another name.
const RENAMED: Record<string, string> = {
	'/colony/view': '/colony',
	'/colony/command-center': '/command-center',
	'/colony/hangar': '/hangar'
};

/** Server-side paths (hint target_url, nav) → Svelte route when migrated, else the Blade page. */
export function appLink(path: string): string {
	const [base, query] = path.split(/(?=\?)/);
	const p = (RENAMED[base] ?? base) + (query ?? '');
	// Exact route or its query string only: /colony/bar etc. are still Blade.
	return MIGRATED.some((m) => p === m || p.startsWith(m + '?')) ? p : `${BACKEND_URL}${path}`;
}
