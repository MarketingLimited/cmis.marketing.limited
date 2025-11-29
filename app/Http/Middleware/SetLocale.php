<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
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
        \Log::info('===== SET LOCALE MIDDLEWARE START =====');
        \Log::info('🌐 Request URL: ' . $request->fullUrl());
        \Log::info('🆔 Session ID: ' . Session::getId());
        \Log::info('🍪 All incoming cookies: ' . json_encode($request->cookies->all()));
        \Log::info('🍪 app_locale cookie: ' . ($request->cookie('app_locale') ?? 'NOT SET'));

        $locale = $this->determineLocale($request);

        \Log::info('✅ Determined locale: ' . $locale);

        // Validate locale is supported
        if (!in_array($locale, ['ar', 'en'])) {
            \Log::warning('Invalid locale determined: ' . $locale . ', falling back to default');
            $locale = config('app.locale', 'ar');
        }

        // Set application locale
        App::setLocale($locale);
        \Log::info('App locale set to: ' . app()->getLocale());

        // Store in session for consistency
        Session::put('locale', $locale);
        \Log::info('Session locale set to: ' . Session::get('locale'));

        // Share HTML direction and language attributes with all views
        $direction = $locale === 'ar' ? 'rtl' : 'ltr';
        View::share('htmlDir', $direction);
        View::share('htmlLang', $locale);
        \Log::info('HTML attributes shared with views: lang=' . $locale . ', dir=' . $direction);

        \Log::info('===== SET LOCALE MIDDLEWARE END =====');

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
        \Log::info('--- Determining locale ---');

        // 1. Check authenticated user's preference
        if (Auth::check()) {
            $userLocale = Auth::user()->locale;
            \Log::info('User is authenticated. User locale: ' . ($userLocale ?? 'null'));
            if ($userLocale) {
                \Log::info('Using user locale: ' . $userLocale);
                return $userLocale;
            }
        } else {
            \Log::info('User is not authenticated');
        }

        // 2. Check standalone locale cookie (independent of session)
        if ($request->hasCookie('app_locale')) {
            $cookieLocale = $request->cookie('app_locale');
            \Log::info('🍪 Cookie has locale: ' . $cookieLocale);
            if (in_array($cookieLocale, ['ar', 'en'])) {
                \Log::info('✅ Using locale from cookie: ' . $cookieLocale);
                return $cookieLocale;
            } else {
                \Log::warning('⚠️ Invalid locale in cookie: ' . $cookieLocale);
            }
        } else {
            \Log::info('❌ Cookie does not have app_locale');
        }

        // 3. Check session
        if (Session::has('locale')) {
            $sessionLocale = Session::get('locale');
            \Log::info('Session has locale: ' . $sessionLocale);
            return $sessionLocale;
        } else {
            \Log::info('Session does not have locale');
        }

        // 4. Check browser Accept-Language header
        $browserLocale = $this->detectBrowserLocale($request);
        if ($browserLocale) {
            \Log::info('Using browser locale: ' . $browserLocale);
            return $browserLocale;
        } else {
            \Log::info('No browser locale detected');
        }

        // 5. Default to Arabic
        $defaultLocale = config('app.locale', 'ar');
        \Log::info('Using default locale: ' . $defaultLocale);
        return $defaultLocale;
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
