<?php

namespace Tests\Feature\Resources;

use Tests\TestCase;

/** Materials sit in a left overlay rail in the (game) layout; Sol, trust and AP sit in the header (top right). */
class ResourceRailTest extends TestCase
{
    private function src(string $path): string
    {
        return file_get_contents(base_path('frontend/src/'.$path));
    }

    public function test_rail_is_mounted_in_the_game_layout(): void
    {
        $this->assertStringContainsString('<ResourceRail s={$shell} />', $this->src('routes/(game)/+layout.svelte'));
    }

    public function test_header_shows_trust_ap_and_sol_and_the_rail_leaves_them_out(): void
    {
        $header = $this->src('lib/components/AppHeader.svelte');
        $this->assertStringContainsString("keys={['trust', 'ap']} top", $header);
        $this->assertStringContainsString("keys={['sol']} top", $header);
        $this->assertStringContainsString('<SolButton ready={s.colony_ap === 0} />', $header);

        $bar = $this->src('lib/components/ResourceBar.svelte');
        $this->assertStringContainsString("const HEADER_KEYS = ['sol', 'trust', 'ap'];", $bar);
        $this->assertStringContainsString('class:spent={i < apSegments - apLeft}', $bar);

        $this->assertStringNotContainsString('colony_ap', $this->src('lib/components/ResourceRail.svelte'));
        $this->assertStringContainsString('class:btn-sol--ready={ready && !loading}', $this->src('lib/components/SolButton.svelte'));
    }

    public function test_rail_uses_accessible_handle_and_translated_labels(): void
    {
        $rail = $this->src('lib/components/ResourceRail.svelte');
        $this->assertStringContainsString('aria-expanded={shown}', $rail);
        foreach (['rail_label', 'rail_toggle', 'rail_warning'] as $key) {
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
