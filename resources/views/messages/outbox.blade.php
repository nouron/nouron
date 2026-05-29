@extends('layouts.colony')

@section('title', 'Postausgang — Nouron')

@section('content')
<div style="padding: 1.5rem 1.5rem 3rem;">
@include('messages.partials.tabs')
<div class="msg-list">
    @if($messages->isEmpty())
        <p class="msg-empty">Keine Nachrichten im Ausgang.</p>
    @endif
    @foreach($messages as $message)
    <div class="msg-item" x-data="{ open: false }">
        <div class="msg-header"
             @click="open = !open"
             :aria-expanded="open"
             role="button">
            <span class="msg-meta">Sol {{ $message->tick }}</span>
            <span class="msg-sender"><strong>an {{ $message->recipient }}</strong></span>
            <span class="msg-subject">{{ $message->subject }}</span>
            <i class="bi bi-chevron-down msg-chevron" :class="{ 'msg-chevron--open': open }"></i>
        </div>
        <div class="msg-body" x-show="open" x-cloak>
            <p class="msg-text">{{ $message->text }}</p>
            <p class="msg-meta">Gesendet in Sol {{ $message->tick }}</p>
        </div>
    </div>
    @endforeach
</div>
</div>
@endsection

