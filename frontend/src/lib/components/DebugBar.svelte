<script lang="ts">
	// Admin debug bar — port of partials/debug-bar.blade.php (run, Sol, bypass flags, env, config).
	// ponytail: finale/result preview buttons of the Blade bar left out; the result page is reachable from the lobby.
	import { api } from '#lib/stores/auth.ts';
	import { solEpoch } from '#lib/stores/shell.ts';

	type Debug = {
		run: { id: number; current_tick: number; status: string } | null;
		tick_limit: number;
		bypass: Record<string, boolean>;
		env: string;
		config: Record<string, Record<string, string | number>>;
	};
	const FLAGS: [string, string][] = [
		['ap_checks', 'AP'],
		['resource_costs', 'Res'],
		['supply_checks', 'Sup']
	];

	let d = $state<Debug | null>(null);
	let open = $state(false);
	let closed = $state(false);

	$effect(() => {
		void $solEpoch; // reload after each Sol
		api<Debug>('/admin/debug').then((r) => (d = r), () => {});
	});
</script>

{#if d && !closed}
	<div class="debug">
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
			<button onclick={() => (closed = true)} aria-label="close">×</button>
		</div>
		{#if open}
			<div class="panel">
				{#each Object.entries(d.config) as [group, values] (group)}
					<div>
						<div class="tag">{group}</div>
						{#each Object.entries(values) as [k, v] (k)}
							<div>
								<span class="mut">{k}:</span>
								<span class={typeof v === 'number' && group === 'Trust Events' ? (v >= 0 ? 'ok' : 'bad') : ''}
									>{typeof v === 'number' && v > 0 && group === 'Trust Events' ? '+' : ''}{v}</span
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
	/* Keep the feedback button above the bar (it sits fixed at the bottom right too). */
	:global(body:has(.debug) .feedback-trigger) {
		bottom: 2.5rem;
	}
</style>
