@extends('layouts.app')
@section('title', 'Ereignisse — Nouron')

@section('content')
@include('messages.partials.tabs')

<div class="row">
    <div class="col-12">
        @if($events->isEmpty())
            <p class="text-muted fst-italic">Keine Ereignisse vorhanden.</p>
        @else
            <div class="list-group">
                @foreach($events as $event)
                @php
                    $params = [];
                    if ($event->parameters) {
                        $raw = @unserialize($event->parameters);
                        if (is_array($raw)) {
                            $params = $raw;
                        }
                    }

                    $resolved = [];
                    foreach ($params as $key => $value) {
                        switch ($key) {
                            case 'tech_id':
                                $tech = \App\Models\Building::find((int) $value);
                                $resolved['tech'] = $tech
                                    ? '<strong>' . e(__('techtree.' . $tech->name)) . '</strong>'
                                    : '<strong>Tech #' . $value . '</strong>';
                                break;
                            case 'research_id':
                                $res = \App\Models\Research::find((int) $value);
                                $resolved['research'] = $res
                                    ? '<strong>' . e(__('techtree.' . $res->name)) . '</strong>'
                                    : '<strong>Forschung #' . $value . '</strong>';
                                break;
                            case 'colony_id':
                                $col = \App\Models\Colony::find((int) $value);
                                $resolved['colony'] = '<strong>' . e($col ? $col->name : 'Kolonie #' . $value) . '</strong>';
                                break;
                            case 'fleet_id':
                                $resolved['fleet'] = '<strong>Flotte #' . $value . '</strong>';
                                break;
                            case 'attacker_id':
                                $attacker = \App\Models\User::find((int) $value);
                                $resolved['attacker'] = '<strong>' . e($attacker ? $attacker->username : 'Spieler #' . $value) . '</strong>';
                                break;
                            case 'defender_id':
                                $defender = \App\Models\User::find((int) $value);
                                $resolved['defender'] = '<strong>' . e($defender ? $defender->username : 'Spieler #' . $value) . '</strong>';
                                break;
                            case 'coords':
                                if (is_array($value) && count($value) >= 2) {
                                    $resolved['coords'] = '<strong>' . $value[0] . '/' . $value[1] . '</strong>';
                                }
                                break;
                        }
                    }

                    $langKey = 'events.' . str_replace('.', '_', $event->event);
                    $text = __($langKey, $resolved);
                    if ($text === $langKey) {
                        $text = __('events.unknown', ['event' => e($event->event)]);
                    }
                @endphp
                <div class="list-group-item">
                    <i class="bi bi-clock me-2 text-muted"></i>
                    <span class="me-2 text-muted small">Tick {{ $event->tick }}</span>
                    <span class="badge bg-secondary me-2">{{ $event->area }}</span>
                    {!! $text !!}
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
