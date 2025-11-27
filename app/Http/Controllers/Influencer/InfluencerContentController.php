<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Influencer\InfluencerContent;
use App\Models\Influencer\InfluencerCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class InfluencerContentController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of influencer content
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $content = InfluencerContent::where('org_id', $orgId)
            ->when($request->campaign_id, fn($q) => $q->where('campaign_id', $request->campaign_id))
            ->when($request->influencer_id, fn($q) => $q->where('influencer_id', $request->influencer_id))
            ->when($request->content_type, fn($q) => $q->where('content_type', $request->content_type))
            ->when($request->platform, fn($q) => $q->where('platform', $request->platform))
            ->when($request->approval_status, fn($q) => $q->where('approval_status', $request->approval_status))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->with(['campaign', 'influencer', 'metrics'])
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($content, 'Influencer content retrieved successfully');
        }

        return view('influencer.content.index', compact('content'));
    }

    /**
     * Store newly created content
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), InfluencerContent::createRules(), InfluencerContent::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $content = InfluencerContent::create(array_merge($request->all(), [
            'org_id' => session('current_org_id'),
        ]));

        if ($request->expectsJson()) {
            return $this->created($content, 'Content created successfully');
        }

        return redirect()->route('influencer.content.show', $content->content_id)
            ->with('success', 'Content created successfully');
    }

    /**
     * Display the specified content
     */
    public function show(string $id)
    {
        $content = InfluencerContent::with(['campaign', 'influencer', 'metrics'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($content, 'Content retrieved successfully');
        }

        return view('influencer.content.show', compact('content'));
    }

    /**
     * Update the specified content
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), InfluencerContent::updateRules(), InfluencerContent::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $content = InfluencerContent::findOrFail($id);
        $content->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($content, 'Content updated successfully');
        }

        return redirect()->route('influencer.content.show', $content->content_id)
            ->with('success', 'Content updated successfully');
    }

    /**
     * Remove the specified content
     */
    public function destroy(string $id)
    {
        $content = InfluencerContent::findOrFail($id);
        $content->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Content deleted successfully');
        }

        return redirect()->route('influencer.content.index')
            ->with('success', 'Content deleted successfully');
    }

    /**
     * Approve or reject content
     */
    public function updateApproval(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'approval_status' => 'required|in:pending,approved,rejected,revision_requested',
            'approval_feedback' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $content = InfluencerContent::findOrFail($id);

        $updateData = [
            'approval_status' => $request->approval_status,
            'approval_feedback' => $request->approval_feedback,
            'approved_by' => auth()->id(),
        ];

        if ($request->approval_status === 'approved') {
            $updateData['approved_at'] = now();
        }

        $content->update($updateData);

        return $this->success($content, 'Approval status updated successfully');
    }

    /**
     * Upload content media
     */
    public function uploadMedia(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'media' => 'required|file|max:102400', // 100MB max
            'media_type' => 'required|in:image,video,document',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $content = InfluencerContent::findOrFail($id);

        $file = $request->file('media');
        $path = $file->store('influencer-content/' . $content->content_id, 'public');

        $mediaUrls = $content->media_urls ?? [];
        $mediaUrls[] = [
            'type' => $request->media_type,
            'url' => Storage::url($path),
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'uploaded_at' => now()->toIso8601String(),
        ];

        $content->update(['media_urls' => $mediaUrls]);

        return $this->success([
            'content' => $content,
            'media_url' => Storage::url($path),
        ], 'Media uploaded successfully');
    }

    /**
     * Get content performance metrics
     */
    public function performance(string $id)
    {
        $content = InfluencerContent::with('metrics')->findOrFail($id);

        $performance = [
            'content_id' => $content->content_id,
            'total_reach' => $content->metrics->sum('reach'),
            'total_impressions' => $content->metrics->sum('impressions'),
            'total_engagement' => $content->metrics->sum('engagement'),
            'total_clicks' => $content->metrics->sum('clicks'),
            'total_conversions' => $content->metrics->sum('conversions'),
            'engagement_rate' => $content->getEngagementRate(),
            'ctr' => $content->getCTR(),
            'conversion_rate' => $content->getConversionRate(),
        ];

        return $this->success($performance, 'Content performance retrieved successfully');
    }

    /**
     * Publish content to platform
     */
    public function publish(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'nullable|date|after:now',
            'platform_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $content = InfluencerContent::findOrFail($id);

        if ($content->approval_status !== 'approved') {
            return $this->error('Content must be approved before publishing', 400);
        }

        $content->update([
            'published_at' => $request->scheduled_at ?? now(),
            'platform_post_id' => $request->platform_post_id ?? null,
        ]);

        return $this->success($content, 'Content scheduled for publishing successfully');
    }

    /**
     * Get content analytics
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $content = InfluencerContent::where('org_id', $orgId)
            ->when($request->campaign_id, fn($q) => $q->where('campaign_id', $request->campaign_id))
            ->get();

        $totalContent = $content->count();
        $approvedContent = $content->where('approval_status', 'approved')->count();
        $pendingContent = $content->where('approval_status', 'pending')->count();
        $publishedContent = $content->whereNotNull('published_at')->count();

        $analytics = [
            'summary' => [
                'total_content' => $totalContent,
                'approved_content' => $approvedContent,
                'pending_content' => $pendingContent,
                'published_content' => $publishedContent,
                'approval_rate' => $totalContent > 0 ? round(($approvedContent / $totalContent) * 100, 2) : 0,
            ],
            'by_type' => $content->groupBy('content_type')->map->count(),
            'by_platform' => $content->groupBy('platform')->map->count(),
            'by_status' => $content->groupBy('approval_status')->map->count(),
        ];

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Content analytics retrieved successfully');
        }

        return view('influencer.content.analytics', compact('analytics'));
    }

    /**
     * Bulk update content
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content_ids' => 'required|array',
            'content_ids.*' => 'uuid',
            'approval_status' => 'nullable|in:pending,approved,rejected,revision_requested',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $updated = InfluencerContent::where('org_id', $orgId)
            ->whereIn('content_id', $request->content_ids)
            ->update(array_filter([
                'approval_status' => $request->approval_status,
            ]));

        return $this->success([
            'updated_count' => $updated,
        ], 'Content updated successfully');
    }

    /**
     * Get top performing content
     */
    public function topPerforming(Request $request)
    {
        $orgId = session('current_org_id');
        $limit = $request->get('limit', 10);

        $content = InfluencerContent::where('org_id', $orgId)
            ->whereNotNull('published_at')
            ->orderBy('total_engagement', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($content, 'Top performing content retrieved successfully');
    }

    /**
     * Request content revision
     */
    public function requestRevision(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'revision_notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $content = InfluencerContent::findOrFail($id);

        $content->update([
            'approval_status' => 'revision_requested',
            'approval_feedback' => $request->revision_notes,
            'approved_by' => auth()->id(),
        ]);

        return $this->success($content, 'Revision requested successfully');
    }

    /**
     * Export content report
     */
    public function export(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|in:csv,json,pdf',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $content = InfluencerContent::with(['campaign', 'influencer', 'metrics'])
            ->findOrFail($id);

        $format = $request->format ?? 'json';

        $export = [
            'content' => [
                'title' => $content->title,
                'type' => $content->content_type,
                'platform' => $content->platform,
                'approval_status' => $content->approval_status,
                'published_at' => $content->published_at,
            ],
            'campaign' => [
                'name' => $content->campaign->name,
            ],
            'influencer' => [
                'name' => $content->influencer->name,
                'platform' => $content->influencer->platform,
            ],
            'performance' => [
                'total_reach' => $content->metrics->sum('reach'),
                'total_engagement' => $content->metrics->sum('engagement'),
                'engagement_rate' => $content->getEngagementRate(),
                'ctr' => $content->getCTR(),
            ],
            'exported_at' => now()->toIso8601String(),
        ];

        return $this->success($export, 'Content report exported successfully');
    }
}
