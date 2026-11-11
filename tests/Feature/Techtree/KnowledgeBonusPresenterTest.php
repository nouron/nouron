<?php

namespace Tests\Feature\Techtree;

use App\Enums\BuildingId;
use App\Services\Techtree\KnowledgeBonusPresenter;
use App\Services\Techtree\ResearchService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Frontend payload of a knowledge bonus result (spec 2026-11-11-tomas-rat-rueckmeldung §1:
 * exact numbers with a lab, a rough word without; reached level and its effect exact).
 */
class KnowledgeBonusPresenterTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const CONSTRUCTION = 90;

    private ResearchService $research;

    private KnowledgeBonusPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.knowledge_rough_progress_bands' => [0.34, 0.67]]);
        $this->research = $this->app->make(ResearchService::class);
        $this->presenter = $this->app->make(KnowledgeBonusPresenter::class);
    }

    private function removeLab(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->delete();
    }

    public function test_with_lab_payload_carries_exact_progress(): void
    {
        $result = $this->research->investBonus(self::COLONY_ID, self::CONSTRUCTION, 3);
        $threshold = $this->research->knowledgeLevelupCost(self::COLONY_ID, self::CONSTRUCTION);

        $payload = $this->presenter->present(self::COLONY_ID, $result->toArray());

        $this->assertTrue($payload['has_lab']);
        $this->assertSame(3, $payload['ap_spend']);
        $this->assertSame($threshold, $payload['ap_for_levelup']);
        $this->assertSame(__('knowledge.construction'), $payload['knowledge_name']);
        $this->assertSame(1, $payload['rough_progress']);
    }

    public function test_without_lab_payload_hides_numbers_and_gives_a_rough_word(): void
    {
        $this->removeLab();
        $result = $this->research->investBonus(self::COLONY_ID, self::CONSTRUCTION, 3);

        $payload = $this->presenter->present(self::COLONY_ID, $result->toArray());

        $this->assertFalse($payload['has_lab']);
        $this->assertNull($payload['ap_spend']);
        $this->assertNull($payload['ap_for_levelup']);
        $this->assertSame(1, $payload['rough_progress']);
        $this->assertSame(__('knowledge.rough_progress_1'), $payload['rough_progress_label']);
    }

    public function test_breakthrough_payload_names_the_reached_level_effect(): void
    {
        $this->removeLab();
        $t1 = $this->research->knowledgeLevelupCost(self::COLONY_ID, self::CONSTRUCTION);
        $result = $this->research->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t1);

        $payload = $this->presenter->present(self::COLONY_ID, $result->toArray());

        $this->assertTrue($payload['leveled_up']);
        $this->assertSame(1, $payload['level']);
        $this->assertNotEmpty($payload['effects']);
        $this->assertNull($payload['rough_progress']);
    }

    public function test_forfeit_payload_names_the_reason_in_one_word(): void
    {
        $payload = $this->presenter->present(self::COLONY_ID, [
            'accepted' => true, 'knowledge_id' => self::CONSTRUCTION, 'knowledge_key' => 'construction',
            'ap_invested' => 2, 'level_before' => 2, 'level' => 3, 'leveled_up' => true,
            'ap_forfeited' => 3, 'block_reason' => 'knowledge_cc_gate',
        ]);

        $this->assertSame(__('knowledge.bonus_wait_knowledge_cc_gate'), $payload['block_label']);
        $this->assertNotSame('knowledge.bonus_wait_knowledge_cc_gate', $payload['block_label']);
    }

    public function test_options_list_every_knowledge_with_selectable_flag_and_reason(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::Hangar->value)
            ->delete();

        $options = collect($this->presenter->options(self::COLONY_ID))->keyBy('key');

        $this->assertCount(count(config('knowledge')), $options);
        $this->assertTrue($options['construction']['selectable']);
        $this->assertNull($options['construction']['blocked_reason']);
        $this->assertFalse($options['cartography']['selectable']);
        $this->assertSame('requires_building', $options['cartography']['blocked_reason']);
        $this->assertSame(__('knowledge.bonus_wait_requires_building'), $options['cartography']['blocked_label']);
        $this->assertSame(__('knowledge.cartography'), $options['cartography']['name']);
    }
}
