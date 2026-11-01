import { BACKEND_URL } from '#lib/stores/auth.ts';

// Screens already migrated to Svelte; everything else still lives in Blade (ADR 0006 coexistence).
const MIGRATED = ['/techtree'];

/** Server-side paths (hint target_url, nav) → Svelte route when migrated, else the Blade page. */
export function appLink(path: string): string {
	return MIGRATED.some((p) => path === p || path.startsWith(p + '?') || path.startsWith(p + '/'))
		? path
		: `${BACKEND_URL}${path}`;
}
