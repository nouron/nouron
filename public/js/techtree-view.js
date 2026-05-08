function techtreeView(config) {
    return {
        categories:   config.categories,
        lines:        config.lines,
        visible:      { building: true, research: true, ship: true, personell: true },
        selectedTech: null,

        init() {
            this.$nextTick(() => this.drawLines());
            window.addEventListener('resize', () => this.drawLines());
        },

        toggle(type) {
            this.visible[type] = !this.visible[type];
            // Update card visibility in the DOM then redraw
            this.$nextTick(() => {
                this.applyVisibility();
                this.drawLines();
            });
        },

        applyVisibility() {
            for (const type of ['building', 'research', 'ship', 'personell']) {
                const cards = document.querySelectorAll(`.tech-${type}`);
                cards.forEach(c => {
                    if (this.visible[type]) c.classList.remove('type-hidden');
                    else c.classList.add('type-hidden');
                });
            }
        },

        drawLines() {
            const svg     = this.$refs.svg;
            const wrapper = this.$refs.wrapper;
            if (!svg || !wrapper) return;

            const wRect = wrapper.getBoundingClientRect();

            // Set SVG to cover full scrollable content area
            const grid = wrapper.querySelector('.techtree-grid');
            const gRect = grid ? grid.getBoundingClientRect() : wRect;
            const svgW = Math.max(wRect.width,  gRect.right  - wRect.left + wrapper.scrollLeft);
            const svgH = Math.max(wRect.height, gRect.bottom - wRect.top  + wrapper.scrollTop);

            svg.setAttribute('width',  svgW);
            svg.setAttribute('height', svgH);
            svg.innerHTML = `
                <defs>
                    <marker id="arrow-met"   markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto">
                        <path d="M0,0 L0,6 L6,3 z" fill="#27ae60"/>
                    </marker>
                    <marker id="arrow-unmet" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto">
                        <path d="M0,0 L0,6 L6,3 z" fill="#bbb"/>
                    </marker>
                </defs>`;

            const scrollLeft = wrapper.scrollLeft;
            const scrollTop  = wrapper.scrollTop;

            for (const line of this.lines) {
                const fromEl = document.getElementById(line.from);
                const toEl   = document.getElementById(line.to);
                if (!fromEl || !toEl) continue;
                if (fromEl.classList.contains('type-hidden') || toEl.classList.contains('type-hidden')) continue;

                const fRect = fromEl.getBoundingClientRect();
                const tRect = toEl.getBoundingClientRect();

                // Coordinates relative to wrapper, accounting for scroll
                const x1 = fRect.left + fRect.width  / 2 - wRect.left + scrollLeft;
                const y1 = fRect.bottom - wRect.top + scrollTop;
                const x2 = tRect.left + tRect.width  / 2 - wRect.left + scrollLeft;
                const y2 = tRect.top  - wRect.top  + scrollTop;

                // Cubic bezier: control points halfway between y1 and y2
                const cy = (y1 + y2) / 2;
                const color  = line.met ? '#27ae60' : '#bbb';
                const marker = line.met ? 'url(#arrow-met)' : 'url(#arrow-unmet)';

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', `M ${x1} ${y1} C ${x1} ${cy} ${x2} ${cy} ${x2} ${y2}`);
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '1.5');
                path.setAttribute('fill', 'none');
                path.setAttribute('marker-end', marker);
                if (!line.met) path.setAttribute('stroke-dasharray', '5 3');

                svg.appendChild(path);
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
            if (tech.status === 'built') {
                return tech.level > 0 ? `Lv ${tech.level}` : 'Gebaut';
            }
            if (tech.status === 'available') return 'Verfügbar';
            return 'Gesperrt';
        },

        typeLabel(type) {
            return { building: 'Gebäude', research: 'Forschung', ship: 'Schiff', personell: 'Personal' }[type] ?? type;
        },
    };
}
