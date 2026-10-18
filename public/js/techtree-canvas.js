/*
 * Techtree canvas renderer (framework-free).
 *
 * Draws the techtree on a 16 px grid: notched boxes, 10 px stripe edges routed orthogonally
 * (downwards/sideways only, arrival from above, shared trunks per source/colour/style), pan/pinch/wheel
 * zoom via pointer events, and — on narrow screens — snap-swiping between the four columns.
 *
 * Data comes from the server (App\Support\TechtreeGraph): every node carries its grid cell
 * (col 0..3, row = global grid row) and its band; edges are derived from the prerequisite columns.
 *
 *   const tree = TechtreeCanvas.create({ root, stage, canvas, dots, items, edges, bandLabels, describe,
 *                                        onSelect, labels });
 *   items: [{ key, cat: 'b'|'k'|'s'|'a', band, col, row, ... }]  edges: [{ from, to, dashed, met }]
 *   tree.refresh();            // re-read describe()/status after data changed
 *   tree.clearSelection();     // panel closed
 *   tree.focusNode(key, {bottom}) // pan a node into the free area
 */
(function (global) {
    'use strict';

    // ── Grid and sizes (multiples of 16 so stripes run on grid lines) ──
    var G = 16,
        NW = 96,
        NH = 32,
        NOTCH = 14,
        PITCH = 320,
        COL0 = 224;
    var STRIPE = 10,
        ARROW_H = 22,
        ARROW_W = 14,
        BEND = 10;
    var NARROW_MAX = 720;
    var MIN_S = 0.2,
        MAX_S = 2.5;
    var FONT = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';

    function rowY(r) {
        return 112 + 256 * r;
    }

    function Heap() {
        this.a = [];
    }
    Heap.prototype.push = function (p, s) {
        var a = this.a,
            i = a.length;
        a.push([p, s]);
        while (i > 0) {
            var j = (i - 1) >> 1;
            if (a[j][0] <= a[i][0]) break;
            var t = a[i];
            a[i] = a[j];
            a[j] = t;
            i = j;
        }
    };
    Heap.prototype.pop = function () {
        var a = this.a,
            top = a[0],
            last = a.pop();
        if (a.length) {
            a[0] = last;
            var i = 0,
                n = a.length;
            for (;;) {
                var l = 2 * i + 1,
                    r = l + 1,
                    m = i;
                if (l < n && a[l][0] < a[m][0]) m = l;
                if (r < n && a[r][0] < a[m][0]) m = r;
                if (m === i) break;
                var t = a[i];
                a[i] = a[m];
                a[m] = t;
                i = m;
            }
        }
        return top;
    };

    function create(opts) {
        var cv = opts.canvas,
            ctx = cv.getContext('2d'),
            stage = opts.stage || cv.parentNode,
            dotsEl = opts.dots || null,
            rootEl = opts.root || stage;
        var labels = opts.labels || {};
        // describe(item) -> { label, sub, status, pulse }, re-read on refresh().
        var describe = opts.describe;
        var onSelect = opts.onSelect || function () {};

        // ── Graph ──
        var N = {},
            IDS = [],
            EDGES = [],
            parents = {},
            kids = {},
            BANDS = [],
            XS = [];
        var MODE = null,
            W = 0,
            H = 0,
            GW = 0,
            GH = 0,
            blocked,
            occH,
            occV,
            gScore,
            parent;

        function setup(items, edges) {
            N = {};
            IDS = [];
            var cols = 4;
            items.forEach(function (it) {
                N[it.key] = {
                    key: it.key,
                    cat: it.cat,
                    band: it.band,
                    c: it.col,
                    r: it.row,
                    status: 'available',
                    pulse: false,
                    item: it,
                    label: '',
                    sub: '',
                    isRoot: it.band === 0,
                };
                IDS.push(it.key);
                cols = Math.max(cols, it.col + 1);
            });
            XS = [];
            for (var i = 0; i < cols; i++) XS.push(COL0 + PITCH * i);
            EDGES = [];
            edges.forEach(function (e, i) {
                if (!N[e.from] || !N[e.to]) return;
                EDGES.push({ i: i, f: e.from, t: e.to, dashed: !!e.dashed, met: e.met !== false, z: N[e.to].cat, pts: null });
            });
            parents = {};
            kids = {};
            IDS.forEach(function (id) {
                parents[id] = [];
                kids[id] = [];
            });
            EDGES.forEach(function (e) {
                parents[e.t].push(e.f);
                kids[e.f].push(e.t);
            });
            var rows = 0;
            IDS.forEach(function (id) {
                var n = N[id];
                n.x = XS[n.c];
                n.y = rowY(n.r);
                rows = Math.max(rows, n.r + 1);
            });
            W = XS[XS.length - 1] + NW + 128;
            H = rowY(rows - 1) + NH + 112;

            // Band stripes: a band starts half a row above its first node; the first band (Start) from 0.
            var minRow = {};
            IDS.forEach(function (id) {
                var n = N[id];
                minRow[n.band] = Math.min(minRow[n.band] === undefined ? Infinity : minRow[n.band], n.r);
            });
            var bandNums = Object.keys(minRow)
                .map(Number)
                .sort(function (a, b) {
                    return a - b;
                });
            var tops = bandNums.map(function (b, i) {
                return i === 0 ? 0 : rowY(minRow[b]) - 128;
            });
            BANDS = bandNums.map(function (b, i) {
                return {
                    label: (opts.bandLabels && opts.bandLabels[b]) || '',
                    top: tops[i],
                    bot: i + 1 < tops.length ? tops[i + 1] : H,
                };
            });
            refreshText();
            assignPorts();
            routeAll();
            buildDots();
        }

        function refreshText() {
            IDS.forEach(function (id) {
                var n = N[id],
                    d = describe(n.item);
                n.label = d.label;
                n.sub = d.sub;
                n.status = d.status;
                n.pulse = !!d.pulse;
            });
        }

        function ancestors(id, acc) {
            parents[id].forEach(function (p) {
                if (!acc[p]) {
                    acc[p] = 1;
                    ancestors(p, acc);
                }
            });
            return acc;
        }

        // ── Router ──
        function stepCost(v, vertical, src, dashed) {
            var c = 1,
                same = false,
                other = false,
                sameDiff = false;
            var arr = (vertical ? occV : occH)[v];
            if (arr)
                for (var i = 0; i < arr.length; i++) {
                    var k = arr[i];
                    if (k.src !== src) other = true;
                    else if (k.dashed !== dashed) sameDiff = true;
                    else same = true;
                }
            if (other) c += 120;
            else if (sameDiff) c += 60;
            else if (same) c = 0.4;
            var cross = (vertical ? occH : occV)[v];
            if (cross)
                for (var j = 0; j < cross.length; j++)
                    if (cross[j].src !== src) {
                        c += 18;
                        break;
                    }
            var nb = vertical ? [v - 1, v + 1] : [v - GW, v + GW];
            for (var q = 0; q < 2; q++) {
                var na = (vertical ? occV : occH)[nb[q]];
                if (na)
                    for (var m = 0; m < na.length; m++)
                        if (na[m].src !== src) {
                            c += 3;
                            break;
                        }
            }
            return c;
        }

        var MOVES = [
            { dx: 0, dy: 1, d: 0 },
            { dx: -1, dy: 0, d: 1 },
            { dx: 1, dy: 0, d: 2 },
        ];

        function astar(startV, goalV, src, dashed) {
            gScore.fill(Infinity);
            parent.fill(-1);
            var gx1 = goalV % GW,
                gy1 = (goalV / GW) | 0;
            function hh(v) {
                return 0.4 * (Math.abs((v % GW) - gx1) + Math.abs(((v / GW) | 0) - gy1));
            }
            var heap = new Heap(),
                s0 = startV * 3;
            gScore[s0] = 0;
            heap.push(hh(startV), s0);
            while (heap.a.length) {
                var it = heap.pop(),
                    st = it[1],
                    v = (st / 3) | 0,
                    d = st % 3;
                if (it[0] > gScore[st] + hh(v) + 1e-4) continue;
                if (v === goalV && d === 0) {
                    var out = [],
                        cur = st;
                    while (cur !== -1) {
                        out.push((cur / 3) | 0);
                        cur = parent[cur];
                    }
                    return out.reverse();
                }
                var gx = v % GW,
                    gy = (v / GW) | 0;
                for (var mi = 0; mi < 3; mi++) {
                    var m = MOVES[mi];
                    if ((d === 1 && m.d === 2) || (d === 2 && m.d === 1)) continue;
                    var nx = gx + m.dx,
                        ny = gy + m.dy;
                    if (nx < 1 || nx > GW - 2 || ny < 0 || ny > GH - 1) continue;
                    var nv = ny * GW + nx;
                    if (blocked[nv] && nv !== goalV) continue;
                    var c = stepCost(nv, m.d === 0, src, dashed);
                    if (m.d !== d) c += BEND;
                    var ns = nv * 3 + m.d,
                        ng = gScore[st] + c;
                    if (ng < gScore[ns]) {
                        gScore[ns] = ng;
                        parent[ns] = st;
                        heap.push(ng + hh(nv), ns);
                    }
                }
            }
            return null;
        }

        function mark(vs, src, dashed) {
            for (var i = 0; i < vs.length - 1; i++) {
                var a = vs[i],
                    b = vs[i + 1],
                    vert = a % GW === b % GW,
                    tbl = vert ? occV : occH;
                [a, b].forEach(function (v) {
                    (tbl[v] = tbl[v] || []).push({ src: src, dashed: dashed });
                });
            }
        }

        function assignPorts() {
            var outs = {},
                ins = {};
            EDGES.forEach(function (e) {
                (outs[e.f] = outs[e.f] || []).push(e);
                (ins[e.t] = ins[e.t] || []).push(e);
            });
            // Arrows from one source with the same colour and style share a trunk and branch off further down.
            // The group whose target sits directly below gets the centre port so its arrow runs straight down.
            Object.keys(outs).forEach(function (id) {
                var groups = {};
                outs[id].forEach(function (e) {
                    var k = e.z + (e.dashed ? 'd' : 's');
                    e.grp = id + '|' + k;
                    var dx = N[e.t].x - N[id].x,
                        g = (groups[k] = groups[k] || { sum: 0, n: 0, near: Infinity });
                    g.sum += dx;
                    g.n++;
                    g.near = Math.min(g.near, Math.abs(dx));
                });
                var keys = Object.keys(groups).sort(function (a, b) {
                    return groups[a].sum / groups[a].n - groups[b].sum / groups[b].n || (a < b ? -1 : 1);
                });
                var center = 0;
                keys.forEach(function (k, i) {
                    if (groups[k].near < groups[keys[center]].near) center = i;
                });
                outs[id].forEach(function (e) {
                    e.spx = (keys.indexOf(e.z + (e.dashed ? 'd' : 's')) - center) * 2 * G;
                });
            });
            Object.keys(ins).forEach(function (id) {
                var l = ins[id].slice().sort(function (a, b) {
                    return N[a.f].x - N[b.f].x || a.dashed - b.dashed;
                });
                l.forEach(function (e, k) {
                    e.tpx = (2 * k - (l.length - 1)) * G;
                });
            });
        }

        function routeAll() {
            GW = Math.round(W / G) + 1;
            GH = Math.round(H / G) + 1;
            blocked = new Uint8Array(GW * GH);
            occH = new Array(GW * GH);
            occV = new Array(GW * GH);
            gScore = new Float32Array(GW * GH * 3);
            parent = new Int32Array(GW * GH * 3);
            IDS.forEach(function (id) {
                var n = N[id],
                    gx = n.x / G,
                    gy = n.y / G;
                for (var dx = -6; dx <= 6; dx++) for (var dy = -2; dy <= 2; dy++) blocked[(gy + dy) * GW + gx + dx] = 1;
            });
            var order = EDGES.slice().sort(function (a, b) {
                return N[a.t].r - N[a.f].r - (N[b.t].r - N[b.f].r) || Math.abs(N[a.t].x - N[a.f].x) - Math.abs(N[b.t].x - N[b.f].x) || a.i - b.i;
            });
            order.forEach(function (e) {
                var f = N[e.f],
                    t = N[e.t];
                var sx = (f.x + e.spx) / G,
                    sy = (f.y + NH) / G,
                    tx = (t.x + e.tpx) / G,
                    ty = (t.y - NH) / G;
                var start = (sy + 2) * GW + sx,
                    goal = (ty - 1) * GW + tx;
                var path = astar(start, goal, e.grp, e.dashed);
                if (!path) {
                    var midY = Math.round((sy + ty) / 2);
                    path = [start, midY * GW + sx, midY * GW + tx, goal];
                }
                var full = [sy * GW + sx, (sy + 1) * GW + sx].concat(path, [ty * GW + tx]);
                mark(full, e.grp, e.dashed);
                var pts = full.map(function (v) {
                    return [(v % GW) * G, ((v / GW) | 0) * G];
                });
                var simp = [pts[0]];
                for (var i = 1; i < pts.length - 1; i++) {
                    var p = simp[simp.length - 1],
                        c = pts[i],
                        n = pts[i + 1];
                    if ((p[0] === c[0] && c[0] === n[0]) || (p[1] === c[1] && c[1] === n[1])) continue;
                    simp.push(c);
                }
                simp.push(pts[pts.length - 1]);
                e.pts = simp;
            });
        }

        // ── Palette from CSS custom properties on the root element ──
        var P = {};
        function readPal() {
            var s = getComputedStyle(rootEl);
            function g(n, d) {
                return s.getPropertyValue(n).trim() || d;
            }
            P = {
                bg: g('--tt-bg', '#ffffff'),
                surface: g('--tt-surface', '#f7f7f5'),
                raised: g('--tt-raised', '#ffffff'),
                text: g('--tt-text', '#1a1a1e'),
                text2: g('--tt-text-2', '#6b6b7a'),
                border: g('--tt-border', '#e8e8ec'),
                accent: g('--tt-accent', '#8c2030'),
                anth: g('--tt-anthracite', '#333333'),
                b: { c: g('--tt-cb', '#5599dd'), fg: g('--tt-cb-fg', '#1e40af'), bg: g('--tt-cb-bg', '#dbeafe') },
                k: { c: g('--tt-ck', '#27ae60'), fg: g('--tt-ck-fg', '#166534'), bg: g('--tt-ck-bg', '#dcfce7') },
                s: { c: g('--tt-cs', '#d4a017'), fg: g('--tt-cs-fg', '#7a5500'), bg: g('--tt-cs-bg', '#fef3c7') },
                a: { c: g('--tt-ca', '#7c65cc'), fg: g('--tt-ca-fg', '#5b21b6'), bg: g('--tt-ca-bg', '#ede9fe') },
            };
        }

        // ── Drawing ──
        var view = { s: 1, tx: 0, ty: 0 },
            vw = 0,
            vh = 0,
            dpr = 1;
        var hover = null,
            pinned = null,
            raf = 0,
            curDot = -1,
            anim = 0,
            insetBottom = 0;
        var reduced = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function activeId() {
            return pinned || hover;
        }
        function relatedSets(id) {
            if (!id || !N[id]) return null;
            var anc = ancestors(id, {}),
                nodes = {},
                edges = {};
            nodes[id] = 1;
            Object.keys(anc).forEach(function (k) {
                nodes[k] = 1;
            });
            kids[id].forEach(function (k) {
                nodes[k] = 1;
            });
            EDGES.forEach(function (e, i) {
                if (e.t === id || anc[e.t] || e.f === id) edges[i] = 1;
            });
            return { nodes: nodes, edges: edges };
        }

        function notchPath(x0, y0, x1, y1, n) {
            ctx.beginPath();
            ctx.moveTo(x0, y0);
            ctx.lineTo(x1 - n, y0);
            ctx.lineTo(x1, y0 + n);
            ctx.lineTo(x1, y1);
            ctx.lineTo(x0, y1);
            ctx.closePath();
        }

        function drawEdge(e, alpha) {
            var pts = e.pts,
                col = P[e.z].c,
                n = pts.length;
            var endY = pts[n - 1][1] - ARROW_H + 1;
            ctx.globalAlpha = alpha;
            ctx.strokeStyle = col;
            ctx.fillStyle = col;
            ctx.lineWidth = STRIPE;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'butt';
            ctx.setLineDash(e.dashed ? [18, 10] : []);
            ctx.beginPath();
            ctx.moveTo(pts[0][0], pts[0][1]);
            for (var i = 1; i < n - 1; i++) {
                var a = pts[i - 1],
                    b = pts[i],
                    c = pts[i + 1];
                var r = Math.min(8, Math.abs(b[0] - a[0] + b[1] - a[1]) / 2, Math.abs(c[0] - b[0] + c[1] - b[1]) / 2);
                ctx.arcTo(b[0], b[1], c[0], c[1], r);
            }
            ctx.lineTo(pts[n - 1][0], endY);
            ctx.stroke();
            ctx.setLineDash([]);
            var tx = pts[n - 1][0],
                ty = pts[n - 1][1];
            ctx.beginPath();
            ctx.moveTo(tx, ty);
            ctx.lineTo(tx - ARROW_W, ty - ARROW_H);
            ctx.lineTo(tx + ARROW_W, ty - ARROW_H);
            ctx.closePath();
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        function fitText(txt, weight, size, maxW) {
            var s = size;
            ctx.font = weight + ' ' + s + 'px ' + FONT;
            var w = ctx.measureText(txt).width;
            if (w > maxW) {
                s = Math.max(9, (size * maxW) / w);
                ctx.font = weight + ' ' + s + 'px ' + FONT;
            }
        }

        function drawNode(n, alpha, isActive) {
            var c = P[n.cat],
                x0 = n.x - NW,
                y0 = n.y - NH,
                x1 = n.x + NW,
                y1 = n.y + NH;
            var locked = n.status === 'locked',
                built = n.status === 'built';
            ctx.globalAlpha = alpha;
            notchPath(x0, y0, x1, y1, NOTCH);
            ctx.fillStyle = isActive || built ? c.bg : locked ? P.surface : P.raised;
            ctx.fill();
            ctx.lineJoin = 'miter';
            ctx.lineWidth = n.isRoot ? 3.5 : isActive ? 3.5 : 2.5;
            ctx.strokeStyle = n.isRoot ? P.accent : c.c;
            if (locked && !isActive) ctx.globalAlpha = alpha * 0.55;
            ctx.stroke();
            ctx.globalAlpha = alpha;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = locked ? P.text2 : P.text;
            fitText(n.label, 600, 16, NW * 2 - 24);
            ctx.fillText(n.label, n.x, n.y - 3);
            ctx.fillStyle = locked ? P.text2 : c.fg;
            fitText(n.sub, 400, 12.5, NW * 2 - 24);
            ctx.fillText(n.sub, n.x, n.y + 16);
            ctx.globalAlpha = 1;
        }

        // Onboarding hint: a soft ring that breathes around the nodes the current hint points at.
        function drawPulse(n, t) {
            var ph = (t % 2000) / 2000,
                o = 3 + 9 * ph;
            ctx.globalAlpha = 0.55 * (1 - ph);
            notchPath(n.x - NW - o, n.y - NH - o, n.x + NW + o, n.y + NH + o, NOTCH + o);
            ctx.lineWidth = 3;
            ctx.strokeStyle = '#60a5fa';
            ctx.stroke();
            ctx.globalAlpha = 1;
        }

        var hasPulse = false;
        function draw(now) {
            raf = 0;
            if (!vw || !MODE) return;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            ctx.fillStyle = P.bg;
            ctx.fillRect(0, 0, vw, vh);
            ctx.setTransform(dpr * view.s, 0, 0, dpr * view.s, dpr * view.tx, dpr * view.ty);

            var bx0 = -view.tx / view.s - 50,
                bw = vw / view.s + 100;
            BANDS.forEach(function (b, i) {
                if (i % 2 === 1) {
                    ctx.fillStyle = P.surface;
                    ctx.fillRect(bx0, b.top, bw, b.bot - b.top);
                }
                ctx.fillStyle = P.border;
                ctx.fillRect(bx0, b.top, bw, 1.5 / view.s);
            });

            var rel = relatedSets(activeId());
            EDGES.forEach(function (e, i) {
                if (!rel || !rel.edges[i]) drawEdge(e, rel ? 0.1 : e.dashed ? 0.8 : 0.92);
            });
            if (rel)
                EDGES.forEach(function (e, i) {
                    if (rel.edges[i]) drawEdge(e, 1);
                });
            hasPulse = false;
            IDS.forEach(function (id) {
                var n = N[id],
                    on = !rel || rel.nodes[id];
                drawNode(n, on ? 1 : 0.16, id === activeId());
                if (n.pulse && n.status === 'available' && on) {
                    hasPulse = true;
                    if (!reduced) drawPulse(n, now || 0);
                }
            });

            // Band marks stay at the left edge while panning.
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            ctx.font = '700 11px ' + FONT;
            ctx.textBaseline = 'middle';
            ctx.textAlign = 'left';
            BANDS.forEach(function (b) {
                var top = view.ty + b.top * view.s,
                    bot = view.ty + b.bot * view.s;
                if (bot < 0 || top > vh || !b.label) return;
                var label = b.label.toUpperCase(),
                    tw = ctx.measureText(label).width + 20;
                var y = Math.min(Math.max(top + 12, 10), bot - 34),
                    x = 12;
                ctx.beginPath();
                ctx.moveTo(x, y);
                ctx.lineTo(x + tw - 7, y);
                ctx.lineTo(x + tw, y + 7);
                ctx.lineTo(x + tw, y + 24);
                ctx.lineTo(x, y + 24);
                ctx.closePath();
                ctx.fillStyle = P.anth;
                ctx.fill();
                ctx.fillStyle = '#ffffff';
                ctx.fillText(label, x + 10, y + 12.5);
            });
            updateDots();
            if (hasPulse && !reduced) requestDraw();
        }
        function requestDraw() {
            if (!raf) raf = requestAnimationFrame(draw);
        }

        // ── Column dots (narrow screens) ──
        function colAtCenter() {
            var f = ((vw / 2 - view.tx) / view.s - XS[0]) / PITCH;
            return Math.max(0, Math.min(XS.length - 1, Math.round(f)));
        }
        function buildDots() {
            if (!dotsEl) return;
            dotsEl.innerHTML = '';
            XS.forEach(function (x, i) {
                var b = document.createElement('button');
                b.type = 'button';
                b.setAttribute('aria-label', (labels.columnN || 'Column :n of :total').replace(':n', i + 1).replace(':total', XS.length));
                b.addEventListener('click', function () {
                    goToCol(i);
                });
                dotsEl.appendChild(b);
            });
            curDot = -1;
        }
        function updateDots() {
            if (!dotsEl) return;
            var narrow = MODE === 'narrow';
            if (dotsEl.hidden === narrow) dotsEl.hidden = !narrow;
            if (!narrow) return;
            var i = colAtCenter();
            if (i !== curDot) {
                curDot = i;
                Array.prototype.forEach.call(dotsEl.children, function (b, k) {
                    b.classList.toggle('on', k === i);
                });
            }
        }
        function goToCol(i) {
            animateTo({ s: view.s, tx: vw / 2 - XS[i] * view.s, ty: view.ty });
        }

        // ── View ──
        function clampView() {
            var m = 140;
            view.tx = Math.min(vw - m, Math.max(m - W * view.s, view.tx));
            view.ty = Math.min(vh - m, Math.max(m - H * view.s, view.ty));
        }
        function startScale() {
            return MODE === 'narrow' ? Math.min(1.1, Math.max(0.85, vw / 400)) : Math.min(1.1, Math.max(0.8, vw / W));
        }
        function rootNode() {
            for (var i = 0; i < IDS.length; i++) if (N[IDS[i]].isRoot) return N[IDS[i]];
            return N[IDS[0]];
        }
        function startView() {
            var s = startScale(),
                cx = W * s <= vw ? W / 2 : rootNode().x;
            return { s: s, tx: vw / 2 - cx * s, ty: 14 };
        }
        function overviewView() {
            var s = Math.min(vw / W, vh / H) * 0.98;
            return { s: s, tx: (vw - W * s) / 2, ty: (vh - H * s) / 2 };
        }
        function animateTo(t) {
            cancelAnimationFrame(anim);
            var from = { s: view.s, tx: view.tx, ty: view.ty },
                t0 = performance.now(),
                dur = reduced ? 1 : 260;
            (function step(now) {
                var p = Math.min(1, (now - t0) / dur),
                    e = 1 - Math.pow(1 - p, 3);
                view.s = from.s + (t.s - from.s) * e;
                view.tx = from.tx + (t.tx - from.tx) * e;
                view.ty = from.ty + (t.ty - from.ty) * e;
                requestDraw();
                if (p < 1) anim = requestAnimationFrame(step);
            })(t0);
        }
        function zoomAt(px, py, factor, animated) {
            var ns = Math.min(MAX_S, Math.max(MIN_S, view.s * factor));
            var wx = (px - view.tx) / view.s,
                wy = (py - view.ty) / view.s;
            var t = { s: ns, tx: px - wx * ns, ty: py - wy * ns };
            if (animated) animateTo(t);
            else {
                view.s = t.s;
                view.tx = t.tx;
                view.ty = t.ty;
                clampView();
                requestDraw();
            }
        }
        // After a swipe, settle on the nearest column; a quick flick moves one column further.
        function snapAfterSwipe(vx) {
            var f = ((vw / 2 - view.tx) / view.s - XS[0]) / PITCH,
                t = Math.round(f);
            if (vx < -0.3) t = Math.floor(f) + 1;
            else if (vx > 0.3) t = Math.ceil(f) - 1;
            goToCol(Math.max(0, Math.min(XS.length - 1, t)));
        }

        // Pan a node into the visible area (not under the bottom sheet / outside the stage).
        function focusNode(key, o) {
            var n = N[key];
            if (!n) return;
            insetBottom = (o && o.bottom) || 0;
            var availH = Math.max(120, vh - insetBottom);
            var sx = view.tx + n.x * view.s,
                sy = view.ty + n.y * view.s,
                hw = NW * view.s,
                hh = NH * view.s;
            var tx = view.tx,
                ty = view.ty;
            if (sx - hw < 8) tx += 8 - (sx - hw);
            else if (sx + hw > vw - 8) tx -= sx + hw - (vw - 8);
            if (sy - hh < 8) ty += 8 - (sy - hh);
            else if (sy + hh > availH - 8) ty -= sy + hh - (availH - 8);
            if (tx !== view.tx || ty !== view.ty) animateTo({ s: view.s, tx: tx, ty: ty });
        }

        function resize() {
            var r = stage.getBoundingClientRect();
            vw = Math.max(1, Math.round(r.width));
            vh = Math.max(1, Math.round(r.height));
            dpr = Math.min(global.devicePixelRatio || 1, 3);
            cv.width = Math.round(vw * dpr);
            cv.height = Math.round(vh * dpr);
            // Mode follows the device width, not the stage: the desktop side panel narrows the stage
            // but must not switch the tree into the phone behaviour (column snapping, dots).
            var mode = global.innerWidth <= NARROW_MAX ? 'narrow' : 'wide';
            if (mode !== MODE) {
                MODE = mode;
                var v = startView();
                view.s = v.s;
                view.tx = v.tx;
                view.ty = v.ty;
            }
            clampView();
            requestDraw();
        }

        // ── Input: pointer events for mouse, touch and pen ──
        var ptrs = {},
            nPtr = 0,
            g0 = null,
            moved = false,
            downPt = null,
            lock = null,
            samples = [];
        function toWorld(px, py) {
            return [(px - view.tx) / view.s, (py - view.ty) / view.s];
        }
        function hit(px, py) {
            var w = toWorld(px, py);
            for (var i = IDS.length - 1; i >= 0; i--) {
                var n = N[IDS[i]];
                if (Math.abs(w[0] - n.x) <= NW && Math.abs(w[1] - n.y) <= NH) return n.key;
            }
            return null;
        }
        function local(e) {
            var r = cv.getBoundingClientRect();
            return [e.clientX - r.left, e.clientY - r.top];
        }
        function pinchState() {
            var ks = Object.keys(ptrs),
                a = ptrs[ks[0]],
                b = ptrs[ks[1]];
            return { d: Math.hypot(a[0] - b[0], a[1] - b[1]) || 1, mx: (a[0] + b[0]) / 2, my: (a[1] + b[1]) / 2 };
        }

        function onDown(e) {
            cancelAnimationFrame(anim);
            var p = local(e);
            ptrs[e.pointerId] = p;
            nPtr++;
            try {
                cv.setPointerCapture(e.pointerId);
            } catch (x) {}
            if (nPtr === 1) {
                moved = false;
                lock = null;
                samples = [{ t: e.timeStamp, x: p[0] }];
                downPt = p.slice();
                g0 = { tx: view.tx, ty: view.ty, px: p[0], py: p[1] };
                cv.classList.add('drag');
            } else if (nPtr === 2) {
                var st = pinchState();
                g0 = { s: view.s, tx: view.tx, ty: view.ty, d: st.d, mx: st.mx, my: st.my };
                moved = true;
                lock = null;
            }
        }
        function onMove(e) {
            var p = local(e);
            if (ptrs[e.pointerId]) {
                ptrs[e.pointerId] = p;
                if (nPtr === 1 && g0) {
                    var dx = p[0] - g0.px,
                        dy = p[1] - g0.py;
                    if (!moved && Math.hypot(p[0] - downPt[0], p[1] - downPt[1]) > 6) moved = true;
                    if (moved) {
                        // On phones one gesture moves either horizontally (switch column) or vertically (scroll).
                        if (MODE === 'narrow' && !lock) lock = Math.abs(dx) > Math.abs(dy) ? 'h' : 'v';
                        if (lock !== 'v') view.tx = g0.tx + dx;
                        if (lock !== 'h') view.ty = g0.ty + dy;
                        clampView();
                        requestDraw();
                        samples.push({ t: e.timeStamp, x: p[0] });
                        while (samples.length > 2 && e.timeStamp - samples[0].t > 120) samples.shift();
                    }
                } else if (nPtr === 2 && g0) {
                    var st = pinchState(),
                        ns = Math.min(MAX_S, Math.max(MIN_S, (g0.s * st.d) / g0.d));
                    var wx = (g0.mx - g0.tx) / g0.s,
                        wy = (g0.my - g0.ty) / g0.s;
                    view.s = ns;
                    view.tx = st.mx - wx * ns;
                    view.ty = st.my - wy * ns;
                    clampView();
                    requestDraw();
                }
                return;
            }
            if (e.pointerType === 'mouse') {
                var h = hit(p[0], p[1]);
                cv.classList.toggle('node', !!h);
                if (h !== hover) {
                    hover = h;
                    requestDraw();
                }
            }
        }
        function endPtr(e) {
            if (!ptrs[e.pointerId]) return;
            var wasTap = nPtr === 1 && !moved && e.type === 'pointerup',
                p = ptrs[e.pointerId];
            var swiped = nPtr === 1 && moved && lock === 'h' && MODE === 'narrow' && e.type === 'pointerup';
            delete ptrs[e.pointerId];
            nPtr = Math.max(0, nPtr - 1);
            if (nPtr === 0) {
                cv.classList.remove('drag');
                g0 = null;
            } else if (nPtr === 1) {
                var k = Object.keys(ptrs)[0],
                    q = ptrs[k];
                g0 = { tx: view.tx, ty: view.ty, px: q[0], py: q[1] };
                downPt = q.slice();
                moved = true;
                lock = null;
            }
            if (swiped && view.s >= startScale() * 0.8) {
                var a = samples[0],
                    b = samples[samples.length - 1],
                    dt = Math.max(1, b.t - a.t);
                snapAfterSwipe((b.x - a.x) / dt);
            }
            if (wasTap) {
                var h = hit(p[0], p[1]);
                pinned = h && h !== pinned ? h : null;
                requestDraw();
                onSelect(pinned);
            }
        }
        function onLeave(e) {
            if (e.pointerType === 'mouse' && hover && !ptrs[e.pointerId]) {
                hover = null;
                cv.classList.remove('node');
                requestDraw();
            }
        }
        function onWheel(e) {
            e.preventDefault();
            cancelAnimationFrame(anim);
            var p = local(e),
                dy = e.deltaMode === 1 ? e.deltaY * 16 : e.deltaY;
            zoomAt(p[0], p[1], Math.exp(-dy * (e.ctrlKey ? 0.01 : 0.0016)), false);
        }
        function onKey(e) {
            var step = 80,
                handled = true;
            if (e.key === 'ArrowLeft') {
                if (MODE === 'narrow') goToCol(Math.max(0, colAtCenter() - 1));
                else view.tx += step;
            } else if (e.key === 'ArrowRight') {
                if (MODE === 'narrow') goToCol(Math.min(XS.length - 1, colAtCenter() + 1));
                else view.tx -= step;
            } else if (e.key === 'ArrowUp') view.ty += step;
            else if (e.key === 'ArrowDown') view.ty -= step;
            else if (e.key === '+' || e.key === '=') zoomAt(vw / 2, vh / 2, 1.2, false);
            else if (e.key === '-') zoomAt(vw / 2, vh / 2, 1 / 1.2, false);
            else if (e.key === 'Escape') {
                pinned = null;
                requestDraw();
                onSelect(null);
            } else handled = false;
            if (handled) {
                e.preventDefault();
                clampView();
                requestDraw();
            }
        }

        cv.addEventListener('pointerdown', onDown);
        cv.addEventListener('pointermove', onMove);
        cv.addEventListener('pointerup', endPtr);
        cv.addEventListener('pointercancel', endPtr);
        cv.addEventListener('pointerleave', onLeave);
        cv.addEventListener('wheel', onWheel, { passive: false });
        cv.addEventListener('keydown', onKey);
        var onWinResize = function () {
            resize();
        };
        global.addEventListener('resize', onWinResize);
        var ro = global.ResizeObserver ? new ResizeObserver(onWinResize) : null;
        if (ro) ro.observe(stage);

        // ── Start ──
        readPal();
        setup(opts.items, opts.edges);
        resize();

        return {
            refresh: function (items, edges) {
                if (edges) {
                    edges.forEach(function (ne) {
                        EDGES.forEach(function (e) {
                            if (e.f === ne.from && e.t === ne.to) e.met = ne.met !== false;
                        });
                    });
                }
                refreshText();
                requestDraw();
            },
            select: function (key) {
                pinned = key && N[key] ? key : null;
                requestDraw();
            },
            clearSelection: function () {
                pinned = null;
                insetBottom = 0;
                requestDraw();
            },
            focusNode: focusNode,
            start: function () {
                animateTo(startView());
            },
            overview: function () {
                animateTo(overviewView());
            },
            zoom: function (factor) {
                zoomAt(vw / 2, vh / 2, factor, true);
            },
            resize: resize,
            // Test/debug access to the computed layout.
            debug: function () {
                return { nodes: N, edges: EDGES, width: W, height: H, mode: MODE, view: view };
            },
            destroy: function () {
                cancelAnimationFrame(raf);
                cancelAnimationFrame(anim);
                global.removeEventListener('resize', onWinResize);
                if (ro) ro.disconnect();
                cv.removeEventListener('pointerdown', onDown);
                cv.removeEventListener('pointermove', onMove);
                cv.removeEventListener('pointerup', endPtr);
                cv.removeEventListener('pointercancel', endPtr);
                cv.removeEventListener('pointerleave', onLeave);
                cv.removeEventListener('wheel', onWheel);
                cv.removeEventListener('keydown', onKey);
            },
        };
    }

    global.TechtreeCanvas = { create: create };
})(window);
