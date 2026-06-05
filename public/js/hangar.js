/**
 * hangar.js — Alpine.js component for the hangar carousel screen.
 *
 * Manages hangar bay slots in a swipe carousel.
 * Ships are requested from Nexus (not built locally).
 * Supports: Nexus request, dispatching, recalling, repairing, and assigning pending ships.
 * Data is injected server-side via window.__hangarData (set in the Blade view).
 *
 * @param {object} config - Matches the window.__hangarData structure from HangarController.
 */
function hangarCarousel(config) {
    return {
        ...carouselMixin(config.slots.length),

        slots:                      config.slots,
        shipTypes:                  config.shipTypes,
        commissionedShipIds:        config.commissionedShipIds,
        hasPilot:                   config.hasPilot,
        routes:                     config.routes,
        csrfToken:                  config.csrfToken,
        i18n:                       config.i18n,

        // New acquisition model data
        shipCosts:                  config.shipCosts ?? {},
        canUseNexusCredit:          config.canUseNexusCredit ?? false,
        hasAktivierterKonsul:       config.hasAktivierterKonsul ?? false,
        verfuegbareVerhandlungsAP:  config.verfuegbareVerhandlungsAP ?? 0,
        pendingShips:               config.pendingShips ?? [],

        // Per-instance UI state: keyed by instance_id
        modalType:    {},
        loading:      {},
        error:        {},

        // Per-instance form values
        dispatchDest: {},
        dispatchSol:  {},
        repairAp:     {},

        // Nexus request modal state (shared across all slots)
        requestModal: {
            open:            false,
            instanceId:      null,
            useNexusCredit:  false,
            consulApSpent:   0,
            loading:         false,
            error:           null,
        },

        // Pending ship assignment state: keyed by ship row id
        pendingAssignTarget: {},
        pendingLoading:      {},
        pendingError:        {},

        init() {
            this._carouselInit();
            this.slots.forEach(slot => {
                const id = slot.instance_id;
                this.modalType[id]    = null;
                this.loading[id]      = false;
                this.error[id]        = null;
                this.dispatchDest[id] = '';
                this.dispatchSol[id]  = 1;
                this.repairAp[id]     = 3;
            });
            this.pendingShips.forEach(ship => {
                this.pendingAssignTarget[ship.id] = '';
                this.pendingLoading[ship.id]      = false;
                this.pendingError[ship.id]        = null;
            });
        },

        prev()    { this._carouselPrev(); },
        next()    { this._carouselNext(); },
        goTo(i)   { this._carouselGoTo(i); },

        /**
         * Returns slot count info: how many slots have a ship vs total slots.
         */
        get slotInfo() {
            const used  = this.slots.filter(s => s.ship !== null).length;
            const total = this.slots.length;
            return { used, total };
        },

        /**
         * Returns slots that have no ship assigned (free bays for pending ship assignment).
         */
        get freeSlots() {
            return this.slots.filter(s => s.ship === null);
        },

        /**
         * Returns the credit savings for the current consul AP selection (50 Cr per AP).
         */
        get consulApSavings() {
            const ap = this.requestModal.consulApSpent ?? 0;
            return ap > 0 ? '−' + (ap * 50) + ' Cr' : '';
        },

        /**
         * Returns the effective cost for a given ship after applying consul AP discount.
         * Each AP spent reduces cost by 50 Cr; result is clamped to 0.
         * @param {number} shipId
         * @returns {number}
         */
        effectiveCostFor(shipId) {
            const entry = this.shipCosts[shipId];
            if (!entry) return 0;
            const discount = (this.requestModal.consulApSpent ?? 0) * 50;
            return Math.max(0, entry.cost - discount);
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
         * Opens the Nexus request dialog for a given empty slot.
         * Resets modal state and pre-selects first ship type.
         * @param {number} instanceId
         */
        openRequestModal(instanceId) {
            this.requestModal = {
                open:           true,
                instanceId,
                useNexusCredit: false,
                consulApSpent:  0,
                loading:        false,
                error:          null,
            };
        },

        /**
         * Closes the Nexus request dialog and resets modal state.
         */
        closeRequestModal() {
            this.requestModal.open = false;
            this.requestModal.error = null;
        },

        /**
         * POST: request a specific ship from Nexus for the currently open empty slot.
         * Called directly from each ship button — no separate confirm step.
         * Endpoint: POST /colony/hangar/request
         * Payload: { instance_id, ship_id, use_nexus_credit, consul_ap_spent }
         * @param {number} shipId
         */
        async submitRequestFor(shipId) {
            this.requestModal.loading = true;
            this.requestModal.error   = null;

            try {
                const res = await this._post(this.routes.request, {
                    instance_id:      this.requestModal.instanceId,
                    ship_id:          shipId,
                    use_nexus_credit: this.requestModal.useNexusCredit ? 1 : 0,
                    consul_ap_spent:  this.requestModal.consulApSpent ?? 0,
                });
                if (res.ok) {
                    this._updateSlot(this.requestModal.instanceId, res.slot);
                    this.closeRequestModal();
                } else {
                    this.requestModal.error = res.error ?? 'Error.';
                }
            } catch {
                this.requestModal.error = 'Network error.';
            } finally {
                this.requestModal.loading = false;
            }
        },

        /**
         * Maps ship name key to display label using i18n strings.
         * @param {string} name - ship name key (e.g. 'ship_corvette')
         * @returns {string}
         */
        shipLabel(name) {
            const map = {
                ship_corvette:  this.i18n.shipCorvette,
                ship_freighter: this.i18n.shipFreighter,
                ship_drone:     this.i18n.shipDrone,
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
                    this.error[instanceId] = res.error ?? 'Error.';
                }
            } catch {
                this.error[instanceId] = 'Network error.';
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
                    this.error[instanceId] = res.error ?? 'Error.';
                }
            } catch {
                this.error[instanceId] = 'Network error.';
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
                    this.error[instanceId] = res.error ?? 'Error.';
                }
            } catch {
                this.error[instanceId] = 'Network error.';
            } finally {
                this.loading[instanceId] = false;
            }
        },

        /**
         * POST: assign a pending (unassigned) ship to a free hangar bay.
         * Endpoint: POST /colony/hangar/assign
         * Payload: { ship_row_id, instance_id }
         * @param {number} shipRowId - pending ship's row id
         */
        async assignShip(shipRowId) {
            const instanceId = this.pendingAssignTarget[shipRowId];
            if (!instanceId) return;

            this.pendingLoading[shipRowId] = true;
            this.pendingError[shipRowId]   = null;

            try {
                const res = await this._post(this.routes.assign, {
                    ship_row_id: shipRowId,
                    instance_id: parseInt(instanceId, 10),
                });
                if (res.ok) {
                    // Remove from pending list
                    this.pendingShips = this.pendingShips.filter(s => s.id !== shipRowId);
                    // Update the newly assigned slot
                    if (res.slot) {
                        this._updateSlot(parseInt(instanceId, 10), res.slot);
                    }
                    delete this.pendingAssignTarget[shipRowId];
                } else {
                    this.pendingError[shipRowId] = res.error ?? 'Error.';
                }
            } catch {
                this.pendingError[shipRowId] = 'Network error.';
            } finally {
                this.pendingLoading[shipRowId] = false;
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
