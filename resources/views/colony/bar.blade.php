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

        // Cantina-Begegnungspool (GDD §12 Kanal 1, A35) — shares the same hotspot
        // rotation as the offers above, taking the next slot after them.
        $encounterSlug = match ($encounter->type ?? null) {
            "wager" => "gambler",
            "auction" => "scrap_dealer",
            default => null,
        };
        $encounterChar = $encounterSlug ? config("characters.{$encounterSlug}") : null;
        $encounterName = $encounterChar["name"] ?? __("colony.bar_encounter_contract_heading");
        $encounterHeading = match ($encounter->type ?? null) {
            "wager" => __("colony.bar_encounter_wager_heading"),
            "auction" => __("colony.bar_encounter_auction_heading"),
            "contract" => __("colony.bar_encounter_contract_heading"),
            default => null,
        };
        $encounterBody = match ($encounter->type ?? null) {
            "wager" => __("colony.bar_encounter_wager_body"),
            "auction" => __("colony.bar_encounter_auction_body"),
            "contract" => __("colony.bar_encounter_contract_body"),
            default => null,
        };
        $encounterSlot = $spotForOffer[$offers->count() % count($spotForOffer)];

        // story_hook Cantina-Begegnung (A36) — pure Flavor, kein Ressourcen-/
        // Credits-Effekt. Nimmt den nächsten Slot nach Angeboten + Begegnungspool.
        $storyChar = $storyEncounterSlug ? config("characters.{$storyEncounterSlug}") : null;
        $storySlot = $spotForOffer[($offers->count() + ($encounter ? 1 : 0)) % count($spotForOffer)];

        // Charakter-Anliegen (A41) — third occupant of the shared rotation,
        // next slot after offers + Begegnungspool + story_hook.
        $concernChar = $concern ? config("characters.{$concern->character_slug}") : null;
        $concernSlot =
            $spotForOffer[($offers->count() + ($encounter ? 1 : 0) + ($storyChar ? 1 : 0)) % count($spotForOffer)];

        // Deva & Lenn Vier-Ausgänge-Pool (A42) — fourth occupant. Not gated by
        // the "1 special event per tick" rule the others share, so it can
        // coexist with any of the above.
        $informationChar = $informationEncounter ? config("characters.{$informationEncounter->character_slug}") : null;
        $informationSlot =
            $spotForOffer[
                ($offers->count() + ($encounter ? 1 : 0) + ($storyChar ? 1 : 0) + ($concern ? 1 : 0)) %
                    count($spotForOffer)
            ];

        // Bundled extra config for barPage() (A40/A41/A42) — kept as a single
        // JSON blob (rather than more positional x-data args) to keep the
        // already-long barPage(...) call signature readable.
        $barPageExtra = [
            "talkToBartenderRoute" => route("colony.bar.talk-to-bartender"),
            "resolveConcernRoute" => route("colony.bar.resolve-concern", ["concern" => "__CONCERN__"]),
            "resolveInformationRoute" => route("colony.bar.resolve-information", [
                "encounter" => "__INFO_ENCOUNTER__",
            ]),
            "concernId" => $concern?->id,
            "concernCharacterSlug" => $concern?->character_slug,
            "informationEncounterId" => $informationEncounter?->id,
            "informationCharacterSlug" => $informationEncounter?->character_slug,
            "informationOutcomeKey" => $informationEncounter?->outcome_key,
            "knowledgeOptions" => $knowledgeOptions,
            "devaKnowledgeChoices" => $devaKnowledgeChoices,
            "resourceLabels" => $resourceLabels,
            "negotiateFailedTemplate" => __("colony.bar_offer_negotiate_failed"),
        ];
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
    @json(route("colony.corporate-contact.buy-harvester")),
    @json($encounter?->id),
    @json(route("colony.bar.accept-encounter", ["encounter" => "__ENCOUNTER__"])),
    @json($barPageExtra)
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
                            $char = $char && ($char["game_role"] ?? null) === "story_hook" ? null : $char;
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

                    {{-- Cantina-Begegnungspool Hotspot (GDD §12 Kanal 1, A35) — shared
                     event slot, at most one active encounter per colony at a time. --}}
                    @if ($encounter)
                        <button
                            class="cantina-hotspot{{ $encounterChar ? " has-portrait" : "" }} hs-slot-{{ $encounterSlot }}"
                            @click="openEncounter()">
                            <span class="hotspot-pulse"></span>
                            @if ($encounterChar)
                                <img class="hotspot-portrait"
                                    src="{{ asset("img/characters/" . $encounterSlug . ".webp") }}"
                                    srcset="{{ asset("img/characters/" . $encounterSlug . ".webp") }} 1x, {{ asset("img/characters/" . $encounterSlug . "_lg.webp") }} 2x"
                                    alt="{{ $encounterName }}">
                            @else
                                <i class="bi bi-briefcase"></i>
                            @endif
                            <span class="hotspot-label">{{ $encounterName }}</span>
                        </button>
                    @endif

                    {{-- story_hook Cantina-Begegnung (A36) — reiner Flavor-Moment,
                     kein Ressourcen-/Credits-Effekt. --}}
                    @if ($storyChar)
                        <button class="cantina-hotspot has-portrait hs-slot-{{ $storySlot }}"
                            @click="openStoryEncounter()">
                            <span class="hotspot-pulse"></span>
                            <img class="hotspot-portrait"
                                src="{{ asset("img/characters/" . $storyEncounterSlug . ".webp") }}"
                                srcset="{{ asset("img/characters/" . $storyEncounterSlug . ".webp") }} 1x, {{ asset("img/characters/" . $storyEncounterSlug . "_lg.webp") }} 2x"
                                alt="{{ $storyChar["name"] ?? "???" }}">
                            <span class="hotspot-label">{{ $storyChar["name"] ?? "???" }}</span>
                        </button>
                    @endif

                    {{-- Charakter-Anliegen (A41, GDD §12 Kanal 1) — second, independent
                     Cantina special-event slot; at most one open concern at a time. --}}
                    @if ($concern)
                        <button
                            class="cantina-hotspot{{ $concernChar ? " has-portrait" : "" }} hs-slot-{{ $concernSlot }}"
                            @click="openConcern()">
                            <span class="hotspot-pulse"></span>
                            @if ($concernChar)
                                <img class="hotspot-portrait"
                                    src="{{ asset("img/characters/" . $concern->character_slug . ".webp") }}"
                                    srcset="{{ asset("img/characters/" . $concern->character_slug . ".webp") }} 1x, {{ asset("img/characters/" . $concern->character_slug . "_lg.webp") }} 2x"
                                    alt="{{ $concernChar["name"] ?? "???" }}">
                            @else
                                <i class="bi bi-chat-dots"></i>
                            @endif
                            <span
                                class="hotspot-label">{{ $concernChar["name"] ?? __("colony.bar_concern_heading") }}</span>
                        </button>
                    @endif

                    {{-- Deva & Lenn Vier-Ausgänge-Pool (A42, GDD §12 "Deva & Lenn —
                     taktische Information") — third, independent channel, not gated by
                     the "1 special event per tick" rule the two pools above share. --}}
                    @if ($informationEncounter)
                        <button
                            class="cantina-hotspot{{ $informationChar ? " has-portrait" : "" }} hs-slot-{{ $informationSlot }}"
                            @click="openInformationEncounter()">
                            <span class="hotspot-pulse"></span>
                            @if ($informationChar)
                                <img class="hotspot-portrait"
                                    src="{{ asset("img/characters/" . $informationEncounter->character_slug . ".webp") }}"
                                    srcset="{{ asset("img/characters/" . $informationEncounter->character_slug . ".webp") }} 1x, {{ asset("img/characters/" . $informationEncounter->character_slug . "_lg.webp") }} 2x"
                                    alt="{{ $informationChar["name"] ?? "???" }}">
                            @else
                                <i class="bi bi-broadcast"></i>
                            @endif
                            <span
                                class="hotspot-label">{{ $informationChar["name"] ?? __("colony.bar_information_heading") }}</span>
                        </button>
                    @endif

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
                @if ($offers->isEmpty() && $merchantVisit === null && !$encounter && !$concern && !$informationEncounter)
                    <div class="cantina-empty-hint">
                        <p style="margin:0; font-size: 0.9rem; font-weight:500;">{{ __("colony.bar_no_offers") }}</p>
                    </div>
                @endif

            </div>

            {{-- Marktbericht (Konsul, A13, GDD §12): Konsul announces Corvan's next visit.
             Pure planning info — read-only, no action, costs nothing. --}}
            @if ($merchantForecast !== null)
                <div class="market-report">
                    <i class="bi bi-megaphone" aria-hidden="true"></i>
                    <div class="market-report__text">
                        <strong>{{ __("colony.merchant_forecast_title") }}</strong>
                        <span>
                            {{ $merchantForecast["sols"] === 1 ? __("colony.merchant_forecast_tomorrow") : __("colony.merchant_forecast_in_sols", ["sols" => $merchantForecast["sols"]]) }}
                        </span>
                        @if ($merchantForecast["categories"] !== null)
                            <span>
                                {{ __("colony.merchant_forecast_inventory", ["categories" => collect($merchantForecast["categories"])->map(fn($category) => __("colony.merchant_category_" . $category))->implode(", ")]) }}
                            </span>
                        @endif
                    </div>
                </div>
            @endif

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

            {{-- Tomas (bartender) — permanent Cantina fixture (A40, GDD §12), no
             random spawn. A fixed banner like Orin's above, not a rotating
             hotspot — Tomas is always here, so there's no "next slot" logic
             that applies to him. --}}
            <div class="corporate-contact-banner" @click="openBartender()">
                <img class="corporate-contact-banner__portrait" src="{{ asset("img/characters/bartender.webp") }}"
                    alt="{{ config("characters.bartender.name") }}">
                <div class="corporate-contact-banner__text">
                    <strong>{{ config("characters.bartender.name") }}</strong>
                    <span>{{ __("colony.bartender_talk_button") }}</span>
                </div>
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
                            <div x-show="toast.visible" x-transition
                                :class="'merchant-toast merchant-toast--' + toast.type" x-text="toast.message"
                                aria-live="polite" role="status"></div>

                            <div class="merchant-items-bar"
                                style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                                <template x-for="item in merchantItems" :key="item.id">
                                    <article class="merchant-item-bar" :class="{ 'merchant-item-bar--sold': item.sold }">
                                        <div class="merchant-item-bar__label" x-text="item.label" :title="item.label">
                                        </div>
                                        <div class="merchant-item-bar__row">
                                            <span class="res-chip res-Cr">
                                                <span class="res-abbr">Cr</span>
                                                <span class="res-amount" x-text="item.price_credits"></span>
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
                        $terms = $offerTerms[$offerId];
                        $isCorvanOffer = $offer->visit_id !== null;
                        $hsSlot = $spotForOffer[$idx % count($spotForOffer)];
                        $char = $isCorvanOffer ? null : $characterAssignment[$hsSlot] ?? null;
                        $char = $char && ($char["game_role"] ?? null) === "story_hook" ? null : $char;
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
// bar_trade Charakter-Zuordnung (A36) — personalisierte Zeile,
// kein neuer Mechanismus, nur Flavor zusätzlich zum Angebot.
$offerFlavorKey =
    !$isCorvanOffer && ($char["game_role"] ?? null) === "bar_trade"
        ? "colony.bar_trade_flavor_" . $offerCharSlug
        : null;
// A42: only forward the slug for an actually-assigned, non-Corvan
// character — BarService::acceptOffer() itself filters by
// game_role === 'bar_trade' before crediting the codex, so passing
                        // any assigned slug here (not just bar_trade ones) is safe.
                        $offerAcceptCharSlug = !$isCorvanOffer && $char ? $char["slug"] : null;
                    @endphp
                    <div x-show="activeModal === 'offer_{{ $offerId }}'">
                        <x-cantina-dialog :portrait-src="$offerPortraitSrc" :portrait-lg-src="$offerPortraitLgSrc" :name="$name" :role="$role">
                            {{-- Toast feedback --}}
                            <div x-show="toast.visible" x-transition
                                :class="'merchant-toast merchant-toast--' + toast.type" x-text="toast.message"
                                aria-live="polite" role="status"></div>

                            @if ($offerFlavorKey)
                                <p style="font-style:italic;color:var(--pico-muted-color)">
                                    {{ __($offerFlavorKey) }}
                                </p>
                            @endif

                            <div
                                style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:0.75rem;background: #f7f7f5;padding:0.75rem 1rem;border-radius:6px;border:1px solid var(--pico-muted-border-color)">
                                <div>
                                    <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                        {{ __("colony.bar_offer_give") }}
                                    </div>
                                    @include("partials.res_chip", [
                                        "abbreviation" => $resourceAbbr[$offer->give_resource_id] ?? "?",
                                        "amount" => $terms["give_amount"],
                                    ])
                                </div>
                                <span style="font-size:1.5rem;color:var(--pico-muted-color)">→</span>
                                <div>
                                    <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                        {{ __("colony.bar_offer_get") }}
                                    </div>
                                    @include("partials.res_chip", [
                                        "abbreviation" => $resourceAbbr[$offer->get_resource_id] ?? "?",
                                        "amount" => $terms["get_amount"],
                                    ])
                                </div>
                            </div>

                            <div
                                style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                                <small style="color:var(--pico-muted-color)">
                                    {{ __("colony.bar_offer_expires") }} {{ $offer->expires_tick }}
                                </small>
                                <div style="display:flex;gap:0.5rem">
                                    @if ($hasConsul && !$terms["fixed_price"])
                                        <button class="tile-action-btn tile-action-btn--secondary" style="width:auto;"
                                            @click='negotiate({{ $offerId }}, $el, @json($name))'
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
                                        @click='accept({{ $offerId }}, @json($offerAcceptCharSlug), $el)'
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
                                x-text="negotiateFailedText[{{ $offerId }}]"
                                style="color:var(--pico-del-color);font-size:0.85rem"></div>
                            <div x-show="error[{{ $offerId }}]" x-text="error[{{ $offerId }}]"
                                style="color:var(--pico-del-color);font-size:0.85rem"></div>
                        </x-cantina-dialog>
                    </div>
                @endforeach

                {{-- Cantina-Begegnungspool dialog (GDD §12 Kanal 1, A35) --}}
                @if ($encounter)
                    @php
                        $encounterPortraitSrc = asset("img/characters/" . ($encounterSlug ?? "stranger") . ".webp");
                        $encounterPortraitLgSrc = asset(
                            "img/characters/" . ($encounterSlug ?? "stranger") . "_lg.webp",
                        );
                        $encounterRole = $encounterChar["role"] ?? "";
                    @endphp
                    <div x-show="activeModal === 'encounter'">
                        <x-cantina-dialog :portrait-src="$encounterPortraitSrc" :portrait-lg-src="$encounterPortraitLgSrc" :name="$encounterHeading" :role="$encounterRole">
                            <div x-show="toast.visible" x-transition
                                :class="'merchant-toast merchant-toast--' + toast.type" x-text="toast.message"
                                aria-live="polite" role="status"></div>

                            <p>{{ $encounterBody }}</p>

                            @if ($encounter->type === "wager")
                                <div
                                    style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:0.75rem;background: #f7f7f5;padding:0.75rem 1rem;border-radius:6px;border:1px solid var(--pico-muted-border-color)">
                                    <div>
                                        <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                            {{ __("colony.bar_offer_give") }}
                                        </div>
                                        @include("partials.res_chip", [
                                            "abbreviation" => $resourceAbbr[$encounter->give_resource_id] ?? "?",
                                            "amount" => $encounter->give_amount,
                                        ])
                                    </div>
                                    <span style="font-size:1.5rem;color:var(--pico-muted-color)">→</span>
                                    <div>
                                        <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                            {{ __("colony.bar_offer_get") }}
                                        </div>
                                        @include("partials.res_chip", [
                                            "abbreviation" => "Cr",
                                            "amount" => $encounter->credits_amount,
                                        ])
                                    </div>
                                </div>
                            @elseif ($encounter->type === "auction")
                                <div
                                    style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:0.75rem;background: #f7f7f5;padding:0.75rem 1rem;border-radius:6px;border:1px solid var(--pico-muted-border-color)">
                                    <div>
                                        <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                            {{ __("colony.bar_offer_give") }}
                                        </div>
                                        @include("partials.res_chip", [
                                            "abbreviation" => $resourceAbbr[$encounter->give_resource_id] ?? "?",
                                            "amount" => $encounter->give_amount,
                                        ])
                                    </div>
                                    <span style="font-size:1.5rem;color:var(--pico-muted-color)">→</span>
                                    <div>
                                        <div style="font-size:0.75rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                                            {{ __("colony.bar_offer_get") }}
                                        </div>
                                        @include("partials.res_chip", [
                                            "abbreviation" => "Cr",
                                            "amount" => $encounter->credits_amount,
                                        ])
                                    </div>
                                </div>
                            @else
                                <div class="res-chip res-Cr" style="display:inline-flex">
                                    <span class="res-abbr">Cr</span>
                                    <span class="res-amount">{{ $encounter->credits_amount }}/Sol</span>
                                </div>
                            @endif

                            <div style="display:flex;justify-content:flex-end;align-items:center;gap:1rem;flex-wrap:wrap">
                                <button class="tile-action-btn" style="width:auto;" @click="acceptEncounter($el)"
                                    :disabled="encounterResolved || loading">
                                    <span class="tile-action-btn__body">
                                        <span x-show="!encounterResolved">{{ __("colony.bar_encounter_accept") }}</span>
                                        <span x-show="encounterResolved">✓</span>
                                    </span>
                                </button>
                            </div>
                            <div x-show="encounterResult === 'won'" style="color:#166534;font-size:0.85rem">
                                {{ __("colony.bar_encounter_wager_won") }}
                            </div>
                            <div x-show="encounterResult === 'lost'"
                                style="color:var(--pico-del-color);font-size:0.85rem">
                                {{ __("colony.bar_encounter_wager_lost") }}
                            </div>
                            <div x-show="encounterError" x-text="encounterError"
                                style="color:var(--pico-del-color);font-size:0.85rem"></div>
                        </x-cantina-dialog>
                    </div>
                @endif

                {{-- story_hook Cantina-Begegnung (A36) — reiner Flavor-Moment,
                 kein Ressourcen-/Credits-Effekt, nur zum Wegklicken. --}}
                @if ($storyChar)
                    @php
                        $storyPortraitSrc = asset("img/characters/" . $storyEncounterSlug . ".webp");
                        $storyPortraitLgSrc = asset("img/characters/" . $storyEncounterSlug . "_lg.webp");
                        $storyName = $storyChar["name"] ?? "???";
                        $storyRole = $storyChar["role"] ?? "";
                        $storyBodyKey = "colony.story_encounter_" . $storyEncounterSlug;
                    @endphp
                    <div x-show="activeModal === 'story'">
                        <x-cantina-dialog :portrait-src="$storyPortraitSrc" :portrait-lg-src="$storyPortraitLgSrc" :name="$storyName" :role="$storyRole">
                            <p>{{ __($storyBodyKey) }}</p>
                            <div style="display:flex;justify-content:flex-end">
                                <button class="tile-action-btn tile-action-btn--secondary" style="width:auto;"
                                    @click="closeModal()">
                                    <span class="tile-action-btn__body">{{ __("colony.story_encounter_close") }}</span>
                                </button>
                            </div>
                        </x-cantina-dialog>
                    </div>
                @endif

                {{-- Tomas (bartender) dialog — A40, GDD §12. --}}
                @php
                    $bartenderPortraitSrc = asset("img/characters/bartender.webp");
                    $bartenderPortraitLgSrc = asset("img/characters/bartender_lg.webp");
                    $bartenderName = config("characters.bartender.name");
                    $bartenderRole = config("characters.bartender.role");
                @endphp
                <div x-show="activeModal === 'bartender'">
                    <x-cantina-dialog :portrait-src="$bartenderPortraitSrc" :portrait-lg-src="$bartenderPortraitLgSrc" :name="$bartenderName" :role="$bartenderRole">
                        <p x-show="bartenderResultTier === null">{{ __("colony.bartender_dialog_intro") }}</p>

                        <template x-for="tier in [0, 1, 2, 3]" :key="tier">
                            <p x-show="bartenderResultTier === tier"
                                x-text="{{ json_encode([
                                    0 => __("colony.bartender_dialog_tier_0"),
                                    1 => __("colony.bartender_dialog_tier_1"),
                                    2 => __("colony.bartender_dialog_tier_2"),
                                    3 => __("colony.bartender_dialog_tier_3"),
                                ]) }}[tier]">
                            </p>
                        </template>

                        <div x-show="bartenderResultTier === null" style="display:flex;flex-direction:column;gap:0.5rem">
                            <label style="font-size:0.85rem;color:var(--pico-muted-color)">
                                {{ __("colony.bartender_knowledge_label") }}
                                <select x-model.number="bartenderKnowledgeId">
                                    <template x-for="opt in knowledgeOptions" :key="opt.id">
                                        <option :value="opt.id" x-text="opt.name"></option>
                                    </template>
                                </select>
                            </label>
                            <div style="display:flex;justify-content:flex-end">
                                <button class="tile-action-btn" style="width:auto;" @click="talkToBartender()"
                                    :disabled="bartenderLoading || !bartenderKnowledgeId">
                                    <span class="tile-action-btn__body">{{ __("colony.bartender_talk_button") }}</span>
                                </button>
                            </div>
                        </div>

                        <div x-show="bartenderResultTier !== null" style="display:flex;justify-content:flex-end">
                            <button class="tile-action-btn tile-action-btn--secondary" style="width:auto;"
                                @click="closeModal()">
                                <span class="tile-action-btn__body">{{ __("colony.story_encounter_close") }}</span>
                            </button>
                        </div>

                        <div x-show="bartenderError" x-text="bartenderError"
                            style="color:var(--pico-del-color);font-size:0.85rem"></div>
                    </x-cantina-dialog>
                </div>

                {{-- Charakter-Anliegen dialog — A41, GDD §12 Kanal 1. --}}
                @if ($concern)
                    @php
                        $concernPortraitSrc = asset("img/characters/" . $concern->character_slug . ".webp");
                        $concernPortraitLgSrc = asset("img/characters/" . $concern->character_slug . "_lg.webp");
                        $concernName = $concernChar["name"] ?? __("colony.bar_concern_heading");
                        $concernRole = $concernChar["role"] ?? "";
                        $concernIntroKey = "colony.bar_concern_" . $concern->character_slug . "_intro";
                        $concernSuccessKey = "colony.bar_concern_" . $concern->character_slug . "_success";
                        $concernFailureKey = "colony.bar_concern_" . $concern->character_slug . "_failure";
                        $concernHasFailureLine = Lang::has($concernFailureKey);
                    @endphp
                    <div x-show="activeModal === 'concern'">
                        <x-cantina-dialog :portrait-src="$concernPortraitSrc" :portrait-lg-src="$concernPortraitLgSrc" :name="$concernName" :role="$concernRole">
                            <p x-show="!concernResolved">{{ __($concernIntroKey) }}</p>

                            <div x-show="!concernResolved && concernCharacterSlug === 'mechanic'"
                                style="margin-bottom:0.75rem">
                                <label style="font-size:0.85rem;color:var(--pico-muted-color)">
                                    {{ __("colony.bar_concern_knowledge_label") }}
                                    <select x-model.number="concernKnowledgeId">
                                        <template x-for="opt in knowledgeOptions" :key="opt.id">
                                            <option :value="opt.id" x-text="opt.name"></option>
                                        </template>
                                    </select>
                                </label>
                            </div>

                            <div x-show="!concernResolved"
                                style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                                @include("partials.ap-cost-chip", [
                                    "type" => "economy",
                                    "amount" => $concernApCost,
                                ])
                                <button class="tile-action-btn" style="width:auto;" @click="resolveConcern()"
                                    :disabled="concernLoading">
                                    <span class="tile-action-btn__body">{{ __("colony.bar_concern_resolve") }}</span>
                                </button>
                            </div>

                            <p x-show="concernResolved && concernSuccess === true" style="color:#166534">
                                {{ __($concernSuccessKey) }}</p>
                            @if ($concernHasFailureLine)
                                <p x-show="concernResolved && concernSuccess === false"
                                    style="color:var(--pico-del-color)">{{ __($concernFailureKey) }}</p>
                            @else
                                <p x-show="concernResolved && concernSuccess === false" style="color:#166534">
                                    {{ __($concernSuccessKey) }}</p>
                            @endif

                            <div x-show="concernResolved" style="display:flex;justify-content:flex-end">
                                <button class="tile-action-btn tile-action-btn--secondary" style="width:auto;"
                                    @click="closeModal()">
                                    <span class="tile-action-btn__body">{{ __("colony.story_encounter_close") }}</span>
                                </button>
                            </div>

                            <div x-show="concernError" x-text="concernError"
                                style="color:var(--pico-del-color);font-size:0.85rem"></div>
                        </x-cantina-dialog>
                    </div>
                @endif

                {{-- Deva & Lenn Vier-Ausgänge-Pool dialog — A42, GDD §12
                 "Deva & Lenn — taktische Information". --}}
                @if ($informationEncounter)
                    @php
                        $informationPortraitSrc = asset(
                            "img/characters/" . $informationEncounter->character_slug . ".webp",
                        );
                        $informationPortraitLgSrc = asset(
                            "img/characters/" . $informationEncounter->character_slug . "_lg.webp",
                        );
                        $informationName = $informationChar["name"] ?? __("colony.bar_information_heading");
                        $informationRole = $informationChar["role"] ?? "";
                        $informationSlug = $informationEncounter->character_slug;
                        $informationOutcomeLines = [];
                        foreach (
                            ["drill_buffer", "knowledge_boost", "nav_discount", "narrative_1", "narrative_2"]
                            as $outcomeKey
                        ) {
                            $lineKey = "colony.bar_information_{$informationSlug}_{$outcomeKey}";
                            if (Lang::has($lineKey)) {
                                $informationOutcomeLines[$outcomeKey] = __($lineKey);
                            }
                        }
                    @endphp
                    <div x-show="activeModal === 'information'">
                        <x-cantina-dialog :portrait-src="$informationPortraitSrc" :portrait-lg-src="$informationPortraitLgSrc" :name="$informationName" :role="$informationRole">
                            <div x-show="!informationResolved && informationNeedsKnowledgeChoice"
                                style="margin-bottom:0.75rem">
                                <label style="font-size:0.85rem;color:var(--pico-muted-color)">
                                    {{ __("colony.bar_information_knowledge_label") }}
                                    <select x-model.number="informationKnowledgeId">
                                        <template x-for="opt in devaKnowledgeChoices" :key="opt.id">
                                            <option :value="opt.id" x-text="opt.name"></option>
                                        </template>
                                    </select>
                                </label>
                            </div>

                            <div x-show="!informationResolved" style="display:flex;justify-content:flex-end">
                                <button class="tile-action-btn" style="width:auto;"
                                    @click="resolveInformationEncounter()" :disabled="informationLoading">
                                    <span class="tile-action-btn__body">{{ __("colony.bar_information_resolve") }}</span>
                                </button>
                            </div>

                            <template x-if="informationResolved">
                                <p x-text="{{ json_encode($informationOutcomeLines) }}[informationOutcome?.outcome_key]">
                                </p>
                            </template>

                            <div x-show="informationResolved" style="display:flex;justify-content:flex-end">
                                <button class="tile-action-btn tile-action-btn--secondary" style="width:auto;"
                                    @click="closeModal()">
                                    <span class="tile-action-btn__body">{{ __("colony.story_encounter_close") }}</span>
                                </button>
                            </div>

                            <div x-show="informationError" x-text="informationError"
                                style="color:var(--pico-del-color);font-size:0.85rem"></div>
                        </x-cantina-dialog>
                    </div>
                @endif
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
            offersCount = 0, corporateContactOfferRoute, corporateContactBuyRoute, encounterId = null,
            acceptEncounterRoute, extra = {}) {
            const hasGuests = (merchantVisit !== null) || (merchantItems && merchantItems.length > 0) || offersCount > 0;
            const panelCount = hasGuests ? 4 : 1;

            return {
                // Inherit swipe carousel properties & methods from swipe.js
                ...swipeCarousel(panelCount, 0),

                // Offers state
                accepted: {},
                negotiated: {}, // offerId -> true once a negotiation succeeded (Get-side bonus applies)
                negotiateResult: {}, // offerId -> 'failed' (negotiation lost the offer entirely)
                negotiateFailedText: {}, // offerId -> failure message naming the facts (who, what stays, AP spent)
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

                // Syncs the single shared AP pool chip (#resbar-ap, GDD §13.1) — no
                // longer a per-domain chip, so this now updates the one global AP chip.
                syncAp(amount) {
                    if (amount === undefined) return;
                    const chip = document.getElementById('resbar-ap');
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

                // Cantina-Begegnungspool (GDD §12 Kanal 1, A35)
                encounterId: encounterId,
                encounterResolved: false,
                encounterResult: null, // 'won' | 'lost' once a wager resolves
                encounterError: null,

                openEncounter() {
                    this.activeModal = 'encounter';
                },

                openStoryEncounter() {
                    this.activeModal = 'story';
                },

                async acceptEncounter(btn) {
                    if (!this.encounterId) return;
                    this.loading = true;
                    this.encounterError = null;
                    try {
                        const res = await fetch(acceptEncounterRoute.replace('__ENCOUNTER__', this.encounterId), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (data.ok) {
                            this.encounterResolved = true;
                            if (data.type === 'wager') {
                                this.encounterResult = data.won ? 'won' : 'lost';
                            }
                            this.syncAp(data.ap_available);
                            this.syncResbarAmount(1, data.credits_balance);
                            if (data.give_resource_id) {
                                this.syncResbarAmount(data.give_resource_id, data.give_resource_amount);
                            }
                        } else {
                            this.encounterError = data.error ?? 'Fehler';
                        }
                    } catch {
                        this.encounterError = 'Verbindungsfehler';
                    } finally {
                        this.loading = false;
                    }
                },

                closeModal() {
                    this.activeModal = null;
                },

                // ── Tomas — Cantina-Barkeeper (A40) ─────────────────────────────
                knowledgeOptions: extra.knowledgeOptions ?? [],
                bartenderKnowledgeId: (extra.knowledgeOptions ?? [])[0]?.id ?? null,
                bartenderLoading: false,
                bartenderResultTier: null, // 0-3 once "Reden" succeeds
                bartenderError: null,

                openBartender() {
                    this.activeModal = 'bartender';
                    this.bartenderResultTier = null;
                    this.bartenderError = null;
                },

                async talkToBartender() {
                    if (this.bartenderLoading || !this.bartenderKnowledgeId) return;
                    this.bartenderLoading = true;
                    this.bartenderError = null;
                    try {
                        const res = await fetch(extra.talkToBartenderRoute, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                knowledge_id: this.bartenderKnowledgeId
                            }),
                        });
                        const data = await res.json();
                        if (data.ok) {
                            // ap_added tiers (0/1/2/3) map 1:1 onto the 4
                            // bartender_dialog_tier_* lines (config('game.bartender.ap_bonus_tiers')).
                            this.bartenderResultTier = Math.min(3, data.ap_added ?? 0);
                        } else if (data.error === 'cooldown_active') {
                            this.bartenderError = @js(__("colony.bartender_error_cooldown"));
                        } else {
                            this.bartenderError = @js(__("colony.bartender_error_generic"));
                        }
                    } catch {
                        this.bartenderError = @js(__("colony.bartender_error_generic"));
                    } finally {
                        this.bartenderLoading = false;
                    }
                },

                // ── Charakter-Anliegen (A41) ─────────────────────────────────────
                concernId: extra.concernId ?? null,
                concernCharacterSlug: extra.concernCharacterSlug ?? null,
                concernKnowledgeId: (extra.knowledgeOptions ?? [])[0]?.id ?? null,
                concernResolved: false,
                concernSuccess: null,
                concernOutcome: null,
                concernError: null,
                concernLoading: false,

                openConcern() {
                    this.activeModal = 'concern';
                },

                async resolveConcern() {
                    if (!this.concernId || this.concernLoading) return;
                    this.concernLoading = true;
                    this.concernError = null;
                    try {
                        const res = await fetch(extra.resolveConcernRoute.replace('__CONCERN__', this.concernId), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                knowledge_id: this.concernCharacterSlug === 'mechanic' ? this
                                    .concernKnowledgeId : null
                            }),
                        });
                        const data = await res.json();
                        if (data.ok) {
                            this.concernResolved = true;
                            this.concernSuccess = data.success;
                            this.concernOutcome = data;
                            this.syncAp(data.ap_available);
                            this.syncResbarAmount(1, data.credits_balance);
                            this.syncResbarAmount(3, data.regolith_balance);
                            this.syncResbarAmount(4, data.compounds_balance);
                        } else {
                            this.concernError = data.error ?? 'Fehler';
                        }
                    } catch {
                        this.concernError = 'Verbindungsfehler';
                    } finally {
                        this.concernLoading = false;
                    }
                },

                // ── Deva & Lenn Vier-Ausgänge-Pool (A42) ─────────────────────────
                informationEncounterId: extra.informationEncounterId ?? null,
                informationCharacterSlug: extra.informationCharacterSlug ?? null,
                informationOutcomeKey: extra.informationOutcomeKey ?? null,
                devaKnowledgeChoices: extra.devaKnowledgeChoices ?? [],
                informationKnowledgeId: (extra.devaKnowledgeChoices ?? [])[0]?.id ?? null,
                informationResolved: false,
                informationOutcome: null,
                informationError: null,
                informationLoading: false,

                openInformationEncounter() {
                    this.activeModal = 'information';
                },

                // Only veteran's (Deva's) 'knowledge_boost' outcome needs a
                // player choice (geology vs. health) — ai_researcher's
                // (Lenn's) knowledge_boost target is fixed (cartography).
                get informationNeedsKnowledgeChoice() {
                    return this.informationCharacterSlug === 'veteran' && this.informationOutcomeKey ===
                        'knowledge_boost';
                },

                async resolveInformationEncounter() {
                    if (!this.informationEncounterId || this.informationLoading) return;
                    this.informationLoading = true;
                    this.informationError = null;
                    try {
                        const res = await fetch(
                            extra.resolveInformationRoute.replace('__INFO_ENCOUNTER__', this
                                .informationEncounterId), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    knowledge_id: this.informationNeedsKnowledgeChoice ? this
                                        .informationKnowledgeId : null
                                }),
                            });
                        const data = await res.json();
                        if (data.ok) {
                            this.informationResolved = true;
                            this.informationOutcome = data;
                        } else {
                            this.informationError = data.error ?? 'Fehler';
                        }
                    } catch {
                        this.informationError = 'Verbindungsfehler';
                    } finally {
                        this.informationLoading = false;
                    }
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

                // Bar offer accept — characterSlug (A36's shown assignment, possibly
                // null for a Corvan offer or an unassigned/story_hook hotspot) is
                // forwarded so the Charakter-Kodex (A42) can credit bar_trade figures.
                async accept(offerId, characterSlug, btn) {
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
                            body: JSON.stringify({
                                character_slug: characterSlug ?? null
                            }),
                        });
                        const data = await res.json();
                        if (data.ok) {
                            this.accepted[offerId] = true;
                            this.syncResbarAmount(data.give_resource_id, data.give_resource_amount);
                            this.syncResbarAmount(data.get_resource_id, data.get_resource_amount);
                            this.syncAp(data.ap_available);
                        } else {
                            this.error[offerId] = data.error ?? 'Fehler';
                        }
                    } catch {
                        this.error[offerId] = 'Verbindungsfehler';
                    } finally {
                        this.loading = false;
                    }
                },

                // Cantina-Verhandlung — flags the offer as negotiated (bonus on the Get side,
                // costs the same AP as Annehmen) and can fail (offer lost entirely). A successful negotiation does NOT
                // execute the trade — the player still confirms with Annehmen.
                async negotiate(offerId, btn, characterName) {
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
                            this.syncAp(data.ap_available);
                            // data.terms = what Annehmen will now execute: base x (1 + Handelsvorteil + negotiation bonus), additive
                            this.updateOfferChipAmounts(btn, data.terms.give_amount, data.terms.get_amount);
                            this.showToast(@js(__("colony.bar_offer_negotiate_success")), 'info');
                        } else if (data.ok && !data.success) {
                            this.negotiateResult[offerId] = 'failed';
                            this.syncAp(data.ap_available);
                            // Facts of the loss: nothing was traded, the Give side stays, the AP are gone.
                            const failedText = (extra.negotiateFailedTemplate ?? '')
                                .replace(':name', characterName)
                                .replace(':resource', (extra.resourceLabels ?? {})[data.give_resource_id] ?? '?')
                                .replace(':ap', data.ap_spent);
                            // Shown inline in the dialog (persistent) — no extra toast that would repeat it.
                            this.negotiateFailedText[offerId] = failedText;
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
