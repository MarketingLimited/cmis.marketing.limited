<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * We return null to avoid framework redirect handling and instead handle
     * unauthenticated responses directly in {@see unauthenticated}.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }

    /**
     * Handle an unauthenticated user without issuing a redirect.
     *
     * If the request expects JSON (or is an API call), we return a 401 JSON response.
     * For regular web requests, we return the login view with a 401 status code while
     * persisting the intended URL so the user can be sent back after logging in.
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 401));
        }

        if ($request->hasSession()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        abort(response()->view('auth.login', status: 401));
    }
}
