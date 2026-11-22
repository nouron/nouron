<script lang="ts">
	// One resource amount: a chip, or `plain` (icon + text, no pill) for running text and tight rows.
	// The icon comes from resources.css via the res-<key> class.
	import type { HTMLAttributes } from 'svelte/elements';

	const LABEL: Record<string, string> = { Cr: 'CR', Sup: 'KOL', Rg: 'RG', RG: 'RG', Co: 'CO', Or: 'OR', Sol: 'Sol' };
	let {
		res,
		amount,
		label = LABEL[res] ?? res,
		plain = false,
		class: cls = '',
		...rest
	}: { res: string; amount: string | number; label?: string; plain?: boolean; class?: string } & HTMLAttributes<HTMLSpanElement> = $props();
</script>

{#if plain}<span class="res-plain res-{res} {cls}" {...rest}><span class="res-amount">{amount}</span> {label}</span>{:else}<span class="res-chip res-{res} {cls}" {...rest}><span class="res-abbr">{label}</span><span class="res-amount">{amount}</span></span>{/if}
