{{--
    Encounter danger notice bar (GDD §9 Sturm/Instabilität/Seuche) — shown above
    the onboarding hint-bar in the shared layout header, Owner-Playtest-Fund
    2026-08-31: these events previously only appeared in the Protokoll log and
    were easy to miss for a whole Sol. Purely server-rendered (see
    AppServiceProvider's $activeEncounterNotices) — no live AJAX sync needed,
    since these events only ever change at Sol-end (game:tick), which already
    reloads the page. Clears itself automatically once the Sol ends, no dismiss
    button (see EncounterNoticeService docblock).
--}}
@php($notices = $activeEncounterNotices ?? [])
@foreach ($notices as $notice)
    <div class="encounter-notice-bar">
        <span class="encounter-notice-bar__icon" aria-hidden="true">⚠</span>
        <span class="encounter-notice-bar__text">{{ $notice["text"] }}</span>
    </div>
@endforeach
