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
        // Matches resources.abbreviation in the DB — same values the resource bar's
// .res-{abbr} chip classes are built for (Cr/Rg/Co/Or).
$resourceAbbr = [1 => "Cr", 3 => "Rg", 4 => "Co", 5 => "Or"];
// Offer index → spot key. spot_0 is reserved for Corvan's curated special
        // inventory (merchant hotspot, catalog dialog) — offers (guest barter +
        // Corvan's Alltagsgeschäft commodity offers, GDD §12 Kanal 1) share the
        // remaining 5 hotspots. Cycled with modulo below since a Corvan visit alone
        // can add up to ~4 commodity offers (2-3 sell lots + 1 buy) on top of the
        // anonymous guest rotation (up to level_max_concurrent, 2-6 by bar level) —
        // known capacity gap, flagged for game-designer/game-developer: at high bar
        // levels + an active Corvan visit, offer count can still exceed 5 available
        // spots and hotspots will overlap.
        $spotForOffer = ["spot_1", "spot_2", "spot_3", "spot_4", "spot_5"];
    @endphp

    <div class="bar-page"
        x-data='barPage(
    @json($merchantVisit),
    @json($merchantItems),
    @json(route("colony.merchant.buy", ["itemId" => "__ID__"])),
    @json(route("colony.merchant.open", ["visitId" => "__VISIT__"])),
    @json(route("colony.bar.accept", ["offer" => "__OFFER__"])),
    @json(route("colony.bar.negotiate", ["offer" => "__OFFER__"])),
    @json($resourceAbbr),
    @json($offers->count()),
    @json(route("colony.corporate-contact.offer")),
    @json(route("colony.corporate-contact.buy-harvester"))
)'
        x-cloak>

        @if ($barLevel < 1)
            <p>{{ __("colony.bar_no_building") }}</p>
        @else
            {{-- Viewport showing background and hotspots --}}
            <div class="cantina-viewport" @touchstart="touchStart" @touchend="touchEnd">

                {{-- Background image wrapper (shifts on mobile swipe, static on desktop) --}}
                <div class="cantina-background-wrapper" :style="{ transform: `translateX(-${current * 22.222}%)` }">

                    {{-- Merchant Hotspot — Panel 0 center: 16.7% --}}
                    @if ($merchantVisit !== null)
                        <button class="cantina-hotspot has-portrait hotspot-merchant hs-slot-spot_0" @click="openMerchant()">
                            <span class="hotspot-badge"><i class="bi bi-exclamation-lg"></i></span>
                            <img class="hotspot-portrait" src="{{ asset("img/characters/merchant.webp") }}"
                                srcset="{{ asset("img/characters/merchant.webp") }} 1x, {{ asset("img/characters/merchant_lg.webp") }} 2x"
                                alt="{{ __("colony.merchant_title") }}">
                            <span class="hotspot-label">{{ __("colony.merchant_title") }}</span>
                        </button>
                    @endif

                    {{-- Offer Hotspots — anonymous guest barter offers + Corvan's Alltagsgeschäft
                     commodity offers (bar_offers.visit_id set, GDD §12 Kanal 1). Corvan's offers
                     get his own portrait/name instead of the random per-slot guest character, so
                     the player can tell "Corvan is selling Organika" apart from "Dax wants to barter". --}}
                    @foreach ($offers as $idx => $offer)
                        @php
                            $hsSlot = $spotForOffer[$idx % count($spotForOffer)];
                            $offerId = $offer->id;
                            $isCorvanOffer = $offer->visit_id !== null;
                            $char = $isCorvanOffer ? null : $characterAssignment[$hsSlot] ?? null;
                            $charName = $isCorvanOffer ? __("colony.merchant_title") : $char["name"] ?? "???";
                        @endphp
                        <button
                            class="cantina-hotspot{{ $isCorvanOffer || $char ? " has-portrait" : "" }}{{ $isCorvanOffer ? " hotspot-corvan-offer" : "" }} hs-slot-{{ $hsSlot }}"
                            @click="openOffer({{ $offerId }})">
                            <span class="hotspot-pulse"></span>
                            @if ($isCorvanOffer)
                                <span class="hotspot-badge hotspot-badge--corvan" aria-hidden="true"><i
                                        class="bi bi-coin"></i></span>
                                <img class="hotspot-portrait" src="{{ asset("img/characters/merchant.webp") }}"
                                    srcset="{{ asset("img/characters/merchant.webp") }} 1x, {{ asset("img/characters/merchant_lg.webp") }} 2x"
                                    alt="{{ $charName }}">
                            @elseif ($char)
                                <img class="hotspot-portrait"
                                    src="{{ asset("img/characters/" . $char["slug"] . ".webp") }}"
                                    srcset="{{ asset("img/characters/" . $char["slug"] . ".webp") }} 1x, {{ asset("img/characters/" . $char["slug"] . "_lg.webp") }} 2x"
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

            {{-- Orin (corporate_rep) — Harvester second-instance offer, Weg A (GDD §4c,
             freigegeben 2026-08-05). Fetched client-side (GET
             colony.corporate-contact.offer) on page load — deliberately NOT part of
             the $offers/$characterAssignment hotspot-rotation system above (BarService),
             see CorporateContactService docblock. A fixed banner rather than a scene
             hotspot: no reserved position exists for a rare, one-off visitor in
             data/cantina_hotspots.json, and the deal is meant to stand out, not blend
             into the random-guest rotation. --}}
            <div class="corporate-contact-banner" x-show="corporateContactOffer" x-cloak @click="openCorporateContact()">
                <img class="corporate-contact-banner__portrait" src="{{ asset("img/characters/corporate_rep.webp") }}"
                    alt="{{ config("characters.corporate_rep.name") }}">
                <div class="corporate-contact-banner__text">
                    <strong>{{ config("characters.corporate_rep.name") }}</strong>
                    <span>{{ __("colony.corporate_contact_banner_hint") }}</span>
                </div>
                <span class="corporate-contact-banner__price res-chip res-Cr" x-show="corporateContactOffer">
                    <span class="res-abbr">Cr</span>
                    <span class="res-amount" x-text="corporateContactOffer?.price"></span>
                </span>
            </div>

            {{-- Backdrop to dim page behind modal --}}
            <div class="cantina-modal-backdrop" x-show="activeModal !== null" @click="closeModal()" x-transition.opacity
                style="position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 999;" x-cloak></div>

            {{-- Interactive Modal / Drawer --}}
            <div class="cantina-modal" :class="{ open: activeModal !== null, ['cantina-modal--' + dialogApType]: true }"
                x-show="activeModal !== null" x-cloak>
                <button @click="closeModal()" class="cantina-modal-close" aria-label="Schließen">&times;</button>

                {{-- Merchant items listing --}}
                @if ($merchantVisit !== null)
                    @php
                        $merchantPortraitSrc = asset("img/characters/merchant.webp");
                        $merchantPortraitLgSrc = asset("img/characters/merchant_lg.webp");
                        $merchantName = __("colony.merchant_title");
                        $merchantRole = __("colony.merchant_until_sol") . " " . $merchantVisit->tick_end;
                    @endphp
                    <div x-show="activeModal === 'merchant'">
                        <x-cantina-dialog :portrait-src="$merchantPortraitSrc" :portrait-lg-src="$merchantPortraitLgSrc" :name="$merchantName" :role="$merchantRole">
                            {{-- Toast feedback --}}
                            <div x-show="toast.visible" x-transition :class="'merchant-toast merchant-toast--' + toast.type"
                                x-text="toast.message" aria-live="polite" role="status"></div>

                            <div class="merchant-items-bar"
                                style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                                <template x-for="item in merchantItems" :key="item.id">
                                    <article class="merchant-item-bar" :class="{ 'merchant-item-bar--sold': item.sold }">
                                        <div class="merchant-item-bar__label" x-text="item.label" :title="item.label">
                                        </div>
                                        <div class="merchant-item-bar__row">
                                            <span class="res-chip res-Cr">
                                                <span class="res-abbr">Cr</span>
                                                <span class="res-amount" x-text="item.cost_credits"></span>
                                            </span>
                                            <button class="merchant-item-bar__buy" :disabled="item.sold || buyLoading"
                                                @click="buyItem(item.id)">
                                                <span x-show="!item.sold">{{ __("colony.merchant_buy") }}</span>
                                                <span x-show="item.sold">{{ __("colony.merchant_sold") }}</span>
                                            </button>
                                        </div>
                                    </article>
                                </template>
                            </div>
                        </x-cantina-dialog>
                    </div>
                @endif

                {{-- Orin's harvester offer dialog --}}
                @php
                    $corporateContactPortraitSrc = asset("img/characters/corporate_rep.webp");
                    $corporateContactPortraitLgSrc = asset("img/characters/corporate_rep_lg.webp");
                    $corporateContactName = config("characters.corporate_rep.name");
                    $corporateContactRole = config("characters.corporate_rep.role");
                @endphp
                <div x-show="activeModal === 'corporate_contact'">
                    <x-cantina-dialog :portrait-src="$corporateContactPortraitSrc" :portrait-lg-src="$corporateContactPortraitLgSrc" :name="$corporateContactName" :role="$corporateContactRole">
                        {{-- Toast feedback --}}
                        <div x-show="toast.visible" x-transition :class="'merchant-toast merchant-toast--' + toast.type"
                            x-text="toast.message" aria-live="polite" role="status"></div>

                        <p>{{ __("colony.corporate_contact_dialog_intro") }}</p>

                        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                            <div>
                                <small
                                    style="color:var(--pico-muted-color)">{{ __("colony.corporate_contact_price_label") }}</small>
                                <span class="res-chip res-Cr">
                                    <span class="res-abbr">Cr</span>
                                    <span class="res-amount" x-text="corporateContactOffer?.price"></span>
                                </span>
                            </div>
                            <button class="tile-action-btn" style="width:auto;" :disabled="corporateContactBuying"
                                @click="buyCorporateContact()">
                                <span class="tile-action-btn__body">{{ __("colony.merchant_buy") }}</span>
                            </button>
                        </div>
                    </x-cantina-dialog>
                </div>

                {{-- Offers listings --}}
                @foreach ($offers as $idx => $offer)
                    @php
                        $offerId = $offer->id;
                        $isCorvanOffer = $offer->visit_id !== null;
                        $hsSlot = $spotForOffer[$idx % count($spotForOffer)];
                        $char = $isCorvanOffer ? null : $characterAssignment[$hsSlot] ?? null;
                        // Corvan offers reuse his existing, already-translated identity
                        // (same portrait/title as the special-inventory hotspot) rather
                        // than introducing new copy — "Bleibt bis Sol X" mirrors the
                        // catalog dialog's role text for a consistent Corvan presentation.
                        $name = $isCorvanOffer ? __("colony.merchant_title") : $char["name"] ?? "???";
                        $role = $isCorvanOffer
                            ? __("colony.merchant_until_sol") . " " . $offer->expires_tick
                            : $char["role"] ?? "";
                        $offerCharSlug = $isCorvanOffer ? "merchant" : $char["slug"] ?? "stranger";
                        $offerPortraitSrc = asset("img/characters/" . $offerCharSlug . ".webp");
                        $offerPortraitLgSrc = asset("img/characters/" . $offerCharSlug . "_lg.webp");
                    @endphp
                    <div x-show="activeModal === 'offer_{{ $offerId }}'">
                        <x-cantina-dialog :portrait-src="$offerPortraitSrc" :portrait-lg-src="$offerPortraitLgSrc" :name="$name" :role="$role">
                            {{-- Toast feedback --}}
                            <div x-show="toast.visible" x-transition
                                :class="'merchant-toast merchant-toast--' + toast.type" x-text="toast.message"
                                aria-live="polite" role="status"></div>

                            <div
                                style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:0.75rem;background: #f7f7f5;padding:0.75rem 1rem;border-radius:6px;border:1px solid var(--pico-muted-border-color)">
                                <div>
                                    <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                        {{ __("colony.bar_offer_give") }}
                                    </div>
                                    @include("partials.res_chip", [
                                        "abbreviation" => $resourceAbbr[$offer->give_resource_id] ?? "?",
                                        "amount" => $offer->give_amount,
                                    ])
                                </div>
                                <span style="font-size:1.5rem;color:var(--pico-muted-color)">→</span>
                                <div>
                                    <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                        {{ __("colony.bar_offer_get") }}
                                    </div>
                                    @include("partials.res_chip", [
                                        "abbreviation" => $resourceAbbr[$offer->get_resource_id] ?? "?",
                                        "amount" => $offer->get_amount,
                                    ])
                                </div>
                            </div>

                            <div
                                style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                                <small style="color:var(--pico-muted-color)">
                                    {{ __("colony.bar_offer_expires") }} {{ $offer->expires_tick }}
                                </small>
                                <div style="display:flex;gap:0.5rem">
                                    @if ($hasConsul)
                                        <button class="tile-action-btn tile-action-btn--secondary" style="width:auto;"
                                            @click="negotiate({{ $offerId }}, $el)"
                                            :disabled="negotiated[{{ $offerId }}] || offerResolved({{ $offerId }}) ||
                                                loading">
                                            <span class="tile-action-btn__body">
                                                <span
                                                    x-show="!negotiated[{{ $offerId }}] && negotiateResult[{{ $offerId }}] !== 'failed'">{{ __("colony.bar_offer_negotiate") }}</span>
                                                <span x-show="negotiated[{{ $offerId }}]">✓</span>
                                                <span x-show="negotiateResult[{{ $offerId }}] === 'failed'">✗</span>
                                            </span>
                                            <span class="ap-chip ap-cost-chip ap-chip--economy" aria-hidden="true"
                                                x-text="`Eco {{ $negotiateApCost }} AP`"></span>
                                        </button>
                                    @endif
                                    <button class="tile-action-btn" style="width:auto;"
                                        @click="accept({{ $offerId }}, $el)"
                                        :disabled="offerResolved({{ $offerId }}) || loading">
                                        <span class="tile-action-btn__body">
                                            <span
                                                x-show="!accepted[{{ $offerId }}]">{{ __("colony.bar_offer_accept") }}</span>
                                            <span x-show="accepted[{{ $offerId }}]">✓</span>
                                        </span>
                                        <span class="ap-chip ap-cost-chip ap-chip--economy" aria-hidden="true"
                                            x-text="negotiated[{{ $offerId }}] ? 'Eco 0 AP' : `Eco {{ $offerApCost }} AP`"></span>
                                    </button>
                                </div>
                            </div>
                            <div x-show="negotiated[{{ $offerId }}] && !accepted[{{ $offerId }}]"
                                style="color:#166534;font-size:0.85rem">
                                {{ __("colony.bar_offer_negotiate_success") }}
                            </div>
                            <div x-show="negotiateResult[{{ $offerId }}] === 'failed'"
                                style="color:var(--pico-del-color);font-size:0.85rem">
                                {{ __("colony.bar_offer_negotiate_failed") }}
                            </div>
                            <div x-show="error[{{ $offerId }}]" x-text="error[{{ $offerId }}]"
                                style="color:var(--pico-del-color);font-size:0.85rem"></div>
                        </x-cantina-dialog>
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
        function barPage(merchantVisit, merchantItems, buyRoute, openRoute, acceptRoute, negotiateRoute, resourceAbbr,
            offersCount = 0, corporateContactOfferRoute, corporateContactBuyRoute) {
            const hasGuests = (merchantVisit !== null) || (merchantItems && merchantItems.length > 0) || offersCount > 0;
            const panelCount = hasGuests ? 4 : 1;

            return {
                // Inherit swipe carousel properties & methods from swipe.js
                ...swipeCarousel(panelCount, 0),

                // Offers state
                accepted: {},
                negotiated: {}, // offerId -> true once a negotiation improved the offer's terms
                negotiateResult: {}, // offerId -> 'failed' (negotiation lost the offer entirely)
                loading: false,
                error: {},

                // Whether an offer's dialog is fully resolved — accepted, or a
                // negotiation was lost (offer gone either way). A successful
                // negotiation alone does NOT resolve the offer: Annehmen is still
                // needed to actually execute the (now improved) trade.
                offerResolved(offerId) {
                    return !!this.accepted[offerId] || this.negotiateResult[offerId] === 'failed';
                },

                // Live resourcebar sync — project convention: every AJAX action that
                // changes AP/resources must update the resourcebar without a reload.
                // Flashes the chip too (same .res-chip--flash/.ap-chip--flash animation
                // used everywhere else in the game) so the change is easy to spot.
                syncResbarAmount(resourceId, amount) {
                    const abbr = resourceAbbr[resourceId];
                    if (!abbr || amount === undefined || amount === null) return;
                    const selector = `.res-${abbr}`;
                    const chip = document.querySelector(selector);
                    const el = chip?.querySelector('.res-amount');
                    if (el) el.textContent = amount.toLocaleString('de-DE');
                    this.flashChip(chip, 'res-chip--flash');
                },

                syncEconomyAp(amount) {
                    if (amount === undefined) return;
                    const chip = document.getElementById('resbar-ap-economy');
                    const el = chip?.querySelector('.res-amount');
                    if (el) el.textContent = amount;
                    this.flashChip(chip, 'ap-chip--flash');
                },

                flashChip(chip, flashClass) {
                    if (!chip) return;
                    clearTimeout(chip._flashTimer);
                    chip.classList.remove(flashClass);
                    void chip.offsetWidth; // force reflow so the animation restarts mid-flash
                    chip.classList.add(flashClass);
                    chip._flashTimer = setTimeout(() => chip.classList.remove(flashClass), 700);
                },

                // Updates the "Du gibst/bekommst" chip amounts inside a specific
                // offer's dialog to the negotiated values — the real terms now differ
                // from what was originally displayed when the dialog was rendered.
                updateOfferChipAmounts(btn, giveAmount, getAmount) {
                    const dialog = btn.closest('.cantina-dialog');
                    if (!dialog) return;
                    const amounts = dialog.querySelectorAll('.res-amount');
                    if (amounts[0]) amounts[0].textContent = giveAmount;
                    if (amounts[1]) amounts[1].textContent = getAmount;
                    this.flashChip(amounts[0]?.closest('.res-chip'), 'res-chip--flash');
                    this.flashChip(amounts[1]?.closest('.res-chip'), 'res-chip--flash');
                },

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

                // Orin (corporate_rep) — Harvester second-instance offer, Weg A (GDD §4c,
                // freigegeben 2026-08-05). Stateless server-side (CorporateContactService
                // re-derives the offer on every read and on purchase), so a plain GET on
                // load is enough — no visits table to reconcile with, unlike the Merchant.
                corporateContactOffer: null,
                corporateContactBuying: false,

                init() {
                    this.loadCorporateContactOffer();
                },

                async loadCorporateContactOffer() {
                    try {
                        const res = await fetch(corporateContactOfferRoute, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        this.corporateContactOffer = data.offer ?? null;
                    } catch {
                        this.corporateContactOffer = null;
                    }
                },

                openCorporateContact() {
                    this.activeModal = 'corporate_contact';
                },

                async buyCorporateContact() {
                    if (!this.corporateContactOffer || this.corporateContactBuying) return;
                    this.corporateContactBuying = true;
                    try {
                        const res = await fetch(corporateContactBuyRoute, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({}),
                        });
                        const data = await res.json();
                        if (data.ok) {
                            this.corporateContactOffer = null;
                            this.closeModal();
                            this.syncResbarAmount(1, data.credits);
                            this.showToast(@json(__("colony.merchant_buy_success")), 'info');
                        } else {
                            // Fallback map for older/unmapped error codes — the backend
                            // sends a pre-translated `message` for every current case.
                            const messages = {
                                corporate_contact_offer_unavailable: @json(__("colony.error_corporate_contact_offer_unavailable")),
                                insufficient_credits: @json(__("colony.error_insufficient_credits")),
                            };
                            this.showToast(
                                data.message ?? messages[data.error] ?? @json(__("colony.merchant_buy_error")),
                                'error',
                            );
                        }
                    } catch {
                        this.showToast(@json(__("colony.merchant_buy_error")), 'error');
                    } finally {
                        this.corporateContactBuying = false;
                    }
                },

                // AP-type accent for the dialog border — both offers and the merchant
                // currently always cost economy AP. Kept as a seam for future event
                // types (e.g. a Nav-AP "investigate lead" encounter).
                get dialogApType() {
                    if (this.activeModal === null) return 'neutral';
                    return 'economy';
                },

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
                            this.syncResbarAmount(data.give_resource_id, data.give_resource_amount);
                            this.syncResbarAmount(data.get_resource_id, data.get_resource_amount);
                            this.syncEconomyAp(data.economy_ap);
                        } else {
                            this.error[offerId] = data.error ?? 'Fehler';
                        }
                    } catch {
                        this.error[offerId] = 'Verbindungsfehler';
                    } finally {
                        this.loading = false;
                    }
                },

                // Cantina-Verhandlung — improves the offer's terms, costs more AP, and
                // can fail (offer lost entirely). A successful negotiation does NOT
                // execute the trade — the player still confirms with Annehmen.
                async negotiate(offerId, btn) {
                    this.loading = true;
                    this.error[offerId] = null;
                    try {
                        const res = await fetch(negotiateRoute.replace('__OFFER__', offerId), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (data.ok && data.success) {
                            this.negotiated[offerId] = true;
                            // No resources move here (see backend docblock) — only AP.
                            this.syncEconomyAp(data.economy_ap);
                            this.updateOfferChipAmounts(btn, data.give_amount, data.get_amount);
                            this.showToast(@js(__("colony.bar_offer_negotiate_success")), 'info');
                        } else if (data.ok && !data.success) {
                            this.negotiateResult[offerId] = 'failed';
                            this.syncEconomyAp(data.economy_ap);
                            this.showToast(@js(__("colony.bar_offer_negotiate_failed")), 'error');
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
