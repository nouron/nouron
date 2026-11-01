<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CharacterCodexService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * User profile and settings controller.
 */
class UserController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly CharacterCodexService $characterCodexService,
    ) {
        parent::__construct($tick);
    }

    /**
     * Charakter-Kodex screen (A42, GDD §12) — user-persistent, account-bound
     * lore progression for the whole Cantina cast. Lives under /user (not
     * /colony) because it survives across runs, unlike everything else in
     * the Cantina system.
     */
    /** Codex roster for the SvelteKit page — same data as codex(). */
    public function codexData(): JsonResponse
    {
        return response()->json(['characters' => $this->characterCodexService->getRosterWithUnlockedEntries(Auth::id())]);
    }

    public function codex()
    {
        return view('user.codex', [
            'characters' => $this->characterCodexService->getRosterWithUnlockedEntries(Auth::id()),
        ]);
    }

    public function show()
    {
        return view('user.show', [
            'user' => Auth::user(),
        ]);
    }

    public function settings()
    {
        return view('user.settings', $this->settingsData());
    }

    /** Profile and settings for the SvelteKit page — same data as show() and settings(). */
    public function settingsJson(): JsonResponse
    {
        ['user' => $user] = $data = $this->settingsData();

        return response()->json([
            ...$user->only('username', 'display_name', 'email', 'role', 'registration'),
            ...collect($data)->except('user'),
        ]);
    }

    /** @return array{user: User, onboarding_hints: bool, locale: string, supported_locales: list<string>} */
    private function settingsData(): array
    {
        $prefs = DB::table('user_preferences')->where('user_id', Auth::id())->first();

        return [
            'user' => Auth::user(),
            'onboarding_hints' => $prefs ? (bool) $prefs->onboarding_hints : true,
            'locale' => app()->getLocale(),
            'supported_locales' => config('app.supported_locales'),
        ];
    }

    /** JSON for the SvelteKit frontend, a redirect with flash message for Blade. */
    private function saved(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->is('api/*')
            ? response()->json(['ok' => true, 'message' => $message])
            : redirect()->route('user.settings')->with('success', $message);
    }

    public function updateDisplayName(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:50'],
        ]);

        Auth::user()->update(['display_name' => $data['display_name']]);

        return $this->saved($request, __('settings.display_name_saved'));
    }

    public function updateLocale(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(config('app.supported_locales'))],
        ]);

        DB::table('user_preferences')
            ->updateOrInsert(
                ['user_id' => Auth::id()],
                ['locale' => $data['locale'], 'updated_at' => now()]
            );

        return $this->saved($request, __('settings.saved', [], $data['locale']));
    }

    public function updateOnboardingHints(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'onboarding_hints' => ['required', 'boolean'],
        ]);

        DB::table('user_preferences')
            ->updateOrInsert(
                ['user_id' => Auth::id()],
                ['onboarding_hints' => $data['onboarding_hints'], 'updated_at' => now()]
            );

        return $this->saved($request, __('settings.saved'));
    }

    public function updatePassword(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->current_password, Auth::user()->password)) {
            throw ValidationException::withMessages(['current_password' => __('settings.password_wrong')]);
        }

        Auth::user()->update(['password' => Hash::make($request->password)]);

        return $this->saved($request, __('settings.password_saved'));
    }
}
