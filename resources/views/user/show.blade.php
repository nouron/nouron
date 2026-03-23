@extends('layouts.app')

@section('title', 'Profil – ' . $user->username)

@section('content')
<h2><i class="bi bi-person-circle"></i> {{ $user->username }}</h2>

<div class="row">
    <div class="col-md-4">
        <dl class="row">
            <dt class="col-sm-5">Anzeigename</dt>
            <dd class="col-sm-7">{{ $user->display_name }}</dd>

            <dt class="col-sm-5">E-Mail</dt>
            <dd class="col-sm-7">{{ $user->email }}</dd>

            <dt class="col-sm-5">Rolle</dt>
            <dd class="col-sm-7">{{ $user->role }}</dd>

            <dt class="col-sm-5">Registriert</dt>
            <dd class="col-sm-7">{{ $user->registration }}</dd>
        </dl>

        <a href="{{ route('user.settings') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-gear"></i> Einstellungen
        </a>
    </div>
</div>
@endsection
