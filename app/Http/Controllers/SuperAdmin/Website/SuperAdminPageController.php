<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\Page;
use App\Models\Website\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Super Admin Page Controller
 *
 * Manages CMS pages for the marketing website.
 */
class SuperAdminPageController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display a listing of pages.
     */
    public function index(Request $request)
    {
        $query = Page::query()->withCount('sections');

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } else {
                $query->where('is_published', false);
            }
        }

        if ($request->filled('template')) {
            $query->where('template', $request->template);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title_en', 'ilike', "%{$search}%")
                  ->orWhere('title_ar', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $pages = $query->paginate(15)->withQueryString();

        $templates = [
            'default' => __('super_admin.website.templates.default'),
            'landing' => __('super_admin.website.templates.landing'),
            'about' => __('super_admin.website.templates.about'),
            'contact' => __('super_admin.website.templates.contact'),
            'custom' => __('super_admin.website.templates.custom'),
        ];

        if ($request->expectsJson()) {
            return $this->success([
                'pages' => $pages,
                'templates' => $templates,
            ]);
        }

        return view('super-admin.website.pages.index', compact('pages', 'templates'));
    }

    /**
     * Show the form for creating a new page.
     */
    public function create()
    {
        $templates = [
            'default' => __('super_admin.website.templates.default'),
            'landing' => __('super_admin.website.templates.landing'),
            'about' => __('super_admin.website.templates.about'),
            'contact' => __('super_admin.website.templates.contact'),
            'custom' => __('super_admin.website.templates.custom'),
        ];

        return view('super-admin.website.pages.create', compact('templates'));
    }

    /**
     * Store a newly created page.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255|unique:cmis_website.pages,slug',
            'content_en' => 'nullable|string',
            'content_ar' => 'nullable|string',
            'template' => 'required|string|max:50',
            'meta_title_en' => 'nullable|string|max:255',
            'meta_title_ar' => 'nullable|string|max:255',
            'meta_description_en' => 'nullable|string|max:500',
            'meta_description_ar' => 'nullable|string|max:500',
            'featured_image_url' => 'nullable|url|max:500',
            'is_published' => 'boolean',
            'is_in_nav' => 'boolean',
            'nav_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['slug']);
            $validated['is_published'] = $request->boolean('is_published', false);
            $validated['is_in_nav'] = $request->boolean('is_in_nav', false);

            $page = Page::create($validated);

            $this->logAction('page_created', 'page', $page->id, $page->title_en, [
                'slug' => $page->slug,
                'template' => $page->template,
            ]);

            if ($request->expectsJson()) {
                return $this->created($page, __('super_admin.website.pages.created_success'));
            }

            return redirect()
                ->route('super-admin.website.pages.edit', $page->id)
                ->with('success', __('super_admin.website.pages.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create page', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.website.pages.create_failed'));
            }

            return back()
                ->withInput()
                ->with('error', __('super_admin.website.pages.create_failed'));
        }
    }

    /**
     * Display the specified page.
     */
    public function show(string $id)
    {
        $page = Page::with(['sections' => function ($q) {
            $q->orderBy('sort_order');
        }, 'seoMetadata'])->findOrFail($id);

        return view('super-admin.website.pages.show', compact('page'));
    }

    /**
     * Show the form for editing a page.
     */
    public function edit(string $id)
    {
        $page = Page::with(['sections' => function ($q) {
            $q->orderBy('sort_order');
        }, 'seoMetadata'])->findOrFail($id);

        $templates = [
            'default' => __('super_admin.website.templates.default'),
            'landing' => __('super_admin.website.templates.landing'),
            'about' => __('super_admin.website.templates.about'),
            'contact' => __('super_admin.website.templates.contact'),
            'custom' => __('super_admin.website.templates.custom'),
        ];

        return view('super-admin.website.pages.edit', compact('page', 'templates'));
    }

    /**
     * Update the specified page.
     */
    public function update(Request $request, string $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('cmis_website.pages', 'slug')->ignore($page->id)],
            'content_en' => 'nullable|string',
            'content_ar' => 'nullable|string',
            'template' => 'required|string|max:50',
            'meta_title_en' => 'nullable|string|max:255',
            'meta_title_ar' => 'nullable|string|max:255',
            'meta_description_en' => 'nullable|string|max:500',
            'meta_description_ar' => 'nullable|string|max:500',
            'featured_image_url' => 'nullable|url|max:500',
            'is_published' => 'boolean',
            'is_in_nav' => 'boolean',
            'nav_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['slug']);
            $validated['is_published'] = $request->boolean('is_published', false);
            $validated['is_in_nav'] = $request->boolean('is_in_nav', false);

            $page->update($validated);

            $this->logAction('page_updated', 'page', $page->id, $page->title_en, [
                'slug' => $page->slug,
            ]);

            if ($request->expectsJson()) {
                return $this->success($page, __('super_admin.website.pages.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.pages.edit', $page->id)
                ->with('success', __('super_admin.website.pages.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update page', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.website.pages.update_failed'));
            }

            return back()
                ->withInput()
                ->with('error', __('super_admin.website.pages.update_failed'));
        }
    }

    /**
     * Toggle page published status.
     */
    public function togglePublished(Request $request, string $id)
    {
        $page = Page::findOrFail($id);

        $page->is_published = !$page->is_published;
        $page->save();

        $action = $page->is_published ? 'page_published' : 'page_unpublished';
        $this->logAction($action, 'page', $page->id, $page->title_en);

        return $this->success([
            'is_published' => $page->is_published,
        ], $page->is_published
            ? __('super_admin.website.pages.published_success')
            : __('super_admin.website.pages.unpublished_success')
        );
    }

    /**
     * Duplicate a page.
     */
    public function duplicate(string $id)
    {
        $original = Page::with('sections')->findOrFail($id);

        $copy = $original->replicate();
        $copy->title_en = $original->title_en . ' (Copy)';
        $copy->title_ar = $original->title_ar ? $original->title_ar . ' (نسخة)' : null;
        $copy->slug = $original->slug . '-copy-' . Str::random(5);
        $copy->is_published = false;
        $copy->save();

        // Duplicate sections
        foreach ($original->sections as $section) {
            $sectionCopy = $section->replicate();
            $sectionCopy->page_id = $copy->id;
            $sectionCopy->save();
        }

        $this->logAction('page_duplicated', 'page', $copy->id, $copy->title_en, [
            'original_id' => $original->id,
        ]);

        return redirect()
            ->route('super-admin.website.pages.edit', $copy->id)
            ->with('success', __('super_admin.website.pages.duplicated_success'));
    }

    /**
     * Remove the specified page.
     */
    public function destroy(string $id)
    {
        $page = Page::findOrFail($id);

        $this->logAction('page_deleted', 'page', $page->id, $page->title_en);

        $page->delete();

        return redirect()
            ->route('super-admin.website.pages.index')
            ->with('success', __('super_admin.website.pages.deleted_success'));
    }

    /**
     * Preview a page.
     */
    public function preview(string $id)
    {
        $page = Page::with(['sections' => function ($q) {
            $q->orderBy('sort_order');
        }])->findOrFail($id);

        return view('marketing.pages.show', compact('page'));
    }
}
