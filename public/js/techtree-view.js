// Techtree screen: Alpine component around the canvas renderer (public/js/techtree-canvas.js).
// The canvas draws the graph; this component owns the detail panel (desktop sidebar / phone bottom
// sheet), the AP investment and the status refresh after an order.
function techtreeView(config) {
    // Kept outside Alpine's reactive data: the renderer is a plain object with its own state.
    let tree = null;

    const CAT = { building: 'b', research: 'k', ship: 's', personell: 'a' };
    const HINT_TYPE = { 2: 'personell', 4: 'research', 5: 'building' };

    return {
        phases: config.phases,
        edges: config.edges ?? [],
        colonyViewUrl: config.colonyViewUrl ?? '/colony/view',
        i18n: config.i18n ?? {},
        hintRank: config.hintRank ?? 0,
        selectedTech: null,
        sheetMode: false,

        init() {
            this.checkBreakpoint();
            this.fit();
            this._onResize = () => {
                this.checkBreakpoint();
                this.fit();
            };
            window.addEventListener('resize', this._onResize);
            // The header grows and shrinks (hint bar, encounter notice): keep the stage flush below it.
            const header = document.querySelector('.colony-header');
            if (header && window.ResizeObserver) new ResizeObserver(() => this.fit()).observe(header);

            this.flatItems().forEach((it) => {
                it.pulse = this.hintRank > 0 && it.type === HINT_TYPE[this.hintRank];
            });

            this.$nextTick(() => {
                tree = window.TechtreeCanvas.create({
                    root: this.$refs.page,
                    stage: this.$refs.stage,
                    canvas: this.$refs.canvas,
                    dots: this.$refs.dots,
                    items: this.flatItems().map((it) => ({
                        key: this.keyOf(it),
                        cat: CAT[it.type],
                        band: it.band,
                        col: it.grid_col,
                        row: it.grid_row,
                        ref: it,
                    })),
                    edges: this.edges,
                    bandLabels: this.bandLabels(),
                    labels: { columnN: this.i18n.columnN },
                    describe: (item) => ({
                        label: item.ref.name,
                        sub: this.statusLabel(item.ref),
                        tag: item.ref.req_cc ? (this.i18n.ccTag ?? 'CC :level').replace(':level', item.ref.req_cc) : '',
                        status: item.ref.status,
                        pulse: item.ref.pulse,
                    }),
                    onSelect: (key) => this.selectByKey(key),
                });
            });
        },

        checkBreakpoint() {
            this.sheetMode = window.innerWidth < 600;
        },

        // Size the stage to the viewport below the sticky header; the side panel starts at the same line.
        fit() {
            const page = this.$refs.page;
            const stage = this.$refs.stage;
            if (!page || !stage) return;
            const top = stage.getBoundingClientRect().top;
            page.style.setProperty('--tt-top', `${Math.max(0, top)}px`);
            stage.style.height = `${Math.max(320, Math.round(window.innerHeight - Math.max(0, top)))}px`;
        },

        flatItems() {
            return Object.keys(this.phases)
                .map(Number)
                .sort((a, b) => a - b)
                .flatMap((n) => this.phases[n].items.map((it) => Object.assign(it, { band: n })));
        },

        keyOf(tech) {
            return `${tech.type}-${tech.id}`;
        },

        bandLabels() {
            const labels = {};
            Object.keys(this.phases).forEach((n) => {
                labels[n] = Number(n) === 0 ? this.i18n.bandStart : (this.i18n.bandCc ?? '').replace(':level', n);
            });
            return labels;
        },

        // A node was tapped/clicked on the canvas (null = background).
        selectByKey(key) {
            if (!key) {
                this.closeDetail(false);
                return;
            }
            const tech = this.flatItems().find((it) => this.keyOf(it) === key);
            if (tech) this.openDetail(tech, false);
        },

        // Fallback for building types without artwork under public/img/buildings/
        // (e.g. uplinkStation) — used by the shared partials/building-detail.blade.php
        // <img x-on:error> handler, same placeholder SVG as advisors.js/colony-hexgrid.js.
        buildingPlaceholderSrc() {
            return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='44' height='44'%3E%3Crect width='44' height='44' rx='6' fill='%23e0e0e8'/%3E%3C/svg%3E";
        },

        zoomIn() {
            tree?.zoom(1.3);
        },
        zoomOut() {
            tree?.zoom(1 / 1.3);
        },
        viewStart() {
            tree?.start();
        },
        viewOverview() {
            tree?.overview();
        },

        // fromCanvas=false: the canvas already knows (tap on a node); true: opened from outside (list).
        openDetail(tech, syncCanvas = true) {
            this.selectedTech = tech;
            const key = this.keyOf(tech);
            if (syncCanvas) tree?.select(key);
            // Let the panel render, then pan the node into the part of the stage the panel leaves free.
            this.$nextTick(() => {
                const panel = document.querySelector('.tech-panel');
                tree?.resize();
                tree?.focusNode(key, { bottom: this.sheetMode && panel ? panel.offsetHeight : 0 });
            });
        },

        closeDetail(syncCanvas = true) {
            this.selectedTech = null;
            if (syncCanvas) tree?.clearSelection();
            this.$nextTick(() => tree?.resize());
        },

        statusLabel(tech) {
            const t = (key, fallback) => this.i18n[key] ?? fallback;
            const gate = {
                available: t('statusAvailable', 'Verfügbar'),
                locked: t('statusLocked', 'Gesperrt'),
            };
            if (tech.type === 'personell') {
                return (
                    {
                        built: t('advisorHired', 'Eingestellt'),
                        available: t('advisorAvailable', 'Verfügbar'),
                        locked: t('advisorLocked', 'Gesperrt'),
                    }[tech.status] ?? tech.status
                );
            }
            if (tech.type === 'ship') {
                if (tech.status === 'built') return tech.level + (tech.hangar_cap ? ' / ' + tech.hangar_cap : '');
                return gate[tech.status] ?? tech.status;
            }
            if (tech.is_instanced) {
                if (tech.status !== 'built') return gate[tech.status] ?? tech.status;
                if (tech.max_level === 1)
                    return tech.instances.length > 0
                        ? t('advisorPlaced', 'Platziert')
                        : t('advisorNotPlaced', 'Nicht gebaut');
                return this.instanceCountLabel(tech);
            }
            if (tech.status === 'built')
                return tech.level > 0
                    ? `Lv ${tech.level}${tech.max_level ? '/' + tech.max_level : ''}`
                    : t('statusBuilt', 'Gebaut');
            return gate[tech.status] ?? tech.status;
        },

        // Placed instances vs. the instance cap (max_instances, GDD §4c) — only the
        // count when the building has no cap (Hangar).
        instanceCountLabel(tech) {
            const placed = tech.instances?.length ?? 0;
            return tech.max_instances ? `${placed} / ${tech.max_instances}` : String(placed);
        },

        // Another instance is possible — below the instance cap (always when
        // uncapped), or for a non-instanced building while none is placed.
        hasInstanceRoom(tech) {
            const placed = tech.instances?.length ?? 0;
            if (!tech.is_instanced) return placed === 0;
            return tech.max_instances === null || tech.max_instances === undefined || placed < tech.max_instances;
        },

        // "In der Kolonie errichten" (GDD techtree §11.4): only for buildings the
        // colony build menu offers — "?build=ID" for anything else opens nothing.
        canBuildMore(tech) {
            return tech.menu_buildable !== false && this.hasInstanceRoom(tech);
        },

        // Replaces the build link for buildings obtained outside the build menu
        // (Harvester, GDD §4c) while another instance is still possible.
        acquireHint(tech) {
            if (tech.menu_buildable !== false || !tech.acquire_hint) return '';
            return this.hasInstanceRoom(tech) ? tech.acquire_hint : '';
        },

        instanceLevelLabel(inst) {
            if (inst.level === 0) return this.i18n.instanceConstruction ?? '';
            if (inst.max_level === null) return this.instanceText('instanceLevelUncapped', { level: inst.level });
            return this.instanceText('instanceLevel', { level: inst.level, max: inst.max_level });
        },

        instanceConditionLabel(inst) {
            return this.instanceText('instanceCondition', { sp: inst.status_points, max: inst.max_status_points });
        },

        // Deep link into the colony view with exactly this instance selected.
        tileLink(tech, inst) {
            const query = new URLSearchParams({ building: tech.id, instance: inst.instance_id });
            return `${this.colonyViewUrl}?${query}`;
        },

        buildLink(tech) {
            return `${this.colonyViewUrl}?${new URLSearchParams({ build: tech.id })}`;
        },

        instanceText(key, values = {}) {
            let text = this.i18n[key] ?? key;
            for (const [name, value] of Object.entries(values)) text = text.replaceAll(`:${name}`, value);
            return text;
        },

        typeLabel(type) {
            const labels = {
                building: 'Gebäude',
                research: 'Forschung',
                ship: 'Schiff',
                personell: 'Berater',
            };
            return labels[type] ?? type;
        },

        // The order endpoint answers 422 with a machine-readable {error, message} body
        // (not just a bare failed status), so the JSON is parsed either way — callers
        // read `success`/`error` off the returned object rather than the HTTP status.
        async sendOrder(type, techId, body) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const res = await fetch(`/techtree/${type}/${techId}/order`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(body),
            });

            return res.json();
        },

        // Invests AP and, once the threshold is reached, the server auto-triggers the
        // actual levelup in the same request (TechtreeController::order()) — updates
        // the tech in place and syncs the AP chip, no page reload (same UX as the
        // colony hex view's building invest).
        async investAp(tech, type, amount) {
            const json = await this.sendOrder(type, tech.id, { order: 'add', ap: amount });
            if (!json.success) {
                this.showToast(json.message ?? 'Investition fehlgeschlagen.', 'error');
                return;
            }

            if (json.tech) Object.assign(tech, json.tech);
            if (json.ap_available !== undefined) {
                tech.ap_available = json.ap_available;
                this.syncApChip(json.ap_available);
            }

            if (json.phases_update) this.applyPhasesUpdate(json.phases_update);
            tree?.refresh();

            if (json.leveled_up) {
                this.showToast(`${tech.name ?? this.typeLabel(type)}: Level ${tech.level} erreicht!`, 'info');
            } else if (json.levelup_blocked_message) {
                this.showToast(json.levelup_blocked_message, 'error');
            }
        },

        // Owner-Playtest-Fund 2026-09-04: a levelup can flip a DIFFERENT (dependent)
        // tech's locked/available status and the edges' 'met' flags —
        // investAp() above only patched the one invested tech, so the rest of the
        // graph stayed stale until a full page reload. TechtreeController::order()
        // now re-derives the same gate logic server-side and ships it as
        // 'phases_update' (see phasesUpdatePayload() there); mutate the existing
        // item objects in place (not a wholesale replace) so Alpine reactivity picks
        // it up and the open detail panel (selectedTech, same object reference)
        // updates too.
        applyPhasesUpdate(update) {
            for (const [phaseNum, phaseUpdate] of Object.entries(update.phases ?? {})) {
                const phase = this.phases[phaseNum];
                if (!phase || !phaseUpdate.items) continue;

                for (const itemUpdate of phaseUpdate.items) {
                    const item = phase.items.find((i) => i.id === itemUpdate.id && i.type === itemUpdate.type);
                    if (item) Object.assign(item, itemUpdate);
                }
            }

            if (update.edges) this.edges = update.edges;
            tree?.refresh(null, update.edges);
        },

        // The AP chip lives in the resource bar (layout header), outside this Alpine
        // component — sync it via DOM after every AJAX action, mirroring
        // colony-hexgrid.js::syncResbarAp(). One shared colony pool (GDD §13.1) —
        // no more per-domain chip, so `type` no longer selects a chip ID.
        syncApChip(value) {
            const el = document.querySelector('#resbar-ap .res-amount');
            if (el) el.textContent = value;
        },

        toastMessage: '',
        toastType: 'error',
        toastVisible: false,

        showToast(message, type = 'error') {
            if (this._toastTimer) clearTimeout(this._toastTimer);
            this.toastMessage = message;
            this.toastType = type;
            this.toastVisible = true;
            this._toastTimer = setTimeout(() => {
                this.toastVisible = false;
            }, 3500);
        },
    };
}
