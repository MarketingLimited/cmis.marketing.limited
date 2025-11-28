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
        // Validate locale
        if (!in_array($locale, ['ar', 'en'])) {
            return redirect()->back()->with('error', __('messages.invalid_locale'));
        }

        // Update authenticated user's preference
        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        }

        // Store in session for guests and immediate effect
        Session::put('locale', $locale);

        // Set locale for current request
        app()->setLocale($locale);

        return redirect()->back()->with('success', __('messages.language_switched'));
    }
}
