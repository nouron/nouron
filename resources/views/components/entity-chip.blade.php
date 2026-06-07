{{--
    Entity Chip — inline pill with hover/tap tooltip.

    Props:
      $type       — string: 'building'|'knowledge'|'resource'|'ship'|'advisor'|'research'
      $entityKey  — string: internal config/DB key (e.g. 'building_commandCenter', 'res_regolith')
      $label      — string: already-translated display name
      $tooltip    — array: optional tooltip data
                      'level'       (int, optional)    — current level
                      'description' (string, optional) — one-sentence description
                      'link'        (string, optional) — URL shown as "Aufrufen" inside tooltip
                      'meta'        (string, optional) — extra info line (pre-translated)

    Note: outer element is always <span>. Links live only inside the tooltip to avoid
    nested-<a> invalid HTML when the component is placed inside a link context.
--}}

@php
    $iconMap = [
        'building'  => 'bi-hexagon',
        'knowledge' => 'bi-book',
        'resource'  => 'bi-layers',
        'ship'      => 'bi-rocket-takeoff',
        'advisor'   => 'bi-person-badge',
        'research'  => 'bi-diagram-3',
    ];

    $icon    = $iconMap[$type] ?? 'bi-circle';
    $link    = $tooltip['link'] ?? null;
    $hasLevel   = isset($tooltip['level']) && $tooltip['level'] !== null && $tooltip['level'] !== '';
    $hasDesc    = !empty($tooltip['description'] ?? null);
    $hasMeta    = !empty($tooltip['meta'] ?? null);
    $hasLink    = !empty($link);
    $hasTooltip = $hasLevel || $hasDesc || $hasMeta || $hasLink;
@endphp

<span
    class="entity-chip entity-chip--{{ $type }}"
    data-chip-type="{{ $type }}"
    data-chip-key="{{ $entityKey }}"
    x-data="{ open: false }"
    @mouseenter="open = true"
    @mouseleave="open = false"
    @click.stop="open = !open"
    @click.away="open = false"
    role="button"
    tabindex="0"
    @keydown.escape="open = false"
    @keydown.enter.prevent="open = !open"
    aria-label="{{ $label }}"
><i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $label }}@if($hasTooltip)<span class="entity-chip-tooltip" x-show="open" x-cloak>
        <span class="entity-chip-tooltip-name">{{ $label }}</span>
        @if($hasLevel)
            <span class="entity-chip-tooltip-row">
                <span class="entity-chip-tooltip-label">{{ __('entity_chip.label_level') }}</span>
                <span>{{ $tooltip['level'] }}</span>
            </span>
        @endif
        @if($hasDesc)
            <span class="entity-chip-tooltip-desc">{{ $tooltip['description'] }}</span>
        @endif
        @if($hasMeta)
            <span class="entity-chip-tooltip-row entity-chip-tooltip-meta">{{ $tooltip['meta'] }}</span>
        @endif
        @if($hasLink)
            <span class="entity-chip-tooltip-link">
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                <a href="{{ e($link) }}">{{ __('entity_chip.label_open_link') }}</a>
            </span>
        @endif
    </span>@endif</span>
