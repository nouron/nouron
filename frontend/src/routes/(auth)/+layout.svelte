<script lang="ts">
	// Frame of login and registration: centred column with the brand above the form.
	let { children } = $props();
</script>

<main>
	<p class="brand">Nouron</p>
	{@render children()}
</main>

<style>
	main {
		max-width: 26rem;
		margin: 0 auto;
		padding: 14vh var(--space-md) var(--space-lg);
	}
	.brand {
		font: 700 var(--text-h1-size, 2rem) var(--font-display, inherit);
		letter-spacing: var(--text-h1-tracking);
		text-transform: uppercase;
		text-align: center;
		margin: 0 0 var(--space-lg);
	}
	main :global(h1) {
		font-size: var(--text-h3-size, 1.25rem);
		margin: 0 0 var(--space-md);
	}
	main :global(form .btn) {
		width: 100%;
		margin-top: var(--space-sm);
	}
	main :global(.field-error),
	main :global(.error) {
		color: var(--color-danger, #c0392b);
		font-size: 0.85rem;
	}
	main :global(.footer) {
		text-align: center;
		margin-top: var(--space-md);
		font-size: 0.9rem;
	}
</style>
