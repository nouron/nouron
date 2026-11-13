<?php

namespace Tests\Feature\PathDepth;

use App\Exceptions\GameRuleException;
use App\Models\User;
use App\Services\CantinaPresenceService;
use App\Services\PathDepthService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Owner addendum 2026-11-13: the short conversation option is always available, also
 * before the first trade. A character sitting in the cantina today can be talked to
 * right away; the first conversation creates the relationship. Absent, never-met
 * characters stay out of reach.
 */
class PresentCharacterConversationTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const TICK = 100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(self::TICK));
        DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->delete();
        foreach (['bar_offers', 'bar_encounters', 'bar_concerns', 'bar_information_encounters'] as $table) {
            DB::table($table)->where('colony_id', self::COLONY_ID)->delete();
        }
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 52, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );
        config([
            'game.bypass.ap_checks' => false,
            'game.bar.story_encounter.chance' => 0.0,
            'path_depth.conversation_option_cantina_level' => ['short' => 1, 'normal' => 2, 'long' => 3],
        ]);
    }

    private function concern(string $slug): void
    {
        DB::table('bar_concerns')->insert([
            'colony_id' => self::COLONY_ID, 'character_slug' => $slug,
            'created_tick' => self::TICK, 'expires_tick' => self::TICK + 3,
        ]);
    }

    private function encounter(string $type): void
    {
        DB::table('bar_encounters')->insert([
            'colony_id' => self::COLONY_ID, 'type' => $type, 'expires_tick' => self::TICK + 3,
            'credits_amount' => 10, 'give_resource_id' => 3, 'give_amount' => 5, 'win_chance' => 0.5,
        ]);
    }

    private function offer(?int $visitId = null): void
    {
        DB::table('bar_offers')->insert([
            'colony_id' => self::COLONY_ID, 'give_resource_id' => 3, 'give_amount' => 5,
            'get_resource_id' => 1, 'get_amount' => 10, 'expires_tick' => self::TICK + 3, 'visit_id' => $visitId,
        ]);
    }

    private function presence(): CantinaPresenceService
    {
        return $this->app->make(CantinaPresenceService::class);
    }

    private function relationRow(string $slug): ?object
    {
        return DB::table('colony_path_levels')
            ->where(['colony_id' => self::COLONY_ID, 'kind' => 'character', 'entity_key' => $slug])->first();
    }

    private function invest(string $slug, int $ap): TestResponse
    {
        return $this->actingAs(User::find(3))->postJson(
            route('colony.pathdepth.invest', ['kind' => 'character', 'key' => $slug]),
            ['ap' => $ap]
        );
    }

    public function test_bartender_is_always_present_with_a_cantina(): void
    {
        $this->assertSame(['bartender'], $this->presence()->presentCharacters(self::COLONY_ID, self::TICK));
    }

    public function test_nobody_is_present_without_a_cantina(): void
    {
        DB::table('colony_buildings')->where(['colony_id' => self::COLONY_ID, 'building_id' => 52])->update(['level' => 0]);
        $this->concern('founder');

        $this->assertSame([], $this->presence()->presentCharacters(self::COLONY_ID, self::TICK));
    }

    public function test_concern_encounter_and_offer_characters_are_present(): void
    {
        $this->concern('founder');
        $this->encounter('auction');
        $this->offer();

        $present = $this->presence()->presentCharacters(self::COLONY_ID, self::TICK);

        $this->assertContains('founder', $present);
        $this->assertContains('scrap_dealer', $present);
        // First offer sits on spot_1 (mirrors the scene in the cantina page).
        $guest = $this->presence()->characterAssignment(self::COLONY_ID)['spot_1']['slug'] ?? null;
        if ($guest !== null && config("characters.{$guest}.game_role") !== 'story_hook') {
            $this->assertContains($guest, $present);
        }
    }

    public function test_merchant_offers_bring_no_character(): void
    {
        $visit = DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID, 'tick_start' => self::TICK, 'tick_end' => self::TICK + 3,
        ]);
        $this->offer($visit);

        $this->assertSame(['bartender'], $this->presence()->presentCharacters(self::COLONY_ID, self::TICK));
    }

    public function test_present_unmet_character_can_be_talked_to_and_becomes_met(): void
    {
        $this->concern('founder');

        $this->invest('founder', 1)->assertOk()->assertJson(['ok' => true, 'ap_spend' => 1]);

        $this->assertNotNull($this->relationRow('founder'));
        $this->assertTrue(app(PathDepthService::class)->state(self::COLONY_ID, 'character', 'founder')['met']);
    }

    public function test_absent_unmet_character_stays_out_of_reach(): void
    {
        $this->invest('doctor', 1)->assertStatus(422)->assertJson(['error' => 'not_met']);
        $this->assertNull($this->relationRow('doctor'));
    }

    public function test_failed_first_conversation_creates_no_relationship(): void
    {
        $this->concern('founder');

        $this->invest('founder', 2)->assertStatus(422)->assertJson(['error' => 'conversation_option_locked']);
        $this->assertNull($this->relationRow('founder'));
    }

    public function test_service_rejects_unmet_character_unless_present_flag_is_set(): void
    {
        $service = app(PathDepthService::class);
        try {
            $service->invest(self::COLONY_ID, 'character', 'doctor', 1);
            $this->fail('expected not_met');
        } catch (GameRuleException $e) {
            $this->assertSame('not_met', $e->errorCode);
        }

        $this->assertSame(1, $service->invest(self::COLONY_ID, 'character', 'doctor', 1, present: true)['ap_spend']);
    }

    public function test_cantina_data_lists_present_unmet_character_as_stranger_with_options(): void
    {
        $this->concern('founder');
        $data = $this->actingAs(User::find(3))->getJson(route('colony.bar'))->assertOk()->json();
        $rows = collect($data['relationships'])->keyBy('key');

        $this->assertArrayHasKey('founder', $rows->all());
        $this->assertSame(0, $rows['founder']['level']);
        $this->assertFalse($rows['founder']['met']);
        $this->assertTrue($rows['founder']['present']);
        $short = collect($rows['founder']['conversation']['options'])->firstWhere('key', 'short');
        $this->assertTrue($short['available']);
        $this->assertTrue($rows['bartender']['present']);
        $this->assertArrayNotHasKey('doctor', $rows->all());
    }
}
