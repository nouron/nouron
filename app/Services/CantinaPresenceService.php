<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Support\RunSeed;
use Illuminate\Support\Facades\DB;

/**
 * Which cantina characters sit in the cantina today (owner 2026-11-13: a present
 * character can be talked to right away, before the first trade). Mirrors the scene
 * the cantina page builds (frontend/src/routes/(game)/cantina/+page.svelte, `scene`):
 * Tomas always; guest offers on the hotspots spot_1..spot_5 in offer order (Corvan's
 * offers and story_hook figures bring no character); the wager/auction encounter;
 * the story encounter; the open concern; the information encounter.
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
     * Slugs of the characters present in the cantina today; empty without a cantina.
     *
     * @return list<string>
     */
    public function presentCharacters(int $colonyId, int $tick): array
    {
        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Bar->value)
            ->max('level');
        if ($barLevel < 1) {
            return [];
        }

        $present = ['bartender'];
        $assignment = $this->characterAssignment($colonyId);
        foreach ($this->barService->getActiveOffers($colonyId, $tick)->values() as $idx => $offer) {
            if ($offer->visit_id !== null) {
                continue;
            }
            $char = $assignment[self::OFFER_SPOTS[$idx % count(self::OFFER_SPOTS)]] ?? null;
            if ($char !== null && ($char['game_role'] ?? null) !== 'story_hook') {
                $present[] = (string) $char['slug'];
            }
        }

        $encounter = $this->barService->getActiveEncounter($colonyId, $tick);
        $present[] = $encounter !== null ? (self::ENCOUNTER_CHARACTERS[$encounter->type] ?? null) : null;
        $present[] = $this->barService->pickStoryEncounter($colonyId, $tick);
        $present[] = $this->barService->getActiveConcern($colonyId, $tick)?->character_slug;
        $present[] = $this->barService->getActiveInformationEncounter($colonyId, $tick)?->character_slug;

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
