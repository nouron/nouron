<script lang="ts">
	// Reveals a text character by character behind a block cursor, like a terminal line.
	// For spoken lines and short system notes only (cantina dialogue, first-visit notes, discoveries), never for numbers or controls.
	// The whole text is in the DOM from the start (the rest is transparent): no layout jump, screen readers get it at once.
	let { text }: { text: string } = $props();
	let shown = $state(0);

	$effect(() => {
		const len = text.length;
		const step = Math.max(1, Math.ceil(len / 80)); // any length finishes within about 1.3 s
		shown = 0;
		const id = setInterval(() => {
			shown = Math.min(len, shown + step);
			if (shown >= len) clearInterval(id);
		}, 16);
		return () => clearInterval(id);
	});
</script>

{text.slice(0, shown)}{#if shown < text.length}<span class="cursor" aria-hidden="true"></span><span class="rest">{text.slice(shown)}</span>{/if}

<style>
	.rest {
		opacity: 0;
	}
	/* Zero-width anchor, so the cursor never pushes the text behind it. */
	.cursor {
		position: relative;
		display: inline-block;
		width: 0;
	}
	.cursor::after {
		content: '';
		position: absolute;
		left: 1px;
		bottom: -0.15em;
		width: 0.5em;
		height: 1.05em;
		background: var(--color-accent);
	}
</style>
