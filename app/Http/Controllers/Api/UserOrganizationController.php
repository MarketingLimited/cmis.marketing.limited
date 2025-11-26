<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserOrganizationController extends Controller
{
    use ApiResponse;

    /**
     * Get all organizations for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $organizations = $user->orgs()
                ->select([
                    'cmis.orgs.org_id',
                    'cmis.orgs.name',
                    'cmis.orgs.default_locale',
                    'cmis.orgs.currency',
                    'cmis.orgs.created_at'
                ])
                ->get()
                ->map(function ($org) use ($user) {
                    return [
                        'org_id' => $org->org_id,
                        'name' => $org->name,
                        'default_locale' => $org->default_locale,
                        'currency' => $org->currency,
                        'is_current' => $org->org_id === session('current_org_id'),
                        'joined_at' => $org->pivot->joined_at,
                        'last_accessed' => $org->pivot->last_accessed,
                        'role_id' => $org->pivot->role_id,
                    ];
                })
                ->sortByDesc('last_accessed')
                ->values();

            return $this->success([
                'organizations' => $organizations,
                'current_org_id' => session('current_org_id'),
            ], 'Organizations retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to fetch user organizations', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Failed to retrieve organizations');
        }
    }

    /**
     * Switch to a different organization.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function switch(Request $request)
    {
        $request->validate([
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id'
        ]);

        try {
            $user = auth()->user();
            $orgId = $request->input('org_id');

            // Verify user has access to this organization
            if (!$user->belongsToOrg($orgId)) {
                return $this->forbidden('You do not have access to this organization');
            }

            // Update session
            session(['current_org_id' => $orgId]);

            // Update user's current_org_id field
            $user->update(['current_org_id' => $orgId]);

            // Update last_accessed timestamp in pivot table
            DB::table('cmis.user_orgs')
                ->where('user_id', $user->user_id)
                ->where('org_id', $orgId)
                ->update(['last_accessed' => now()]);

            // Get the organization details
            $org = $user->orgs()->where('cmis.orgs.org_id', $orgId)->first();

            Log::info('User switched organization', [
                'user_id' => $user->user_id,
                'org_id' => $orgId,
                'org_name' => $org->name ?? 'Unknown'
            ]);

            return $this->success([
                'org_id' => $orgId,
                'org_name' => $org->name ?? 'Unknown',
                'default_locale' => $org->default_locale ?? 'ar-BH',
                'currency' => $org->currency ?? 'BHD',
            ], 'Organization switched successfully');

        } catch (\Exception $e) {
            Log::error('Failed to switch organization', [
                'user_id' => auth()->id(),
                'org_id' => $request->input('org_id'),
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Failed to switch organization');
        }
    }
}
