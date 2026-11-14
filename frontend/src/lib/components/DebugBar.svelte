<script lang="ts">
	// Admin debug bar — port of partials/debug-bar.blade.php (run, Sol, bypass flags, env, config).
	// ponytail: finale/result preview buttons of the Blade bar left out; the result page is reachable from the lobby.
	import { api } from '#lib/stores/auth.ts';
	import { solEpoch } from '#lib/stores/shell.ts';

	// docked: sits in a parent container (game layout bottom chrome) instead of fixed to the viewport edge.
	let { docked = false }: { docked?: boolean } = $props();

	type Debug = {
		run: { id: number; current_tick: number; status: string } | null;
		tick_limit: number;
		bypass: Record<string, boolean>;
		env: string;
		config: Record<string, Record<string, string | number>>;
		// Exact knowledge progress (owner checks silent research without a lab).
		knowledge?: {
			id: number;
			key: string;
			name: string;
			level: number;
			ap_spend: number;
			ap_for_levelup: number;
			max_level: number;
			blocker: string | null;
			blocker_label: string | null;
		}[];
	};
	const FLAGS: [string, string][] = [
		['ap_checks', 'AP'],
		['resource_costs', 'Res'],
		['supply_checks', 'KOL']
	];

	let d = $state<Debug | null>(null);
	let open = $state(false);
	// Collapsed to a thin handle by default; the choice is remembered (storage may be blocked).
	const KEY = 'nouron.debugbar.expanded';
	let expanded = $state(false);
	try {
		expanded = localStorage.getItem(KEY) === '1';
	} catch {
		/* ignore */
	}
	function toggleExpanded() {
		expanded = !expanded;
		try {
			localStorage.setItem(KEY, expanded ? '1' : '0');
		} catch {
			/* ignore */
		}
	}

	$effect(() => {
		void $solEpoch; // reload after each Sol
		api<Debug>('/admin/debug').then((r) => (d = r), () => {});
	});
</script>

{#if d && !expanded}
	<div class="debug" class:docked>
		<button class="handle" onclick={toggleExpanded} aria-expanded="false" aria-label="DEBUG">⚙ DEBUG ▴</button>
	</div>
{:else if d}
	<div class="debug" class:docked>
		<div class="row">
			<span class="tag">⚙ DEBUG</span>
			<span class="sep">|</span>
			{#if d.run}
				<span>Run <b>#{d.run.id}</b></span>
				<span>Sol <b>{d.run.current_tick}</b><span class="dim">/{d.tick_limit}</span></span>
				<span class="dim">{d.run.status}</span>
			{:else}
				<span class="bad">kein aktiver Run</span>
			{/if}
			<span class="sep">|</span>
			<span class="mut">bypass:</span>
			{#each FLAGS as [key, label] (key)}
				<span class={d.bypass[key] ? 'warn' : 'ok'}>{label}:{d.bypass[key] ? 'OFF' : 'on'}</span>
			{/each}
			<span class="sep">|</span>
			<span class="mut">env:</span>
			<span class={d.env === 'production' ? 'bad' : 'ok'}>{d.env}</span>
			<button class="push" onclick={() => (open = !open)}>Config {open ? '▴' : '▾'}</button>
			<button onclick={toggleExpanded} aria-expanded="true" aria-label="collapse">▾</button>
		</div>
		<!-- Knowledge progress is always visible in the expanded bar (owner 2026-11-13, no extra toggle). -->
		{#if d.knowledge}
			<div class="panel">
				{#if d.knowledge?.length}
					<table class="kn">
						<thead>
							<tr><th>Kenntnis</th><th>Stufe</th><th>AP</th><th>Sperrgrund</th></tr>
						</thead>
						<tbody>
							{#each d.knowledge as k (k.id)}
								<tr>
									<td title={k.key}>{k.name}</td>
									<td><b>{k.level}</b><span class="dim">/{k.max_level}</span></td>
									<td>
										{#if k.level >= k.max_level}<span class="ok">max</span>
										{:else}{k.ap_spend}<span class="dim">/{k.ap_for_levelup}</span>{/if}
									</td>
									<td class={k.blocker ? 'warn' : 'dim'} title={k.blocker ?? undefined}>{k.blocker_label ?? k.blocker ?? '–'}</td>
								</tr>
							{/each}
						</tbody>
					</table>
				{:else}
					<span class="dim">keine Kenntnisse</span>
				{/if}
			</div>
		{/if}
		{#if open}
			<div class="panel">
				{#each Object.entries(d.config) as [group, values] (group)}
					<div>
						<div class="tag">{group}</div>
						{#each Object.entries(values) as [k, v] (k)}
							<div>
								<span class="mut">{k}:</span>
								<span class={typeof v === 'number' && group === 'Vertrauen' ? (v >= 0 ? 'ok' : 'bad') : ''}
									>{typeof v === 'number' && v > 0 && group === 'Vertrauen' ? '+' : ''}{v}</span
								>
							</div>
						{/each}
					</div>
				{/each}
			</div>
		{/if}
	</div>
{/if}

<style>
	/* Dev tool: own dark look on purpose, outside the design system (like the Blade bar). */
	.debug {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		z-index: 9999;
		background: #0d0d0d;
		color: #ccc;
		font: 11px monospace;
		border-top: 1px solid #2a2a2a;
	}
	.debug.docked {
		position: static;
	}
	.handle {
		display: block;
		width: 100%;
		background: none;
		border: none;
		color: #ff0;
		font: 10px monospace;
		line-height: 14px;
		padding: 0;
		cursor: pointer;
	}
	.row {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: 12px;
		padding: 3px 10px;
	}
	.panel {
		display: flex;
		flex-wrap: wrap;
		gap: 24px;
		padding: 8px 12px;
		background: #0a0a0a;
		border-top: 1px solid #1e1e1e;
	}
	b {
		color: #fff;
	}
	.tag {
		color: #ff0;
		font-weight: bold;
	}
	.sep {
		color: #444;
	}
	.dim {
		color: #555;
	}
	.mut {
		color: #888;
	}
	.ok {
		color: #4c4;
	}
	.warn {
		color: #f90;
	}
	.bad {
		color: #f55;
	}
	button {
		background: none;
		border: 1px solid #333;
		color: #888;
		cursor: pointer;
		font: inherit;
		padding: 0 6px;
		line-height: 16px;
	}
	.push {
		margin-left: auto;
	}
	.kn {
		border-collapse: collapse;
	}
	.kn th {
		color: #888;
		font-weight: normal;
		text-align: left;
	}
	.kn th,
	.kn td {
		padding: 1px 10px 1px 0;
		white-space: nowrap;
	}
	.panel:has(.kn) {
		max-height: 40vh;
		overflow: auto;
	}
	/* Keep the floating feedback button (infra layout) above the bar. */
	:global(body:has(.debug) .feedback-trigger) {
		bottom: 2.5rem;
	}
</style>
