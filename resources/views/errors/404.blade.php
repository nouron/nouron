@extends('layouts.infra')

@section('title', '404 — Nicht gefunden')

@section('content')
<div style="text-align:center; padding:4rem 1rem;">
    <p style="font-size:5rem; font-weight:700; color:var(--pico-muted-color,#6c757d); margin:0 0 0.5rem;">404</p>
    <h2 style="margin:0 0 0.75rem;">Seite nicht gefunden</h2>
    <p style="color:var(--pico-muted-color,#6c757d); margin-bottom:1.5rem;">
        Die angeforderte Seite existiert nicht oder wurde verschoben.
    </p>
    <a href="{{ url('/') }}" role="button" style="width:auto;">
        <i class="bi bi-house"></i> Zurück zur Startseite
    </a>
</div>
@endsection
