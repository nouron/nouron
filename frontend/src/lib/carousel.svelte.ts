import '../../../public/css/carousel.css';

// Port of public/js/carousel.js (card carousel for advisors and hangar): swipe with drag-to-peek,
// arrow keys, full-width cards on mobile, a row on desktop. Not for the Cantina panorama.
export class Carousel {
	index = $state(0);
	count = $state(0);
	mobile = $state(false);
	#startX = 0;
	#startY = 0;
	#dx = $state(0);
	#dragging = $state(false);

	constructor(
		count: number,
		private opts = { breakpoint: 900, cardMaxWidth: 320, swipeMin: 40, gapPx: 16 }
	) {
		this.count = count;
	}

	prev = () => {
		if (this.index > 0) this.index--;
	};
	next = () => {
		if (this.index < this.count - 1) this.index++;
	};
	goTo = (i: number) => {
		this.index = Math.max(0, Math.min(this.count - 1, i));
	};

	/** Svelte attachment for the page root: tracks the breakpoint and arrow keys. */
	attach = () => {
		const resize = () => (this.mobile = window.innerWidth < this.opts.breakpoint);
		const key = (e: KeyboardEvent) => {
			if (e.key === 'ArrowRight') this.next();
			if (e.key === 'ArrowLeft') this.prev();
		};
		resize();
		window.addEventListener('resize', resize);
		window.addEventListener('keydown', key);
		return () => {
			window.removeEventListener('resize', resize);
			window.removeEventListener('keydown', key);
		};
	};

	get trackStyle(): string {
		if (!this.mobile) return '';
		// Below 768px cards are full-bleed; above, min(100vw - 48px, cardMaxWidth) with arrows.
		const w = window.innerWidth;
		const card = w < 768 ? w : Math.min(w - 48, this.opts.cardMaxWidth);
		const offset = this.index * (card + this.opts.gapPx) - (this.#dragging ? this.#dx : 0);
		return `transform: translateX(-${offset}px)` + (this.#dragging ? '; transition: transform 0s' : '');
	}

	touchstart = (e: TouchEvent) => {
		this.#startX = e.touches[0].clientX;
		this.#startY = e.touches[0].clientY;
		this.#dx = 0;
		this.#dragging = true;
	};

	touchmove = (e: TouchEvent) => {
		if (!this.#dragging) return;
		const dx = e.touches[0].clientX - this.#startX;
		const dy = e.touches[0].clientY - this.#startY;
		// Vertical scroll wins: cancel the drag.
		if (Math.abs(dy) > Math.abs(dx) && Math.abs(dy) > 10) {
			this.#dragging = false;
			this.#dx = 0;
			return;
		}
		// Rubber band at the edges.
		const edge = (this.index === 0 && dx > 0) || (this.index === this.count - 1 && dx < 0);
		this.#dx = edge ? dx * 0.25 : dx;
	};

	touchend = (e: TouchEvent) => {
		if (!this.#dragging) return;
		this.#dragging = false;
		this.#dx = 0;
		const dx = e.changedTouches[0].clientX - this.#startX;
		const dy = e.changedTouches[0].clientY - this.#startY;
		if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > this.opts.swipeMin) {
			if (dx < 0) this.next();
			else this.prev();
		}
	};
}
