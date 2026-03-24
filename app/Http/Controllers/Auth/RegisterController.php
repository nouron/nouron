<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Handles new player registration.
 * Replaces LmcUser registration functionality.
 */
class RegisterController extends Controller
{
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

        $user = User::create([
            'username'        => $validated['username'],
            'display_name'    => $validated['username'],
            'email'           => $validated['email'],
            'password'        => $validated['password'], // auto-hashed via cast
            'role'            => 'player',
            'activation_key'  => Str::random(32),
            'activated'       => true, // no email verification yet
        ]);

        Auth::login($user);

        return redirect()->route('galaxy.index');
    }
}
