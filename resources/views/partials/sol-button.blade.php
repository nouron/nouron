{{--
    Sol-Button partial — Alpine.js component.
    Checks remaining AP before ending the Sol, then plays the animated Sol-Report.
    Keeps the trigger button + confirm dialog; the former loading overlay is now a
    short spinner that hands off to the report (or auto-dismisses on skip_pref).
--}}
<div x-data="solButton({
    i18n: {
        title: @js(__("colony.sol_report_title")),
        continue: @js(__("colony.sol_report_continue")),
        skipHint: @js(__("colony.sol_report_skip_hint")),
        skipSetting: @js(__("colony.sol_report_skip_setting")),
        finaleWinCta: @js(__("colony.sol_report_finale_win_cta")),
        finaleLoseCta: @js(__("colony.sol_report_finale_lose_cta")),
        computing: @js(__("colony.next_sol_button")),
    },
    routes: {
        remainingAp: @js(route("sol.remaining-ap")),
        next: @js(route("sol.next")),
        reportSkip: @js(route("sol.report-skip")),
        colony: @js(route("colony.view")),
    },
    csrf: @js(csrf_token()),
})" style="display: inline-flex; align-items: center">
    {{-- Sol form (hidden, submitted via fetch) --}}
    <form id="sol-next-form" method="POST" action="{{ route("sol.next") }}" style="display: none">
        @csrf
    </form>

    {{-- Trigger button --}}
    <button type="button" class="btn-sol" @click="handleClick">
        <i class="bi bi-skip-forward-fill"></i> {{ __("colony.next_sol_button") }}
    </button>

    {{-- Confirm dialog overlay --}}
    <div x-show="confirmOpen" x-cloak class="sol-overlay">
        <div class="sol-dialog">
            <h3 class="sol-dialog__title">Sol beenden?</h3>
            <p class="sol-dialog__body">
                Du hast noch nicht alle AP ausgegeben:
                <strong x-text="apSummary"></strong>.
            </p>
            <p class="sol-dialog__body">Ungenutzte AP verfallen am Sol-Ende.</p>
            <div class="sol-dialog__actions">
                <button type="button" class="sol-btn sol-btn--ghost"
                    @click="confirmOpen = false">Weiterspielen</button>
                <button type="button" class="sol-btn sol-btn--primary" @click="submitSol">Sol trotzdem beenden</button>
            </div>
        </div>
    </div>

    {{-- Short loading spinner (bridges the POST; hidden once the report opens) --}}
    <div x-show="loading" x-cloak class="sol-overlay sol-overlay--loading">
        <div class="sol-loading">
            <div class="sol-spinner"></div>
            <p class="sol-loading__text">Sol wird berechnet …</p>
            <p class="sol-loading__sub">Ereignisse, Produktion, Verfall</p>
        </div>
    </div>

    {{-- Sol-Report overlay --}}
    <div x-show="reportOpen" x-cloak class="sol-overlay sol-overlay--report" @click="skipToEnd()">
        {{-- Finale: full-screen win/lose --}}
        <template x-if="report && report.finale">
            <div class="sol-report sol-report--finale" :class="'sol-report--finale-' + report.finale.outcome"
                @click.stop>
                <div class="sol-finale__glyph">
                    <i class="bi"
                        :class="report.finale.outcome === 'win' ? 'bi-trophy-fill' : 'bi-x-octagon-fill'"></i>
                </div>
                <h2 class="sol-finale__title" x-text="report.finale.title"></h2>
                <p class="sol-finale__body" x-text="report.finale.body"></p>
                <button type="button" class="sol-btn sol-btn--primary sol-finale__cta" @click.stop="goFinale()">
                    <span x-text="report.finale.outcome === 'win' ? i18n.finaleWinCta : i18n.finaleLoseCta"></span>
                </button>
            </div>
        </template>

        {{-- Standard group report --}}
        <template x-if="report && !report.finale">
            <div class="sol-report" @click.stop="skipToEnd()">
                <header class="sol-report__header">
                    <h2 class="sol-report__title" x-text="headerTitle"></h2>
                </header>

                <div class="sol-report__groups">
                    <template x-for="(group, gi) in report.groups" :key="group.key">
                        <article class="sol-report__group" x-show="gi <= visibleGroup" x-cloak>
                            <h3 class="sol-report__group-title">
                                <i class="bi" :class="'bi-' + group.icon"></i>
                                <span x-text="group.title"></span>
                            </h3>
                            <ul class="sol-report__lines">
                                <template x-for="(line, li) in group.lines" :key="li">
                                    <li class="sol-report__line"
                                        :class="[
                                            'sol-report__line--' + (line.tone || 'neutral'),
                                            line.beat ? 'sol-report__line--beat' : '',
                                            shakeKey === group.key + ':' + li ? 'sol-report__line--shake' : '',
                                        ]">
                                        <span class="sol-report__label" x-text="line.label"></span>
                                        <span class="sol-report__value">
                                            <template x-if="line.from !== undefined && line.to !== undefined">
                                                <span class="sol-report__counter"
                                                    x-text="counterValue(group.key, li)"></span>
                                            </template>
                                            <span class="sol-report__detail" x-text="line.detail"></span>
                                        </span>
                                    </li>
                                </template>
                            </ul>
                        </article>
                    </template>
                </div>

                <footer class="sol-report__footer">
                    <p class="sol-report__skip-hint" x-show="!finished" x-cloak x-text="i18n.skipHint"></p>
                    <label class="sol-report__skip-setting">
                        <input type="checkbox" x-model="skipPref" @change="persistSkipPref()" />
                        <span x-text="i18n.skipSetting"></span>
                    </label>
                    <button type="button" class="sol-btn sol-btn--primary sol-report__continue" x-show="finished"
                        x-cloak @click.stop="goContinue()" x-text="continueLabel"></button>
                </footer>
            </div>
        </template>
    </div>
</div>

<script>
    function solButton(config) {
        return {
            confirmOpen: false,
            loading: false,
            reportOpen: false,
            apData: null,

            i18n: config.i18n,
            routes: config.routes,
            csrf: config.csrf,

            report: null,
            skipPref: false,
            visibleGroup: -1,
            finished: false,
            counters: {}, // key `${groupKey}:${lineIndex}` -> current displayed value
            shakeKey: null,
            _timers: [],
            _reduceMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,

            get hasAp() {
                return this.apData && this.apData.total > 0;
            },

            get apSummary() {
                if (!this.apData) return '';
                const parts = [];
                if (this.apData.construction > 0) parts.push(this.apData.construction + ' Bau-AP');
                if (this.apData.research > 0) parts.push(this.apData.research + ' Forschungs-AP');
                if (this.apData.navigation > 0) parts.push(this.apData.navigation + ' Nav-AP');
                return parts.join(', ');
            },

            get headerTitle() {
                if (!this.report) return '';
                return this.i18n.title.replace(':sol', this.report.completed_sol);
            },

            get continueLabel() {
                if (!this.report) return '';
                return this.i18n.continue.replace(':sol', this.report.next_sol);
            },

            async handleClick() {
                const resp = await fetch(this.routes.remainingAp, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });
                this.apData = await resp.json();

                if (this.hasAp) {
                    this.confirmOpen = true;
                } else {
                    await this.submitSol();
                }
            },

            async submitSol() {
                this.confirmOpen = false;
                this.loading = true;

                const form = document.getElementById('sol-next-form');
                const formData = new FormData(form);

                try {
                    const resp = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    });
                    const report = await resp.json();
                    this.report = report;
                    this.skipPref = !!report.skip_pref;

                    // Auto-dismiss: user opted out and this is not a forced beat.
                    if (report.skip_pref && !report.force_show && !report.finale) {
                        window.location.href = this.routes.colony;
                        return;
                    }

                    this.loading = false;
                    this.reportOpen = true;

                    if (report.finale) {
                        // Finale renders immediately; no group animation.
                        this.finished = true;
                        return;
                    }

                    this.$nextTick(() => this.playReport());
                } catch (e) {
                    this.loading = false;
                    alert('Fehler beim Beenden des Sol. Bitte Seite neu laden.');
                }
            },

            playReport() {
                const groups = this.report.groups || [];

                if (this._reduceMotion) {
                    // No motion: reveal everything, set counters to final, show continue.
                    this.finishNow();
                    return;
                }

                let delay = 0;
                const groupReveal = 250;
                const groupPause = 300;

                groups.forEach((group, gi) => {
                    this.schedule(() => {
                        this.visibleGroup = gi;
                    }, delay);
                    delay += groupReveal;

                    let groupExtra = 0;
                    group.lines.forEach((line, li) => {
                        const key = group.key + ':' + li;

                        if (line.from !== undefined && line.to !== undefined) {
                            this.schedule(() => this.animateCounter(key, line.from, line.to), delay +
                                groupExtra);
                            groupExtra += 200;
                        }

                        if (line.beat) {
                            // Extra hold so the moment lands.
                            groupExtra += 500;
                            if (line.tone === 'danger') {
                                this.schedule(() => this.triggerShake(key), delay + groupExtra - 350);
                            }
                        }
                    });

                    delay += groupExtra + groupPause;
                });

                this.schedule(() => {
                    this.finished = true;
                }, delay);
            },

            animateCounter(key, from, to) {
                const duration = 700;
                const start = performance.now();
                const tick = (now) => {
                    if (this.finished) {
                        this.counters[key] = to;
                        return;
                    }
                    const t = Math.min(1, (now - start) / duration);
                    // easeOutCubic
                    const eased = 1 - Math.pow(1 - t, 3);
                    this.counters[key] = Math.round(from + (to - from) * eased);
                    if (t < 1) requestAnimationFrame(tick);
                    else this.counters[key] = to;
                };
                requestAnimationFrame(tick);
            },

            counterValue(groupKey, li) {
                const key = groupKey + ':' + li;
                if (this.counters[key] !== undefined) return this.counters[key];
                const line = this.lineFor(groupKey, li);
                return line ? line.from : '';
            },

            lineFor(groupKey, li) {
                const group = (this.report.groups || []).find((g) => g.key === groupKey);
                return group ? group.lines[li] : null;
            },

            triggerShake(key) {
                this.shakeKey = key;
                this.schedule(() => {
                    if (this.shakeKey === key) this.shakeKey = null;
                }, 200);
            },

            skipToEnd() {
                if (this.finished) return;
                this.finishNow();
            },

            finishNow() {
                this.clearTimers();
                this.shakeKey = null;
                this.visibleGroup = (this.report.groups || []).length - 1;
                // Snap every counter to its final value.
                (this.report.groups || []).forEach((group) => {
                    group.lines.forEach((line, li) => {
                        if (line.from !== undefined && line.to !== undefined) {
                            this.counters[group.key + ':' + li] = line.to;
                        }
                    });
                });
                this.finished = true;
            },

            schedule(fn, delay) {
                this._timers.push(setTimeout(fn, delay));
            },

            clearTimers() {
                this._timers.forEach((t) => clearTimeout(t));
                this._timers = [];
            },

            async persistSkipPref() {
                try {
                    await fetch(this.routes.reportSkip, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                        },
                        body: JSON.stringify({
                            skip: this.skipPref
                        }),
                    });
                } catch (e) {
                    // Non-critical; preference simply won't persist this time.
                }
            },

            goContinue() {
                window.location.href = this.routes.colony;
            },

            goFinale() {
                if (this.report && this.report.result_url) {
                    window.location.href = this.report.result_url;
                } else {
                    window.location.href = this.routes.colony;
                }
            },
        };
    }
</script>
