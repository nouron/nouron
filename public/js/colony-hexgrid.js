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
    terrain_impassable: '#8a8f9a',
    regolith_rich:      '#6fa3d4',
    regolith_normal:    '#90b8dc',
    regolith_poor:      '#b5cfe6',
};

const CC_COLOR      = '#a8d5a8';
const FOG_COLOR     = '#e8eaed';
const LOCKED_COLOR  = '#d0d4db';
const EVENT_COLOR   = '#e8d89a';

// ── Alpine component ──────────────────────────────────────────────────────────

function colonyHexView(config) {
    return {
        tiles:        config.tiles,
        colony:       config.colony,
        ccLevel:      config.ccLevel,
        selectedTile: null,
        _svgPolygons: new Map(),

        init() {
            this.$nextTick(() => {
                initHexGrid(this.$refs.hexgrid, this.tiles, {
                    onSelect:    (tile) => this.selectTile(tile),
                    polygonMap:  this._svgPolygons,
                });
            });
        },

        selectTile(tile) {
            // Remove highlight from previously selected tile
            if (this.selectedTile) {
                const prev = this._svgPolygons.get(`${this.selectedTile.q},${this.selectedTile.r}`);
                if (prev) {
                    prev.setAttribute('stroke', '#555');
                    prev.setAttribute('stroke-width', '1.5');
                }
            }
            this.selectedTile = tile;
            // Highlight newly selected tile
            const cur = this._svgPolygons.get(`${tile.q},${tile.r}`);
            if (cur) {
                cur.setAttribute('stroke', '#c0392b');
                cur.setAttribute('stroke-width', '3');
            }
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

        statusLine() {
            const total    = this.tiles.length;
            const explored = this.tiles.filter(t => t.is_explored).length;
            return `${explored} / ${total} Tiles erkundet · CC Level ${this.ccLevel}`;
        },
    };
}

// ── SVG hex grid renderer ─────────────────────────────────────────────────────

function initHexGrid(container, tiles, opts = {}) {
    if (!container || tiles.length === 0) return;

    const SIZE    = 36;
    const PADDING = SIZE * 2;

    const maxRing = Math.max(...tiles.map(t => t.ring));

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
    svg.style.maxWidth = `${svgW}px`;

    for (const { tile, px, py } of positions) {
        const cx = px + offX;
        const cy = py + offY;
        const g = createHexTile(cx, cy, SIZE - 2, tile, opts);
        svg.appendChild(g);
    }

    container.innerHTML = '';
    container.appendChild(svg);
}

function createHexTile(cx, cy, size, tile, opts) {
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');

    const points = hexCorners(cx, cy, size);
    const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    polygon.setAttribute('points', points.join(' '));
    polygon.setAttribute('fill',         getTileColor(tile));
    polygon.setAttribute('stroke',       '#555');
    polygon.setAttribute('stroke-width', '1.5');

    const isCC = tile.q === 0 && tile.r === 0;
    if (isCC) {
        polygon.setAttribute('stroke', '#2e7d32');
        polygon.setAttribute('stroke-width', '2.5');
    }

    if (tile.is_ring_unlocked) {
        g.style.cursor = 'pointer';
        g.addEventListener('click', () => opts.onSelect && opts.onSelect(tile));
    }

    g.appendChild(polygon);

    if (opts.polygonMap) {
        opts.polygonMap.set(`${tile.q},${tile.r}`, polygon);
    }

    // Label for CC tile
    if (isCC) {
        g.appendChild(svgText(cx, cy, 'CC', 10, '#2e7d32', 700));
    }

    // Ring indicator for locked outer tiles
    if (!tile.is_ring_unlocked && tile.ring === Math.max(...(opts._maxRing ?? [tile.ring]))) {
        // outer boundary — no extra label needed
    }

    // Small resource indicator dot
    if (tile.is_explored && tile.resource_max > 0) {
        const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('cx', cx + size * 0.35);
        dot.setAttribute('cy', cy - size * 0.35);
        dot.setAttribute('r', '4');
        dot.setAttribute('fill', '#2196f3');
        dot.setAttribute('stroke', '#fff');
        dot.setAttribute('stroke-width', '1');
        g.appendChild(dot);
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
    if (!tile.is_ring_unlocked) return LOCKED_COLOR;
    if (!tile.is_explored)      return FOG_COLOR;
    if (tile.q === 0 && tile.r === 0) return CC_COLOR;
    if (tile.is_deep_scanned && tile.event_type) return EVENT_COLOR;
    return TILE_COLORS[tile.tile_type] ?? '#c8cdd6';
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
