<?php

namespace App\Http\Controllers;

use App\Services\TickService;
use Illuminate\Support\Facades\Auth;

/**
 * User profile and settings.
 * Replaces User\Controller\UserController + SettingsController (Laminas).
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
}
