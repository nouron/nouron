<?php

namespace App\Http\Controllers;

use App\Services\TickService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * User profile and settings controller.
 */
class UserController extends BaseController
{
    public function __construct(TickService $tick)
    {
        parent::__construct($tick);
        $this->middleware('auth');
    }

    public function show()
    {
        return view('user.show', [
            'user' => Auth::user(),
        ]);
    }

    public function settings()
    {
        return view('user.settings', [
            'user' => Auth::user(),
        ]);
    }

    public function updateDisplayName(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:50'],
        ]);

        Auth::user()->update(['display_name' => $data['display_name']]);

        return redirect()->route('user.settings')->with('success', 'Anzeigename gespeichert.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return redirect()->route('user.settings')
                ->withErrors(['current_password' => 'Das aktuelle Passwort ist falsch.']);
        }

        Auth::user()->update(['password' => Hash::make($request->password)]);

        return redirect()->route('user.settings')->with('success', 'Passwort erfolgreich geändert.');
    }
}
