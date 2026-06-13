@extends("layouts.colony")
@section("title", __("colony.hangar_title") . " — Nouron")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/hangar.css") }}">
@endpush

@push("scripts")
    <script src="{{ asset("js/hangar.js") }}"></script>
@endpush

@section("content")
    <script>
        window.__hangarData = {
            slots: @json($slots),
            shipTypes: @json($shipTypes),
            commissionedShipIds: @json($commissionedShipIds),
            hasPilot: @json($hasPilot),

            {{-- New acquisition model data — controller provides these; fall back to safe defaults --}}
            shipCosts: @json($shipCosts ?? []),
            canUseNexusCredit: @json($canUseNexusCredit ?? false),
            hasAktivierterKonsul: @json($hasAktivierterKonsul ?? false),
            verfuegbareVerhandlungsAP: @json($verfuegbareVerhandlungsAP ?? 0),
            pendingShips: @json($pendingShips ?? []),

            routes: {
                dispatch: @json(route("colony.hangar.dispatch", ["instanceId" => "__ID__"])),
                recall: @json(route("colony.hangar.recall", ["instanceId" => "__ID__"])),
                repair: @json(route("colony.hangar.repair", ["instanceId" => "__ID__"])),
                request: @json(route("colony.hangar.request")),
                assign: @json(route("colony.hangar.assign")),
            },
            csrfToken: @json(csrf_token()),

            {{-- Pre-translated strings for Alpine (never hardcode German in JS) --}}
            i18n: {
                empty: @json(__("colony.hangar_empty")),
                buildShip: @json(__("colony.hangar_build_ship")),
                dispatch: @json(__("colony.hangar_dispatch")),
                recall: @json(__("colony.hangar_recall")),
                repair: @json(__("colony.hangar_repair")),
                destination: @json(__("colony.hangar_destination")),
                solDistance: @json(__("colony.hangar_sol_distance")),
                inTransit: @json(__("colony.hangar_in_transit")),
                inConstruction: @json(__("colony.hangar_in_construction")),
                pilotReady: @json(__("colony.hangar_pilot_ready")),
                status: @json(__("colony.hangar_status")),
                nexusRequest: @json(__("colony.hangar_nexus_request")),
                nexusRequestTitle: @json(__("colony.hangar_nexus_request_title")),
                nexusRequestSubmit: @json(__("colony.hangar_nexus_request_submit")),
                shipType: @json(__("colony.hangar_ship_type")),
                paymentMethod: @json(__("colony.hangar_payment_method")),
                standardPurchase: @json(__("colony.hangar_standard_purchase")),
                nexusCredit: @json(__("colony.hangar_nexus_credit")),
                nexusCreditHint: @json(__("colony.hangar_nexus_credit_hint")),
                consulApTitle: @json(__("colony.hangar_consul_ap_title")),
                deliveryPending: @json(__("colony.hangar_delivery_pending")),
                pendingSection: @json(__("colony.hangar_pending_section")),
                assignHangar: @json(__("colony.hangar_assign_hangar")),
                assignSelect: @json(__("colony.hangar_assign_select")),
                pendingExpires: @json(__("colony.hangar_pending_expires")),
                cancel: @json(__("colony.cancel")),
                shipDrone: @json(__("colony.hangar_ship_drone")),
                shipFreighter: @json(__("colony.hangar_ship_freighter")),
                shipCorvette: @json(__("colony.hangar_ship_corvette")),
            },
        };
    </script>

    <div class="hangar-page" x-data="hangarCarousel(window.__hangarData)" x-cloak>

        {{-- ── Header bar ──────────────────────────────────────────────────────── --}}
        <div class="hangar-header">
            <h2>{{ __("colony.hangar_title") }}</h2>

            @if (count($slots) > 0)
                <span class="hangar-slot-badge">
                    {{ __("colony.hangar_slot_count") }}:
                    <strong x-text="slotInfo.used + '/' + slotInfo.total"></strong>
                </span>
            @endif
        </div>

        {{-- ── No hangar built ─────────────────────────────────────────────────── --}}
        @if (count($slots) === 0)
            <p style="color: var(--pico-muted-color); margin-top: 2rem;">
                {{ __("colony.hangar_none_built") }}
            </p>
        @else
            {{-- ── Carousel ─────────────────────────────────────────────────────────── --}}
            <div class="carousel-wrapper">

                <button class="carousel-arrow" @click="prev()" :disabled="activeIndex === 0"
                    aria-label="Previous bay">&#8249;</button>

                <div class="carousel-viewport" @touchstart.passive="onTouchStart($event)"
                    @touchmove.passive="onTouchMove($event)" @touchend.passive="onTouchEnd($event)">

                    <div class="carousel-track" :style="trackStyle()">

                        <template x-for="(slot, i) in slots" :key="slot.instance_id">
                            <div class="hangar-card" :class="{ 'hangar-card--current': i === activeIndex }">

                                {{-- Icon area --}}
                                <div class="hangar-icon-area">
                                    <span class="hangar-bay-label" x-text="'Bay ' + (i + 1)"></span>

                                    {{-- Rocket icon when ship is present --}}
                                    <template x-if="slot.ship !== null">
                                        <svg class="hangar-icon-placeholder" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 2C8 2 5 8 5 14l2 2h10l2-2c0-6-3-12-7-12z" />
                                            <path d="M9 18l-2 4h10l-2-4" />
                                            <line x1="9" y1="12" x2="15" y2="12" />
                                        </svg>
                                    </template>

                                    {{-- Grid placeholder when slot is empty --}}
                                    <template x-if="slot.ship === null">
                                        <svg class="hangar-icon-placeholder" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="3" width="7" height="7" rx="1" />
                                            <rect x="14" y="3" width="7" height="7" rx="1" />
                                            <rect x="3" y="14" width="7" height="7" rx="1" />
                                            <rect x="14" y="14" width="7" height="7" rx="1" />
                                        </svg>
                                    </template>
                                </div>

                                {{-- Info panel --}}
                                <div class="hangar-info">

                                    {{-- Name row --}}
                                    <div class="hangar-name-row">
                                        <span class="hangar-name" x-text="slot.ship ? shipLabel(slot.ship.name) : '—'">
                                        </span>
                                        <template x-if="slot.ship !== null">
                                            <span class="hangar-stat-value" x-text="'Lv ' + slot.ship.level">
                                            </span>
                                        </template>
                                    </div>

                                    {{-- Subtitle --}}
                                    <template x-if="slot.ship === null">
                                        <span class="hangar-subtitle" x-text="i18n.empty"></span>
                                    </template>
                                    <template x-if="slot.ship !== null && slot.ship.ship_state === 'docked'">
                                        <span class="hangar-subtitle">{{ __("colony.hangar_status") }}: <span
                                                x-text="slot.ship.status_points + '/20'"></span></span>
                                    </template>
                                    <template x-if="slot.ship !== null && slot.ship.ship_state === 'building'">
                                        <span class="hangar-subtitle" x-text="i18n.deliveryPending"></span>
                                    </template>
                                    <template x-if="slot.ship !== null && slot.ship.ship_state === 'dispatched'">
                                        <span class="hangar-subtitle" x-text="i18n.inTransit"></span>
                                    </template>

                                    {{-- ── EMPTY SLOT ────────────────────────────────────────────── --}}
                                    <template x-if="slot.ship === null">
                                        <div style="display:contents">

                                            <div class="hangar-stat-row">
                                                <span class="hangar-stat-label">{{ __("colony.hangar_slot_count") }}</span>
                                                <span
                                                    class="hangar-status-chip hangar-status-chip--empty">{{ __("colony.hangar_empty") }}</span>
                                            </div>

                                            {{-- "Nexus anfragen" button — opens native <dialog> modal --}}
                                            <div class="hangar-nexus-request-wrap">
                                                <button class="btn-hangar-nexus-request"
                                                    @click="openRequestModal(slot.instance_id)"
                                                    :disabled="loading[slot.instance_id]">
                                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                        aria-hidden="true" class="hangar-nexus-icon">
                                                        <circle cx="10" cy="10" r="8" />
                                                        <path d="M10 6v4l3 3" />
                                                    </svg>
                                                    <span x-text="i18n.nexusRequest"></span>
                                                </button>
                                                <div class="hangar-error" x-show="error[slot.instance_id]"
                                                    x-text="error[slot.instance_id]"></div>
                                            </div>

                                        </div>
                                    </template>

                                    {{-- ── BUILDING (delivery pending) ──────────────────────────── --}}
                                    <template x-if="slot.ship !== null && slot.ship.ship_state === 'building'">
                                        <div style="display:contents">

                                            <div class="hangar-stat-row">
                                                <span class="hangar-stat-label">{{ __("colony.hangar_status") }}</span>
                                                <span class="hangar-status-chip hangar-status-chip--building"
                                                    x-text="i18n.deliveryPending">
                                                </span>
                                            </div>

                                            <div class="hangar-construction-anim">
                                                <div class="hangar-construction-bar"></div>
                                            </div>

                                            {{-- Arrival tick — shown when deliver_at_tick is present --}}
                                            <template x-if="slot.ship.deliver_at_tick != null">
                                                <div class="hangar-stat-row">
                                                    <span
                                                        class="hangar-stat-label">{{ __("colony.hangar_delivery_pending") }}</span>
                                                    <span class="hangar-stat-value hangar-arrival-tick"
                                                        x-text="'Sol ' + slot.ship.deliver_at_tick">
                                                    </span>
                                                </div>
                                            </template>

                                            {{-- No action buttons while delivery is pending --}}

                                        </div>
                                    </template>

                                    {{-- ── DOCKED ────────────────────────────────────────────────── --}}
                                    <template x-if="slot.ship !== null && slot.ship.ship_state === 'docked'">
                                        <div style="display:contents">

                                            {{-- Status bar --}}
                                            <div class="hangar-status-track">
                                                <div class="hangar-status-fill"
                                                    :style="{ width: statusBarWidth(slot.ship.status_points) }">
                                                </div>
                                            </div>

                                            {{-- Pilot badge --}}
                                            <template x-if="hasPilot">
                                                <span class="hangar-pilot-badge" x-text="i18n.pilotReady"></span>
                                            </template>

                                            <div class="hangar-stat-row">
                                                <span class="hangar-stat-label">{{ __("colony.hangar_status") }}</span>
                                                <span class="hangar-stat-value" x-text="slot.ship.status_points + '/20'">
                                                </span>
                                            </div>

                                            {{-- Dispatch form --}}
                                            <template x-if="modalType[slot.instance_id] === 'dispatch'">
                                                <div class="hangar-form">
                                                    <label>
                                                        <span x-text="i18n.destination"></span>
                                                        <input type="text" x-model="dispatchDest[slot.instance_id]"
                                                            :placeholder="i18n.destination">
                                                    </label>
                                                    <label>
                                                        <div class="form-row-label">
                                                            <span x-text="i18n.solDistance"></span>
                                                            <strong x-text="dispatchSol[slot.instance_id]"></strong>
                                                        </div>
                                                        <input type="number" min="1" max="999"
                                                            x-model="dispatchSol[slot.instance_id]">
                                                    </label>
                                                    <div class="hangar-error" x-text="error[slot.instance_id]"></div>
                                                    <div class="hangar-form-actions">
                                                        <button class="btn-hangar-action btn-hangar-action--secondary"
                                                            @click="closeModal(slot.instance_id)">
                                                            {{ __("colony.cancel") }}
                                                        </button>
                                                        <button class="btn-hangar-action"
                                                            @click="dispatch(slot.instance_id)"
                                                            :disabled="loading[slot.instance_id]">
                                                            <span
                                                                x-text="loading[slot.instance_id] ? '…' : i18n.dispatch"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Repair form --}}
                                            <template x-if="modalType[slot.instance_id] === null">
                                                <div class="hangar-card-footer">
                                                    <button class="btn-hangar-action"
                                                        @click="openModal(slot.instance_id, 'dispatch')">
                                                        {{ __("colony.hangar_dispatch") }}
                                                    </button>
                                                    <button class="btn-hangar-action btn-hangar-action--secondary"
                                                        @click="repair(slot.instance_id)"
                                                        :disabled="loading[slot.instance_id] || slot.ship.status_points >= 20">
                                                        {{ __("colony.hangar_repair") }}
                                                        @include("partials.ap-cost-chip", [
                                                            "amount" => 1,
                                                            "type" => "build",
                                                        ])
                                                    </button>
                                                </div>
                                            </template>

                                            {{-- Inline error shown outside form modals --}}
                                            <template
                                                x-if="modalType[slot.instance_id] === null && error[slot.instance_id]">
                                                <div class="hangar-error" x-text="error[slot.instance_id]"></div>
                                            </template>

                                        </div>
                                    </template>

                                    {{-- ── DISPATCHED ────────────────────────────────────────────── --}}
                                    <template x-if="slot.ship !== null && slot.ship.ship_state === 'dispatched'">
                                        <div style="display:contents">

                                            <div class="hangar-mission-info" x-show="slot.ship.active_mission !== null">
                                                <template x-if="slot.ship.active_mission">
                                                    <span>
                                                        <strong x-text="i18n.destination + ':'"></strong>
                                                        <span x-text="slot.ship.active_mission.destination"></span>
                                                        &nbsp;·&nbsp;
                                                        <strong x-text="i18n.solDistance + ':'"></strong>
                                                        <span x-text="slot.ship.active_mission.sol_distance"></span>
                                                    </span>
                                                </template>
                                            </div>

                                            <div class="hangar-stat-row">
                                                <span class="hangar-stat-label">{{ __("colony.hangar_status") }}</span>
                                                <span class="hangar-status-chip hangar-status-chip--dispatched"
                                                    x-text="i18n.inTransit">
                                                </span>
                                            </div>

                                            <div class="hangar-error" x-text="error[slot.instance_id]"></div>

                                            <div class="hangar-card-footer">
                                                <span></span>
                                                <button class="btn-hangar-action btn-hangar-action--danger"
                                                    @click="recall(slot.instance_id)"
                                                    :disabled="loading[slot.instance_id]">
                                                    <span x-text="loading[slot.instance_id] ? '…' : i18n.recall"></span>
                                                </button>
                                            </div>

                                        </div>
                                    </template>

                                </div>{{-- /.hangar-info --}}
                            </div>{{-- /.hangar-card --}}
                        </template>

                    </div>{{-- /.carousel-track --}}
                </div>{{-- /.carousel-viewport --}}

                <button class="carousel-arrow" @click="next()" :disabled="activeIndex >= slots.length - 1"
                    aria-label="Next bay">&#8250;</button>

            </div>{{-- /.carousel-wrapper --}}

            {{-- Pagination dots (mobile only) --}}
            <div class="carousel-dots">
                <template x-for="(slot, i) in slots" :key="'dot-' + i">
                    <button class="carousel-dot" :class="{ 'carousel-dot--active': i === activeIndex }" @click="goTo(i)"
                        :aria-label="'Bay ' + (i + 1)"></button>
                </template>
            </div>

            {{-- ── Pending ships section (ships without hangar assignment) ─────────── --}}
            <template x-if="pendingShips.length > 0">
                <section class="hangar-pending-section">
                    <details>
                        <summary class="hangar-pending-summary">
                            <span x-text="i18n.pendingSection + ' (' + pendingShips.length + ')'"></span>
                        </summary>

                        <div class="hangar-pending-list">
                            <template x-for="ship in pendingShips" :key="ship.id">
                                <article class="hangar-pending-card">
                                    <div class="hangar-pending-card-info">
                                        <span class="hangar-pending-name" x-text="shipLabel(ship.name)"></span>
                                        <span class="hangar-pending-expires"
                                            x-text="i18n.pendingExpires.replace(':tick', ship.expires_at_tick)">
                                        </span>
                                    </div>

                                    <div class="hangar-pending-card-actions">
                                        {{-- Assign select --}}
                                        <select class="hangar-pending-select" x-model="pendingAssignTarget[ship.id]">
                                            <option value="" x-text="i18n.assignSelect"></option>
                                            <template x-for="freeSlot in freeSlots" :key="freeSlot.instance_id">
                                                <option :value="freeSlot.instance_id"
                                                    x-text="'Bay ' + (slots.indexOf(freeSlot) + 1)">
                                                </option>
                                            </template>
                                        </select>
                                        <button class="btn-hangar-action" @click="assignShip(ship.id)"
                                            :disabled="!pendingAssignTarget[ship.id] || pendingLoading[ship.id]">
                                            <span x-text="pendingLoading[ship.id] ? '…' : i18n.assignHangar"></span>
                                        </button>
                                    </div>

                                    <div class="hangar-error" x-show="pendingError[ship.id]"
                                        x-text="pendingError[ship.id]">
                                    </div>
                                </article>
                            </template>
                        </div>
                    </details>
                </section>
            </template>
        @endif {{-- hangar slots exist --}}

        {{-- ── Nexus-Request modal (native <dialog>, shared for all slots) ────── --}}
        {{--
        Uses showModal() via x-effect to get browser backdrop + focus-trap + Escape-key.
        Instance data is stored in requestModal.* on the Alpine component.
    --}}
        <dialog x-ref="requestDialog" class="hangar-request-dialog"
            x-effect="requestModal.open ? $refs.requestDialog.showModal() : $refs.requestDialog.close()"
            @close="closeRequestModal()">

            <article>
                <header class="hangar-dialog-header">
                    <strong x-text="i18n.nexusRequestTitle"></strong>
                    <button class="hangar-dialog-close" @click="closeRequestModal()" aria-label="Close">&#x2715;</button>
                </header>

                {{-- ── Optional top controls ────────────────────────────────────── --}}
                <div class="hangar-dialog-controls">

                    {{-- Nexus credit toggle — only if CC level >= 2 --}}
                    <template x-if="canUseNexusCredit">
                        <label class="hangar-nexus-toggle">
                            <input type="checkbox" role="switch" x-model="requestModal.useNexusCredit">
                            <span>
                                <span x-text="i18n.nexusCredit"></span>
                                <small class="hangar-nexus-credit-hint" x-text="i18n.nexusCreditHint"></small>
                            </span>
                        </label>
                    </template>

                    {{-- Consul AP range — only if an active Konsul advisor exists with AP available --}}
                    <template x-if="hasAktivierterKonsul && verfuegbareVerhandlungsAP > 0">
                        <label class="hangar-consul-ap-label">
                            <div class="form-row-label">
                                <span x-text="i18n.consulApTitle"></span>
                                <strong x-text="consulApSavings"></strong>
                            </div>
                            <input type="range" min="0" :max="verfuegbareVerhandlungsAP"
                                x-model.number="requestModal.consulApSpent">
                        </label>
                    </template>

                </div>{{-- /.hangar-dialog-controls --}}

                {{-- ── Ship buttons ─────────────────────────────────────────────── --}}
                <div class="hangar-ship-btn-list">
                    <template x-for="type in shipTypes" :key="type.id">
                        <button class="hangar-ship-btn" @click="submitRequestFor(type.id)"
                            :disabled="requestModal.loading">
                            <span class="hangar-ship-btn-name" x-text="shipLabel(type.name)"></span>
                            <span class="hangar-ship-btn-meta" x-show="shipCosts[type.id]"
                                x-text="shipCosts[type.id]
                                  ? effectiveCostFor(type.id) + ' Cr · Sol +' + shipCosts[type.id].delivery_ticks
                                  : ''">
                            </span>
                        </button>
                    </template>
                </div>

                {{-- Error display --}}
                <div class="hangar-error hangar-dialog-error" x-show="requestModal.error" x-text="requestModal.error">
                </div>

                {{-- Cancel link --}}
                <div class="hangar-dialog-cancel">
                    <a href="#" @click.prevent="closeRequestModal()" x-text="i18n.cancel"></a>
                </div>

            </article>

        </dialog>

    </div>{{-- /.hangar-page --}}
@endsection
