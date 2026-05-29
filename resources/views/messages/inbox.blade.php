@extends('layouts.colony')

@section('title', 'Posteingang — Nouron')

@section('content')
<div style="padding: 1.5rem 1.5rem 3rem;">
@include('messages.partials.tabs')

<div class="msg-list">
    @if($messages->isEmpty())
        <p class="msg-empty">Keine Nachrichten im Eingang.</p>
    @endif
    @foreach($messages as $message)
    <div id="message-{{ $message->id }}"
         class="msg-item @if(!$message->is_read) msg-item--unread @endif"
         x-data="{ open: false }">
        <div class="msg-header" @click="open = !open" :aria-expanded="open">
            <span class="msg-meta">Sol {{ $message->tick }}</span>
            <span class="msg-sender"><strong>{{ $message->sender }}</strong></span>
            <span class="msg-subject">{{ $message->subject }}</span>
            @if(!$message->is_read)
                <span class="msg-unread-dot" title="Ungelesen"></span>
            @endif
            <i class="bi bi-chevron-down msg-chevron" :class="{ 'msg-chevron--open': open }"></i>
        </div>
        <div class="msg-body" x-show="open" x-cloak>
            <p class="msg-text">{{ $message->text }}</p>
            <div class="msg-actions">
                @if(!$message->is_read)
                    <button class="msg-action-btn js-react" data-id="{{ $message->id }}" title="Als gelesen markieren">
                        <i class="bi bi-check2"></i> gelesen
                    </button>
                @endif
                <form method="POST" action="{{ route('messages.archive.message', $message->id) }}" style="display:inline;margin:0">
                    @csrf
                    <button type="submit" class="msg-action-btn" title="Archivieren">
                        <i class="bi bi-archive"></i> archivieren
                    </button>
                </form>
                <form method="POST" action="{{ route('messages.remove', $message->id) }}" style="display:inline;margin:0">
                    @csrf
                    <button type="submit" class="msg-action-btn msg-action-btn--danger" title="Löschen">
                        <i class="bi bi-x-lg"></i> löschen
                    </button>
                </form>
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
            if (data.result) { btn.remove(); }
        });
    });
});
</script>
@endpush
