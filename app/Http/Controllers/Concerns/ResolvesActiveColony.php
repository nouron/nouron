<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ColonyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Resolves the colony the current request acts on.
 *
 * Reads the active colony from the session and falls back to the player's prime
 * colony, healing the session on the way. Never default to a hard-coded colony id:
 * that silently serves another player's data to anyone whose session lacks the key.
 *
 * @throws \RuntimeException via ColonyService::getPrimeColony() when the user has no
 *                           colony. Callers behind the `run.started` middleware always
 *                           have one; registration creates it (OnboardingService).
 */
trait ResolvesActiveColony
{
    protected function resolveColonyId(): int
    {
        $colonyId = Session::get('activeIds.colonyId');

        if ($colonyId) {
            return (int) $colonyId;
        }

        $colony = app(ColonyService::class)->getPrimeColony((int) Auth::id());

        Session::put('activeIds.colonyId', $colony->id);

        return (int) $colony->id;
    }
}
