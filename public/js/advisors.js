/**
 * advisors.js — Alpine.js component for the advisor carousel screen.
 *
 * Manages a 5-slot advisor carousel with hire/fire dialogs.
 * Data is injected server-side via window.__advisorData (set in the Blade view).
 *
 * @param {object} config - Matches the $pageData structure from AdvisorController.
 */
function advisorCarousel(config) {
    return {
        slots:        config.slots,
        slotInfo:     config.slotInfo,
        routes:       config.routes,
        juniorUpkeep: config.junior_upkeep ?? 10,
        activeIndex: 0,
        isMobile:    false,
        touchStartX: 0,
        touchStartY: 0,
        dialogSlot:  null,
        errorMsg:    null,

        init() {
            this.checkBreakpoint();
            window.addEventListener('resize', () => this.checkBreakpoint());
        },

        checkBreakpoint() {
            this.isMobile = window.innerWidth < 900;
        },

        prev() {
            if (this.activeIndex > 0) this.activeIndex--;
        },

        next() {
            if (this.activeIndex < this.slots.length - 1) this.activeIndex++;
        },

        goTo(i) {
            this.activeIndex = i;
        },

        /**
         * Returns the inline style string for the carousel track.
         * On desktop the track wraps naturally; on mobile it slides by card width + gap.
         */
        trackStyle() {
            if (!this.isMobile) return '';
            const cardWidth = Math.min(window.innerWidth - 48, 320);
            const gap = 16; // 1rem in px
            const offset = this.activeIndex * (cardWidth + gap);
            return `transform: translateX(-${offset}px)`;
        },

        onTouchStart(e) {
            this.touchStartX = e.touches[0].clientX;
            this.touchStartY = e.touches[0].clientY;
        },

        onTouchEnd(e) {
            const dx = e.changedTouches[0].clientX - this.touchStartX;
            const dy = e.changedTouches[0].clientY - this.touchStartY;
            if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) {
                if (dx < 0) this.next();
                else this.prev();
            }
        },

        openHireDialog(slot) {
            this.dialogSlot = slot;
            this.errorMsg = null;
            this.$nextTick(() => this.$refs.hireDialog.showModal());
        },

        openFireDialog(slot) {
            this.dialogSlot = slot;
            this.errorMsg = null;
            this.$nextTick(() => this.$refs.fireDialog.showModal());
        },

        closeDialogs() {
            this.$refs.hireDialog?.close();
            this.$refs.fireDialog?.close();
            this.dialogSlot = null;
            this.errorMsg = null;
        },

        async doHire() {
            const res = await this.post(this.routes.hire, {
                personell_id: this.dialogSlot.personell_id,
            });
            if (res.ok) {
                this.slots    = res.slots;
                this.slotInfo = res.slotInfo;
                this.closeDialogs();
            } else {
                this.errorMsg = res.error ?? 'Fehler beim Einstellen.';
            }
        },

        async doFire() {
            const url = this.routes.fire.replace('__ID__', this.dialogSlot.advisor.id);
            const res = await this.delete(url);
            if (res.ok) {
                this.slots    = res.slots;
                this.slotInfo = res.slotInfo;
                this.closeDialogs();
            } else {
                this.errorMsg = res.error ?? 'Fehler beim Entlassen.';
            }
        },

        /**
         * Returns the human-readable AP type label for a given ap_type key.
         * @param {string} type
         * @returns {string}
         */
        apTypeLabel(type) {
            const labels = {
                construction: 'Bau-AP',
                research:     'Forschungs-AP',
                navigation:   'Navigations-AP',
                economy:      'Wirtschafts-AP',
                strategy:     'Strategie-AP',
            };
            return labels[type] ?? type;
        },

        /**
         * Returns the two-letter initials displayed as a watermark in the portrait area.
         * @param {string} key - Advisor type key (engineer, scientist, pilot, trader, stratege)
         * @returns {string}
         */
        portraitInitials(key) {
            const map = {
                engineer:  'Ba',
                scientist: 'An',
                pilot:     'Rf',
                trader:    'Ko',
                stratege:  'St',
            };
            return map[key] ?? key.substring(0, 2).toUpperCase();
        },

        _csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        post(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':     this._csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                },
                body: JSON.stringify(data),
            }).then(r => r.json());
        },

        delete(url) {
            return fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN':     this._csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                },
            }).then(r => r.json());
        },
    };
}
