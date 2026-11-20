<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Support\RunSeed;
use Illuminate\Support\Facades\DB;

/**
 * Which cantina characters sit in the cantina today (owner 2026-11-13: a present
 * character can be talked to right away, before the first trade). Tomas always, plus the
 * cast of the Sol (T60, cast()): drawn from the guest offers (hotspots spot_1..spot_5 in
 * offer order; Corvan's offers and story_hook figures bring no character), the
 * wager/auction encounter, the story encounter, the open concern, the information encounter
 * and the travelling merchant.
 */
class CantinaPresenceService
{
    /** Hotspots guest offers are laid out on, in order (same list as the page's SPOTS). */
    public const OFFER_SPOTS = ['spot_1', 'spot_2', 'spot_3', 'spot_4', 'spot_5'];

    private const ENCOUNTER_CHARACTERS = ['wager' => 'gambler', 'auction' => 'scrap_dealer'];

    public function __construct(private readonly BarService $barService) {}

    /**
     * Character on each hotspot for this run (stable per run seed).
     *
     * @return array<string, array<string, mixed>> spot key => ['slug' => ...] + config('characters.<slug>')
     */
    public function characterAssignment(int $colonyId): array
    {
        $seed = RunSeed::forColony($colonyId);
        $characters = (array) config('characters');
        $assignment = [];
        foreach ($this->hotspots() as $spotKey => $spot) {
            if (empty($spot['characters'])) {
                continue;
            }
            $idx = abs(crc32($seed.$spotKey)) % count($spot['characters']);
            $slug = $spot['characters'][$idx];
            $char = $characters[$slug] ?? null;
            if ($char) {
                $assignment[$spotKey] = ['slug' => $slug] + $char;
            }
        }

        return $assignment;
    }

    /** @return array<string, array<string, mixed>> */
    public function hotspots(): array
    {
        $file = base_path('data/cantina_hotspots.json');

        return file_exists($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    }

    /**
     * Figures sitting in the cantina on this Sol besides Tomas (T60, owner 2026-11-20): one to
     * three per Sol (game.bar.guests_per_sol, rolled per Sol), the travelling merchant first,
     * the rest by chance. Stored per Sol, so the cast stays fixed: a resolved figure keeps its
     * place and nobody moves up. Figures arriving later fill only free places.
     *
     * Keys: merchant, offer_<id>, encounter_<id>, story_<slug>, concern_<id>, information_<id>.
     *
     * @return list<string>
     */
    public function cast(int $colonyId, int $tick): array
    {
        if ($this->barLevel($colonyId) < 1) {
            return [];
        }

        $row = DB::table('cantina_casts')->where(['colony_id' => $colonyId, 'tick' => $tick])->first();
        $size = $row !== null ? (int) $row->size : $this->rollSize($colonyId, $tick);
        $cast = $row !== null ? (array) json_decode((string) $row->figures, true) : [];

        $waiting = array_values(array_diff(array_keys($this->figures($colonyId, $tick)), $cast));
        if ($row !== null && (count($cast) >= $size || $waiting === [])) {
            return $cast;
        }

        $seed = RunSeed::forColony($colonyId);
        usort($waiting, fn (string $a, string $b) => [$a !== 'merchant', crc32("{$seed}:{$tick}:{$a}")]
            <=> [$b !== 'merchant', crc32("{$seed}:{$tick}:{$b}")]);
        $cast = [...$cast, ...array_slice($waiting, 0, max(0, $size - count($cast)))];

        DB::table('cantina_casts')->where('colony_id', $colonyId)->where('tick', '<', $tick)->delete();
        DB::table('cantina_casts')->updateOrInsert(
            ['colony_id' => $colonyId, 'tick' => $tick],
            ['size' => $size, 'figures' => json_encode($cast)],
        );

        return $cast;
    }

    /** Whether the figure (key as in cast()) may act on this Sol; without a cantina there is no cast to enforce. */
    public function inCast(int $colonyId, int $tick, string $key): bool
    {
        return $this->barLevel($colonyId) < 1 || in_array($key, $this->cast($colonyId, $tick), true);
    }

    /**
     * Hotspot of each guest offer still running on this Sol, accepted ones included, so the
     * character of an offer never changes when an earlier offer is taken.
     *
     * @return array<int, string> offer id => spot key
     */
    public function offerSpots(int $colonyId, int $tick): array
    {
        $ids = DB::table('bar_offers')
            ->where('colony_id', $colonyId)
            ->where('expires_tick', '>', $tick)
            ->orderBy('id')
            ->pluck('id');
        $spots = [];
        foreach ($ids as $idx => $id) {
            $spots[(int) $id] = self::OFFER_SPOTS[$idx % count(self::OFFER_SPOTS)];
        }

        return $spots;
    }

    /**
     * Figures that could sit in the cantina right now, before the cast is applied.
     *
     * @return array<string, string|null> cast key => character slug (null: no character)
     */
    private function figures(int $colonyId, int $tick): array
    {
        $figures = [];
        if (DB::table('merchant_visits')->where('colony_id', $colonyId)
            ->where('tick_start', '<=', $tick)->where('tick_end', '>=', $tick)->exists()) {
            $figures['merchant'] = null;
        }

        $assignment = $this->characterAssignment($colonyId);
        $spots = $this->offerSpots($colonyId, $tick);
        foreach ($this->barService->getActiveOffers($colonyId, $tick) as $offer) {
            if ($offer->visit_id !== null) {
                $figures['merchant'] = null;

                continue;
            }
            $char = $assignment[$spots[(int) $offer->id] ?? ''] ?? null;
            $figures["offer_{$offer->id}"] = $char !== null && ($char['game_role'] ?? null) !== 'story_hook'
                ? (string) $char['slug'] : null;
        }

        if ($encounter = $this->barService->getActiveEncounter($colonyId, $tick)) {
            $figures["encounter_{$encounter->id}"] = self::ENCOUNTER_CHARACTERS[$encounter->type] ?? null;
        }
        if ($story = $this->barService->pickStoryEncounter($colonyId, $tick)) {
            $figures["story_{$story}"] = $story;
        }
        if ($concern = $this->barService->getActiveConcern($colonyId, $tick)) {
            $figures["concern_{$concern->id}"] = $concern->character_slug;
        }
        if ($info = $this->barService->getActiveInformationEncounter($colonyId, $tick)) {
            $figures["information_{$info->id}"] = $info->character_slug;
        }

        return $figures;
    }

    private function rollSize(int $colonyId, int $tick): int
    {
        $min = (int) config('game.bar.guests_per_sol.min', 1);
        $max = max($min, (int) config('game.bar.guests_per_sol.max', 3));

        return $min + abs(crc32(RunSeed::forColony($colonyId).":size:{$tick}")) % ($max - $min + 1);
    }

    private function barLevel(int $colonyId): int
    {
        return (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Bar->value)
            ->max('level');
    }

    /**
     * Slugs of the characters present in the cantina today: Tomas plus the cast (T60);
     * empty without a cantina.
     *
     * @return list<string>
     */
    public function presentCharacters(int $colonyId, int $tick): array
    {
        if ($this->barLevel($colonyId) < 1) {
            return [];
        }

        $figures = $this->figures($colonyId, $tick);
        $present = ['bartender'];
        foreach ($this->cast($colonyId, $tick) as $key) {
            $present[] = $figures[$key] ?? null;
        }

        $known = (array) config('characters');

        return array_values(array_unique(array_filter(
            $present,
            fn ($slug) => is_string($slug) && array_key_exists($slug, $known),
        )));
    }

    public function isPresent(int $colonyId, string $characterSlug, int $tick): bool
    {
        return in_array($characterSlug, $this->presentCharacters($colonyId, $tick), true);
    }
}
