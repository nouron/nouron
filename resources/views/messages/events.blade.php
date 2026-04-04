@extends('layouts.app')
@section('title', 'Ereignisse — Nouron')

@section('content')
@include('messages.partials.tabs')

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-xl-8">
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
                                // tech_id can refer to a building, research, or ship — try all three
                                $entity = \App\Models\Building::find((int) $value)
                                    ?? \App\Models\Research::find((int) $value)
                                    ?? \App\Models\Ship::find((int) $value);
                                $resolved['tech'] = $entity
                                    ? '<strong>' . e(__('techtree.' . $entity->name)) . '</strong>'
                                    : '<strong>Technologie #' . (int) $value . '</strong>';
                                break;

                            case 'research_id':
                                $res = \App\Models\Research::find((int) $value);
                                $resolved['research'] = $res
                                    ? '<strong>' . e(__('techtree.' . $res->name)) . '</strong>'
                                    : '<strong>Forschung #' . (int) $value . '</strong>';
                                break;

                            case 'colony_id':
                                $col = (int) $value > 0 ? \App\Models\Colony::find((int) $value) : null;
                                $resolved['colony'] = $col
                                    ? '<strong>' . e($col->name) . '</strong>'
                                    : '<strong>unbekannte Kolonie</strong>';
                                break;

                            case 'fleet_id':
                                $fleet = \App\Models\Fleet::find((int) $value);
                                $resolved['fleet'] = $fleet
                                    ? '<strong>' . e($fleet->fleet) . '</strong>'
                                    : '<strong>Flotte #' . (int) $value . '</strong>';
                                break;

                            case 'attacker_id':
                                $attacker = (int) $value > 0 ? \App\Models\User::find((int) $value) : null;
                                $resolved['attacker'] = $attacker
                                    ? '<strong>' . e($attacker->username) . '</strong>'
                                    : '<strong>unbekannter Angreifer</strong>';
                                break;

                            case 'defender_id':
                                $defender = (int) $value > 0 ? \App\Models\User::find((int) $value) : null;
                                $resolved['defender'] = $defender
                                    ? '<strong>' . e($defender->username) . '</strong>'
                                    : '<strong>unbekannter Verteidiger</strong>';
                                break;

                            case 'coords':
                                if (is_array($value) && count($value) >= 2) {
                                    $resolved['coords'] = '<strong>' . (int) $value[0] . '/' . (int) $value[1] . '</strong>';
                                }
                                break;
                        }
                    }

                    // Fill missing placeholders so raw ":placeholder" never leaks into the output
                    $resolved += [
                        'tech'      => '<em class="text-muted">unbekannt</em>',
                        'research'  => '<em class="text-muted">unbekannt</em>',
                        'colony'    => '<em class="text-muted">unbekannte Kolonie</em>',
                        'fleet'     => '<em class="text-muted">unbekannte Flotte</em>',
                        'attacker'  => '<em class="text-muted">unbekannt</em>',
                        'defender'  => '<em class="text-muted">unbekannt</em>',
                        'coords'    => '<em class="text-muted">?/?</em>',
                    ];

                    $langKey = 'events.' . str_replace('.', '_', $event->event);
                    $text = __($langKey, $resolved);
                    if ($text === $langKey) {
                        $text = __('events.unknown', ['event' => e($event->event)]);
                    }

                    // Icon per area
                    $icon = match($event->area) {
                        'techtree' => 'bi-tools',
                        'galaxy'   => 'bi-rocket-takeoff',
                        'trade'    => 'bi-bag',
                        default    => 'bi-info-circle',
                    };
                @endphp
                <div class="list-group-item d-flex align-items-start gap-2">
                    <i class="bi {{ $icon }} mt-1 text-muted flex-shrink-0"></i>
                    <div>
                        <span class="text-muted small me-2">Tick {{ $event->tick }}</span>
                        {!! $text !!}
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
