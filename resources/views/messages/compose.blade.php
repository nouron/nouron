@extends('layouts.colony')

@section('title', 'Neue Nachricht — Nouron')

@section('content')
@include('messages.partials.tabs')

<div style="padding: 1.5rem 1.5rem 3rem;">
<div class="msg-compose">
    <div class="msg-compose-tabs">
        <button class="msg-tab msg-tab--active" type="button">
            <i class="bi bi-person"></i> an Spieler
        </button>
        <button class="msg-tab" type="button" disabled title="Nicht verfügbar">
            <i class="bi bi-people"></i> an Fraktion
        </button>
        <button class="msg-tab" type="button" disabled title="Nicht verfügbar">
            <i class="bi bi-newspaper"></i> an INNN
        </button>
        <button class="msg-tab" type="button" disabled title="Nicht verfügbar">
            <i class="bi bi-headset"></i> an Support
        </button>
    </div>

    @if($errors->any())
        <div class="msg-error-list">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('messages.send') }}" class="msg-form">
        @csrf
        <div class="msg-form-field">
            <label for="recipient_id">Empfanger-ID</label>
            <input type="number" id="recipient_id" name="recipient_id"
                   value="{{ old('recipient_id') }}" min="0" required>
        </div>
        <div class="msg-form-field">
            <label for="attitude">Haltung</label>
            <select id="attitude" name="attitude">
                <option value="mood_factual" @selected(old('attitude', 'mood_factual') === 'mood_factual')>Sachlich</option>
                <option value="mood_friendly" @selected(old('attitude') === 'mood_friendly')>Freundlich</option>
                <option value="mood_hostile"  @selected(old('attitude') === 'mood_hostile')>Feindlich</option>
            </select>
        </div>
        <div class="msg-form-field">
            <label for="subject">Betreff</label>
            <input type="text" id="subject" name="subject"
                   value="{{ old('subject') }}" maxlength="255" required>
        </div>
        <div class="msg-form-field">
            <label for="text">Nachricht</label>
            <textarea id="text" name="text" rows="8" required>{{ old('text') }}</textarea>
        </div>
        <div class="msg-form-actions">
            <button type="submit" class="msg-action-btn msg-action-btn--primary">
                <i class="bi bi-send"></i> Senden
            </button>
            <a href="{{ route('messages.inbox') }}" class="msg-action-btn">Abbrechen</a>
        </div>
    </form>
</div>
</div>
@endsection
