<script lang="ts">
	// Port of resources/views/advisors/index.blade.php + public/js/advisors.js (ADR 0006 stage 4a).
	import '../../../../../public/css/advisors.css';
	import { onMount } from 'svelte';
	import { api, ApiError, BACKEND_URL } from '#lib/stores/auth.ts';
	import { refreshShell, setAp, setHint, type Shell } from '#lib/stores/shell.ts';
	import { t } from '#lib/i18n.ts';
	import { Carousel } from '#lib/carousel.svelte.ts';
	import EntityChip from '#lib/components/EntityChip.svelte';

	type Advisor = {
		id: number;
		rank: number;
		rank_name: string;
		ap_per_tick: number;
		active_ticks: number;
		progress_pct: number;
		next_rank_ticks: number | null;
		is_max_rank: boolean;
		is_unavailable: boolean;
		unavailable_until_tick: number | null;
		upkeep: number;
	};
	type Slot = {
		position: number;
		key: string;
		name: string;
		desc: string;
		personell_id: number | null;
		hire_cost: number;
		junior_ap: number;
		junior_upkeep: number;
		cc_required: number;
		state: 'locked' | 'empty' | 'active' | 'unavailable';
		advisor: Advisor | null;
		building_warning: string | null;
		is_path_open: boolean;
		preview_advisor_key: string | null;
	};
	type SlotInfo = { used: number; max: number; cc_level: number };
	type Result = { ok: boolean; message?: string; error?: string; slots?: Slot[]; slotInfo?: SlotInfo; apAvailable?: number; activeHint?: unknown };

	const ROLES = ['engineer', 'scientist', 'pilot', 'trader'];
	const PREREQ: Record<string, { slug: string; name: string }> = {
		scientist: { slug: 'sciencelab', name: t('techtree.building_sciencelab') },
		pilot: { slug: 'hangar', name: t('techtree.building_hangar') },
		trader: { slug: 'cantina', name: t('techtree.building_bar') }
	};
	const PORTRAITS: Record<string, string> = {
		engineer: 'construction_master_male',
		scientist: 'analyst_female',
		pilot: 'pilot_male',
		trader: 'trader_female'
	};
	const PLACEHOLDER =
		"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='44' height='44'%3E%3Crect width='44' height='44' rx='6' fill='%23e0e0e8'/%3E%3C/svg%3E";

	let slots = $state<Slot[]>([]);
	let slotInfo = $state<SlotInfo>({ used: 0, max: 0, cc_level: 0 });
	let juniorUpkeep = $state(10);
	let loadError = $state('');
	let dialogSlot = $state<Slot | null>(null);
	let dialogKind = $state<'hire' | 'fire'>('hire');
	let errorMsg = $state<string | null>(null);
	let pageError = $state<string | null>(null);
	let dialog = $state<HTMLDialogElement>();
	const carousel = new Carousel(4);

	const roleOf = (s: Slot) => s.preview_advisor_key ?? s.key;
	const chipTip = (key: string) => ({ description: t(`advisors.${key}_desc`), meta: t('advisors.ap_label') });
	function portraitStyle(key: string) {
		const p = PORTRAITS[key];
		if (!p) return '';
		const url = `${BACKEND_URL}/img/advisors/${p}`;
		return `background-image: image-set(url('${url}.webp') 1x, url('${url}_lg.webp') 2x)`;
	}
	function effects(s: Slot) {
		const key = s.is_path_open
			? ({ path_open_2: 'scientist', path_open_3: 'pilot', path_open_4: 'trader' } as Record<string, string>)[s.key]
			: s.key;
		return key && ROLES.includes(key) ? t(`advisors.effects_${key}`) : s.is_path_open ? t('advisors.ui_path_pending') : '';
	}
	const fallbackImg = (e: Event) => {
		const img = e.currentTarget as HTMLImageElement;
		img.onerror = null;
		img.src = PLACEHOLDER;
	};

	function openDialog(slot: Slot, kind: 'hire' | 'fire') {
		dialogSlot = slot;
		dialogKind = kind;
		errorMsg = null;
		queueMicrotask(() => dialog?.showModal());
	}
	function closeDialog() {
		dialog?.close();
		dialogSlot = null;
		errorMsg = null;
	}

	/** Apply a successful action: new slots, header resync (credits, AP, hint). */
	function apply(res: Result) {
		if (res.slots) slots = res.slots;
		if (res.slotInfo) slotInfo = res.slotInfo;
		if (res.apAvailable !== undefined) setAp(res.apAvailable);
		if ('activeHint' in res) setHint(res.activeHint as Shell['active_hint']);
		refreshShell();
	}

	async function send(path: string, method: string, body?: object): Promise<Result> {
		return api<Result>(path, { method, body, allowError: true }).catch(() => ({
			ok: false,
			message: t('colony.network_error')
		}));
	}

	async function doHire() {
		if (!dialogSlot) return;
		const res = await send('/advisors/hire', 'POST', { personell_id: dialogSlot.personell_id });
		if (!res.ok) return void (errorMsg = res.message ?? t('advisors.ui_error_hire'));
		apply(res);
		closeDialog();
	}

	// Blade left the AP/credits chips stale after firing; the shell refresh covers it here.
	async function doFire() {
		if (!dialogSlot?.advisor) return;
		const res = await send(`/advisors/${dialogSlot.advisor.id}`, 'DELETE');
		if (!res.ok) return void (errorMsg = res.message ?? res.error ?? t('advisors.ui_error_fire'));
		apply(res);
		closeDialog();
	}

	// Player-triggered promotion: no confirmation, fires directly like repair.
	async function doPromote(slot: Slot) {
		if (!slot.advisor) return;
		const res = await send(`/advisors/${slot.advisor.id}/promote`, 'POST');
		if (!res.ok) return void (pageError = res.message ?? t('advisors.ui_error_promote'));
		pageError = null;
		apply(res);
	}

	onMount(() => {
		api<{ slots: Slot[]; slotInfo: SlotInfo; junior_upkeep: number }>('/advisors')
			.then((d) => {
				slots = d.slots;
				slotInfo = d.slotInfo;
				juniorUpkeep = d.junior_upkeep;
				carousel.count = d.slots.length;
			})
			.catch((e) => {
				if (!(e instanceof ApiError && e.status === 401)) loadError = String(e.message ?? e);
			});
	});
</script>

{#snippet ccChip(level: number)}
	<div class="prereq-cc-chip">
		<img class="prereq-cc-img" src="{BACKEND_URL}/img/buildings/command-center.webp" alt="CC" onerror={fallbackImg} />
		<span class="prereq-cc-label">CC Lv{level}</span>
	</div>
{/snippet}

{#snippet roleChip(key: string)}
	{#if ROLES.includes(key)}
		<EntityChip type="advisor" entityKey="advisor_{key}" label={t(`advisors.${key}`)} tooltip={chipTip(key)} />
	{/if}
{/snippet}

{#if loadError}<p class="error load-error" role="alert">{loadError}</p>{/if}

<div class="advisors-page" {@attach carousel.attach}>
	<div class="advisors-header">
		<h2>{t('advisors.ui_title')}</h2>
		<div style="display:flex;gap:0.5rem;flex-wrap:wrap">
			{#each slots.filter((s) => s.state === 'active' && s.advisor) as s (s.key)}
				<span class="ap-chip"><strong>+{s.advisor?.ap_per_tick} AP</strong></span>
			{/each}
		</div>
		<span class="slot-badge">
			{t('advisors.ui_slots')}: <strong>{slotInfo.used}/{slotInfo.max}</strong>
			<span>(CC Lv{slotInfo.cc_level})</span>
		</span>
	</div>

	{#if pageError}<p class="dialog-error" role="alert">{pageError}</p>{/if}

	<div class="carousel-wrapper">
		<button class="carousel-arrow" onclick={carousel.prev} disabled={carousel.index === 0} aria-label={t('advisors.ui_prev')}
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
				{#each slots as slot, i (slot.key)}
					{@const role = roleOf(slot)}
					<div
						class="advisor-card"
						class:advisor-card--locked={slot.state === 'locked'}
						class:advisor-card--current={i === carousel.index}
					>
						<!-- Section 1: building prerequisite -->
						<div class="advisor-card__prereq">
							<span class="prereq-section-label">{t('advisors.ui_prereq')}</span>
							{#if slot.key === 'engineer'}
								<div class="prereq-dual prereq-dual--solo">{@render ccChip(1)}</div>
							{:else}
								<div class="prereq-dual">
									{@render ccChip(slot.cc_required)}
									<span class="prereq-plus">+</span>
									<div class="prereq-main">
										<img
											class="prereq-main-img"
											src="{BACKEND_URL}/img/buildings/{PREREQ[role]?.slug}.webp"
											alt={PREREQ[role]?.name}
											onerror={fallbackImg}
										/>
										<span class="prereq-main-name">{PREREQ[role]?.name ?? ''}</span>
									</div>
								</div>
							{/if}
						</div>

						<!-- Section 2: portrait -->
						<div class="advisor-portrait" style={portraitStyle(role)}>
							{#if !PORTRAITS[role]}
								<svg
									class="advisor-portrait-placeholder"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									stroke-width="1"
									stroke-linecap="round"
									stroke-linejoin="round"
									aria-hidden="true"
								>
									<circle cx="12" cy="8" r="4" />
									<path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
								</svg>
							{/if}
						</div>

						<div class="advisor-info">
							<div class="advisor-name-row">
								<span class="advisor-name">{@render roleChip(role)}</span>
								{#if slot.advisor}
									<span class="rank-badge rank-badge--{slot.advisor.rank}">{slot.advisor.rank_name}</span>
								{/if}
							</div>

							{#if slot.advisor}
								<span class="advisor-subtitle">
									<span class="ap-chip ap-chip--neutral"><span class="res-amount">{slot.advisor.ap_per_tick}</span> AP</span>
								</span>
							{:else if slot.state === 'locked'}
								<span class="advisor-subtitle">{t('advisors.ui_locked')}</span>
							{:else if slot.is_path_open}
								<span class="advisor-subtitle"
									>{slot.preview_advisor_key ? t('advisors.ui_vacant') : t('advisors.ui_path_pending')}</span
								>
							{:else if slot.state === 'empty'}
								<span class="advisor-subtitle">{t('advisors.ui_vacant')}</span>
							{/if}

							<div class="advisor-stats">
								{#if slot.advisor}
									{@const a = slot.advisor}
									<div style="display:contents">
										<div class="stat-row">
											<span class="stat-label">{t('advisors.ui_ticks')}</span>
											<span class="stat-value">{a.active_ticks}</span>
										</div>
										<div class="stat-row">
											<span class="stat-label">{t('advisors.dialog_upkeep')}</span>
											<span class="res-chip res-Cr"><span class="res-abbr">CR</span><span class="res-amount">{a.upkeep}</span></span>
										</div>
										<div class="advisor-progress-wrap">
											<div class="stat-row">
												<span class="stat-label">{t('advisors.ui_promotion')}</span>
												<span class="stat-label"
													>{a.is_max_rank ? t('advisors.ui_max') : `${a.active_ticks}/${a.next_rank_ticks}`}</span
												>
											</div>
											<div class="advisor-progress">
												<div class="advisor-progress-fill" style="width: {a.progress_pct}%"></div>
											</div>
										</div>
										<div class="advisor-card-footer">
											<span
												class="advisor-status"
												class:advisor-status--unavailable={a.is_unavailable}
												class:advisor-status--active={!a.is_unavailable}
												>{a.is_unavailable
													? t('advisors.ui_inactive_until', { tick: a.unavailable_until_tick ?? '' })
													: t('advisors.ui_active')}</span
											>
											{#if !a.is_max_rank && a.next_rank_ticks !== null && a.active_ticks >= a.next_rank_ticks}
												<button class="btn-hire" onclick={() => doPromote(slot)}>{t('advisors.ui_promote')}</button>
											{/if}
											<button class="btn-fire" onclick={() => openDialog(slot, 'fire')}>{t('advisors.dialog_fire')}</button>
										</div>
									</div>
								{:else if slot.is_path_open && slot.state !== 'locked'}
									<p class="path-open-guidance">{t('advisors.ui_path_guidance')}</p>
								{:else if slot.state === 'empty'}
									<div style="display:contents">
										<div class="stat-row">
											<span class="stat-label">{t('advisors.ui_hire_cost')}</span>
											<span class="res-chip res-Cr"
												><span class="res-abbr">CR</span><span class="res-amount">{slot.hire_cost}</span></span
											>
										</div>
										<!-- Blade showed a hardcoded 4 here; junior_ap is the configured value the hire dialog uses. -->
										<div class="stat-row">
											<span class="stat-label">{t('advisors.ui_junior_ap')}</span>
											<span class="ap-chip ap-chip--neutral"><span class="res-amount">{slot.junior_ap}</span> AP</span>
										</div>
										<div class="stat-row">
											<span class="stat-label">{t('advisors.dialog_upkeep')}</span>
											<span class="res-chip res-Cr"><span class="res-abbr">CR</span><span class="res-amount">{juniorUpkeep}</span></span>
										</div>
										<div class="advisor-card-footer">
											<span class="advisor-status advisor-status--empty">{t('advisors.ui_vacant')}</span>
											<button class="btn-hire" onclick={() => openDialog(slot, 'hire')}>{t('advisors.dialog_hire')}</button>
										</div>
									</div>
								{:else if slot.state === 'locked'}
									<div class="locked-box">
										<svg
											width="28"
											height="28"
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											stroke-width="1.5"
											stroke-linecap="round"
											stroke-linejoin="round"
											aria-hidden="true"
										>
											<rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
											<path d="M7 11V7a5 5 0 0 1 10 0v4" />
										</svg>
										<span class="advisor-status advisor-status--locked">{t('advisors.ui_locked')}</span>
										<span class="locked-req">{t('advisors.ui_cc_required', { level: slot.cc_required })}</span>
									</div>
								{/if}
							</div>
						</div>

						<!-- Section 3: unlocked effects -->
						<div class="advisor-card__effects">
							<span class="effects-section-label">{t('advisors.ui_effects')}</span>
							<span class="effects-text">{effects(slot)}</span>
						</div>
					</div>
				{/each}
			</div>
		</div>

		<button
			class="carousel-arrow"
			onclick={carousel.next}
			disabled={carousel.index >= slots.length - 1}
			aria-label={t('advisors.ui_next')}>&#8250;</button
		>
	</div>

	<!-- Pagination dots (mobile only, hidden on desktop via CSS) -->
	<div class="carousel-dots">
		{#each slots as slot, i (slot.key)}
			<button
				class="carousel-dot"
				class:carousel-dot--active={i === carousel.index}
				onclick={() => carousel.goTo(i)}
				aria-label={slot.name}
			></button>
		{/each}
	</div>

	<dialog class="advisor-dialog" bind:this={dialog} onclose={() => (dialogSlot = null)}>
		{#if dialogSlot && dialogKind === 'hire'}
			{@const s = dialogSlot}
			<h3>{t('advisors.dialog_hire_title')}</h3>
			<div class="dialog-advisor">
				<div class="dialog-portrait" style={portraitStyle(s.key)}></div>
				<div class="dialog-advisor-info">
					<div class="dialog-advisor-name">
						<strong>{@render roleChip(s.key)}</strong>
						<span class="dialog-rank-badge">{t('advisors.dialog_rank_junior')}</span>
					</div>
					<p class="dialog-advisor-desc">{s.desc}</p>
				</div>
			</div>
			<dl class="dialog-stats">
				<div class="dialog-stat">
					<dt>AP</dt>
					<dd class="dialog-stat-positive">+{s.junior_ap} {t('advisors.dialog_ap_label')}</dd>
				</div>
				<div class="dialog-stat">
					<dt>{t('advisors.dialog_cost_once')}</dt>
					<dd><span class="res-chip res-Cr"><span class="res-abbr">CR</span><span class="res-amount">{s.hire_cost}</span></span></dd>
				</div>
				<div class="dialog-stat">
					<dt>{t('advisors.dialog_upkeep')}</dt>
					<dd>
						<span class="res-chip res-Cr"><span class="res-abbr">CR</span><span class="res-amount">{s.junior_upkeep}</span></span>
					</dd>
				</div>
			</dl>
			{#if s.building_warning}<div class="dialog-warning">{s.building_warning}</div>{/if}
			<div class="dialog-error">{errorMsg ?? ''}</div>
			<div class="dialog-actions">
				<button class="btn-cancel" onclick={closeDialog}>{t('advisors.dialog_cancel')}</button>
				<button class="btn-confirm-hire" onclick={doHire}>{t('advisors.dialog_hire')}</button>
			</div>
		{:else if dialogSlot}
			<h3>{t('advisors.dialog_fire_title')}</h3>
			<p class="dialog-cost"><strong>{@render roleChip(dialogSlot.key)}</strong> {t('advisors.dialog_fire_confirm')}</p>
			<div class="dialog-error">{errorMsg ?? ''}</div>
			<div class="dialog-actions">
				<button class="btn-cancel" onclick={closeDialog}>{t('advisors.dialog_cancel')}</button>
				<button class="btn-confirm-fire" onclick={doFire}>{t('advisors.dialog_fire')}</button>
			</div>
		{/if}
	</dialog>
</div>

<style>
	.load-error {
		padding: var(--space-md);
	}
	/* Was inline style in Blade. */
	.locked-box {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		flex: 1;
		gap: 0.5rem;
		color: #bbb;
		text-align: center;
		padding: 0.5rem 0;
	}
	.locked-req {
		font-size: 0.7rem;
		color: #bbb;
	}
</style>
