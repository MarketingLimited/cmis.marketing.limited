<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\Platform\SyncAudienceJob;
use App\Models\Audience\Audience;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Audience Web Controller (Frontend UI)
 *
 * Handles unified multi-platform audience management web pages
 * Supports: Meta, Google, TikTok, Snapchat, X/Twitter, LinkedIn
 */
class AudienceWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display audiences listing page
     */
    public function index(string $org, Request $request)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        // Verify access
        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403, __('common.no_access_to_organization'));
        }

        // Set RLS context
        $this->setRlsContext($user, $org);

        // Build query with filters
        $query = Audience::where('org_id', $org);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Size filter
        if ($minSize = $request->get('min_size')) {
            $query->where('size', '>=', (int) $minSize);
        }

        $audiences = $query->orderBy('created_at', 'desc')->paginate(20);

        // Stats (simplified - table doesn't have platform column)
        $stats = [
            'total' => Audience::where('org_id', $org)->count(),
            'with_criteria' => Audience::where('org_id', $org)->whereNotNull('criteria')->count(),
            'with_size' => Audience::where('org_id', $org)->where('size', '>', 0)->count(),
        ];

        // Get connected platforms for display
        $platforms = $this->getConnectedPlatforms($org);

        return view('audiences.index', compact('orgModel', 'audiences', 'stats', 'platforms'));
    }

    /**
     * Display create audience form
     */
    public function create(string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        // Get available platforms (connected ad accounts)
        $platforms = $this->getConnectedPlatforms($org);

        return view('audiences.create', compact('orgModel', 'platforms'));
    }

    /**
     * Store a new audience
     */
    public function store(Request $request, string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'criteria' => 'nullable|array',
            'size' => 'nullable|integer|min:0',
        ]);

        $this->setRlsContext($user, $org);

        $audience = Audience::create([
            'org_id' => $org,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'criteria' => $validated['criteria'] ?? [],
            'size' => $validated['size'] ?? 0,
        ]);

        return redirect()
            ->route('orgs.audiences.show', ['org' => $org, 'audience' => $audience->audience_id])
            ->with('success', __('audiences.created_successfully'));
    }

    /**
     * Display audience builder (advanced visual builder)
     */
    public function builder(string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $platforms = $this->getConnectedPlatforms($org);

        return view('audiences.builder', compact('orgModel', 'platforms'));
    }

    /**
     * Display audience details
     */
    public function show(string $org, string $audience)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $audience = Audience::where('org_id', $org)->findOrFail($audience);

        return view('audiences.show', compact('orgModel', 'audience'));
    }

    /**
     * Display edit audience form
     */
    public function edit(string $org, string $audience)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $audience = Audience::where('org_id', $org)->findOrFail($audience);
        $platforms = $this->getConnectedPlatforms($org);

        return view('audiences.edit', compact('orgModel', 'audience', 'platforms'));
    }

    /**
     * Update audience
     */
    public function update(Request $request, string $org, string $audience)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'criteria' => 'nullable|array',
            'size' => 'nullable|integer|min:0',
        ]);

        $this->setRlsContext($user, $org);

        $audienceModel = Audience::where('org_id', $org)->findOrFail($audience);
        $audienceModel->update($validated);

        return redirect()
            ->route('orgs.audiences.show', ['org' => $org, 'audience' => $audienceModel->audience_id])
            ->with('success', __('audiences.updated_successfully'));
    }

    /**
     * Delete audience
     */
    public function destroy(string $org, string $audience)
    {
        $user = auth()->user();

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $audience = Audience::where('org_id', $org)->findOrFail($audience);
        $audience->delete();

        return redirect()
            ->route('orgs.audiences.index', ['org' => $org])
            ->with('success', __('audiences.deleted_successfully'));
    }

    /**
     * Sync audience to platform
     */
    public function syncToPlatform(string $org, string $audience, string $platform)
    {
        $user = auth()->user();

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $audienceModel = Audience::where('org_id', $org)->findOrFail($audience);

        // Validate platform is connected
        $connectedPlatforms = DB::table('cmis_platform.ad_accounts')
            ->where('org_id', $org)
            ->where('platform', $platform)
            ->where('is_active', true)
            ->exists();

        if (!$connectedPlatforms) {
            return back()->with('error', __('audiences.platform_not_connected', ['platform' => ucfirst($platform)]));
        }

        // Queue sync job for platform
        SyncAudienceJob::dispatch(
            $org,
            $audienceModel->audience_id,
            $platform,
            $user->user_id
        );

        return back()->with('info', __('audiences.sync_started', ['platform' => ucfirst($platform)]));
    }

    /**
     * Set RLS context for database queries
     */
    private function setRlsContext($user, string $org): void
    {
        try {
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $org
            ]);
        } catch (\Exception $e) {
            \Log::warning("Could not set RLS context: " . $e->getMessage());
        }
    }

    /**
     * Get connected ad platforms for organization
     */
    private function getConnectedPlatforms(string $org): array
    {
        $connected = DB::table('cmis_platform.ad_accounts')
            ->where('org_id', $org)
            ->where('is_active', true)
            ->distinct()
            ->pluck('platform')
            ->toArray();

        $allPlatforms = [
            'meta' => ['name' => 'Meta (Facebook/Instagram)', 'icon' => 'fa-facebook', 'color' => 'blue'],
            'google' => ['name' => 'Google Ads', 'icon' => 'fa-google', 'color' => 'red'],
            'tiktok' => ['name' => 'TikTok', 'icon' => 'fa-tiktok', 'color' => 'pink'],
            'snapchat' => ['name' => 'Snapchat', 'icon' => 'fa-snapchat', 'color' => 'yellow'],
            'twitter' => ['name' => 'X (Twitter)', 'icon' => 'fa-x-twitter', 'color' => 'slate'],
            'linkedin' => ['name' => 'LinkedIn', 'icon' => 'fa-linkedin', 'color' => 'sky'],
        ];

        foreach ($allPlatforms as $key => &$platform) {
            $platform['connected'] = in_array($key, $connected);
        }

        return $allPlatforms;
    }
}
