<script lang="ts">
	// Port of partials/sol-button.blade.php: AP check → confirm → POST /sol/next → animated
	// Sol report (groups with counters) → optional phase success → phase progress → "Sol N starts".
	import '#lib/styles/sol.css';
	import { t } from '#lib/i18n.ts';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { refreshShell, solEpoch } from '#lib/stores/shell.ts';

	type Line = { label: string; detail?: string; tone?: string; beat?: boolean; from?: number; to?: number };
	type Group = { key: string; icon: string; title: string; lines: Line[] };
	type Criterion = { key: string; label: string; done: boolean; current: number; target: number };
	type Objective = { label: string; done: boolean; revealed: boolean; current: number; target: number };
	type Report = {
		completed_sol: number;
		next_sol: number;
		groups: Group[];
		finale?: { outcome: 'win' | 'lose'; title: string; body: string } | null;
		result_url?: string | null;
		phase_transition?: { criteria: string[] } | null;
		phase_progress: { phase: number; criteria?: Criterion[]; objectives?: Objective[] };
	};

	let confirmAp = $state<number | null>(null);
	let loading = $state(false);
	let report = $state<Report | null>(null);
	let screen = $state<1 | 'success' | 2 | 3>(1);
	let visibleGroup = $state(-1);
	let finished = $state(false);
	let counters = $state<Record<string, number>>({});
	let shakeKey = $state<string | null>(null);
	let launchPhase = $state(0); // 0 nothing, 1 Sol number, 2 "starts", 3 button
	let timers: ReturnType<typeof setTimeout>[] = [];
	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	const schedule = (fn: () => void, ms: number) => timers.push(setTimeout(fn, ms));
	const clearTimers = () => {
		timers.forEach(clearTimeout);
		timers = [];
	};

	async function handleClick() {
		if (loading) return; // a Sol is already being computed (R11)
		const { total } = await api<{ total: number }>('/sol/remaining-ap');
		if (total > 0) confirmAp = total;
		else await submit();
	}

	async function submit() {
		confirmAp = null;
		loading = true;
		try {
			const r = await api<Report>('/sol/next', { method: 'POST' });
			counters = {};
			visibleGroup = -1;
			finished = false;
			screen = 1;
			launchPhase = 0;
			report = r;
			loading = false;
			if (r.finale) finished = true;
			else play();
		} catch (e) {
			loading = false;
			// 409: another tab/click is already advancing this Sol — local state is stale either way.
			if (e instanceof ApiError && e.status === 409) return location.reload();
			alert(t('colony.sol_error'));
		}
	}

	function play() {
		if (!report) return;
		if (reduceMotion) return finishNow();
		let delay = 0;
		report.groups.forEach((group, gi) => {
			schedule(() => (visibleGroup = gi), delay);
			delay += 250;
			let extra = 0;
			group.lines.forEach((line, li) => {
				const key = `${group.key}:${li}`;
				if (line.from !== undefined && line.to !== undefined) {
					const [from, to] = [line.from, line.to];
					schedule(() => animateCounter(key, from, to), delay + extra);
					extra += 200;
				}
				if (line.beat) {
					extra += 500; // hold so the moment lands
					if (line.tone === 'danger') schedule(() => shake(key), delay + extra - 350);
				}
			});
			delay += extra + 300;
		});
		schedule(() => (finished = true), delay);
	}

	function animateCounter(key: string, from: number, to: number) {
		const start = performance.now();
		const step = (now: number) => {
			if (finished) return void (counters[key] = to);
			const p = Math.min(1, (now - start) / 700);
			counters[key] = Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3))); // easeOutCubic
			if (p < 1) requestAnimationFrame(step);
		};
		requestAnimationFrame(step);
	}

	function shake(key: string) {
		shakeKey = key;
		schedule(() => shakeKey === key && (shakeKey = null), 200);
	}

	function finishNow() {
		if (!report || screen !== 1) return;
		clearTimers();
		shakeKey = null;
		visibleGroup = report.groups.length - 1;
		report.groups.forEach((g) =>
			g.lines.forEach((line, li) => {
				if (line.to !== undefined) counters[`${g.key}:${li}`] = line.to;
			})
		);
		finished = true;
	}

	function goLaunch() {
		screen = 3;
		if (reduceMotion) return void (launchPhase = 3);
		schedule(() => (launchPhase = 1), 150);
		schedule(() => (launchPhase = 2), 900);
		schedule(() => (launchPhase = 3), 2000);
	}

	// Stay on the current screen: reload header data and remount the page (new Sol, new state).
	async function begin() {
		clearTimers();
		report = null;
		await refreshShell();
		solEpoch.update((n) => n + 1);
	}

	const counterValue = (g: Group, li: number) => counters[`${g.key}:${li}`] ?? g.lines[li].from ?? '';
</script>

<button type="button" class="btn-sol" onclick={handleClick} disabled={loading}>
	<i class="bi bi-skip-forward-fill"></i>
	{t('colony.next_sol_button')}
</button>

{#if confirmAp !== null}
	<div class="sol-overlay">
		<div class="sol-dialog">
			<h3 class="sol-dialog__title">{t('colony.sol_confirm_title')}</h3>
			<p class="sol-dialog__body">{t('colony.sol_confirm_body', { ap: confirmAp })}</p>
			<p class="sol-dialog__body">{t('colony.sol_confirm_expire')}</p>
			<div class="sol-dialog__actions">
				<button type="button" class="sol-btn sol-btn--ghost" onclick={() => (confirmAp = null)}
					>{t('colony.sol_confirm_keep_playing')}</button
				>
				<button type="button" class="sol-btn sol-btn--primary" onclick={submit}
					>{t('colony.sol_confirm_end_anyway')}</button
				>
			</div>
		</div>
	</div>
{/if}

{#if loading}
	<div class="sol-overlay sol-overlay--loading">
		<div class="sol-loading">
			<div class="sol-spinner"></div>
			<p class="sol-loading__text">{t('colony.sol_loading')}</p>
			<p class="sol-loading__sub">{t('colony.sol_loading_sub')}</p>
		</div>
	</div>
{/if}

{#if report}
	{@const r = report}
	<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
	<div class="sol-overlay sol-overlay--report" onclick={() => screen === 1 && !finished && finishNow()}>
		{#if r.finale}
			<div class="sol-report sol-report--finale sol-report--finale-{r.finale.outcome}" onclick={(e) => e.stopPropagation()}>
				<div class="sol-finale__glyph">
					<i class="bi {r.finale.outcome === 'win' ? 'bi-trophy-fill' : 'bi-x-octagon-fill'}"></i>
				</div>
				<h2 class="sol-finale__title">{r.finale.title}</h2>
				<p class="sol-finale__body">{r.finale.body}</p>
				<button
					type="button"
					class="sol-btn sol-btn--primary sol-finale__cta"
					onclick={() => (r.result_url ? (location.href = r.result_url) : begin())}
				>
					{r.finale.outcome === 'win' ? t('colony.sol_report_finale_win_cta') : t('colony.sol_report_finale_lose_cta')}
				</button>
			</div>
		{:else if screen === 1}
			<div class="sol-report">
				<header class="sol-report__header">
					<h2 class="sol-report__title">{t('colony.sol_report_title', { sol: r.completed_sol })}</h2>
				</header>
				<div class="sol-report__groups">
					{#each r.groups as group, gi (group.key)}
						{#if gi <= visibleGroup}
							<article class="sol-report__group">
								<h3 class="sol-report__group-title">
									<i class="bi bi-{group.icon}"></i>
									<span>{group.title}</span>
								</h3>
								<ul class="sol-report__lines">
									{#each group.lines as line, li (li)}
										<li
											class={[
												'sol-report__line',
												'sol-report__line--' + (line.tone || 'neutral'),
												line.beat && 'sol-report__line--beat',
												shakeKey === `${group.key}:${li}` && 'sol-report__line--shake'
											]}
										>
											<span class="sol-report__label">{line.label}</span>
											<span class="sol-report__value">
												{#if line.from !== undefined && line.to !== undefined}
													<span class="sol-report__counter">{counterValue(group, li)}</span>
												{/if}
												<span class="sol-report__detail">{line.detail ?? ''}</span>
											</span>
										</li>
									{/each}
								</ul>
							</article>
						{/if}
					{/each}
				</div>
				<footer class="sol-report__footer">
					{#if finished}
						<button
							type="button"
							class="sol-btn sol-btn--primary sol-report__continue"
							onclick={(e) => {
								e.stopPropagation();
								clearTimers();
								screen = r.phase_transition ? 'success' : 2;
							}}>{t('colony.sol_report_next_screen')}</button
						>
					{/if}
				</footer>
			</div>
		{:else if screen === 'success' && r.phase_transition}
			<div class="sol-report sol-report--phase sol-report--success" onclick={(e) => e.stopPropagation()}>
				<header class="sol-report__header">
					<h2 class="sol-report__title">{t('colony.sol_report_phase_success_title')}</h2>
					<p class="sol-phase__subtitle">{t('colony.sol_report_phase_success_body')}</p>
				</header>
				<div class="sol-report__groups sol-phase__body">
					<ul class="sol-phase__list">
						{#each r.phase_transition.criteria as label, i (i)}
							<li class="sol-phase__item sol-phase__item--done">
								<i class="bi sol-phase__icon bi-check-circle-fill"></i>
								<span class="sol-phase__label">{label}</span>
								<span class="sol-phase__progress">{t('colony.sol_report_phase_success_achieved')}</span>
							</li>
						{/each}
					</ul>
				</div>
				<footer class="sol-report__footer sol-phase__footer">
					<button type="button" class="sol-btn sol-btn--primary sol-report__continue" onclick={() => (screen = 2)}
						>{t('colony.sol_report_next_screen')}</button
					>
				</footer>
			</div>
		{:else if screen === 2}
			{@const pp = r.phase_progress}
			<div class="sol-report sol-report--phase" onclick={(e) => e.stopPropagation()}>
				<header class="sol-report__header">
					<h2 class="sol-report__title">{t('colony.sol_report_screen2_title')}</h2>
					<p class="sol-phase__subtitle">
						{pp.phase === 1 ? t('colony.sol_report_phase1_title') : t('colony.sol_report_phase2_title')}
					</p>
				</header>
				<div class="sol-report__groups sol-phase__body">
					<ul class="sol-phase__list">
						{#if pp.phase === 1}
							{#each pp.criteria ?? [] as c (c.key)}
								<li class="sol-phase__item" class:sol-phase__item--done={c.done}>
									<i class="bi sol-phase__icon {c.done ? 'bi-check-circle-fill' : 'bi-circle'}"></i>
									<span class="sol-phase__label">{c.label}</span>
									<span class="sol-phase__progress">{c.current} / {c.target}</span>
								</li>
							{/each}
						{:else}
							{#each pp.objectives ?? [] as obj, i (i)}
								<li
									class="sol-phase__item"
									class:sol-phase__item--done={obj.done}
									class:sol-phase__item--hidden={!obj.done && !obj.revealed}
								>
									<i
										class="bi sol-phase__icon {obj.done
											? 'bi-check-circle-fill'
											: obj.revealed
												? 'bi-circle'
												: 'bi-question-circle'}"
									></i>
									<span class="sol-phase__label"
										>{obj.revealed ? obj.label : t('colony.sol_report_phase2_objective_hidden')}</span
									>
									{#if obj.revealed}<span class="sol-phase__progress">{obj.current} / {obj.target}</span>{/if}
								</li>
							{/each}
						{/if}
					</ul>
				</div>
				<footer class="sol-report__footer sol-phase__footer">
					<button type="button" class="sol-btn sol-btn--primary sol-report__continue" onclick={goLaunch}
						>{t('colony.sol_report_next_screen')}</button
					>
				</footer>
			</div>
		{:else}
			<div class="sol-launch" onclick={(e) => e.stopPropagation()}>
				<div class="sol-launch__text">
					<div
						class="sol-launch__sol"
						style:opacity={launchPhase >= 1 ? 1 : 0}
						style:transform={launchPhase >= 1 ? 'translateY(0)' : 'translateY(1.5rem)'}
					>
						SOL {r.next_sol}
					</div>
					<div
						class="sol-launch__starts"
						style:opacity={launchPhase >= 2 ? 1 : 0}
						style:transform={launchPhase >= 2 ? 'translateY(0)' : 'translateY(1rem)'}
					>
						{t('colony.sol_report_screen3_starts')}
					</div>
				</div>
				<button
					type="button"
					class="sol-btn sol-btn--ghost sol-launch__begin"
					style:opacity={launchPhase >= 3 ? 1 : 0}
					style:pointer-events={launchPhase >= 3 ? 'auto' : 'none'}
					onclick={begin}>{t('colony.sol_report_screen3_begin')}</button
				>
			</div>
		{/if}
	</div>
{/if}

<style>
	.btn-sol {
		display: inline-flex;
		align-items: center;
		gap: 0.3rem;
		padding: 0.2rem 0.65rem;
		font: 600 0.8rem var(--font-body);
		color: #fff;
		background: var(--color-accent);
		border: 1px solid var(--color-accent);
		border-radius: var(--radius-sm);
		white-space: nowrap;
		cursor: pointer;
		transition: opacity var(--duration-fast);
	}
	.btn-sol:hover {
		opacity: 0.88;
	}
	.btn-sol:disabled {
		opacity: 0.5;
		cursor: wait;
	}
</style>
