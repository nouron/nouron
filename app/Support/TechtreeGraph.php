<?php

namespace App\Support;

/**
 * Pure layout/edge derivation for the techtree screen — no framework, no DB.
 *
 * Input is the structure of TechtreeColonyService::getTechtree(): ['building'|'research'|'ship'|'personell'][id] => row
 * with at least phase, row, column, level and the required_building* columns. The reference data
 * (database/seeders/data) is the single source of the layout: `phase` is the Command-Center band,
 * `row`/`column` the cell inside that band (column 1..4). The band rows are stacked into one global
 * grid so a prerequisite always sits above its dependents.
 */
final class TechtreeGraph
{
    public const COMMAND_CENTER_ID = 25;

    private const TYPES = ['building', 'research', 'ship', 'personell'];

    /** @return string key such as "building-25" */
    public static function key(string $type, int $id): string
    {
        return "{$type}-{$id}";
    }

    /**
     * Placed nodes (phase > 0, plus the Command Center as band 0) with their global grid cell.
     *
     * @return array<string, array{key: string, type: string, id: int, band: int, gridRow: int, gridCol: int, ccLevel: int|null}>
     */
    public static function nodes(array $techtree): array
    {
        $placed = [];
        foreach (self::TYPES as $type) {
            foreach ($techtree[$type] ?? [] as $id => $tech) {
                $band = (int) ($tech['phase'] ?? 0);
                $isCc = $type === 'building' && (int) $id === self::COMMAND_CENTER_ID;
                if ($band < 1 && ! $isCc) {
                    continue;
                }
                $placed[] = [
                    'key' => self::key($type, (int) $id),
                    'type' => $type,
                    'id' => (int) $id,
                    'band' => $isCc ? 0 : $band,
                    'row' => (int) ($tech['row'] ?? 0),
                    'col' => (int) ($tech['column'] ?? 1),
                ];
            }
        }

        // Stack the bands: each band is as tall as its own row span.
        $minRow = [];
        $maxRow = [];
        foreach ($placed as $n) {
            $minRow[$n['band']] = min($minRow[$n['band']] ?? PHP_INT_MAX, $n['row']);
            $maxRow[$n['band']] = max($maxRow[$n['band']] ?? PHP_INT_MIN, $n['row']);
        }
        ksort($minRow);
        $offset = [];
        $acc = 0;
        foreach (array_keys($minRow) as $band) {
            $offset[$band] = $acc;
            $acc += $maxRow[$band] - $minRow[$band] + 1;
        }

        $nodes = [];
        foreach ($placed as $n) {
            $nodes[$n['key']] = [
                'key' => $n['key'],
                'type' => $n['type'],
                'id' => $n['id'],
                'band' => $n['band'],
                'gridRow' => $offset[$n['band']] + $n['row'] - $minRow[$n['band']],
                'gridCol' => $n['col'] - 1,
                'ccLevel' => self::ccLevel($techtree, $n['type'], $n['id']),
            ];
        }

        return $nodes;
    }

    /**
     * Dependency edges derived from required_building_id (solid) / required_building2_id (dashed).
     * Rule: edges leaving the Command Center only reach phase-1 targets (later bands state the CC level
     * in their band header instead), and nothing points at the Command Center itself.
     *
     * @return list<array{from: string, to: string, dashed: bool, level: int, met: bool}>
     */
    public static function edges(array $techtree): array
    {
        $nodes = self::nodes($techtree);
        $edges = [];
        foreach ($nodes as $node) {
            if ($node['key'] === self::key('building', self::COMMAND_CENTER_ID)) {
                continue;
            }
            $tech = $techtree[$node['type']][$node['id']];
            foreach ([[1, false], [2, true]] as [$n, $dashed]) {
                $suffix = $n === 1 ? '' : '2';
                $reqId = (int) ($tech["required_building{$suffix}_id"] ?? 0);
                if ($reqId < 1) {
                    continue;
                }
                $from = self::key('building', $reqId);
                if (! isset($nodes[$from])) {
                    continue;
                }
                if ($reqId === self::COMMAND_CENTER_ID && $node['band'] !== 1) {
                    continue;
                }
                $level = (int) ($tech["required_building{$suffix}_level"] ?? 1);
                $edges[] = [
                    'from' => $from,
                    'to' => $node['key'],
                    'dashed' => $dashed,
                    'level' => $level,
                    'met' => (int) ($techtree['building'][$reqId]['level'] ?? 0) >= $level,
                ];
            }
        }

        return $edges;
    }

    /**
     * Lowest Command Center level this entry needs, following the building prerequisites
     * transitively (null for the Command Center itself and for entries without a CC requirement).
     * Shown as a small tag on the node, so the dependency is visible without an arrow.
     */
    private static function ccLevel(array $techtree, string $type, int $id, array $seen = []): ?int
    {
        if ($type === 'building' && $id === self::COMMAND_CENTER_ID) {
            return null;
        }
        $key = self::key($type, $id);
        if (isset($seen[$key])) {
            return null;
        }
        $seen[$key] = true;
        $tech = $techtree[$type][$id] ?? [];
        $level = null;
        foreach (['', '2'] as $suffix) {
            $reqId = (int) ($tech["required_building{$suffix}_id"] ?? 0);
            if ($reqId < 1) {
                continue;
            }
            if ($reqId === self::COMMAND_CENTER_ID) {
                $candidate = (int) ($tech["required_building{$suffix}_level"] ?? 1);
            } else {
                $candidate = self::ccLevel($techtree, 'building', $reqId, $seen);
            }
            if ($candidate !== null) {
                $level = max($level ?? 0, $candidate);
            }
        }

        return $level;
    }
}
