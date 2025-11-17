<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Flag to prevent infinite recursion when setting RLS context
     */
    protected static bool $settingOrgId = false;

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Sets the current organization ID for Row-Level Security (RLS) on every database query.
     */
    public function boot(): void
    {
        // Note: Setting RLS context on every query is expensive and can cause infinite loops.
        // Instead, we'll rely on middleware to set context once per request.
        // Commenting out the listeners to prevent infinite recursion.

        // DB::listen(function ($query) {
        //     $this->setOrgIdForRLS();
        // });

        // Event::listen(StatementPrepared::class, function ($event) {
        //     $this->setOrgIdForRLS();
        // });
    }

    /**
     * Set the current organization ID in the PostgreSQL session for RLS.
     *
     * @deprecated Use SetOrgContextMiddleware instead to avoid infinite loops
     */
    protected function setOrgIdForRLS(): void
    {
        // Prevent infinite recursion
        if (self::$settingOrgId) {
            return;
        }

        try {
            self::$settingOrgId = true;

            // Only set if user is authenticated
            if (Auth::check()) {
                $user = Auth::user();

                // Check if user has a current_org_id
                if (!empty($user->current_org_id)) {
                    // Set the org_id in the PostgreSQL session
                    // This will be used by the RLS policies
                    DB::statement(
                        "SET LOCAL app.current_org_id = ?",
                        [$user->current_org_id]
                    );
                }
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking queries
            // Log the error for debugging
            \Log::warning('Failed to set org_id for RLS: ' . $e->getMessage());
        } finally {
            self::$settingOrgId = false;
        }
    }
}
