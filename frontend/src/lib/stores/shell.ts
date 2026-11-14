import { writable } from 'svelte/store';
import { api } from '#lib/stores/auth.ts';
import { useLocale } from '#lib/i18n.ts';

type Flow = { sources: Record<string, number>; net: number; consumption?: number; upkeep?: number };

/**
 * Trust as the player may see it (TrustService::view(), T59): band word + AP effect always;
 * the number (`total`) and source amounts only with a science lab (`lab`).
 */
export type TrustView = {
	band: string;
	ap: number;
	bands: { key: string; min: number; max: number; ap: number }[];
	lab: boolean;
	sources: { key: string; value?: number }[];
	total?: number;
	/** Next Sol's value if it differs: total with a lab, otherwise only a band change. */
	next_total?: number;
	next_band?: string;
};

/** GET /api/v1/shell — header data of every in-run screen (App\Services\ShellStateService). */
export type Shell = {
	username: string;
	locale: string;
	is_admin: boolean;
	resource_bar_possessions: Record<string, { amount: number; abbreviation: string }>;
	current_sol: number | null;
	sol_limit: number;
	nexus_debt: number | null;
	nexus_debt_max: number;
	in_active_run: boolean;
	bar_built: boolean;
	hangar_built: boolean;
	sciencelab_built: boolean;
	unread_nexus_count: number;
	trust?: TrustView;
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
	ap_breakdown?: { base: number; advisor: number; trust_ap: number; plague_ap: number; total: number } | null;
	active_hint?: { key: string; text: string; target_url: string } | null;
	active_encounter_notices?: { text: string }[];
};

export const shell = writable<Shell | null>(null);

/** Bumped when a new Sol starts: the (game) layout remounts the page so it reloads its data. */
export const solEpoch = writable(0);

export async function refreshShell(): Promise<void> {
	const s = await api<Shell>('/shell');
	useLocale(s.locale); // player changed the language (other device, settings page)
	shell.set(s);
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
