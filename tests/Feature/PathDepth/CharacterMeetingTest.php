<?php

namespace Tests\Feature\PathDepth;

use App\Services\BarService;
use App\Services\CorporateContactService;
use App\Services\PathDepthService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CharacterMeetingTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private BarService $barService;

    private PathDepthService $paths;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->delete();
        $this->barService = $this->app->make(BarService::class);
        $this->paths = $this->app->make(PathDepthService::class);
    }

    private function met(string $slug): bool
    {
        return $this->paths->state(self::COLONY_ID, 'character', $slug)['met'];
    }

    private function rows(string $slug): int
    {
        return DB::table('colony_path_levels')
            ->where('colony_id', self::COLONY_ID)->where('kind', 'character')->where('entity_key', $slug)->count();
    }

    private function resource(int $id, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $id],
            ['amount' => $amount]
        );
    }

    private function insertOffer(): int
    {
        return DB::table('bar_offers')->insertGetId([
            'colony_id' => self::COLONY_ID, 'give_resource_id' => 3, 'give_amount' => 10,
            'get_resource_id' => 4, 'get_amount' => 5, 'expires_tick' => 50,
            'is_accepted' => false, 'is_negotiated' => false,
        ]);
    }

    public function test_guest_barter_marks_character_met_and_is_idempotent(): void
    {
        $this->resource(3, 200);
        $this->assertFalse($this->met('prospector'));

        $this->barService->acceptOffer(self::COLONY_ID, $this->insertOffer(), self::USER_ID, 10, 'prospector');
        $this->barService->acceptOffer(self::COLONY_ID, $this->insertOffer(), self::USER_ID, 11, 'prospector');

        $this->assertTrue($this->met('prospector'));
        $this->assertSame(1, $this->rows('prospector'));
    }

    public function test_barter_without_character_marks_nobody(): void
    {
        $this->resource(3, 200);
        $this->barService->acceptOffer(self::COLONY_ID, $this->insertOffer(), self::USER_ID, 10);

        $this->assertSame(0, DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_concern_of_bar_trade_character_marks_met_without_codex(): void
    {
        $id = DB::table('bar_concerns')->insertGetId([
            'colony_id' => self::COLONY_ID, 'character_slug' => 'doctor', 'created_tick' => 10,
            'expires_tick' => 20, 'is_resolved' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($this->met('doctor'));
        $this->assertSame(0, DB::table('character_codex_entries')->where('user_id', self::USER_ID)->where('character_slug', 'doctor')->count());
    }

    public function test_wager_marks_gambler_met(): void
    {
        $this->resource(3, 100);
        $id = DB::table('bar_encounters')->insertGetId([
            'colony_id' => self::COLONY_ID, 'type' => 'wager', 'give_resource_id' => 3, 'give_amount' => 20,
            'win_chance' => 1.0, 'credits_amount' => 50, 'expires_tick' => 50, 'is_accepted' => false,
            'resolved' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($this->met('gambler'));
    }

    public function test_information_encounter_marks_character_met(): void
    {
        $id = DB::table('bar_information_encounters')->insertGetId([
            'colony_id' => self::COLONY_ID, 'character_slug' => 'veteran', 'outcome_key' => 'narrative_1',
            'created_tick' => 10, 'expires_tick' => 20, 'is_resolved' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($this->met('veteran'));
    }

    public function test_corporate_contact_purchase_marks_corporate_rep_met(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 25, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 5000]);
        config([
            'game.corporate_contact.appearance_interval_min' => 1,
            'game.corporate_contact.appearance_interval_max' => 1,
            'game.corporate_contact.offer_chance' => 1.0,
        ]);
        $service = $this->app->make(CorporateContactService::class);
        $bought = false;
        for ($tick = 15; $tick < 60 && ! $bought; $tick++) {
            $bought = $service->buyHarvesterOffer(self::COLONY_ID, self::USER_ID, $tick)['ok'];
        }

        $this->assertTrue($bought);
        $this->assertTrue($this->met('corporate_rep'));
    }

    public function test_story_roll_in_game_tick_marks_figure_met(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 52)
            ->update(['level' => 1, 'status_points' => 999]);
        config(['game.bar.story_encounter.chance' => 1.0]);

        $this->artisan('game:tick', ['--tick' => 5000])->assertExitCode(0);

        $this->assertSame(1, DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)
            ->where('kind', 'character')->whereIn('entity_key', config('game.bar.story_encounter.slugs'))->count());
    }

    public function test_mark_met_ignores_unknown_slug(): void
    {
        $this->paths->markMet(self::COLONY_ID, 'no_such_person');

        $this->assertSame(0, $this->rows('no_such_person'));
    }

    public function test_bartender_always_met_without_row(): void
    {
        $this->assertTrue($this->met('bartender'));
    }
}
