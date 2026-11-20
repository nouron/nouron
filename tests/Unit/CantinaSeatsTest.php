<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Seats of the cantina picture (frontend/src/lib/cantina-seats.json, % of the 16:9 picture).
 * Owner 2026-11-20: figures stand where people would be, and on phones (picture 4 screens
 * wide) no card may sit across two pages.
 */
class CantinaSeatsTest extends TestCase
{
    // Largest card relative to the picture: 80px minimum on a 768px-wide scene (cantina.css).
    private const CARD_W = 80 / 768 * 100;

    private const CARD_H = self::CARD_W * 11 / 8 * 16 / 9;

    // Phones: 96px cards on a picture 4 screens wide, narrowest screen 320px.
    private const PHONE_HALF = 48 / (4 * 320) * 100;

    /** @return list<array{id: string, kind: string, x: float, y: float}> */
    private function seats(): array
    {
        return json_decode((string) file_get_contents(__DIR__.'/../../frontend/src/lib/cantina-seats.json'), true);
    }

    public function test_cards_stay_inside_the_picture(): void
    {
        foreach ($this->seats() as $s) {
            $this->assertGreaterThanOrEqual(0, $s['x'] - self::CARD_W / 2, $s['id']);
            $this->assertLessThanOrEqual(100, $s['x'] + self::CARD_W / 2, $s['id']);
            $this->assertGreaterThanOrEqual(0, $s['y'] - self::CARD_H / 2, $s['id']);
            $this->assertLessThanOrEqual(100, $s['y'] + self::CARD_H / 2, $s['id']);
        }
    }

    public function test_no_two_cards_overlap(): void
    {
        $seats = $this->seats();
        foreach ($seats as $i => $a) {
            foreach (array_slice($seats, $i + 1) as $b) {
                $apart = abs($a['x'] - $b['x']) >= self::CARD_W || abs($a['y'] - $b['y']) >= self::CARD_H;
                $this->assertTrue($apart, "{$a['id']} overlaps {$b['id']}");
            }
        }
    }

    public function test_on_phones_each_card_sits_on_one_page(): void
    {
        foreach ($this->seats() as $s) {
            $page = floor($s['x'] / 25);
            $this->assertGreaterThanOrEqual($page * 25, $s['x'] - self::PHONE_HALF, "{$s['id']} crosses into the page on its left");
            $this->assertLessThanOrEqual($page * 25 + 25, $s['x'] + self::PHONE_HALF, "{$s['id']} crosses into the page on its right");
        }
    }

    public function test_there_is_a_seat_for_every_figure_and_one_bar(): void
    {
        $kinds = array_count_values(array_column($this->seats(), 'kind'));
        $this->assertSame(1, $kinds['bar'] ?? 0);
        // Corvan + up to 6 guest offers + encounter, story, concern, information.
        $this->assertGreaterThanOrEqual(11, count($this->seats()) - 1);
        foreach (['counter', 'table', 'entrance', 'corner'] as $kind) {
            $this->assertArrayHasKey($kind, $kinds);
        }
    }
}
