// Resource rail: lets a page ask the rail to fold in (colony tile tap on phones) without importing the component.
import { writable } from 'svelte/store';

/** Counter, incremented on every hex tile tap in the colony; the rail decides what to do with it. */
export const tileTaps = writable(0);

export const notifyTileTap = () => tileTaps.update((n) => n + 1);
