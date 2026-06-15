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
const FOG_COLOR = '#eceef1'; // unexplored colony zone — very light
const FOG_STROKE = '#d4d8de';
const LOCKED_COLOR = '#f2f3f5'; // unexplored exploration zone — almost white
const LOCKED_STROKE = '#dddfe3';
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
        trust: config.trust ?? 0,
        currentSol: config.currentSol ?? 0,
        solLimit: config.solLimit ?? 100,
        activeHint: config.activeHint ?? null,
        merchantVisit: config.merchantVisit ?? null,
        merchantItems: config.merchantItems ?? [],
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
        },

        // ── Grid rendering ────────────────────────────────────────────────────

        redrawGrid() {
            this._tilePositions.clear();
            initHexGrid(this._gridEl ?? this.$refs.hexgrid, this.tiles, {
                onSelect: (tile) => this.onTileClick(tile),
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
            // Harvester move mode: clicking a valid target triggers the move.
            // Clicking anything else gives feedback instead of failing silently.
            if (this.harvesterMoveMode) {
                if (this.isHarvesterTarget(tile)) {
                    this.doMoveHarvester(tile);
                } else {
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
                const msg = res.error === 'ap_limit' ? res.message : res.error;
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
                const msg = res.error === 'ap_limit' ? res.message : res.error;
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
                const msg = res.error === 'ap_limit' ? res.message : res.error;
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
                if (res.leveled_up) {
                    this.showLevelupNotice(res.building.label);
                    if (this.selectedTile) this.selectedTile = { ...this.selectedTile };
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
                const msg = res.error === 'ap_limit' ? res.message : res.error;
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
                const msg = res.error === 'ap_limit' ? res.message : res.error;
                this.showToast(msg, 'error');
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
                const msg = res.error === 'ap_limit' ? res.message : res.error;
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
        },

        // The AP chips live in the resource bar (layout header), outside this
        // Alpine component — sync them via DOM after every AJAX action.
        syncResbarAp(chipId, value) {
            const el = document.querySelector(`#${chipId} .res-amount`);
            if (el) el.textContent = value;
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
            if ('activeHint' in res) this.activeHint = res.activeHint;
        },

        // ── Merchant ──────────────────────────────────────────────────────────

        hasMerchant() {
            return this.merchantVisit !== null;
        },

        async dismissHint() {
            if (!this.activeHint) return;
            const res = await this.post(this.routes.dismissHint, { hint_key: this.activeHint.key });
            if (res.ok) {
                this.activeHint = res.hint ?? null;
                this.$nextTick(() => this.redrawGrid());
            }
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
    return (
        tile.is_colony_zone &&
        tile.is_explored &&
        tile.tile_type.startsWith('terrain_') &&
        tile.tile_type !== 'terrain_impassable'
    );
}

function isHarvesterTargetTile(tile, buildingsByTile) {
    return tile.is_explored && tile.tile_type.startsWith('regolith_') && !buildingsByTile?.has(`${tile.q},${tile.r}`);
}

function drawHarvesterArrow(group, x1, y1, x2, y2) {
    group.innerHTML = '';
    group.style.display = '';
    const dx = x2 - x1,
        dy = y2 - y1;
    const len = Math.sqrt(dx * dx + dy * dy) || 1;
    const nx = dx / len,
        ny = dy / len;
    // Shorten: start 12px from HV center, end 16px from target center
    const sx = x1 + nx * 12,
        sy = y1 + ny * 12;
    const ex = x2 - nx * 16,
        ey = y2 - ny * 16;

    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    line.setAttribute('x1', sx);
    line.setAttribute('y1', sy);
    line.setAttribute('x2', ex);
    line.setAttribute('y2', ey);
    line.setAttribute('stroke', '#f59e0b');
    line.setAttribute('stroke-width', '2.5');
    line.setAttribute('stroke-dasharray', '7 3');
    group.appendChild(line);

    const angle = Math.atan2(ey - sy, ex - sx);
    const hl = 11;
    const pts = [
        `${ex},${ey}`,
        `${ex + hl * Math.cos(angle - 2.6)},${ey + hl * Math.sin(angle - 2.6)}`,
        `${ex + hl * Math.cos(angle + 2.6)},${ey + hl * Math.sin(angle + 2.6)}`,
    ].join(' ');
    const head = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    head.setAttribute('points', pts);
    head.setAttribute('fill', '#f59e0b');
    group.appendChild(head);
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

    // Arrow overlay: drawn on top of all tiles, visible on target hover
    if (opts.harvesterMoveMode && opts.harvesterBuilding?.tile_x !== null) {
        const hvKey = `${opts.harvesterBuilding.tile_x},${opts.harvesterBuilding.tile_y}`;
        const hvPos = tilePixelMap.get(hvKey);
        if (hvPos) {
            const arrowGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            arrowGroup.setAttribute('pointer-events', 'none');
            arrowGroup.style.display = 'none';
            svg.appendChild(arrowGroup);

            for (const { tile } of positions) {
                if (!isHarvesterTargetTile(tile, buildingsByTile)) continue;
                const targetPos = tilePixelMap.get(`${tile.q},${tile.r}`);
                if (!targetPos) continue;
                const tileGroup = opts.polygonMap?.get(`${tile.q},${tile.r}`)?.parentElement;
                if (!tileGroup) continue;
                tileGroup.addEventListener('mouseenter', () =>
                    drawHarvesterArrow(arrowGroup, hvPos.cx, hvPos.cy, targetPos.cx, targetPos.cy),
                );
                tileGroup.addEventListener('mouseleave', () => {
                    arrowGroup.style.display = 'none';
                });
            }
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
    // Dashed stroke signals "explored but not yet colony zone"
    if (tile.is_explored && !tile.is_colony_zone && tile.tile_type.startsWith('terrain_')) {
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

    // hint_3: CC tile (guide upgrade).
    const isPulseCc = hintKey === 'hint_3' && tile.q === 0 && tile.r === 0;

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

    // "Not yet claimed" badge on explored terrain outside colony zone
    if (
        tile.is_explored &&
        !tile.is_colony_zone &&
        tile.tile_type.startsWith('terrain_') &&
        tile.tile_type !== 'terrain_impassable'
    ) {
        const badgeW = 26,
            badgeH = 11;
        const bx = cx - badgeW / 2,
            by = cy - badgeH / 2;
        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('x', bx);
        rect.setAttribute('y', by);
        rect.setAttribute('width', badgeW);
        rect.setAttribute('height', badgeH);
        rect.setAttribute('rx', '3');
        rect.setAttribute('fill', 'rgba(100,100,120,0.45)');
        rect.setAttribute('pointer-events', 'none');
        g.appendChild(rect);
        g.appendChild(svgText(cx, by + badgeH / 2 + 0.5, 'CC ↑', 7, '#eee', 600));
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

function getTileColor(tile) {
    if (!tile.is_explored && !tile.is_colony_zone) return LOCKED_COLOR; // unexplored exploration zone
    if (!tile.is_explored) return FOG_COLOR; // unexplored colony zone (shouldn't normally appear)
    if (tile.q === 0 && tile.r === 0) return CC_COLOR;
    if (tile.is_deep_scanned && tile.event_type) return EVENT_COLOR;

    // Exploration-zone terrain shows same fill as colony zone — distinction is dashed stroke + level badge

    return TILE_COLORS[tile.tile_type] ?? '#c8cdd6';
}

function getTileStroke(tile, isCC) {
    if (isCC) return [CC_STROKE, '2.5'];
    if (!tile.is_explored && !tile.is_colony_zone) return [LOCKED_STROKE, '1'];
    if (!tile.is_explored) return [FOG_STROKE, '1'];
    if (tile.is_deep_scanned && tile.event_type) return [EVENT_STROKE, '1.5'];
    if (tile.tile_type === 'terrain_impassable') return ['#555', '0'];

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
