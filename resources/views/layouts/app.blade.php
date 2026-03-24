<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nouron')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
</head>
<body>

@auth
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('galaxy.index') }}">Nouron</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('galaxy.*')) active @endif"
                       href="{{ route('galaxy.index') }}"><i class="bi bi-globe2"></i> Galaxis</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('fleet.*')) active @endif"
                       href="{{ route('fleet.index') }}"><i class="bi bi-send"></i> Flotte</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('techtree.*')) active @endif"
                       href="{{ route('techtree.index') }}"><i class="bi bi-diagram-3"></i> Techtree</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('trade.*')) active @endif"
                       href="{{ route('trade.resources') }}"><i class="bi bi-cart3"></i> Handel</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('messages.*')) active @endif"
                       href="{{ route('messages.inbox') }}"><i class="bi bi-envelope"></i> Nachrichten</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> {{ Auth::user()->username }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('user.show') }}"><i class="bi bi-person"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="{{ route('user.settings') }}"><i class="bi bi-gear"></i> Einstellungen</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item"><i class="bi bi-power"></i> Abmelden</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- Resource bar --}}
@if(!empty($resourceBarPossessions))
<div class="bg-dark border-bottom border-secondary py-1 px-3" id="resourcebar-container">
    @include('resources.resourcebar', ['possessions' => $resourceBarPossessions])
</div>
@endif

@endauth

{{-- Sub-navigation tabs (module-specific) --}}
@hasSection('subnav')
<div class="bg-dark border-bottom border-secondary">
    <div class="container-fluid">
        @yield('subnav')
    </div>
</div>
@endif

<div class="container-fluid mt-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/jquery.bootstrap-growl.min.js') }}"></script>
<script src="{{ asset('js/nouron.js') }}"></script>
<script src="{{ asset('js/techtree.js') }}"></script>
<script src="{{ asset('js/fleets.js') }}"></script>
<script src="{{ asset('js/trade.js') }}"></script>
<script src="{{ asset('js/innn.js') }}"></script>
@stack('scripts')
<script>
$(document).ready(function () {
    // Bootstrap 5: init tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // Module JS init (guarded by element presence)
    if ($('#colony').length > 0)     { techtree.init(); }
    if ($('#fleetlist').length > 0)  { fleetlist.init(); }
    if ($('#fleetconfig').length > 0){ fleetconfig.init(); }
    if ($('#trade').length > 0)      { trade.init(); }
});
</script>
</body>
</html>
