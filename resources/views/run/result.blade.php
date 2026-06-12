@extends('layouts.infra')
@section('title', $run->status === 'completed'
    ? __('run.result_title_completed')
    : __('run.result_title_failed') . ' — Nouron')

@section('content')
<div style="max-width: 42rem; margin: 0 auto;">

    {{-- ── Status header ──────────────────────────────────────────────── --}}
    @if($run->status === 'completed')
        <div style="display:flex; align-items:center; gap:1rem; padding:1.25rem; margin-bottom:1.5rem; border-radius:4px; background:#d1e7dd; border:1px solid #a3cfbb;">
            <i class="bi bi-check-circle-fill" style="font-size:2.5rem; color:#198754; flex-shrink:0;"></i>
            <div>
                <h1 style="margin:0 0 0.25rem; font-size:1.4rem; color:#0a3622;">{{ __('run.result_title_completed') }}</h1>
                <p style="margin:0; color:#0a3622;">{{ __('run.run_completed') }}</p>
            </div>
        </div>
    @else
        <div style="display:flex; align-items:center; gap:1rem; padding:1.25rem; margin-bottom:1.5rem; border-radius:4px; background:#f8d7da; border:1px solid #f1aeb5;">
            <i class="bi bi-x-circle-fill" style="font-size:2.5rem; color:#c0392b; flex-shrink:0;"></i>
            <div>
                <h1 style="margin:0 0 0.25rem; font-size:1.4rem; color:#58151c;">{{ __('run.result_title_failed') }}</h1>
                <p style="margin:0; color:#58151c;">
                    @if($run->fail_reason === 'trust_collapse')
                        {{ __('run.result_fail_trust') }}
                    @else
                        {{ __('run.result_fail_time') }}
                    @endif
                </p>
            </div>
        </div>
    @endif

    {{-- ── Score ──────────────────────────────────────────────────────── --}}
    <article style="text-align:center; padding:2rem 1rem; margin-bottom:1.5rem;">
        <p style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--pico-muted-color,#6c757d); margin:0 0 0.5rem;">
            {{ __('run.result_score_label') }}
        </p>
        <p style="font-size:3.5rem; font-weight:700; margin:0;">{{ number_format($score) }}</p>
        @if($run->status === 'completed')
            <p style="font-size:0.9rem; color:var(--pico-muted-color,#6c757d); margin:0.5rem 0 0;">
                {{ __('run.result_ticks_label', [
                    'current' => $run->current_tick,
                    'limit'   => $run->getTickLimit(),
                ]) }}
            </p>
        @endif
    </article>

    {{-- ── Objectives table ─────────────────────────────────────────── --}}
    <article style="padding:0; margin-bottom:1.5rem; overflow:hidden;">
        <header style="padding:0.75rem 1.25rem;">
            <strong>{{ __('run.phase1_complete') }}</strong>
        </header>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                <tbody>
                    @forelse($objectives as $item)
                        @php
                            $obj = $item['model'];
                            $pct = $obj->progressPct();
                        @endphp
                        <tr style="border-top:1px solid var(--pico-muted-border-color,#ddd);">
                            <td style="padding:0.75rem 1.25rem; width:40%;">
                                <strong style="font-size:0.9rem;">{{ $item['label'] }}</strong>
                            </td>
                            <td style="padding:0.75rem 1rem; width:35%;">
                                <progress value="{{ $pct }}" max="100" title="{{ $pct }}%" style="height:0.5rem; margin-bottom:0.25rem;"></progress>
                                <small style="color:var(--pico-muted-color,#6c757d);">{{ $obj->current_value }} / {{ $obj->target_value }}</small>
                            </td>
                            <td style="padding:0.75rem 1.25rem; width:25%; text-align:right;">
                                @if($obj->isCompleted())
                                    <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2em 0.6em;border-radius:0.3em;font-size:0.78rem;font-weight:600;background:#198754;color:#fff;">
                                        <i class="bi bi-check-lg"></i>
                                        {{ __('run.result_objective_fulfilled') }}
                                    </span>
                                @else
                                    <span style="display:inline-block;padding:0.2em 0.6em;border-radius:0.3em;font-size:0.78rem;font-weight:600;background:#6c757d;color:#fff;">
                                        {{ __('run.result_objective_open') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;padding:1rem;color:var(--pico-muted-color,#6c757d);">—</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    {{-- ── Footer buttons ─────────────────────────────────────────────── --}}
    <div style="display:flex; gap:0.75rem; justify-content:center; flex-wrap:wrap;">
        <form method="POST" action="{{ route('run.new') }}" style="margin:0;">
            @csrf
            <button type="submit">
                <i class="bi bi-arrow-repeat"></i>
                {{ __('run.result_btn_new_run') }}
            </button>
        </form>

        @if($run->status === 'completed')
            <a href="{{ route('colony.view') }}" role="button" class="secondary outline">
                <i class="bi bi-hexagon"></i>
                {{ __('run.result_btn_colony') }}
            </a>
        @else
            <button type="button" class="secondary outline" disabled
                    title="{{ __('run.result_btn_colony_disabled') }}">
                <i class="bi bi-hexagon"></i>
                {{ __('run.result_btn_colony') }}
            </button>
        @endif
    </div>

</div>
@endsection
