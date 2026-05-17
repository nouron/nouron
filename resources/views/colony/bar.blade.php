@extends('layouts.colony')
@section('title', __('colony.bar_title') . ' — Nouron')

@section('content')
@php
    $resourceLabels = [
        1 => __('resources.res_credits'),
        3 => __('resources.res_regolith'),
        4 => __('resources.res_werkstoffe'),
        5 => __('resources.res_organika'),
    ];
@endphp

<div x-data="barPage()" x-cloak>

    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
        <h2 style="margin:0">{{ __('colony.bar_title') }}</h2>
        <small style="color:var(--pico-muted-color)">Sol {{ $tick }}</small>
    </div>

    @if ($barLevel < 1)
        <p>{{ __('colony.bar_no_building') }}</p>
    @elseif ($offers->isEmpty())
        <p style="color:var(--pico-muted-color)">{{ __('colony.bar_no_offers') }}</p>
    @else
        <p style="font-weight:600;margin-bottom:1rem">{{ __('colony.bar_offer_heading') }}</p>

        <div style="display:flex;flex-direction:column;gap:1rem;max-width:480px">
            @foreach ($offers as $offer)
            <article style="margin:0;padding:1.25rem;border-radius:var(--pico-border-radius);border:1px solid var(--pico-muted-border-color)">
                <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:0.75rem;margin-bottom:1rem">
                    <div>
                        <div style="font-size:0.8rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                            {{ __('colony.bar_offer_give') }}
                        </div>
                        <strong>{{ $offer->give_amount }}×</strong>
                        {{ $resourceLabels[$offer->give_resource_id] ?? $offer->give_resource_id }}
                    </div>
                    <span style="font-size:1.25rem">→</span>
                    <div>
                        <div style="font-size:0.8rem;color:var(--pico-muted-color);margin-bottom:0.25rem">
                            {{ __('colony.bar_offer_get') }}
                        </div>
                        <strong>{{ $offer->get_amount }}×</strong>
                        {{ $resourceLabels[$offer->get_resource_id] ?? $offer->get_resource_id }}
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <small style="color:var(--pico-muted-color)">
                        {{ __('colony.bar_offer_expires') }} {{ $offer->expires_tick }}
                    </small>
                    <button
                        @click="accept({{ $offer->id }}, $el)"
                        :disabled="accepted.has({{ $offer->id }}) || loading"
                        style="margin:0"
                    >
                        <span x-show="!accepted.has({{ $offer->id }})">{{ __('colony.bar_offer_accept') }}</span>
                        <span x-show="accepted.has({{ $offer->id }})">✓</span>
                    </button>
                </div>
                <div x-show="error[{{ $offer->id }}]" x-text="error[{{ $offer->id }}]"
                     style="color:var(--pico-del-color);font-size:0.85rem;margin-top:0.5rem"></div>
            </article>
            @endforeach
        </div>
    @endif

</div>

<script>
function barPage() {
    return {
        accepted: new Set(),
        loading: false,
        error: {},
        async accept(offerId, btn) {
            this.loading = true;
            this.error[offerId] = null;
            try {
                const res = await fetch('{{ route('colony.bar.accept', ['offer' => '__ID__']) }}'.replace('__ID__', offerId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.ok) {
                    this.accepted.add(offerId);
                } else {
                    this.error[offerId] = data.error ?? 'Fehler';
                }
            } catch {
                this.error[offerId] = 'Verbindungsfehler';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
