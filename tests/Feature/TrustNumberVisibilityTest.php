<?php

namespace Tests\Feature;

use App\Enums\BuildingId;
use App\Models\Run;
use App\Models\User;
use App\Services\RunProgressService;
use App\Services\SolReportService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T59 F3 (Owner 2026-11-14): the trust number and the source amounts leave the API
 * only with a science lab (level >= 1) — every surface beyond the shell, too.
 */
class TrustNumberVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function removeLab(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->update(['level' => 0]);
    }

    private function setTrust(int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 12],
            ['amount' => $amount]
        );
    }

    private function bart(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    private function activeRun(int $tick): Run
    {
        $run = Run::where('user_id', self::USER_ID)->where('status', 'active')->firstOrFail();
        $run->update(['current_tick' => $tick]);

        return $run->refresh();
    }

    // ── Colony screen ────────────────────────────────────────────────────────

    public function test_colony_data_carries_no_trust_number(): void
    {
        $this->setTrust(62);

        $json = $this->actingAs($this->bart())->getJson('/api/v1/colony')->assertOk()->json();

        $this->assertArrayNotHasKey('trust', $json);
    }

    // ── Command center ───────────────────────────────────────────────────────

    public function test_command_center_trust_events_without_lab_are_names_only(): void
    {
        $this->removeLab();
        $tick = (int) $this->activeRun(5)->current_tick;
        DB::table('trust_events')->insert([
            'colony_id' => self::COLONY_ID, 'tick' => $this->app->make(TickService::class)->getTickCount() + 1,
            'event_type' => 'trade_success',
        ]);
        Cache::put('colony:'.self::COLONY_ID.':last_sol_deltas', ['regolith' => 3, 'trust' => 2]);

        $json = $this->actingAs($this->bart())->getJson('/api/v1/command-center')->assertOk()->json();

        $this->assertNotEmpty($json['trustEvents'], "tick {$tick}");
        foreach ($json['trustEvents'] as $event) {
            $this->assertArrayNotHasKey('delta', $event);
            $this->assertArrayNotHasKey('following', $event);
            $this->assertArrayHasKey('description', $event);
        }
        $this->assertArrayNotHasKey('trustEventsSum', $json);
        $this->assertArrayNotHasKey('trust', $json['lastSolDeltas']);
        $this->assertSame(3, $json['lastSolDeltas']['regolith']);
    }

    public function test_command_center_trust_events_with_lab_carry_amounts(): void
    {
        $this->activeRun(5);
        DB::table('trust_events')->insert([
            'colony_id' => self::COLONY_ID, 'tick' => $this->app->make(TickService::class)->getTickCount() + 1,
            'event_type' => 'trade_success',
        ]);
        Cache::put('colony:'.self::COLONY_ID.':last_sol_deltas', ['regolith' => 3, 'trust' => 2]);

        $json = $this->actingAs($this->bart())->getJson('/api/v1/command-center')->assertOk()->json();

        $this->assertSame((int) config('game.trust.events.trade_success'), $json['trustEvents'][0]['delta']);
        $this->assertArrayHasKey('trustEventsSum', $json);
        $this->assertSame(2, $json['lastSolDeltas']['trust']);
    }

    // ── Sol report ───────────────────────────────────────────────────────────

    private function trustLine(): array
    {
        $service = $this->app->make(SolReportService::class);
        $run = $this->activeRun(4);
        $this->setTrust(62);
        $before = $service->snapshot(self::COLONY_ID, self::USER_ID, (int) $run->phase);
        $this->setTrust(38);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 2]);

        $report = $service->buildReport($run, $before);
        $colony = collect($report['groups'])->firstWhere('key', 'colony');

        return collect($colony['lines'])->firstWhere('key', 'trust');
    }

    public function test_sol_report_without_lab_shows_band_change_only(): void
    {
        $this->removeLab();

        $line = $this->trustLine();

        $this->assertArrayNotHasKey('from', $line);
        $this->assertArrayNotHasKey('to', $line);
        $this->assertSame('content', $line['band_from']);
        $this->assertSame('restless', $line['band_to']);
        $this->assertSame(__('trust.band_restless'), $line['detail']);
        $this->assertSame('danger', $line['tone']);
        $this->assertStringContainsString(__('resources.popup_trust_hunger'), $line['reason']);
        $this->assertDoesNotMatchRegularExpression('/\d/', $line['reason'].$line['detail']);
    }

    public function test_sol_report_with_lab_shows_numbers_and_bands(): void
    {
        $line = $this->trustLine();

        $this->assertSame(62, $line['from']);
        $this->assertSame(38, $line['to']);
        $this->assertSame('-24', $line['detail']);
        $this->assertSame('restless', $line['band_to']);
        $this->assertStringContainsString('-2', $line['reason']);
    }

    public function test_plague_detail_names_band_not_number(): void
    {
        $service = $this->app->make(SolReportService::class);
        $run = $this->activeRun(7);
        $before = $service->snapshot(self::COLONY_ID, self::USER_ID, (int) $run->phase);
        DB::table('colony_log')->insert([
            'user' => self::USER_ID, 'tick' => 7, 'event' => 'encounter.plague_triggered', 'area' => 'encounter',
            'parameters' => json_encode([
                'colony_id' => self::COLONY_ID, 'debuff_until_tick' => 12, 'reason' => 'trust',
                'hunger_streak' => 0, 'trust_band' => 'riot',
            ]),
            'created_at' => now(), 'is_read' => 1,
        ]);

        $report = $service->buildReport($run, $before);
        $events = collect($report['groups'])->firstWhere('key', 'events');
        $line = collect($events['lines'])->firstWhere('label', __('colony.sol_report_event_plague'));

        $this->assertSame(__('colony.sol_report_plague_detail_trust', ['trust' => __('trust.band_riot')]), $line['detail']);
    }

    // ── Comm log parameters ──────────────────────────────────────────────────

    public function test_nexus_trust_warning_stores_band_not_number(): void
    {
        $run = $this->activeRun(10);
        $this->setTrust(8);

        $this->app->make(RunProgressService::class)->checkTrustWarnings($run);

        $params = json_decode((string) DB::table('colony_log')
            ->where('event', 'run.nexus_trust_critical')->value('parameters'), true);
        $this->assertArrayNotHasKey('trust', $params);
        $this->assertSame('riot', $params['trust_band']);
    }
}
