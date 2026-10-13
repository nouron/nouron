<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\PlaysSolLoop;
use Tests\TestCase;

/**
 * Spec 4 (S0, Golden): every legacy rule as a LegacyRuleSource behind the selector
 * must play exactly the legacy action sequence. Both runs live in this test (no
 * fixture file that could drift). Short runs (Sols 0-7) keep the playtest suite
 * fast; R5b made all game rolls depend on runs.rng_seed only, so two bots with the
 * same seed in one database play the same game. No wall clock: BotSession::boot()
 * aligns TickService with the run.
 */
class AdapterGoldenTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    private const UNTIL_SOL = 8;

    /** URL segments carrying auto-increment row ids, which differ between two bots in one database. */
    private const ID_SEGMENTS = [
        '#^/advisors/\d+/#' => '/advisors/#/',
        '#^/colony/bar/(accept|accept-encounter|concern|information-encounter)/\d+$#' => '/colony/bar/$1/#',
        '#^/colony/merchant/buy/\d+$#' => '/colony/merchant/buy/#',
    ];

    public static function seeds(): array
    {
        return ['seed 4242 labor' => [4242, 'labor'], 'seed 7 hangar' => [7, 'hangar']];
    }

    public function test_two_legacy_runs_with_the_same_seed_play_the_same_sequence(): void
    {
        $profile = BotProfile::named('default')->withOpening('hangar');

        $first = $this->play(7, BotStrategy::default($profile));
        $second = $this->play(7, BotStrategy::default($profile));

        $this->assertSame($first, $second, 'Legacy itself is not deterministic — stop and report to the Owner before comparing engines. '.self::firstDifference($first, $second));
    }

    #[DataProvider('seeds')]
    public function test_adapter_plays_the_legacy_action_sequence(int $seed, string $opening): void
    {
        $profile = BotProfile::named('default')->withOpening($opening);

        $legacy = $this->play($seed, BotStrategy::default($profile));
        $adapter = $this->play($seed, UtilityStrategy::adapterOnly($profile));

        $this->assertGreaterThan(self::UNTIL_SOL + 5, count($legacy), 'the short run must exercise more than sol_next');
        $this->assertSame($legacy, $adapter, self::firstDifference($legacy, $adapter));
    }

    /** @return list<array{0:int, 1:string, 2:string, 3:bool, 4:?string}> */
    private function play(int $seed, array $rules): array
    {
        $bot = BotSession::boot($this, $seed);
        $this->playSolsUntil($bot, $rules, self::stopAtSol(self::UNTIL_SOL));

        return array_map(fn (array $e): array => [$e['sol'], $e['rule'], self::normalizeUrl($e['url']), $e['ok'], $e['error']], $bot->log);
    }

    private static function normalizeUrl(string $url): string
    {
        return (string) preg_replace(array_keys(self::ID_SEGMENTS), array_values(self::ID_SEGMENTS), $url);
    }

    private static function firstDifference(array $a, array $b): string
    {
        foreach ($a as $i => $entry) {
            if (($b[$i] ?? null) !== $entry) {
                return "First difference at action {$i}: first ".json_encode($entry).' vs second '.json_encode($b[$i] ?? null);
            }
        }

        return count($a) === count($b) ? 'identical' : 'second run has '.(count($b) - count($a)).' extra actions';
    }
}
