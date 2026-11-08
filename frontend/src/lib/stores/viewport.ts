// Viewport class helpers. Mirrors the CSS breakpoint "Mobile" (max-width: 599px), see docs/frontend-conventions.md section 4.
import { readable } from 'svelte/store';

const PHONE_QUERY = '(max-width: 599px)';

/** True below 600 px viewport width; follows resize/rotation. */
export const isPhone = readable(false, (set) => {
	if (typeof matchMedia !== 'function') return;
	const mq = matchMedia(PHONE_QUERY);
	set(mq.matches);
	const on = (e: MediaQueryListEvent) => set(e.matches);
	mq.addEventListener('change', on);
	return () => mq.removeEventListener('change', on);
});
