<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ColonyService;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Handles login and logout.
 * Replaces LmcUser login functionality.
 *
 * Users can log in with username OR email (like LmcUser did).
 * The `user` table has a non-standard PK (user_id) — Eloquent handles this
 * via User::$primaryKey = 'user_id'.
 */
class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Try username first, then email (like LmcUser did)
        $field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $field     => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $userId = Auth::id();
            try {
                $primeColony = app(ColonyService::class)->getPrimeColony($userId);
                $request->session()->put('activeIds.colonyId', $primeColony->id);
            } catch (\Throwable) {
                // User has no colony — run onboarding (e.g. legacy account or seeding gap)
                try {
                    $colony = app(OnboardingService::class)->setupNewPlayer($userId);
                    $request->session()->put('activeIds.colonyId', $colony->id);
                } catch (\Throwable $e) {
                    Log::error('Onboarding failed on login for user ' . $userId . ': ' . $e->getMessage());
                }
            }

            return redirect()->intended(route('galaxy.index'));
        }

        return back()->withErrors([
            'username' => 'Die eingegebenen Zugangsdaten sind nicht korrekt.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
