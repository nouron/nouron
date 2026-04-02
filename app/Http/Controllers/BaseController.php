<?php

namespace App\Http\Controllers;

use App\Services\TickService;
use Illuminate\Support\Facades\Auth;

/**
 * BaseController — shared foundation for all Nouron game controllers.
 *
 * Provides:
 * - getCurrentUserId() — returns the authenticated user's ID or null
 * - getTick() — provides the current game tick
 *
 * All game controllers should extend this class.
 */
abstract class BaseController extends Controller
{
    public function __construct(protected TickService $tick)
    {
    }

    /**
     * Returns the currently authenticated user's ID, or null if not logged in.
     */
    protected function getCurrentUserId(): ?int
    {
        return Auth::id();
    }

    /**
     * Returns the current game tick count.
     */
    protected function getTick(): int
    {
        return $this->tick->getTickCount();
    }
}
