<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles new player registration.
 * Replaces LmcUser registration functionality.
 */
class RegisterController extends Controller
{
    public function __construct(private readonly OnboardingService $onboardingService) {}

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username'  => ['required', 'string', 'max:255', 'unique:user,username'],
            'email'     => ['required', 'email', 'max:255', 'unique:user,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            [$user, $colony] = DB::transaction(function () use ($validated) {
                $user = User::create([
                    'username'        => $validated['username'],
                    'display_name'    => $validated['username'],
                    'email'           => $validated['email'],
                    'password'        => $validated['password'], // auto-hashed via cast
                    'role'            => 'player',
                    'activation_key'  => Str::random(32),
                    'activated'       => true, // no email verification yet
                ]);

                $colony = $this->onboardingService->setupNewPlayer(
                    $user->user_id,
                    $user->username . 's Kolonie'
                );

                return [$user, $colony];
            });
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'No free planets')) {
                return back()->withErrors([
                    'username' => 'Derzeit sind leider keine freien Planeten verfügbar. Bitte versuche es später erneut.',
                ])->onlyInput('username', 'email');
            }
            Log::error('Registration failed: ' . $e->getMessage());
            return back()->withErrors([
                'username' => 'Registrierung fehlgeschlagen. Bitte versuche es erneut.',
            ])->onlyInput('username', 'email');
        }

        Auth::login($user);
        $request->session()->put('activeIds.colonyId', $colony->id);

        return redirect()->route('galaxy.index');
    }
}
