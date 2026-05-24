@extends('layouts.app')
@section('title', $run->status === 'completed'
    ? __('run.result_title_completed')
    : __('run.result_title_failed') . ' — Nouron')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">

        {{-- ── Status header ────────────────────────────────────────────── --}}
        @if($run->status === 'completed')
            <div class="p-4 mb-4 rounded" style="background-color: #d1e7dd; border: 1px solid #a3cfbb;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
                    <div>
                        <h1 class="h3 mb-1 text-success">{{ __('run.result_title_completed') }}</h1>
                        <p class="mb-0 text-success-emphasis">{{ __('run.run_completed') }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="p-4 mb-4 rounded" style="background-color: #f8d7da; border: 1px solid #f1aeb5;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 2.5rem;"></i>
                    <div>
                        <h1 class="h3 mb-1 text-danger">{{ __('run.result_title_failed') }}</h1>
                        <p class="mb-0 text-danger-emphasis">
                            @if($run->fail_reason === 'trust_collapse')
                                {{ __('run.result_fail_trust') }}
                            @else
                                {{ __('run.result_fail_time') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Score ───────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-body text-center py-4">
                <p class="text-muted mb-1 text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.08em;">
                    {{ __('run.result_score_label') }}
                </p>
                <p class="display-3 fw-bold mb-0">{{ number_format($score) }}</p>
                @if($run->status === 'completed')
                    <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">
                        {{ __('run.result_ticks_label', [
                            'current' => $run->current_tick,
                            'limit'   => $run->getTickLimit(),
                        ]) }}
                    </p>
                @endif
            </div>
        </div>

        {{-- ── Objectives table ─────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h6 mb-0">{{ __('run.phase1_complete') }}</h2>
            </div>
            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tbody>
                        @forelse($objectives as $item)
                            @php
                                /** @var \App\Models\RunObjective $obj */
                                $obj = $item['model'];
                                $pct = $obj->progressPct();
                            @endphp
                            <tr>
                                <td class="ps-3 py-3" style="width: 40%;">
                                    <span class="fw-semibold" style="font-size: 0.9rem;">
                                        {{ $item['label'] }}
                                    </span>
                                </td>
                                <td class="py-3" style="width: 35%;">
                                    <div class="progress" style="height: 0.6rem;" title="{{ $pct }}%">
                                        <div
                                            class="progress-bar {{ $obj->isCompleted() ? 'bg-success' : 'bg-secondary' }}"
                                            role="progressbar"
                                            style="width: {{ $pct }}%"
                                            aria-valuenow="{{ $pct }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                    <small class="text-muted">{{ $obj->current_value }} / {{ $obj->target_value }}</small>
                                </td>
                                <td class="pe-3 py-3 text-end" style="width: 25%;">
                                    @if($obj->isCompleted())
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-lg"></i>
                                            {{ __('run.result_objective_fulfilled') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            {{ __('run.result_objective_open') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">—</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Footer buttons ───────────────────────────────────────────── --}}
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <form method="POST" action="{{ route('run.new') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat"></i>
                    {{ __('run.result_btn_new_run') }}
                </button>
            </form>

            @if($run->status === 'completed')
                <a href="{{ route('colony.view') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-globe2"></i>
                    {{ __('run.result_btn_colony') }}
                </a>
            @else
                <button type="button" class="btn btn-outline-secondary" disabled
                        title="{{ __('run.result_btn_colony_disabled') }}">
                    <i class="bi bi-globe2"></i>
                    {{ __('run.result_btn_colony') }}
                </button>
            @endif
        </div>

    </div>
</div>
@endsection
