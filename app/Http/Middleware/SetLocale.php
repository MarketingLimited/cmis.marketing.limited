<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if locale is stored in session
        if (session()->has('locale')) {
            $locale = session('locale');
        }
        // Check if user is authenticated and has a preferred locale
        elseif (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }
        // Default to browser language if supported
        else {
            $locale = $this->getPreferredLocale($request);
        }

        // Validate locale
        if (in_array($locale, config('app.available_locales', ['en', 'ar']))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Get preferred locale from browser accept-language header
     */
    private function getPreferredLocale(Request $request): string
    {
        $availableLocales = config('app.available_locales', ['en', 'ar']);
        $preferredLanguage = $request->getPreferredLanguage($availableLocales);

        return $preferredLanguage ?? config('app.fallback_locale', 'en');
    }
}
