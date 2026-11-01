<script lang="ts">
	// Port of resources/views/colony/hangar.blade.php + public/js/hangar.js (ADR 0006 stage 4c).
	import '../../../../../public/css/hangar.css';
	import '../../../../../public/css/dialogs.css';
	import { onMount } from 'svelte';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { refreshShell, setAp } from '#lib/stores/shell.ts';
	import { t } from '#lib/i18n.ts';
	import { Carousel } from '#lib/carousel.svelte.ts';
	import ApUpgradeButton, { type ApRow } from '#lib/components/ApUpgradeButton.svelte';
	import FirstVisit from '#lib/components/FirstVisit.svelte';

	type Readiness = { ready: boolean; wear: number; sp_after: number; repair_ap: number };
	type Ship = {
		ship_id: number;
		name: string;
		level: number;
		ship_state: 'docked' | 'building' | 'dispatched';
		status_points: number;
		deliver_at_tick?: number | null;
		inactive?: boolean;
		inactive_reason?: string | null;
		active_mission?: { mission_name: string; return_tick: number } | null;
		readiness?: Record<string, Readiness>;
	};
	type Slot = { bay: number; open: boolean; required_level?: number; ship: Ship | null };
	type Pending = { id: number; name: string; expires_at_tick?: number | null; pending_until_tick?: number | null };
	type Target = {
		q?: number;
		r?: number;
		ring?: number;
		research_id?: number;
		label?: string;
		level?: number;
		carry?: Record<string, boolean>;
		yield?: { total: number };
		ap_cost?: number;
		organika?: number;
		duration?: number;
		return_sol?: number;
		sol_distance?: number;
	};
	type Mission = {
		key: string;
		name: string;
		desc: string;
		ships: string[];
		family?: string | null;
		family_name?: string;
		family_level?: number;
		family_scales?: boolean;
		reward_label: string;
		ap_cost: number;
		organika: number;
		duration: number;
		wear?: Record<string, number>;
		sol_distance?: number;
		target_type?: 'knowledge' | 'find_tile' | 'tile' | null;
		targets?: Target[];
		roll?: boolean;
		difficulty_options?: { key: string; label: string; chance_pct: number; reward_multiplier: number }[];
		availability: 'ok' | 'missing_knowledge' | 'missing_target' | 'missing_ap' | 'missing_organika';
		gate?: { knowledge_label: string; required_level: number } | null;
		ap_available: number;
		organika_available: number;
	};
	type Family = ApRow & {
		key: string;
		name: string;
		level: number;
		level_cap: number;
		locked_until: number | null;
		lock_reason: string | null;
		yield_per_level_pct: number;
		yield_now_pct: number;
		yield_next_pct: number | null;
		missions: string[];
	};
	type Data = {
		slots: Slot[];
		pendingShips: Pending[];
		shipTypes: { id: number; name: string }[];
		hasPilot: boolean;
		shipCosts: Record<string, { cost: number; delivery_ticks: number; max_consul_ap?: number }>;
		consulApDiscount: number;
		canUseNexusCredit: boolean;
		hasAktivierterKonsul: boolean;
		verfuegbareVerhandlungsAP: number;
		firstVisit: boolean;
		missionCatalog: Mission[];
		hangarMaxLevel: number;
		maxShipStatus: number;
		trainingFamilies: Family[];
		yieldPerLevelPct: number;
		trainingMaxLevel: number;
		trainingBuildingGate: Record<string, number>;
	};
	type Result = { ok: boolean; message?: string; error?: string; [k: string]: unknown };

	// Ship id → config key in the mission catalog / required hangar level (mirror of HangarService).
	const SHIP_KEY: Record<number, string> = { 85: 'drone', 47: 'freighter', 37: 'corvette' };
	const REQUIRED_LEVEL: Record<number, number> = { 85: 1, 47: 2, 37: 3 };
	const SHIP_LABEL: Record<string, string> = {
		ship_corvette: 'colony.hangar_ship_corvette',
		ship_freighter: 'colony.hangar_ship_freighter',
		ship_drone: 'colony.hangar_ship_drone'
	};

	let d = $state<Data | null>(null);
	let loadError = $state('');
	let loading = $state<Record<number, boolean>>({});
	let error = $state<Record<number, string | null>>({});
	let trainingLoading = $state<Record<string, boolean>>({});
	let trainingError = $state<Record<string, string>>({});
	let pendingOpen = $state(false);
	let requestNotice = $state<string | null>(null);
	let pendingAssign = $state<Record<number, string>>({});
	let pendingLoading = $state<Record<number, boolean>>({});
	let pendingError = $state<Record<number, string | null>>({});
	let missionDialog = $state<HTMLDialogElement>();
	let requestDialog = $state<HTMLDialogElement>();
	let mm = $state({
		bay: 0,
		shipKey: null as string | null,
		selectedKey: null as string | null,
		difficulty: null as string | null,
		targetIndex: '',
		loading: false,
		error: null as string | null
	});
	let rm = $state({ bay: 0, useNexusCredit: false, consulAp: 0, loading: false, error: null as string | null });
	const carousel = new Carousel(0);

	const shipLabel = (name: string) => (SHIP_LABEL[name] ? t(SHIP_LABEL[name]) : name);
	const bayLabel = (bay: number) => (bay === 0 ? t('colony.hangar_apron') : t('colony.hangar_bay', { n: bay }));
	const slotInfo = $derived.by(() => {
		const bays = d?.slots.filter((s) => s.open && s.bay > 0) ?? [];
		return { used: bays.filter((s) => s.ship).length, total: bays.length };
	});
	const freeSlots = $derived(d?.slots.filter((s) => s.open && s.bay > 0 && !s.ship) ?? []);
	const statusWidth = (sp: number) => `${Math.max(0, Math.min(100, (sp / (d?.maxShipStatus ?? 1)) * 100))}%`;
	const fail = (res: Result) => res.message ?? res.error ?? t('colony.network_error');

	async function post(path: string, body: object = {}): Promise<Result> {
		return api<Result>(path, { method: 'POST', body, allowError: true }).catch(() => ({
			ok: false,
			message: t('colony.network_error')
		}));
	}

	function updateSlot(bay: number, slot: unknown) {
		if (!d || !slot) return;
		const i = d.slots.findIndex((s) => s.bay === bay);
		if (i !== -1) d.slots[i] = slot as Slot;
	}

	/** Resync the header after AP / Organika / Credits changed. */
	function sync(res: Result) {
		if (res.apAvailable !== undefined) setAp(res.apAvailable as number);
		refreshShell();
	}

	async function load() {
		const r = await api<Data>('/hangar');
		d = r;
		carousel.count = r.slots.length;
	}

	// ── Nexus request ───────────────────────────────────────────────────────

	const consulApSliderMax = $derived(
		Math.min(
			d?.verfuegbareVerhandlungsAP ?? 0,
			Math.max(0, ...Object.values(d?.shipCosts ?? {}).map((e) => e.max_consul_ap ?? 0))
		)
	);
	// Never spend more negotiation AP than it takes to make this ship free.
	const consulApFor = (shipId: number) => Math.min(rm.consulAp, d?.shipCosts[shipId]?.max_consul_ap ?? 0);
	const effectiveCost = (shipId: number) =>
		Math.max(0, (d?.shipCosts[shipId]?.cost ?? 0) - consulApFor(shipId) * (d?.consulApDiscount ?? 0));
	const shipUnlocked = (shipId: number) => (d?.hangarMaxLevel ?? 0) >= (REQUIRED_LEVEL[shipId] ?? 1);

	function openRequest(bay: number) {
		rm = { bay, useNexusCredit: false, consulAp: 0, loading: false, error: null };
		requestDialog?.showModal();
	}

	// The ordered ship lands in the pending list; the bay stays empty until it is assigned.
	async function submitRequest(shipId: number) {
		rm.loading = true;
		rm.error = null;
		const res = await post('/colony/hangar/request', {
			bay: rm.bay,
			ship_id: shipId,
			use_nexus_credit: rm.useNexusCredit ? 1 : 0,
			consul_ap_spent: consulApFor(shipId)
		});
		rm.loading = false;
		if (!res.ok) return void (rm.error = fail(res));
		if (d) d.pendingShips = res.pending as Pending[];
		pendingOpen = true;
		sync(res);
		requestDialog?.close();
		requestNotice = t('colony.hangar_request_ordered');
		setTimeout(() => (requestNotice = null), 5000);
	}

	async function assignShip(id: number) {
		const bay = parseInt(pendingAssign[id] ?? '', 10);
		if (!bay || !d) return;
		pendingLoading[id] = true;
		pendingError[id] = null;
		const res = await post('/colony/hangar/assign', { ship_row_id: id, bay });
		pendingLoading[id] = false;
		if (!res.ok) return void (pendingError[id] = fail(res));
		d.pendingShips = d.pendingShips.filter((s) => s.id !== id);
		updateSlot(bay, res.slot);
	}

	// ── Bay actions ─────────────────────────────────────────────────────────

	async function bayAction(bay: number, action: 'recall' | 'repair') {
		loading[bay] = true;
		error[bay] = null;
		const res = await post(`/colony/hangar/${bay}/${action}`);
		loading[bay] = false;
		if (!res.ok) return void (error[bay] = fail(res));
		updateSlot(bay, res.slot);
		sync(res);
	}

	// ── Missions ────────────────────────────────────────────────────────────

	const missions = $derived(mm.shipKey ? (d?.missionCatalog.filter((m) => m.ships.includes(mm.shipKey!)) ?? []) : []);
	const dialogShip = $derived(d?.slots.find((s) => s.bay === mm.bay)?.ship ?? null);
	const needsTarget = (m: Mission) => m.target_type != null;
	const picked = (m: Mission) => m.targets?.[parseInt(mm.targetIndex, 10)];
	const readiness = (distance?: number) => (distance === undefined ? null : (dialogShip?.readiness?.[distance] ?? null));

	function openMissions(bay: number) {
		const ship = d?.slots.find((s) => s.bay === bay)?.ship;
		mm = {
			bay,
			shipKey: ship ? (SHIP_KEY[ship.ship_id] ?? null) : null,
			selectedKey: null,
			difficulty: null,
			targetIndex: '',
			loading: false,
			error: null
		};
		missionDialog?.showModal();
	}

	function selectMission(m: Mission) {
		if (m.availability !== 'ok' || mm.selectedKey === m.key) return;
		mm.selectedKey = m.key;
		mm.difficulty = m.difficulty_options?.[0]?.key ?? null;
		mm.targetIndex = '';
		mm.error = null;
	}

	function targetLabel(m: Mission, tg: Target) {
		if (m.target_type === 'knowledge') return `${tg.label} (Lv ${tg.level})`;
		if (m.target_type === 'find_tile') {
			// Each find has its own distance, so its own AP/Organika/duration.
			const carry = tg.carry?.[mm.shipKey ?? ''] ? '' : ' — ' + t('missions.target_too_large');
			return (
				`(${tg.q}|${tg.r}) Ring ${tg.ring} · ${t('missions.target_find_yield', { total: tg.yield?.total ?? 0 })}` +
				` · ${tg.ap_cost} AP · Or ${tg.organika} · ${t('missions.return_label', { sol: tg.return_sol ?? '' })}${carry}`
			);
		}
		return `(${tg.q}|${tg.r}) — Ring ${tg.ring}`;
	}
	const targetDisabled = (m: Mission, tg: Target) => m.target_type === 'find_tile' && !tg.carry?.[mm.shipKey ?? ''];

	// "Wear -6 · condition 14 -> 8" or the blocking line with the repair AP; never greyed out silently.
	function readinessLine(distance?: number) {
		const r = readiness(distance);
		if (!r || !dialogShip) return '';
		const sp = dialogShip.status_points;
		return r.ready
			? t('missions.wear_line', { wear: r.wear, from: sp, to: r.sp_after })
			: t('missions.too_worn_line', { current: sp, wear: r.wear, ap: r.repair_ap });
	}

	// A find flight shows the picked target's own costs, otherwise the catalog entry's.
	function costs(m: Mission) {
		const tg = m.target_type === 'find_tile' && mm.selectedKey === m.key ? picked(m) : undefined;
		const wear = m.wear?.[mm.shipKey ?? ''] ?? 0;
		return tg
			? { ap_cost: tg.ap_cost ?? 0, organika: tg.organika ?? 0, duration: tg.duration ?? 0, wear: readiness(tg.sol_distance)?.wear ?? wear }
			: { ap_cost: m.ap_cost, organika: m.organika, duration: m.duration, wear };
	}

	const blockedByWear = (m: Mission) => m.target_type !== 'find_tile' && readiness(m.sol_distance)?.ready === false;
	const pickedDisabled = (m: Mission) => (m.target_type === 'find_tile' && picked(m) ? targetDisabled(m, picked(m)!) : false);
	function shortfall(m: Mission): string | null {
		const tg = m.target_type === 'find_tile' ? picked(m) : undefined;
		if (!tg) return null;
		if ((tg.ap_cost ?? 0) > m.ap_available)
			return t('missions.gate_nav_ap_hint', { available: m.ap_available, required: tg.ap_cost ?? 0 });
		if ((tg.organika ?? 0) > m.organika_available)
			return t('missions.gate_organika_hint', { available: m.organika_available, required: tg.organika ?? 0 });
		return null;
	}
	const startDisabled = (m: Mission) =>
		m.availability !== 'ok' ||
		mm.loading ||
		blockedByWear(m) ||
		pickedDisabled(m) ||
		shortfall(m) !== null ||
		(needsTarget(m) && mm.selectedKey === m.key && mm.targetIndex === '');

	// First click selects (reveals difficulty/target pickers), the second one dispatches.
	async function startMission(m: Mission) {
		if (m.availability !== 'ok' || mm.loading) return;
		if (mm.selectedKey !== m.key) return selectMission(m);
		if ((needsTarget(m) && mm.targetIndex === '') || !mm.difficulty || startDisabled(m)) return;
		const tg = picked(m);
		mm.loading = true;
		mm.error = null;
		const res = await post(`/colony/hangar/${mm.bay}/dispatch`, {
			mission_key: m.key,
			difficulty: mm.difficulty,
			target: needsTarget(m) && tg ? (m.target_type === 'knowledge' ? { research_id: tg.research_id } : { q: tg.q, r: tg.r }) : null
		});
		mm.loading = false;
		if (!res.ok) return void (mm.error = fail(res));
		updateSlot(mm.bay, res.slot);
		sync(res);
		missionDialog?.close();
	}

	// ── Training (path depth of mission families, T41) ──────────────────────

	async function investTraining(fam: Family) {
		if (trainingLoading[fam.key] || !d) return;
		trainingLoading[fam.key] = true;
		trainingError[fam.key] = '';
		const res = await post(`/colony/path-depth/mission_family/${fam.key}/invest`, { ap: 1 });
		trainingLoading[fam.key] = false;
		if (!res.ok) return void (trainingError[fam.key] = (res.message || res.error || '') as string);
		sync(res);
		// Mission rewards are priced with the family level on the server: refetch so shown = paid.
		if (res.leveled_up) return load();
		Object.assign(fam, {
			level: res.level,
			ap_spend: res.ap_spend,
			ap_for_levelup: res.ap_for_levelup,
			level_cap: res.level_cap,
			ap_cap: res.ap_cap ?? null
		});
		const required = d.trainingBuildingGate[fam.level + 1];
		fam.lock_reason =
			required !== undefined && fam.level >= fam.level_cap
				? t('pathdepth.ui_locked_building', { building: t('buildings.hangar'), level: required })
				: null;
	}

	onMount(() => {
		load().catch((e) => {
			if (!(e instanceof ApiError && e.status === 401)) loadError = String(e.message ?? e);
		});
	});
</script>

{#if loadError}<p class="error load-error" role="alert">{loadError}</p>{/if}

{#if d}
	{@const data = d}
	<div class="hangar-page" {@attach carousel.attach}>
		<div class="hangar-header">
			<h2>{t('colony.hangar_title')}</h2>
			{#if data.slots.length}
				<span class="hangar-slot-badge">{t('colony.hangar_slot_count')}: <strong>{slotInfo.used}/{slotInfo.total}</strong></span>
			{/if}
		</div>

		{#if data.slots.length === 0}
			<p class="hangar-none">{t('colony.hangar_none_built')}</p>
		{:else}
			<!-- Training: path depth of mission families (T41), above apron and bays -->
			<section class="hangar-training">
				<h3 class="hangar-training-title">{t('pathdepth.ui_training_title')}</h3>
				<p class="hangar-training-hint">{t('pathdepth.ui_experience_label')}</p>
				<div class="hangar-training-grid">
					{#each data.trainingFamilies as fam (fam.key)}
						<article class="hangar-training-card" class:hangar-training-card--locked={fam.locked_until}>
							<header class="hangar-training-head">
								<strong>{fam.name}</strong>
								<span class="hangar-training-level">{t('pathdepth.ui_level', { level: fam.level, max: data.trainingMaxLevel })}</span>
							</header>
							<div class="hangar-training-levelbar" aria-hidden="true">
								<span style="width: {(fam.level / data.trainingMaxLevel) * 100}%"></span>
							</div>
							<p class="hangar-training-effect" title={t('pathdepth.ui_training_missions', { list: fam.missions.join(', ') })}>
								<span>{t('pathdepth.ui_training_effect', { pct: fam.yield_per_level_pct })}</span>
								<span class="hangar-training-effect-now"
									>{fam.yield_next_pct !== null
										? t('pathdepth.ui_training_effect_now', { now: fam.yield_now_pct, next: fam.yield_next_pct })
										: t('pathdepth.ui_training_effect_max', { now: fam.yield_now_pct })}</span
								>
							</p>
							<p class="hangar-training-missions">{fam.missions.join(' · ')}</p>
							<div class="hangar-training-action">
								{#if fam.locked_until}
									<p class="hangar-training-lock">{fam.lock_reason}</p>
								{:else if fam.ap_for_levelup <= 0}
									<p class="hangar-training-progress">{t('pathdepth.ui_level_max')}</p>
								{:else}
									<div class="hangar-training-buy">
										<ApUpgradeButton
											row={fam}
											label={t('pathdepth.ui_train')}
											blocked={!!trainingLoading[fam.key]}
											gateReason={fam.lock_reason}
											tipId="training-tip-{fam.key}"
											capTip={t('pathdepth.ui_training_ap_cap_tooltip')}
											onclick={() => investTraining(fam)}
										/>
										{#if fam.lock_reason}<p class="hangar-training-lock">{fam.lock_reason}</p>{/if}
										<p class="hangar-training-progress">
											{t('pathdepth.ui_invest_progress', { spent: fam.ap_spend, needed: fam.ap_for_levelup })}
										</p>
										{#if fam.ap_cap}
											<p class="hangar-training-progress">
												{t('pathdepth.ui_training_ap_today', { spent: fam.ap_cap.spent, cap: fam.ap_cap.cap })}
											</p>
										{/if}
									</div>
								{/if}
								{#if trainingError[fam.key]}<p class="hangar-error">{trainingError[fam.key]}</p>{/if}
							</div>
						</article>
					{/each}
				</div>
			</section>

			<div class="carousel-wrapper">
				<button class="carousel-arrow" onclick={carousel.prev} disabled={carousel.index === 0} aria-label={t('colony.hangar_prev_bay')}
					>&#8249;</button
				>
				<!-- svelte-ignore a11y_no_static_element_interactions (swipe is a touch shortcut; arrows and dots stay usable) -->
				<div
					class="carousel-viewport"
					ontouchstart={carousel.touchstart}
					ontouchmove={carousel.touchmove}
					ontouchend={carousel.touchend}
				>
					<div class="carousel-track" style={carousel.trackStyle}>
						{#each data.slots as slot, i (slot.bay)}
							{@const ship = slot.ship}
							<div class="hangar-card" class:hangar-card--current={i === carousel.index}>
								<div class="hangar-icon-area">
									<span class="hangar-bay-label">{bayLabel(slot.bay)}</span>
									{#if ship}
										<svg
											class="hangar-icon-placeholder"
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											stroke-width="1.2"
											stroke-linecap="round"
											stroke-linejoin="round"
											aria-hidden="true"
										>
											<path d="M12 2C8 2 5 8 5 14l2 2h10l2-2c0-6-3-12-7-12z" />
											<path d="M9 18l-2 4h10l-2-4" />
											<line x1="9" y1="12" x2="15" y2="12" />
										</svg>
									{:else}
										<svg
											class="hangar-icon-placeholder"
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											stroke-width="1"
											stroke-linecap="round"
											stroke-linejoin="round"
											aria-hidden="true"
										>
											<rect x="3" y="3" width="7" height="7" rx="1" />
											<rect x="14" y="3" width="7" height="7" rx="1" />
											<rect x="3" y="14" width="7" height="7" rx="1" />
											<rect x="14" y="14" width="7" height="7" rx="1" />
										</svg>
									{/if}
								</div>

								<div class="hangar-info">
									<div class="hangar-name-row">
										<span class="hangar-name">{ship ? shipLabel(ship.name) : '—'}</span>
										{#if ship}<span class="hangar-stat-value">Lv {ship.level}</span>{/if}
									</div>

									{#if !ship}
										<span class="hangar-subtitle">{t('colony.hangar_empty')}</span>
										{#if !slot.open}
											<span class="hangar-status-chip hangar-status-chip--empty"
												>{t('colony.hangar_bay_locked', { level: slot.required_level ?? '' })}</span
											>
										{:else if slot.bay === 0}
											<!-- Apron: reserved for the gift drone -->
											<span class="hangar-status-chip hangar-status-chip--empty">{t('colony.hangar_apron_reserved')}</span>
										{:else}
											<div style="display:contents">
												<div class="hangar-stat-row">
													<span class="hangar-stat-label">{t('colony.hangar_slot_count')}</span>
													<span class="hangar-status-chip hangar-status-chip--empty">{t('colony.hangar_empty')}</span>
												</div>
												<div class="hangar-nexus-request-wrap">
													<button class="btn-hangar-nexus-request" onclick={() => openRequest(slot.bay)} disabled={loading[slot.bay]}>
														<svg
															viewBox="0 0 20 20"
															fill="none"
															stroke="currentColor"
															stroke-width="1.5"
															stroke-linecap="round"
															stroke-linejoin="round"
															aria-hidden="true"
															class="hangar-nexus-icon"
														>
															<circle cx="10" cy="10" r="8" />
															<path d="M10 6v4l3 3" />
														</svg>
														<span>{t('colony.hangar_nexus_request')}</span>
													</button>
													{#if error[slot.bay]}<div class="hangar-error">{error[slot.bay]}</div>{/if}
												</div>
											</div>
										{/if}
									{:else if ship.ship_state === 'building'}
										<span class="hangar-subtitle">{t('colony.hangar_delivery_pending')}</span>
										<div style="display:contents">
											<div class="hangar-stat-row">
												<span class="hangar-stat-label">{t('colony.hangar_status')}</span>
												<span class="hangar-status-chip hangar-status-chip--building">{t('colony.hangar_delivery_pending')}</span>
											</div>
											<div class="hangar-construction-anim"><div class="hangar-construction-bar"></div></div>
											{#if ship.deliver_at_tick != null}
												<div class="hangar-stat-row">
													<span class="hangar-stat-label">{t('colony.hangar_delivery_pending')}</span>
													<span class="hangar-stat-value hangar-arrival-tick">Sol {ship.deliver_at_tick}</span>
												</div>
											{/if}
										</div>
									{:else if ship.ship_state === 'docked'}
										<span class="hangar-subtitle">{t('colony.hangar_status')}: <span>{ship.status_points}/{data.maxShipStatus}</span></span>
										<div style="display:contents">
											<div class="hangar-status-track">
												<div class="hangar-status-fill" style="width: {statusWidth(ship.status_points)}"></div>
											</div>
											{#if data.hasPilot}<span class="hangar-pilot-badge">{t('colony.hangar_pilot_ready')}</span>{/if}
											<div class="hangar-stat-row">
												<span class="hangar-stat-label">{t('colony.hangar_status')}</span>
												<span class="hangar-stat-value">{ship.status_points}/{data.maxShipStatus}</span>
											</div>
											<!-- Inactive (GDD §7): hangar below the ship class — no mission start -->
											{#if ship.inactive}<div class="hangar-error">{ship.inactive_reason}</div>{/if}
											<div class="hangar-card-footer">
												<button class="btn-hangar-action" onclick={() => openMissions(slot.bay)} disabled={loading[slot.bay] || ship.inactive}>
													{t('colony.hangar_dispatch')}
												</button>
												<button
													class="btn-hangar-action btn-hangar-action--secondary"
													onclick={() => bayAction(slot.bay, 'repair')}
													disabled={loading[slot.bay] || ship.status_points >= data.maxShipStatus}
												>
													{t('colony.hangar_repair')}
													<span class="ap-chip ap-cost-chip ap-chip--build" aria-hidden="true">1 AP</span>
												</button>
											</div>
											{#if error[slot.bay]}<div class="hangar-error">{error[slot.bay]}</div>{/if}
										</div>
									{:else}
										<span class="hangar-subtitle">{t('colony.hangar_in_transit')}</span>
										<div style="display:contents">
											{#if ship.active_mission}
												<div class="hangar-mission-info">
													<span>
														<strong>{ship.active_mission.mission_name}</strong><br />
														<span>{t('missions.return_label', { sol: ship.active_mission.return_tick })}</span>
													</span>
												</div>
											{/if}
											<div class="hangar-stat-row">
												<span class="hangar-stat-label">{t('colony.hangar_status')}</span>
												<span class="hangar-status-chip hangar-status-chip--dispatched">{t('colony.hangar_in_transit')}</span>
											</div>
											<div class="hangar-error">{error[slot.bay] ?? ''}</div>
											<div class="hangar-card-footer">
												<span></span>
												<button
													class="btn-hangar-action btn-hangar-action--danger"
													onclick={() => bayAction(slot.bay, 'recall')}
													disabled={loading[slot.bay]}
												>
													<span>{loading[slot.bay] ? '…' : t('colony.hangar_recall')}</span>
												</button>
											</div>
										</div>
									{/if}
								</div>
							</div>
						{/each}
					</div>
				</div>
				<button
					class="carousel-arrow"
					onclick={carousel.next}
					disabled={carousel.index >= data.slots.length - 1}
					aria-label={t('colony.hangar_next_bay')}>&#8250;</button
				>
			</div>

			<div class="carousel-dots">
				{#each data.slots as slot, i (slot.bay)}
					<button
						class="carousel-dot"
						class:carousel-dot--active={i === carousel.index}
						onclick={() => carousel.goTo(i)}
						aria-label={bayLabel(slot.bay)}
					></button>
				{/each}
			</div>

			{#if requestNotice}<p class="hangar-notice" role="status">{requestNotice}</p>{/if}

			<!-- Ships delivered without a bay yet -->
			{#if data.pendingShips.length}
				<section class="hangar-pending-section">
					<details bind:open={pendingOpen}>
						<summary class="hangar-pending-summary">
							<span>{t('colony.hangar_pending_section')} ({data.pendingShips.length})</span>
						</summary>
						<div class="hangar-pending-list">
							{#each data.pendingShips as ship (ship.id)}
								{@const expires = ship.expires_at_tick || ship.pending_until_tick}
								<article class="hangar-pending-card">
									<div class="hangar-pending-card-info">
										<span class="hangar-pending-name">{shipLabel(ship.name)}</span>
										{#if expires}<span class="hangar-pending-expires">{t('colony.hangar_pending_expires', { tick: expires })}</span>{/if}
									</div>
									<div class="hangar-pending-card-actions">
										<select class="hangar-pending-select" bind:value={pendingAssign[ship.id]}>
											<option value="">{t('colony.hangar_assign_select')}</option>
											{#each freeSlots as free (free.bay)}
												<option value={String(free.bay)}>{t('colony.hangar_bay', { n: free.bay })}</option>
											{/each}
										</select>
										<button
											class="btn-hangar-action"
											onclick={() => assignShip(ship.id)}
											disabled={!pendingAssign[ship.id] || pendingLoading[ship.id]}
										>
											<span>{pendingLoading[ship.id] ? '…' : t('colony.hangar_assign_hangar')}</span>
										</button>
									</div>
									{#if pendingError[ship.id]}<div class="hangar-error">{pendingError[ship.id]}</div>{/if}
								</article>
							{/each}
						</div>
					</details>
				</section>
			{/if}
		{/if}

		<!-- Nexus request dialog (shared for all bays) -->
		<dialog bind:this={requestDialog} class="hangar-request-dialog sol-modal">
			<article>
				<header class="hangar-dialog-header">
					<strong>{t('colony.hangar_nexus_request_title')}</strong>
					<button class="hangar-dialog-close" onclick={() => requestDialog?.close()} aria-label={t('colony.cancel')}>&#x2715;</button>
				</header>
				<div class="hangar-dialog-controls">
					{#if data.canUseNexusCredit}
						<label class="hangar-nexus-toggle">
							<input type="checkbox" role="switch" bind:checked={rm.useNexusCredit} />
							<span>
								<span>{t('colony.hangar_nexus_credit')}</span>
								<small class="hangar-nexus-credit-hint">{t('colony.hangar_nexus_credit_hint')}</small>
							</span>
						</label>
					{/if}
					{#if data.hasAktivierterKonsul && data.verfuegbareVerhandlungsAP > 0}
						<label class="hangar-consul-ap-label">
							<div class="form-row-label">
								<span>{t('colony.hangar_consul_ap_title')}</span>
								<strong>{rm.consulAp > 0 ? `−${rm.consulAp * data.consulApDiscount} Cr` : ''}</strong>
							</div>
							<input type="range" min="0" max={consulApSliderMax} bind:value={rm.consulAp} />
						</label>
					{/if}
				</div>
				<div class="hangar-ship-btn-list">
					{#each data.shipTypes as type (type.id)}
						{@const cost = data.shipCosts[type.id]}
						<button class="hangar-ship-btn" onclick={() => submitRequest(type.id)} disabled={rm.loading || !shipUnlocked(type.id)}>
							<span class="hangar-ship-btn-name">{shipLabel(type.name)}</span>
							{#if cost}
								<span class="hangar-ship-btn-meta">
									<span class="hangar-ship-btn-chips">
										<span class="res-chip res-Cr"
											><span class="res-abbr">CR</span><span class="res-amount">{effectiveCost(type.id)}</span></span
										>
										<span class="res-chip res-Sol"
											><span class="res-abbr">Sol</span><span class="res-amount">+{cost.delivery_ticks}</span></span
										>
									</span>
								</span>
							{/if}
							{#if !shipUnlocked(type.id)}
								<span class="hangar-ship-btn-locked-hint">{t('colony.hangar_request_min_level', { level: REQUIRED_LEVEL[type.id] ?? 1 })}</span>
							{/if}
						</button>
					{/each}
				</div>
				{#if rm.error}<div class="hangar-error hangar-dialog-error">{rm.error}</div>{/if}
				<div class="hangar-dialog-cancel">
					<button type="button" class="link-btn" onclick={() => requestDialog?.close()}>{t('colony.cancel')}</button>
				</div>
			</article>
		</dialog>

		<!-- Mission dispatch dialog (catalog filtered by the docked ship type) -->
		<dialog bind:this={missionDialog} class="hangar-mission-dialog sol-modal">
			<article>
				<header class="hangar-dialog-header">
					<strong>{t('missions.dialog_title')}</strong>
					<button class="hangar-dialog-close" onclick={() => missionDialog?.close()} aria-label={t('colony.cancel')}>&#x2715;</button>
				</header>
				<div class="hangar-mission-list">
					{#if missions.length === 0}<p class="hangar-mission-empty">{t('missions.dialog_no_missions')}</p>{/if}
					{#each missions as m (m.key)}
						{@const c = costs(m)}
						{@const sel = mm.selectedKey === m.key}
						{@const short = shortfall(m)}
						<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_noninteractive_element_interactions -->
						<article
							class="hangar-mission-card"
							class:hangar-mission-card--locked={m.availability !== 'ok'}
							class:hangar-mission-card--selected={sel}
							onclick={() => selectMission(m)}
						>
							<div class="hangar-mission-card-head">
								<span class="hangar-mission-name">{m.name}</span>
								{#if m.family}
									<span class="hangar-mission-family">{t('missions.family_pill', { family: m.family_name ?? '', level: m.family_level ?? 0 })}</span>
								{/if}
							</div>
							<p class="hangar-mission-desc">{m.desc}</p>
							<div class="hangar-mission-yield">
								<span class="hangar-mission-yield-label">{t('missions.reward_label')}</span>
								<strong class="hangar-mission-reward">{m.reward_label}</strong>
								{#if m.family_scales && (m.family_level ?? 0) > 0}
									<small class="hangar-mission-yield-note"
										>{t('missions.family_yield_included', { pct: Math.round((m.family_level ?? 0) * data.yieldPerLevelPct) })}</small
									>
								{/if}
							</div>
							<div class="hangar-mission-chips">
								<span class="ap-chip ap-cost-chip ap-chip--nav">{c.ap_cost} AP</span>
								{#if c.organika > 0}<span class="hangar-mission-chip">Or {c.organika}</span>{/if}
								<span class="hangar-mission-chip">{t('missions.chip_duration', { sols: c.duration })}</span>
								<span class="hangar-mission-chip">{t('missions.chip_wear')} −{c.wear}</span>
							</div>
							{#if m.target_type !== 'find_tile'}
								<p class="hangar-mission-ready-line" class:hangar-mission-gate-hint={readiness(m.sol_distance)?.ready === false}>
									{readinessLine(m.sol_distance)}
								</p>
							{/if}

							{#if needsTarget(m) && sel}
								<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_noninteractive_element_interactions -->
								<label class="hangar-mission-target" onclick={(e) => e.stopPropagation()}>
									<span>{t('missions.select_target')}</span>
									<select bind:value={mm.targetIndex}>
										<option value="">{t('missions.select_target')}</option>
										{#each m.targets ?? [] as tg, ti (ti)}
											<option value={String(ti)} disabled={targetDisabled(m, tg)}>{targetLabel(m, tg)}</option>
										{/each}
									</select>
								</label>
							{/if}

							{#if sel && m.roll}
								<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
								<div class="hangar-mission-difficulty" onclick={(e) => e.stopPropagation()}>
									{#each m.difficulty_options ?? [] as opt (opt.key)}
										<button
											type="button"
											class="hangar-difficulty-chip"
											class:hangar-difficulty-chip--selected={mm.difficulty === opt.key}
											aria-label={t('missions.difficulty_chance_label', { chance: opt.chance_pct })}
											onclick={() => (mm.difficulty = opt.key)}
										>
											<span>{opt.label}</span>
											<span>{opt.chance_pct}%</span>
											<span class="hangar-difficulty-chip-reward">{t('missions.difficulty_reward_label', { multiplier: opt.reward_multiplier })}</span>
										</button>
									{/each}
								</div>
							{/if}

							<div class="hangar-mission-card-footer">
								{#if m.availability === 'missing_knowledge' && m.gate}
									<span class="hangar-mission-gate-hint"
										>{t('missions.gate_knowledge_hint', { name: m.gate.knowledge_label, level: m.gate.required_level })}</span
									>
								{:else if m.availability === 'missing_target'}
									<span class="hangar-mission-gate-hint">{t('missions.gate_target_hint')}</span>
								{:else if m.availability === 'missing_ap'}
									<span class="hangar-mission-gate-hint"
										>{t('missions.gate_nav_ap_hint', { available: m.ap_available, required: m.ap_cost })}</span
									>
								{:else if m.availability === 'missing_organika'}
									<span class="hangar-mission-gate-hint"
										>{t('missions.gate_organika_hint', { available: m.organika_available, required: m.organika })}</span
									>
								{/if}
								{#if short}<span class="hangar-mission-gate-hint">{short}</span>{/if}
								<button
									class="btn-hangar-action hangar-mission-start"
									onclick={(e) => {
										e.stopPropagation();
										startMission(m);
									}}
									disabled={startDisabled(m)}
								>
									<span>{mm.loading && sel ? '…' : t('missions.start_button')}</span>
								</button>
							</div>
						</article>
					{/each}
				</div>
				{#if mm.error}<div class="hangar-error hangar-dialog-error">{mm.error}</div>{/if}
				<div class="hangar-dialog-cancel">
					<button type="button" class="link-btn" onclick={() => missionDialog?.close()}>{t('colony.cancel')}</button>
				</div>
			</article>
		</dialog>
	</div>

	{#if data.firstVisit}<FirstVisit screen="hangar" />{/if}
{/if}

<style>
	.load-error {
		padding: var(--space-md);
	}
	/* PicoCSS drew <article> as a white card with a soft shadow in Blade. */
	.hangar-training-card {
		background: var(--color-bg);
		border-radius: var(--radius-sm);
		box-shadow:
			0 1px 3px rgba(0, 0, 0, 0.08),
			0 0 0 1px rgba(0, 0, 0, 0.03);
	}
	/* Was inline style in Blade (PicoCSS muted colour). */
	.hangar-none {
		color: var(--color-text-secondary);
		margin-top: 2rem;
	}
	/* Blade used <a href="#">; a button keeps it keyboard-friendly without a fake link target. */
	.link-btn {
		background: none;
		border: none;
		padding: 0;
		font: inherit;
		color: var(--color-accent);
		text-decoration: underline;
		cursor: pointer;
	}
</style>
