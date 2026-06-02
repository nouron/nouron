@push('styles')
<link rel="stylesheet" href="{{ asset('css/messages.css') }}">
@endpush

{{-- Desktop: full tab bar --}}
<div class="msg-tabs-bar">
    <nav class="msg-tabs">
        <a class="msg-tab @if(request()->routeIs('messages.inbox')) msg-tab--active @endif"
           href="{{ route('messages.inbox') }}"><i class="bi bi-inbox"></i><span class="nav-label"> Eingang</span></a>
        <a class="msg-tab @if(request()->routeIs('messages.outbox')) msg-tab--active @endif"
           href="{{ route('messages.outbox') }}"><i class="bi bi-send"></i><span class="nav-label"> Ausgang</span></a>
        <a class="msg-tab @if(request()->routeIs('messages.archive')) msg-tab--active @endif"
           href="{{ route('messages.archive') }}"><i class="bi bi-archive"></i><span class="nav-label"> Archiv</span></a>
        <a class="msg-tab @if(request()->routeIs('messages.events')) msg-tab--active @endif"
           href="{{ route('messages.events') }}"><i class="bi bi-lightning-charge"></i><span class="nav-label"> Ereignisse</span></a>
        <a class="msg-tab @if(request()->routeIs('messages.actions')) msg-tab--active @endif"
           href="{{ route('messages.actions') }}"><i class="bi bi-cursor"></i><span class="nav-label"> Aktionen</span></a>
        <a class="msg-tab @if(request()->routeIs('messages.news')) msg-tab--active @endif"
           href="{{ route('messages.news') }}"><i class="bi bi-newspaper"></i><span class="nav-label"> INNN</span></a>
        <a class="msg-tab msg-tab--action @if(request()->routeIs('messages.compose')) msg-tab--active @endif"
           href="{{ route('messages.compose') }}"><i class="bi bi-pencil-square"></i><span class="nav-label"> Neue Nachricht</span></a>
    </nav>
</div>

{{-- Mobile: current tab name + dot indicators --}}
@php
$tabOrder = ['messages.inbox', 'messages.outbox', 'messages.archive', 'messages.events', 'messages.actions', 'messages.news'];
$tabNames = ['Eingang', 'Ausgang', 'Archiv', 'Ereignisse', 'Aktionen', 'INNN'];
$tabUrls  = [
    route('messages.inbox'),
    route('messages.outbox'),
    route('messages.archive'),
    route('messages.events'),
    route('messages.actions'),
    route('messages.news'),
];
$currentIdx = 0;
foreach ($tabOrder as $i => $routeName) {
    if (request()->routeIs($routeName)) { $currentIdx = $i; break; }
}
$prevUrl = $currentIdx > 0 ? $tabUrls[$currentIdx - 1] : null;
$nextUrl = $currentIdx < count($tabUrls) - 1 ? $tabUrls[$currentIdx + 1] : null;
@endphp

<div class="msg-mobile-header">
    <span class="msg-mobile-title">{{ $tabNames[$currentIdx] }}</span>
    <div class="swipe-dots">
        @foreach($tabNames as $i => $name)
        <a href="{{ $tabUrls[$i] }}"
           class="swipe-dot @if($i === $currentIdx) swipe-dot--active @endif"
           title="{{ $name }}"></a>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
(function () {
    var prev = @json($prevUrl);
    var next = @json($nextUrl);
    if (!prev && !next) return;
    var sx = 0, sy = 0;
    window.addEventListener('touchstart', function (e) {
        sx = e.touches[0].clientX;
        sy = e.touches[0].clientY;
    }, { passive: true });
    window.addEventListener('touchend', function (e) {
        var dx = e.changedTouches[0].clientX - sx;
        var dy = e.changedTouches[0].clientY - sy;
        if (Math.abs(dx) < 45 || Math.abs(dy) > Math.abs(dx)) return;
        if (dx < 0 && next) { window.location.href = next; }
        else if (dx > 0 && prev) { window.location.href = prev; }
    }, { passive: true });
}());
</script>
@endpush
