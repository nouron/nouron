<script lang="ts">
	import type { Snippet } from 'svelte';
	import type { HTMLButtonAttributes } from 'svelte/elements';

	let {
		variant = 'primary',
		children,
		...rest
	}: HTMLButtonAttributes & { variant?: 'primary' | 'secondary' | 'ghost'; children: Snippet } =
		$props();
</script>

<!-- Shape, hover lift and press come from lib/styles/controls.css (shared by every button). -->
<button class="btn {variant}" {...rest}>{@render children()}</button>

<style>
	.btn {
		padding: 0.6rem 1.2rem;
		border: 1px solid var(--color-accent);
		background: var(--color-accent);
		color: #fff;
		font: 700 var(--text-button-size) var(--font-body);
		letter-spacing: 0.08em;
		text-transform: uppercase;
		cursor: pointer;
	}
	.btn:hover:not(:disabled) {
		background: var(--color-accent-hover);
	}
	.secondary {
		border-color: var(--color-text-primary);
		background: var(--color-bg);
		color: var(--color-text-primary);
	}
	.ghost {
		border-color: var(--color-border-strong);
		background: var(--color-bg);
		color: var(--color-text-secondary);
	}
	.secondary:hover:not(:disabled),
	.ghost:hover:not(:disabled) {
		background: var(--color-surface);
	}
	.btn:disabled {
		opacity: 0.45;
		cursor: not-allowed;
	}
</style>
