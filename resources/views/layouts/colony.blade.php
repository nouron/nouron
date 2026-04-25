<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nouron')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="{{ asset('css/colony.css') }}">
    @stack('styles')
</head>
<body>

<header class="colony-header">
    <nav>
        <ul>
            <li><strong><a href="{{ route('galaxy.index') }}">Nouron</a></strong></li>
        </ul>
        <ul>
            <li><a href="{{ route('galaxy.index') }}" @class(['active' => request()->routeIs('galaxy.*')])>Galaxis</a></li>
            <li><a href="{{ route('fleet.index') }}" @class(['active' => request()->routeIs('fleet.*')])>Flotte</a></li>
            <li><a href="{{ route('colony.view') }}" @class(['active' => request()->routeIs('colony.*')])>Kolonie</a></li>
            <li><a href="{{ route('techtree.index') }}" @class(['active' => request()->routeIs('techtree.*')])>Techtree</a></li>
            <li><a href="{{ route('advisors.index') }}" @class(['active' => request()->routeIs('advisors.*')])>Berater</a></li>
            <li><a href="{{ route('messages.inbox') }}" @class(['active' => request()->routeIs('messages.*')])>Nachrichten</a></li>
        </ul>
        <ul>
            <li>
                <details class="dropdown">
                    <summary>{{ Auth::user()->username }}</summary>
                    <ul dir="rtl">
                        <li><a href="{{ route('user.show') }}">Profil</a></li>
                        <li><a href="{{ route('user.settings') }}">Einstellungen</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                                @csrf
                                <button type="submit" class="secondary outline">Abmelden</button>
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
        @foreach($resourceBarPossessions as $resId => $res)
            @php $rid = (int)$resId; $empty = ($res['amount'] ?? 0) == 0; @endphp
            @if(in_array($rid, [1, 2]))
            <span class="res-chip res-chip--primary {{ $empty ? 'res-chip--empty' : '' }}">
                <span class="res-abbr">{{ $res['abbreviation'] ?? '' }}</span>
                <span class="res-amount">{{ number_format($res['amount'] ?? 0, 0, ',', '.') }}</span>
            </span>
            @elseif(in_array($rid, [3, 4, 5, 12]))
            <span class="res-chip {{ $empty ? 'res-chip--empty' : '' }}">
                <span class="res-abbr">{{ $res['abbreviation'] ?? '' }}</span>
                <span class="res-amount">{{ number_format($res['amount'] ?? 0, 0, ',', '.') }}</span>
            </span>
            @endif
        @endforeach
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
