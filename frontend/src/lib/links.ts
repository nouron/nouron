import { BACKEND_URL } from '#lib/stores/auth.ts';

// Screens already migrated to Svelte; everything else still lives in Blade (ADR 0006 coexistence).
const MIGRATED = ['/techtree', '/colony', '/advisors', '/command-center', '/hangar', '/cantina', '/comm-log', '/nexus-db', '/codex', '/lobby', '/settings'];
// Blade paths whose Svelte route has another name.
const RENAMED: Record<string, string> = {
	'/colony/view': '/colony',
	'/colony/command-center': '/command-center',
	'/colony/hangar': '/hangar',
	'/colony/bar': '/cantina',
	'/comm-log/nexus': '/comm-log?tab=nexus',
	'/user/codex': '/codex',
	'/user': '/settings',
	'/user/settings': '/settings'
};

/** Server-side paths (hint target_url, nav) → Svelte route when migrated, else the Blade page. */
export function appLink(path: string): string {
	// /run/{id}/result (Sol report finale, server URLs) → the Svelte result page.
	const result = path.match(/\/run\/(\d+)\/result/);
	if (result) return `/run/${result[1]}`;
	const [base, query] = path.split(/(?=\?)/);
	const p = (RENAMED[base] ?? base) + (query ?? '');
	// Exact route or its query string only: other /colony/* paths are still Blade.
	return MIGRATED.some((m) => p === m || p.startsWith(m + '?')) ? p : `${BACKEND_URL}${path}`;
}
