@extends('layouts.app')
@section('title', 'INNN — Nouron')

@section('content')
@include('messages.partials.tabs')

@php
$topicBadge = [
    'economy'   => 'bg-success',
    'politics'  => 'bg-primary',
    'diplomacy' => 'bg-info text-dark',
    'culture'   => 'bg-warning text-dark',
    'sports'    => 'bg-danger',
    'misc'      => 'bg-secondary',
];
$topicLabel = [
    'economy'   => 'Wirtschaft',
    'politics'  => 'Politik',
    'diplomacy' => 'Diplomatie',
    'culture'   => 'Kultur',
    'sports'    => 'Sport',
    'misc'      => 'Verschiedenes',
];
@endphp

<div class="row">
    <div class="col-12">
        @if($news->isEmpty())
            <p class="text-muted fst-italic">Keine Nachrichten vorhanden.</p>
        @else
            <div class="list-group">
                @foreach($news as $item)
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 fw-bold">{{ $item->headline }}</h6>
                        <div class="d-flex gap-2 align-items-center ms-3 flex-shrink-0">
                            <span class="badge {{ $topicBadge[$item->topic] ?? 'bg-secondary' }}">
                                {{ $topicLabel[$item->topic] ?? $item->topic }}
                            </span>
                            <span class="text-muted small">Tick {{ $item->tick }}</span>
                        </div>
                    </div>
                    <p class="mb-0 text-muted small">{{ $item->text }}</p>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
