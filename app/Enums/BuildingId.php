<?php

namespace App\Enums;

/**
 * Building IDs that game logic references by name.
 *
 * This is the single source for building IDs *in code* — never inline the
 * literal, and never look the ID up via config('buildings.*.id').
 *
 * These values mirror the `id` field of the matching entry in config/buildings.php,
 * which stays canonical for per-building mechanics (costs, decay, supply) and
 * covers every building. Only the handful of IDs that logic branches on live here.
 * The values are `buildings.id` primary keys — stable, and not a balance knob.
 */
enum BuildingId: int
{
    case CommandCenter = 25;
    case Harvester = 27;
    case Housing = 28;
    case Sciencelab = 31;
    case Hangar = 44;
    case Bar = 52;
}
