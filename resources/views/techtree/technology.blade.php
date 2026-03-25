{{--
    techtree/technology.blade.php — AJAX modal partial, NO @extends

    Loaded via $.ajax() into .techModal .modal-dialog when a tech button is clicked.
    techtree.js replaces the entire .modal-dialog element with this response HTML,
    so this file renders a full .modal-dialog/.modal-content structure.

    Variables (from TechtreeController::technology):
      $type                  — 'building' | 'research' | 'ship' | 'personell'
      $techId                — int
      $tech                  — array (id, name, level, ap_spend, ap_for_levelup,
                                       status_points, max_status_points, max_level,
                                       purpose, required_building_id,
                                       required_building_level, required_research_id)
      $costs                 — Collection of stdClass (resource_id, amount)
      $resources             — Collection keyed by id (name, abbreviation, icon)
      $apAvailable           — int
      $requiredBuildingsCheck — bool
      $requiredResourcesCheck — bool
      $buildings             — array|null (all buildings keyed by id, for name lookup)
      $researches            — array|null (all researches keyed by id, for ship requirement lookup)
--}}
@php
    $techId   = (int)($tech['id'] ?? 0);
    $level    = (int)($tech['level'] ?? 0);
    $purpose  = $tech['purpose'] ?? '';
    $reqBldId = $tech['required_building_id'] ?? null;
    $reqBldLv = (int)($tech['required_building_level'] ?? 1);
    $reqResId = $tech['required_research_id'] ?? null;
@endphp

<div class="modal-dialog" role="document">
    <div class="modal-content">

        <div class="modal-header">
            <h3 class="modal-title">
                {{ __('techtree.' . ($tech['name'] ?? '')) }}
                ({{ $level }})
                <small class="text-muted fs-6">({{ __('techtree.types_' . $type) }}{{ $purpose ? ' / ' . __('techtree.purposes_' . $purpose) : '' }})</small>
            </h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>

        <div class="modal-body">

            <p class="mb-2">freie AP: <strong>{{ $apAvailable }}</strong></p>

            <h4>Kosten und Voraussetzungen</h4>

            <table class="table table-sm table-borderless">

                {{-- Row 1: resource costs --}}
                <tr>
                    <td class="align-middle" style="width:2rem;">
                        @if($requiredResourcesCheck)
                            <i class="bi bi-check-circle-fill text-success" title="Ressourcen verfügbar"></i>
                        @else
                            <i class="bi bi-x-circle-fill text-danger" title="Ressourcen fehlen"></i>
                        @endif
                    </td>
                    <td>
                        @foreach($costs as $cost)
                        @if((int)$cost->amount > 0)
                        @php $res = $resources[$cost->resource_id] ?? null; @endphp
                        @if($res)
                            <span class="resicon-{{ $res->abbreviation }}"
                                  data-bs-toggle="tooltip"
                                  title="{{ $res->name }}">{{ $res->abbreviation }}</span>
                            {{ (int)$cost->amount }}
                        @else
                            Res#{{ $cost->resource_id }}: {{ (int)$cost->amount }}
                        @endif
                        @endif
                        @endforeach
                    </td>
                </tr>

                {{-- Row 2: required building (buildings and researches only) --}}
                @if($reqBldId && $type !== 'personell')
                @php
                    $rawBldName = $buildings[$reqBldId]['name'] ?? '';
                    $reqBldName = $rawBldName ? __('techtree.' . $rawBldName) : 'Gebäude #' . $reqBldId;
                    $reqBldCurrentLevel = $buildings[$reqBldId]['level'] ?? 0;
                    $reqBldMet = (int)$reqBldCurrentLevel >= $reqBldLv;
                @endphp
                <tr>
                    <td class="align-middle">
                        @if($reqBldMet)
                            <i class="bi bi-check-circle-fill text-success" title="Voraussetzung erfüllt"></i>
                        @else
                            <i class="bi bi-x-circle-fill text-danger" title="Voraussetzung nicht erfüllt"></i>
                        @endif
                    </td>
                    <td>
                        {{ $reqBldName }} ({{ $reqBldLv }})
                    </td>
                </tr>
                @endif

                {{-- Row 3: required research (ships only) --}}
                @if($type === 'ship' && $reqResId)
                @php
                    $rawResName = $researches[$reqResId]['name'] ?? '';
                    $reqResName = $rawResName ? __('techtree.' . $rawResName) : 'Forschung #' . $reqResId;
                    $reqResLevel = $researches[$reqResId]['level'] ?? 0;
                    $reqResLv = (int)($tech['required_research_level'] ?? 1);
                    $reqResMet = (int)$reqResLevel >= $reqResLv;
                @endphp
                <tr>
                    <td class="align-middle">
                        @if($reqResMet)
                            <i class="bi bi-check-circle-fill text-success" title="Forschung erfüllt"></i>
                        @else
                            <i class="bi bi-x-circle-fill text-danger" title="Forschung fehlt"></i>
                        @endif
                    </td>
                    <td>
                        {{ $reqResName }} ({{ $reqResLv }})
                    </td>
                </tr>
                @endif

                {{-- Row 4: status bar + leveldown (not for personell) --}}
                @if($type !== 'personell')
                <tr>
                    <td colspan="2">
                        @include('techtree.partials.techstatus_bar', [
                            'tech'        => $tech,
                            'type'        => $type,
                            'apAvailable' => $apAvailable,
                        ])
                        {{-- leveldown active only when status_points == 0 and level > 0 --}}
                        @php $canLevelDown = ($level > 0 && (int)($tech['status_points'] ?? -1) === 0); @endphp
                        <a id="{{ $type }}-{{ $techId }}|leveldown"
                           class="btn btn-sm btn-danger mt-1{{ $canLevelDown ? '' : ' disabled' }}"
                           href="#"
                           role="button"
                           title="Stufe abbauen">
                            <i class="bi bi-arrow-down-circle"></i> abbauen
                        </a>
                    </td>
                </tr>
                @else
                {{-- personell: feuern button instead --}}
                <tr>
                    <td colspan="2">
                        @php $canFire = ($level > 0); @endphp
                        <a id="{{ $type }}-{{ $techId }}|leveldown"
                           class="btn btn-sm btn-danger{{ $canFire ? '' : ' disabled' }}"
                           href="#"
                           role="button"
                           title="Personal feuern">
                            <i class="bi bi-person-dash"></i> feuern
                        </a>
                    </td>
                </tr>
                @endif

                {{-- Row 5: AP levelup bar + levelup button --}}
                <tr>
                    <td colspan="2">
                        @include('techtree.partials.techlevelup_bar', [
                            'tech'        => $tech,
                            'type'        => $type,
                            'apAvailable' => $apAvailable,
                        ])
                        @php
                            $apForLevelup = (int)($tech['ap_for_levelup'] ?? 1);
                            $apSpend      = (int)($tech['ap_spend'] ?? 0);
                            $maxLevel     = (int)($tech['max_level'] ?? 0);
                            $canLevelUp   = ($apSpend >= $apForLevelup)
                                            && ($maxLevel === 0 || $level < $maxLevel);
                            $btnLabel     = $type === 'personell' ? 'anheuern' : 'ausbauen';
                            $btnIcon      = $type === 'personell' ? 'bi-person-plus' : 'bi-arrow-up-circle';
                        @endphp
                        <a id="{{ $type }}-{{ $techId }}|levelup"
                           class="btn btn-sm btn-success mt-1{{ $canLevelUp ? '' : ' disabled' }}"
                           href="#"
                           role="button"
                           title="{{ $btnLabel }}">
                            <i class="bi {{ $btnIcon }}"></i> {{ $btnLabel }}
                        </a>
                    </td>
                </tr>

            </table>

        </div>{{-- .modal-body --}}

    </div>{{-- .modal-content --}}
</div>{{-- .modal-dialog --}}
