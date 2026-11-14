<script lang="ts">
	// Port of resources/views/colony/bar.blade.php incl. its inline barPage() component (ADR 0006 stage 4d).
	import '#lib/styles/swipe.css';
	import '#lib/styles/colony-shared.css';
	import '#lib/styles/cantina.css';
	import { onMount } from 'svelte';
	import { fade } from 'svelte/transition';
	import { api, ApiError, BACKEND_URL } from '#lib/stores/auth.ts';
	import { refreshShell, setAp } from '#lib/stores/shell.ts';
	import { t } from '#lib/i18n.ts';
	import FirstVisit from '#lib/components/FirstVisit.svelte';
	import CantinaDialog from './CantinaDialog.svelte';
	import KnowledgeResultLine, { type KnowledgeResult } from './KnowledgeResultLine.svelte';

	type Pos = { left: number; top: number };
	type Char = { slug?: string; name?: string; role?: string; game_role?: string };
	type Offer = { id: number; visit_id: number | null; expires_tick: number; get_resource_id: number; give_resource_id: number };
	type Advantage = { total_text?: string; lines: { label: string; percent_text: string }[]; hints: string[]; summary?: string };
	type Dialog = {
		fixed_price: boolean;
		fixed_price_note?: string;
		give_amount: number;
		credits_give: boolean;
		base_credits_text?: string;
		base_give: number;
		base_get: number;
		give_label: string;
		get_label: string;
		advantage: Advantage;
		negotiation_percent: number | null;
		amount_safe: number;
		plus_safe: number;
		amount_negotiated: number | null;
		plus_negotiated: number | null;
		price_stays_text?: string;
		can_negotiate: boolean;
		chance: { total_percent: number; lose_percent: number; breakdown: string };
		negotiation_sum: string;
	};
	type Encounter = { id: number; type: 'wager' | 'auction' | 'contract'; give_resource_id: number; give_amount: number; credits_amount: number };
	// selectable=false: the knowledge cannot take bonus AP now ("wartet auf <blocked_label>").
	type Knowledge = { id: number; key: string; name: string; selectable?: boolean; blocked_label?: string | null };
	// Tomas' advice state (relationship level drives the bonus; level 0 = no advice).
	type Bartender = {
		relationship_level: number;
		ap_bonus: number;
		// How often Tomas gives advice (every N Sols; null below relationship level 1) and in how many Sols the next one comes (0 = today).
		advice_every_sols: number | null;
		advice_in_sols: number;
		talked_today: boolean;
		today_result: KnowledgeResult | null;
		advice_open: boolean;
	};
	type Relationship = {
		key: string;
		name: string;
		level: number;
		level_cap: number;
		ap_spend: number;
		ap_for_levelup: number;
		met: boolean;
		// In the cantina today (scene dialog) — false for characters only reachable via the overview chips.
		present: boolean;
		// Variant C: one conversation per Sol; to_next_level = AP still missing for the next level.
		conversation: { cap: { spent: number; cap: number } | null; talked_today: boolean; to_next_level: number; options: ConversationOption[] };
	};
	// available=false: the cantina level does not allow this option yet; locked_line is the
	// character's in-voice line for it (resolved server-side), null when available.
	// ap = full price (always charged); wasted = AP beyond the next level that would be lost;
	// capped = not selectable today (already talked, or above the character's per-Sol limit).
	type ConversationOption = {
		key: string;
		name: string;
		ap: number;
		cost: number;
		wasted: number;
		capped?: boolean;
		available: boolean;
		requires_cantina_level: number;
		locked_line_key: string | null;
		locked_line: string | null;
	};
	type Data = {
		barLevel: number;
		offers: Offer[];
		offerTerms: Record<string, { get_amount: number }>;
		offerDialogs: Record<string, Dialog>;
		negotiatedOffers: Record<string, boolean>;
		tradeAdvantage: Advantage | null;
		merchantAdvantage: Advantage | null;
		merchantVisit: { id: number; tick_end: number; was_visited: boolean } | null;
		merchantItems: { id: number; label: string; price_credits: number; cost_credits: number; sold: boolean }[];
		merchantForecast: { sols: number; categories: string[] | null } | null;
		hotspots: Record<string, Record<'desktop' | 'tablet' | 'mobile', Pos>>;
		characterAssignment: Record<string, Char>;
		characters: Record<string, Char>;
		firstVisit: boolean;
		offerApCost: number;
		negotiateApCost: number;
		encounter: Encounter | null;
		storyEncounterSlug: string | null;
		concern: { id: number; character_slug: string } | null;
		concernHasFailureLine: boolean;
		concernApCost: number | null;
		informationEncounter: { id: number; character_slug: string; outcome_key: string } | null;
		informationOutcomeLines: Record<string, string>;
		knowledgeOptions: Knowledge[];
		devaKnowledgeChoices: Knowledge[];
		relationships: Relationship[];
		bartender: Bartender;
		conversationOptionCantinaLevel: Record<string, number>;
		relationshipMaxLevel: number;
	};
	type Result = { ok: boolean; message?: string; error?: string; [k: string]: unknown };

	// resources.abbreviation — the resource bar's .res-{abbr} chip classes.
	const ABBR: Record<number, string> = { 1: 'Cr', 3: 'Rg', 4: 'Co', 5: 'Or' };
	const RES_LABEL: Record<number, string> = {
		1: 'resources.res_credits',
		3: 'resources.res_regolith',
		4: 'resources.res_werkstoffe',
		5: 'resources.res_organika'
	};
	// spot_0 is Corvan's special inventory; offers and events share the other five, cycled.
	const SPOTS = ['spot_1', 'spot_2', 'spot_3', 'spot_4', 'spot_5'];
	const portrait = (slug: string) => `${BACKEND_URL}/img/characters/${slug}`;

	let d = $state<Data | null>(null);
	let loadError = $state('');
	let activeModal = $state<string | null>(null);
	let current = $state(0);
	let loading = $state(false);
	let toast = $state<{ msg: string; type: 'info' | 'error'; modal: string | null } | null>(null);
	let buyLoading = $state(false);
	// Offers
	let accepted = $state<Record<number, boolean>>({});
	let negotiated = $state<Record<string, boolean>>({});
	let negotiateFailed = $state<Record<number, string>>({});
	let offerError = $state<Record<number, string | null>>({});
	// Encounter pool, bartender, concern, information pool
	let encounterResolved = $state(false);
	let encounterResult = $state<'won' | 'lost' | null>(null);
	let encounterError = $state<string | null>(null);
	let bartenderKnowledgeId = $state<number | null>(null);
	let bartenderTier = $state<number | null>(null);
	let bartenderResult = $state<KnowledgeResult | null>(null);
	let bartenderError = $state<string | null>(null);
	let bartenderLoading = $state(false);
	let concernKnowledgeId = $state<number | null>(null);
	let concernResolved = $state(false);
	let concernSuccess = $state<boolean | null>(null);
	let concernError = $state<string | null>(null);
	let concernKnowledgeResult = $state<KnowledgeResult | null>(null);
	let concernLoading = $state(false);
	let informationKnowledgeId = $state<number | null>(null);
	let informationResolved = $state(false);
	let informationOutcomeKey = $state<string | null>(null);
	let informationError = $state<string | null>(null);
	let informationKnowledgeResult = $state<KnowledgeResult | null>(null);
	let informationLoading = $state(false);
	// Relationships
	let relationLoading = $state<Record<string, boolean>>({});
	let relationError = $state<Record<string, string>>({});
	// AP lost in today's conversation per character (shown in the done-today line).
	let relationWasted = $state<Record<string, number>>({});

	// ── Scene (which character stands where) — mirror of the @php block in bar.blade.php ──

	const scene = $derived.by(() => {
		if (!d) return null;
		const data = d;
		const n = data.offers.length;
		const offers = data.offers.map((offer, idx) => {
			const slot = SPOTS[idx % SPOTS.length];
			const corvan = offer.visit_id !== null;
			let char: Char | null = corvan ? null : (data.characterAssignment[slot] ?? null);
			if (char?.game_role === 'story_hook') char = null;
			return {
				offer,
				slot,
				corvan,
				char,
				slug: corvan ? 'merchant' : (char?.slug ?? 'stranger'),
				name: corvan ? t('colony.merchant_title') : (char?.name ?? '???'),
				role: corvan ? `${t('colony.merchant_until_sol')} ${offer.expires_tick}` : (char?.role ?? ''),
				// bar_trade figures get a personal line; the accept call credits them in the codex.
				flavor: !corvan && char?.game_role === 'bar_trade' ? `colony.bar_trade_flavor_${char.slug}` : null,
				acceptSlug: !corvan && char ? (char.slug ?? null) : null
			};
		});
		const enc = data.encounter;
		const encSlug = enc?.type === 'wager' ? 'gambler' : enc?.type === 'auction' ? 'scrap_dealer' : null;
		const story = data.storyEncounterSlug ? (data.characters[data.storyEncounterSlug] ?? null) : null;
		const concern = data.concern ? (data.characters[data.concern.character_slug] ?? null) : null;
		const info = data.informationEncounter ? (data.characters[data.informationEncounter.character_slug] ?? null) : null;
		const at = (k: number) => SPOTS[k % SPOTS.length];
		return {
			offers,
			encounter: enc && {
				slug: encSlug,
				char: encSlug ? (data.characters[encSlug] ?? null) : null,
				name: (encSlug && data.characters[encSlug]?.name) || t('colony.bar_encounter_contract_heading'),
				heading: t(`colony.bar_encounter_${enc.type}_heading`),
				body: t(`colony.bar_encounter_${enc.type}_body`),
				slot: at(n)
			},
			story: story && { char: story, slot: at(n + (enc ? 1 : 0)) },
			concern: data.concern && { char: concern, slot: at(n + (enc ? 1 : 0) + (story ? 1 : 0)) },
			info: data.informationEncounter && { char: info, slot: at(n + (enc ? 1 : 0) + (story ? 1 : 0) + (data.concern ? 1 : 0)) }
		};
	});

	// Hotspot position per device (data/cantina_hotspots.json), applied via CSS variables.
	function spotStyle(slot: string) {
		const p = d?.hotspots[slot];
		const v = (dev: 'desktop' | 'tablet' | 'mobile') => p?.[dev] ?? { left: 50, top: 50 };
		return (
			`--hs-dl:${v('desktop').left}%;--hs-dt:${v('desktop').top}%;` +
			`--hs-tl:${v('tablet').left}%;--hs-tt:${v('tablet').top}%;` +
			`--hs-ml:${v('mobile').left}%;--hs-mt:${v('mobile').top}%`
		);
	}

	const panelCount = $derived(d && (d.merchantVisit || d.merchantItems.length || d.offers.length) ? 4 : 1);
	const empty = $derived(
		!!d && !d.offers.length && !d.merchantVisit && !d.encounter && !d.concern && !d.informationEncounter
	);

	// ── Swipe (port of swipeCarousel in public/js/swipe.js) ──
	let x0 = 0;
	let y0 = 0;
	const touchstart = (e: TouchEvent) => ((x0 = e.touches[0].clientX), (y0 = e.touches[0].clientY));
	function touchend(e: TouchEvent) {
		const dx = e.changedTouches[0].clientX - x0;
		const dy = e.changedTouches[0].clientY - y0;
		if (Math.abs(dx) < 60 || Math.abs(dy) > Math.abs(dx)) return;
		current = Math.max(0, Math.min(panelCount - 1, current + (dx < 0 ? 1 : -1)));
	}

	// ── Helpers ──

	// A toast belongs to the dialog that raised it; changing dialogs hides it.
	let toastTimer: ReturnType<typeof setTimeout>;
	function showToast(msg: string, type: 'info' | 'error' = 'info') {
		clearTimeout(toastTimer);
		toast = { msg, type, modal: activeModal };
		toastTimer = setTimeout(() => (toast = null), 3500);
	}
	function open(modal: string | null) {
		activeModal = modal;
		toast = null;
	}

	async function post(path: string, body: object = {}): Promise<Result> {
		return api<Result>(path, { method: 'POST', body, allowError: true }).catch(() => ({
			ok: false,
			message: t('colony.network_error')
		}));
	}
	const fail = (res: Result) => res.message ?? res.error ?? t('colony.network_error');

	/** Header resync after any action that moved AP or resources. */
	function sync(ap?: unknown) {
		if (typeof ap === 'number') setAp(ap);
		refreshShell();
	}

	// ── Merchant ──

	function openMerchant() {
		open('merchant');
		const visit = d?.merchantVisit;
		if (visit && !visit.was_visited) {
			post(`/colony/merchant/visit/${visit.id}/open`); // fire-and-forget
			visit.was_visited = true;
		}
	}

	async function buyItem(id: number) {
		buyLoading = true;
		const res = await post(`/colony/merchant/buy/${id}`);
		buyLoading = false;
		if (res.ok) {
			const item = d?.merchantItems.find((i) => i.id === id);
			if (item) item.sold = true;
			showToast(res.message ?? t('colony.merchant_buy_success'), 'info');
			sync();
		} else showToast(res.message ?? t('colony.merchant_buy_error'), 'error');
	}

	// ── Offers ──

	// Resolved = accepted, or the negotiation was lost (offer gone). A won negotiation alone is not.
	const resolved = (id: number) => !!accepted[id] || negotiateFailed[id] !== undefined;

	async function accept(id: number, slug: string | null) {
		loading = true;
		offerError[id] = null;
		const res = await post(`/colony/bar/accept/${id}`, { character_slug: slug });
		loading = false;
		if (!res.ok) return void (offerError[id] = fail(res));
		accepted[id] = true;
		sync(res.ap_available);
		refreshRelationships(slug);
	}

	// A won negotiation flags the offer (bonus on the Get side); Annehmen then books it at 0 AP.
	async function negotiate(id: number, name: string) {
		loading = true;
		offerError[id] = null;
		const res = await post(`/colony/bar/negotiate/${id}`);
		loading = false;
		if (!res.ok) return void (offerError[id] = fail(res));
		sync(res.ap_available);
		if (res.success) negotiated[id] = true;
		else
			negotiateFailed[id] = t('colony.bar_offer_negotiate_failed', {
				name,
				resource: t(RES_LABEL[res.give_resource_id as number] ?? '') || '?',
				ap: res.ap_spent as number
			});
	}

	// ── Encounter pool / bartender / concern / information ──

	async function acceptEncounter() {
		if (!d?.encounter) return;
		loading = true;
		encounterError = null;
		const res = await post(`/colony/bar/accept-encounter/${d.encounter.id}`);
		loading = false;
		if (!res.ok) return void (encounterError = fail(res));
		encounterResolved = true;
		if (res.type === 'wager') encounterResult = res.won ? 'won' : 'lost';
		sync(res.ap_available);
		refreshRelationships(scene?.encounter?.slug ?? null);
	}

	async function talkToBartender() {
		if (bartenderLoading || !bartenderKnowledgeId) return;
		bartenderLoading = true;
		bartenderError = null;
		const res = await post('/colony/bar/talk-to-bartender', { knowledge_id: bartenderKnowledgeId });
		bartenderLoading = false;
		if (res.ok) {
			// Tomas' line follows his relationship level (1–5) onto the four bartender_dialog_tier_* lines.
			bartenderTier = Math.min(3, Math.ceil((d?.bartender.relationship_level ?? 0) / 2));
			bartenderResult = (res.knowledge_result as KnowledgeResult) ?? null;
			if (d) d.bartender = { ...d.bartender, talked_today: true, today_result: bartenderResult, advice_open: false };
			sync();
			refreshKnowledgeState();
		} else if (res.error === 'cooldown_active') {
			// Fallback only (e.g. second tab): the popup normally knows this before the click.
			bartenderError = t('colony.bartender_error_cooldown');
			refreshKnowledgeState();
		} else bartenderError = res.message ?? t('colony.bartender_error_generic');
	}

	/** Opening Tomas' dialog: fresh state, the done-today view comes from d.bartender. */
	function openBartender() {
		open('bartender');
		bartenderTier = null;
		bartenderResult = null;
		bartenderError = null;
	}

	const optionLabel = (opt: Knowledge) =>
		opt.selectable === false ? t('colony.knowledge_option_waiting', { name: opt.name, reason: opt.blocked_label ?? '?' }) : opt.name;
	const firstSelectable = (opts: Knowledge[]) => opts.find((o) => o.selectable !== false)?.id ?? null;
	const isSelectable = (opts: Knowledge[], id: number | null) => id !== null && opts.some((o) => o.id === id && o.selectable !== false);

	// After a bonus or a Tomas relationship step: knowledge selectability and Tomas' state
	// (bonus, advice open) changed server-side — reload them without touching the scene.
	async function refreshKnowledgeState() {
		const r = await api<Data>('/cantina').catch(() => null);
		if (!r || !d) return;
		d.knowledgeOptions = r.knowledgeOptions;
		d.devaKnowledgeChoices = r.devaKnowledgeChoices;
		d.bartender = r.bartender;
		if (!isSelectable(r.knowledgeOptions, bartenderKnowledgeId)) bartenderKnowledgeId = firstSelectable(r.knowledgeOptions);
		if (!isSelectable(r.knowledgeOptions, concernKnowledgeId)) concernKnowledgeId = firstSelectable(r.knowledgeOptions);
		if (!isSelectable(r.devaKnowledgeChoices, informationKnowledgeId))
			informationKnowledgeId = firstSelectable(r.devaKnowledgeChoices);
	}

	async function resolveConcern() {
		if (!d?.concern || concernLoading) return;
		concernLoading = true;
		concernError = null;
		const res = await post(`/colony/bar/concern/${d.concern.id}`, {
			knowledge_id: d.concern.character_slug === 'mechanic' ? concernKnowledgeId : null
		});
		concernLoading = false;
		if (!res.ok) return void (concernError = fail(res));
		concernResolved = true;
		concernSuccess = res.success as boolean;
		concernKnowledgeResult = (res.knowledge_result as KnowledgeResult) ?? null;
		sync(res.ap_available);
		refreshRelationships(d.concern.character_slug);
		if (concernKnowledgeResult) refreshKnowledgeState();
	}

	// Only Deva's knowledge_boost outcome needs a choice; Lenn's target is fixed.
	const infoNeedsChoice = $derived(
		d?.informationEncounter?.character_slug === 'veteran' && d.informationEncounter.outcome_key === 'knowledge_boost'
	);
	async function resolveInformation() {
		if (!d?.informationEncounter || informationLoading) return;
		informationLoading = true;
		informationError = null;
		const res = await post(`/colony/bar/information-encounter/${d.informationEncounter.id}`, {
			knowledge_id: infoNeedsChoice ? informationKnowledgeId : null
		});
		informationLoading = false;
		if (!res.ok) return void (informationError = fail(res));
		informationResolved = true;
		informationOutcomeKey = (res.outcome_key as string) ?? null;
		informationKnowledgeResult = (res.knowledge_result as KnowledgeResult) ?? null;
		sync();
		if (informationKnowledgeResult) refreshKnowledgeState();
		refreshRelationships(d.informationEncounter.character_slug);
	}

	// ── Relationships (path depth of met characters, T41) ──
	// Shown as the relationship column inside each character dialog; the overview
	// under the scene only lists met characters and opens their dialog.

	const relFor = (slug: string | null) => (slug ? (d?.relationships.find((r) => r.key === slug) ?? null) : null);

	/** Character behind the open dialog (null for Corvan or a guest without a character). */
	const activeSlug = $derived.by((): string | null => {
		const m = activeModal;
		if (!m || !d || !scene) return null;
		if (m === 'bartender') return 'bartender';
		if (m.startsWith('rel_')) return m.slice(4);
		if (m.startsWith('offer_')) return scene.offers.find((o) => `offer_${o.offer.id}` === m)?.acceptSlug ?? null;
		if (m === 'encounter') return scene.encounter?.slug ?? null;
		if (m === 'story') return d.storyEncounterSlug;
		if (m === 'concern') return d.concern?.character_slug ?? null;
		if (m === 'information') return d.informationEncounter?.character_slug ?? null;
		return null;
	});
	// Every character in the scene today has a relationship row (level 0 before the first talk).
	const hasRelation = $derived(!!activeSlug && !!d?.characters[activeSlug] && !!relFor(activeSlug));

	/** Scene dialog of a character standing in the cantina today, else null. */
	function modalFor(slug: string): string | null {
		if (!d || !scene) return null;
		if (slug === 'bartender') return 'bartender';
		const offer = scene.offers.find((o) => o.acceptSlug === slug);
		if (offer) return `offer_${offer.offer.id}`;
		if (scene.encounter?.slug === slug) return 'encounter';
		if (d.storyEncounterSlug === slug) return 'story';
		if (d.concern?.character_slug === slug) return 'concern';
		if (d.informationEncounter?.character_slug === slug) return 'information';
		return null;
	}

	// The first trade/encounter/talk marks a character as met server-side; reload the
	// relationship list so the overview chips pick the character up.
	async function refreshRelationships(slug: string | null) {
		if (!slug || relFor(slug)?.met || !d?.characters[slug]) return;
		const r = await api<Data>('/cantina').catch(() => null);
		if (r && d) d.relationships = r.relationships;
	}

	function conversationBlock(opt: ConversationOption) {
		if (!opt.available) return opt.locked_line ?? '';
		return opt.capped ? t('pathdepth.ui_conversation_cap_tooltip') : null;
	}

	/** In-voice line of the lowest locked option — shown once under the options. */
	function lockedLine(rel: Relationship): string | null {
		const locked = rel.conversation.options.filter((o) => !o.available && o.locked_line);
		if (!locked.length) return null;
		return locked.reduce((a, b) => (b.requires_cantina_level < a.requires_cantina_level ? b : a)).locked_line;
	}

	async function investRelation(rel: Relationship, opt: ConversationOption) {
		if (relationLoading[rel.key] || !d) return;
		relationLoading[rel.key] = true;
		relationError[rel.key] = '';
		const res = await post(`/colony/path-depth/character/${rel.key}/invest`, { ap: opt.cost });
		relationLoading[rel.key] = false;
		if (!res.ok) {
			relationError[rel.key] = (res.error === 'conversation_done_today'
				? t('pathdepth.error_conversation_done_today')
				: res.message || res.error || '') as string;
			// Talked elsewhere (other tab/dialog): reload so the panel switches to its done-today state.
			if (res.error === 'conversation_done_today') {
				const r = await api<Data>('/cantina').catch(() => null);
				if (r && d) d.relationships = r.relationships;
			}
			return;
		}
		relationWasted[rel.key] = Number(res.wasted ?? 0);
		Object.assign(rel, { level: res.level, ap_spend: res.ap_spend, ap_for_levelup: res.ap_for_levelup, level_cap: res.level_cap });
		if (res.conversation) rel.conversation = res.conversation as Relationship['conversation'];
		sync(res.apAvailable);
		// Tomas' bonus follows his relationship level: the talk button must show the new number.
		if (rel.key === 'bartender') refreshKnowledgeState();
		// First talk with a present character: the server now lists them as met.
		if (!rel.met) {
			rel.met = true;
			const r = await api<Data>('/cantina').catch(() => null);
			if (r && d) d.relationships = r.relationships;
		}
	}

	onMount(() => {
		api<Data>('/cantina')
			.then((r) => {
				d = r;
				negotiated = { ...r.negotiatedOffers };
				bartenderKnowledgeId = firstSelectable(r.knowledgeOptions);
				concernKnowledgeId = firstSelectable(r.knowledgeOptions);
				informationKnowledgeId = firstSelectable(r.devaKnowledgeChoices);
			})
			.catch((e) => {
				if (!(e instanceof ApiError && e.status === 401)) loadError = String(e.message ?? e);
			});
	});
</script>

{#snippet chip(id: number | string, amount: number | string)}
	{@const abbr = typeof id === 'number' ? (ABBR[id] ?? '?') : id}
	<span class="res-chip res-{abbr}"><span class="res-abbr">{abbr}</span><span class="res-amount">{amount}</span></span>
{/snippet}

{#snippet toastFor(modal: string)}
	{#if toast && toast.modal === modal}
		<div class="merchant-toast merchant-toast--{toast.type}" aria-live="polite" role="status" transition:fade>{toast.msg}</div>
	{/if}
{/snippet}

{#snippet hotspotImg(slug: string, alt: string)}
	<img class="hotspot-portrait" src="{portrait(slug)}.webp" srcset="{portrait(slug)}.webp 1x, {portrait(slug)}_lg.webp 2x" {alt} />
{/snippet}

{#snippet closeButton()}
	<div class="row-end">
		<button class="tile-action-btn tile-action-btn--secondary auto" onclick={() => open(null)}>
			<span class="tile-action-btn__body">{t('colony.story_encounter_close')}</span>
		</button>
	</div>
{/snippet}

<!-- Talk button after today's advice: off, with a chip naming when the next advice comes. -->
{#snippet bartenderDoneButton(bt: Bartender)}
	<div class="row-end">
		<button class="tile-action-btn auto" disabled data-bartender-done>
			<span class="tile-action-btn__body">{t('colony.bartender_talk_button')}</span>
			<span class="ap-chip ap-cost-chip ap-chip--neutral"
				>{t('pathdepth.bartender_advice_next', { n: Math.max(1, bt.advice_in_sols || (bt.advice_every_sols ?? 1)) })}</span
			>
		</button>
	</div>
{/snippet}

<!-- How often Tomas gives advice (by relationship level); "today" only while advice is open —
     otherwise the disabled talk button's chip names the next Sol. -->
{#snippet bartenderAdviceInfo(bt: Bartender)}
	{#if bt.advice_every_sols !== null && bt.ap_bonus > 0}
		<p class="hint-line" data-bartender-advice-every>
			{bt.advice_every_sols <= 1 ? t('pathdepth.bartender_advice_daily') : t('pathdepth.bartender_advice_every', { n: bt.advice_every_sols })}
			{#if bt.advice_in_sols === 0 && !bt.talked_today}
				· <span data-bartender-advice-today>{t('pathdepth.bartender_advice_today')}</span>
			{/if}
		</p>
	{/if}
{/snippet}

{#snippet knowledgeSelect(label: string, options: Knowledge[], value: number | null, set: (v: number) => void)}
	<label class="muted-label">
		{label}
		<select value={value} onchange={(e) => set(Number((e.currentTarget as HTMLSelectElement).value))}>
			{#each options as opt (opt.id)}<option value={opt.id} disabled={opt.selectable === false}>{optionLabel(opt)}</option>{/each}
		</select>
	</label>
{/snippet}

<!-- Beziehung pflegen for the character behind the open dialog (T41). -->
{#snippet relationPanel()}
	{@const rel = relFor(activeSlug)}
	{@const max = d?.relationshipMaxLevel ?? 0}
	<section class="cantina-relation" data-relation={activeSlug}>
		{#if rel}
			<div class="cantina-relation__level">
				<span title={t('pathdepth.ui_level', { level: rel.level, max })}>{t(`pathdepth.relationship_level_${rel.level}`)}</span>
				<span class="cantina-relation__pips" aria-hidden="true">
					{#each Array.from({ length: max }, (_, i) => i) as i (i)}
						<span class="cantina-relation__pip" class:cantina-relation__pip--on={i < rel.level}></span>
					{/each}
				</span>
			</div>
			{#if rel.ap_for_levelup <= 0}
				<p class="cantina-relation__note">{t('pathdepth.ui_level_max')}</p>
			{:else}
				<div class="cantina-relation__progress">
					<span>{t('pathdepth.ui_invest_progress', { spent: rel.ap_spend, needed: rel.ap_for_levelup })}</span>
					<span class="cantina-relation__bar" aria-hidden="true"
						><span style="width: {Math.min(100, (rel.ap_spend / rel.ap_for_levelup) * 100)}%"></span></span
					>
				</div>
				{#if rel.conversation.talked_today}
					<!-- One conversation per Sol (variant C): options are off until the next Sol. -->
					<div class="bar-conversation" data-conversation-done>
						<p class="cantina-relation__note">{t('pathdepth.conversation_done_today')}</p>
						{#if relationWasted[rel.key]}
							<p class="bar-conversation-wasted" data-conversation-wasted-result>
								{t('pathdepth.conversation_wasted_result', { n: relationWasted[rel.key] })}
							</p>
						{/if}
						<div class="row-end">
							<span class="ap-chip ap-cost-chip ap-chip--neutral">{t('colony.bartender_next_sol')}</span>
						</div>
					</div>
				{:else}
					{@const wasteHint = rel.conversation.options.some((o) => o.available && !o.capped && o.wasted > 0)}
					<div class="bar-conversation">
						<p class="bar-conversation-caption">{t('pathdepth.ui_get_to_know')}</p>
						{#if wasteHint}
							<p class="bar-conversation-wasted" data-conversation-wasted-hint>
								{t('pathdepth.conversation_wasted_hint', { n: rel.conversation.to_next_level })}
							</p>
						{/if}
						{#each rel.conversation.options as opt (opt.key)}
							{@const block = conversationBlock(opt)}
							<button
								type="button"
								class="bar-conversation-btn"
								class:bar-conversation-btn--blocked={block !== null}
								class:bar-conversation-btn--locked={!opt.available}
								aria-disabled={block !== null && opt.available ? 'true' : undefined}
								disabled={!opt.available || relationLoading[rel.key]}
								title={block || undefined}
								data-option={opt.key}
								data-available={opt.available ? 'true' : 'false'}
								data-wasted={opt.wasted}
								onclick={() => block === null && investRelation(rel, opt)}
							>
								<span class="bar-conversation-btn__name"
									>{#if !opt.available}<i class="bi bi-lock-fill" aria-hidden="true"></i> {/if}{opt.name}</span
								>
								{#if opt.wasted > 0 && opt.available}
									<!-- Full price stays visible; the warn chip names the AP that would be lost. -->
									<span class="ap-chip ap-cost-chip bar-conversation-wasted-chip" data-wasted-chip
										>{t('pathdepth.conversation_wasted_chip', { ap: opt.ap, n: opt.wasted })}</span
									>
								{:else}
									<span class="ap-chip ap-cost-chip ap-chip--build" aria-hidden="true">{opt.ap} AP</span>
								{/if}
							</button>
						{/each}
					</div>
					{@const line = lockedLine(rel)}
					{#if line}<p class="cantina-relation__quote" data-locked-line>{line}</p>{/if}
				{/if}
			{/if}
			{#if relationError[rel.key]}<p class="bar-relationship-error" role="alert">{relationError[rel.key]}</p>{/if}
		{/if}
	</section>
{/snippet}

{#if loadError}<p class="error load-error" role="alert">{loadError}</p>{/if}

{#if d && scene}
	{@const data = d}
	<div class="bar-page">
		{#if data.barLevel < 1}
			<p>{t('colony.bar_no_building')}</p>
		{:else}
			<!-- svelte-ignore a11y_no_static_element_interactions (swipe shortcut; dots stay usable) -->
			<div class="cantina-viewport" ontouchstart={touchstart} ontouchend={touchend}>
				<div class="cantina-background-wrapper" style="transform: translateX(-{current * 22.222}%)">
					{#if data.merchantVisit}
						<button class="cantina-hotspot has-portrait hotspot-merchant hs" style={spotStyle('spot_0')} onclick={openMerchant}>
							<span class="hotspot-badge"><i class="bi bi-exclamation-lg"></i></span>
							{@render hotspotImg('merchant', t('colony.merchant_title'))}
							<span class="hotspot-label">{t('colony.merchant_title')}</span>
						</button>
					{/if}

					<!-- Guest barter offers + Corvan's commodity offers (his portrait, not a random guest) -->
					{#each scene.offers as o (o.offer.id)}
						<button
							class="cantina-hotspot hs"
							class:has-portrait={o.corvan || o.char}
							class:hotspot-corvan-offer={o.corvan}
							style={spotStyle(o.slot)}
							onclick={() => open(`offer_${o.offer.id}`)}
						>
							<span class="hotspot-pulse"></span>
							{#if o.corvan}
								<span class="hotspot-badge hotspot-badge--corvan" aria-hidden="true"><i class="bi bi-coin"></i></span>
								{@render hotspotImg('merchant', o.name)}
							{:else if o.char}
								{@render hotspotImg(o.char.slug ?? 'stranger', o.name)}
							{:else}
								<i class="bi bi-chat-right-text"></i>
							{/if}
							<span class="hotspot-label">{o.name}</span>
						</button>
					{/each}

					{#if scene.encounter}
						{@const e = scene.encounter}
						<button class="cantina-hotspot hs" class:has-portrait={e.char} style={spotStyle(e.slot)} onclick={() => open('encounter')}>
							<span class="hotspot-pulse"></span>
							{#if e.char && e.slug}{@render hotspotImg(e.slug, e.name)}{:else}<i class="bi bi-briefcase"></i>{/if}
							<span class="hotspot-label">{e.name}</span>
						</button>
					{/if}

					{#if scene.story && data.storyEncounterSlug}
						<button class="cantina-hotspot has-portrait hs" style={spotStyle(scene.story.slot)} onclick={() => open('story')}>
							<span class="hotspot-pulse"></span>
							{@render hotspotImg(data.storyEncounterSlug, scene.story.char.name ?? '???')}
							<span class="hotspot-label">{scene.story.char.name ?? '???'}</span>
						</button>
					{/if}

					{#if scene.concern && data.concern}
						{@const c = scene.concern}
						<button class="cantina-hotspot hs" class:has-portrait={c.char} style={spotStyle(c.slot)} onclick={() => open('concern')}>
							<span class="hotspot-pulse"></span>
							{#if c.char}{@render hotspotImg(data.concern.character_slug, c.char.name ?? '???')}{:else}<i class="bi bi-chat-dots"></i>{/if}
							<span class="hotspot-label">{c.char?.name ?? t('colony.bar_concern_heading')}</span>
						</button>
					{/if}

					{#if scene.info && data.informationEncounter}
						{@const c = scene.info}
						<button class="cantina-hotspot hs" class:has-portrait={c.char} style={spotStyle(c.slot)} onclick={() => open('information')}>
							<span class="hotspot-pulse"></span>
							{#if c.char}{@render hotspotImg(data.informationEncounter.character_slug, c.char.name ?? '???')}{:else}<i
									class="bi bi-broadcast"
								></i>{/if}
							<span class="hotspot-label">{c.char?.name ?? t('colony.bar_information_heading')}</span>
						</button>
					{/if}
				</div>

				<!-- Tomas behind the counter: always on screen, outside the swiped background. -->
				<button
					type="button"
					class="cantina-bartender"
					data-bartender-counter
					onclick={openBartender}
				>
					{#if data.bartender.advice_open}
						<!-- Advice open today (relationship level >= 1, not talked yet). -->
						<span class="hotspot-badge cantina-bartender__badge" data-advice-open title={t('colony.bartender_advice_open')}
							><i class="bi bi-lightbulb" aria-hidden="true"></i><span class="visually-hidden"
								>{t('colony.bartender_advice_open')}</span
							></span
						>
					{/if}
					<img class="cantina-bartender__portrait" src="{portrait('bartender')}.webp" srcset="{portrait('bartender')}.webp 1x, {portrait('bartender')}_lg.webp 2x" alt={data.characters.bartender?.name} />
					<span class="cantina-bartender__counter">
						<strong>{data.characters.bartender?.name}</strong>
						<span>{t('colony.bartender_counter_role')}</span>
					</span>
				</button>

				<div class="swipe-dots cantina-dots">
					{#each Array.from({ length: panelCount }, (_, i) => i) as i (i)}
						<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
						<span class="swipe-dot" class:swipe-dot--active={current === i} onclick={() => (current = i)}></span>
					{/each}
				</div>

				{#if empty}
					<div class="cantina-empty-hint"><p class="empty-text">{t('colony.bar_no_offers')}</p></div>
				{/if}
			</div>

			{#if data.tradeAdvantage?.summary}
				<div class="trade-advantage-bar">
					<i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
					<span>{data.tradeAdvantage.summary}</span>
				</div>
			{/if}

			<!-- Marktbericht (Konsul): announces Corvan's next visit — read-only planning info. -->
			{#if data.merchantForecast}
				{@const f = data.merchantForecast}
				<div class="market-report">
					<i class="bi bi-megaphone" aria-hidden="true"></i>
					<div class="market-report__text">
						<strong>{t('colony.merchant_forecast_title')}</strong>
						<span>{f.sols === 1 ? t('colony.merchant_forecast_tomorrow') : t('colony.merchant_forecast_in_sols', { sols: f.sols })}</span>
						{#if f.categories !== null}
							<span
								>{t('colony.merchant_forecast_inventory', {
									categories: f.categories.map((c) => t(`colony.merchant_category_${c}`)).join(', ')
								})}</span
							>
						{/if}
					</div>
				</div>
			{/if}

			{#if activeModal !== null}
				<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
				<div class="cantina-modal-backdrop backdrop" onclick={() => open(null)} transition:fade={{ duration: 150 }}></div>
			{/if}

			<div
				class="cantina-modal cantina-modal--{activeModal === null ? 'neutral' : 'economy'}"
				class:cantina-modal--wide={hasRelation && !activeModal?.startsWith('rel_')}
				class:open={activeModal !== null}
				hidden={activeModal === null}
			>
				<button onclick={() => open(null)} class="cantina-modal-close" aria-label={t('colony.cancel')}>&times;</button>

				{#if activeModal === 'merchant' && data.merchantVisit}
					<CantinaDialog slug="merchant" name={t('colony.merchant_title')} role="{t('colony.merchant_until_sol')} {data.merchantVisit.tick_end}">
						{@render toastFor('merchant')}
						{#if data.merchantAdvantage?.summary}<p class="trade-price-line">{data.merchantAdvantage.summary}</p>{/if}
						{#if data.merchantAdvantage?.hints.length}
							<div class="trade-hints">
								{#each data.merchantAdvantage.hints as hint, i (i)}<p class="trade-hint">{hint}</p>{/each}
							</div>
						{/if}
						<div class="merchant-items-bar scroll-list">
							{#each data.merchantItems as item (item.id)}
								<article class="merchant-item-bar" class:merchant-item-bar--sold={item.sold}>
									<div class="merchant-item-bar__label" title={item.label}>{item.label}</div>
									<div class="merchant-item-bar__row">
										<span class="merchant-item-bar__price">
											{#if item.price_credits < item.cost_credits}
												<s class="merchant-item-bar__base" title={t('colony.merchant_price_base_title', { price: item.cost_credits })}
													>{item.cost_credits}</s
												>
											{/if}
											{@render chip(1, item.price_credits)}
										</span>
										<button class="merchant-item-bar__buy" disabled={item.sold || buyLoading} onclick={() => buyItem(item.id)}>
											{item.sold ? t('colony.merchant_sold') : t('colony.merchant_buy')}
										</button>
									</div>
								</article>
							{/each}
						</div>
					</CantinaDialog>
				{/if}

				{#each scene.offers as o (o.offer.id)}
					{@const id = o.offer.id}
					{@const dlg = data.offerDialogs[id]}
					{#if activeModal === `offer_${id}`}
						<CantinaDialog slug={o.slug} name={o.name} role={o.role} relation={hasRelation ? relationPanel : undefined}>
							{@render toastFor(`offer_${id}`)}
							{#if o.flavor}<p class="flavor">{t(o.flavor)}</p>{/if}

							<!-- Offer dialog with source breakdown: every number from BarOfferDialogPresenter (shown == booked). -->
							{#if dlg.fixed_price}
								<div class="offer-terms offer-terms--fixed">
									<div class="offer-terms__swap">
										{@render chip(o.offer.give_resource_id, dlg.give_amount)}
										<span class="offer-terms__arrow" aria-hidden="true">→</span>
										{@render chip(o.offer.get_resource_id, data.offerTerms[id].get_amount)}
									</div>
									<p class="offer-note">{dlg.fixed_price_note}</p>
								</div>
							{:else}
								<div class="offer-terms">
									<div class="offer-terms__row offer-terms__row--base">
										<span class="offer-terms__label"
											>{dlg.credits_give ? t('colony.bar_offer_base_label_credits') : t('colony.bar_offer_base_label')}</span
										>
										<span class="offer-terms__value offer-terms__value--nowrap">
											{dlg.credits_give ? dlg.base_credits_text : `${dlg.base_give} ${dlg.give_label} → ${dlg.base_get} ${dlg.get_label}`}
										</span>
									</div>
									<div class="offer-terms__row">
										<span class="offer-terms__label">{t('colony.bar_offer_advantage_label')}</span>
										<span class="offer-terms__value offer-terms__value--percent">{dlg.advantage.total_text}</span>
									</div>
									{#each dlg.advantage.lines as line, i (i)}
										<div class="offer-terms__row offer-terms__row--source">
											<span class="offer-terms__label">{line.label}</span>
											<span class="offer-terms__value offer-terms__value--percent">{line.percent_text}</span>
										</div>
									{/each}
									{#if dlg.negotiation_percent !== null && negotiated[id]}
										<div class="offer-terms__row">
											<span class="offer-terms__label">{t('colony.bar_offer_negotiation_label')}</span>
											<span class="offer-terms__value offer-terms__value--percent">+{dlg.negotiation_percent} %</span>
										</div>
									{/if}
									{#if !negotiated[id] && negotiateFailed[id] === undefined}
										<div class="offer-terms__row offer-terms__row--result">
											<span class="offer-terms__label">{t('colony.bar_offer_receive_label')}</span>
											<span class="offer-terms__value">
												{@render chip(o.offer.get_resource_id, dlg.amount_safe)}
												<span class="offer-terms__plus">{t('colony.bar_offer_plus_vs_base', { plus: `+${dlg.plus_safe}` })}</span>
											</span>
										</div>
									{/if}
									{#if dlg.amount_negotiated !== null && negotiated[id]}
										<div class="offer-terms__row offer-terms__row--result">
											<span class="offer-terms__label">{t('colony.bar_offer_receive_label')}</span>
											<span class="offer-terms__value">
												{@render chip(o.offer.get_resource_id, dlg.amount_negotiated)}
												<span class="offer-terms__plus">{t('colony.bar_offer_plus_vs_base', { plus: `+${dlg.plus_negotiated}` })}</span>
											</span>
										</div>
									{/if}
									{#if dlg.credits_give}<p class="offer-note">{dlg.price_stays_text}</p>{/if}
								</div>
								{#if dlg.advantage.hints.length}
									<div class="trade-hints">
										{#each dlg.advantage.hints as hint, i (i)}<p class="trade-hint">{hint}</p>{/each}
									</div>
								{/if}
							{/if}

							{#if !dlg.fixed_price && dlg.amount_negotiated !== null && negotiated[id] && !accepted[id]}
								<div class="offer-status offer-status--ok">
									<strong>{t('colony.bar_offer_negotiated_label')}</strong>
									{dlg.amount_negotiated}
									{dlg.get_label}.
								</div>
							{/if}

							<div class="offer-actions">
								<div class="offer-action">
									<button class="tile-action-btn auto" onclick={() => accept(id, o.acceptSlug)} disabled={resolved(id) || loading}>
										<span class="tile-action-btn__body">{accepted[id] ? '✓' : t('colony.bar_offer_accept')}</span>
										<span class="ap-chip ap-cost-chip ap-chip--economy" aria-hidden="true"
											>Eco {negotiated[id] ? 0 : data.offerApCost} AP</span
										>
									</button>
									{#if !dlg.fixed_price && !negotiated[id] && !resolved(id)}
										<span class="offer-action__outcome"
											>{t('colony.bar_offer_accept_safe', { amount: dlg.amount_safe, resource: dlg.get_label, plus: `+${dlg.plus_safe}` })}</span
										>
									{/if}
								</div>

								<!-- Gone once negotiated (the "Verhandelt:" line takes over) or lost. -->
								{#if dlg.can_negotiate && !negotiated[id] && negotiateFailed[id] === undefined}
									<div class="offer-action">
										<button
											class="tile-action-btn tile-action-btn--secondary auto"
											onclick={() => negotiate(id, o.name)}
											disabled={resolved(id) || loading}
										>
											<span class="tile-action-btn__body">{t('colony.bar_offer_negotiate')}</span>
											<span class="ap-chip ap-cost-chip ap-chip--economy" aria-hidden="true">Eco {data.negotiateApCost} AP</span>
										</button>
										{#if !resolved(id)}
											<span class="offer-action__outcome">
												<span class="offer-action__win"
													>{t('colony.bar_offer_negotiate_win', {
														chance: dlg.chance.total_percent,
														amount: dlg.amount_negotiated ?? '',
														resource: dlg.get_label,
														plus: `+${dlg.plus_negotiated}`
													})}</span
												>
												{#if dlg.chance.lose_percent > 0}
													<span class="offer-action__lose"
														>{t('colony.bar_offer_negotiate_lose', {
															chance: dlg.chance.lose_percent,
															resource: dlg.give_label,
															ap: data.negotiateApCost
														})}</span
													>
												{/if}
											</span>
										{/if}
									</div>
									{#if !resolved(id)}
										<div class="offer-notes">
											<p class="offer-note">{dlg.chance.breakdown}</p>
											<p class="offer-note">{dlg.negotiation_sum}</p>
										</div>
									{/if}
								{/if}
							</div>

							<small class="muted">{t('colony.bar_offer_expires')} {o.offer.expires_tick}</small>
							{#if negotiateFailed[id] !== undefined}<div class="offer-status offer-status--fail">{negotiateFailed[id]}</div>{/if}
							{#if offerError[id]}<div class="error-line">{offerError[id]}</div>{/if}
						</CantinaDialog>
					{/if}
				{/each}

				{#if activeModal === 'encounter' && scene.encounter && data.encounter}
					{@const e = data.encounter}
					<CantinaDialog slug={scene.encounter.slug ?? 'stranger'} name={scene.encounter.heading} role={scene.encounter.char?.role ?? ''} relation={hasRelation ? relationPanel : undefined}>
						{@render toastFor('encounter')}
						<p>{scene.encounter.body}</p>
						{#if e.type === 'contract'}
							<div class="res-chip res-Cr inline"><span class="res-abbr">Cr</span><span class="res-amount">{e.credits_amount}/Sol</span></div>
						{:else}
							<div class="swap-box">
								<div>
									<div class="swap-label">{t('colony.bar_offer_give')}</div>
									{@render chip(e.give_resource_id, e.give_amount)}
								</div>
								<span class="swap-arrow">→</span>
								<div>
									<div class="swap-label">{t('colony.bar_offer_get')}</div>
									{@render chip('Cr', e.credits_amount)}
								</div>
							</div>
						{/if}
						<div class="row-end">
							<button class="tile-action-btn auto" onclick={acceptEncounter} disabled={encounterResolved || loading}>
								<span class="tile-action-btn__body">{encounterResolved ? '✓' : t('colony.bar_encounter_accept')}</span>
							</button>
						</div>
						{#if encounterResult === 'won'}<div class="ok-line">{t('colony.bar_encounter_wager_won')}</div>{/if}
						{#if encounterResult === 'lost'}<div class="error-line">{t('colony.bar_encounter_wager_lost')}</div>{/if}
						{#if encounterError}<div class="error-line">{encounterError}</div>{/if}
					</CantinaDialog>
				{/if}

				<!-- story_hook encounter: pure flavour, only to dismiss -->
				{#if activeModal === 'story' && scene.story && data.storyEncounterSlug}
					<CantinaDialog slug={data.storyEncounterSlug} name={scene.story.char.name ?? '???'} role={scene.story.char.role ?? ''} relation={hasRelation ? relationPanel : undefined}>
						<p>{t(`colony.story_encounter_${data.storyEncounterSlug}`)}</p>
						{@render closeButton()}
					</CantinaDialog>
				{/if}

				{#if activeModal === 'bartender'}
					{@const b = data.characters.bartender ?? {}}
					<CantinaDialog slug="bartender" name={b.name ?? ''} role={b.role ?? ''} relation={hasRelation ? relationPanel : undefined}>
						{@const bt = data.bartender}
						{#if bartenderTier !== null}
							<!-- Right after the conversation: Tomas' line + today's result. -->
							<p>{t(`colony.bartender_dialog_tier_${bartenderTier}`)}</p>
							{#if bartenderResult}<KnowledgeResultLine result={bartenderResult} source={b.name ?? ''} />{/if}
							{@render bartenderAdviceInfo(bt)}
							{@render bartenderDoneButton(bt)}
						{:else if bt.talked_today}
							<p>{t('colony.bartender_done_today')}</p>
							{#if bt.today_result}<KnowledgeResultLine result={bt.today_result} source={b.name ?? ''} />{/if}
							{@render bartenderAdviceInfo(bt)}
							{@render bartenderDoneButton(bt)}
						{:else if bt.relationship_level < 1 || bt.ap_bonus < 1}
							<!-- "fremd": no advice yet; Beziehung pflegen sits in the same dialog. -->
							<p>{t('colony.bartender_dialog_intro')}</p>
							<p class="hint-line" data-bartender-too-low>{t('colony.bartender_relationship_too_low')}</p>
							<div class="row-end">
								<button class="tile-action-btn auto" disabled>
									<span class="tile-action-btn__body">{t('colony.bartender_talk_button')}</span>
								</button>
							</div>
						{:else if bt.advice_in_sols > 0}
							<!-- Advice not due yet (frequency follows the relationship): button off, next Sol named. -->
							<p>{t('colony.bartender_dialog_intro')}</p>
							{@render bartenderAdviceInfo(bt)}
							<div class="row-end">
								<button class="tile-action-btn auto" disabled data-bartender-not-due>
									<span class="tile-action-btn__body">{t('colony.bartender_talk_button')}</span>
									<span class="ap-chip ap-cost-chip ap-chip--neutral">{t('pathdepth.bartender_advice_next', { n: bt.advice_in_sols })}</span>
								</button>
							</div>
						{:else}
							{@const canTalk = isSelectable(data.knowledgeOptions, bartenderKnowledgeId)}
							<p>{t('colony.bartender_dialog_intro')}</p>
							{@render bartenderAdviceInfo(bt)}
							<div class="stack">
								{@render knowledgeSelect(t('colony.bartender_knowledge_label'), data.knowledgeOptions, bartenderKnowledgeId, (v) => (bartenderKnowledgeId = v))}
								{#if firstSelectable(data.knowledgeOptions) === null}
									<p class="hint-line">{t('colony.bartender_no_selectable')}</p>
								{/if}
								<div class="row-end">
									<button class="tile-action-btn auto" onclick={talkToBartender} disabled={bartenderLoading || !canTalk}>
										<span class="tile-action-btn__body">{t('colony.bartender_talk_button_bonus', { ap: bt.ap_bonus })}</span>
										<span class="ap-chip ap-cost-chip ap-chip--research" aria-hidden="true">+{bt.ap_bonus} AP</span>
									</button>
								</div>
							</div>
						{/if}
						{#if bartenderError}<div class="error-line">{bartenderError}</div>{/if}
					</CantinaDialog>
				{/if}

				{#if activeModal === 'concern' && data.concern}
					{@const slug = data.concern.character_slug}
					{@const ch = data.characters[slug]}
					<CantinaDialog {slug} name={ch?.name ?? t('colony.bar_concern_heading')} role={ch?.role ?? ''} relation={hasRelation ? relationPanel : undefined}>
						{#if !concernResolved}
							<p>{t(`colony.bar_concern_${slug}_intro`)}</p>
							{#if slug === 'mechanic'}
								<div class="gap-below">
									{@render knowledgeSelect(t('colony.bar_concern_knowledge_label'), data.knowledgeOptions, concernKnowledgeId, (v) => (concernKnowledgeId = v))}
								</div>
							{/if}
							<div class="row-between">
								<span class="ap-chip ap-cost-chip ap-chip--economy" aria-hidden="true">{data.concernApCost ?? 1} AP</span>
								<button
									class="tile-action-btn auto"
									onclick={resolveConcern}
									disabled={concernLoading || (slug === 'mechanic' && !isSelectable(data.knowledgeOptions, concernKnowledgeId))}
								>
									<span class="tile-action-btn__body">{t('colony.bar_concern_resolve')}</span>
								</button>
							</div>
						{:else}
							{#if concernSuccess === false && data.concernHasFailureLine}
								<p class="error-line">{t(`colony.bar_concern_${slug}_failure`)}</p>
							{:else}
								<p class="ok-line">{t(`colony.bar_concern_${slug}_success`)}</p>
							{/if}
							{#if concernKnowledgeResult}<KnowledgeResultLine result={concernKnowledgeResult} source={ch?.name ?? ''} />{/if}
							{@render closeButton()}
						{/if}
						{#if concernError}<div class="error-line">{concernError}</div>{/if}
					</CantinaDialog>
				{/if}

				<!-- Met earlier, not in the scene today: portrait | relationship only. -->
				{#if activeModal?.startsWith('rel_') && activeSlug}
					{@const ch = data.characters[activeSlug]}
					<CantinaDialog slug={activeSlug} name={ch?.name ?? relFor(activeSlug)?.name ?? '???'} role={ch?.role ?? ''}>
						{@render relationPanel()}
					</CantinaDialog>
				{/if}

				{#if activeModal === 'information' && data.informationEncounter}
					{@const slug = data.informationEncounter.character_slug}
					{@const ch = data.characters[slug]}
					<CantinaDialog {slug} name={ch?.name ?? t('colony.bar_information_heading')} role={ch?.role ?? ''} relation={hasRelation ? relationPanel : undefined}>
						{#if !informationResolved}
							{#if infoNeedsChoice}
								<div class="gap-below">
									{@render knowledgeSelect(t('colony.bar_information_knowledge_label'), data.devaKnowledgeChoices, informationKnowledgeId, (v) => (informationKnowledgeId = v))}
								</div>
							{/if}
							<div class="row-end">
								<button
									class="tile-action-btn auto"
									onclick={resolveInformation}
									disabled={informationLoading || (infoNeedsChoice && !isSelectable(data.devaKnowledgeChoices, informationKnowledgeId))}
								>
									<span class="tile-action-btn__body">{t('colony.bar_information_resolve')}</span>
								</button>
							</div>
						{:else}
							<p>{data.informationOutcomeLines[informationOutcomeKey ?? ''] ?? ''}</p>
							{#if informationKnowledgeResult}<KnowledgeResultLine result={informationKnowledgeResult} source={ch?.name ?? ''} />{/if}
							{@render closeButton()}
						{/if}
						{#if informationError}<div class="error-line">{informationError}</div>{/if}
					</CantinaDialog>
				{/if}
			</div>

			<!-- Beziehung pflegen overview: met characters; each opens their dialog (T41). -->
			{#if data.relationships.some((r) => r.met)}
				<section class="bar-relationships">
					<h3 class="bar-relationships-title">{t('pathdepth.ui_relationship_title')}</h3>
					<p class="bar-relationships-hint">{t('pathdepth.ui_relationship_met_only')}</p>
					<p class="bar-relationships-hint">{t('pathdepth.ui_relationship_run_hint')}</p>
					<ul class="bar-relationships-list">
						{#each data.relationships.filter((r) => r.met) as rel (rel.key)}
							<li>
								<button
									type="button"
									class="bar-relationship-chip"
									data-character={rel.key}
									onclick={() => open(modalFor(rel.key) ?? `rel_${rel.key}`)}
								>
									<img src="{portrait(rel.key)}.webp" alt="" />
									<span class="bar-relationship-chip__text">
										<strong>{rel.name}</strong>
										<span class="bar-relationship-chip__level"
											>{t(`pathdepth.relationship_level_${rel.level}`)}</span
										>
									</span>
								</button>
							</li>
						{/each}
					</ul>
				</section>
			{/if}
		{/if}
	</div>

	{#if data.firstVisit}<FirstVisit screen="cantina" />{/if}
{/if}

<style>
	.load-error {
		padding: var(--space-md);
	}
	/* Hotspot positions per device (Blade generated .hs-slot-* rules from the same JSON). */
	:global(.cantina-hotspot.hs) {
		left: var(--hs-dl);
		top: var(--hs-dt);
	}
	@media (min-width: 768px) and (max-width: 1023px) {
		:global(.cantina-hotspot.hs) {
			left: var(--hs-tl);
			top: var(--hs-tt);
		}
	}
	@media (max-width: 767px) {
		:global(.cantina-hotspot.hs) {
			left: var(--hs-ml);
			top: var(--hs-mt);
		}
	}
	/* Phones: the viewport stays full-bleed, the text sections keep the 16px gutter. */
	@media (max-width: 599px) {
		.bar-relationships,
		.trade-advantage-bar,
		.market-report {
			margin-left: var(--space-md);
			margin-right: var(--space-md);
		}
	}
	/* Mobile-only swipe dots (Blade: .nav-mobile). */
	.cantina-dots {
		position: absolute;
		bottom: 0.75rem;
		left: 0;
		right: 0;
		z-index: 20;
	}
	@media (min-width: 600px) {
		.cantina-dots {
			display: none;
		}
	}
	/* Former inline styles from bar.blade.php, on design tokens. */
	.empty-text {
		margin: 0;
		font-size: 0.9rem;
		font-weight: 500;
	}
	.backdrop {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, 0.4);
		z-index: 999;
	}
	.cantina-modal[hidden] {
		display: none;
	}
	.scroll-list {
		max-height: 250px;
		overflow-y: auto;
		padding-right: 4px;
	}
	.flavor {
		font-style: italic;
		color: var(--color-text-secondary);
	}
	.auto {
		width: auto;
	}
	.muted {
		color: var(--color-text-secondary);
	}
	.muted-label {
		font-size: 0.85rem;
		color: var(--color-text-secondary);
	}
	.error-line {
		color: var(--color-danger);
		font-size: 0.85rem;
	}
	.hint-line {
		color: var(--color-text-secondary);
		font-size: 0.9rem;
		font-style: italic;
	}
	.cantina-bartender__badge {
		top: -8px;
		right: 6px;
	}
	.visually-hidden {
		position: absolute;
		width: 1px;
		height: 1px;
		overflow: hidden;
		clip: rect(0 0 0 0);
		white-space: nowrap;
	}
	.ok-line {
		color: #166534;
		font-size: 0.85rem;
	}
	.inline {
		display: inline-flex;
	}
	.swap-box {
		display: grid;
		grid-template-columns: 1fr auto 1fr;
		align-items: center;
		gap: 0.75rem;
		background: #f7f7f5;
		padding: 0.75rem 1rem;
		border-radius: 6px;
		border: 1px solid var(--color-border);
	}
	.swap-label {
		font-size: 0.75rem;
		color: var(--color-text-secondary);
		margin-bottom: 0.25rem;
	}
	.swap-arrow {
		font-size: 1.5rem;
		color: var(--color-text-secondary);
	}
	.row-end {
		display: flex;
		justify-content: flex-end;
		align-items: center;
		gap: 1rem;
		flex-wrap: wrap;
	}
	.row-between {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 1rem;
		flex-wrap: wrap;
	}
	.stack {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}
	.gap-below {
		margin-bottom: 0.75rem;
	}
</style>
