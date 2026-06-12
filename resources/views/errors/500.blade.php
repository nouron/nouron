@extends('layouts.infra')

@section('title', '500 — Serverfehler')

@section('content')
<div style="text-align:center; padding:4rem 1rem;">
    <p style="font-size:5rem; font-weight:700; color:var(--pico-muted-color,#6c757d); margin:0 0 0.5rem;">500</p>
    <h2 style="margin:0 0 0.75rem;">Interner Serverfehler</h2>
    <p style="color:var(--pico-muted-color,#6c757d); margin-bottom:1.5rem;">
        Es ist ein unerwarteter Fehler aufgetreten. Bitte versuche es später erneut.
    </p>
    <a href="{{ url('/') }}" role="button" style="width:auto;">
        <i class="bi bi-house"></i> Zurück zur Startseite
    </a>
</div>
@endsection
