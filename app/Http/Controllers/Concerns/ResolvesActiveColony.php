<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ColonyService;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the colony the current request acts on: the player's prime colony
 * (one colony per player). Token auth has no session to remember anything in
 * (ADR 0006). Never default to a hard-coded colony id: that silently serves
 * another player's data.
 *
 * @throws \RuntimeException via ColonyService::getPrimeColony() when the user has no
 *                           colony. Callers behind the `run.started` middleware always
 *                           have one; registration creates it (OnboardingService).
 */
trait ResolvesActiveColony
{
    protected function resolveColonyId(): int
    {
        return (int) app(ColonyService::class)->getPrimeColony((int) Auth::id())->id;
    }
}
