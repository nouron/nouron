{{--
    Onboarding hint bar — shown in the shared layout header on every screen (not just
    colony.view), so the "Vorschlag" row looks the same everywhere. Self-contained Alpine
    component, independent of colonyHexView: starts from the server-rendered $activeHint
    (see AppServiceProvider) and stays in sync with colony.view's live AJAX updates via
    the `hint:sync` window event (dispatched from colony-hexgrid.js::setActiveHint). Other
    screens never change the hint mid-page, so the initial server value is all they need.
--}}
<script>
    window.__hintBarData = {
        initialHint: @json($activeHint ?? null),
        dismissRoute: @js(route("colony.hint.dismiss")),
    };
</script>

<div class="hint-bar-stack" x-data="hintBar(window.__hintBarData)">
    <div class="hint-bar hint-bar--done" x-show="completedHint" x-cloak>
        <span class="hint-bar__icon hint-bar__icon--done" aria-hidden="true">✓</span>
        <span class="hint-bar__text" x-text="completedHint?.text"></span>
    </div>
    <div class="hint-bar" x-show="activeHint" x-cloak x-ref="hintBar">
        <span class="hint-bar__icon" aria-hidden="true" title="{{ __("colony.hint_not_mandatory") }}">!</span>
        <span class="hint-bar__text" x-text="activeHint?.text"></span>
        <span class="hint-bar__badge"
            title="{{ __("colony.hint_not_mandatory") }}">{{ __("colony.hint_suggestion_label") }}</span>
        <a class="hint-bar__link" :href="activeHint?.target_url">→</a>
        <button class="hint-bar__dismiss" aria-label="Dismiss hint" @click="dismiss()">×</button>
    </div>
</div>

<script>
    function hintBar(config) {
        return {
            activeHint: config.initialHint,
            completedHint: null,
            dismissRoute: config.dismissRoute,
            _completeTimer: null,

            init() {
                window.addEventListener('hint:sync', (e) => this.setActiveHint(e.detail));
            },

            setActiveHint(newHint) {
                const newKey = newHint?.key ?? null;
                const oldHint = this.activeHint;
                if (newKey === (oldHint?.key ?? null)) {
                    this.activeHint = newHint;
                    return;
                }
                if (oldHint) {
                    this.completedHint = oldHint;
                    clearTimeout(this._completeTimer);
                    this._completeTimer = setTimeout(() => {
                        this.completedHint = null;
                    }, 1000);
                }
                this.activeHint = newHint;
                this.$nextTick(() => {
                    const bar = this.$refs.hintBar;
                    if (!bar) return;
                    bar.classList.remove('hint-bar--enter');
                    void bar.offsetWidth;
                    bar.classList.add('hint-bar--enter');
                });
            },

            async dismiss() {
                if (!this.activeHint) return;
                const res = await fetch(this.dismissRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        hint_key: this.activeHint.key
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    this.setActiveHint(data.hint ?? null);
                    window.dispatchEvent(new CustomEvent('hint:dismissed', {
                        detail: data.hint ?? null
                    }));
                }
            },
        };
    }
</script>
