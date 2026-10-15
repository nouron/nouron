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
 * fixture file that could drift). R5b made all game rolls depend on runs.rng_seed
 * only, so two bots with the same seed in one database play the same game. No wall
 * clock: BotSession::boot() aligns TickService with the run.
 *
 * Compared per action: Sol, rule, URL (row ids normalized), status, ok, error,
 * AP/Regolith/Organics before and after, and a hash of the request payload.
 *
 * Scenarios (spec 4): seed 4242/labor to Sol 8 (92 actions), seed 7/hangar to
 * Sol 13 (145 actions: first find flights, request_ship, ~50 invest_production),
 * and the focus profile (objectiveFocus > 0: the spliced focus_* rules shift every
 * later band index; to Sol 8 it plays the same 92 actions as default).
 * Exercised rules: hire_advisor, place_agrardom, feed_colony, invest_cc,
 * place_building, relocate_harvester, explore_tile, deep_scan_signal_tile,
 * invest_production, repair_maintenance, research_knowledge, dispatch_mission,
 * dispatch_find_flight, request_ship (+ sol_next).
 *
 * Known gap: rules that need mid/late-game state never fire within these short runs
 * and are only covered structurally (band = rule index, cursor = legacy "next rule",
 * one act() per rule under its own name — see BotStrategy::default()):
 * repair_critical, promote_advisor, buy_merchant_item, buy_corporate_harvester_offer,
 * place_harvester_instance2, dispatch_salvage_mission, repair_ship_for_flight,
 * dispatch_credit_mission, dispatch_compounds_mission, invest_find, the bar rules
 * (accept_bar_encounter, resolve_bar_concern, resolve_information_encounter,
 * accept_bar_offer) and the focus_* rules themselves (Phase-2 objectives). Longer
 * runs would cover them but cost minutes per scenario.
 */
class AdapterGoldenTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    /** Sanity floor: an empty or trivially short run must not pass as "identical". */
    private const MIN_ACTIONS = 60;

    /**
     * Phase 1 is now the Grundaufbau (CC Lv2, Uplink, building CC gate, CC AP 20/40/60/80): the labor
     * scenarios spend their first 8 Sols on foundation rules and no longer reach invest_production /
     * research_knowledge. The hangar scenario (Sol 13) still exercises 10+; the labor ones 9.
     */
    private const MIN_DISTINCT_RULES = 9;

    /** URL segments carrying auto-increment row ids, which differ between two bots in one database. */
    private const ID_SEGMENTS = [
        '#^/advisors/\d+/#' => '/advisors/#/',
        '#^/colony/bar/(accept|accept-encounter|concern|information-encounter)/\d+$#' => '/colony/bar/$1/#',
        '#^/colony/merchant/buy/\d+$#' => '/colony/merchant/buy/#',
    ];

    public static function scenarios(): array
    {
        return [
            'seed 4242 labor' => [4242, 'default', 'labor', 8],
            'seed 7 hangar' => [7, 'default', 'hangar', 13],
            'seed 4242 focus labor' => [4242, 'focus', 'labor', 8],
        ];
    }

    public function test_two_legacy_runs_with_the_same_seed_play_the_same_sequence(): void
    {
        $profile = BotProfile::named('default')->withOpening('hangar');

        $first = $this->play(7, BotStrategy::default($profile), 8);
        $second = $this->play(7, BotStrategy::default($profile), 8);

        $this->assertSame($first, $second, 'Legacy itself is not deterministic — stop and report to the Owner before comparing engines. '.self::firstDifference($first, $second));
    }

    #[DataProvider('scenarios')]
    public function test_adapter_plays_the_legacy_action_sequence(int $seed, string $profileName, string $opening, int $untilSol): void
    {
        $profile = BotProfile::named($profileName)->withOpening($opening);

        $legacy = $this->play($seed, BotStrategy::default($profile), $untilSol);
        $adapter = $this->play($seed, UtilityStrategy::adapterOnly($profile), $untilSol);

        $this->assertGreaterThan(self::MIN_ACTIONS, count($legacy), 'the run is too short to prove anything');
        $rules = array_unique(array_column($legacy, 1));
        $this->assertGreaterThanOrEqual(self::MIN_DISTINCT_RULES, count($rules), 'too few distinct rules exercised: '.implode(', ', $rules));
        $this->assertSame($legacy, $adapter, self::firstDifference($legacy, $adapter));
    }

    /** @return list<list<mixed>> one tuple per logged action */
    private function play(int $seed, array $rules, int $untilSol): array
    {
        $bot = BotSession::boot($this, $seed);
        $this->playSolsUntil($bot, $rules, self::stopAtSol($untilSol));

        return array_map(fn (array $e): array => [
            $e['sol'], $e['rule'], self::normalizeUrl($e['url']), $e['status'], $e['ok'], $e['error'],
            $e['ap_before'], $e['ap_after'], $e['regolith_before'], $e['regolith_after'],
            $e['organics_before'], $e['organics_after'], $e['payload_hash'] ?? null,
        ], $bot->log);
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

    public function test_shadow_plays_the_legacy_action_sequence_and_scores_decisions(): void
    {
        $profile = BotProfile::named('default')->withOpening('hangar');

        $legacy = $this->play(7, BotStrategy::default($profile), 8);
        $bot = BotSession::boot($this, 7);
        $this->playSolsUntil($bot, UtilityStrategy::shadow($profile), self::stopAtSol(8));
        $shadow = array_map(fn (array $e): array => [
            $e['sol'], $e['rule'], self::normalizeUrl($e['url']), $e['status'], $e['ok'], $e['error'],
            $e['ap_before'], $e['ap_after'], $e['regolith_before'], $e['regolith_after'],
            $e['organics_before'], $e['organics_after'], $e['payload_hash'] ?? null,
        ], $bot->log);

        $this->assertSame($legacy, $shadow, self::firstDifference($legacy, $shadow));
        $this->assertNotEmpty(array_filter($bot->decisions, fn (array $d): bool => $d['shadow'] !== null), 'shadow must score at least one decision');
    }
}
