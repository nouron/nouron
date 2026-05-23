@extends('layouts.app')
@section('title', 'Mission starten — Nouron')

@section('content')
<div class="container" style="max-width: 540px; margin-top: 10vh;">

    <div class="text-center mb-4">
        <h1 class="h3 text-light">Willkommen, {{ Auth::user()->username }}</h1>
        <p class="text-secondary">{{ __('lobby.subtitle') }}</p>
    </div>

    @if($run)
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <h5 class="card-title text-light mb-1">
                <i class="bi bi-geo-alt-fill text-warning"></i>
                {{ $run->colony->name ?? '—' }}
            </h5>
            <p class="text-secondary small mb-3">{{ __('lobby.colony_ready') }}</p>

            <form method="POST" action="{{ route('lobby.start') }}">
                @csrf
                <button type="submit" class="btn btn-warning w-100">
                    <i class="bi bi-play-fill"></i> {{ __('lobby.start_button') }}
                </button>
            </form>
        </div>
    </div>
    @else
    <div class="alert alert-warning">
        {{ __('lobby.no_run') }}
    </div>
    @endif

</div>
@endsection
