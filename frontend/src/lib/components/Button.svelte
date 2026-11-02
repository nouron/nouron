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

<!-- Beveled sci-fi button (docs/design-system/components/core/Button): outer = 2px line, inner = fill. -->
<button class="btn {variant}" {...rest}><span>{@render children()}</span></button>

<style>
	.btn {
		--notch: 12px;
		--line: var(--color-accent);
		--fg: var(--color-accent);
		display: inline-flex;
		padding: 2px;
		border: none;
		background: var(--line);
		clip-path: polygon(0 0, calc(100% - var(--notch)) 0, 100% var(--notch), 100% 100%, var(--notch) 100%, 0 calc(100% - var(--notch)));
		font: 700 var(--text-button-size) var(--font-body);
		letter-spacing: 0.08em;
		text-transform: uppercase;
		cursor: pointer;
	}
	.btn span {
		flex: 1;
		padding: 0.6rem 1.2rem;
		background: var(--color-bg);
		color: var(--fg);
		clip-path: polygon(0 0, calc(100% - var(--notch) + 2px) 0, 100% calc(var(--notch) - 2px), 100% 100%, calc(var(--notch) - 2px) 100%, 0 calc(100% - var(--notch) + 2px));
		transition:
			background var(--duration-fast),
			color var(--duration-fast);
	}
	.secondary {
		--line: var(--color-text-primary);
		--fg: var(--color-text-primary);
	}
	.ghost {
		--line: var(--color-border-strong);
		--fg: var(--color-text-secondary);
	}
	.btn:hover:not(:disabled) span,
	.btn:focus-visible span {
		background: var(--line);
		color: #fff;
	}
	.ghost:hover:not(:disabled) span {
		background: var(--color-surface);
		color: var(--fg);
	}
	.btn:disabled {
		opacity: 0.45;
		cursor: not-allowed;
	}
</style>
