<script lang="ts">
	// Running text with resource amounts ("30 Cr", "-12 RG", "4 KOL") shown with their resource icon.
	import Res from './Res.svelte';

	const KEY: Record<string, string> = { CR: 'Cr', RG: 'Rg', CO: 'Co', OR: 'Or', KOL: 'Sup' };
	// Amount (optionally signed, "a / b" ranges, "/Sol" rates) followed by a resource abbreviation.
	const AMOUNT = /([+\-−]?\d[\d.,]*(?:\s?\/\s?\d[\d.,]*)?)\s?(Cr|CR|Rg|RG|Co|CO|Or|OR|KOL)\b/g;

	let { text }: { text: string } = $props();
	const parts = $derived(text.split(AMOUNT));
</script>

{#each parts as part, i (i)}{#if i % 3 === 1}<Res plain res={KEY[parts[i + 1].toUpperCase()]} amount={part} />{:else if i % 3 === 0}{part}{/if}{/each}
