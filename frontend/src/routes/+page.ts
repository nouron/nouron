import { redirect } from '@sveltejs/kit';

// ponytail: techtree is the only migrated screen; point "/" at the colony once stage 3 lands.
export function load() {
	redirect(307, '/colony');
}
