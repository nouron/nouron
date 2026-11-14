<?php

namespace Tests\Feature\Bar;

use App\Models\User;
use App\Services\BarService;
use App\Services\PathDepthService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * AP scale spec 2026-11-13 §6/§12.8: Tomas' advice always gives the same bonus AP; the
 * relationship level decides how often (game.bartender.advice_every_sols_by_relationship_level).
 */
class BarBartenderFrequencyTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const KNOWLEDGE_ID = 96;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config([
            'game.bartender.ap_bonus' => 1,
            'game.bartender.advice_every_sols_by_relationship_level' => [1 => 3, 2 => 3, 3 => 2, 4 => 2, 5 => 1],
        ]);
    }

    private function setRelationship(int $level): void
    {
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'kind' => PathDepthService::KIND_CHARACTER, 'entity_key' => 'bartender'],
            ['level' => $level, 'ap_spend' => 0],
        );
    }

    private function talk(int $tick): array
    {
        $this->app->instance(TickService::class, new TickService($tick));

        return $this->app->make(BarService::class)->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, $tick);
    }

    public function test_bonus_is_the_same_at_every_relationship_level(): void
    {
        $this->setRelationship(5);

        $this->assertSame(1, $this->talk(10)['ap_added']);
    }

    public function test_level_five_gives_advice_every_sol(): void
    {
        $this->setRelationship(5);

        $this->assertTrue($this->talk(10)['success']);
        $this->assertTrue($this->talk(11)['success']);
    }

    public function test_level_three_gives_advice_every_second_sol(): void
    {
        $this->setRelationship(3);

        $this->assertTrue($this->talk(10)['success']);
        $this->assertSame('cooldown_active', $this->talk(11)['error']);
        $this->assertTrue($this->talk(12)['success']);
    }

    public function test_level_one_gives_advice_every_third_sol(): void
    {
        $this->setRelationship(1);

        $this->assertTrue($this->talk(10)['success']);
        $this->assertSame('cooldown_active', $this->talk(12)['error']);
        $this->assertTrue($this->talk(13)['success']);
    }

    public function test_api_reports_advice_rhythm_and_next_advice(): void
    {
        $this->setRelationship(3);
        $this->talk(10);
        $this->app->instance(TickService::class, new TickService(11));
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/cantina')->assertOk()->assertJson(['bartender' => [
            'relationship_level' => 3,
            'ap_bonus' => 1,
            'advice_every_sols' => 2,
            'advice_in_sols' => 1,
            'talked_today' => false,
            'advice_open' => false,
        ]]);
    }
}
