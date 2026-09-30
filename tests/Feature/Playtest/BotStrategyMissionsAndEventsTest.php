<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Baseline batch 2026-09-30: across 32 runs no bot ever accepted a Cantina
 * event (wager/auction/contract), resolved a character concern or an
 * information encounter — there were no rules for them — and credit missions
 * flew rarely (dispatch_mission only sends recon). Owner decision: every
 * profile plays missions and events, so the batch measures a player who uses
 * the game's credit sources before their rewards get tuned (task_credit_reserve).
 */
class BotStrategyMissionsAndEventsTest extends TestCase
{
    use RefreshDatabase;

    private const SHIP_FREIGHTER = 47;

    private const SHIP_CORVETTE = 37;

    private const SHIP_DRONE = 85;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const RES_ORGANICS = 5;

    // ── dispatch_credit_mission ───────────────────────────────────────────────

    public function test_every_profile_has_the_mission_and_event_rules(): void
    {
        foreach (['default', 'thrifty', 'focus', 'eager'] as $profile) {
            $names = array_column(BotStrategy::default(BotProfile::named($profile)), 'name');
            foreach (['dispatch_credit_mission', 'accept_bar_encounter', 'resolve_bar_concern', 'resolve_information_encounter'] as $rule) {
                $this->assertContains($rule, $names, "{$profile} lacks {$rule}");
            }
        }
    }

    public function test_drone_flies_the_courier_run_in_phase_2(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_DRONE, hangarLevel: 1);

        $candidate = $this->rule('dispatch_credit_mission')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame('mission_courier_run', $candidate['mission_key']);
    }

    public function test_corvette_flies_the_most_lucrative_credit_mission(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_CORVETTE, hangarLevel: 3);

        $candidate = $this->rule('dispatch_credit_mission')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame('mission_escort_convoy', $candidate['mission_key']);
    }

    public function test_freighter_stays_on_compounds_while_compounds_are_scarce(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_FREIGHTER, hangarLevel: 2, compounds: 0);
        // construction Lv1 unlocks mission_salvage_sweep, the freighter's compounds job.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'research_id' => 90],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0],
        );

        $this->assertEmpty($this->rule('dispatch_credit_mission')['when']($bot));
    }

    public function test_freighter_flies_a_credit_mission_when_compounds_are_plentiful(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_FREIGHTER, hangarLevel: 2, compounds: 500);

        $candidate = $this->rule('dispatch_credit_mission')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame('mission_aid_transport', $candidate['mission_key']);
    }

    public function test_no_credit_mission_in_phase_1(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_DRONE, hangarLevel: 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 1]);

        $this->assertEmpty($this->rule('dispatch_credit_mission')['when']($bot));
    }

    public function test_credit_mission_dispatch_succeeds_against_the_server(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_DRONE, hangarLevel: 1);
        $rule = $this->rule('dispatch_credit_mission');

        $res = $rule['do']($bot, $rule['when']($bot));

        $this->assertTrue($res['ok'], json_encode($res['body']));
    }

    // ── accept_bar_encounter ─────────────────────────────────────────────────

    public function test_accepts_an_open_affordable_encounter(): void
    {
        $bot = $this->bootWithBar();
        $id = $this->insertEncounter($bot, 'auction', self::RES_ORGANICS, 20);

        $candidate = $this->rule('accept_bar_encounter')['when']($bot);

        $this->assertNotNull($candidate);
        $this->assertSame($id, (int) $candidate->id);

        $res = $this->rule('accept_bar_encounter')['do']($bot, $candidate);
        $this->assertTrue($res['ok'], json_encode($res['body']));
    }

    public function test_skips_an_encounter_the_colony_cannot_pay(): void
    {
        $bot = $this->bootWithBar();
        $this->setResource($bot, self::RES_ORGANICS, 5);
        $this->insertEncounter($bot, 'auction', self::RES_ORGANICS, 20);

        $this->assertNull($this->rule('accept_bar_encounter')['when']($bot));
    }

    public function test_a_contract_without_stake_is_accepted(): void
    {
        $bot = $this->bootWithBar();
        $id = $this->insertEncounter($bot, 'contract', null, 0);

        $this->assertSame($id, (int) $this->rule('accept_bar_encounter')['when']($bot)->id);
    }

    // ── resolve_bar_concern / resolve_information_encounter ──────────────────

    public function test_resolves_an_open_concern_with_a_knowledge_choice(): void
    {
        $bot = $this->bootWithBar();
        $id = DB::table('bar_concerns')->insertGetId([
            'colony_id' => $bot->colonyId,
            'character_slug' => 'mechanic',
            'created_tick' => 0,
            'expires_tick' => 2000000000,
            'is_resolved' => false,
        ]);
        $rule = $this->rule('resolve_bar_concern');

        $candidate = $rule['when']($bot);
        $this->assertNotNull($candidate);
        $this->assertSame($id, (int) $candidate['concern']->id);
        $this->assertNotNull($candidate['knowledge_id'], 'mechanic needs a knowledge to invest the AP bonus into');

        $res = $rule['do']($bot, $candidate);
        $this->assertTrue($res['ok'], json_encode($res['body']));
        $this->assertTrue((bool) DB::table('bar_concerns')->where('id', $id)->value('is_resolved'));
    }

    public function test_resolves_an_open_information_encounter(): void
    {
        $bot = $this->bootWithBar();
        $id = DB::table('bar_information_encounters')->insertGetId([
            'colony_id' => $bot->colonyId,
            'character_slug' => 'lenn',
            'outcome_key' => 'storm_forecast',
            'created_tick' => 0,
            'expires_tick' => 2000000000,
            'is_resolved' => false,
        ]);
        $rule = $this->rule('resolve_information_encounter');

        $candidate = $rule['when']($bot);
        $this->assertNotNull($candidate);

        $res = $rule['do']($bot, $candidate);
        $this->assertTrue($res['ok'], json_encode($res['body']));
        $this->assertTrue((bool) DB::table('bar_information_encounters')->where('id', $id)->value('is_resolved'));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function bootWithDockedShip(int $shipId, int $hangarLevel, int $compounds = 100): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2]);

        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->delete();
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Hangar->value)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1,
            'level' => $hangarLevel,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => -1,
            'tile_y' => 1,
        ]);
        DB::table('colony_ships')->insert([
            'colony_id' => $bot->colonyId,
            'ship_id' => $shipId,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'hangar_instance_id' => 1,
            'ship_state' => 'docked',
        ]);

        // Plenty of Regolith (no Regolith mission) and provisions for any flight.
        $this->setResource($bot, self::RES_REGOLITH, 1000);
        $this->setResource($bot, self::RES_ORGANICS, 200);
        $this->setResource($bot, self::RES_COMPOUNDS, $compounds);

        return $bot;
    }

    private function bootWithBar(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2]);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Bar->value)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => BuildingId::Bar->value,
            'instance_id' => 1,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => -1,
            'tile_y' => 1,
        ]);
        DB::table('bar_encounters')->where('colony_id', $bot->colonyId)->delete();
        $this->setResource($bot, self::RES_REGOLITH, 1000);
        $this->setResource($bot, self::RES_ORGANICS, 200);

        return $bot;
    }

    private function insertEncounter(BotSession $bot, string $type, ?int $giveResourceId, int $giveAmount): int
    {
        return DB::table('bar_encounters')->insertGetId([
            'colony_id' => $bot->colonyId,
            'type' => $type,
            'give_resource_id' => $giveResourceId,
            'give_amount' => $giveAmount,
            'win_chance' => $type === 'wager' ? 0.45 : null,
            'credits_amount' => 40,
            'duration_ticks' => $type === 'contract' ? 3 : null,
            'expires_tick' => 2000000000,
            'is_accepted' => false,
            'resolved' => false,
        ]);
    }

    private function setResource(BotSession $bot, int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => $resourceId],
            ['amount' => $amount],
        );
    }

    /**
     * @return array{name:string, when:callable, do:callable}
     */
    private function rule(string $name): array
    {
        foreach (BotStrategy::default() as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }
}
