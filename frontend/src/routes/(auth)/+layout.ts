import { redirect } from '@sveltejs/kit';
import { get } from 'svelte/store';
import { token } from '#lib/stores/auth.ts';

// Like Blade's guest middleware: logged-in players skip the forms.
export function load() {
	if (get(token)) redirect(307, '/');
}
