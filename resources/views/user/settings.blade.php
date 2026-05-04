@extends('layouts.app')

@section('title', 'Einstellungen – Nouron')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Display Name --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person"></i> Anzeigename ändern</div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.update.displayname') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Anzeigename</label>
                        <input type="text" id="display_name" name="display_name"
                               class="form-control @error('display_name') is-invalid @enderror"
                               value="{{ old('display_name', $user->display_name) }}"
                               maxlength="50" required>
                        @error('display_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg"></i> Speichern
                    </button>
                </form>
            </div>
        </div>

        {{-- Password --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lock"></i> Passwort ändern</div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.update.password') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Aktuelles Passwort</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Neues Passwort</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Passwort bestätigen</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg"></i> Passwort ändern
                    </button>
                </form>
            </div>
        </div>

        {{-- Onboarding hints toggle --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle"></i> Onboarding-Hinweise</div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.update.onboarding') }}">
                    @csrf
                    @method('PATCH')
                    {{-- Hidden field ensures value 0 is sent when checkbox is unchecked --}}
                    <input type="hidden" name="onboarding_hints" value="0">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="onboarding_hints" name="onboarding_hints" value="1"
                               {{ $onboarding_hints ? 'checked' : '' }}>
                        <label class="form-check-label" for="onboarding_hints">
                            Onboarding-Hinweise anzeigen
                        </label>
                    </div>
                    <p class="text-muted small mb-3">
                        Wenn aktiviert, werden beim Spielstart hilfreiche Hinweise und Erklärungen eingeblendet.
                    </p>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg"></i> Speichern
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
