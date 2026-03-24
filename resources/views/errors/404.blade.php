@extends('layouts.app')

@section('title', '404 — Nicht gefunden')

@section('content')
<div class="text-center py-5">
    <h1 class="display-1 text-muted">404</h1>
    <h2 class="mb-3">Seite nicht gefunden</h2>
    <p class="text-muted mb-4">Die angeforderte Seite existiert nicht oder wurde verschoben.</p>
    <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house"></i> Zurück zur Startseite</a>
</div>
@endsection
