<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the logged-in user's language preference (user_preferences.locale).
 * Without a preference, or for guests, the app default (APP_LOCALE) stays.
 */
class SetUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Session login (Blade) or bearer token (SvelteKit API, ADR 0006).
        $userId = Auth::id() ?? Auth::guard('sanctum')->id();
        if ($userId !== null) {
            $locale = DB::table('user_preferences')
                ->where('user_id', $userId)
                ->value('locale');

            if (in_array($locale, config('app.supported_locales'), true)) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
