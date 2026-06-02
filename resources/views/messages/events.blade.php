@extends('layouts.colony')
@section('title', 'Ereignisse — Nouron')

@section('content')
<div style="padding: 1.5rem 1.5rem 3rem;">
@include('messages.partials.tabs')
<div class="msg-list">
    @if($events->isEmpty())
        <p class="msg-empty">Keine Ereignisse vorhanden.</p>
    @else
        @foreach($events as $event)
        @php
            $params = [];
            if ($event->parameters) {
                $raw = json_decode($event->parameters, true);
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
                'nexus'    => 'bi-broadcast-pin',
                'colony'   => 'bi-globe2',
                'run'      => 'bi-hourglass-split',
                default    => 'bi-info-circle',
            };
        @endphp
        <div class="msg-item">
            <div class="msg-header" style="cursor: default;">
                <span class="msg-meta">Sol {{ $event->tick }}</span>
                <i class="bi {{ $icon }}" style="color: var(--color-text-secondary); flex-shrink: 0;"></i>
                {{-- {!! !!} is intentional: lang strings embed HTML-wrapped entity names.
                     All user-derived values in $resolved are sanitised with e() above. --}}
                <span class="msg-subject">{!! $text !!}</span>
            </div>
        </div>
        @endforeach
    @endif
</div>
</div>
@endsection
