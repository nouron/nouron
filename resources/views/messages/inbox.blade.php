@extends('layouts.app')

@section('title', 'Posteingang — Nouron')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('messages.inbox') }}">
                    <i class="bi bi-inbox"></i> Eingang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('messages.outbox') }}">
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
    <div class="accordion col-12" id="inbox-accordion">
        @if($messages->isEmpty())
            <p class="text-muted fst-italic">Keine Nachrichten im Eingang.</p>
        @endif
        @foreach($messages as $message)
        <div id="message-{{ $message->id }}" class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-message-{{ $message->id }}"
                        aria-expanded="false">
                    <i class="bi bi-clock me-1"></i> {{ $message->tick }}
                    &nbsp;<strong>{{ $message->sender }}:</strong>&nbsp;
                    @if(!$message->is_read)
                        <i class="bi bi-envelope-fill text-primary me-1"></i>
                    @endif
                    {{ $message->subject }}
                </button>
            </h2>
            <div id="collapse-message-{{ $message->id }}" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <p>{{ $message->text }}</p>
                    <div class="d-flex gap-2 flex-wrap mt-2 message-options"
                         id="message-{{ $message->id }}-options">
                        @if(!$message->is_read)
                            <button class="btn btn-sm btn-outline-secondary js-react"
                                    data-id="{{ $message->id }}"
                                    title="Als gelesen markieren">
                                <i class="bi bi-hand-thumbs-up"></i>
                            </button>
                        @endif
                        <form method="POST" action="{{ route('messages.archive.message', $message->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                    title="Archivieren">
                                <i class="bi bi-archive"></i> archivieren
                            </button>
                        </form>
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
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-react').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var id = btn.dataset.id;
        fetch('{{ route('messages.react') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ id: id })
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.result) {
                btn.remove();
            }
        });
    });
});
</script>
@endpush
