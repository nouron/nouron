<?php

namespace App\Services;

use App\Models\Colony;
use App\Models\GlxSystem;
use App\Models\GlxSystemObject;
use App\Services\Concerns\ValidatesId;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * GalaxyService — provides read access to the star-system layer of the galaxy map.
 * Systems and their planetary objects are stored separately; the spatial
 * relationship between them is coordinate-based (proximity within the
 * system_view_config range), not a direct FK.
 *
 * Config values (range, scale, …) live in config/game.php → galaxy and
 * config/game.php → system_view to keep balance figures out of code.
 */
class GalaxyService
{
    use ValidatesId;

    // ── Systems ───────────────────────────────────────────────────────────────

    /**
     * Return all known star systems (keyed by DB order).
     */
    public function getSystems(): Collection
    {
        return GlxSystem::all();
    }

    /**
     * Return a single system by primary key, or false when not found.
     *
     * @throws InvalidArgumentException for non-positive IDs
     */
    public function getSystem(int|string $systemId): GlxSystem|false
    {
        $this->validateId($systemId);
        return GlxSystem::find($systemId) ?? false;
    }

    // ── System Objects ────────────────────────────────────────────────────────

    /**
     * Return all planetary objects within the coordinate range of $systemId.
     *
     * The proximity radius is taken from config/game.php → system_view.range.
     *
     * @throws InvalidArgumentException for non-positive IDs
     */
    public function getSystemObjects(int|string $systemId): Collection
    {
        $this->validateId($systemId);
        $system = $this->getSystem($systemId);
        if ($system === false) {
            return collect();
        }
        return $this->getObjectsByCoords([$system->x, $system->y]);
    }

    /**
     * Return a single system object by primary key, or false when not found.
     *
     * @throws InvalidArgumentException for non-positive IDs
     */
    public function getSystemObject(int|string $id): GlxSystemObject|false
    {
        $this->validateId($id);
        return GlxSystemObject::find($id) ?? false;
    }

    /**
     * Return the system object that hosts $colonyId, or false when not found.
     *
     * @throws InvalidArgumentException for non-positive IDs
     */
    public function getSystemObjectByColonyId(int|string $colonyId): GlxSystemObject|false
    {
        $this->validateId($colonyId);
        $colony = Colony::find($colonyId);
        if (!$colony) {
            return false;
        }
        return $this->getSystemObject($colony->system_object_id);
    }

    /**
     * Return the system object at exact coordinates [x, y], or null.
     *
     * @throws InvalidArgumentException for non-numeric coordinates
     */
    public function getSystemObjectByCoords(array $coords): GlxSystemObject|null
    {
        [$x, $y] = $coords;
        if (!is_numeric($x) || !is_numeric($y)) {
            throw new InvalidArgumentException('Invalid Coordinates.');
        }
        return GlxSystemObject::where('x', $x)->where('y', $y)->first();
    }

    // ── Coordinate-based lookups ──────────────────────────────────────────────

    /**
     * Return system objects within the proximity square around $coords.
     * Radius = system_view_config range / 2 (default 50).
     */
    public function getObjectsByCoords(array $coords): Collection
    {
        $radius = (int) round($this->getSystemViewRange() / 2);
        [$cx, $cy] = $coords;

        return GlxSystemObject::whereBetween('x', [$cx - $radius, $cx + $radius])
            ->whereBetween('y', [$cy - $radius, $cy + $radius])
            ->get();
    }

    /**
     * Return colonies within the proximity square around $coords.
     * Uses v_glx_colonies which includes x/y from the joined system object.
     * Radius = system_view range / 2.
     */
    public function getColoniesByCoords(array $coords): Collection
    {
        $radius = (int) round($this->getSystemViewRange() / 2);
        [$cx, $cy] = $coords;

        return Colony::whereBetween('x', [$cx - $radius, $cx + $radius])
            ->whereBetween('y', [$cy - $radius, $cy + $radius])
            ->get();
    }

    // ── System discovery ──────────────────────────────────────────────────────

    /**
     * Find the star system that contains a given SystemObject.
     *
     * Accepts a GlxSystemObject instance or a numeric ID.
     * Returns false if no system contains the object's coordinates.
     *
     * @throws InvalidArgumentException when $object is not a valid system object
     */
    public function getSystemBySystemObject(GlxSystemObject|int $object): GlxSystem|false
    {
        if (is_int($object)) {
            $object = $this->getSystemObject($object);
        }

        if (!($object instanceof GlxSystemObject)) {
            throw new InvalidArgumentException('Not a valid system object.');
        }

        return $this->getSystemByObjectCoords([$object->x, $object->y]);
    }

    /**
     * Find the system whose bounding box (range/2 on each axis) contains $coords.
     *
     * @param  array  $coords [x, y]
     * @return GlxSystem|false
     */
    public function getSystemByObjectCoords(array $coords): GlxSystem|false
    {
        [$x, $y] = $coords;
        $radius = (int) round($this->getSystemViewRange() / 2);

        foreach ($this->getSystems() as $system) {
            if (
                $x >= $system->x - $radius && $x <= $system->x + $radius &&
                $y >= $system->y - $radius && $y <= $system->y + $radius
            ) {
                return $system;
            }
        }

        return false;
    }

    // ── Distance / path calculation ───────────────────────────────────────────

    /**
     * Manhattan distance between two coordinate pairs.
     *
     * Note: behaviour at grid borders with large or negative coords is undefined.
     */
    public function getDistance(array $coordsA, array $coordsB): int
    {
        return abs($coordsA[0] - $coordsB[0]) + abs($coordsA[1] - $coordsB[1]);
    }

    /**
     * Distance in ticks between two positions (distance + 1).
     *
     * Convention: one coordinate unit equals one tick of travel time.
     * This is a temporary convention and may be changed in future.
     */
    public function getDistanceTicks(array $coordsA, array $coordsB): int
    {
        return $this->getDistance($coordsA, $coordsB) + 1;
    }

    /**
     * Compute the tick-by-tick path from A to B at a given speed.
     *
     * Uses a speed-aware variant of Bresenham's line algorithm.
     * One entry is stored per game tick (i.e. per $speed steps).
     *
     * @param  array   $coordsA  Source [x, y, slot?]
     * @param  array   $coordsB  Target [x, y, slot?]
     * @param  int     $speed    Fields per tick
     * @param  int     $startTick  Tick number for the first path entry
     * @return array<int, array{0: int, 1: int, 2: int}>
     */
    public function getPath(array $coordsA, array $coordsB, int $speed, int $startTick = 0): array
    {
        $tick = $startTick;

        $xstart = $coordsA[0];
        $ystart = $coordsA[1];
        $xend   = $coordsB[0];
        $yend   = $coordsB[1];

        $dx = $xend - $xstart;
        $dy = $yend - $ystart;

        $incx = ($dx > 0) ? 1 : (($dx < 0) ? -1 : 0);
        $incy = ($dy > 0) ? 1 : (($dy < 0) ? -1 : 0);

        if ($dx < 0) {
            $dx = -$dx;
        }
        if ($dy < 0) {
            $dy = -$dy;
        }

        if ($dx > $dy) {
            $pdx = $incx;
            $pdy = 0;
            $ddx = $incx;
            $ddy = $incy;
            $es  = $dy;
            $el  = $dx;
        } else {
            $pdx = 0;
            $pdy = $incy;
            $ddx = $incx;
            $ddy = $incy;
            $es  = $dx;
            $el  = $dy;
        }

        $x   = $xstart;
        $y   = $ystart;
        $err = $el / 2;

        $path         = [];
        $path[$tick]  = [$coordsA[0], $coordsA[1], $coordsA[2] ?? 0];

        for ($t = 1; $t <= $el; ++$t) {
            $err -= $es;
            if ($err < 0) {
                $err += $el;
                $x   += $ddx;
                $y   += $ddy;
            } else {
                $x += $pdx;
                $y += $pdy;
            }

            if (($t % $speed) === 0 || ($x === $xend && $y === $yend)) {
                $path[++$tick] = [$x, $y, 0];
                if (isset($coordsB[2]) && $x === $xend && $y === $yend) {
                    $path[$tick][2] = $coordsB[2];
                }
            }
        }

        return $path;
    }

    // ── Config helpers ────────────────────────────────────────────────────────

    /**
     * Returns the system_view range from config (default 100).
     */
    private function getSystemViewRange(): int
    {
        return (int) config('game.system_view.range', 100);
    }
}
