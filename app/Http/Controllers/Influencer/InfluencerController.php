<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Influencer\InfluencerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InfluencerController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of influencer profiles.
     */
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        $influencers = InfluencerProfile::query()
            ->where('org_id', $orgId)
            ->with('partnerships')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('influencer.index', [
            'influencers' => $influencers,
            'currentOrg' => $orgId
        ]);
    }

    /**
     * Show the form for creating a new influencer profile.
     */
    public function create(Request $request)
    {
        return view('influencer.create', [
            'currentOrg' => $request->route('org')
        ]);
    }

    /**
     * Store a newly created influencer profile.
     */
    public function store(Request $request)
    {
        $orgId = $request->route('org');

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'bio' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'tier' => 'required|in:nano,micro,mid,macro,mega,celebrity',
            'social_accounts' => 'required|array',
            'niches' => 'nullable|array',
            'content_types' => 'nullable|array',
        ]);

        $validated['org_id'] = $orgId;
        $validated['added_by'] = Auth::id();
        $validated['status'] = 'active';

        $influencer = InfluencerProfile::create($validated);

        return redirect()
            ->route('orgs.influencer.show', ['org' => $orgId, 'influencer' => $influencer->influencer_id])
            ->with('success', 'تم إضافة المؤثر بنجاح');
    }

    /**
     * Display the specified influencer profile.
     */
    public function show(Request $request, $org, $influencer)
    {
        $influencerProfile = InfluencerProfile::where('org_id', $org)
            ->where('influencer_id', $influencer)
            ->with('partnerships')
            ->firstOrFail();

        return view('influencer.show', [
            'influencer' => $influencerProfile,
            'currentOrg' => $org
        ]);
    }

    /**
     * Show the form for editing the specified influencer profile.
     */
    public function edit(Request $request, $org, $influencer)
    {
        $influencerProfile = InfluencerProfile::where('org_id', $org)
            ->where('influencer_id', $influencer)
            ->firstOrFail();

        return view('influencer.edit', [
            'influencer' => $influencerProfile,
            'currentOrg' => $org
        ]);
    }

    /**
     * Update the specified influencer profile.
     */
    public function update(Request $request, $org, $influencer)
    {
        $influencerProfile = InfluencerProfile::where('org_id', $org)
            ->where('influencer_id', $influencer)
            ->firstOrFail();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'bio' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'tier' => 'required|in:nano,micro,mid,macro,mega,celebrity',
            'social_accounts' => 'required|array',
            'niches' => 'nullable|array',
            'content_types' => 'nullable|array',
            'status' => 'required|in:active,inactive,blacklisted,pending',
        ]);

        $influencerProfile->update($validated);

        return redirect()
            ->route('orgs.influencer.show', ['org' => $org, 'influencer' => $influencer])
            ->with('success', 'تم تحديث المؤثر بنجاح');
    }

    /**
     * Remove the specified influencer profile.
     */
    public function destroy(Request $request, $org, $influencer)
    {
        $influencerProfile = InfluencerProfile::where('org_id', $org)
            ->where('influencer_id', $influencer)
            ->firstOrFail();

        $influencerProfile->delete();

        return redirect()
            ->route('orgs.influencer.index', ['org' => $org])
            ->with('success', 'تم حذف المؤثر بنجاح');
    }
}
