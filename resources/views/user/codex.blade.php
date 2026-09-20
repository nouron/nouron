@extends("layouts.infra")

@section("title", __("colony.codex_heading") . " — Nouron")

@section("content")
    <div style="max-width: 48rem;">
        <h2><i class="bi bi-journal-bookmark"></i> {{ __("colony.codex_heading") }}</h2>
        <p style="color: var(--pico-muted-color)">{{ __("colony.codex_page_intro") }}</p>

        @foreach ($characters as $character)
            @php
                $slug = $character["slug"];
                $unlocked = $character["unlocked"];
                $maxEntries = $character["max_entries"];
                $displayName = $character["name"] ?? __("colony.codex_locked_entry");
            @endphp
            <details>
                <summary>
                    <strong>{{ $displayName }}</strong>
                    @if ($character["role"])
                        <small style="color: var(--pico-muted-color)"> — {{ $character["role"] }}</small>
                    @endif
                    <span style="float: inline-end; font-size: 0.85rem; color: var(--pico-muted-color)">
                        {{ count($unlocked) }} / {{ $maxEntries }}
                    </span>
                </summary>
                @if (count($unlocked) === 0)
                    <p style="color: var(--pico-muted-color)">{{ __("colony.codex_no_entries_yet") }}</p>
                @else
                    <ul>
                        @for ($n = 1; $n <= $maxEntries; $n++)
                            <li @if (!in_array($n, $unlocked, true)) style="color: var(--pico-muted-color)" @endif>
                                @if (in_array($n, $unlocked, true))
                                    {{ __("colony.codex_entry_{$slug}_{$n}") }}
                                @else
                                    {{ __("colony.codex_locked_entry") }}
                                @endif
                            </li>
                        @endfor
                    </ul>
                @endif
            </details>
        @endforeach
    </div>
@endsection
