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
    <link rel="stylesheet" href="{{ asset('css/swipe.css') }}">
    @stack('styles')
</head>
<body class="@if(request()->routeIs('colony.view')) page-colony @endif">

<header class="colony-header">
    <nav>
        <ul>
            <li><a href="{{ route('colony.view') }}" class="colony-logo">Nouron</a></li>
        </ul>

        {{-- Desktop nav (visible from 600px up) --}}
        <ul class="nav-desktop">
            <li><a href="{{ route('colony.view') }}" @class(['active' => request()->routeIs('colony.*') && !request()->routeIs('colony.bar*') && !request()->routeIs('colony.merchant*')])><i class="bi bi-hexagon"></i><span class="nav-label"> Kolonie</span></a></li>
            <li><a href="{{ route('advisors.index') }}" @class(['active' => request()->routeIs('advisors.*')])><i class="bi bi-people"></i><span class="nav-label"> Berater</span></a></li>
            <li><a href="{{ route('techtree.index') }}" @class(['active' => request()->routeIs('techtree.*')])><i class="bi bi-diagram-3"></i><span class="nav-label"> Techtree</span></a></li>
            @if($barBuilt ?? false)
            <li><a href="{{ route('colony.bar') }}" @class(['active' => request()->routeIs('colony.bar*')])><i class="bi bi-cup-hot"></i><span class="nav-label"> Cantina</span></a></li>
            @else
            <li><span class="nav-link-locked" title="{{ __('colony.nav_cantina_locked') }}"><i class="bi bi-cup-hot"></i><span class="nav-label"> Cantina</span></span></li>
            @endif
            <li><a href="{{ route('messages.inbox') }}" @class(['active' => request()->routeIs('messages.*')])><i class="bi bi-envelope"></i><span class="nav-label"> Nachrichten</span></a></li>
        </ul>

        <ul>
            {{-- Sol-Button: always visible on desktop; mobile only on colony.view (via .sol-btn-wrap) --}}
            @auth
            @if($inActiveRun ?? false)
            <li class="sol-btn-wrap">@include('partials.sol-button')</li>
            @endif
            @endauth

            {{-- Desktop: user dropdown --}}
            <li class="nav-desktop">
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

            {{-- Mobile: hamburger button + flyout menu --}}
            <li class="nav-mobile" x-data="{ open: false }">
                <button class="nav-burger" @click="open = !open" :aria-expanded="open.toString()" type="button" aria-label="Menü">
                    <i class="bi bi-list"></i>
                </button>
                <div class="nav-flyout" x-show="open" @click.outside="open = false" x-cloak>
                    <a href="{{ route('colony.view') }}" @class(['nav-flyout-item', 'active' => request()->routeIs('colony.*') && !request()->routeIs('colony.bar*') && !request()->routeIs('colony.merchant*')])>
                        <i class="bi bi-hexagon"></i> Kolonie
                    </a>
                    <a href="{{ route('advisors.index') }}" @class(['nav-flyout-item', 'active' => request()->routeIs('advisors.*')])>
                        <i class="bi bi-people"></i> Berater
                    </a>
                    <a href="{{ route('techtree.index') }}" @class(['nav-flyout-item', 'active' => request()->routeIs('techtree.*')])>
                        <i class="bi bi-diagram-3"></i> Techtree
                    </a>
                    @if($barBuilt ?? false)
                    <a href="{{ route('colony.bar') }}" @class(['nav-flyout-item', 'active' => request()->routeIs('colony.bar*')])>
                        <i class="bi bi-cup-hot"></i> Cantina
                    </a>
                    @else
                    <span class="nav-flyout-item nav-link-locked" title="{{ __('colony.nav_cantina_locked') }}">
                        <i class="bi bi-cup-hot"></i> Cantina
                    </span>
                    @endif
                    <a href="{{ route('messages.inbox') }}" @class(['nav-flyout-item', 'active' => request()->routeIs('messages.*')])>
                        <i class="bi bi-envelope"></i> Nachrichten
                    </a>
                    <div class="nav-flyout-divider"></div>
                    <a href="{{ route('user.show') }}" class="nav-flyout-item"><i class="bi bi-person"></i> Profil</a>
                    <a href="{{ route('user.settings') }}" class="nav-flyout-item"><i class="bi bi-gear"></i> Einstellungen</a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0">
                        @csrf
                        <button type="submit" class="nav-flyout-item nav-flyout-item--btn">
                            <i class="bi bi-power"></i> Abmelden
                        </button>
                    </form>
                </div>
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
<script src="{{ asset('js/swipe.js') }}"></script>
<script src="{{ asset('js/colony-hexgrid.js') }}"></script>
@stack('scripts')
</body>
</html>
