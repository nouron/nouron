<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;

/** Shadow mode (S2): utility estimate for the legacy candidates of the S3 classes; null = not scored. */
final class LegacyValuation
{
    public static function estimate(Candidate $c, BotSession $b, BotContext $ctx): ?Estimate
    {
        if (in_array($c->name, ['invest_production', 'focus_engineering_levelup'], true)) {
            $row = Valuers::buildingRow($b, (int) $c->payload->building_id, (int) $c->payload->instance_id);

            return $row === null ? null : Valuers::upgrade($b, $ctx, $row);
        }
        if ($c->name === 'invest_cc') {
            $cc = Valuers::ccRow($b);

            return $cc === null ? null : Valuers::cc($b, $ctx, $cc);
        }
        if ($c->name === 'research_knowledge') {
            $row = DB::table('colony_researches')->where('colony_id', $b->colonyId)->where('research_id', (int) $c->payload)->first(['level', 'ap_spend']);

            return Valuers::research($b, $ctx, (int) $c->payload, (int) ($row->level ?? 0), (int) ($row->ap_spend ?? 0));
        }
        if ($c->name === 'place_building' && in_array((int) $c->payload[0]['building_id'], Valuers::PATH_IDS, true)) {
            return Valuers::pathPlacement($b, $ctx, (int) $c->payload[0]['building_id']);
        }

        return null;
    }
}
