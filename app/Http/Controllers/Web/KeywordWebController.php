<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Keyword Web Controller (Frontend UI)
 *
 * Handles Google Ads keyword management web pages
 * Includes: Keyword Planner, Negative Keywords, Keyword Groups
 */
class KeywordWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display keywords listing page
     */
    public function index(string $org, Request $request)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403, __('common.no_access_to_organization'));
        }

        $this->setRlsContext($user, $org);

        // Check if keywords tables exist
        $tablesExist = $this->checkKeywordTablesExist();

        if (!$tablesExist) {
            // Return empty state view when tables don't exist
            $keywords = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
            $stats = [
                'total' => 0,
                'active' => 0,
                'paused' => 0,
                'negative_count' => 0,
                'groups' => 0,
            ];
            $adAccounts = collect();

            // Try to get Google ad accounts from platform connections
            try {
                $adAccounts = DB::table('cmis_platform.ad_accounts')
                    ->where('org_id', $org)
                    ->where('platform', 'google')
                    ->where('is_active', true)
                    ->get();
            } catch (\Exception $e) {
                \Log::warning("Could not fetch ad accounts: " . $e->getMessage());
            }

            return view('keywords.index', compact('orgModel', 'keywords', 'stats', 'adAccounts'));
        }

        // Get keywords with filters
        $query = DB::table('cmis_google.keywords')
            ->where('org_id', $org);

        // Match type filter
        if ($matchType = $request->get('match_type')) {
            $query->where('match_type', $matchType);
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where('keyword', 'ilike', "%{$search}%");
        }

        $keywords = $query->orderBy('created_at', 'desc')->paginate(50);

        // Stats
        $stats = [
            'total' => DB::table('cmis_google.keywords')->where('org_id', $org)->count(),
            'active' => DB::table('cmis_google.keywords')->where('org_id', $org)->where('status', 'active')->count(),
            'paused' => DB::table('cmis_google.keywords')->where('org_id', $org)->where('status', 'paused')->count(),
            'negative_count' => $this->safeCount('cmis_google.negative_keywords', $org),
            'groups' => $this->safeCount('cmis_google.keyword_groups', $org),
        ];

        // Get ad accounts for context
        $adAccounts = collect();
        try {
            $adAccounts = DB::table('cmis_platform.ad_accounts')
                ->where('org_id', $org)
                ->where('platform', 'google')
                ->where('is_active', true)
                ->get();
        } catch (\Exception $e) {
            \Log::warning("Could not fetch ad accounts: " . $e->getMessage());
        }

        return view('keywords.index', compact('orgModel', 'keywords', 'stats', 'adAccounts'));
    }

    /**
     * Check if keyword tables exist
     */
    private function checkKeywordTablesExist(): bool
    {
        try {
            DB::select("SELECT 1 FROM cmis_google.keywords LIMIT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Safely count records in a table
     */
    private function safeCount(string $table, string $org): int
    {
        try {
            return DB::table($table)->where('org_id', $org)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Display create keyword form
     */
    public function create(string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        // Get campaigns for keyword assignment
        $campaigns = DB::table('cmis.campaigns')
            ->where('org_id', $org)
            ->where('platform', 'google')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        // Get keyword groups
        $groups = DB::table('cmis_google.keyword_groups')
            ->where('org_id', $org)
            ->orderBy('name')
            ->get();

        return view('keywords.create', compact('orgModel', 'campaigns', 'groups'));
    }

    /**
     * Store new keywords
     */
    public function store(Request $request, string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'keywords' => 'required|string', // One keyword per line
            'match_type' => 'required|in:exact,phrase,broad',
            'campaign_id' => 'nullable|uuid',
            'ad_group_id' => 'nullable|uuid',
            'group_id' => 'nullable|uuid',
            'default_bid' => 'nullable|numeric|min:0.01',
        ]);

        $this->setRlsContext($user, $org);

        // Parse keywords (one per line)
        $keywordLines = array_filter(array_map('trim', explode("\n", $validated['keywords'])));

        $created = 0;
        foreach ($keywordLines as $keyword) {
            if (empty($keyword)) continue;

            DB::table('cmis_google.keywords')->insert([
                'keyword_id' => Str::uuid()->toString(),
                'org_id' => $org,
                'keyword' => $keyword,
                'match_type' => $validated['match_type'],
                'campaign_id' => $validated['campaign_id'] ?? null,
                'ad_group_id' => $validated['ad_group_id'] ?? null,
                'group_id' => $validated['group_id'] ?? null,
                'default_bid' => $validated['default_bid'] ?? null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        return redirect()
            ->route('orgs.keywords.index', ['org' => $org])
            ->with('success', __('keywords.created_count', ['count' => $created]));
    }

    /**
     * Display keyword planner page
     */
    public function planner(string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        return view('keywords.planner', compact('orgModel'));
    }

    /**
     * Display negative keywords page
     */
    public function negative(string $org, Request $request)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $negativeKeywords = DB::table('cmis_google.negative_keywords')
            ->where('org_id', $org)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Get negative keyword lists
        $lists = DB::table('cmis_google.negative_keyword_lists')
            ->where('org_id', $org)
            ->orderBy('name')
            ->get();

        return view('keywords.negative', compact('orgModel', 'negativeKeywords', 'lists'));
    }

    /**
     * Display keyword groups page
     */
    public function groups(string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $groups = DB::table('cmis_google.keyword_groups')
            ->where('org_id', $org)
            ->orderBy('name')
            ->paginate(20);

        // Get keyword count per group
        $groupIds = $groups->pluck('group_id')->toArray();
        $keywordCounts = DB::table('cmis_google.keywords')
            ->whereIn('group_id', $groupIds)
            ->groupBy('group_id')
            ->selectRaw('group_id, count(*) as count')
            ->pluck('count', 'group_id');

        return view('keywords.groups', compact('orgModel', 'groups', 'keywordCounts'));
    }

    /**
     * Display keyword details
     */
    public function show(string $org, string $keyword)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $keyword = DB::table('cmis_google.keywords')
            ->where('org_id', $org)
            ->where('keyword_id', $keyword)
            ->first();

        if (!$keyword) {
            abort(404);
        }

        // Get keyword performance
        $performance = DB::table('cmis_google.keyword_metrics')
            ->where('keyword_id', $keyword->keyword_id)
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return view('keywords.show', compact('orgModel', 'keyword', 'performance'));
    }

    /**
     * Display edit keyword form
     */
    public function edit(string $org, string $keyword)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $keyword = DB::table('cmis_google.keywords')
            ->where('org_id', $org)
            ->where('keyword_id', $keyword)
            ->first();

        if (!$keyword) {
            abort(404);
        }

        $groups = DB::table('cmis_google.keyword_groups')
            ->where('org_id', $org)
            ->orderBy('name')
            ->get();

        return view('keywords.edit', compact('orgModel', 'keyword', 'groups'));
    }

    /**
     * Update keyword
     */
    public function update(Request $request, string $org, string $keyword)
    {
        $user = auth()->user();

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'match_type' => 'required|in:exact,phrase,broad',
            'status' => 'required|in:active,paused,removed',
            'default_bid' => 'nullable|numeric|min:0.01',
            'group_id' => 'nullable|uuid',
        ]);

        $this->setRlsContext($user, $org);

        DB::table('cmis_google.keywords')
            ->where('org_id', $org)
            ->where('keyword_id', $keyword)
            ->update([
                'match_type' => $validated['match_type'],
                'status' => $validated['status'],
                'default_bid' => $validated['default_bid'],
                'group_id' => $validated['group_id'],
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('orgs.keywords.show', ['org' => $org, 'keyword' => $keyword])
            ->with('success', __('keywords.updated_successfully'));
    }

    /**
     * Delete keyword
     */
    public function destroy(string $org, string $keyword)
    {
        $user = auth()->user();

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        DB::table('cmis_google.keywords')
            ->where('org_id', $org)
            ->where('keyword_id', $keyword)
            ->update([
                'status' => 'removed',
                'deleted_at' => now(),
            ]);

        return redirect()
            ->route('orgs.keywords.index', ['org' => $org])
            ->with('success', __('keywords.deleted_successfully'));
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
}
