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

/** Maps ship_id from the slot payload to the config ship key used in the mission catalog. */
const HANGAR_SHIP_ID_TO_KEY = { 85: 'drone', 47: 'freighter', 37: 'corvette' };

/**
 * Minimum hangar level required to request a given ship class from the Nexus.
 * Frontend mirror of HangarService::SHIP_ID_TO_REQUIRED_HANGAR_LEVEL — kept in
 * sync manually; the server is the source of truth and validates this too.
 */
const HANGAR_SHIP_ID_TO_REQUIRED_LEVEL = { 85: 1, 47: 2, 37: 3 };

function hangarCarousel(config) {
    return {
        ...carouselMixin(config.slots.length),

        slots: config.slots,
        shipTypes: config.shipTypes,
        commissionedShipIds: config.commissionedShipIds,
        hasPilot: config.hasPilot,
        routes: config.routes,
        csrfToken: config.csrfToken,
        i18n: config.i18n,

        // New acquisition model data
        shipCosts: config.shipCosts ?? {},
        canUseNexusCredit: config.canUseNexusCredit ?? false,
        hasAktivierterKonsul: config.hasAktivierterKonsul ?? false,
        verfuegbareVerhandlungsAP: config.verfuegbareVerhandlungsAP ?? 0,
        pendingShips: config.pendingShips ?? [],
        missionCatalog: config.missionCatalog ?? [],
        hangarMaxLevel: config.hangarMaxLevel ?? 0,

        // Per-instance UI state: keyed by instance_id
        loading: {},
        error: {},

        // Mission dispatch modal state (shared across all slots)
        missionModal: {
            open: false,
            instanceId: null,
            shipKey: null,
            selectedKey: null,
            selectedDifficulty: null,
            targetIndex: '',
            loading: false,
            error: null,
        },

        // Nexus request modal state (shared across all slots)
        requestModal: {
            open: false,
            instanceId: null,
            useNexusCredit: false,
            consulApSpent: 0,
            loading: false,
            error: null,
        },

        // Pending ship assignment state: keyed by ship row id
        pendingAssignTarget: {},
        pendingLoading: {},
        pendingError: {},

        init() {
            this._carouselInit();
            this.slots.forEach((slot) => {
                const id = slot.instance_id;
                this.loading[id] = false;
                this.error[id] = null;
            });
            this.pendingShips.forEach((ship) => {
                this.pendingAssignTarget[ship.id] = '';
                this.pendingLoading[ship.id] = false;
                this.pendingError[ship.id] = null;
            });
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

        /**
         * Returns slot count info: how many slots have a ship vs total slots.
         */
        get slotInfo() {
            const used = this.slots.filter((s) => s.ship !== null).length;
            const total = this.slots.length;
            return { used, total };
        },

        /**
         * Returns slots that have no ship assigned (free bays for pending ship assignment).
         */
        get freeSlots() {
            return this.slots.filter((s) => s.ship === null);
        },

        /**
         * Returns the credit savings for the current consul AP selection (50 Cr per AP).
         */
        get consulApSavings() {
            const ap = this.requestModal.consulApSpent ?? 0;
            return ap > 0 ? '−' + ap * 50 + ' Cr' : '';
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

        /**
         * Minimum hangar level required to request the given ship type.
         * @param {number} shipId
         * @returns {number}
         */
        shipRequiredLevel(shipId) {
            return HANGAR_SHIP_ID_TO_REQUIRED_LEVEL[shipId] ?? 1;
        },

        /**
         * Whether the current hangar level is high enough to request this ship
         * type from the Nexus. Mirrors the server-side gate in
         * HangarService::requestShip() — server still validates on submit.
         * @param {number} shipId
         * @returns {boolean}
         */
        isShipLevelUnlocked(shipId) {
            return this.hangarMaxLevel >= this.shipRequiredLevel(shipId);
        },

        /**
         * Catalog entries available for the ship type docked in the slot the
         * mission dialog was opened for.
         */
        get missionsForModal() {
            if (!this.missionModal.shipKey) return [];
            return this.missionCatalog.filter((m) => m.ships.includes(this.missionModal.shipKey));
        },

        /**
         * Opens the mission dispatch dialog for a docked ship's slot.
         * @param {number} instanceId
         */
        openMissionDialog(instanceId) {
            const slot = this.slots.find((s) => s.instance_id === instanceId);
            const shipKey = slot?.ship ? (HANGAR_SHIP_ID_TO_KEY[slot.ship.ship_id] ?? null) : null;
            this.missionModal = {
                open: true,
                instanceId,
                shipKey,
                selectedKey: null,
                selectedDifficulty: null,
                targetIndex: '',
                loading: false,
                error: null,
            };
        },

        /**
         * Closes the mission dispatch dialog and resets its error state.
         */
        closeMissionDialog() {
            this.missionModal.open = false;
            this.missionModal.error = null;
        },

        /**
         * Marks a mission card as selected (reveals the target picker if needed).
         * Locked missions (gated / no valid target) cannot be selected.
         * @param {object} mission - catalog entry
         */
        selectMission(mission) {
            if (mission.availability !== 'ok') return;
            if (this.missionModal.selectedKey === mission.key) return;
            this.missionModal.selectedKey = mission.key;
            this.missionModal.selectedDifficulty = mission.difficulty_options?.[0]?.key ?? null;
            this.missionModal.targetIndex = '';
            this.missionModal.error = null;
        },

        /**
         * Marks a difficulty tier as selected for the currently chosen mission.
         * @param {string} difficultyKey
         */
        selectDifficulty(difficultyKey) {
            this.missionModal.selectedDifficulty = difficultyKey;
        },

        /**
         * Whether a catalog entry requires a player-picked target before dispatch.
         * @param {object} mission - catalog entry
         * @returns {boolean}
         */
        missionRequiresTarget(mission) {
            return mission.target_type != null;
        },

        /**
         * Wear forecast for the ship type the dialog was opened for.
         * @param {object} mission - catalog entry
         * @returns {number}
         */
        missionWear(mission) {
            return mission.wear?.[this.missionModal.shipKey] ?? 0;
        },

        /**
         * Display label for a pickable mission target.
         * @param {object} mission - catalog entry
         * @param {object} target - tile {q,r,ring} or knowledge {research_id,label,level}
         * @returns {string}
         */
        targetLabel(mission, target) {
            if (mission.target_type === 'knowledge') {
                return target.label + ' (Lv ' + target.level + ')';
            }
            return '(' + target.q + '|' + target.r + ') — Ring ' + target.ring;
        },

        /**
         * Start button handler: first click selects the mission (revealing the
         * target picker when one is required), second click submits.
         * @param {object} mission - catalog entry
         */
        startMission(mission) {
            if (mission.availability !== 'ok' || this.missionModal.loading) return;
            if (this.missionModal.selectedKey !== mission.key) {
                this.selectMission(mission);
                return; // let the player see the difficulty (and target, if any) picker first
            }
            if (this.missionRequiresTarget(mission) && this.missionModal.targetIndex === '') return;
            if (!this.missionModal.selectedDifficulty) return;
            this.submitMission(mission);
        },

        /**
         * Builds the dispatch target payload from the picked target index.
         * @param {object} mission - catalog entry
         * @returns {object|null} {q,r} | {research_id} | null
         */
        _missionTargetPayload(mission) {
            const idx = parseInt(this.missionModal.targetIndex, 10);
            const target = mission.targets?.[idx];
            if (!target) return null;
            if (mission.target_type === 'knowledge') {
                return { research_id: target.research_id };
            }
            return { q: target.q, r: target.r };
        },

        /**
         * POST: dispatch the docked ship on the given catalog mission.
         * Endpoint: POST /colony/hangar/{instanceId}/dispatch
         * Payload: { mission_key, target? }
         * @param {object} mission - catalog entry
         */
        async submitMission(mission) {
            this.missionModal.loading = true;
            this.missionModal.error = null;
            try {
                const url = this.routes.dispatch.replace('__ID__', this.missionModal.instanceId);
                const res = await this._post(url, {
                    mission_key: mission.key,
                    difficulty: this.missionModal.selectedDifficulty,
                    target: this.missionRequiresTarget(mission) ? this._missionTargetPayload(mission) : null,
                });
                if (res.ok) {
                    this._updateSlot(this.missionModal.instanceId, res.slot);
                    this.syncHangarResources(res);
                    this.closeMissionDialog();
                } else {
                    this.missionModal.error = res.error ?? 'Error.';
                }
            } catch {
                this.missionModal.error = 'Network error.';
            } finally {
                this.missionModal.loading = false;
            }
        },

        /**
         * Opens the Nexus request dialog for a given empty slot.
         * Resets modal state and pre-selects first ship type.
         * @param {number} instanceId
         */
        openRequestModal(instanceId) {
            this.requestModal = {
                open: true,
                instanceId,
                useNexusCredit: false,
                consulApSpent: 0,
                loading: false,
                error: null,
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
         * Response: { ok, slots, pending } — the ordered ship lands in the
         * "pending" list, not directly in a bay (bay stays empty until the
         * player assigns the delivered ship via assignShip()), so only
         * `pending` needs to be applied here.
         * @param {number} shipId
         */
        async submitRequestFor(shipId) {
            this.requestModal.loading = true;
            this.requestModal.error = null;

            try {
                const res = await this._post(this.routes.request, {
                    instance_id: this.requestModal.instanceId,
                    ship_id: shipId,
                    use_nexus_credit: this.requestModal.useNexusCredit ? 1 : 0,
                    consul_ap_spent: this.requestModal.consulApSpent ?? 0,
                });
                if (res.ok) {
                    this.pendingShips = res.pending;
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
                ship_corvette: this.i18n.shipCorvette,
                ship_freighter: this.i18n.shipFreighter,
                ship_drone: this.i18n.shipDrone,
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
         * POST: recall a dispatched ship back to docked state.
         * @param {number} instanceId
         */
        async recall(instanceId) {
            this.loading[instanceId] = true;
            this.error[instanceId] = null;
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
         * POST: repair a docked ship — fixed cost (1 Construction-AP per click).
         * @param {number} instanceId
         */
        async repair(instanceId) {
            this.loading[instanceId] = true;
            this.error[instanceId] = null;
            try {
                const url = this.routes.repair.replace('__ID__', instanceId);
                const res = await this._post(url, {});
                if (res.ok) {
                    this._updateSlot(instanceId, res.slot);
                    this.syncHangarResources(res);
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
            this.pendingError[shipRowId] = null;

            try {
                const res = await this._post(this.routes.assign, {
                    ship_row_id: shipRowId,
                    instance_id: parseInt(instanceId, 10),
                });
                if (res.ok) {
                    // Remove from pending list
                    this.pendingShips = this.pendingShips.filter((s) => s.id !== shipRowId);
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
            const idx = this.slots.findIndex((s) => s.instance_id === instanceId);
            if (idx !== -1) {
                this.slots[idx] = updatedSlot;
            }
        },

        /**
         * Syncs the resourcebar (layout header, outside this Alpine component)
         * after dispatch/repair — frontend-conventions.md live-sync rule. Mirrors
         * colony-hexgrid.js's updateAp() pattern; the previous value is read
         * from the DOM rather than tracked locally since hangar.js doesn't
         * otherwise need AP/resource state for affordability checks. One shared
         * colony AP pool (GDD §13.1) — no more per-domain chip.
         * @param {object} res - JSON response, may carry apAvailable/organika
         */
        syncHangarResources(res) {
            if (res.apAvailable !== undefined) this.syncResbarChip('#resbar-ap', res.apAvailable);
            if (res.organika !== undefined) this.syncResbarChip('.res-Or', res.organika);
        },

        /**
         * Writes a new value into a resourcebar chip's .res-amount span and
         * briefly flashes the chip if the value dropped.
         * @param {string} selector - CSS selector for the chip (id or class)
         * @param {number} value
         */
        syncResbarChip(selector, value) {
            const el = document.querySelector(`${selector} .res-amount`);
            if (!el) return;
            const old = parseInt(el.textContent.replace(/[^0-9-]/g, ''), 10);
            el.textContent = typeof value === 'number' ? value.toLocaleString('de-DE') : value;
            if (!Number.isNaN(old) && value < old) this.flashResbarChip(selector);
        },

        /**
         * @param {string} selector - CSS selector for the chip (id or class)
         */
        flashResbarChip(selector) {
            const chip = document.querySelector(selector);
            if (!chip) return;
            const flashClass = chip.classList.contains('ap-chip') ? 'ap-chip--flash' : 'res-chip--flash';
            chip.classList.remove(flashClass);
            void chip.offsetWidth; // force reflow so the animation restarts even mid-flash
            chip.classList.add(flashClass);
            setTimeout(() => chip.classList.remove(flashClass), 700);
        },

        _csrf() {
            return this.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        _post(url, data) {
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
    };
}
