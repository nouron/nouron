<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ColonyService;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Username or e-mail, same as the web login.
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $data['login'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        // Same fallback as the web login: legacy accounts without a colony get one now.
        try {
            app(ColonyService::class)->getPrimeColony($user->user_id);
        } catch (\RuntimeException) {
            try {
                app(OnboardingService::class)->setupNewPlayer($user->user_id);
            } catch (\RuntimeException $e) {
                Log::error('Onboarding failed on login for user '.$user->user_id.': '.$e->getMessage());
            }
        }

        return ['token' => $user->createToken('spa')->plainTextToken];
    }

    /** Revoke the bearer token used for this request. */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
