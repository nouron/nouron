@extends('layouts.colony')
@section('title', 'Aktionen — Nouron')

@section('content')
<div style="padding: 1.5rem 1.5rem 3rem;">
@include('messages.partials.tabs')
<div class="msg-list">
    @if($actions->isEmpty())
        <p class="msg-empty">Noch keine Aktionen aufgezeichnet.</p>
    @else
        @foreach($actions as $event)
        @php
            $params = [];
            if ($event->parameters) {
                $raw = json_decode($event->parameters, true);
                if (is_array($raw)) $params = $raw;
            }

            $resolved = [];
            foreach ($params as $key => $value) {
                switch ($key) {
                    case 'colony_id':
                        $col = (int) $value > 0 ? \App\Models\Colony::find((int) $value) : null;
                        $resolved['colony'] = $col
                            ? '<strong>' . e($col->name) . '</strong>'
                            : '<strong>Kolonie #' . (int) $value . '</strong>';
                        break;

                    case 'building_id':
                        $building = \App\Models\Building::find((int) $value);
                        $resolved['tech'] = $building
                            ? '<strong>' . e(__('buildings.' . $building->name)) . '</strong>'
                            : '<strong>Gebäude #' . (int) $value . '</strong>';
                        break;

                    case 'advisor_type':
                        $advisorName = __('advisors.' . $value);
                        $resolved['advisor_type'] = '<strong>' . e($advisorName !== 'advisors.' . $value ? $advisorName : $value) . '</strong>';
                        break;

                    case 'item_id':
                        $resolved['item_id'] = '<strong>#' . (int) $value . '</strong>';
                        break;

                    case 'sol':
                        $resolved['sol'] = '<strong>' . (int) $value . '</strong>';
                        break;
                }
            }

            $resolved += [
                'colony'      => '<em class="text-muted">unbekannte Kolonie</em>',
                'tech'        => '<em class="text-muted">unbekannt</em>',
                'advisor_type'=> '<em class="text-muted">unbekannt</em>',
                'item_id'     => '<em class="text-muted">?</em>',
                'sol'         => '<em class="text-muted">?</em>',
            ];

            $langKey = 'events.' . str_replace('.', '_', $event->event);
            $text = __($langKey, $resolved);
            if ($text === $langKey) {
                $text = __('events.unknown', ['event' => e($event->event)]);
            }

            $icon = match($event->area) {
                'colony'   => 'bi-globe2',
                'run'      => 'bi-hourglass-split',
                'trade'    => 'bi-bag',
                'techtree' => 'bi-tools',
                default    => 'bi-cursor',
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
