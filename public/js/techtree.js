/**
 * techtree.js — Legacy Bootstrap-modal tech detail handler.
 *
 * NOTE: As of the Alpine.js techtree redesign (techtree-view.js), this file is no
 * longer loaded by any view. The new techtree screen renders details in an Alpine
 * side-panel and uses fetch() directly. This file is kept as a clean, jQuery-free
 * reference implementation in case the Bootstrap-modal flow is ever re-enabled.
 *
 * Requires: Bootstrap 5 bundle (bootstrap.Tooltip, bootstrap Modal events)
 * No jQuery. No LeaderLine (was only used by the old index grid — removed).
 */

document.addEventListener('DOMContentLoaded', () => {

    const COLORS = {
        building:  '#99d',
        research:  '#9d9',
        ship:      '#cc7',
        personell: '#aaa',
    };

    // ── CSRF helper ──────────────────────────────────────────────────────────

    /**
     * Return the CSRF token from the page meta tag.
     * @returns {string}
     */
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    // ── techtree namespace ───────────────────────────────────────────────────

    const techtree = {

        // LeaderLine instances keyed by target type, for toggle support
        lines: { building: [], research: [], ship: [], personell: [] },

        init() {
            // Move each .techdata span's content into the matching grid cell
            document.querySelectorAll('.techdata').forEach(el => {
                const key  = el.id.replace('techsource', 'grid');
                const cell = document.getElementById(key);
                if (cell) {
                    cell.innerHTML = el.innerHTML;
                }
            });

            techtree.draw_requirements();

            // LeaderLine repositions automatically on scroll; reposition on resize
            window.addEventListener('resize', () => {
                ['building', 'research', 'ship', 'personell'].forEach(type => {
                    techtree.lines[type].forEach(l => { l.position(); });
                });
            });
        },

        draw_requirements() {
            // Remove any existing lines first
            ['building', 'research', 'ship', 'personell'].forEach(type => {
                techtree.lines[type].forEach(l => { try { l.remove(); } catch (e) { /* ignore */ } });
                techtree.lines[type] = [];
            });

            document.querySelectorAll('.requirementsdata').forEach(el => {
                let fromClass, toClass;
                if      (el.classList.contains('building'))  { fromClass = 'building';  toClass = 'building'; }
                else if (el.classList.contains('research'))  { fromClass = 'building';  toClass = 'research'; }
                else if (el.classList.contains('ship'))      { fromClass = 'research';  toClass = 'ship'; }
                else                                         { fromClass = 'building';  toClass = 'personell'; }

                const parts        = el.textContent.trim().split('-');
                const techId       = parseInt(parts[0], 10);
                const reqTechId    = parseInt(parts[1], 10);
                const reqLevel     = parseInt(parts[2], 10);
                const currentLevel = parseInt(parts[3], 10);
                const fulfilled    = currentLevel >= reqLevel;

                const srcEl  = document.getElementById(fromClass + '-' + reqTechId);
                const trgtEl = document.getElementById(toClass   + '-' + techId);
                if (!srcEl || !trgtEl) { return; }

                const options = {
                    color:               COLORS[toClass],
                    size:                fulfilled ? 2.5 : 1.5,
                    path:                'fluid',
                    startSocket:         'bottom',
                    endSocket:           'top',
                    endPlug:             'arrow3',
                    endPlugSize:         1.5,
                    startSocketGravity:  40,
                    endSocketGravity:    40,
                };

                if (!fulfilled) {
                    options.dash  = { len: 6, gap: 4 };
                    options.color = COLORS[toClass].replace(')', ', 0.55)').replace('rgb', 'rgba');
                }

                if (reqLevel > 1) {
                    options.middleLabel = LeaderLine.captionLabel('Lv' + reqLevel, {
                        color:    '#999',
                        fontSize: '10px',
                    });
                }

                try {
                    const line = new LeaderLine(srcEl, trgtEl, options);
                    techtree.lines[toClass].push(line);
                } catch (e) {
                    console.warn('LeaderLine error:', e);
                }
            });
        },

        /** Reset hover-highlight colors on AP-spend bar buttons. */
        reset_colors_for_bar_buttons() {
            document.querySelectorAll('.techModal .progress a.progress-bar i').forEach(icon => {
                const btn = icon.parentElement;
                btn.style.backgroundImage = 'none';
                btn.style.backgroundColor = '#eee';
            });
        },

        refresh_resource_bar() {
            const bar = document.getElementById('resource-bar');
            if (!bar) return;

            fetch('/resources/json/reloadresourcebar', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken(),
                },
            })
                .then(r => r.text())
                .then(html => {
                    bar.innerHTML = html;
                    console.log('resource bar loaded');
                })
                .catch(err => console.warn('resource bar reload failed:', err));
        },

        /**
         * Load modal content from the server via fetch and inject it into the modal.
         *
         * @param {Element}       modalEl  — the .techModal element
         * @param {string}        url      — endpoint to load
         * @param {Function|null} done     — optional callback after successful load
         */
        loadModalContent(modalEl, url, done = null) {
            const contentEl = modalEl.querySelector('.modal-content');
            if (contentEl) {
                contentEl.innerHTML =
                    '<div class="modal-body text-center py-4">' +
                    '<div class="spinner-border text-secondary" role="status">' +
                    '<span class="visually-hidden">Laden...</span></div></div>';
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken(),
                },
            })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(html => {
                    const dialogEl = modalEl.querySelector('.modal-dialog');
                    if (dialogEl) {
                        // Replace the entire .modal-dialog with the server-rendered partial
                        const tmp = document.createElement('div');
                        tmp.innerHTML = html;
                        dialogEl.replaceWith(tmp.firstElementChild);
                    }

                    // Re-initialise Bootstrap tooltips inside the freshly loaded content
                    modalEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                        new bootstrap.Tooltip(el);
                    });

                    if (typeof done === 'function') { done(); }
                })
                .catch(() => {
                    const failContentEl = modalEl.querySelector('.modal-content');
                    if (failContentEl) {
                        failContentEl.innerHTML =
                            '<div class="modal-header">' +
                            '<h5 class="modal-title">Fehler</h5>' +
                            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                            '</div>' +
                            '<div class="modal-body">Inhalt konnte nicht geladen werden.</div>';
                    }
                });
        },
    };

    // ── Bootstrap modal events (no jQuery) ──────────────────────────────────

    // Bootstrap 5 dispatches modal events on the modal element itself, but when
    // using event delegation we listen on document and filter by the selector.

    /**
     * Load modal content from the triggering tech button's href when the modal opens.
     */
    document.addEventListener('show.bs.modal', e => {
        if (!e.target.matches('.techModal')) return;
        const trigger = e.relatedTarget;
        if (!trigger) return;
        const url = trigger.getAttribute('href');
        if (!url || url === '#') return;
        techtree.loadModalContent(e.target, url);
    });

    /**
     * After the modal has finished opening: reset bar colours and refresh resource bar.
     */
    document.addEventListener('shown.bs.modal', e => {
        if (!e.target.matches('.techModal')) return;
        techtree.reset_colors_for_bar_buttons();
        techtree.refresh_resource_bar();
    });

    // ── Action buttons inside modals ─────────────────────────────────────────
    // Button id format: "{type}-{techId}|{order}[-{ap}]"
    // Examples: "building-25|levelup"  "building-25|add-3"  "building-25|repair-2"

    document.addEventListener('click', e => {
        const btn = e.target.closest('.techModal [id*="|"]');
        if (!btn) return;
        e.preventDefault();

        const raw        = btn.id;
        const halves     = raw.split('|');
        const typeid     = halves[0].split('-');
        const type       = typeid[0];
        const techId     = typeid[1];
        const orderparts = halves[1].split('-');
        const order      = orderparts[0];
        const ap         = orderparts[1] ?? '';

        const url     = '/techtree/' + type + '/' + techId + '/' + order + (ap ? '/' + ap : '');
        const modalEl = btn.closest('.techModal');

        techtree.loadModalContent(modalEl, url, () => {
            techtree.refresh_resource_bar();
        });
    });

    // ── Hover preview: AP-spend bar ──────────────────────────────────────────

    document.addEventListener('mouseover', e => {
        const bar = e.target.closest('.techModal .progress.ap_spend a.progress-bar');
        if (!bar) return;

        // All previous siblings: switch bg-info → bg-success
        let sib = bar.previousElementSibling;
        while (sib) {
            if (sib.matches('a.bg-info')) {
                sib.classList.replace('bg-info', 'bg-success');
            }
            sib = sib.previousElementSibling;
        }

        bar.classList.replace('bg-info', 'bg-success');

        // All following siblings: switch bg-success → bg-info
        sib = bar.nextElementSibling;
        while (sib) {
            if (sib.matches('a.bg-success')) {
                sib.classList.replace('bg-success', 'bg-info');
            }
            sib = sib.nextElementSibling;
        }

        techtree.reset_colors_for_bar_buttons();
    });

    document.addEventListener('mouseout', e => {
        const progress = e.target.closest('.techModal .progress.ap_spend');
        if (!progress) return;
        // Only fire when leaving the .progress container itself (not bubbling from children)
        if (progress.contains(e.relatedTarget)) return;

        progress.querySelectorAll('a.progress-bar.bg-success').forEach(el => {
            el.classList.replace('bg-success', 'bg-info');
        });
        techtree.reset_colors_for_bar_buttons();
    });

    // ── Hover preview: status-points / repair bar ────────────────────────────

    document.addEventListener('mouseover', e => {
        const bar = e.target.closest('.techModal .progress.status_points a.progress-bar');
        if (!bar) return;

        if (bar.classList.contains('bg-danger')) {
            // All previous bg-danger siblings → bg-warning
            let sib = bar.previousElementSibling;
            while (sib) {
                if (sib.matches('a.bg-danger')) {
                    sib.classList.replace('bg-danger', 'bg-warning');
                }
                sib = sib.previousElementSibling;
            }
            bar.classList.replace('bg-danger', 'bg-warning');

            // All following bg-warning siblings → bg-danger
            sib = bar.nextElementSibling;
            while (sib) {
                if (sib.matches('a.bg-warning') && !sib.matches('span')) {
                    sib.classList.replace('bg-warning', 'bg-danger');
                }
                sib = sib.nextElementSibling;
            }
        } else if (bar.classList.contains('bg-warning')) {
            // Following bg-warning siblings → bg-danger
            let sib = bar.nextElementSibling;
            while (sib) {
                if (sib.matches('a.bg-warning')) {
                    sib.classList.replace('bg-warning', 'bg-danger');
                }
                sib = sib.nextElementSibling;
            }
        }

        techtree.reset_colors_for_bar_buttons();
    });

    document.addEventListener('mouseout', e => {
        const progress = e.target.closest('.techModal .progress.status_points');
        if (!progress) return;
        // Only fire when leaving the .progress container itself
        if (progress.contains(e.relatedTarget)) return;

        progress.querySelectorAll('a.progress-bar.bg-warning').forEach(el => {
            el.classList.replace('bg-warning', 'bg-danger');
        });
        techtree.reset_colors_for_bar_buttons();
    });

    // ── Bootstrap modal events: auto-init for pre-rendered modals ────────────

    // If the page contains .techModal elements with pre-rendered grids, run init.
    if (document.querySelector('.techdata')) {
        techtree.init();
    }

});
