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

        slots: config.slots,
        slotInfo: config.slotInfo,
        routes: config.routes,
        juniorUpkeep: config.junior_upkeep ?? 10,
        dialogSlot: null,
        errorMsg: null,

        init() {
            this._carouselInit();
        },

        prev() {
            this._carouselPrev();
        },
        next() {
            this._carouselNext();
        },
        goTo(i) {
            this._carouselGoTo(i);
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
                this.slots = res.slots;
                this.slotInfo = res.slotInfo;
                this.closeDialogs();
                this.syncCreditsChip(res.credits);
                this.syncApChip(res.apAvailable);
                this.syncHint(res);
            } else {
                this.errorMsg = res.message ?? 'Fehler beim Einstellen.';
            }
        },

        // Hiring/firing changes onboarding hint state — broadcast the fresh hint
        // to the hint bar (partials/hint-bar.blade.php listens on `hint:sync`),
        // same pattern as colony-hexgrid.js::setActiveHint().
        syncHint(res) {
            if ('activeHint' in res) {
                window.dispatchEvent(new CustomEvent('hint:sync', { detail: res.activeHint }));
            }
        },

        // ponytail: resourcebar is server-rendered, not reactive — direct DOM
        // patch mirrors colony-hexgrid.js's syncResbarAp() pattern. Upgrade to
        // a shared Alpine store if more screens need cross-component resource sync.
        syncCreditsChip(credits) {
            if (credits === undefined) return;
            const chip = document.querySelector('.res-Cr');
            if (!chip) return;
            const el = chip.querySelector('.res-amount');
            if (el) el.textContent = credits.toLocaleString('de-DE');
            chip.classList.remove('res-chip--flash');
            void chip.offsetWidth;
            chip.classList.add('res-chip--flash');
            setTimeout(() => chip.classList.remove('res-chip--flash'), 600);
        },

        // Single shared AP pool chip (#resbar-ap, GDD §13.1) — hiring/firing an
        // advisor changes it immediately, but the resourcebar is server-rendered
        // and won't reflect that until a full reload without this patch (Owner-
        // Playtest 2026-08-31: "Forschungs-AP übrig" hint fired while the header
        // still showed the pre-hire AP count).
        syncApChip(apAvailable) {
            if (apAvailable === undefined) return;
            const chip = document.getElementById('resbar-ap');
            if (!chip) return;
            const el = chip.querySelector('.res-amount');
            if (el) el.textContent = apAvailable;
            chip.classList.remove('ap-chip--flash');
            void chip.offsetWidth;
            chip.classList.add('ap-chip--flash');
            setTimeout(() => chip.classList.remove('ap-chip--flash'), 600);
        },

        async doFire() {
            const url = this.routes.fire.replace('__ID__', this.dialogSlot.advisor.id);
            const res = await this.delete(url);
            if (res.ok) {
                this.slots = res.slots;
                this.slotInfo = res.slotInfo;
                this.closeDialogs();
                this.syncApChip(res.apAvailable);
                this.syncHint(res);
            } else {
                this.errorMsg = res.error ?? 'Fehler beim Entlassen.';
            }
        },

        buildingPlaceholderSrc() {
            return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='44' height='44'%3E%3Crect width='44' height='44' rx='6' fill='%23e0e0e8'/%3E%3C/svg%3E";
        },

        advisorPrereqBuilding(key) {
            const map = {
                engineer: { slug: 'command-center', name: 'Command Center', ccLevel: 1 },
                scientist: { slug: 'sciencelab', name: 'Analytiklabor' },
                pilot: { slug: 'hangar', name: 'Hangar' },
                trader: { slug: 'cantina', name: 'Cantina' },
            };
            return map[key] ?? null;
        },

        advisorEffects(key, isPathOpen) {
            // Domain-specific "Bau-AP"/"Forschungs-AP"/etc. prefixes dropped
            // (single-pool AP consolidation, see apTypeLabel() above) — the
            // effect area itself still communicates the advisor's role.
            const map = {
                engineer: 'AP · Gebäudeausbau · Reparatur',
                scientist: 'AP · Techtree · Kenntnisforschung',
                pilot: 'AP · Missionen · Hangar-Events',
                trader: 'AP · Handel · Cantina-Events',
            };
            if (isPathOpen) {
                // Use preview advisor key (path_open_2 → scientist, etc.)
                const preview = { path_open_2: 'scientist', path_open_3: 'pilot', path_open_4: 'trader' };
                return map[preview[key]] ?? 'Pfad noch ausstehend';
            }
            return map[key] ?? '';
        },

        /**
         * Returns the human-readable AP label. Advisors feed one shared colony
         * AP pool now (single-pool consolidation), so the label is neutral and
         * no longer branches on the domain-specific ap_type key.
         * @returns {string}
         */
        apTypeLabel() {
            return 'AP';
        },

        /**
         * Returns the two-letter initials displayed as a watermark in the portrait area.
         * @param {string} key - Advisor type key (engineer, scientist, pilot, trader)
         * @returns {string}
         */
        portraitInitials(key) {
            const map = {
                engineer: 'Ba',
                scientist: 'An',
                pilot: 'Rf',
                trader: 'Ko',
            };
            return map[key] ?? key.substring(0, 2).toUpperCase();
        },

        /**
         * Returns the portrait image URL for a given advisor slot key.
         * Gender is fixed per slot (index-based alternation, no gender attribute on model).
         * @param {string} key - Advisor type key (engineer, scientist, pilot, trader)
         * @returns {string}
         */
        portraitImageUrl(key) {
            const map = {
                engineer: '/img/advisors/construction_master_male.webp',
                scientist: '/img/advisors/analyst_female.webp',
                pilot: '/img/advisors/pilot_male.webp',
                trader: '/img/advisors/trader_female.webp',
            };
            return map[key] ?? '';
        },

        /**
         * CSS background-image value for a given advisor slot key, using image-set()
         * to serve the sharper _lg variant on HiDPI screens. Empty string when no
         * portrait exists (falls back to the placeholder SVG).
         * @param {string} key
         * @returns {string}
         */
        portraitBackgroundStyle(key) {
            const url = this.portraitImageUrl(key);
            if (!url) return '';
            const lgUrl = url.replace(/\.webp$/, '_lg.webp');
            return `background-image: image-set(url('${url}') 1x, url('${lgUrl}') 2x)`;
        },

        _csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        post(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this._csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify(data),
            }).then((r) => r.json());
        },

        delete(url) {
            return fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this._csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            }).then((r) => r.json());
        },
    };
}
