<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * LanguageController
 *
 * Handles locale switching for both authenticated and guest users
 *
 * @package App\Http\Controllers
 */
class LanguageController extends Controller
{
    /**
     * Switch the application locale
     *
     * @param string $locale The locale to switch to ('ar' or 'en')
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(string $locale)
    {
        \Log::info('===== LANGUAGE SWITCH START =====');
        \Log::info('🔄 Requested locale: ' . $locale);
        \Log::info('📍 Current locale before switch: ' . app()->getLocale());
        \Log::info('🆔 Session ID: ' . Session::getId());
        \Log::info('👤 Is authenticated: ' . (Auth::check() ? 'yes' : 'no'));
        \Log::info('🍪 Incoming cookies: ' . json_encode(request()->cookies->all()));
        \Log::info('🍪 app_locale cookie from request: ' . (request()->cookie('app_locale') ?? 'NOT SET'));

        // Validate locale
        if (!in_array($locale, ['ar', 'en'])) {
            \Log::error('Invalid locale requested: ' . $locale);
            return redirect()->back()->with('error', __('messages.invalid_locale'));
        }

        // Update authenticated user's preference
        if (Auth::check()) {
            $user = Auth::user();
            \Log::info('User before update - ID: ' . $user->id . ', Email: ' . $user->email . ', Locale: ' . ($user->locale ?? 'null'));

            $user->locale = $locale;
            $saved = $user->save();

            \Log::info('User save result: ' . ($saved ? 'SUCCESS' : 'FAILED'));
            \Log::info('User after update - Locale: ' . $user->locale);
        }

        // Store in session for guests and immediate effect
        \Log::info('Session locale before: ' . Session::get('locale', 'not set'));
        Session::put('locale', $locale);
        \Log::info('Session locale after put: ' . Session::get('locale'));

        // Force session to save immediately
        Session::save();
        \Log::info('Session saved forcefully');

        // Set locale for current request
        app()->setLocale($locale);
        \Log::info('App locale set to: ' . app()->getLocale());

        \Log::info('🔙 Redirecting back...');
        \Log::info('🍪 SETTING COOKIE: app_locale=' . $locale);
        \Log::info('🍪 Cookie params: domain=.kazaaz.com, secure=true, httpOnly=false, duration=30days');
        \Log::info('===== LANGUAGE SWITCH END =====');

        // Set a standalone locale cookie (30 days, works independently of session)
        $response = redirect()->back()
            ->with('success', __('messages.language_switched'))
            ->cookie('app_locale', $locale, 43200, '/', '.kazaaz.com', true, false); // 30 days, domain-wide, secure, not httpOnly

        \Log::info('✅ Response created with cookie attached');

        return $response;
    }
}
