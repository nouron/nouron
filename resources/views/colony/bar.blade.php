@extends("layouts.colony")
@section("title", __("colony.bar_title") . " — Nouron")

@php
    $hs = fn(string $slot, string $device): array => $hotspots[$slot][$device] ?? ["left" => 50, "top" => 50];
@endphp

@push("styles")
    <style>
        @foreach (["spot_0", "spot_1", "spot_2", "spot_3", "spot_4", "spot_5"] as $s)
            .hs-slot-{{ $s }} {
                left: {{ $hs($s, "desktop")["left"] }}%;
                top: {{ $hs($s, "desktop")["top"] }}%;
            }
        @endforeach
        @@media (min-width: 768px) and (max-width: 1023px) {
            @foreach (["spot_0", "spot_1", "spot_2", "spot_3", "spot_4", "spot_5"] as $s)
                .hs-slot-{{ $s }} {
                    left: {{ $hs($s, "tablet")["left"] }}%;
                    top: {{ $hs($s, "tablet")["top"] }}%;
                }
            @endforeach
        }

        @@media (max-width: 767px) {
            @foreach (["spot_0", "spot_1", "spot_2", "spot_3", "spot_4", "spot_5"] as $s)
                .hs-slot-{{ $s }} {
                    left: {{ $hs($s, "mobile")["left"] }}%;
                    top: {{ $hs($s, "mobile")["top"] }}%;
                }
            @endforeach
        }
    </style>
@endpush

@section("content")
    @php
        $resourceLabels = [
            1 => __("resources.res_credits"),
            3 => __("resources.res_regolith"),
            4 => __("resources.res_werkstoffe"),
            5 => __("resources.res_organika"),
        ];
        $spotForOffer = ["spot_1", "spot_2"]; // offer index → spot key
    @endphp

    <div class="bar-page"
        x-data='barPage(
    @json($merchantVisit),
    @json($merchantItems),
    @json(route("colony.merchant.buy", ["itemId" => "__ID__"])),
    @json(route("colony.merchant.open", ["visitId" => "__VISIT__"])),
    @json(route("colony.bar.accept", ["offer" => "__OFFER__"])),
    @json($offers->count())
)'
        x-cloak>

        @if ($barLevel < 1)
            <p>{{ __("colony.bar_no_building") }}</p>
        @else
            {{-- Viewport showing background and hotspots --}}
            <div class="cantina-viewport" @touchstart="touchStart" @touchend="touchEnd">

                {{-- Background image wrapper (shifts on mobile swipe, static on desktop) --}}
                <div class="cantina-background-wrapper" :style="{ transform: `translateX(-${current * 22.222}%)` }">

                    {{-- Merchant Hotspot (Jara) — Panel 0 center: 16.7% --}}
                    @if ($merchantVisit !== null)
                        <button class="cantina-hotspot hs-slot-spot_0" @click="openMerchant()">
                            <span class="hotspot-pulse"></span>
                            <i class="bi bi-shop"></i>
                            <span class="hotspot-label">{{ __("colony.merchant_title") }}</span>
                        </button>
                    @endif

                    {{-- Offer Hotspots — Panel 1 center: 39%, Panel 2 center: 61% --}}
                    @foreach ($offers as $idx => $offer)
                        @php
                            $hsSlot = $spotForOffer[$idx] ?? "spot_1";
                            $offerId = $offer->id;
                            $char = $characterAssignment[$hsSlot] ?? null;
                            $charName = $char["name"] ?? "???";
                        @endphp
                        <button class="cantina-hotspot{{ $char ? " has-portrait" : "" }} hs-slot-{{ $hsSlot }}"
                            @click="openOffer({{ $offerId }})">
                            <span class="hotspot-pulse"></span>
                            @if ($char)
                                <img class="hotspot-portrait"
                                    src="{{ asset("img/characters/" . $char["slug"] . ".webp") }}"
                                    alt="{{ $charName }}">
                            @else
                                <i class="bi bi-chat-right-text"></i>
                            @endif
                            <span class="hotspot-label">{{ $charName }}</span>
                        </button>
                    @endforeach

                </div>

                {{-- Mobile-only swipe dots indicators --}}
                <div class="swipe-dots nav-mobile"
                    style="position: absolute; bottom: 0.75rem; left: 0; right: 0; z-index: 20;">
                    <template x-for="i in count" :key="i">
                        <span class="swipe-dot" :class="{ 'swipe-dot--active': current === (i - 1) }"
                            @click="goTo(i-1)"></span>
                    </template>
                </div>

                {{-- Empty cantina indicator --}}
                @if ($offers->isEmpty() && $merchantVisit === null)
                    <div class="cantina-empty-hint">
                        <p style="margin:0; font-size: 0.9rem; font-weight:500;">{{ __("colony.bar_no_offers") }}</p>
                    </div>
                @endif

            </div>

            {{-- Backdrop to dim page behind modal --}}
            <div class="cantina-modal-backdrop" x-show="activeModal !== null" @click="closeModal()" x-transition.opacity
                style="position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 999;" x-cloak></div>

            {{-- Interactive Modal / Drawer --}}
            <div class="cantina-modal" :class="{ 'open': activeModal !== null }" x-show="activeModal !== null" x-cloak>
                <button @click="closeModal()" class="cantina-modal-close" aria-label="Schließen">&times;</button>

                {{-- Merchant items listing --}}
                @if ($merchantVisit !== null)
                    <div x-show="activeModal === 'merchant'">
                        <div style="margin-bottom:1rem">
                            <h3 style="margin:0;font-size:1.25rem;">🛸 {{ __("colony.merchant_title") }}</h3>
                            <small style="color:var(--pico-muted-color)">
                                {{ __("colony.merchant_until_sol") }} {{ $merchantVisit->tick_end }}
                            </small>
                        </div>

                        {{-- Toast feedback --}}
                        <div x-show="toast.visible" x-transition :class="'merchant-toast merchant-toast--' + toast.type"
                            x-text="toast.message" aria-live="polite" role="status"></div>

                        <div class="merchant-items-bar" style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                            <template x-for="item in merchantItems" :key="item.id">
                                <article class="merchant-item-bar" :class="{ 'merchant-item-bar--sold': item.sold }">
                                    <div class="merchant-item-bar__label" x-text="item.label"></div>
                                    <div class="merchant-item-bar__cost" x-text="`${item.cost_credits} Cr`"></div>
                                    <button class="merchant-item-bar__buy" :disabled="item.sold || buyLoading"
                                        @click="buyItem(item.id)">
                                        <span x-show="!item.sold">{{ __("colony.merchant_buy") }}</span>
                                        <span x-show="item.sold">{{ __("colony.merchant_sold") }}</span>
                                    </button>
                                </article>
                            </template>
                        </div>
                    </div>
                @endif

                {{-- Offers listings --}}
                @foreach ($offers as $idx => $offer)
                    @php
                        $offerId = $offer->id;
                        $char = $characterAssignment[$spotForOffer[$idx] ?? "spot_1"] ?? null;
                        $name = $char["name"] ?? "???";
                        $role = $char["role"] ?? "";
                    @endphp
                    <div x-show="activeModal === 'offer_{{ $offerId }}'">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem">
                            <div class="guest-avatar">
                                @if ($char && isset($char["slug"]))
                                    <img class="guest-avatar__portrait"
                                        src="{{ asset("img/characters/" . $char["slug"] . ".webp") }}"
                                        alt="{{ $name }}">
                                @else
                                    <i class="bi bi-person-fill" style="font-size: 1.35rem; color: var(--color-accent)"></i>
                                @endif
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.15rem;">{{ $name }}</h3>
                                <small style="color:var(--pico-muted-color); font-weight: 500;">{{ $role }}</small>
                            </div>
                        </div>

                        <div
                            style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:0.75rem;margin-bottom:1.5rem;background: #f7f7f5;padding:0.75rem 1rem;border-radius:6px;border:1px solid var(--pico-muted-border-color)">
                            <div>
                                <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                    {{ __("colony.bar_offer_give") }}
                                </div>
                                <strong style="font-size: 1.15rem;">{{ $offer->give_amount }}×</strong>
                                {{ $resourceLabels[$offer->give_resource_id] ?? $offer->give_resource_id }}
                            </div>
                            <span style="font-size:1.5rem;color:var(--pico-muted-color)">→</span>
                            <div>
                                <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                    {{ __("colony.bar_offer_get") }}
                                </div>
                                <strong style="font-size: 1.15rem;">{{ $offer->get_amount }}×</strong>
                                {{ $resourceLabels[$offer->get_resource_id] ?? $offer->get_resource_id }}
                            </div>
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem">
                            <small style="color:var(--pico-muted-color)">
                                {{ __("colony.bar_offer_expires") }} {{ $offer->expires_tick }}
                            </small>
                            <div style="display:flex;gap:0.5rem">
                                <button @click="accept({{ $offerId }}, $el)"
                                    :disabled="accepted[{{ $offerId }}] || loading"
                                    style="margin:0;padding: 0.35rem 1rem;font-size:0.85rem;">
                                    <span
                                        x-show="!accepted[{{ $offerId }}]">{{ __("colony.bar_offer_accept") }}</span>
                                    <span x-show="accepted[{{ $offerId }}]">✓</span>
                                </button>
                            </div>
                        </div>
                        <div x-show="error[{{ $offerId }}]" x-text="error[{{ $offerId }}]"
                            style="color:var(--pico-del-color);font-size:0.85rem;margin-top:0.5rem"></div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

    @include("partials.first-visit-popup", [
        "firstVisitKey" => "cantina",
        "firstVisitTitle" => "colony.first_visit_cantina_title",
        "firstVisitText" => "colony.first_visit_cantina_text",
    ])

    <script>
        function barPage(merchantVisit, merchantItems, buyRoute, openRoute, acceptRoute, offersCount = 0) {
            const hasGuests = (merchantVisit !== null) || (merchantItems && merchantItems.length > 0) || offersCount > 0;
            const panelCount = hasGuests ? 4 : 1;

            return {
                // Inherit swipe carousel properties & methods from swipe.js
                ...swipeCarousel(panelCount, 0),

                // Offers state
                accepted: {},
                loading: false,
                error: {},

                // Merchant state
                merchantVisit: merchantVisit,
                merchantItems: merchantItems ?? [],
                buyLoading: false,
                toast: {
                    visible: false,
                    message: '',
                    type: 'info'
                },
                _toastTimer: null,

                // Modal Drawer state
                activeModal: null,

                openMerchant() {
                    this.activeModal = 'merchant';
                    this.markVisitSeen();
                },

                openOffer(offerId) {
                    this.activeModal = 'offer_' + offerId;
                },

                closeModal() {
                    this.activeModal = null;
                },

                // Mark visit as seen (fire-and-forget)
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
                            this.showToast(data.message ?? @json(__("colony.merchant_buy_success")), 'info');
                        } else {
                            this.showToast(data.error ?? @json(__("colony.merchant_buy_error")), 'error');
                        }
                    } catch {
                        this.showToast(@json(__("colony.merchant_buy_error")), 'error');
                    } finally {
                        this.buyLoading = false;
                    }
                },

                showToast(message, type = 'info') {
                    if (this._toastTimer) clearTimeout(this._toastTimer);
                    this.toast = {
                        visible: true,
                        message,
                        type
                    };
                    this._toastTimer = setTimeout(() => {
                        this.toast.visible = false;
                    }, 3500);
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
