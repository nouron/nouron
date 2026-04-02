<?php

namespace App\Services;

use App\Models\Colony;
use App\Services\Concerns\ValidatesId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use RuntimeException;

/**
 * ColonyService — Laravel port of Colony\Service\ColonyService.
 *
 * Reads colonies from v_glx_colonies (which joins glx_colonies and
 * glx_system_objects, so x/y coordinates are available directly).
 */
class ColonyService
{
    use ValidatesId;

    public function getColonies(): Collection
    {
        return Colony::all();
    }

    public function getColony(int|string $colonyId): Colony|false
    {
        $this->validateId($colonyId);
        return Colony::find($colonyId) ?? false;
    }

    public function getColoniesByUserId(int|string $userId): Collection
    {
        $this->validateId($userId);
        return Colony::where('user_id', $userId)->get();
    }

    /**
     * @param Colony|int $colony  colony object or ID
     */
    public function checkColonyOwner(Colony|int $colony, int $userId): bool
    {
        if (is_int($colony)) {
            $colony = $this->getColony($colony);
        }
        return $colony && $colony->user_id == $userId;
    }

    /**
     * Returns the primary colony for $userId.
     *
     * If the user has exactly one colony, it is treated as primary (matching
     * the Laminas behaviour where is_primary was auto-set when only one exists).
     *
     * @throws RuntimeException when no primary colony is found
     */
    public function getPrimeColony(int|string $userId): Colony
    {
        $this->validateId($userId);
        $colonies = $this->getColoniesByUserId((int) $userId);

        if ($colonies->count() === 1) {
            return $colonies->first();
        }

        $primary = $colonies->first(fn(Colony $c) => $c->is_primary);
        if ($primary) {
            return $primary;
        }

        throw new RuntimeException('No primary colony found for user.');
    }

    /**
     * Store the active colony in the session (only if the colony belongs to
     * the currently active user).
     */
    public function setActiveColony(int $selectedColonyId): void
    {
        $userId = Session::get('activeIds.userId');
        if ($this->checkColonyOwner($selectedColonyId, $userId)) {
            Session::put('activeIds.colonyId', $selectedColonyId);
        }
    }

    public function setSelectedColony(int $selectedColonyId): void
    {
        Session::put('selectedIds.colonyId', $selectedColonyId);
    }

    /**
     * Return all colonies within a 50-unit square around the given coordinates.
     * Since v_glx_colonies includes x/y from glx_system_objects, no extra join needed.
     */
    public function getColoniesByCoords(array $coords): Collection
    {
        $radius = (int) round(50 / 2);
        [$cx, $cy] = $coords;

        return Colony::whereBetween('x', [$cx - $radius, $cx + $radius])
            ->whereBetween('y', [$cy - $radius, $cy + $radius])
            ->get();
    }

    /**
     * Find the colony at exact coordinates [x, y, spot].
     * Returns false when no colony exists at those coords.
     *
     * @throws InvalidArgumentException for non-numeric coordinates
     */
    public function getColonyByCoords(array $coords): Colony|false
    {
        [$x, $y] = $coords;

        if (!is_numeric($x) || !is_numeric($y)) {
            throw new InvalidArgumentException('Invalid Coordinates.');
        }

        $spot = $coords[2] ?? null;

        $query = Colony::where('x', $x)->where('y', $y);
        if ($spot !== null) {
            $query->where('spot', $spot);
        }

        return $query->first() ?? false;
    }

    public function getColoniesBySystemObjectId(int|string $planetaryId): Collection
    {
        $this->validateId($planetaryId);
        return Colony::where('system_object_id', $planetaryId)->get();
    }

    /**
     * Create a new colony row in glx_colonies.
     *
     * glx_colonies uses a manual integer PK (not auto-increment).
     * Writes go to the base table, not the v_glx_colonies view.
     */
    public function createColony(int $userId, int $systemObjectId, string $name, int $sinceTick = 0): Colony
    {
        $nextId   = (int) DB::table('glx_colonies')->max('id') + 1;
        $nextSpot = (int) DB::table('glx_colonies')
            ->where('system_object_id', $systemObjectId)
            ->max('spot') + 1;

        DB::table('glx_colonies')->insert([
            'id'               => $nextId,
            'name'             => $name,
            'system_object_id' => $systemObjectId,
            'spot'             => $nextSpot,
            'user_id'          => $userId,
            'since_tick'       => $sinceTick,
            'is_primary'       => 1,
        ]);

        return Colony::findOrFail($nextId);
    }
}
