{{--
    Reusable chip popup.
    Variables:
      $popup_title  — bold heading (string)
      $popup_desc   — one-sentence description (string)
      $popup_extra  — optional extra HTML rows (raw, server-controlled only)
--}}
<div class="res-popup" x-show="$data.open ?? open" x-cloak>
    <div class="res-popup-header">{{ $popup_title }}</div>
    <div class="res-popup-body">{{ $popup_desc }}</div>
    @if(!empty($popup_extra ?? null))
        <div class="res-popup-extra">{!! $popup_extra !!}</div>
    @endif
</div>
