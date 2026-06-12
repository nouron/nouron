@extends('layouts.infra')

@section('title', 'Registrieren – Nouron')

@section('content')
<div class="infra-auth-wrap">
    <hgroup>
        <h1>Registrieren</h1>
    </hgroup>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label for="username">Benutzername
            <input type="text" id="username" name="username"
                   value="{{ old('username') }}" required autofocus
                   aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}">
            @error('username')<small>{{ $message }}</small>@enderror
        </label>

        <label for="email">E-Mail
            <input type="email" id="email" name="email"
                   value="{{ old('email') }}" required
                   aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
            @error('email')<small>{{ $message }}</small>@enderror
        </label>

        <label for="password">Passwort
            <input type="password" id="password" name="password" required
                   aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
            @error('password')<small>{{ $message }}</small>@enderror
        </label>

        <label for="password_confirmation">Passwort bestätigen
            <input type="password" id="password_confirmation"
                   name="password_confirmation" required>
        </label>

        <button type="submit">Registrieren</button>
    </form>

    <p class="infra-auth-footer">
        Bereits registriert? <a href="{{ route('login') }}">Anmelden</a>
    </p>
</div>
@endsection
