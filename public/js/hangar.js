/**
 * hangar.js — Alpine.js component for the hangar carousel screen.
 *
 * Manages hangar bay slots in a swipe carousel.
 * Supports building, dispatching, recalling, and repairing ships.
 * Data is injected server-side via window.__hangarData (set in the Blade view).
 *
 * @param {object} config - Matches the $pageData structure from HangarController.
 */
function hangarCarousel(config) {
    return {
        slots:       config.slots,
        shipTypes:   config.shipTypes,
        hasPilot:    config.hasPilot,
        routes:      config.routes,
        csrfToken:   config.csrfToken,

        activeIndex: 0,
        isMobile:    false,
        touchStartX: 0,
        touchStartY: 0,

        // Per-instance UI state: keyed by instance_id
        modalType:    {},  // instance_id → 'build'|'dispatch'|'repair'|null
        loading:      {},  // instance_id → bool
        error:        {},  // instance_id → string|null

        // Per-instance form values
        buildShipId:  {},  // instance_id → selected ship_id (int)
        dispatchDest: {},  // instance_id → destination string
        dispatchSol:  {},  // instance_id → sol_distance int
        repairAp:     {},  // instance_id → ap_spent int

        init() {
            this.checkBreakpoint();
            window.addEventListener('resize', () => this.checkBreakpoint());

            // Initialize per-slot defaults
            this.slots.forEach(slot => {
                const id = slot.instance_id;
                this.modalType[id]    = null;
                this.loading[id]      = false;
                this.error[id]        = null;
                this.buildShipId[id]  = this.shipTypes.length > 0 ? this.shipTypes[0].id : null;
                this.dispatchDest[id] = '';
                this.dispatchSol[id]  = 1;
                this.repairAp[id]     = 3;
            });
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

        /**
         * Returns slot count info: how many slots have a ship vs total slots.
         */
        get slotInfo() {
            const used  = this.slots.filter(s => s.ship !== null).length;
            const total = this.slots.length;
            return { used, total };
        },

        openModal(instanceId, type) {
            this.error[instanceId] = null;
            this.modalType[instanceId] = type;
        },

        closeModal(instanceId) {
            this.modalType[instanceId] = null;
            this.error[instanceId]     = null;
        },

        /**
         * Maps ship name key to German display label.
         * @param {string} name - ship name key (e.g. 'ship_corvette')
         * @returns {string}
         */
        shipLabel(name) {
            const map = {
                ship_corvette:  'Korvette',
                ship_freighter: 'Frachter',
                ship_drone:     'Drohne',
            };
            return map[name] ?? name;
        },

        /**
         * Returns the CSS width percentage for the ship status bar.
         * status_points range is 0–20.
         * @param {number} points
         * @returns {string}
         */
        statusBarWidth(points) {
            return Math.max(0, Math.min(100, (points / 20) * 100)) + '%';
        },

        /**
         * POST: build a new ship in an empty slot.
         * @param {number} instanceId
         */
        async buildShip(instanceId) {
            this.loading[instanceId] = true;
            this.error[instanceId]   = null;
            try {
                const url = this.routes.build.replace('__ID__', instanceId);
                const res = await this._post(url, { ship_id: this.buildShipId[instanceId] });
                if (res.ok) {
                    this._updateSlot(instanceId, res.slot);
                    this.closeModal(instanceId);
                } else {
                    this.error[instanceId] = res.error ?? 'Fehler beim Bau.';
                }
            } catch (e) {
                this.error[instanceId] = 'Netzwerkfehler.';
            } finally {
                this.loading[instanceId] = false;
            }
        },

        /**
         * POST: dispatch a docked ship on a mission.
         * @param {number} instanceId
         */
        async dispatch(instanceId) {
            this.loading[instanceId] = true;
            this.error[instanceId]   = null;
            try {
                const url = this.routes.dispatch.replace('__ID__', instanceId);
                const res = await this._post(url, {
                    destination:  this.dispatchDest[instanceId],
                    sol_distance: parseInt(this.dispatchSol[instanceId], 10),
                });
                if (res.ok) {
                    this._updateSlot(instanceId, res.slot);
                    this.closeModal(instanceId);
                } else {
                    this.error[instanceId] = res.error ?? 'Fehler beim Entsenden.';
                }
            } catch (e) {
                this.error[instanceId] = 'Netzwerkfehler.';
            } finally {
                this.loading[instanceId] = false;
            }
        },

        /**
         * POST: recall a dispatched ship back to docked state.
         * @param {number} instanceId
         */
        async recall(instanceId) {
            this.loading[instanceId] = true;
            this.error[instanceId]   = null;
            try {
                const url = this.routes.recall.replace('__ID__', instanceId);
                const res = await this._post(url, {});
                if (res.ok) {
                    this._updateSlot(instanceId, res.slot);
                } else {
                    this.error[instanceId] = res.error ?? 'Fehler beim Zurückrufen.';
                }
            } catch (e) {
                this.error[instanceId] = 'Netzwerkfehler.';
            } finally {
                this.loading[instanceId] = false;
            }
        },

        /**
         * POST: repair a docked ship by spending AP.
         * @param {number} instanceId
         */
        async repair(instanceId) {
            this.loading[instanceId] = true;
            this.error[instanceId]   = null;
            try {
                const url = this.routes.repair.replace('__ID__', instanceId);
                const res = await this._post(url, { ap_spent: parseInt(this.repairAp[instanceId], 10) });
                if (res.ok) {
                    this._updateSlot(instanceId, res.slot);
                    this.closeModal(instanceId);
                } else {
                    this.error[instanceId] = res.error ?? 'Fehler beim Reparieren.';
                }
            } catch (e) {
                this.error[instanceId] = 'Netzwerkfehler.';
            } finally {
                this.loading[instanceId] = false;
            }
        },

        /**
         * Replaces the matching slot in this.slots with an updated slot from the server.
         * @param {number} instanceId
         * @param {object} updatedSlot
         */
        _updateSlot(instanceId, updatedSlot) {
            const idx = this.slots.findIndex(s => s.instance_id === instanceId);
            if (idx !== -1) {
                this.slots[idx] = updatedSlot;
            }
        },

        _csrf() {
            return this.csrfToken
                || document.querySelector('meta[name="csrf-token"]')?.content
                || '';
        },

        _post(url, data) {
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
    };
}
