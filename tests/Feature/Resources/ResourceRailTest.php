<?php

namespace Tests\Feature\Resources;

use Tests\TestCase;

/** The resource bar is a left overlay in the (game) layout, no longer part of the header (UI rework PR 3). */
class ResourceRailTest extends TestCase
{
    private function src(string $path): string
    {
        return file_get_contents(base_path('frontend/src/'.$path));
    }

    public function test_rail_is_mounted_in_the_game_layout_not_in_the_header(): void
    {
        $this->assertStringContainsString('<ResourceRail s={$shell} />', $this->src('routes/(game)/+layout.svelte'));
        $this->assertStringNotContainsString('ResourceBar', $this->src('lib/components/AppHeader.svelte'));
    }

    public function test_rail_uses_accessible_handle_and_translated_labels(): void
    {
        $rail = $this->src('lib/components/ResourceRail.svelte');
        $this->assertStringContainsString('aria-expanded={shown}', $rail);
        foreach (['rail_label', 'rail_toggle', 'rail_toggle_ap', 'rail_warning'] as $key) {
            $this->assertStringContainsString("resources.$key", $rail);
            $this->assertNotEmpty(trans("resources.$key", [], 'de'));
            $this->assertStringNotContainsString('TODO', trans("resources.$key", [], 'de'));
            $this->assertNotEmpty(trans("resources.$key", [], 'en'));
        }
    }

    public function test_chips_are_real_buttons_with_aria_expanded(): void
    {
        $bar = $this->src('lib/components/ResourceBar.svelte');
        $this->assertStringContainsString('aria-expanded={open === chip.key}', $bar);
        $this->assertStringNotContainsString('a11y_click_events_have_key_events', $bar);
    }
}
