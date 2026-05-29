@extends('layouts.colony')

@section('title', 'Archiv — Nouron')

@section('content')
<div style="padding: 1.5rem 1.5rem 3rem;">
@include('messages.partials.tabs')
<div class="msg-list">
    @if($messages->isEmpty())
        <p class="msg-empty">Keine archivierten Nachrichten.</p>
    @endif
    @foreach($messages as $message)
    <div id="message-{{ $message->id }}" class="msg-item" x-data="{ open: false }">
        <div class="msg-header"
             @click="open = !open"
             :aria-expanded="open"
             role="button">
            <span class="msg-meta">Sol {{ $message->tick }}</span>
            <span class="msg-sender"><strong>{{ $message->sender }}</strong></span>
            <span class="msg-subject">{{ $message->subject }}</span>
            <i class="bi bi-chevron-down msg-chevron" :class="{ 'msg-chevron--open': open }"></i>
        </div>
        <div class="msg-body" x-show="open" x-cloak>
            <p class="msg-text">{{ $message->text }}</p>
            <div class="msg-actions">
                <form method="POST" action="{{ route('messages.remove', $message->id) }}" style="display:inline">
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
