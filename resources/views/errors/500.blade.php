@extends('layouts.app')

@section('title', '500 — Serverfehler')

@section('content')
<div class="text-center py-5">
    <h1 class="display-1 text-muted">500</h1>
    <h2 class="mb-3">Interner Serverfehler</h2>
    <p class="text-muted mb-4">Es ist ein unerwarteter Fehler aufgetreten. Bitte versuche es später erneut.</p>
    <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house"></i> Zurück zur Startseite</a>
</div>
@endsection
