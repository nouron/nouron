import { redirect } from '@sveltejs/kit';
import { get } from 'svelte/store';
import { token } from '#lib/stores/auth.ts';
import { refreshShell, shell } from '#lib/stores/shell.ts';

// Entry point like Blade's "/": the colony during a started run, otherwise the lobby.
export async function load() {
	if (!get(token)) redirect(307, '/login');
	await refreshShell().catch(() => {});
	redirect(307, get(shell)?.in_active_run ? '/colony' : '/lobby');
}
