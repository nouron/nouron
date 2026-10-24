<?php

namespace Tests\Feature\PathDepth;

use App\Models\User;
use App\Services\AdvisorService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

class PathDepthControllerTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(10));
        DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->delete();
        $this->setBuilding(44, 3);
        $this->setBuilding(52, 3);
        config(['game.bypass.ap_checks' => false]);
    }

    private function setBuilding(int $buildingId, int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => $buildingId, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20.0, 'ap_spend' => 0]
        );
    }

    private function url(string $kind, string $key): string
    {
        return route('colony.pathdepth.invest', ['kind' => $kind, 'key' => $key]);
    }

    private function bart(): User
    {
        return User::find(3);
    }

    public function test_invest_requires_auth(): void
    {
        $this->postJson($this->url('mission_family', 'exploration'))->assertUnauthorized();
    }

    public function test_invest_family_returns_ok_json_with_ap_available(): void
    {
        $before = app(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);

        $this->actingAs($this->bart())
            ->postJson($this->url('mission_family', 'exploration'), ['ap' => 2])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'level' => 0,
                'ap_spend' => 2,
                'ap_for_levelup' => 6,
                'leveled_up' => false,
                'level_cap' => 5,
                'apAvailable' => $before - 2,
            ]);
    }

    public function test_ap_defaults_to_one(): void
    {
        $this->actingAs($this->bart())
            ->postJson($this->url('mission_family', 'exploration'))
            ->assertOk()
            ->assertJson(['ap_spend' => 1]);
    }

    public function test_invalid_ap_is_rejected(): void
    {
        $this->actingAs($this->bart())
            ->postJson($this->url('mission_family', 'exploration'), ['ap' => 0])
            ->assertStatus(422);
        $this->assertSame(0, DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_foreign_colony_cannot_be_targeted(): void
    {
        $foreign = $this->createForeignColony([44 => 3]);

        $this->actingAs($this->bart())
            ->postJson($this->url('mission_family', 'exploration'), ['colony_id' => $foreign['colony_id']])
            ->assertOk();

        $this->assertSame(0, DB::table('colony_path_levels')->where('colony_id', $foreign['colony_id'])->count());
        $this->assertSame(1, DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_unknown_entity_returns_422(): void
    {
        $this->assertError('unknown_entity', $this->url('mission_family', 'nonsense'));
        $this->assertError('unknown_entity', $this->url('bogus_kind', 'exploration'));
    }

    public function test_not_met_returns_422(): void
    {
        $this->assertError('not_met', $this->url('character', 'smuggler'));
    }

    public function test_max_level_returns_422(): void
    {
        DB::table('colony_path_levels')->insert(['colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => 'exploration', 'level' => 5, 'ap_spend' => 0]);
        $this->assertError('max_level', $this->url('mission_family', 'exploration'));
    }

    public function test_building_level_cap_returns_422(): void
    {
        $this->setBuilding(44, 1);
        DB::table('colony_path_levels')->insert(['colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => 'exploration', 'level' => 2, 'ap_spend' => 0]);
        $this->assertError('building_level_cap', $this->url('mission_family', 'exploration'));
    }

    public function test_insufficient_ap_returns_422(): void
    {
        $adv = app(AdvisorService::class);
        $adv->lockActionPoints(self::COLONY_ID, $adv->getAvailableActionPoints(self::COLONY_ID));
        $this->assertError('insufficient_ap', $this->url('mission_family', 'exploration'));
    }

    public function test_every_error_key_exists_in_de_and_en(): void
    {
        foreach (['de', 'en'] as $locale) {
            foreach (['unknown_entity', 'not_met', 'max_level', 'building_level_cap', 'insufficient_ap'] as $code) {
                $key = "pathdepth.error_{$code}";
                $this->assertNotSame($key, __($key, [], $locale), "{$key} missing in {$locale}");
            }
            foreach (array_keys(config('path_depth.families')) as $family) {
                $key = "pathdepth.family_{$family}";
                $this->assertNotSame($key, __($key, [], $locale), "{$key} missing in {$locale}");
            }
        }
    }

    private function assertError(string $code, string $url): void
    {
        foreach (['de', 'en'] as $locale) {
            app()->setLocale($locale);
            $this->actingAs($this->bart())
                ->postJson($url)
                ->assertStatus(422)
                ->assertJson([
                    'ok' => false,
                    'error' => $code,
                    'message' => __("pathdepth.error_{$code}", [], $locale),
                ]);
        }
        $this->assertNotSame("pathdepth.error_{$code}", __("pathdepth.error_{$code}"));
    }
}
