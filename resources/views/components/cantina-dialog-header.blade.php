{{--
    Cantina dialog header — portrait + name + role, shared by the offer dialog
    and the merchant dialog (same NPC/portrait presentation, only the body
    content below differs).

    Props:
      $portraitSrc  — base (1x) image URL, e.g. asset('img/characters/tomas.webp')
      $portraitLgSrc — optional 2x/HiDPI image URL; omit to skip srcset
      $name         — display name (string)
      $role         — role/subtitle (string, optional)
--}}
<div class="cantina-dialog-header">
    <div class="cantina-dialog-header__portrait">
        <img src="{{ $portraitSrc }}"
            @if (!empty($portraitLgSrc)) srcset="{{ $portraitSrc }} 1x, {{ $portraitLgSrc }} 2x" @endif
            alt="{{ $name }}">
    </div>
    <div>
        <h3 class="cantina-dialog-header__name">{{ $name }}</h3>
        @if (!empty($role))
            <div class="cantina-dialog-header__role">{{ $role }}</div>
        @endif
    </div>
</div>
