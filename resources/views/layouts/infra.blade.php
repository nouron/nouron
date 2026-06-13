<!DOCTYPE html>
<html lang="de" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield("title", "Nouron")</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400&display=swap">
    <link rel="stylesheet" href="{{ asset("css/infra.css") }}">
    @stack("styles")
</head>

<body>

    @auth
        <header class="infra-header">
            <nav>
                <ul>
                    <li><a href="{{ route("lobby") }}" class="infra-brand">Nouron</a></li>
                </ul>
                <ul>
                    @if ($inActiveRun ?? false)
                        <li><a href="{{ route("colony.view") }}"><i class="bi bi-hexagon"></i><span class="infra-nav-label">
                                    Kolonie</span></a></li>
                    @endif
                    <li>
                        <details class="dropdown">
                            <summary>{{ Auth::user()->username }}</summary>
                            <ul dir="rtl">
                                <li><a href="{{ route("lobby") }}">{{ __("lobby.nav_runs") }}</a></li>
                                <li><a href="{{ route("user.show") }}">Profil</a></li>
                                <li><a href="{{ route("user.settings") }}">Einstellungen</a></li>
                                <li>
                                    <form method="POST" action="{{ route("logout") }}" style="margin:0">
                                        @csrf
                                        <button type="submit">Abmelden</button>
                                    </form>
                                </li>
                            </ul>
                        </details>
                    </li>
                </ul>
            </nav>
        </header>
    @endauth

    <main class="infra-main">
        @if (session("success"))
            <p class="infra-alert infra-alert--success" role="alert">{{ session("success") }}</p>
        @endif
        @if (session("error"))
            <p class="infra-alert infra-alert--error" role="alert">{{ session("error") }}</p>
        @endif
        @if (session("info"))
            <p class="infra-alert infra-alert--info" role="alert">{{ session("info") }}</p>
        @endif
        @if (session("sol_advanced"))
            <p class="infra-alert infra-alert--success" role="alert">Sol {{ session("sol_advanced") }} berechnet.</p>
        @endif

        @yield("content")
    </main>

    @auth
        @if (Auth::user()->role === "admin")
            @include("partials.debug-bar")
        @endif
    @endauth

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    @stack("scripts")
</body>

</html>
