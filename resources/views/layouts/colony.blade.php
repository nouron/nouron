<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nouron')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400&display=swap">
    <link rel="stylesheet" href="{{ asset('css/colony.css') }}">
    @stack('styles')
</head>
<body>

<header class="colony-header">
    <nav>
        <ul>
            <li><a href="{{ route('colony.view') }}" class="colony-logo">Nouron</a></li>
        </ul>
        <ul>
            <li><a href="{{ route('colony.view') }}" @class(['active' => request()->routeIs('colony.*') && !request()->routeIs('colony.bar*') && !request()->routeIs('colony.merchant*')])><i class="bi bi-hexagon"></i> Kolonie</a></li>
            <li><a href="{{ route('advisors.index') }}" @class(['active' => request()->routeIs('advisors.*')])><i class="bi bi-people"></i> Berater</a></li>
            <li><a href="{{ route('techtree.index') }}" @class(['active' => request()->routeIs('techtree.*')])><i class="bi bi-diagram-3"></i> Techtree</a></li>
            <li><a href="{{ route('colony.bar') }}" @class(['active' => request()->routeIs('colony.bar*')])><i class="bi bi-cup-hot"></i> Cantina</a></li>
            <li><a href="{{ route('messages.inbox') }}" @class(['active' => request()->routeIs('messages.*')])><i class="bi bi-envelope"></i> Nachrichten</a></li>
        </ul>
        <ul>
            {{-- Sol-Button (only in an active run) --}}
            @auth
            @if($inActiveRun ?? false)
            <li>@include('partials.sol-button')</li>
            @endif
            @endauth
            <li>
                <details class="dropdown colony-user-dropdown">
                    <summary>{{ Auth::user()->username }}</summary>
                    <ul>
                        <li><a href="{{ route('user.show') }}">Profil</a></li>
                        <li><a href="{{ route('user.settings') }}">Einstellungen</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                                @csrf
                                <button type="submit">Abmelden</button>
                            </form>
                        </li>
                    </ul>
                </details>
            </li>
        </ul>
    </nav>

    {{-- Resource bar --}}
    @if(!empty($resourceBarPossessions))
    <div class="colony-resbar">
        @include('resources.resourcebar', [
            'possessions'  => $resourceBarPossessions,
            'currentSol'   => $currentSol ?? null,
            'solLimit'     => $solLimit ?? 100,
            'nexusDebt'    => ($inActiveRun ?? false) ? ($nexusDebt ?? null) : null,
            'nexusDebtMax' => $nexusDebtMax ?? 12000,
        ])
    </div>
    @endif
</header>

<main class="colony-main">
    @yield('content')
</main>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
<script src="{{ asset('js/colony-hexgrid.js') }}"></script>
@stack('scripts')
</body>
</html>
