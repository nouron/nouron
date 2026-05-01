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
    terrain_empty:      'Leeres Terrain',
    terrain_hazard:     'Gefahrenzone',
    terrain_impassable: 'Unpassierbar',
    regolith_rich:      'Regolith (reich)',
    regolith_normal:    'Regolith (normal)',
    regolith_poor:      'Regolith (karg)',
    event_wreck:        'Wrack',
    event_ruin:         'Ruine',
    event_bunker:       'Bunker',
    event_probe:        'Sonde',
    event_crystal:      'Kristallfeld',
    event_vent:         'Thermalspalte',
    event_cave:         'Höhle',
    event_cache:        'Cache',
    event_signal:       'Signal',
    event_anomaly:      'Anomalie',
};

const TILE_COLORS = {
    terrain_empty:      '#c8cdd6',
    terrain_hazard:     '#e8b87a',
    terrain_impassable: '#7a7f8a',
    regolith_rich:      '#5a9fd4',
    regolith_normal:    '#7fb5dc',
    regolith_poor:      '#a8cfe6',
};

const TILE_STROKES = {
    terrain_empty:      '#a0a8b4',
    terrain_hazard:     '#c08040',
    terrain_impassable: '#555',
    regolith_rich:      '#3a7ab0',
    regolith_normal:    '#5090c0',
    regolith_poor:      '#7aaace',
};

const CC_COLOR      = '#7ec87e';
const CC_STROKE     = '#2e7d32';
const FOG_COLOR     = '#dde0e5';
const FOG_STROKE    = '#b8bec6';
const LOCKED_COLOR  = '#c8ccd4';
const LOCKED_STROKE = '#a8adb8';
const EVENT_COLOR   = '#e8d89a';
const EVENT_STROKE  = '#b09a40';

// Abbreviations shown on tiles with buildings
const BUILDING_ABBR = {
    building_commandCenter:  'CC',
    building_harvester:      'HV',
    building_housingComplex: 'WH',
    building_depot:          'LH',
    building_sciencelab:     'AL',
    building_temple:         'RS',
    building_bioFacility:    'AD',
    building_hangar:         'HG',
    building_hospital:       'KS',
    building_denkmal:        'KD',
    building_bar:            'CA',
};

// ── Alpine component ──────────────────────────────────────────────────────────

function colonyHexView(config) {
    return {
        tiles:             config.tiles,
        colony:            config.colony,
        ccLevel:           config.ccLevel,
        buildings:         config.buildings ?? [],
        routes:            config.routes ?? {},
        i18n:              config.i18n ?? {},
        apNav:             config.apNav ?? 0,
        apConstruction:    config.apConstruction ?? 0,
        selectedTile:      null,
        buildMode:         false,
        pendingBuilding:   null,
        availableBuildings: [],
        _svgPolygons:      new Map(),

        init() {
            this.$nextTick(() => this.redrawGrid());
        },

        // ── Grid rendering ────────────────────────────────────────────────────

        redrawGrid() {
            initHexGrid(this.$refs.hexgrid, this.tiles, {
                onSelect:        (tile) => this.onTileClick(tile),
                polygonMap:      this._svgPolygons,
                buildings:       this.buildings,
                buildMode:       this.buildMode,
                pendingBuilding: this.pendingBuilding,
            });
        },

        // ── Tile selection ────────────────────────────────────────────────────

        onTileClick(tile) {
            if (this.buildMode && this.pendingBuilding) {
                if (isBuildableTile(tile) && !this.buildingForTile(tile))
                    this.doPlaceBuilding(tile);
                return;
            }
            this.selectTile(tile);
        },

        selectTile(tile) {
            if (this.selectedTile) {
                const prev = this._svgPolygons.get(`${this.selectedTile.q},${this.selectedTile.r}`);
                if (prev) {
                    prev.setAttribute('stroke',       prev._defaultStroke  ?? '#555');
                    prev.setAttribute('stroke-width', prev._defaultStrokeW ?? '1.5');
                }
            }
            this.selectedTile = tile;
            const cur = this._svgPolygons.get(`${tile.q},${tile.r}`);
            if (cur) {
                cur.setAttribute('stroke',       '#c0392b');
                cur.setAttribute('stroke-width', '3');
            }
        },

        // ── Build mode ────────────────────────────────────────────────────────

        async toggleBuildMode() {
            if (this.buildMode) {
                this.buildMode       = false;
                this.pendingBuilding = null;
                this.$nextTick(() => this.redrawGrid());
                return;
            }
            const data             = await this.get(this.routes.buildingsAvailable);
            this.availableBuildings = data.buildings ?? [];
            this.buildMode          = true;
            this.selectedTile       = null;
            this.$nextTick(() => this.redrawGrid());
        },

        selectPendingBuilding(building) {
            this.pendingBuilding = (this.pendingBuilding?.building_id === building.building_id)
                ? null
                : building;
            this.$nextTick(() => this.redrawGrid());
        },

        // ── Tile actions ──────────────────────────────────────────────────────

        async doExploreTile(tile) {
            const res = await this.post(this.routes.explore, { q: tile.q, r: tile.r });
            if (res.ok) {
                this.updateTile(res.tile);
                this.selectedTile = res.tile;
                this.updateAp(res);
                this.$nextTick(() => this.redrawGrid());
            } else {
                alert(res.error);
            }
        },

        async doDeepScan(tile) {
            const res = await this.post(this.routes.deepScan, { q: tile.q, r: tile.r });
            if (res.ok) {
                this.updateTile(res.tile);
                this.selectedTile = res.tile;
                this.updateAp(res);
                this.$nextTick(() => this.redrawGrid());
            } else {
                alert(res.error);
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
                this.buildMode       = false;
                this.pendingBuilding = null;
                this.selectedTile    = tile;
                this.updateAp(res);
                this.$nextTick(() => this.redrawGrid());
            } else {
                alert(res.error);
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
                if (res.tiles) {
                    this.tiles = res.tiles;
                    if (this.selectedTile) {
                        const updated = res.tiles.find(t => t.q === this.selectedTile.q && t.r === this.selectedTile.r);
                        if (updated) this.selectedTile = updated;
                    }
                    this.$nextTick(() => this.redrawGrid());
                } else if (this.selectedTile) {
                    this.selectedTile = { ...this.selectedTile };
                }
            } else {
                alert(res.error);
            }
        },

        // ── State helpers ─────────────────────────────────────────────────────

        updateAp(res) {
            if (res.apNav          !== undefined) this.apNav          = res.apNav;
            if (res.apConstruction !== undefined) this.apConstruction = res.apConstruction;
        },

        updateTile(tile) {
            const idx = this.tiles.findIndex(t => t.q === tile.q && t.r === tile.r);
            if (idx !== -1) this.tiles[idx] = tile;
        },

        updateBuilding(building) {
            const idx = this.buildings.findIndex(
                b => b.building_id === building.building_id && b.instance_id === building.instance_id
            );
            if (idx !== -1) this.buildings[idx] = building;
            else this.buildings.push(building);
        },

        tileHeading(tile) {
            if (tile.q === 0 && tile.r === 0) return 'Kommandozentrale';
            if (!tile.is_explored)             return 'Unbekanntes Terrain';
            return TILE_LABELS[tile.tile_type] ?? tile.tile_type;
        },

        tileTypeName(type) {
            return TILE_LABELS[type] ?? type;
        },

        eventTypeName(type) {
            return TILE_LABELS[type] ?? type;
        },

        buildingForTile(tile) {
            if (!tile) return null;
            if (tile.q === 0 && tile.r === 0) {
                return this.buildings.find(b => b.building_id === 25) ?? null;
            }
            return this.buildings.find(b => b.tile_x === tile.q && b.tile_y === tile.r) ?? null;
        },

        statusLine() {
            const total    = this.tiles.length;
            const explored = this.tiles.filter(t => t.is_explored).length;
            return `${explored} / ${total} Tiles erkundet · CC Level ${this.ccLevel}`;
        },

        // ── HTTP helpers ──────────────────────────────────────────────────────

        get(url) {
            return fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).then(r => r.json());
        },

        post(url, data) {
            return fetch(url, {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            }).then(r => r.json());
        },
    };
}

// ── Tile helpers ──────────────────────────────────────────────────────────────

function isBuildableTile(tile) {
    return tile.is_colony_zone
        && tile.is_explored
        && tile.tile_type.startsWith('terrain_')
        && tile.tile_type !== 'terrain_impassable';
}

// ── SVG hex grid renderer ─────────────────────────────────────────────────────

function initHexGrid(container, tiles, opts = {}) {
    if (!container || tiles.length === 0) return;

    const SIZE    = 40;
    const PADDING = SIZE * 1.5;

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

    // Compute bounding box from actual tile positions
    let minPx = Infinity, maxPx = -Infinity;
    let minPy = Infinity, maxPy = -Infinity;

    const positions = tiles.map(t => {
        const px = SIZE * Math.sqrt(3) * (t.q + t.r / 2);
        const py = SIZE * 1.5 * t.r;
        minPx = Math.min(minPx, px);
        maxPx = Math.max(maxPx, px);
        minPy = Math.min(minPy, py);
        maxPy = Math.max(maxPy, py);
        return { tile: t, px, py };
    });

    const svgW = maxPx - minPx + SIZE * Math.sqrt(3) + PADDING * 2;
    const svgH = maxPy - minPy + SIZE * 2 + PADDING * 2;
    const offX = -minPx + PADDING + SIZE * Math.sqrt(3) / 2;
    const offY = -minPy + PADDING + SIZE;

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', `0 0 ${svgW} ${svgH}`);
    svg.setAttribute('width', '100%');
    svg.style.display = 'block';

    for (const { tile, px, py } of positions) {
        const cx       = px + offX;
        const cy       = py + offY;
        const building = buildingsByTile.get(`${tile.q},${tile.r}`) ?? null;
        const g        = createHexTile(cx, cy, SIZE - 2, tile, building, opts, buildingsByTile);
        svg.appendChild(g);
    }

    container.innerHTML = '';
    container.appendChild(svg);
}

function createHexTile(cx, cy, size, tile, building, opts, buildingsByTile) {
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');

    const isCC         = tile.q === 0 && tile.r === 0;
    const isImpassable = tile.tile_type === 'terrain_impassable' && tile.is_explored;

    // Build-mode: valid placement tile (colony zone, explored terrain, not occupied)
    const isBuildTarget = opts.buildMode && opts.pendingBuilding
        && isBuildableTile(tile)
        && !buildingsByTile?.has(`${tile.q},${tile.r}`);

    const points  = hexCorners(cx, cy, size);
    const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    polygon.setAttribute('points', points.join(' '));

    let fillColor = getTileColor(tile);
    if (isBuildTarget) fillColor = '#b8e8b8';  // light-green build-target highlight
    polygon.setAttribute('fill', fillColor);

    const [stroke, strokeW] = getTileStroke(tile, isCC);
    polygon.setAttribute('stroke',       stroke);
    polygon.setAttribute('stroke-width', isImpassable ? '0' : strokeW);
    polygon._defaultStroke  = stroke;
    polygon._defaultStrokeW = isImpassable ? '0' : strokeW;

    if (!isImpassable) {
        g.classList.add('tile--unlocked');
        g.addEventListener('click', () => opts.onSelect && opts.onSelect(tile));
    }

    g.appendChild(polygon);

    if (opts.polygonMap) {
        opts.polygonMap.set(`${tile.q},${tile.r}`, polygon);
    }

    if (isImpassable) {
        return g;
    }

    // CC label
    if (isCC) {
        g.appendChild(svgText(cx, cy, 'CC', 10, '#2e7d32', 700));
    }

    // Resource indicator dot (top-right, blue)
    if (tile.is_explored && tile.resource_max > 0) {
        const dot = svgCircle(cx + size * 0.38, cy - size * 0.38, 4, '#2196f3', '#fff');
        g.appendChild(dot);
    }

    // Building badge (center-bottom, skip CC — already labeled)
    if (building && !isCC && tile.is_explored) {
        const abbr   = BUILDING_ABBR[building.building_key] ?? '?';
        const badgeW = 22;
        const badgeH = 12;
        const bx     = cx - badgeW / 2;
        const by     = cy + size * 0.28;

        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('x',      bx);
        rect.setAttribute('y',      by);
        rect.setAttribute('width',  badgeW);
        rect.setAttribute('height', badgeH);
        rect.setAttribute('rx',     '3');
        // Under-construction (level 0) gets a lighter badge
        rect.setAttribute('fill', building.level === 0 ? 'rgba(80,80,80,0.55)' : 'rgba(30,30,30,0.72)');
        rect.setAttribute('pointer-events', 'none');
        g.appendChild(rect);

        g.appendChild(svgText(cx, by + badgeH / 2 + 0.5, abbr, 8, '#fff', 700));
    }

    // Event indicator dot (top-left, orange — only when deep-scanned)
    if (tile.is_deep_scanned && tile.event_type) {
        g.appendChild(svgCircle(cx - size * 0.38, cy - size * 0.38, 4, '#e67e22', '#fff'));
    }

    // Signal indicator (center-top, pulsing yellow — explored tile with hidden event)
    if (tile.has_signal) {
        const pulse = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        pulse.setAttribute('cx',           cx);
        pulse.setAttribute('cy',           cy - size * 0.15);
        pulse.setAttribute('r',            '5');
        pulse.setAttribute('fill',         '#f0d060');
        pulse.setAttribute('stroke',       '#c0a820');
        pulse.setAttribute('stroke-width', '1');
        pulse.setAttribute('class',        'signal-pulse');
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
    if (!tile.is_explored && !tile.is_colony_zone) return LOCKED_COLOR;   // unexplored exploration zone
    if (!tile.is_explored)                         return FOG_COLOR;        // unexplored colony zone (shouldn't normally appear)
    if (tile.q === 0 && tile.r === 0)              return CC_COLOR;
    if (tile.is_deep_scanned && tile.event_type)   return EVENT_COLOR;
    return TILE_COLORS[tile.tile_type] ?? '#c8cdd6';
}

function getTileStroke(tile, isCC) {
    if (isCC)                                         return [CC_STROKE,     '2.5'];
    if (!tile.is_explored && !tile.is_colony_zone)    return [LOCKED_STROKE, '1'];
    if (!tile.is_explored)                            return [FOG_STROKE,    '1'];
    if (tile.is_deep_scanned && tile.event_type)      return [EVENT_STROKE,  '1.5'];
    if (tile.tile_type === 'terrain_impassable')      return ['#555',        '0'];
    return [TILE_STROKES[tile.tile_type] ?? '#8a9aaa', '1.5'];
}

function svgText(x, y, text, size, fill, weight) {
    const el = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    el.setAttribute('x',            x);
    el.setAttribute('y',            y + size / 3);
    el.setAttribute('text-anchor',  'middle');
    el.setAttribute('font-size',    size);
    el.setAttribute('font-weight',  weight ?? 'normal');
    el.setAttribute('fill',         fill ?? '#333');
    el.setAttribute('pointer-events', 'none');
    el.textContent = text;
    return el;
}

function svgCircle(cx, cy, r, fill, stroke) {
    const el = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    el.setAttribute('cx',           cx);
    el.setAttribute('cy',           cy);
    el.setAttribute('r',            r);
    el.setAttribute('fill',         fill);
    el.setAttribute('stroke',       stroke);
    el.setAttribute('stroke-width', '1');
    el.setAttribute('pointer-events', 'none');
    return el;
}
