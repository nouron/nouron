/**
 * carousel.js — shared Alpine.js mixin for card-carousel screens.
 *
 * Used by: advisorCarousel(), hangarCarousel(), and future card-based screens.
 * NOT for the Cantina panorama (that uses swipe.js / swipeCarousel).
 *
 * Features:
 *   - Touch swipe (left/right) with momentum feel
 *   - Drag-to-peek: while dragging, card moves with finger in real time
 *   - Arrow key navigation
 *   - Responsive: full-width cards on mobile, row on desktop
 *
 * Usage:
 *   function myCarousel(config) {
 *       return {
 *           ...carouselMixin(config.items.length),
 *           // screen-specific state & methods
 *           init() { this._carouselInit(); },
 *       };
 *   }
 */
function carouselMixin(itemCount, options = {}) {
    const BREAKPOINT = options.breakpoint ?? 900; // px: below = mobile/tablet mode
    const CARD_MAX_W = options.cardMaxWidth ?? 320; // px: max card width on tablet
    const SWIPE_MIN = options.swipeMin ?? 40; // px: min horizontal delta for swipe
    const GAP_PX = options.gapPx ?? 16; // px: must match CSS gap (1rem)

    return {
        activeIndex: 0,
        _isMobile: false,
        _itemCount: itemCount,

        // Drag-while-swiping state
        _dragStartX: 0,
        _dragStartY: 0,
        _dragDeltaX: 0,
        _dragging: false,

        /* ── Init ──────────────────────────────────────────────────────────── */

        _carouselInit() {
            this._isMobile = window.innerWidth < BREAKPOINT;
            window.addEventListener('resize', () => {
                this._isMobile = window.innerWidth < BREAKPOINT;
            });
            window.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight') this._carouselNext();
                if (e.key === 'ArrowLeft') this._carouselPrev();
            });
        },

        /* ── Navigation ────────────────────────────────────────────────────── */

        _carouselPrev() {
            if (this.activeIndex > 0) this.activeIndex--;
        },

        _carouselNext() {
            if (this.activeIndex < this._itemCount - 1) this.activeIndex++;
        },

        _carouselGoTo(i) {
            this.activeIndex = Math.max(0, Math.min(this._itemCount - 1, i));
        },

        /* ── Track transform ────────────────────────────────────────────────── */

        trackStyle() {
            if (!this._isMobile) return '';
            // Below 768px cards are 100vw (full-bleed, no side padding or arrows).
            // Above that cards are min(100vw - 48px, CARD_MAX_W) with arrow buttons.
            const cardWidth =
                window.innerWidth < 768 ? window.innerWidth : Math.min(window.innerWidth - 48, CARD_MAX_W);
            const base = this.activeIndex * (cardWidth + GAP_PX);
            const drag = this._dragging ? this._dragDeltaX : 0;
            const offset = base - drag;
            // Suppress CSS transition while finger drags (instant follow); restore on release.
            return this._dragging
                ? `transform: translateX(-${offset}px); transition: transform 0s`
                : `transform: translateX(-${offset}px)`;
        },

        /* ── Touch handlers ─────────────────────────────────────────────────── */

        onTouchStart(e) {
            this._dragStartX = e.touches[0].clientX;
            this._dragStartY = e.touches[0].clientY;
            this._dragDeltaX = 0;
            this._dragging = true;
        },

        onTouchMove(e) {
            if (!this._dragging) return;
            const dx = e.touches[0].clientX - this._dragStartX;
            const dy = e.touches[0].clientY - this._dragStartY;
            // If vertical scroll dominates, cancel drag
            if (Math.abs(dy) > Math.abs(dx) && Math.abs(dy) > 10) {
                this._dragging = false;
                this._dragDeltaX = 0;
                return;
            }
            // Rubber-band at edges
            const atStart = this.activeIndex === 0 && dx > 0;
            const atEnd = this.activeIndex === this._itemCount - 1 && dx < 0;
            this._dragDeltaX = atStart || atEnd ? dx * 0.25 : dx;
        },

        onTouchEnd(e) {
            if (!this._dragging) return;
            this._dragging = false;
            const dx = e.changedTouches[0].clientX - this._dragStartX;
            const dy = e.changedTouches[0].clientY - this._dragStartY;
            this._dragDeltaX = 0;
            if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > SWIPE_MIN) {
                if (dx < 0) this._carouselNext();
                else this._carouselPrev();
            }
        },
    };
}
