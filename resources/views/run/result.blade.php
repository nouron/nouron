@extends("layouts.infra")
@section("title",
    $run->status === "completed"
    ? __("run.result_title_completed")
    : __("run.result_title_failed") .
    " —
    Nouron")

@section("content")
    <div class="run-result">

        {{-- ── Status header ──────────────────────────────────────────────── --}}
        <div
            class="run-result__status {{ $run->status === "completed" ? "run-result__status--win" : "run-result__status--lose" }}">
            <i
                class="bi run-result__status-icon
            {{ $run->status === "completed" ? "bi-trophy-fill" : "bi-x-octagon-fill" }}"></i>
            <div>
                <h1 class="run-result__status-title">
                    {{ $run->status === "completed" ? __("run.result_title_completed") : __("run.result_title_failed") }}
                </h1>
                <p class="run-result__status-body">
                    @if ($run->status === "completed")
                        {{ __("run.run_completed") }}
                    @elseif($run->fail_reason === "trust_collapse")
                        {{ __("run.result_fail_trust") }}
                    @else
                        {{ __("run.result_fail_time") }}
                    @endif
                </p>
            </div>
        </div>

        {{-- ── Score ──────────────────────────────────────────────────────── --}}
        <div class="run-result__score-wrap">
            <p class="run-result__score-label">{{ __("run.result_score_label") }}</p>
            <p class="run-result__score-value">{{ number_format($score) }}</p>
            @if ($run->status === "completed")
                <p class="run-result__score-sub">
                    {{ __("run.result_ticks_label", [
                        "current" => $run->current_tick,
                        "limit" => $run->getTickLimit(),
                    ]) }}
                </p>
            @endif
        </div>

        {{-- ── Objectives ──────────────────────────────────────────────────── --}}
        <div class="run-result__objectives">
            <p class="run-result__obj-title">{{ __("run.phase1_complete") }}</p>
            <ul class="run-result__obj-list">
                @forelse($objectives as $item)
                    @php
                        $obj = $item["model"];
                        $pct = $obj->progressPct();
                    @endphp
                    <li class="run-result__obj-item">
                        <span class="run-result__obj-label">{{ $item["label"] }}</span>
                        <div class="run-result__obj-bar">
                            <progress class="run-result__obj-progress" value="{{ $pct }}"
                                max="100"></progress>
                            <span class="run-result__obj-count">{{ $obj->current_value }} /
                                {{ $obj->target_value }}</span>
                        </div>
                        <span
                            class="run-result__obj-badge {{ $obj->isCompleted() ? "run-result__obj-badge--done" : "run-result__obj-badge--open" }}">
                            @if ($obj->isCompleted())
                                <i class="bi bi-check-lg"></i>
                            @endif
                            {{ $obj->isCompleted() ? __("run.result_objective_fulfilled") : __("run.result_objective_open") }}
                        </span>
                    </li>
                @empty
                    <li style="text-align:center;padding:1rem;color:var(--infra-text-muted);">—</li>
                @endforelse
            </ul>
        </div>

        {{-- ── Actions ─────────────────────────────────────────────────────── --}}
        <div class="run-result__actions">
            <form method="POST" action="{{ route("run.new") }}" style="margin:0;">
                @csrf
                <button type="submit">
                    <i class="bi bi-arrow-repeat"></i>
                    {{ __("run.result_btn_new_run") }}
                </button>
            </form>

            @if ($run->status === "completed")
                <a href="{{ route("colony.view") }}" role="button" class="secondary outline">
                    <i class="bi bi-hexagon"></i>
                    {{ __("run.result_btn_colony") }}
                </a>
            @else
                <button type="button" class="secondary outline" disabled
                    title="{{ __("run.result_btn_colony_disabled") }}">
                    <i class="bi bi-hexagon"></i>
                    {{ __("run.result_btn_colony") }}
                </button>
            @endif
        </div>

    </div>
@endsection
