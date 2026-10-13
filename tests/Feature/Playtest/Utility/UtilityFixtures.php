<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;

/** Shared state fixtures for the utility tests (pattern of BotStrategyCcReserveTest / BotStrategyFindFlightTest). */
trait UtilityFixtures
{
    private function placeAt(BotSession $bot, int $buildingId, int $level, int $apSpend = 0, int $instanceId = 1): void
    {
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)->delete();
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->where(fn ($q) => $q->where('q', '!=', 0)->orWhere('r', '!=', 0))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)
                ->whereColumn('cb.tile_x', 'colony_tiles.q')
                ->whereColumn('cb.tile_y', 'colony_tiles.r'))
            ->orderBy('ring')->orderBy('q')->orderBy('r')
            ->first();
        $this->assertNotNull($tile, 'fixture needs a free colony-zone tile');

        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId, 'building_id' => $buildingId, 'instance_id' => $instanceId,
            'level' => $level, 'status_points' => 20, 'ap_spend' => $apSpend, 'tile_x' => $tile->q, 'tile_y' => $tile->r,
        ]);
    }

    private function setCc(BotSession $bot, int $level, int $apSpend = 0): void
    {
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)->update(['level' => $level, 'ap_spend' => $apSpend]);
    }

    private function setResource(BotSession $bot, int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(['colony_id' => $bot->colonyId, 'resource_id' => $resourceId], ['amount' => $amount]);
    }

    private function setCredits(BotSession $bot, int $credits): void
    {
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $credits]);
    }

    private function setSupply(BotSession $bot, int $cap): void
    {
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['supply' => $cap]);
    }

    private function setPhase(BotSession $bot, int $phase): void
    {
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => $phase]);
    }

    private function giveAp(BotSession $bot, int $ap): void
    {
        $service = app(AdvisorService::class);
        DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $bot->colonyId)->delete();
        $service->lockActionPoints($bot->colonyId, $service->getTotalActionPoints($bot->colonyId) - $ap);
    }

    private function makeFind(BotSession $bot, string $type, int $ring = 2): object
    {
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->whereNull('event_type')
            ->where('is_colony_zone', 0)->where('ring', $ring)->orderBy('q')->orderBy('r')->first();
        DB::table('colony_tiles')->where('id', $tile->id)->update(['event_type' => $type, 'is_explored' => 1,
            'is_deep_scanned' => 1, 'salvage_ap_spent' => 0, 'salvage_tick' => null]);

        return DB::table('colony_tiles')->where('id', $tile->id)->first();
    }

    private function setKnowledge(BotSession $bot, int $researchId, int $level, int $apSpend = 0): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'research_id' => $researchId],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => $apSpend],
        );
    }

    /** A fake ok sol_next log entry with the given Regolith gain (BotContext income). */
    private function logSolNext(BotSession $bot, int $gain): void
    {
        $bot->log[] = ['sol' => $bot->sol, 'rule' => 'sol_next', 'url' => '/sol/next', 'status' => 200, 'ok' => true, 'error' => null,
            'ap_before' => 0, 'ap_after' => 0, 'regolith_before' => 100, 'regolith_after' => 100 + $gain,
            'organics_before' => 0, 'organics_after' => 0];
    }
}
