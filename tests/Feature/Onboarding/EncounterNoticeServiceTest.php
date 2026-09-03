<?php

namespace Tests\Feature\Onboarding;

use App\Services\EncounterNoticeService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * EncounterNoticeService — surfaces GDD §9 danger events (Sturm/Instabilität/
 * Seuche) prominently in the UI. Owner-Playtest-Fund (2026-08-31): these events
 * only ever appeared in the Protokoll log, easy to miss if the player doesn't
 * check it every Sol.
 */
class EncounterNoticeServiceTest extends TestCase
{
    use RefreshDatabase;

    private EncounterNoticeService $service;

    private const COLONY_ID = 1;

    private const OTHER_COLONY_ID = 2;

    private const CURRENT_TICK = 42;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(EncounterNoticeService::class);
    }

    private function insertEncounterLog(string $event, array $params, int $tick = self::CURRENT_TICK): void
    {
        DB::table('colony_log')->insert([
            'user' => 3,
            'tick' => $tick,
            'event' => $event,
            'area' => 'encounter',
            'parameters' => json_encode($params),
            'created_at' => now(),
            'is_read' => false,
        ]);
    }

    public function test_no_notices_when_log_empty(): void
    {
        $this->assertSame([], $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK));
    }

    public function test_storm_warning_produces_a_notice_naming_the_target_building(): void
    {
        $this->insertEncounterLog('encounter.storm_warning', [
            'colony_id' => self::COLONY_ID,
            'building_id' => 25,
            'instance_id' => 1,
        ]);

        $notices = $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK);

        $this->assertCount(1, $notices);
        $this->assertSame('encounter.storm_warning', $notices[0]['event']);
        $this->assertSame(
            __('colony.encounter_notice_storm_warning', ['building' => __('techtree.building_commandCenter')]),
            $notices[0]['text']
        );
    }

    // Regression (colony-wide storm scope, 2026-09-03): the storm event
    // payload no longer carries building_id (colony.storm_warning now
    // targets all zone buildings at once, not a single instance) — the
    // notice text must not reference a building at all anymore.
    public function test_storm_warning_produces_a_notice_without_a_building_reference(): void
    {
        $this->insertEncounterLog('encounter.storm_warning', [
            'colony_id' => self::COLONY_ID,
        ]);

        $notices = $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK);

        $this->assertCount(1, $notices);
        $this->assertSame('encounter.storm_warning', $notices[0]['event']);
        $this->assertSame(__('colony.encounter_notice_storm_warning'), $notices[0]['text']);
        $this->assertStringNotContainsString('?', $notices[0]['text']);
    }

    // Regression: encounter.storm_resolved replaced the old per-building
    // storm_abgewehrt/_beschaedigt/_kritisch events but was never added to
    // EVENT_KEYS/textFor() — the danger banner for storm resolution never
    // fires at all right now.
    public function test_storm_resolved_produces_a_notice_with_aggregated_counts(): void
    {
        $this->insertEncounterLog('encounter.storm_resolved', [
            'colony_id' => self::COLONY_ID,
            'counts' => ['abgewehrt' => 2, 'beschaedigt' => 1, 'kritisch' => 0],
            'trust_event' => 'colony_threatened',
        ]);

        $notices = $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK);

        $this->assertCount(1, $notices);
        $this->assertSame('encounter.storm_resolved', $notices[0]['event']);
        $this->assertSame(
            __('colony.encounter_notice_storm_resolved', [
                'abgewehrt' => 2,
                'beschaedigt' => 1,
                'kritisch' => 0,
            ]),
            $notices[0]['text']
        );
    }

    public function test_storm_kritisch_produces_a_notice(): void
    {
        $this->insertEncounterLog('encounter.storm_kritisch', [
            'colony_id' => self::COLONY_ID,
            'building_id' => 25,
            'instance_id' => 1,
            'tier' => 'kritisch',
        ]);

        $notices = $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK);

        $this->assertCount(1, $notices);
        $this->assertSame(
            __('colony.encounter_notice_storm_kritisch', ['building' => __('techtree.building_commandCenter')]),
            $notices[0]['text']
        );
    }

    public function test_instability_triggered_produces_a_notice(): void
    {
        $this->insertEncounterLog('encounter.instability_triggered', [
            'colony_id' => self::COLONY_ID,
            'instance_id' => 1,
            'outage_until_tick' => self::CURRENT_TICK + 3,
        ]);

        $notices = $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK);

        $this->assertCount(1, $notices);
        $this->assertSame('encounter.instability_triggered', $notices[0]['event']);
        $this->assertSame(
            __('comm_log.desc.instability_triggered', ['sols' => (int) config('game.encounter.instability.outage_sols', 3)]),
            $notices[0]['text']
        );
    }

    public function test_plague_triggered_produces_a_notice(): void
    {
        $this->insertEncounterLog('encounter.plague_triggered', [
            'colony_id' => self::COLONY_ID,
            'debuff_until_tick' => self::CURRENT_TICK + 3,
        ]);

        $notices = $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK);

        $this->assertCount(1, $notices);
        $this->assertSame(__('comm_log.desc.plague_triggered'), $notices[0]['text']);
    }

    public function test_notice_disappears_once_tick_advances(): void
    {
        // The whole point of the feature: no dismiss button needed, it clears
        // automatically once the Sol ends (Owner decision 2026-08-31).
        $this->insertEncounterLog('encounter.plague_triggered', [
            'colony_id' => self::COLONY_ID,
            'debuff_until_tick' => self::CURRENT_TICK + 3,
        ], self::CURRENT_TICK);

        $this->assertNotEmpty($this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK));
        $this->assertSame([], $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK + 1));
    }

    public function test_notice_from_a_different_colony_is_not_shown(): void
    {
        $this->insertEncounterLog('encounter.plague_triggered', [
            'colony_id' => self::OTHER_COLONY_ID,
            'debuff_until_tick' => self::CURRENT_TICK + 3,
        ]);

        $this->assertSame([], $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK));
    }

    public function test_non_encounter_log_entries_are_ignored(): void
    {
        DB::table('colony_log')->insert([
            'user' => 3,
            'tick' => self::CURRENT_TICK,
            'event' => 'colony.passive_credits',
            'area' => 'colony',
            'parameters' => json_encode(['colony_id' => self::COLONY_ID]),
            'created_at' => now(),
            'is_read' => false,
        ]);

        $this->assertSame([], $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK));
    }

    public function test_multiple_notices_in_the_same_tick_are_all_returned(): void
    {
        $this->insertEncounterLog('encounter.instability_triggered', [
            'colony_id' => self::COLONY_ID,
            'instance_id' => 1,
            'outage_until_tick' => self::CURRENT_TICK + 3,
        ]);
        $this->insertEncounterLog('encounter.plague_triggered', [
            'colony_id' => self::COLONY_ID,
            'debuff_until_tick' => self::CURRENT_TICK + 3,
        ]);

        $this->assertCount(2, $this->service->activeNotices(self::COLONY_ID, self::CURRENT_TICK));
    }
}
