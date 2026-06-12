@extends('layouts.infra')

@section('title', 'Einstellungen – Nouron')

@section('content')
<div style="max-width: 36rem;">
    <h2>Einstellungen</h2>

    {{-- Display Name --}}
    <article>
        <header>
            <strong><i class="bi bi-person"></i> Anzeigename ändern</strong>
        </header>
        <form method="POST" action="{{ route('user.update.displayname') }}">
            @csrf
            @method('PATCH')
            <label for="display_name">Anzeigename
                <input type="text" id="display_name" name="display_name"
                       value="{{ old('display_name', $user->display_name) }}"
                       maxlength="50" required
                       aria-invalid="{{ $errors->has('display_name') ? 'true' : 'false' }}">
                @error('display_name')<small>{{ $message }}</small>@enderror
            </label>
            <button type="submit" style="width:auto;">
                <i class="bi bi-check-lg"></i> Speichern
            </button>
        </form>
    </article>

    {{-- Password --}}
    <article>
        <header>
            <strong><i class="bi bi-lock"></i> Passwort ändern</strong>
        </header>
        <form method="POST" action="{{ route('user.update.password') }}">
            @csrf
            @method('PATCH')
            <label for="current_password">Aktuelles Passwort
                <input type="password" id="current_password" name="current_password" required
                       aria-invalid="{{ $errors->has('current_password') ? 'true' : 'false' }}">
                @error('current_password')<small>{{ $message }}</small>@enderror
            </label>
            <label for="password">Neues Passwort
                <input type="password" id="password" name="password" required
                       aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                @error('password')<small>{{ $message }}</small>@enderror
            </label>
            <label for="password_confirmation">Passwort bestätigen
                <input type="password" id="password_confirmation"
                       name="password_confirmation" required>
            </label>
            <button type="submit" style="width:auto;">
                <i class="bi bi-check-lg"></i> Passwort ändern
            </button>
        </form>
    </article>

    {{-- Onboarding hints --}}
    <article>
        <header>
            <strong><i class="bi bi-info-circle"></i> Onboarding-Hinweise</strong>
        </header>
        <form method="POST" action="{{ route('user.update.onboarding') }}">
            @csrf
            @method('PATCH')
            {{-- Hidden field ensures value 0 is sent when checkbox is unchecked --}}
            <input type="hidden" name="onboarding_hints" value="0">
            <label>
                <input type="checkbox" role="switch"
                       id="onboarding_hints" name="onboarding_hints" value="1"
                       {{ $onboarding_hints ? 'checked' : '' }}>
                Onboarding-Hinweise anzeigen
            </label>
            <p style="font-size:0.85rem; color:var(--pico-muted-color,#6c757d); margin:0.5rem 0 1rem;">
                Wenn aktiviert, werden beim Spielstart hilfreiche Hinweise und Erklärungen eingeblendet.
            </p>
            <button type="submit" style="width:auto;">
                <i class="bi bi-check-lg"></i> Speichern
            </button>
        </form>
    </article>

</div>
@endsection
