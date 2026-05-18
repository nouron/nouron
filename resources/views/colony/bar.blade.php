@extends('layouts.colony')
@section('title', __('colony.bar_title') . ' — Nouron')

@section('content')
@php
    $resourceLabels = [
        1 => __('resources.res_credits'),
        3 => __('resources.res_regolith'),
        4 => __('resources.res_werkstoffe'),
        5 => __('resources.res_organika'),
    ];
@endphp

<div x-data='barPage(
    @json($merchantVisit),
    @json($merchantItems),
    @json(route("colony.merchant.buy", ["itemId" => "__ID__"])),
    @json(route("colony.merchant.open", ["visitId" => "__VISIT__"])),
    @json(route("colony.bar.accept", ["offer" => "__OFFER__"]))
)' x-cloak>

    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
        <h2 style="margin:0">{{ __('colony.bar_title') }}</h2>
        <small style="color:var(--pico-muted-color)">Sol {{ $currentSol }}</small>
    </div>

    {{-- Traveling Merchant section — only shown when an active visit exists --}}
    @if ($merchantVisit !== null)
    <section class="merchant-section"
             x-init="markVisitSeen()">
        <div class="merchant-section__header">
            <h3 style="margin:0">🛸 {{ __('colony.merchant_title') }}</h3>
            <small style="color:var(--pico-muted-color)">
                {{ __('colony.merchant_until_sol') }} {{ $merchantVisit->tick_end }}
            </small>
        </div>

        {{-- Toast for buy feedback --}}
        <div x-show="toast.visible"
             x-transition
             :class="'merchant-toast merchant-toast--' + toast.type"
             x-text="toast.message"
             aria-live="polite"
             role="status"></div>

        <div class="merchant-items-bar">
            <template x-for="item in merchantItems" :key="item.id">
                <article class="merchant-item-bar"
                         :class="{ 'merchant-item-bar--sold': item.sold }">
                    <div class="merchant-item-bar__label" x-text="item.label"></div>
                    <div class="merchant-item-bar__cost" x-text="`${item.cost_credits} Cr`"></div>
                    <button class="merchant-item-bar__buy"
                            :disabled="item.sold || buyLoading"
                            @click="buyItem(item.id)">
                        <span x-show="!item.sold">{{ __('colony.merchant_buy') }}</span>
                        <span x-show="item.sold">{{ __('colony.merchant_sold') }}</span>
                    </button>
                </article>
            </template>
        </div>
    </section>
    @endif

    @if ($barLevel < 1)
        <p>{{ __('colony.bar_no_building') }}</p>
    @elseif ($offers->isEmpty())
        <p style="color:var(--pico-muted-color)">{{ __('colony.bar_no_offers') }}</p>
    @else
        <p style="font-weight:600;margin-bottom:1rem">{{ __('colony.bar_offer_heading') }}</p>

        <div style="display:flex;flex-direction:column;gap:1rem;max-width:480px">
            @foreach ($offers as $offer)
            <article style="margin:0;padding:1.25rem;border-radius:var(--pico-border-radius);border:1px solid var(--pico-muted-border-color)">
                <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:0.75rem;margin-bottom:1rem">
                    <div>
                        <div style="font-size:0.8rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                            {{ __('colony.bar_offer_give') }}
                        </div>
                        <strong>{{ $offer->give_amount }}×</strong>
                        {{ $resourceLabels[$offer->give_resource_id] ?? $offer->give_resource_id }}
                    </div>
                    <span style="font-size:1.25rem">→</span>
                    <div>
                        <div style="font-size:0.8rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                            {{ __('colony.bar_offer_get') }}
                        </div>
                        <strong>{{ $offer->get_amount }}×</strong>
                        {{ $resourceLabels[$offer->get_resource_id] ?? $offer->get_resource_id }}
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <small style="color:var(--pico-muted-color)">
                        {{ __('colony.bar_offer_expires') }} {{ $offer->expires_tick }}
                    </small>
                    <button
                        @click="accept({{ $offer->id }}, $el)"
                        :disabled="accepted[{{ $offer->id }}] || loading"
                        style="margin:0"
                    >
                        <span x-show="!accepted[{{ $offer->id }}]">{{ __('colony.bar_offer_accept') }}</span>
                        <span x-show="accepted[{{ $offer->id }}]">✓</span>
                    </button>
                </div>
                <div x-show="error[{{ $offer->id }}]" x-text="error[{{ $offer->id }}]"
                     style="color:var(--pico-del-color);font-size:0.85rem;margin-top:0.5rem"></div>
            </article>
            @endforeach
        </div>
    @endif

</div>

<script>
function barPage(merchantVisit, merchantItems, buyRoute, openRoute, acceptRoute) {
    return {
        // Bar offer state — plain object for Alpine reactivity compatibility (Set is not tracked)
        accepted: {},
        loading: false,
        error: {},

        // Merchant state
        merchantVisit: merchantVisit,
        merchantItems: merchantItems ?? [],
        buyLoading: false,
        toast: { visible: false, message: '', type: 'info' },
        _toastTimer: null,

        // Mark visit as seen on first render (fire-and-forget)
        markVisitSeen() {
            if (!this.merchantVisit || this.merchantVisit.was_visited) return;
            const url = openRoute.replace('__VISIT__', this.merchantVisit.id);
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({}),
            }).catch(() => {});
            this.merchantVisit.was_visited = true;
        },

        async buyItem(itemId) {
            this.buyLoading = true;
            const url = buyRoute.replace('__ID__', itemId);
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });
                const data = await res.json();
                if (data.ok) {
                    const item = this.merchantItems.find(i => i.id === itemId);
                    if (item) item.sold = true;
                    this.showToast(data.message ?? @json(__('colony.merchant_buy_success')), 'info');
                } else {
                    this.showToast(data.error ?? @json(__('colony.merchant_buy_error')), 'error');
                }
            } catch {
                this.showToast(@json(__('colony.merchant_buy_error')), 'error');
            } finally {
                this.buyLoading = false;
            }
        },

        showToast(message, type = 'info') {
            if (this._toastTimer) clearTimeout(this._toastTimer);
            this.toast = { visible: true, message, type };
            this._toastTimer = setTimeout(() => { this.toast.visible = false; }, 3500);
        },

        // Bar offer accept
        async accept(offerId, btn) {
            this.loading = true;
            this.error[offerId] = null;
            try {
                const res = await fetch(acceptRoute.replace('__OFFER__', offerId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.ok) {
                    this.accepted[offerId] = true;
                } else {
                    this.error[offerId] = data.error ?? 'Fehler';
                }
            } catch {
                this.error[offerId] = 'Verbindungsfehler';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
