@extends('layouts.colony')
@section('title', 'INNN — Nouron')

@section('content')
<div style="padding: 1.5rem 1.5rem 3rem;">
@include('messages.partials.tabs')

@php
$topicLabel = [
    'economy'   => 'Wirtschaft',
    'politics'  => 'Politik',
    'diplomacy' => 'Diplomatie',
    'culture'   => 'Kultur',
    'sports'    => 'Sport',
    'misc'      => 'Verschiedenes',
];
@endphp

<div class="msg-list">
    @if($news->isEmpty())
        <p class="msg-empty">Keine Nachrichten vorhanden.</p>
    @else
        @foreach($news as $item)
        <div class="msg-item" x-data="{ open: false }">
            <div class="msg-header"
                 @click="open = !open"
                 :aria-expanded="open"
                 role="button">
                <span class="msg-meta">Sol {{ $item->tick }}</span>
                <span class="msg-sender">
                    <span class="msg-topic-badge">{{ $topicLabel[$item->topic] ?? $item->topic }}</span>
                </span>
                <span class="msg-subject">{{ $item->headline }}</span>
                <i class="bi bi-chevron-down msg-chevron" :class="{ 'msg-chevron--open': open }"></i>
            </div>
            <div class="msg-body" x-show="open" x-cloak>
                <p class="msg-text">{{ $item->text }}</p>
            </div>
        </div>
        @endforeach
    @endif
</div>
</div>
@endsection
