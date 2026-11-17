<?php

namespace Tests\Feature\Hangar;

use Tests\TestCase;

/** Every ship type has its illustration, and the hangar card shows it (owner 2026-11-17). */
class ShipImagesTest extends TestCase
{
    public function test_every_ship_type_has_an_image(): void
    {
        foreach (array_keys(config('ships')) as $key) {
            $this->assertFileExists(public_path("img/ships/{$key}.webp"), "ship image missing for '{$key}'");
        }
    }

    public function test_hangar_card_shows_the_ship_image(): void
    {
        $page = file_get_contents(base_path('frontend/src/routes/(game)/hangar/+page.svelte'));

        $this->assertStringContainsString('/img/ships/', $page);
    }
}
