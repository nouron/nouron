@extends('layouts.app')

@section('title', 'Postausgang — Nouron')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('messages.inbox') }}">
                    <i class="bi bi-inbox"></i> Eingang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('messages.outbox') }}">
                    <i class="bi bi-send"></i> Ausgang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('messages.archive') }}">
                    <i class="bi bi-archive"></i> Archiv
                </a>
            </li>
            <li class="nav-item ms-auto">
                <a class="nav-link" href="{{ route('messages.compose') }}">
                    <i class="bi bi-pencil-square"></i> Neue Nachricht
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="accordion col-12" id="outbox-accordion">
        @if($messages->isEmpty())
            <p class="text-muted fst-italic">Keine Nachrichten im Ausgang.</p>
        @endif
        @foreach($messages as $message)
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-message-{{ $message->id }}">
                    <i class="bi bi-clock me-1"></i> {{ $message->tick }}
                    &nbsp;<strong>an {{ $message->recipient }}:</strong>&nbsp;
                    {{ $message->subject }}
                </button>
            </h2>
            <div id="collapse-message-{{ $message->id }}" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <p>{{ $message->text }}</p>
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        <span class="text-muted small">Gesendet in Tick {{ $message->tick }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
