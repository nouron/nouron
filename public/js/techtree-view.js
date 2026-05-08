function techtreeView(config) {
    return {
        categories:   config.categories,
        lines:        config.lines ?? [],
        visible:      { building: true, research: true, ship: true, personell: true },
        selectedTech: null,

        init() {
            this.$nextTick(() => this.drawAllLines());
            window.addEventListener('resize', () => this.drawAllLines());
        },

        toggle(type) {
            this.visible[type] = !this.visible[type];
            this.$nextTick(() => this.drawAllLines());
        },

        /**
         * Draw orthogonal L-shaped dependency arrows across the full sections wrapper.
         * Arrows connect any tech card to its required building, across section boundaries.
         * Coordinates are relative to the sectionsWrapper container.
         */
        drawAllLines() {
            const svgEl     = this.$refs.globalSvg;
            const wrapperEl = this.$refs.sectionsWrapper;
            if (!svgEl || !wrapperEl) return;

            while (svgEl.firstChild) svgEl.removeChild(svgEl.firstChild);

            const wRect = wrapperEl.getBoundingClientRect();

            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            const makeMarker = (id, color) => {
                const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
                marker.setAttribute('id', id);
                marker.setAttribute('markerWidth', '8');
                marker.setAttribute('markerHeight', '8');
                marker.setAttribute('refX', '7');
                marker.setAttribute('refY', '3');
                marker.setAttribute('orient', 'auto');
                const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                poly.setAttribute('points', '0 0, 8 3, 0 6');
                poly.setAttribute('fill', color);
                marker.appendChild(poly);
                return marker;
            };
            defs.appendChild(makeMarker('arr-met',   '#27ae60'));
            defs.appendChild(makeMarker('arr-unmet', '#ccc'));
            svgEl.appendChild(defs);

            let drew = false;

            for (const line of this.lines) {
                const fromEl = document.getElementById(line.from);
                const toEl   = document.getElementById(line.to);
                if (!fromEl || !toEl) continue;
                // Skip if either card is inside a hidden section (display:none)
                if (!fromEl.offsetParent || !toEl.offsetParent) continue;

                const fR = fromEl.getBoundingClientRect();
                const tR = toEl.getBoundingClientRect();

                const x1 = fR.left + fR.width  / 2 - wRect.left;
                const y1 = fR.bottom               - wRect.top;
                const x2 = tR.left + tR.width  / 2 - wRect.left;
                const y2 = tR.top                  - wRect.top;
                const midY = (y1 + y2) / 2;

                const color = line.met ? '#27ae60' : '#ccc';
                const mid   = line.met ? 'url(#arr-met)' : 'url(#arr-unmet)';

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', `M ${x1} ${y1} L ${x1} ${midY} L ${x2} ${midY} L ${x2} ${y2}`);
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '2');
                path.setAttribute('marker-end', mid);
                if (!line.met) path.setAttribute('stroke-dasharray', '5 3');
                svgEl.appendChild(path);
                drew = true;
            }

            if (drew) {
                const h = wrapperEl.scrollHeight;
                const w = wrapperEl.scrollWidth;
                svgEl.setAttribute('width',   String(w));
                svgEl.setAttribute('height',  String(h));
                svgEl.setAttribute('viewBox', `0 0 ${w} ${h}`);
            }
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
