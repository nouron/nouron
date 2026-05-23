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
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
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
                    <a class="nav-link @if(request()->routeIs('colony.*')) active @endif"
                       href="{{ route('colony.view') }}"><i class="bi bi-globe2"></i> Kolonie</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('techtree.*')) active @endif"
                       href="{{ route('techtree.index') }}"><i class="bi bi-building"></i> Techtree</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('advisors.*')) active @endif"
                       href="{{ route('advisors.index') }}"><i class="bi bi-people"></i> Berater</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('colony.bar*')) active @endif"
                       href="{{ route('colony.bar') }}"><i class="bi bi-cup-hot"></i> Cantina</a>
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
                @auth
                <li class="nav-item">
                    <form method="POST" action="{{ route('sol.next') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light"
                                onclick="this.disabled=true; this.form.submit();">
                            <i class="bi bi-skip-forward-fill"></i> {{ __('colony.next_sol_button') }}
                        </button>
                    </form>
                </li>
                @endauth
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
<div id="resourcebar-container">
    @include('resources.resourcebar', [
        'possessions' => $resourceBarPossessions,
        'currentSol'  => $currentSol ?? null,
        'solLimit'    => $solLimit ?? 100,
    ])
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
    @if(session('sol_advanced'))
        <div class="alert alert-success alert-dismissible">
            Sol {{ session('sol_advanced') }} berechnet.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/nouron.js') }}"></script>
<script src="{{ asset('js/fleets.js') }}"></script>
<script src="{{ asset('js/innn.js') }}"></script>
@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('fleetconfig')) { fleetconfig.init(); }
});
</script>
</body>
</html>
