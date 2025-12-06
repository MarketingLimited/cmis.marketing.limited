<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Hero Slides Controller
 *
 * Manages homepage hero carousel slides.
 */
class SuperAdminHeroController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display a listing of hero slides.
     */
    public function index(Request $request)
    {
        $query = HeroSlide::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $slides = $query->orderBy('sort_order')->get();

        if ($request->expectsJson()) {
            return $this->success(['slides' => $slides]);
        }

        return view('super-admin.website.hero.index', compact('slides'));
    }

    /**
     * Show the form for creating a new slide.
     */
    public function create()
    {
        return view('super-admin.website.hero.create');
    }

    /**
     * Store a newly created slide.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'headline_en' => 'required|string|max:255',
            'headline_ar' => 'nullable|string|max:255',
            'subheadline_en' => 'nullable|string|max:500',
            'subheadline_ar' => 'nullable|string|max:500',
            'cta_text_en' => 'nullable|string|max:100',
            'cta_text_ar' => 'nullable|string|max:100',
            'cta_url' => 'nullable|string|max:500',
            'cta_secondary_text_en' => 'nullable|string|max:100',
            'cta_secondary_text_ar' => 'nullable|string|max:100',
            'cta_secondary_url' => 'nullable|string|max:500',
            'background_image_url' => 'nullable|url|max:500',
            'background_video_url' => 'nullable|url|max:500',
            'overlay_color' => 'nullable|string|max:50',
            'overlay_opacity' => 'nullable|integer|min:0|max:100',
            'text_color' => 'nullable|string|max:50',
            'text_alignment' => 'nullable|string|in:left,center,right',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['sort_order'] = $validated['sort_order'] ?? HeroSlide::max('sort_order') + 1;

            $slide = HeroSlide::create($validated);

            $this->logAction('hero_slide_created', 'hero_slide', $slide->id, $slide->headline_en);

            if ($request->expectsJson()) {
                return $this->created($slide, __('super_admin.website.hero.created_success'));
            }

            return redirect()
                ->route('super-admin.website.hero.index')
                ->with('success', __('super_admin.website.hero.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create hero slide', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.website.hero.create_failed'));
            }

            return back()->withInput()->with('error', __('super_admin.website.hero.create_failed'));
        }
    }

    /**
     * Show the form for editing a slide.
     */
    public function edit(string $id)
    {
        $slide = HeroSlide::findOrFail($id);
        return view('super-admin.website.hero.edit', compact('slide'));
    }

    /**
     * Update the specified slide.
     */
    public function update(Request $request, string $id)
    {
        $slide = HeroSlide::findOrFail($id);

        $validated = $request->validate([
            'headline_en' => 'required|string|max:255',
            'headline_ar' => 'nullable|string|max:255',
            'subheadline_en' => 'nullable|string|max:500',
            'subheadline_ar' => 'nullable|string|max:500',
            'cta_text_en' => 'nullable|string|max:100',
            'cta_text_ar' => 'nullable|string|max:100',
            'cta_url' => 'nullable|string|max:500',
            'cta_secondary_text_en' => 'nullable|string|max:100',
            'cta_secondary_text_ar' => 'nullable|string|max:100',
            'cta_secondary_url' => 'nullable|string|max:500',
            'background_image_url' => 'nullable|url|max:500',
            'background_video_url' => 'nullable|url|max:500',
            'overlay_color' => 'nullable|string|max:50',
            'overlay_opacity' => 'nullable|integer|min:0|max:100',
            'text_color' => 'nullable|string|max:50',
            'text_alignment' => 'nullable|string|in:left,center,right',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $slide->update($validated);

            $this->logAction('hero_slide_updated', 'hero_slide', $slide->id, $slide->headline_en);

            if ($request->expectsJson()) {
                return $this->success($slide, __('super_admin.website.hero.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.hero.index')
                ->with('success', __('super_admin.website.hero.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update hero slide', ['id' => $id, 'error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.website.hero.update_failed'));
            }

            return back()->withInput()->with('error', __('super_admin.website.hero.update_failed'));
        }
    }

    /**
     * Toggle slide active status.
     */
    public function toggleActive(string $id)
    {
        $slide = HeroSlide::findOrFail($id);
        $slide->is_active = !$slide->is_active;
        $slide->save();

        $action = $slide->is_active ? 'hero_slide_activated' : 'hero_slide_deactivated';
        $this->logAction($action, 'hero_slide', $slide->id, $slide->headline_en);

        return $this->success(['is_active' => $slide->is_active]);
    }

    /**
     * Reorder slides.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'slides' => 'required|array',
            'slides.*.id' => 'required|uuid',
            'slides.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['slides'] as $item) {
            HeroSlide::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        $this->logAction('hero_slides_reordered', 'hero_slide', null, 'Slides reordered');

        return $this->success(null, __('super_admin.website.hero.reordered_success'));
    }

    /**
     * Remove the specified slide.
     */
    public function destroy(string $id)
    {
        $slide = HeroSlide::findOrFail($id);
        $this->logAction('hero_slide_deleted', 'hero_slide', $slide->id, $slide->headline_en);
        $slide->delete();

        return redirect()
            ->route('super-admin.website.hero.index')
            ->with('success', __('super_admin.website.hero.deleted_success'));
    }
}
