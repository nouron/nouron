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
        if (Auth::check()) {
            $locale = DB::table('user_preferences')
                ->where('user_id', Auth::id())
                ->value('locale');

            if (in_array($locale, config('app.supported_locales'), true)) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
