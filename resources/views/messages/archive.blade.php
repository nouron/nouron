@extends('layouts.app')

@section('title', 'Archiv — Nouron')

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
                <a class="nav-link" href="{{ route('messages.outbox') }}">
                    <i class="bi bi-send"></i> Ausgang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('messages.archive') }}">
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
    <div class="accordion col-12" id="archive-accordion">
        @if($messages->isEmpty())
            <p class="text-muted fst-italic">Keine archivierten Nachrichten.</p>
        @endif
        @foreach($messages as $message)
        <div id="message-{{ $message->id }}" class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-message-{{ $message->id }}">
                    <span class="me-2"><i class="bi bi-clock"></i> {{ $message->tick }}</span>
                    <strong class="me-2">{{ $message->sender }}:</strong>
                    {{ $message->subject }}
                </button>
            </h2>
            <div id="collapse-message-{{ $message->id }}" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <p>{{ $message->text }}</p>
                    <div class="d-flex gap-2 flex-wrap mt-2 message-options"
                         id="message-{{ $message->id }}-options">
                        <form method="POST" action="{{ route('messages.remove', $message->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    title="Löschen">
                                <i class="bi bi-x-lg"></i> löschen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <ul class="pagination mt-3">
        <li class="page-item"><a class="page-link" href="#">&laquo;</a></li>
        <li class="page-item"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
    </ul>
</div>
@endsection
