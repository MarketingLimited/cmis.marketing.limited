<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetLocale Middleware
 *
 * Determines and sets the application locale based on:
 * 1. Authenticated user's locale preference (from users.locale column)
 * 2. Session locale (for guests)
 * 3. Browser Accept-Language header
 * 4. Default locale (Arabic - 'ar')
 *
 * @package App\Http\Middleware
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        // Validate locale is supported
        if (!in_array($locale, ['ar', 'en'])) {
            $locale = config('app.locale', 'ar');
        }

        // Set application locale
        App::setLocale($locale);

        // Store in session for consistency
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Determine the appropriate locale for the current request
     *
     * Priority:
     * 1. Authenticated user preference
     * 2. Session locale
     * 3. Browser header
     * 4. Default (Arabic)
     *
     * @param Request $request
     * @return string
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check authenticated user's preference
        if (Auth::check() && Auth::user()->locale) {
            return Auth::user()->locale;
        }

        // 2. Check session
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 3. Check browser Accept-Language header
        $browserLocale = $this->detectBrowserLocale($request);
        if ($browserLocale) {
            return $browserLocale;
        }

        // 4. Default to Arabic
        return config('app.locale', 'ar');
    }

    /**
     * Detect locale from browser Accept-Language header
     *
     * @param Request $request
     * @return string|null
     */
    protected function detectBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header (e.g., "ar-SA,ar;q=0.9,en;q=0.8")
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $language) {
            $locale = strtolower(substr($language, 0, 2));

            if (in_array($locale, ['ar', 'en'])) {
                return $locale;
            }
        }

        return null;
    }
}
