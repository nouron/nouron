{{--
    Reusable chip popup.
    Variables:
      $popup_key    — unique string for this chip within the shared `openChip`
                      state on the nearest ancestor x-data (.res-bar-wrap) —
                      only one popup can be open at a time, so two adjacent
                      wide popups never render on top of each other.
      $popup_title  — bold heading (string)
      $popup_desc   — one-sentence description (string)
      $popup_extra  — optional extra HTML rows (raw, server-controlled only)
--}}
<div class="res-popup" x-show="openChip === {{ Illuminate\Support\Js::from($popup_key) }}" x-cloak
    x-effect="(openChip === {{ Illuminate\Support\Js::from($popup_key) }}) && $nextTick(() => { $el.style.marginLeft = ''; const r = $el.getBoundingClientRect(); if (r.left < 8) $el.style.marginLeft = (8 - r.left) + 'px'; else if (r.right > window.innerWidth - 8) $el.style.marginLeft = (window.innerWidth - 8 - r.right) + 'px'; })">
    <div class="res-popup-header">{{ $popup_title }}</div>
    <div class="res-popup-body">{{ $popup_desc }}</div>
    @if (!empty($popup_extra ?? null))
        <div class="res-popup-extra">{!! $popup_extra !!}</div>
    @endif
</div>
