@extends('layouts.app')
@section('title', 'Ereignisse — Nouron')

@section('content')
@include('messages.partials.tabs')

<div class="row">
    <div class="col-12">
        @if($events->isEmpty())
            <p class="text-muted fst-italic">Keine Ereignisse vorhanden.</p>
        @else
            <div class="accordion" id="events-accordion">
                @foreach($events as $event)
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse-event-{{ $event->id }}"
                                aria-expanded="false">
                            <i class="bi bi-clock me-2 text-muted"></i>
                            <span class="me-2 text-muted small">Tick {{ $event->tick }}</span>
                            <span class="badge bg-secondary me-2">{{ $event->area }}</span>
                            {{ $event->event }}
                        </button>
                    </h2>
                    <div id="collapse-event-{{ $event->id }}" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-2">Bereich</dt>
                                <dd class="col-sm-10">{{ $event->area }}</dd>
                                <dt class="col-sm-2">Ereignis</dt>
                                <dd class="col-sm-10">{{ $event->event }}</dd>
                                <dt class="col-sm-2">Tick</dt>
                                <dd class="col-sm-10">{{ $event->tick }}</dd>
                                @if($event->parameters)
                                <dt class="col-sm-2">Parameter</dt>
                                <dd class="col-sm-10"><code>{{ $event->parameters }}</code></dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
