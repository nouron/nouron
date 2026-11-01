// Shapes of GET /api/v1/colony (ColonyController::data) and the action responses.
import type { ApRow } from '#lib/components/ApUpgradeButton.svelte';

export type Find = {
	rg: number;
	false?: boolean;
	ap_total?: number;
	action?: {
		action: 'self_salvage' | 'flight' | string;
		text?: string;
		bay?: number;
		costs?: { ap_cost: number; organika: number };
	} | null;
};

export type Tile = {
	q: number;
	r: number;
	ring: number;
	tile_type: string;
	is_explored: boolean;
	is_colony_zone: boolean;
	is_deep_scanned: boolean;
	has_signal?: boolean;
	next_zone?: boolean;
	event_type?: string | null;
	resource_amount: number;
	resource_max: number;
	regolith_remaining?: number;
	regolith_max?: number;
	sols_remaining?: number | null;
	find?: Find | null;
	[k: string]: unknown;
};

export type Building = ApRow & {
	building_id: number;
	instance_id: number;
	building_key: string;
	label: string;
	level: number;
	max_level: number | null;
	status_points: number;
	max_status_points: number | null;
	tile_x: number | null;
	tile_y: number | null;
	image_slug: string | null;
	description: string | null;
	required_list: string[];
	unlocks_next_level: { text?: string }[];
	workplaces: number;
	in_transit: boolean;
	levelup_cost: number;
	first_level_prepaid: boolean;
	repair_cost: number;
	tier_label: string | null;
	[k: string]: unknown;
};

export type ColonyData = {
	tiles: Tile[];
	buildings: Building[];
	ccLevel: number;
	buildingCcLevelOffset: number;
	ccGateTemplate: string;
	ccBuildingId: number;
	buildingCatalog: Record<string, { label: string; tooltip: { description: string } }>;
	apAvailable: number;
	regolith: number;
	werkstoffe: number;
	organika: number;
	freeSupply: number;
	merchantVisit: unknown;
	supplyCapFull: boolean;
	findTotal: number;
	exploreCostPerRing: Record<string, number>;
	exploreCostDefault: number;
	tileYields: Record<string, number>;
	repairDisplayThreshold: number;
	damagedThresholdPct: number;
	criticalThresholdPct: number;
	deepScanAp: number;
	relocateApPerHex: number;
	regolithFallbackTiles: { q: number; r: number }[];
	[k: string]: unknown;
};

/** Action responses: { ok, error?, message? } plus whatever the action returns. */
export type ActionResult = {
	ok: boolean;
	error?: string;
	message?: string;
	tile?: Tile;
	tiles?: Tile[];
	building?: Building;
	apAvailable?: number;
	regolith?: number;
	werkstoffe?: number;
	organika?: number;
	freeSupply?: number;
	activeHint?: unknown;
	completed?: boolean;
	leveled_up?: boolean;
	ap_cap?: Building['ap_cap'];
	cap?: number;
	cost?: Record<string, number>;
	showHarvesterMoveTip?: boolean;
	completion_popup?: { title: string; text: string } | null;
	nav_unlocked?: boolean;
	tile_released?: boolean;
	construction_cancelled?: boolean;
	[k: string]: unknown;
};
