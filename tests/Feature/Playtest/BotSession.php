<?php

namespace Tests\Feature\Playtest;

use App\Models\Run;
use App\Models\User;
use App\Services\OnboardingService;
use Database\Seeders\TestSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HTTP driver for the playtest bot. Every mutation goes through a real route
 * via $test->json() — the only exception is boot(), which sets up the Sol-1
 * fixture (equivalent to a test fixture, not a player action).
 */
class BotSession
{
    public int $sol = 0;

    private string $status = 'active';

    private ?string $failReason = null;

    /** @var array<int, array{sol:int, rule:string, url:string, status:int, ok:bool, error:?string}> */
    public array $log = [];

    private function __construct(
        private readonly TestCase $test,
        public readonly int $userId,
        public readonly int $colonyId,
        public readonly int $runId,
    ) {}

    public static function boot(TestCase $test, int $seed): self
    {
        $userId = 3;
        $colonyId = 1;

        app(TestSeeder::class)->run();

        // Bypass flags must be off BEFORE the run is created — OnboardingService
        // snapshots config('game.bypass') into run.settings at creation time.
        config([
            'game.bypass.ap_checks' => false,
            'game.bypass.resource_costs' => false,
            'game.bypass.supply_checks' => false,
        ]);

        app(OnboardingService::class)->resetColonyToSol1($userId, $colonyId);

        // Determinism — fixture setup, not a game move.
        DB::table('runs')->where('user_id', $userId)->where('status', 'active')->update(['rng_seed' => $seed]);

        $run = Run::where('user_id', $userId)->where('status', 'active')->firstOrFail();

        $test->actingAs(User::where('user_id', $userId)->firstOrFail());
        $test->postJson('/lobby/start')->assertRedirect();

        return new self($test, $userId, $colonyId, (int) $run->id);
    }

    /**
     * Advance one Sol via the real endpoint. Never call `game:tick` directly —
     * it doesn't increment current_tick, only SolController::next does.
     */
    public function nextSol(): array
    {
        $this->sol++;
        $res = $this->act('sol_next', 'POST', '/sol/next');
        $this->refreshRunState();

        return $res;
    }

    private function refreshRunState(): void
    {
        $run = Run::find($this->runId);
        $this->status = $run->status;
        $this->failReason = $run->fail_reason ?? null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function status(): string
    {
        return $this->status;
    }

    public function failReason(): ?string
    {
        return $this->failReason;
    }

    public function act(string $name, string $method, string $url, array $payload = []): array
    {
        $res = $this->test->json($method, $url, $payload);
        $norm = $this->normalize($res);

        $this->log[] = [
            'sol' => $this->sol,
            'rule' => $name,
            'url' => $url,
            'status' => $norm['status'],
            'ok' => $norm['ok'],
            'error' => $norm['error'],
        ];

        return $norm;
    }

    /**
     * Read-only GET, not counted as a bot action — used by BotStrategy to look
     * at what a player would see on screen (e.g. available-buildings) while
     * deciding, without inflating the action log the report aggregates.
     */
    public function peek(string $url): array
    {
        return $this->normalize($this->test->json('GET', $url));
    }

    private function normalize($res): array
    {
        $status = $res->getStatusCode();

        if ($status >= 500) {
            $tail = array_slice($this->log, -20);
            throw new \RuntimeException(
                "Bot run hit HTTP {$status}. Last 20 actions: ".json_encode($tail)
            );
        }

        $body = json_decode($res->getContent(), true) ?? [];

        if (in_array($status, [409, 302], true)) {
            // run_not_started / redirect during bootstrap is a broken fixture, not a game decision.
            throw new \RuntimeException("Unexpected {$status} response for bootstrap/session state: ".$res->getContent());
        }

        return [
            'status' => $status,
            'ok' => (bool) ($body['ok'] ?? ($status < 400)),
            'error' => $body['error'] ?? null,
            'body' => $body,
        ];
    }
}
