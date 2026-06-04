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
        ...carouselMixin(config.slots.length),

        slots:        config.slots,
        slotInfo:     config.slotInfo,
        routes:       config.routes,
        juniorUpkeep: config.junior_upkeep ?? 10,
        dialogSlot:   null,
        errorMsg:     null,

        init() {
            this._carouselInit();
        },

        prev()    { this._carouselPrev(); },
        next()    { this._carouselNext(); },
        goTo(i)   { this._carouselGoTo(i); },

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
         * @param {string} key - Advisor type key (engineer, scientist, pilot, trader, strategist)
         * @returns {string}
         */
        portraitInitials(key) {
            const map = {
                engineer:   'Ba',
                scientist:  'An',
                pilot:      'Rf',
                trader:     'Ko',
                strategist: 'St',
            };
            return map[key] ?? key.substring(0, 2).toUpperCase();
        },

        /**
         * Returns the portrait image URL for a given advisor slot key.
         * Gender is fixed per slot (index-based alternation, no gender attribute on model).
         * @param {string} key - Advisor type key (engineer, scientist, pilot, trader, strategist)
         * @returns {string}
         */
        portraitImageUrl(key) {
            const map = {
                engineer:   '/img/advisors/construction_master_male.png',
                scientist:  '/img/advisors/analyst_female.png',
                pilot:      '/img/advisors/pilot_male.png',
                trader:     '/img/advisors/trader_female.png',
                strategist: '/img/advisors/strategist_male.png',
            };
            return map[key] ?? '';
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
