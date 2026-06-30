/**
 * Colony Hex-Grid: Alpine.js component + plain-JS SVG renderer.
 *
 * Coordinate system: Axial (q, r), Pointy-top hexagons.
 * Pixel conversion:
 *   px = cx + SIZE * sqrt(3) * (q + r/2)
 *   py = cy + SIZE * 1.5 * r
 */

// ── Tile metadata ─────────────────────────────────────────────────────────────

const TILE_LABELS = {
    terrain_empty: 'Freies Feld',
    terrain_hazard: 'Gefahrenzone',
    terrain_impassable: 'Unpassierbar',
    regolith_rich: 'Regolith (reich)',
    regolith_normal: 'Regolith (normal)',
    regolith_poor: 'Regolith (karg)',
    event_wreck: 'Wrack',
    event_ruin: 'Ruine',
    event_bunker: 'Bunker',
    event_probe: 'Sonde',
    event_crystal: 'Kristallfeld',
    event_vent: 'Thermalspalte',
    event_cave: 'Höhle',
    event_cache: 'Cache',
    event_signal: 'Signal',
    event_anomaly: 'Anomalie',
};

const TILE_COLORS = {
    terrain_empty: '#c8cdd6',
    terrain_hazard: '#e8b87a',
    terrain_impassable: '#7a7f8a',
    regolith_rich: '#5a9fd4',
    regolith_normal: '#7fb5dc',
    regolith_poor: '#a8cfe6',
};

const TILE_STROKES = {
    terrain_empty: '#a0a8b4',
    terrain_hazard: '#c08040',
    terrain_impassable: '#555',
    regolith_rich: '#3a7ab0',
    regolith_normal: '#5090c0',
    regolith_poor: '#7aaace',
};

const CC_COLOR = '#7ec87e';
const CC_STROKE = '#2e7d32';
// Colony-zone terrain: noticeably lighter than exploration-zone so "buildable" is
// immediately visible without clicking. Exploration zone keeps the darker neutral grey.
const BUILDABLE_COLOR = '#eaedf5'; // explored colony-zone terrain_empty
const BUILDABLE_STROKE = '#b8c0d0';
// Fog (state 3+4): clearly readable slate/blue-grey, distinct from the warm-grey
// buildable terrain. Fog must stand out so the player sees what is still hidden.
const FOG_COLOR = '#a9b2c4'; // unexplored colony zone (state 3) — buildable but hidden
const FOG_STROKE = '#7f8aa0';
const LOCKED_COLOR = '#9aa4b8'; // unexplored exploration zone (state 4) — scout target
const LOCKED_STROKE = '#6f7a90';
const EVENT_COLOR = '#e8d89a';
const EVENT_STROKE = '#b09a40';

// Abbreviations shown on tiles with buildings
const BUILDING_ABBR = {
    building_commandCenter: 'CC',
    building_harvester: 'HV',
    building_housingComplex: 'WH',
    building_depot: 'LH',
    building_sciencelab: 'AL',
    building_temple: 'RS',
    building_bioFacility: 'AD',
    building_hangar: 'HG',
    building_infirmary: 'KS',
    building_monument: 'KD',
    building_bar: 'CA',
    building_securityHub: 'HUB',
};

// ── Event-type display names (discovery popup) ────────────────────────────────

const EVENT_TYPE_NAMES = {
    event_ruin: 'Alte Ruinen',
    event_crystal: 'Kristallformation',
    event_wreck: 'Schiffswrack',
};

// ── Alpine component ──────────────────────────────────────────────────────────

function colonyHexView(config) {
    return {
        tiles: config.tiles,
        colony: config.colony,
        ccLevel: config.ccLevel,
        ccBuildingId: config.ccBuildingId ?? 25,
        buildings: config.buildings ?? [],
        routes: config.routes ?? {},
        i18n: config.i18n ?? {},
        apNav: config.apNav ?? 0,
        apConstruction: config.apConstruction ?? 0,
        regolith: config.regolith ?? 0,
        werkstoffe: config.werkstoffe ?? 0,
        freeSupply: config.freeSupply ?? 0,
        trust: config.trust ?? 0,
        currentSol: config.currentSol ?? 0,
        solLimit: config.solLimit ?? 100,
        activeHint: config.activeHint ?? null,
        merchantVisit: config.merchantVisit ?? null,
        merchantItems: config.merchantItems ?? [],
        uplinkBuildingId: config.uplinkBuildingId ?? 54,
        compoundImportPrice: config.compoundImportPrice ?? 90,
        exploreCostPerRing: config.exploreCostPerRing ?? { 1: 1, 2: 2, 3: 3 },
        exploreCostDefault: config.exploreCostDefault ?? 1,
        phaseProgress: config.phaseProgress ?? null,
        nexusImportAmount: 10,
        selectedTile: null,
        buildMode: false,
        pendingBuilding: null,
        availableBuildings: [],
        eventDiscovery: null, // set to the tile object when a discovery popup should show
        toastMessage: '',
        toastVisible: false,
        toastType: 'error', // 'error' | 'info'
        _toastTimer: null,
        levelupNotice: null, // set to label string when a building levels up
        _lvlTimer: null,
        _apFlashTimers: {}, // per-chip flash timers (resbar AP chips)
        harvesterMoveMode: false,
        buildTargetTile: null,
        _svgPolygons: new Map(),
        _tilePositions: new Map(),
        _gridEl: null,
        _panState: { x: 0, y: 0 },

        init() {
            this.$nextTick(async () => {
                // Cache the grid container: $refs lookups fail when redrawGrid is
                // triggered from a button inside an x-if template that Alpine has
                // already removed from the DOM (e.g. the relocate button).
                this._gridEl = this.$refs.hexgrid;
                this.redrawGrid();
                const params = new URLSearchParams(window.location.search);
                const buildId = parseInt(params.get('build'), 10);
                if (buildId) {
                    await this.toggleBuildMode();
                    const match = this.availableBuildings.find((b) => b.building_id === buildId);
                    if (match) this.selectPendingBuilding(match);
                }
            });

            // The hint bar now lives in layouts/colony.blade.php (partials/hint-bar.blade.php)
            // and owns its own dismiss flow — mirror its result here so grid highlighting
            // (which still depends on this.activeHint) stays in sync.
            window.addEventListener('hint:dismissed', (e) => {
                this.activeHint = e.detail;
                this.redrawGrid();
            });
        },

        // ── Grid rendering ────────────────────────────────────────────────────

        redrawGrid() {
            this._tilePositions.clear();
            initHexGrid(this._gridEl ?? this.$refs.hexgrid, this.tiles, {
                onSelect: (tile) => this.onTileClick(tile),
                onHarvesterMoveConfirm: (tile) => this.doMoveHarvester(tile),
                polygonMap: this._svgPolygons,
                positionMap: this._tilePositions,
                buildings: this.buildings,
                buildMode: this.buildMode,
                pendingBuilding: this.pendingBuilding,
                activeHint: this.activeHint ?? null,
                harvesterMoveMode: this.harvesterMoveMode,
                harvesterBuilding: this.harvesterMoveMode ? this.harvesterBuilding() : null,
                panState: this._panState,
            });
        },

        // ── Tile selection ────────────────────────────────────────────────────

        onTileClick(tile) {
            // Harvester move mode: a click/tap only selects (the preview arrow is
            // already shown by the tile's pointerdown/mouseenter listeners in
            // initHexGrid) — the actual move requires pressing and holding the
            // target (same gesture on desktop and mobile). Invalid targets still
            // get feedback so clicking around isn't a silent dead end.
            if (this.harvesterMoveMode) {
                if (!this.isHarvesterTarget(tile)) {
                    this.showToast(this.i18n.harvesterMoveInvalidTarget, 'info');
                }
                return;
            }
            if (this.buildMode && this.pendingBuilding) {
                if (isBuildableTile(tile) && !this.buildingForTile(tile)) this.doPlaceBuilding(tile);
                return;
            }
            // Tile has a building but build mode is active without selection:
            // exit build mode so the tile-info panel (with AP invest button) renders.
            if (this.buildMode && this.buildingForTile(tile)) {
                this.buildMode = false;
                this.pendingBuilding = null;
                this.harvesterMoveMode = false;
                this.$nextTick(() => this.redrawGrid());
            }
            this.selectTile(tile);
        },

        selectTile(tile) {
            if (this.selectedTile) {
                const prev = this._svgPolygons.get(`${this.selectedTile.q},${this.selectedTile.r}`);
                if (prev) {
                    prev.setAttribute('stroke', prev._defaultStroke ?? '#555');
                    prev.setAttribute('stroke-width', prev._defaultStrokeW ?? '1.5');
                }
            }
            this.selectedTile = tile;
            const cur = this._svgPolygons.get(`${tile.q},${tile.r}`);
            if (cur) {
                cur.setAttribute('stroke', '#c0392b');
                cur.setAttribute('stroke-width', '3');
            }
        },

        // ── Build mode ────────────────────────────────────────────────────────

        async toggleBuildMode() {
            if (this.buildMode) {
                this.buildMode = false;
                this.pendingBuilding = null;
                this.buildTargetTile = null;
                this.$nextTick(() => this.redrawGrid());
                return;
            }
            const data = await this.get(this.routes.buildingsAvailable);
            this.availableBuildings = data.buildings ?? [];
            this.buildMode = true;
            this.selectedTile = null;
            this.$nextTick(() => this.redrawGrid());
        },

        async startBuildForTile(tile) {
            const data = await this.get(this.routes.buildingsAvailable);
            this.availableBuildings = data.buildings ?? [];
            this.buildTargetTile = tile;
            this.buildMode = true;
            this.selectedTile = null;
            this.$nextTick(() => this.redrawGrid());
        },

        cancelBuildMode() {
            this.buildMode = false;
            this.pendingBuilding = null;
            this.buildTargetTile = null;
            this.$nextTick(() => this.redrawGrid());
        },

        selectPendingBuilding(building) {
            this.pendingBuilding = this.pendingBuilding?.building_id === building.building_id ? null : building;
            if (this.pendingBuilding && this.buildTargetTile) {
                this.doPlaceBuilding(this.buildTargetTile);
            } else {
                this.$nextTick(() => this.redrawGrid());
            }
        },

        // ── Tile actions ──────────────────────────────────────────────────────

        async doExploreTile(tile) {
            const res = await this.post(this.routes.explore, { q: tile.q, r: tile.r });
            if (res.ok) {
                this.updateTile(res.tile);
                this.selectedTile = res.tile;
                this.updateAp(res);
                this.updateHint(res);
                this.$nextTick(() => this.redrawGrid());
            } else {
                const msg = res.message ?? res.error;
                this.showToast(msg, 'error');
            }
        },

        async doDeepScan(tile) {
            const res = await this.post(this.routes.deepScan, { q: tile.q, r: tile.r });
            if (res.ok) {
                this.updateTile(res.tile);
                this.selectedTile = res.tile;
                this.updateAp(res);
                this.updateHint(res);
                // Show discovery popup when the scan reveals an event on this tile
                if (res.tile.event_type) {
                    this.eventDiscovery = res.tile;
                }
                this.$nextTick(() => this.redrawGrid());
            } else {
                const msg = res.message ?? res.error;
                this.showToast(msg, 'error');
            }
        },

        // ── Building actions ──────────────────────────────────────────────────

        async doPlaceBuilding(tile) {
            const res = await this.post(this.routes.placeBuilding, {
                building_id: this.pendingBuilding.building_id,
                q: tile.q,
                r: tile.r,
            });
            if (res.ok) {
                this.updateBuilding(res.building);
                this.buildMode = false;
                this.pendingBuilding = null;
                this.buildTargetTile = null;
                this.selectedTile = tile;
                this.updateAp(res);
                this.updateHint(res);
                this.$nextTick(() => this.redrawGrid());
            } else {
                const msg = res.message ?? res.error;
                this.showToast(msg, 'error');
            }
        },

        async doInvestAp(building) {
            const res = await this.post(this.routes.investBuilding, {
                building_id: building.building_id,
                instance_id: building.instance_id ?? 1,
            });
            if (res.ok) {
                this.updateBuilding(res.building);
                this.updateAp(res);
                this.updateHint(res);
                if ('phase_progress' in res) this.phaseProgress = res.phase_progress;
                if (res.leveled_up) {
                    this.showLevelupNotice(res.building.label);
                    if (this.selectedTile) this.selectedTile = { ...this.selectedTile };
                    if (res.nav_unlocked) {
                        setTimeout(() => window.location.reload(), 600);
                    }
                }
                if (res.showHarvesterMoveTip) {
                    this.showToast(this.i18n.harvesterMoveTip, 'info');
                }
                if (res.tiles) {
                    this.tiles = res.tiles;
                    if (this.selectedTile) {
                        const updated = res.tiles.find(
                            (t) => t.q === this.selectedTile.q && t.r === this.selectedTile.r,
                        );
                        if (updated) this.selectedTile = updated;
                    }
                    this.$nextTick(() => this.redrawGrid());
                } else if (this.selectedTile) {
                    this.selectedTile = { ...this.selectedTile };
                }
            } else {
                let msg = res.message ?? res.error;
                if (res.error === 'resource_limit' && res.cost?.[3]) {
                    const needed = res.cost[3];
                    const have = this.regolith ?? 0;
                    msg += ` (${needed} RG benötigt, ${have} RG vorhanden)`;
                }
                this.showToast(msg, 'error');
            }
        },

        canRepair(building) {
            if (!building || building.level < 1) return false;
            const maxSp = building.max_status_points ?? 20;
            return building.status_points < maxSp;
        },

        // Percent gained per repair click (1 status point as % of max).
        repairStepPct(building) {
            const maxSp = building?.max_status_points ?? 20;
            return Math.round(100 / maxSp);
        },

        async doRepair(building) {
            const res = await this.post(this.routes.repairBuilding, {
                building_id: building.building_id,
                instance_id: building.instance_id ?? 1,
            });
            if (res.ok) {
                this.updateBuilding(res.building);
                this.updateAp(res);
                this.updateHint(res);
                if (this.selectedTile) this.selectedTile = { ...this.selectedTile };
            } else {
                const msg = res.message ?? res.error;
                this.showToast(msg, 'error');
            }
        },

        // ── Nexus compound import ─────────────────────────────────────────────

        // Current Uplink-Station level (0 if not built). Gates the Nexus import panel.
        uplinkLevel() {
            const uplink = this.buildings.find((b) => b.building_id === this.uplinkBuildingId);
            return uplink ? uplink.level : 0;
        },

        async doNexusImport() {
            const amount = parseInt(this.nexusImportAmount, 10);
            if (!amount || amount < 1) return;
            const res = await this.post(this.routes.nexusImport, { amount });
            if (res.ok) {
                this.showToast(
                    (this.i18n.nexusImportSuccess ?? '').replace(':amount', res.amount).replace(':cost', res.cost),
                    'info',
                );
            } else {
                this.showToast(res.message ?? res.error ?? this.i18n.nexusImportError, 'error');
            }
        },

        // ── Harvester relocation ──────────────────────────────────────────────

        startHarvesterMove() {
            this.harvesterMoveMode = true;
            this.buildMode = false;
            this.pendingBuilding = null;
            this.selectedTile = null;
            this.$nextTick(() => this.redrawGrid());
        },

        cancelHarvesterMove() {
            this.harvesterMoveMode = false;
            this.$nextTick(() => this.redrawGrid());
        },

        isHarvesterTarget(tile) {
            if (!tile.is_explored) return false;
            if (!tile.tile_type.startsWith('regolith_')) return false;
            return !this.buildings.some((b) => b.tile_x === tile.q && b.tile_y === tile.r);
        },

        harvesterBuilding() {
            return this.buildings.find((b) => b.building_key === 'building_harvester') ?? null;
        },

        hasHarvesterTargets() {
            return this.tiles.some((t) => this.isHarvesterTarget(t));
        },

        async doMoveHarvester(tile) {
            const hv = this.harvesterBuilding();
            if (!hv) return;
            const oldPos = hv.tile_x !== null ? this._tilePositions.get(`${hv.tile_x},${hv.tile_y}`) : null;
            const newPos = this._tilePositions.get(`${tile.q},${tile.r}`);
            let res;
            try {
                res = await this.post(this.routes.placeBuilding, {
                    building_id: hv.building_id,
                    q: tile.q,
                    r: tile.r,
                });
            } catch {
                this.showToast(this.i18n.networkError ?? 'Network error.', 'error');
                return;
            }
            if (res.ok) {
                this.updateBuilding(res.building);
                this.harvesterMoveMode = false;
                this.selectedTile = tile;
                this.updateAp(res);
                this.updateHint(res);
                if (oldPos && newPos) {
                    this.animateHarvesterMove(oldPos, newPos);
                    setTimeout(() => this.$nextTick(() => this.redrawGrid()), 400);
                } else {
                    this.$nextTick(() => this.redrawGrid());
                }
            } else {
                const msg = res.message ?? res.error;
                this.showToast(msg, 'error');
            }
        },

        animateHarvesterMove(oldPos, newPos) {
            const svg = (this._gridEl ?? this.$refs.hexgrid)?.querySelector('svg');
            if (!svg) return;
            const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            dot.setAttribute('cx', oldPos.cx);
            dot.setAttribute('cy', oldPos.cy);
            dot.setAttribute('r', '10');
            dot.setAttribute('fill', '#f59e0b');
            dot.setAttribute('stroke', '#b45309');
            dot.setAttribute('stroke-width', '2');
            dot.setAttribute('pointer-events', 'none');
            dot.setAttribute('class', 'hv-move-dot');
            dot.style.setProperty('--dx', `${newPos.cx - oldPos.cx}px`);
            dot.style.setProperty('--dy', `${newPos.cy - oldPos.cy}px`);
            svg.appendChild(dot);
            setTimeout(() => dot.remove(), 450);
        },

        // Ring-staggered explore cost (matches ColonyTileService::exploreTile()).
        exploreCostFor(tile) {
            return this.exploreCostPerRing[tile?.ring] ?? this.exploreCostDefault;
        },

        hexDistance(q1, r1, q2, r2) {
            const dq = q1 - q2,
                dr = r1 - r2;
            return Math.max(Math.abs(dq), Math.abs(dr), Math.abs(dq + dr));
        },

        // ── State helpers ─────────────────────────────────────────────────────

        updateAp(res) {
            if (res.apNav !== undefined) {
                if (res.apNav < this.apNav) this.flashApChip('resbar-ap-nav');
                this.apNav = res.apNav;
                this.syncResbarAp('resbar-ap-nav', res.apNav);
            }
            if (res.apConstruction !== undefined) {
                if (res.apConstruction < this.apConstruction) this.flashApChip('resbar-ap-build');
                this.apConstruction = res.apConstruction;
                this.syncResbarAp('resbar-ap-build', res.apConstruction);
            }
            if (res.regolith !== undefined) {
                if (res.regolith < this.regolith) this.flashResChip('.res-Rg');
                this.regolith = res.regolith;
                this.syncResbarAmount('.res-Rg', res.regolith);
            }
            if (res.werkstoffe !== undefined) {
                if (res.werkstoffe < this.werkstoffe) this.flashResChip('.res-Co');
                this.werkstoffe = res.werkstoffe;
                this.syncResbarAmount('.res-Co', res.werkstoffe);
            }
            if (res.freeSupply !== undefined) this.freeSupply = res.freeSupply;
        },

        syncResbarAmount(selector, value) {
            const el = document.querySelector(`${selector} .res-amount`);
            if (el) el.textContent = value.toLocaleString('de-DE');
        },

        // Build chip affordability: placing always costs exactly 1 Bau-AP
        // (see ColonyController::placeBuilding) — full resource/supply cost is
        // paid on placement too, so all three gates must clear up front.
        canAffordBuilding(b) {
            if (this.apConstruction < 1) return false;
            if ((b.build_cost?.[3] ?? 0) > this.regolith) return false;
            if ((b.build_cost?.[4] ?? 0) > this.werkstoffe) return false;
            if ((b.supply_cost ?? 0) > this.freeSupply) return false;
            return true;
        },

        // The AP chips live in the resource bar (layout header), outside this
        // Alpine component — sync them via DOM after every AJAX action.
        syncResbarAp(chipId, value) {
            const el = document.querySelector(`#${chipId} .res-amount`);
            if (el) el.textContent = value;
        },

        // Briefly pulse a resource chip (by CSS selector) when its amount drops.
        flashResChip(selector) {
            const chip = document.querySelector(selector);
            if (!chip) return;
            chip.classList.remove('res-chip--flash');
            void chip.offsetWidth;
            chip.classList.add('res-chip--flash');
            setTimeout(() => chip.classList.remove('res-chip--flash'), 600);
        },

        // Briefly pulse an AP chip so the player notices the pool shrinking.
        flashApChip(chipId) {
            const chip = document.getElementById(chipId);
            if (!chip) return;
            clearTimeout(this._apFlashTimers[chipId]);
            chip.classList.remove('ap-chip--flash');
            // force reflow so the animation restarts even mid-flash
            void chip.offsetWidth;
            chip.classList.add('ap-chip--flash');
            this._apFlashTimers[chipId] = setTimeout(() => chip.classList.remove('ap-chip--flash'), 700);
        },

        updateHint(res) {
            if ('activeHint' in res) this.setActiveHint(res.activeHint);
        },

        // Updates grid-highlight state and broadcasts the change to the global hint
        // bar (partials/hint-bar.blade.php) via a window event — that component owns
        // the "done" confirmation flash and the dismiss button now.
        setActiveHint(newHint) {
            this.activeHint = newHint;
            window.dispatchEvent(new CustomEvent('hint:sync', { detail: newHint }));
        },

        // ── Merchant ──────────────────────────────────────────────────────────

        hasMerchant() {
            return this.merchantVisit !== null;
        },

        updateTile(tile) {
            const idx = this.tiles.findIndex((t) => t.q === tile.q && t.r === tile.r);
            if (idx !== -1) this.tiles[idx] = tile;
        },

        updateBuilding(building) {
            const idx = this.buildings.findIndex(
                (b) => b.building_id === building.building_id && b.instance_id === building.instance_id,
            );
            if (idx !== -1) this.buildings[idx] = building;
            else this.buildings.push(building);
        },

        tileHeading(tile) {
            if (tile.q === 0 && tile.r === 0) return 'Kommandozentrale';
            if (!tile.is_explored) return 'Unbekanntes Terrain';
            return TILE_LABELS[tile.tile_type] ?? tile.tile_type;
        },

        tileTypeName(type) {
            return TILE_LABELS[type] ?? type;
        },

        eventTypeName(type) {
            return EVENT_TYPE_NAMES[type] ?? 'Unbekanntes Phänomen';
        },

        dismissEventDiscovery() {
            this.eventDiscovery = null;
        },

        // ── Toast notifications ───────────────────────────────────────────────

        showToast(message, type = 'error') {
            if (this._toastTimer) clearTimeout(this._toastTimer);
            this.toastMessage = message;
            this.toastType = type;
            this.toastVisible = true;
            this._toastTimer = setTimeout(() => {
                this.toastVisible = false;
            }, 3500);
        },

        showLevelupNotice(label) {
            if (this._lvlTimer) clearTimeout(this._lvlTimer);
            this.levelupNotice = label;
            this._lvlTimer = setTimeout(() => {
                this.levelupNotice = null;
            }, 2200);
        },

        buildingForTile(tile) {
            if (!tile) return null;
            if (tile.q === 0 && tile.r === 0) {
                return this.buildings.find((b) => b.building_id === this.ccBuildingId) ?? null;
            }
            return this.buildings.find((b) => b.tile_x === tile.q && b.tile_y === tile.r) ?? null;
        },

        // The building on the currently selected tile, or null. Single source
        // of truth for the sidebar so the markup never repeats buildingForTile().
        get selectedBuilding() {
            return this.buildingForTile(this.selectedTile);
        },

        // Levelling up is possible while the building is below its max level
        // (null max_level = unlimited).
        buildingCanLevelUp(building) {
            return !!building && (building.max_level === null || building.level < building.max_level);
        },

        // AP invested towards the next level, as a percentage.
        // Remaining tile resource (regolith), as a percentage of its maximum.
        resourcePct(tile) {
            if (!tile || !tile.resource_max) return 0;
            return Math.round((tile.resource_amount / tile.resource_max) * 100);
        },

        isBuildableTile(tile) {
            return (
                tile &&
                tile.is_colony_zone &&
                tile.is_explored &&
                tile.tile_type.startsWith('terrain_') &&
                tile.tile_type !== 'terrain_impassable'
            );
        },

        // ── HTTP helpers ──────────────────────────────────────────────────────

        get(url) {
            return fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).then((r) => r.json());
        },

        post(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            }).then((r) => r.json());
        },
    };
}

// ── Tile helpers ──────────────────────────────────────────────────────────────

function isBuildableTile(tile) {
    // Colony-zone grants build permission regardless of fog: building on a still-
    // fogged zone tile is allowed and reveals it server-side (settle → see).
    return tile.is_colony_zone && tile.tile_type.startsWith('terrain_') && tile.tile_type !== 'terrain_impassable';
}

function isHarvesterTargetTile(tile, buildingsByTile) {
    return tile.is_explored && tile.tile_type.startsWith('regolith_') && !buildingsByTile?.has(`${tile.q},${tile.r}`);
}

// Rounds fractional cube coordinates to the nearest valid hex (Red Blob Games
// "cube_round") — needed because linear interpolation between two hex centers
// rarely lands exactly on a third hex's center.
function cubeRound(x, y, z) {
    let rx = Math.round(x),
        ry = Math.round(y),
        rz = Math.round(z);
    const dx = Math.abs(rx - x),
        dy = Math.abs(ry - y),
        dz = Math.abs(rz - z);
    if (dx > dy && dx > dz) rx = -ry - rz;
    else if (dy > dz) ry = -rx - rz;
    else rz = -rx - ry;
    return [rx, rz]; // axial [q, r] (cube x=q, z=r)
}

// Hex-grid line draw (Red Blob Games): the sequence of hex tiles a straight
// line from (q1,r1) to (q2,r2) actually passes through — used so the
// Harvester-move preview arrow visually follows hex centers instead of
// floating over them in a straight pixel line.
function hexLinePath(q1, r1, q2, r2) {
    const dq = q2 - q1,
        dr = r2 - r1;
    const n = Math.max(Math.abs(dq), Math.abs(dr), Math.abs(dq + dr));
    if (n === 0) return [[q1, r1]];

    const x1 = q1,
        z1 = r1,
        y1 = -x1 - z1;
    const x2 = q2,
        z2 = r2,
        y2 = -x2 - z2;
    const pts = [];
    for (let i = 0; i <= n; i++) {
        const t = i / n;
        pts.push(cubeRound(x1 + (x2 - x1) * t, y1 + (y2 - y1) * t, z1 + (z2 - z1) * t));
    }
    return pts;
}

function drawHarvesterArrow(group, pixelPath, apCost) {
    group.innerHTML = '';
    group.style.display = '';
    if (pixelPath.length < 2) return;

    // Shorten the first/last segment so the line doesn't overlap the HV icon
    // or the target tile's center, while intermediate hex-center points stay
    // exact (that's the point — the path bends through real hex centers).
    const path = pixelPath.slice();
    const trim = (a, b, px) => {
        const dx = b.cx - a.cx,
            dy = b.cy - a.cy;
        const len = Math.sqrt(dx * dx + dy * dy) || 1;
        return { cx: a.cx + (dx / len) * px, cy: a.cy + (dy / len) * px };
    };
    path[0] = trim(path[0], path[1], 12);
    path[path.length - 1] = trim(path[path.length - 1], path[path.length - 2], 16);

    const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
    poly.setAttribute('points', path.map((p) => `${p.cx},${p.cy}`).join(' '));
    poly.setAttribute('fill', 'none');
    poly.setAttribute('stroke', '#f59e0b');
    poly.setAttribute('stroke-width', '2.5');
    poly.setAttribute('stroke-dasharray', '7 3');
    poly.setAttribute('stroke-linejoin', 'round');
    group.appendChild(poly);

    const last = path[path.length - 1];
    const prev = path[path.length - 2];
    const angle = Math.atan2(last.cy - prev.cy, last.cx - prev.cx);
    const hl = 11;
    const headPts = [
        `${last.cx},${last.cy}`,
        `${last.cx + hl * Math.cos(angle - 2.6)},${last.cy + hl * Math.sin(angle - 2.6)}`,
        `${last.cx + hl * Math.cos(angle + 2.6)},${last.cy + hl * Math.sin(angle + 2.6)}`,
    ].join(' ');
    const head = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    head.setAttribute('points', headPts);
    head.setAttribute('fill', '#f59e0b');
    group.appendChild(head);

    // AP-cost badge at the path's midpoint hex — same visual language as the
    // building-level badges (dark rounded rect, white bold text).
    const mid = pixelPath[Math.floor(pixelPath.length / 2)];
    const label = `${apCost} AP`;
    const badgeW = 11 + label.length * 6;
    const badgeH = 14;
    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    rect.setAttribute('x', mid.cx - badgeW / 2);
    rect.setAttribute('y', mid.cy - badgeH / 2);
    rect.setAttribute('width', badgeW);
    rect.setAttribute('height', badgeH);
    rect.setAttribute('rx', '3');
    rect.setAttribute('fill', '#b45309');
    rect.setAttribute('pointer-events', 'none');
    group.appendChild(rect);
    group.appendChild(svgText(mid.cx, mid.cy, label, 9, '#fff', 700));
}

// Radial "hold to confirm" progress ring at the target tile — animates from
// empty to full over durationMs via a plain CSS transition on stroke-dashoffset.
function drawPressProgress(group, cx, cy, durationMs) {
    group.innerHTML = '';
    group.style.display = '';
    const r = 16;
    const circumference = 2 * Math.PI * r;

    const track = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    track.setAttribute('cx', cx);
    track.setAttribute('cy', cy);
    track.setAttribute('r', r);
    track.setAttribute('fill', 'none');
    track.setAttribute('stroke', 'rgba(245,158,11,0.25)');
    track.setAttribute('stroke-width', '4');
    group.appendChild(track);

    const fill = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    fill.setAttribute('cx', cx);
    fill.setAttribute('cy', cy);
    fill.setAttribute('r', r);
    fill.setAttribute('fill', 'none');
    fill.setAttribute('stroke', '#f59e0b');
    fill.setAttribute('stroke-width', '4');
    fill.setAttribute('stroke-linecap', 'round');
    fill.setAttribute('stroke-dasharray', `${circumference}`);
    fill.setAttribute('stroke-dashoffset', `${circumference}`);
    fill.setAttribute('transform', `rotate(-90 ${cx} ${cy})`);
    fill.style.transition = `stroke-dashoffset ${durationMs}ms linear`;
    group.appendChild(fill);

    requestAnimationFrame(() => fill.setAttribute('stroke-dashoffset', '0'));
}

function hidePressProgress(group) {
    group.style.display = 'none';
    group.innerHTML = '';
}

// ── SVG hex grid renderer ─────────────────────────────────────────────────────

function initHexGrid(container, tiles, opts = {}) {
    if (!container || tiles.length === 0) return;

    const SIZE = 40;
    // On narrow screens, use tighter padding so the colony zone fills the viewport.
    const isMobile = container.clientWidth > 0 && container.clientWidth < 600;
    const PADDING = isMobile ? SIZE * 0.6 : SIZE * 1.5;

    // Build tile-coordinate → building lookup
    const buildingsByTile = new Map();
    if (opts.buildings) {
        for (const b of opts.buildings) {
            if (b.building_id === 25) {
                buildingsByTile.set('0,0', b);
            } else if (b.tile_x !== null && b.tile_y !== null) {
                buildingsByTile.set(`${b.tile_x},${b.tile_y}`, b);
            }
        }
    }

    // Compute bounding boxes: full grid + colony zone only
    let minPx = Infinity,
        maxPx = -Infinity;
    let minPy = Infinity,
        maxPy = -Infinity;
    let czMinPx = Infinity,
        czMaxPx = -Infinity;
    let czMinPy = Infinity,
        czMaxPy = -Infinity;

    const positions = tiles.map((t) => {
        const px = SIZE * Math.sqrt(3) * (t.q + t.r / 2);
        const py = SIZE * 1.5 * t.r;
        minPx = Math.min(minPx, px);
        maxPx = Math.max(maxPx, px);
        minPy = Math.min(minPy, py);
        maxPy = Math.max(maxPy, py);
        if (t.is_colony_zone) {
            czMinPx = Math.min(czMinPx, px);
            czMaxPx = Math.max(czMaxPx, px);
            czMinPy = Math.min(czMinPy, py);
            czMaxPy = Math.max(czMaxPy, py);
        }
        return { tile: t, px, py };
    });

    // On mobile, clip the viewBox to the colony zone + one hex margin so ring 3
    // is partially visible ("hinted") while the colony zone fills most of the screen.
    const hasCZ = czMinPx !== Infinity;
    const margin = SIZE * 1.1;
    const vbMinPx = isMobile && hasCZ ? czMinPx - margin : minPx;
    const vbMaxPx = isMobile && hasCZ ? czMaxPx + margin : maxPx;
    const vbMinPy = isMobile && hasCZ ? czMinPy - margin : minPy;
    const vbMaxPy = isMobile && hasCZ ? czMaxPy + margin : maxPy;

    const svgW = vbMaxPx - vbMinPx + SIZE * Math.sqrt(3) + PADDING * 2;
    const svgH = vbMaxPy - vbMinPy + SIZE * 2 + PADDING * 2;
    const offX = -vbMinPx + PADDING + (SIZE * Math.sqrt(3)) / 2;
    const offY = -vbMinPy + PADDING + SIZE;

    // Restore pan offset (persists across redraws so the user keeps their position)
    let panX = opts.panState?.x ?? 0;
    let panY = opts.panState?.y ?? 0;

    // Clamp bounds: leftmost/rightmost tile edges within SVG coordinate space
    const panXMin = minPx + offX - PADDING;
    const panXMax = Math.max(0, maxPx + offX + SIZE * Math.sqrt(3) + PADDING - svgW);
    const panYMin = minPy + offY - PADDING;
    const panYMax = Math.max(0, maxPy + offY + SIZE * 2 + PADDING - svgH);
    panX = Math.max(panXMin, Math.min(panX, panXMax));
    panY = Math.max(panYMin, Math.min(panY, panYMax));

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', `${panX} ${panY} ${svgW} ${svgH}`);
    svg.setAttribute('width', '100%');
    svg.style.display = 'block';

    svg.appendChild(buildFogDefs());

    // Build pixel position map (needed for arrow + move animation)
    const tilePixelMap = new Map();
    for (const { tile, px, py } of positions) {
        tilePixelMap.set(`${tile.q},${tile.r}`, { cx: px + offX, cy: py + offY });
    }
    if (opts.positionMap) {
        for (const [k, v] of tilePixelMap) opts.positionMap.set(k, v);
    }

    for (const { tile, px, py } of positions) {
        const cx = px + offX;
        const cy = py + offY;
        const building = buildingsByTile.get(`${tile.q},${tile.r}`) ?? null;
        const g = createHexTile(cx, cy, SIZE - 2, tile, building, opts, buildingsByTile);
        svg.appendChild(g);
    }

    // Arrow overlay: drawn on top of all tiles. Unified desktop+mobile gesture
    // (Pointer Events cover mouse/touch/pen alike): click/tap selects and
    // shows the preview arrow + AP cost; pressing and holding ~0.9s on an
    // already-valid target confirms the move, with a radial progress ring at
    // the target tile so the player sees the hold registering. onTileClick()
    // never triggers the move directly anymore — see its harvesterMoveMode
    // branch, which is now a no-op for valid targets.
    if (opts.harvesterMoveMode && opts.harvesterBuilding?.tile_x !== null) {
        const hv = opts.harvesterBuilding;
        const axialToPixel = (q, r) => ({
            cx: SIZE * Math.sqrt(3) * (q + r / 2) + offX,
            cy: SIZE * 1.5 * r + offY,
        });
        const LONG_PRESS_MS = 900;

        const arrowGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        arrowGroup.setAttribute('pointer-events', 'none');
        arrowGroup.style.display = 'none';
        svg.appendChild(arrowGroup);

        const progressGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        progressGroup.setAttribute('pointer-events', 'none');
        progressGroup.style.display = 'none';
        svg.appendChild(progressGroup);

        for (const { tile } of positions) {
            if (!isHarvesterTargetTile(tile, buildingsByTile)) continue;
            const tileGroup = opts.polygonMap?.get(`${tile.q},${tile.r}`)?.parentElement;
            if (!tileGroup) continue;

            const hexPath = hexLinePath(hv.tile_x, hv.tile_y, tile.q, tile.r);
            const pixelPath = hexPath.map(([q, r]) => axialToPixel(q, r));
            const apCost = Math.max(1, hexPath.length - 1);
            const targetPos = pixelPath[pixelPath.length - 1];
            const showArrow = () => drawHarvesterArrow(arrowGroup, pixelPath, apCost);
            const hideArrow = () => {
                arrowGroup.style.display = 'none';
            };

            tileGroup.addEventListener('mouseenter', showArrow);
            tileGroup.addEventListener('mouseleave', hideArrow);

            let pressTimer = null;
            const cancelPress = () => {
                clearTimeout(pressTimer);
                pressTimer = null;
                hidePressProgress(progressGroup);
            };
            tileGroup.addEventListener('pointerdown', () => {
                showArrow();
                cancelPress();
                drawPressProgress(progressGroup, targetPos.cx, targetPos.cy, LONG_PRESS_MS);
                pressTimer = setTimeout(() => {
                    pressTimer = null;
                    hidePressProgress(progressGroup);
                    opts.onHarvesterMoveConfirm && opts.onHarvesterMoveConfirm(tile);
                }, LONG_PRESS_MS);
            });
            tileGroup.addEventListener('pointerup', cancelPress);
            tileGroup.addEventListener('pointerleave', cancelPress);
            tileGroup.addEventListener('pointercancel', cancelPress);
        }
    }

    // Touch-drag pan (mobile only): lets the user slide the grid to reach ring 3+ tiles.
    if (isMobile) {
        let touchStartX,
            touchStartY,
            touchStartPanX,
            touchStartPanY,
            touchMoved = false;

        svg.addEventListener(
            'touchstart',
            (e) => {
                if (e.touches.length !== 1) return;
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                touchStartPanX = panX;
                touchStartPanY = panY;
                touchMoved = false;
            },
            { passive: true },
        );

        svg.addEventListener(
            'touchmove',
            (e) => {
                if (e.touches.length !== 1) return;
                const dx = e.touches[0].clientX - touchStartX;
                const dy = e.touches[0].clientY - touchStartY;
                if (!touchMoved && Math.hypot(dx, dy) < 6) return;
                touchMoved = true;
                const scale = svgW / (svg.clientWidth || svgW);
                panX = Math.max(panXMin, Math.min(touchStartPanX - dx * scale, panXMax));
                panY = Math.max(panYMin, Math.min(touchStartPanY - dy * scale, panYMax));
                svg.setAttribute('viewBox', `${panX} ${panY} ${svgW} ${svgH}`);
                if (opts.panState) {
                    opts.panState.x = panX;
                    opts.panState.y = panY;
                }
                e.preventDefault();
            },
            { passive: false },
        );

        svg.addEventListener(
            'touchend',
            () => {
                if (touchMoved) {
                    // Suppress the synthetic click that fires after a drag gesture.
                    svg.addEventListener('click', (e) => e.stopPropagation(), { once: true, capture: true });
                }
            },
            { passive: true },
        );
    }

    container.innerHTML = '';
    container.appendChild(svg);
}

function createHexTile(cx, cy, size, tile, building, opts, buildingsByTile) {
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('data-q', tile.q);
    g.setAttribute('data-r', tile.r);

    const isCC = tile.q === 0 && tile.r === 0;
    const isImpassable = tile.tile_type === 'terrain_impassable' && tile.is_explored;

    // Build-mode: valid placement tile (colony zone, explored terrain, not occupied)
    const isBuildTarget =
        opts.buildMode && opts.pendingBuilding && isBuildableTile(tile) && !buildingsByTile?.has(`${tile.q},${tile.r}`);

    // Harvester move mode: valid relocation target (explored regolith, not occupied)
    const isHarvesterTarget =
        opts.harvesterMoveMode &&
        tile.is_explored &&
        tile.tile_type.startsWith('regolith_') &&
        !buildingsByTile?.has(`${tile.q},${tile.r}`);

    const points = hexCorners(cx, cy, size);
    const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    polygon.setAttribute('points', points.join(' '));

    let fillColor = getTileColor(tile);
    if (isBuildTarget) fillColor = '#b8e8b8'; // light-green build target
    if (isHarvesterTarget) fillColor = '#b0d8f8'; // light-blue harvester target
    polygon.setAttribute('fill', fillColor);

    const [stroke, strokeW] = getTileStroke(tile, isCC);
    polygon.setAttribute('stroke', stroke);
    polygon.setAttribute('stroke-width', isImpassable ? '0' : strokeW);
    // Dashed stroke signals a buildable-but-not-yet edge:
    //  - next_zone tiles ("soon buildable" via the next CC upgrade), or
    //  - colony-zone fog (state 3: buildable but still undiscovered).
    const isZoneFog = !tile.is_explored && tile.is_colony_zone;
    if (tile.next_zone || isZoneFog) {
        polygon.setAttribute('stroke-dasharray', '4 3');
    }
    polygon._defaultStroke = stroke;
    polygon._defaultStrokeW = isImpassable ? '0' : strokeW;

    if (!isImpassable) {
        g.classList.add('tile--unlocked');
        g.addEventListener('click', () => opts.onSelect && opts.onSelect(tile));
    }

    g.appendChild(polygon);

    if (opts.polygonMap) {
        opts.polygonMap.set(`${tile.q},${tile.r}`, polygon);
    }

    // Fog overlay + glyph (states 3 + 4). Skipped while this tile is an active
    // build/move target, so the highlight stays clean. The concrete tile_type is
    // never revealed under fog — only the fog kind (scout vs. buildable) shows.
    // next_zone tiles get the padlock badge instead of a fog glyph (see below),
    // so the hatch is still drawn but the "+"/"?" glyph is suppressed for them.
    const isFog = !tile.is_explored && !isImpassable;
    if (isFog && !isBuildTarget && !isHarvesterTarget) {
        // Diagonal mist hatch on top of the slate fill.
        const hatch = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        hatch.setAttribute('points', points.join(' '));
        hatch.setAttribute('fill', 'url(#fog-hatch)');
        hatch.setAttribute('stroke', 'none');
        hatch.setAttribute('pointer-events', 'none');
        g.appendChild(hatch);

        if (tile.next_zone) {
            // Handled by the padlock badge below ("next CC upgrade unlocks this").
        } else if (tile.is_colony_zone) {
            // State 3 — buildable but undiscovered: padlock-free build affordance.
            // A faint trowel/plus mark signals "you may build here (reveals on build)".
            g.appendChild(svgText(cx, cy + 0.5, '+', 13, 'rgba(255,255,255,0.82)', 700));
            const ring = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            ring.setAttribute('cx', cx);
            ring.setAttribute('cy', cy);
            ring.setAttribute('r', '7.5');
            ring.setAttribute('fill', 'none');
            ring.setAttribute('stroke', 'rgba(255,255,255,0.55)');
            ring.setAttribute('stroke-width', '1.2');
            ring.setAttribute('stroke-dasharray', '2 2');
            ring.setAttribute('pointer-events', 'none');
            g.appendChild(ring);
        } else {
            // State 4 — exploration target: inviting "?" glyph, the Nav-AP scout goal.
            g.appendChild(svgText(cx, cy + 0.5, '?', 13, 'rgba(255,255,255,0.85)', 700));
        }
    }

    // Onboarding pulse ring — drawn behind the fill polygon.
    // Keyed off the hint KEY (not rank) so re-ordering hints never desyncs the pulse.
    const hintKey = opts.activeHint?.key ?? '';

    // hint_repair: any building below max status points (guide repair).
    const isPulseRepair = hintKey === 'hint_repair' && building && building.status_points < building.max_status_points;

    // hint_repair_urgent: building near level-down (<=15% status, mirrors the
    // server-side hint_repair_urgent_sp=3 of 20 threshold) — pulse the critical one.
    const isPulseRepairUrgent =
        hintKey === 'hint_repair_urgent' && building && building.status_points <= building.max_status_points * 0.15;

    // hint_2: Harvester tile in colony zone (guide relocation).
    const isPulseHarvester = hintKey === 'hint_2' && building?.building_key === 'building_harvester';

    // hint_3 / hint_cc_invest: CC tile (guide upgrade / pre-invest).
    const isPulseCc = (hintKey === 'hint_3' || hintKey === 'hint_cc_invest') && tile.q === 0 && tile.r === 0;

    // Harvester current position highlight while in move mode.
    const isHarvesterCurrent = opts.harvesterMoveMode && building?.building_key === 'building_harvester';

    const shouldPulse = isPulseRepair || isPulseRepairUrgent || isPulseHarvester || isPulseCc || isHarvesterCurrent;

    if (shouldPulse) {
        const pulseHex = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        pulseHex.setAttribute('points', hexCorners(cx, cy, size + 4).join(' '));
        pulseHex.setAttribute('fill', 'none');
        pulseHex.setAttribute('stroke', '#93c5fd'); // blue-300
        pulseHex.setAttribute('stroke-width', '2.5');
        pulseHex.setAttribute('pointer-events', 'none');
        pulseHex.setAttribute('class', 'onboarding-pulse');
        g.insertBefore(pulseHex, polygon); // behind the fill polygon
    }

    if (isImpassable) {
        return g;
    }

    // CC label
    if (isCC) {
        g.appendChild(svgText(cx, cy, 'CC', 10, '#2e7d32', 700));
    }

    // "Soon buildable": the tiles the NEXT Command-Center upgrade adds to the
    // colony zone — flagged server-side via tile.next_zone. The padlock reads as
    // "locked for now, unlocks with the next CC upgrade". Shown on both explored
    // and fogged next_zone tiles (overrides the fog glyph). Other explored terrain
    // outside the zone gets no badge — the CC never reaches most of it.
    if (tile.next_zone) {
        // Padlock body + shackle, drawn centred on the tile.
        const lockW = 11,
            lockH = 8;
        const lx = cx - lockW / 2,
            ly = cy - lockH / 2 + 1;
        const body = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        body.setAttribute('x', lx);
        body.setAttribute('y', ly);
        body.setAttribute('width', lockW);
        body.setAttribute('height', lockH);
        body.setAttribute('rx', '1.5');
        body.setAttribute('fill', 'rgba(60,66,82,0.78)');
        body.setAttribute('pointer-events', 'none');
        const shackle = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        const sr = 3.2;
        shackle.setAttribute('d', `M ${cx - sr} ${ly} v -2 a ${sr} ${sr} 0 0 1 ${2 * sr} 0 v 2`);
        shackle.setAttribute('fill', 'none');
        shackle.setAttribute('stroke', 'rgba(60,66,82,0.78)');
        shackle.setAttribute('stroke-width', '1.6');
        shackle.setAttribute('pointer-events', 'none');
        g.appendChild(shackle);
        g.appendChild(body);
    }

    // Resource indicator dot (top-right, blue)
    if (tile.is_explored && tile.resource_max > 0) {
        const dot = svgCircle(cx + size * 0.38, cy - size * 0.38, 4, '#2196f3', '#fff');
        g.appendChild(dot);
    }

    // Building badge (center-bottom, skip CC — already labeled)
    if (building && !isCC && tile.is_explored) {
        const abbr = BUILDING_ABBR[building.building_key] ?? '?';
        const isBuilt = building.level > 0;
        const inTransit = !!building.in_transit;
        const levelSuffix = isBuilt ? ` ${building.level}` : '';
        const badgeText = inTransit ? `${abbr} →` : abbr + levelSuffix;
        const badgeW = isBuilt ? 28 : 22;
        const badgeH = 12;
        const bx = cx - badgeW / 2;
        const by = cy + size * 0.28;

        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('x', bx);
        rect.setAttribute('y', by);
        rect.setAttribute('width', badgeW);
        rect.setAttribute('height', badgeH);
        rect.setAttribute('rx', '3');
        rect.setAttribute(
            'fill',
            inTransit ? 'rgba(180,83,9,0.85)' : isBuilt ? 'rgba(30,30,30,0.72)' : 'rgba(80,80,80,0.55)',
        );
        rect.setAttribute('pointer-events', 'none');
        g.appendChild(rect);

        g.appendChild(svgText(cx, by + badgeH / 2 + 0.5, badgeText, 8, '#fff', 700));

        // Red warning dot (top-right of badge) when condition < 10%
        if (isBuilt) {
            const maxSp = building.max_status_points ?? 20;
            const condPct = building.status_points / maxSp;
            if (condPct < 0.1) {
                g.appendChild(svgCircle(bx + badgeW - 2, by + 2, 3, '#ef4444', '#fff'));
            }
        }
    }

    // Event indicator dot (top-left, orange — only when deep-scanned)
    if (tile.is_deep_scanned && tile.event_type) {
        g.appendChild(svgCircle(cx - size * 0.38, cy - size * 0.38, 4, '#e67e22', '#fff'));
    }

    // Signal indicator (center-top, pulsing yellow — explored tile with hidden event)
    if (tile.has_signal) {
        const pulse = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        pulse.setAttribute('cx', cx);
        pulse.setAttribute('cy', cy - size * 0.15);
        pulse.setAttribute('r', '5');
        pulse.setAttribute('fill', '#f0d060');
        pulse.setAttribute('stroke', '#c0a820');
        pulse.setAttribute('stroke-width', '1');
        pulse.setAttribute('class', 'signal-pulse');
        pulse.setAttribute('pointer-events', 'none');
        g.appendChild(pulse);
    }

    return g;
}

function hexCorners(cx, cy, size) {
    const pts = [];
    for (let i = 0; i < 6; i++) {
        const angle = (Math.PI / 180) * (60 * i - 30); // pointy-top
        pts.push(`${(cx + size * Math.cos(angle)).toFixed(2)},${(cy + size * Math.sin(angle)).toFixed(2)}`);
    }
    return pts;
}

// Shared <defs> with the fog hatch pattern. Both fog states (3 + 4) reuse it as
// a subtle diagonal mist texture so fogged tiles read as "hidden" at a glance.
function buildFogDefs() {
    const ns = 'http://www.w3.org/2000/svg';
    const defs = document.createElementNS(ns, 'defs');

    const pattern = document.createElementNS(ns, 'pattern');
    pattern.setAttribute('id', 'fog-hatch');
    pattern.setAttribute('width', '7');
    pattern.setAttribute('height', '7');
    pattern.setAttribute('patternUnits', 'userSpaceOnUse');
    pattern.setAttribute('patternTransform', 'rotate(45)');

    const line = document.createElementNS(ns, 'line');
    line.setAttribute('x1', '0');
    line.setAttribute('y1', '0');
    line.setAttribute('x2', '0');
    line.setAttribute('y2', '7');
    line.setAttribute('stroke', '#ffffff');
    line.setAttribute('stroke-width', '2');
    line.setAttribute('stroke-opacity', '0.28');
    pattern.appendChild(line);

    defs.appendChild(pattern);
    return defs;
}

function getTileColor(tile) {
    if (!tile.is_explored && !tile.is_colony_zone) return LOCKED_COLOR; // state 4: exploration-zone fog
    if (!tile.is_explored) return FOG_COLOR; // state 3: colony-zone fog (buildable but undiscovered)
    if (tile.q === 0 && tile.r === 0) return CC_COLOR;
    if (tile.is_deep_scanned && tile.event_type) return EVENT_COLOR;

    // Colony-zone terrain_empty is clearly lighter than exploration zone.
    if (tile.is_colony_zone && tile.tile_type.startsWith('terrain_') && tile.tile_type !== 'terrain_impassable') {
        return BUILDABLE_COLOR;
    }

    return TILE_COLORS[tile.tile_type] ?? '#c8cdd6';
}

function getTileStroke(tile, isCC) {
    if (isCC) return [CC_STROKE, '2.5'];
    // State 4 (exploration fog): solid scout-target border, slightly heavier.
    if (!tile.is_explored && !tile.is_colony_zone) return [LOCKED_STROKE, '1.5'];
    // State 3 (colony-zone fog): dashed border echoes the "buildable" affordance.
    if (!tile.is_explored) return [FOG_STROKE, '1.5'];
    if (tile.is_deep_scanned && tile.event_type) return [EVENT_STROKE, '1.5'];
    if (tile.tile_type === 'terrain_impassable') return ['#555', '0'];

    // Colony-zone terrain: lighter stroke matching the buildable fill.
    if (tile.is_colony_zone && tile.tile_type.startsWith('terrain_')) {
        return [BUILDABLE_STROKE, '1.5'];
    }

    // Exploration-zone terrain: dashed stroke signals "not yet claimed"
    if (!tile.is_colony_zone && tile.tile_type.startsWith('terrain_')) {
        return [TILE_STROKES[tile.tile_type] ?? '#a0a8b4', '1.5'];
    }

    return [TILE_STROKES[tile.tile_type] ?? '#8a9aaa', '1.5'];
}

function svgText(x, y, text, size, fill, weight) {
    const el = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    el.setAttribute('x', x);
    el.setAttribute('y', y + size / 3);
    el.setAttribute('text-anchor', 'middle');
    el.setAttribute('font-size', size);
    el.setAttribute('font-weight', weight ?? 'normal');
    el.setAttribute('fill', fill ?? '#333');
    el.setAttribute('pointer-events', 'none');
    el.textContent = text;
    return el;
}

function svgCircle(cx, cy, r, fill, stroke) {
    const el = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    el.setAttribute('cx', cx);
    el.setAttribute('cy', cy);
    el.setAttribute('r', r);
    el.setAttribute('fill', fill);
    el.setAttribute('stroke', stroke);
    el.setAttribute('stroke-width', '1');
    el.setAttribute('pointer-events', 'none');
    return el;
}
