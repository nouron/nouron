<?php

namespace Tests\Unit\Support;

use App\Support\TechtreeGraph;
use PHPUnit\Framework\TestCase;

/**
 * The techtree screen is laid out purely from the reference data (phase/row/column of the
 * seeder files) — these tests pin the graph rules against that real data, not a fixture.
 */
class TechtreeGraphTest extends TestCase
{
    /** @return array<string, array<int, array>> techtree keyed like TechtreeColonyService::getTechtree() */
    private function seedTechtree(): array
    {
        $dir = dirname(__DIR__, 3).'/database/seeders/data';
        $map = ['building' => 'buildings', 'research' => 'researches', 'ship' => 'ships', 'personell' => 'personell'];
        $out = [];
        foreach ($map as $type => $file) {
            $out[$type] = [];
            foreach (require "$dir/$file.php" as $row) {
                $out[$type][$row['id']] = $row + ['level' => 0, 'required_building2_id' => null, 'required_building2_level' => null];
            }
        }

        return $out;
    }

    private function edge(array $edges, string $from, string $to): ?array
    {
        foreach ($edges as $e) {
            if ($e['from'] === $from && $e['to'] === $to) {
                return $e;
            }
        }

        return null;
    }

    public function test_every_placed_node_has_a_unique_grid_cell(): void
    {
        $nodes = TechtreeGraph::nodes($this->seedTechtree());
        $cells = [];
        foreach ($nodes as $n) {
            $cell = $n['gridRow'].':'.$n['gridCol'];
            $this->assertArrayNotHasKey($cell, $cells, "grid cell $cell used twice");
            $cells[$cell] = true;
        }
        $this->assertCount(27, $nodes);
        $this->assertArrayNotHasKey('personell-93', $nodes, 'strategist is hidden (phase 0) until further notice');
    }

    public function test_grid_has_four_columns_and_command_center_on_top(): void
    {
        $nodes = TechtreeGraph::nodes($this->seedTechtree());
        foreach ($nodes as $n) {
            $this->assertGreaterThanOrEqual(0, $n['gridCol']);
            $this->assertLessThanOrEqual(3, $n['gridCol']);
        }
        $cc = $nodes['building-25'];
        $this->assertSame(0, $cc['gridRow']);
        $this->assertSame(0, $cc['band']);
    }

    public function test_prerequisites_are_always_above_their_dependents(): void
    {
        $techtree = $this->seedTechtree();
        $nodes = TechtreeGraph::nodes($techtree);
        $edges = TechtreeGraph::edges($techtree);
        $this->assertNotEmpty($edges);
        foreach ($edges as $e) {
            $from = $nodes[$e['from']];
            $to = $nodes[$e['to']];
            $this->assertLessThan($to['gridRow'], $from['gridRow'], "{$e['from']} must sit above {$e['to']}");
        }
    }

    public function test_edges_are_derived_from_required_buildings_with_second_prerequisite_dashed(): void
    {
        $edges = TechtreeGraph::edges($this->seedTechtree());
        $find = fn (string $from, string $to) => $this->edge($edges, $from, $to);

        // cartography (research 91): sciencelab Lv1 solid + hangar Lv1 dashed
        $this->assertFalse($find('building-31', 'research-91')['dashed']);
        $this->assertTrue($find('building-44', 'research-91')['dashed']);
        $this->assertSame(1, $find('building-44', 'research-91')['level']);
        // bioFacility needs harvester
        $this->assertNotNull($find('building-27', 'building-41'));
    }

    public function test_command_center_edges_only_reach_phase_one_targets(): void
    {
        $techtree = $this->seedTechtree();
        $nodes = TechtreeGraph::nodes($techtree);
        $fromCc = array_filter(TechtreeGraph::edges($techtree), fn ($e) => $e['from'] === 'building-25');
        $this->assertNotEmpty($fromCc);
        foreach ($fromCc as $e) {
            $this->assertSame(1, $nodes[$e['to']]['band'], "CC edge to {$e['to']} must not leave phase 1");
        }
        // and nothing points back at the CC (its own DB prerequisite is a seed cycle)
        $this->assertEmpty(array_filter(TechtreeGraph::edges($techtree), fn ($e) => $e['to'] === 'building-25'));
    }

    public function test_edge_met_flag_follows_the_prerequisite_level(): void
    {
        $techtree = $this->seedTechtree();
        $techtree['building'][44]['level'] = 1;
        $edges = TechtreeGraph::edges($techtree);
        $e = $this->edge($edges, 'building-44', 'research-91');
        $this->assertTrue($e['met']);
        $techtree['building'][44]['level'] = 0;
        $e = $this->edge(TechtreeGraph::edges($techtree), 'building-44', 'research-91');
        $this->assertFalse($e['met']);
    }

    public function test_nodes_carry_the_effective_command_center_level(): void
    {
        $nodes = TechtreeGraph::nodes($this->seedTechtree());
        $this->assertNull($nodes['building-25']['ccLevel'], 'the Command Center has no CC requirement');
        $this->assertSame(1, $nodes['building-27']['ccLevel']);   // harvester: CC Lv1 direct
        $this->assertSame(1, $nodes['building-41']['ccLevel']);   // bioFacility via harvester
        $this->assertSame(2, $nodes['building-31']['ccLevel']);   // sciencelab: CC Lv2 direct
        $this->assertSame(2, $nodes['research-90']['ccLevel']);   // construction via sciencelab
        $this->assertSame(2, $nodes['ship-85']['ccLevel']);       // drone via hangar
        $this->assertSame(5, $nodes['building-50']['ccLevel']);   // monument: CC Lv5 direct
    }
}
