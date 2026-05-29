@push('styles')
<link rel="stylesheet" href="{{ asset('css/messages.css') }}">
@endpush
<div class="msg-tabs-bar">
    <nav class="msg-tabs">
        <a class="msg-tab @if(request()->routeIs('messages.inbox')) msg-tab--active @endif"
           href="{{ route('messages.inbox') }}">
            <i class="bi bi-inbox"></i> Eingang
        </a>
        <a class="msg-tab @if(request()->routeIs('messages.outbox')) msg-tab--active @endif"
           href="{{ route('messages.outbox') }}">
            <i class="bi bi-send"></i> Ausgang
        </a>
        <a class="msg-tab @if(request()->routeIs('messages.archive')) msg-tab--active @endif"
           href="{{ route('messages.archive') }}">
            <i class="bi bi-archive"></i> Archiv
        </a>
        <a class="msg-tab @if(request()->routeIs('messages.events')) msg-tab--active @endif"
           href="{{ route('messages.events') }}">
            <i class="bi bi-lightning-charge"></i> Ereignisse
        </a>
        <a class="msg-tab @if(request()->routeIs('messages.news')) msg-tab--active @endif"
           href="{{ route('messages.news') }}">
            <i class="bi bi-newspaper"></i> INNN
        </a>
        <a class="msg-tab msg-tab--action @if(request()->routeIs('messages.compose')) msg-tab--active @endif"
           href="{{ route('messages.compose') }}">
            <i class="bi bi-pencil-square"></i> Neue Nachricht
        </a>
    </nav>
</div>
