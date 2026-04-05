<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link @if(request()->routeIs('trade.resources')) active @endif"
           href="{{ route('trade.resources') }}">
            <i class="bi bi-boxes"></i> Rohstoffe
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(request()->routeIs('trade.researches')) active @endif"
           href="{{ route('trade.researches') }}">
            <i class="bi bi-lightbulb"></i> Forschungen
        </a>
    </li>
</ul>
