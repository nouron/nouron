<div class="row mb-3">
    <div class="col-12">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('messages.inbox')) active @endif"
                   href="{{ route('messages.inbox') }}">
                    <i class="bi bi-inbox"></i> Eingang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('messages.outbox')) active @endif"
                   href="{{ route('messages.outbox') }}">
                    <i class="bi bi-send"></i> Ausgang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('messages.archive')) active @endif"
                   href="{{ route('messages.archive') }}">
                    <i class="bi bi-archive"></i> Archiv
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('messages.events')) active @endif"
                   href="{{ route('messages.events') }}">
                    <i class="bi bi-lightning-charge"></i> Ereignisse
                </a>
            </li>
            <li class="nav-item ms-auto">
                <a class="nav-link @if(request()->routeIs('messages.compose')) active @endif"
                   href="{{ route('messages.compose') }}">
                    <i class="bi bi-pencil-square"></i> Neue Nachricht
                </a>
            </li>
        </ul>
    </div>
</div>
