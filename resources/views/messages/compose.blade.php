@extends('layouts.app')

@section('title', 'Neue Nachricht — Nouron')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('messages.inbox') }}">
                    <i class="bi bi-inbox"></i> Eingang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('messages.outbox') }}">
                    <i class="bi bi-send"></i> Ausgang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('messages.archive') }}">
                    <i class="bi bi-archive"></i> Archiv
                </a>
            </li>
            <li class="nav-item ms-auto">
                <a class="nav-link active" href="{{ route('messages.compose') }}">
                    <i class="bi bi-pencil-square"></i> Neue Nachricht
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <ul class="nav nav-tabs mb-3" id="compose-tabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab"
                        data-bs-target="#msg-to-user" type="button">
                    ... an Spieler
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link disabled" type="button">... an Fraktion</button>
            </li>
            <li class="nav-item">
                <button class="nav-link disabled" type="button">... an INNN</button>
            </li>
            <li class="nav-item">
                <button class="nav-link disabled" type="button">... an Support</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="msg-to-user">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('messages.send') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="recipient_id" class="form-label">Empfanger-ID</label>
                        <input type="number" class="form-control" id="recipient_id"
                               name="recipient_id" value="{{ old('recipient_id') }}"
                               min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="attitude" class="form-label">Haltung</label>
                        <select class="form-select" id="attitude" name="attitude">
                            <option value="mood_factual" @selected(old('attitude', 'mood_factual') === 'mood_factual')>Sachlich</option>
                            <option value="mood_friendly" @selected(old('attitude') === 'mood_friendly')>Freundlich</option>
                            <option value="mood_hostile"  @selected(old('attitude') === 'mood_hostile')>Feindlich</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Betreff</label>
                        <input type="text" class="form-control" id="subject"
                               name="subject" value="{{ old('subject') }}"
                               maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="text" class="form-label">Nachricht</label>
                        <textarea class="form-control" id="text" name="text"
                                  rows="8" required>{{ old('text') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Senden
                    </button>
                    <a href="{{ route('messages.inbox') }}" class="btn btn-secondary ms-2">Abbrechen</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
