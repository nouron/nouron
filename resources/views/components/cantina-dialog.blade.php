{{--
    Cantina dialog — portrait fills the full left side, name/role/body content
    on the right. Shared by the offer dialog and the merchant dialog (same
    NPC/portrait presentation, only the body slot content differs).
    Mobile: stacks — portrait on top (fixed height), name/role below, then
    the slot content.

    Props:
      $portraitSrc  — base (1x) image URL, e.g. asset('img/characters/tomas.webp')
      $portraitLgSrc — optional 2x/HiDPI image URL; omit to skip srcset
      $name         — display name (string)
      $role         — role/subtitle (string, optional)
    Slot: body content (offer chips, expiry text, action buttons, ...)
--}}
<div class="cantina-dialog">
    <div class="cantina-dialog__portrait">
        <img src="{{ $portraitSrc }}"
            @if (!empty($portraitLgSrc)) srcset="{{ $portraitSrc }} 1x, {{ $portraitLgSrc }} 2x" @endif
            alt="{{ $name }}">
    </div>
    <div class="cantina-dialog__body">
        <h3 class="cantina-dialog__name">{{ $name }}</h3>
        @if (!empty($role))
            <div class="cantina-dialog__role">{{ $role }}</div>
        @endif
        <div class="cantina-dialog__content">
            {{ $slot }}
        </div>
    </div>
</div>
