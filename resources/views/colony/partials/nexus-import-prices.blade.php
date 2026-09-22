{{--
    Nexus import price list (A13 P4): base price (struck through while the
    Handelsvorteil lowers it), the price actually charged, the advantage sources
    one by one, and a quiet hint for a missing source. All numbers come from
    NexusImportService::quote() / TradeAdvantagePresenter — nothing is computed here.

    Params:
      nexusImport — NexusImportService::quote() + 'advantage_view' (presenter output)
      resourceIds — resource ids to list, in display order
--}}
@php
    $priceView = $nexusImport["advantage_view"];
    $resourceLabels = [
        3 => "resources.res_regolith",
        4 => "resources.res_werkstoffe",
        5 => "resources.res_organika",
    ];
    $sourceText = implode(
        ", ",
        array_map(fn($line) => $line["label"] . " " . $line["percent_text"], $priceView["lines"]),
    );
@endphp
<ul class="cc-nexus-prices">
    @foreach ($resourceIds as $resourceId)
        @php $row = $nexusImport["resources"][$resourceId]; @endphp
        <li class="cc-nexus-price">
            <span class="cc-nexus-price-name">{{ __($resourceLabels[$resourceId]) }}</span>
            @if ($row["price"] < $row["base"])
                <s class="cc-nexus-price-base">{{ $row["base"] }}</s>
            @endif
            <strong
                class="cc-nexus-price-final">{{ __("colony.nexus_import_price_each", ["price" => $row["price"]]) }}</strong>
        </li>
    @endforeach
</ul>
@if ($sourceText !== "")
    <p class="cc-card-hint cc-nexus-price-sources">
        {{ __("colony.nexus_import_price_sources", ["sources" => $sourceText]) }}</p>
@endif
@foreach ($priceView["hints"] as $hint)
    <p class="cc-card-hint cc-nexus-price-hint">{{ $hint }}</p>
@endforeach
