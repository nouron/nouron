// Kommandozentrale dashboard — Alpine component. Deliberately small: only the
// Kolonisten-Zulage action needs client-side interaction (rest of the screen
// is server-rendered). Copied from colonyHexView() in colony-hexgrid.js
// (toast/HTTP/resourcebar-sync helpers) rather than reusing that component
// directly — it's tightly coupled to the hex-grid canvas (see initHexGrid()
// call in its init()), which this screen doesn't have.
function commandCenter(config = {}) {
    return {
        routes: config.routes ?? {},
        i18n: config.i18n ?? {},

        toastMessage: '',
        toastVisible: false,
        toastType: 'error', // 'error' | 'info'
        _toastTimer: null,

        // Kolonisten-Zulage (GDD §14) — trust effect only applies from the next
        // Sol onward (TrustService writes the stored trust value only during
        // GameTick), so only the Credits chip is synced immediately here.
        async doPurchaseStipend(tier) {
            const res = await this.post(this.routes.stipend, { tier });
            if (res.ok) {
                this.syncResbarAmount('.res-Cr', res.credits);
                this.flashResChip('.res-Cr');
                this.showToast((this.i18n.stipendSuccess ?? '').replace(':cost', res.cost), 'info');
            } else {
                this.showToast(res.message ?? res.error ?? this.i18n.stipendError, 'error');
            }
        },

        // ── Toast notifications ───────────────────────────────────────────────

        showToast(message, type = 'error') {
            if (this._toastTimer) clearTimeout(this._toastTimer);
            this.toastMessage = message;
            this.toastType = type;
            this.toastVisible = true;
            this._toastTimer = setTimeout(() => {
                this.toastVisible = false;
            }, 3500);
        },

        // ── Resourcebar sync (server-rendered, not reactive — direct DOM patch) ──

        syncResbarAmount(selector, value) {
            const el = document.querySelector(`${selector} .res-amount`);
            if (el) el.textContent = value.toLocaleString('de-DE');
        },

        flashResChip(selector) {
            const chip = document.querySelector(selector);
            if (!chip) return;
            chip.classList.remove('res-chip--flash');
            void chip.offsetWidth;
            chip.classList.add('res-chip--flash');
            setTimeout(() => chip.classList.remove('res-chip--flash'), 600);
        },

        // ── HTTP helpers ──────────────────────────────────────────────────────
        //
        // These deliberately ignore the HTTP status and resolve with the parsed body.
        // The server answers rule violations with 422 + { ok: false, error, message },
        // so callers branch on the JSON `ok` field, never on `response.ok`.
        //
        // Load-bearing: adding a global "non-2xx = throw" interceptor here would swallow
        // every specific error message at once. Keep the status out of it.

        get(url) {
            return fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).then((r) => r.json());
        },

        post(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            }).then((r) => r.json());
        },
    };
}
