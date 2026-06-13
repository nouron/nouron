/**
 * Swipe utilities for Nouron mobile views.
 * Two Alpine.js components:
 *
 * swipeNav({ prev, next })  — swipe left/right triggers URL navigation
 * swipeCarousel(count, initial) — in-page panel carousel (future: Berater, Cantina, Hangar)
 */

function swipeNav({ prev = null, next = null } = {}) {
    return {
        _x0: 0,
        _y0: 0,

        touchStart(e) {
            this._x0 = e.touches[0].clientX;
            this._y0 = e.touches[0].clientY;
        },

        touchEnd(e) {
            const dx = e.changedTouches[0].clientX - this._x0;
            const dy = e.changedTouches[0].clientY - this._y0;
            // Require dominant horizontal movement (>60px, less than 45° vertical)
            if (Math.abs(dx) < 60 || Math.abs(dy) > Math.abs(dx)) return;
            if (dx < 0 && next) window.location.href = next;
            if (dx > 0 && prev) window.location.href = prev;
        },
    };
}

function swipeCarousel(count = 1, initial = 0) {
    return {
        current: initial,
        count,
        _x0: 0,
        _y0: 0,

        get offset() {
            return `translateX(-${this.current * 100}%)`;
        },

        touchStart(e) {
            this._x0 = e.touches[0].clientX;
            this._y0 = e.touches[0].clientY;
        },

        touchEnd(e) {
            const dx = e.changedTouches[0].clientX - this._x0;
            const dy = e.changedTouches[0].clientY - this._y0;
            if (Math.abs(dx) < 60 || Math.abs(dy) > Math.abs(dx)) return;
            if (dx < 0) this.next();
            if (dx > 0) this.prev();
        },

        next() {
            if (this.current < this.count - 1) this.current++;
        },
        prev() {
            if (this.current > 0) this.current--;
        },
        goTo(i) {
            this.current = Math.max(0, Math.min(this.count - 1, i));
        },
    };
}
