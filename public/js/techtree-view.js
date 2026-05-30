function techtreeView(config) {
    return {
        phases:        config.phases,
        selectedTech:  null,
        activePhase:   1,
        isMobile:      false,
        touchStartX:   0,
        hoveredTechId: null,

        init() {
            this.checkBreakpoint();
            window.addEventListener('resize', () => {
                this.checkBreakpoint();
                this.$nextTick(() => this.drawAllLines());
            });
            this.$nextTick(() => this.drawAllLines());
        },

        checkBreakpoint() {
            this.isMobile = window.innerWidth < 640;
        },

        prevPhase() {
            if (this.activePhase > 1) {
                this.activePhase--;
                this.$nextTick(() => this.drawAllLines());
            }
        },

        nextPhase() {
            if (this.activePhase < 5) {
                this.activePhase++;
                this.$nextTick(() => this.drawAllLines());
            }
        },

        goToPhase(n) {
            this.activePhase = n;
            this.$nextTick(() => this.drawAllLines());
        },

        onTouchStart(e) {
            this.touchStartX = e.touches[0].clientX;
        },

        onTouchEnd(e) {
            if (!this.isMobile) return;
            const dx = e.changedTouches[0].clientX - this.touchStartX;
            if (Math.abs(dx) > 40) {
                dx < 0 ? this.nextPhase() : this.prevPhase();
            }
        },

        // ── Hover focus ────────────────────────────────────────────

        onCardEnter(tech) {
            this.hoveredTechId = 'tech-' + tech.type + '-' + tech.id;
            this.applyHoverFocus();
        },

        onCardLeave() {
            this.hoveredTechId = null;
            this.clearHoverFocus();
        },

        applyHoverFocus() {
            const focusedId = this.hoveredTechId;
            if (!focusedId) return;

            // Collect all lines from all phases
            const allLines = [];
            for (const phaseNum of Object.keys(this.phases)) {
                const phase = this.phases[phaseNum];
                if (phase.lines) allLines.push(...phase.lines);
            }

            // Find prerequisite source ids for the hovered card
            const prereqFromIds = new Set(
                allLines
                    .filter(l => l.to === focusedId)
                    .map(l => l.from)
            );

            // Dim / highlight cards (el.id matches full element id, e.g. "tech-building-25")
            document.querySelectorAll('.tech-card').forEach(el => {
                const isRelevant = el.id === focusedId || prereqFromIds.has(el.id);
                el.classList.remove('hover-dim', 'hover-highlight');
                el.classList.add(isRelevant ? 'hover-highlight' : 'hover-dim');
            });

            // Dim / highlight SVG paths and label chips (rect + text share data-to/data-from)
            const svgEl = this.$refs.globalSvg;
            if (svgEl) {
                svgEl.querySelectorAll('[data-to]').forEach(el => {
                    const isRelevant = el.dataset.to === focusedId;
                    el.classList.remove('hover-dim', 'hover-highlight');
                    el.classList.add(isRelevant ? 'hover-highlight' : 'hover-dim');
                });
            }
        },

        clearHoverFocus() {
            document.querySelectorAll('.tech-card').forEach(el => {
                el.classList.remove('hover-dim', 'hover-highlight');
            });
            const svgEl = this.$refs.globalSvg;
            if (svgEl) {
                svgEl.querySelectorAll('[data-to]').forEach(el => {
                    el.classList.remove('hover-dim', 'hover-highlight');
                });
            }
        },

        // ── Arrow drawing ──────────────────────────────────────────

        drawAllLines() {
            const svgEl     = this.$refs.globalSvg;
            const wrapperEl = this.$refs.sectionsWrapper;
            if (!svgEl || !wrapperEl) return;

            while (svgEl.firstChild) svgEl.removeChild(svgEl.firstChild);

            // Collect all lines from all phases
            const allLines = [];
            for (const phaseNum of Object.keys(this.phases)) {
                const phase = this.phases[phaseNum];
                if (phase.lines) allLines.push(...phase.lines);
            }

            const wRect = wrapperEl.getBoundingClientRect();

            // Arrow markers — smaller, compact arrowheads
            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            const makeMarker = (id, color) => {
                const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
                marker.setAttribute('id', id);
                marker.setAttribute('markerWidth',  '6');
                marker.setAttribute('markerHeight', '6');
                marker.setAttribute('refX', '5');
                marker.setAttribute('refY', '3');
                marker.setAttribute('orient', 'auto');
                const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                poly.setAttribute('points', '0 0, 6 3, 0 6');
                poly.setAttribute('fill', color);
                marker.appendChild(poly);
                return marker;
            };
            defs.appendChild(makeMarker('arr-met',   '#555'));
            defs.appendChild(makeMarker('arr-unmet', '#bbb'));
            svgEl.appendChild(defs);

            // Gather only visible lines (skip hidden x-show elements)
            const visibleLines = [];
            for (const line of allLines) {
                const fromEl = document.getElementById(line.from);
                const toEl   = document.getElementById(line.to);
                if (!fromEl || !toEl) continue;
                if (!fromEl.offsetParent || !toEl.offsetParent) continue;
                visibleLines.push({ ...line, fromEl, toEl });
            }

            if (visibleLines.length === 0) return;

            // Group by source/target for parallel offset
            const fromGroups = {};
            const toGroups   = {};
            for (const line of visibleLines) {
                (fromGroups[line.from] ??= []).push(line);
                (toGroups[line.to]     ??= []).push(line);
            }

            const SPREAD = 12; // px between parallel lines at same node

            for (const line of visibleLines) {
                const fR = line.fromEl.getBoundingClientRect();
                const tR = line.toEl.getBoundingClientRect();

                const fromList = fromGroups[line.from];
                const fromIdx  = fromList.indexOf(line);
                const fromN    = fromList.length;

                const toList = toGroups[line.to];
                const toIdx  = toList.indexOf(line);
                const toN    = toList.length;

                const cxFrom = fR.left + fR.width  / 2 - wRect.left;
                const cxTo   = tR.left + tR.width  / 2 - wRect.left;

                const x1 = cxFrom + (fromIdx - (fromN - 1) / 2) * SPREAD;
                const y1 = fR.bottom - wRect.top;

                const x2 = cxTo   + (toIdx   - (toN   - 1) / 2) * SPREAD;
                const y2 = tR.top  - wRect.top;

                // Staggered midY: lines from the same source bend at different heights (0.25–0.75)
                const midFraction = fromN === 1 ? 0.5 : 0.25 + (fromIdx / (fromN - 1)) * 0.5;
                const midY = y1 + (y2 - y1) * midFraction;

                const color    = line.met ? '#555' : '#bbb';
                const markerId = line.met ? 'url(#arr-met)' : 'url(#arr-unmet)';

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', `M ${x1} ${y1} L ${x1} ${midY} L ${x2} ${midY} L ${x2} ${y2}`);
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '1.5');
                path.setAttribute('marker-end', markerId);
                path.setAttribute('data-from', line.from);
                path.setAttribute('data-to',   line.to);
                if (!line.met) path.setAttribute('stroke-dasharray', '5 3');
                svgEl.appendChild(path);

                if (line.label) {
                    const midX   = (x1 + x2) / 2;
                    const labelW = line.label.length * 6 + 10;
                    const bgRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    bgRect.setAttribute('x',            String(midX - labelW / 2));
                    bgRect.setAttribute('y',            String(midY - 8));
                    bgRect.setAttribute('width',        String(labelW));
                    bgRect.setAttribute('height',       '13');
                    bgRect.setAttribute('rx',           '3');
                    bgRect.setAttribute('fill',         '#fff');
                    bgRect.setAttribute('stroke',       color);
                    bgRect.setAttribute('stroke-width', '1');
                    bgRect.setAttribute('data-from', line.from);
                    bgRect.setAttribute('data-to',   line.to);
                    svgEl.appendChild(bgRect);

                    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    text.setAttribute('x',                 String(midX));
                    text.setAttribute('y',                 String(midY + 1));
                    text.setAttribute('text-anchor',       'middle');
                    text.setAttribute('dominant-baseline', 'middle');
                    text.setAttribute('font-size',         '9');
                    text.setAttribute('font-weight',       '700');
                    text.setAttribute('font-family',       'sans-serif');
                    text.setAttribute('fill',      color);
                    text.setAttribute('data-from', line.from);
                    text.setAttribute('data-to',   line.to);
                    text.textContent = line.label;
                    svgEl.appendChild(text);
                }
            }

            const h = wrapperEl.scrollHeight;
            const w = wrapperEl.scrollWidth;
            svgEl.setAttribute('width',   String(w));
            svgEl.setAttribute('height',  String(h));
            svgEl.setAttribute('viewBox', `0 0 ${w} ${h}`);
        },

        openDetail(tech) {
            this.selectedTech = tech;
        },

        closeDetail() {
            this.selectedTech = null;
        },

        statusLabel(tech) {
            if (tech.type === 'personell') {
                return { built: 'Eingestellt', available: 'Verfügbar', locked: 'Gesperrt' }[tech.status] ?? tech.status;
            }
            const labels = {
                built:     tech.level > 0 ? `Lv ${tech.level}` : 'Gebaut',
                available: 'Verfügbar',
                locked:    'Gesperrt',
            };
            return labels[tech.status] ?? tech.status;
        },

        typeLabel(type) {
            const labels = {
                building:  'Gebäude',
                research:  'Forschung',
                ship:      'Schiff',
                personell: 'Berater',
            };
            return labels[type] ?? type;
        },
    };
}
