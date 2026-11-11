<script lang="ts">
	// Character dialog: portrait | (relationship) | today's interaction.
	// Without `relation` it stays the classic two-column dialog (portrait | body).
	// With `relation` (any cantina character): wide (>= 900px) three columns —
	// portrait | relationship | interaction; 768–899px portrait | stacked body.
	// Phones (all dialogs): compact header (thumbnail + name), interaction, then relationship.
	import type { Snippet } from 'svelte';
	import { BACKEND_URL } from '#lib/stores/auth.ts';

	let {
		slug,
		name,
		role = '',
		children,
		relation
	}: { slug: string; name: string; role?: string; children: Snippet; relation?: Snippet } = $props();
	const src = $derived(`${BACKEND_URL}/img/characters/${slug}`);
</script>

<div class="cantina-dialog" class:cantina-dialog--relation={!!relation}>
	<div class="cantina-dialog__portrait">
		<img src="{src}.webp" srcset="{src}.webp 1x, {src}_lg.webp 2x" alt={name} />
	</div>
	<div class="cantina-dialog__body">
		<header class="cantina-dialog__head">
			<h3 class="cantina-dialog__name">{name}</h3>
			{#if role}<div class="cantina-dialog__role">{role}</div>{/if}
		</header>
		<div class="cantina-dialog__columns">
			<!-- DOM order = phone order: the interaction you tapped for first, relationship below. -->
			<div class="cantina-dialog__content">{@render children()}</div>
			{#if relation}
				<aside class="cantina-dialog__relation">{@render relation()}</aside>
			{/if}
		</div>
	</div>
</div>
