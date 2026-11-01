<script lang="ts">
	// Port of resources/views/components/cantina-dialog.blade.php: portrait on the left
	// (stacked on mobile), name/role and the body slot on the right.
	import type { Snippet } from 'svelte';
	import { BACKEND_URL } from '#lib/stores/auth.ts';

	let { slug, name, role = '', children }: { slug: string; name: string; role?: string; children: Snippet } = $props();
	const src = $derived(`${BACKEND_URL}/img/characters/${slug}`);
</script>

<div class="cantina-dialog">
	<div class="cantina-dialog__portrait">
		<img src="{src}.webp" srcset="{src}.webp 1x, {src}_lg.webp 2x" alt={name} />
	</div>
	<div class="cantina-dialog__body">
		<h3 class="cantina-dialog__name">{name}</h3>
		{#if role}<div class="cantina-dialog__role">{role}</div>{/if}
		<div class="cantina-dialog__content">{@render children()}</div>
	</div>
</div>
