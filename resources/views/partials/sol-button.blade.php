{{--
    Sol-Button partial — Alpine.js component.
    Checks remaining AP before ending the Sol.
    Shows confirm dialog if AP unspent, then shows loading overlay (min 5s).
--}}
<div
    x-data="{
        confirmOpen: false,
        loading: false,
        apData: null,

        get hasAp() {
            return this.apData && this.apData.total > 0;
        },

        get apSummary() {
            if (!this.apData) return '';
            const parts = [];
            if (this.apData.construction > 0) parts.push(this.apData.construction + ' Bau-AP');
            if (this.apData.research > 0)     parts.push(this.apData.research + ' Forschungs-AP');
            if (this.apData.navigation > 0)   parts.push(this.apData.navigation + ' Nav-AP');
            return parts.join(', ');
        },

        async handleClick() {
            const resp = await fetch('{{ route('sol.remaining-ap') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
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

            const minDuration = 5000;
            const form = document.getElementById('sol-next-form');
            const formData = new FormData(form);

            try {
                await Promise.all([
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                    }),
                    new Promise(resolve => setTimeout(resolve, minDuration)),
                ]);
                // Always navigate to colony view — redirect()->back() is unreliable via fetch
                window.location.href = '{{ route('colony.view') }}';
            } catch (e) {
                this.loading = false;
                alert('Fehler beim Beenden des Sol. Bitte Seite neu laden.');
            }
        }
    }"
    style="display:inline-flex;align-items:center;"
>
    {{-- Sol form (hidden, submitted via fetch) --}}
    <form id="sol-next-form" method="POST" action="{{ route('sol.next') }}" style="display:none;">
        @csrf
    </form>

    {{-- Trigger button --}}
    <button type="button" class="btn-sol" @click="handleClick">
        <i class="bi bi-skip-forward-fill"></i> {{ __('colony.next_sol_button') }}
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
                <button type="button" class="sol-btn sol-btn--ghost" @click="confirmOpen = false">
                    Weiterspielen
                </button>
                <button type="button" class="sol-btn sol-btn--primary" @click="submitSol">
                    Sol trotzdem beenden
                </button>
            </div>
        </div>
    </div>

    {{-- Loading overlay --}}
    <div x-show="loading" x-cloak class="sol-overlay sol-overlay--loading">
        <div class="sol-loading">
            <div class="sol-spinner"></div>
            <p class="sol-loading__text">Sol wird berechnet …</p>
            <p class="sol-loading__sub">Ereignisse, Produktion, Verfall</p>
        </div>
    </div>
</div>
