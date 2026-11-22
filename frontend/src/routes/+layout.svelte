<script lang="ts">
	import '../app.css';
	import type { LayoutProps } from './$types';
	import { BACKEND_URL } from '#lib/stores/auth.ts';

	// Resource icons are served by the backend (public/img/icons, ADR 0001); CSS reads them via these properties.
	const ICONS = { cr: 'credits', sup: 'colonists', rg: 'regolith', co: 'compounds', or: 'organics', tr: 'trust' };
	const iconVars = Object.entries(ICONS)
		.map(([k, f]) => `--icon-${k}: url(${BACKEND_URL}/img/icons/res_${f}.webp);`)
		.join(' ') + ` --map-ground: url(${BACKEND_URL}/img/tiles/ground_background.webp);`;

	let { children }: LayoutProps = $props();
</script>

<svelte:head>
	<link rel="icon" href="/favicon.ico" />
	<!-- eslint-disable-next-line svelte/no-at-html-tags -- static, built from constants -->
	{@html `<style>:root { ${iconVars} }</style>`}
</svelte:head>

{@render children()}
