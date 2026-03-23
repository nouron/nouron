@extends('layouts.app')

@section('title', 'Anmelden – Nouron')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Anmelden</div>
            <div class="card-body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">Benutzername</label>
                        <input type="text" id="username" name="username"
                               class="form-control @error('username') is-invalid @enderror"
                               value="{{ old('username') }}" required autofocus>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Passwort</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" id="remember" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">Angemeldet bleiben</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Anmelden</button>
                </form>
            </div>
            <div class="card-footer text-center">
                Noch kein Konto? <a href="{{ route('register') }}">Registrieren</a>
            </div>
        </div>
    </div>
</div>
@endsection
