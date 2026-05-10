function techtreeView(config) {
    return {
        phases:       config.phases,
        selectedTech: null,
        activePhase:  1,
        isMobile:     false,
        touchStartX:  0,

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

            // Arrow markers
            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            const makeMarker = (id, color) => {
                const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
                marker.setAttribute('id', id);
                marker.setAttribute('markerWidth', '10');
                marker.setAttribute('markerHeight', '10');
                marker.setAttribute('refX', '9');
                marker.setAttribute('refY', '4');
                marker.setAttribute('orient', 'auto');
                const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                poly.setAttribute('points', '0 0, 10 4, 0 8');
                poly.setAttribute('fill', color);
                marker.appendChild(poly);
                return marker;
            };
            defs.appendChild(makeMarker('arr-met',   '#27ae60'));
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

            const SPREAD = 16; // px between parallel lines at same node

            for (const line of visibleLines) {
                const fR = line.fromEl.getBoundingClientRect();
                const tR = line.toEl.getBoundingClientRect();

                const fromList = fromGroups[line.from];
                const fromIdx  = fromList.indexOf(line);
                const fromN    = fromList.length;

                const toList = toGroups[line.to];
                const toIdx  = toList.indexOf(line);
                const toN    = toList.length;

                const cxFrom = fR.left + fR.width / 2 - wRect.left;
                const cxTo   = tR.left + tR.width / 2 - wRect.left;

                const x1   = cxFrom + (fromIdx - (fromN - 1) / 2) * SPREAD;
                const y1   = fR.bottom - wRect.top;
                const x2   = cxTo   + (toIdx   - (toN   - 1) / 2) * SPREAD;
                const y2   = tR.top  - wRect.top;
                const midY = (y1 + y2) / 2;

                const color    = line.met ? '#27ae60' : '#bbb';
                const markerId = line.met ? 'url(#arr-met)' : 'url(#arr-unmet)';

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', `M ${x1} ${y1} L ${x1} ${midY} L ${x2} ${midY} L ${x2} ${y2}`);
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('marker-end', markerId);
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
                    svgEl.appendChild(bgRect);

                    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    text.setAttribute('x',                 String(midX));
                    text.setAttribute('y',                 String(midY + 1));
                    text.setAttribute('text-anchor',       'middle');
                    text.setAttribute('dominant-baseline', 'middle');
                    text.setAttribute('font-size',         '9');
                    text.setAttribute('font-weight',       '700');
                    text.setAttribute('font-family',       'sans-serif');
                    text.setAttribute('fill',              color);
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
            this.$nextTick(() => this.$refs.detailDialog.showModal());
        },

        closeDetail() {
            this.$refs.detailDialog.close();
            this.selectedTech = null;
        },

        statusLabel(tech) {
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
                personell: 'Personal',
            };
            return labels[type] ?? type;
        },
    };
}
