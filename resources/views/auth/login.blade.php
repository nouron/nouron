@extends('layouts.infra')

@section('title', 'Anmelden – Nouron')

@section('content')
<div class="infra-auth-wrap">
    <hgroup>
        <h1>Anmelden</h1>
    </hgroup>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="username">Benutzername
            <input type="text" id="username" name="username"
                   value="{{ old('username') }}" required autofocus
                   aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}">
            @error('username')<small>{{ $message }}</small>@enderror
        </label>

        <label for="password">Passwort
            <input type="password" id="password" name="password" required
                   aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
            @error('password')<small>{{ $message }}</small>@enderror
        </label>

        <label>
            <input type="checkbox" name="remember">
            Angemeldet bleiben
        </label>

        <button type="submit">Anmelden</button>
    </form>

    <p class="infra-auth-footer">
        Noch kein Konto? <a href="{{ route('register') }}">Registrieren</a>
    </p>
</div>
@endsection
