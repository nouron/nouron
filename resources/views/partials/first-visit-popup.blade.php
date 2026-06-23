{{--
    First-visit popup: explains a screen the first time a player opens it
    (Techtree, Nexus-DB, Cantina, Hangar — see project_hint_system_review_needed
    memory). Separate from the hint-bar: event-triggered ("screen opened"), not
    game-state-triggered, so it lives outside OnboardingHintService::getActiveHint().
    Dismiss is reused from the existing hint-dismiss endpoint with a `visit_*` key —
    no new route, same dismissed_hints store.

    Props: $firstVisitKey (string, e.g. "techtree"), $firstVisitTitle / $firstVisitText (lang keys)
--}}
@if ($firstVisit ?? false)
    <div class="first-visit-popup" x-data="{ open: true }" x-show="open" x-cloak>
        <div class="first-visit-popup__card">
            <div class="first-visit-popup__icon" aria-hidden="true">ℹ</div>
            <div class="first-visit-popup__body">
                <p class="first-visit-popup__title">{{ __($firstVisitTitle) }}</p>
                <p class="first-visit-popup__text">{{ __($firstVisitText) }}</p>
            </div>
            <button class="first-visit-popup__dismiss" type="button"
                @click="open = false; fetch('{{ route("colony.hint.dismiss") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ hint_key: 'visit_{{ $firstVisitKey }}' }),
                })">{{ __("colony.first_visit_dismiss") }}</button>
        </div>
    </div>
@endif
