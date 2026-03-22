<?php

namespace App\Http\Controllers;

use App\Services\TickService;
use Illuminate\Support\Facades\Auth;

/**
 * BaseController — shared foundation for all Nouron game controllers.
 *
 * Provides helpers that were previously in Core\Controller\IngameController
 * (Laminas), namely:
 * - getCurrentUserId() replaces getActive('user') / getServiceLocator() shim
 * - getTick() provides the current game tick
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
     * Replaces: $this->getActive('user') from IngameController (Laminas).
     */
    protected function getCurrentUserId(): ?int
    {
        return Auth::id();
    }

    /**
     * Returns the current game tick count.
     * Replaces: $this->getTick() from AbstractService (Laminas).
     */
    protected function getTick(): int
    {
        return $this->tick->getTickCount();
    }
}
