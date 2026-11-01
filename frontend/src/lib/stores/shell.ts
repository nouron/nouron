import { writable } from 'svelte/store';
import { api } from '#lib/stores/auth.ts';

type Flow = { sources: Record<string, number>; net: number; consumption?: number; upkeep?: number };

/** GET /api/v1/shell — header data of every in-run screen (App\Services\ShellStateService). */
export type Shell = {
	username: string;
	resource_bar_possessions: Record<string, { amount: number; abbreviation: string }>;
	current_sol: number | null;
	sol_limit: number;
	nexus_debt: number | null;
	nexus_debt_max: number;
	trust_chip_red: number;
	in_active_run: boolean;
	bar_built: boolean;
	hangar_built: boolean;
	sciencelab_built: boolean;
	unread_nexus_count: number;
	trust?: number;
	colony_ap?: number;
	supply_breakdown?: {
		cap: number;
		sources: { cc: number; housing: number };
		used: { buildings: number; researches: number; advisors: number };
	};
	colonists_overview?: { colonists: number; employed: number; free: number; unfilled: number };
	staffing_share?: number;
	resource_flow?: { regolith: Flow; organika: Flow; credits: Flow };
	hunger_penalty?: number;
	ap_breakdown?: { base: number; advisor: number; multiplier: number; plague_multiplier?: number } | null;
	active_hint?: { key: string; text: string; target_url: string } | null;
	active_encounter_notices?: { text: string }[];
};

export const shell = writable<Shell | null>(null);

export async function refreshShell(): Promise<void> {
	shell.set(await api<Shell>('/shell'));
}

/** Live AP sync after an action that spent AP (every AJAX action must keep the bar current). */
export function setAp(value: number): void {
	shell.update((s) => (s ? { ...s, colony_ap: value } : s));
}

export function setHint(hint: Shell['active_hint']): void {
	shell.update((s) => (s ? { ...s, active_hint: hint } : s));
}

/** Hint "×" and first-visit popups share one endpoint; returns the next active hint. */
export async function dismissHint(key: string): Promise<void> {
	const res = await api<{ ok: boolean; hint: Shell['active_hint'] }>('/hint/dismiss', {
		method: 'POST',
		body: { hint_key: key }
	});
	if (res.ok) setHint(res.hint ?? null);
}
