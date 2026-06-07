{{--
    Entity Chip — inline pill with hover/tap tooltip.

    Props:
      $type       — string: 'building'|'knowledge'|'resource'|'ship'|'advisor'|'research'
      $entityKey  — string: internal config/DB key (e.g. 'building_commandCenter', 'res_regolith')
      $label      — string: already-translated display name
      $tooltip    — array: optional tooltip data
                      'level'       (int, optional)   — current level
                      'description' (string, optional) — one-sentence description
                      'link'        (string, optional) — URL to detail screen
                      'meta'        (string, optional) — extra info line (pre-translated)

    Usage:
      <x-entity-chip
          type="building"
          entity-key="building_harvester"
          label="{{ __('techtree.building_harvester') }}"
          :tooltip="['level' => 3, 'description' => __('buildings.harvester_desc'), 'link' => '/colony']"
      />
--}}

@php
    /** Map entity type to Bootstrap Icon class */
    $iconMap = [
        'building'  => 'bi-hexagon',
        'knowledge' => 'bi-book',
        'resource'  => 'bi-layers',   // fallback — callers may pass more specific via $tooltip['icon']
        'ship'      => 'bi-rocket-takeoff',
        'advisor'   => 'bi-person-badge',
        'research'  => 'bi-diagram-3',
    ];

    $icon     = $iconMap[$type] ?? 'bi-circle';
    $hasLink  = !empty($tooltip['link'] ?? null);
    $tag      = $hasLink ? 'a' : 'span';
    $href     = $hasLink ? e($tooltip['link']) : null;

    // Tooltip content availability flags
    $hasLevel = isset($tooltip['level']) && $tooltip['level'] !== null && $tooltip['level'] !== '';
    $hasDesc  = !empty($tooltip['description'] ?? null);
    $hasMeta  = !empty($tooltip['meta'] ?? null);
    $hasTooltip = $hasLevel || $hasDesc || $hasMeta;
@endphp

<{{ $tag }}
    class="entity-chip entity-chip--{{ $type }}"
    data-chip-type="{{ $type }}"
    data-chip-key="{{ $entityKey }}"
    @if($hasLink) href="{{ $href }}" @endif
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
            <a href="{{ $href }}">{{ __('entity_chip.label_open_link') }}</a>
        </span>
        @endif
    </span>@endif</{{ $tag }}>
