{{--
    Shared building detail header: image + name + level badge.

    Parameters (PHP, Blade-side):
      $expr        — Alpine JS expression that evaluates to the building object
                     e.g. "buildingForTile(selectedTile)" or "selectedTech"
      $name_field  — field name for the display label (default: 'name')
                     colony sidebar uses 'label', techtree panel uses 'name'
      $show_header — boolean; show the name + level badge line below the image
                     (default: true). Set false when the caller already renders
                     the building name elsewhere (e.g. techtree <h3 detail-title>).

    Expected fields on the Alpine building object:
      image_slug   — file slug for /img/buildings/<slug>.webp
                     null when the item is not a building type → entire block hidden
      level        — integer; level badge shown only when > 0
      <name_field> — display name string
--}}
@php
    $nameField  = $name_field  ?? 'name';
    $showHeader = $show_header ?? true;
@endphp

{{-- Entire block hidden when image_slug is falsy (non-building types in techtree) --}}
<div class="building-detail-wrap"
     x-show="{!! $expr !!}.image_slug">

    <div class="building-detail-img-wrap">
        <img class="building-detail-img"
             :src="'/img/buildings/' + {!! $expr !!}.image_slug + '.webp'"
             :alt="{!! $expr !!}.{{ $nameField }}">
    </div>

    @if($showHeader)
    <div class="building-detail-header">
        <strong class="building-detail-name"
                x-text="{!! $expr !!}.{{ $nameField }}"></strong>
        <span class="sidebar-level-badge"
              x-show="{!! $expr !!}.level > 0"
              x-text="`Lv. ${ {!! $expr !!}.level }`"></span>
    </div>
    @endif

</div>
