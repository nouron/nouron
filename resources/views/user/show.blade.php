@extends('layouts.infra')

@section('title', 'Profil – ' . $user->username)

@section('content')
<div style="max-width: 32rem;">
    <h2 style="display:flex; align-items:center; gap:0.5rem;">
        <i class="bi bi-person-circle"></i> {{ $user->username }}
    </h2>

    <dl>
        <dt>Anzeigename</dt>
        <dd>{{ $user->display_name }}</dd>

        <dt>E-Mail</dt>
        <dd>{{ $user->email }}</dd>

        <dt>Rolle</dt>
        <dd>{{ $user->role }}</dd>

        <dt>Registriert</dt>
        <dd>{{ $user->registration }}</dd>
    </dl>

    <a href="{{ route('user.settings') }}" role="button" class="secondary outline" style="width:auto;">
        <i class="bi bi-gear"></i> Einstellungen
    </a>
</div>
@endsection
