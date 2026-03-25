@extends('layouts.app')

@section('title', 'Postausgang — Nouron')

@section('content')
@include('messages.partials.tabs')

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
                    {{-- Outbox action buttons are disabled (sender cannot react to own messages) --}}
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        <a href="#" class="btn btn-sm btn-outline-secondary disabled"
                           title="positiv reagieren">
                            <i class="bi bi-hand-thumbs-up"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-secondary disabled"
                           title="positiv reagieren + antworten">
                            <i class="bi bi-hand-thumbs-up"></i> + <i class="bi bi-envelope"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-secondary disabled"
                           title="antworten">
                            <i class="bi bi-envelope"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-secondary disabled"
                           title="negativ reagieren">
                            <i class="bi bi-hand-thumbs-down"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-secondary disabled"
                           title="negativ reagieren + antworten">
                            <i class="bi bi-hand-thumbs-down"></i> + <i class="bi bi-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
