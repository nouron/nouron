<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POST colony/hint/dismiss — regression for a bug where the endpoint returned
 * the next hint's raw text_key instead of the translated text, leaving the
 * hint-bar showing an empty/undefined label that never visually cleared.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart).
 */
class ColonyHintDismissTest extends TestCase
{
    use RefreshDatabase;

    private const BART_USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        // Test env defaults to 'en' (config/app.php fallback); production runs
        // 'de' (.env APP_LOCALE) and lang/en/colony.php has no onboarding_hint_*
        // keys at all — force 'de' so __() actually translates like production.
        $this->app->setLocale('de');
    }

    private function makeUser(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    public function test_dismiss_returns_next_hint_with_translated_text(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->postJson(route('colony.hint.dismiss'), ['hint_key' => 'hint_1']);

        $response->assertOk()->assertJson(['ok' => true]);

        $hint = $response->json('hint');
        $this->assertNotNull($hint, 'precondition: a follow-up hint must be active for this fixture');
        $this->assertArrayHasKey('text', $hint);
        $this->assertNotEmpty($hint['text']);
        // The bug returned the raw key untranslated — text must differ from text_key.
        $this->assertNotSame($hint['text_key'], $hint['text']);
    }

    public function test_dismiss_returns_null_hint_when_none_remain(): void
    {
        $keys = ['hint_1', 'hint_repair_urgent', 'hint_repair', 'hint_2', 'hint_3', 'hint_advisor_slot2',
            'hint_cc_invest', 'hint_explore', 'hint_4', 'hint_5', 'hint_build_priority', 'hint_6', 'hint_agrardome', 'hint_analytik', 'hint_end_sol'];
        foreach ($keys as $key) {
            DB::table('user_preferences')->updateOrInsert(
                ['user_id' => self::BART_USER_ID],
                ['dismissed_hints' => json_encode($keys)]
            );
        }

        $response = $this->actingAs($this->makeUser())
            ->postJson(route('colony.hint.dismiss'), ['hint_key' => 'hint_end_sol']);

        $response->assertOk()->assertJson(['ok' => true, 'hint' => null]);
    }
}
