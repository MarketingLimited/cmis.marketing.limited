<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\CaseStudy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Super Admin Case Study Controller
 *
 * Manages success stories and case studies.
 */
class SuperAdminCaseStudyController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    public function index(Request $request)
    {
        $query = CaseStudy::query();

        if ($request->filled('status')) {
            $query->where('is_published', $request->status === 'published');
        }

        if ($request->filled('featured')) {
            $query->where('is_featured', $request->featured === 'yes');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title_en', 'ilike', "%{$search}%")
                  ->orWhere('client_name_en', 'ilike', "%{$search}%")
                  ->orWhere('industry_en', 'ilike', "%{$search}%");
            });
        }

        $caseStudies = $query->latest('published_at')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return $this->success(['case_studies' => $caseStudies]);
        }

        return view('super-admin.website.case-studies.index', compact('caseStudies'));
    }

    public function create()
    {
        return view('super-admin.website.case-studies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:cmis_website.case_studies,slug',
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'excerpt_en' => 'nullable|string|max:500',
            'excerpt_ar' => 'nullable|string|max:500',
            'client_name_en' => 'required|string|max:255',
            'client_name_ar' => 'nullable|string|max:255',
            'client_logo_url' => 'nullable|url|max:500',
            'industry_en' => 'required|string|max:255',
            'industry_ar' => 'nullable|string|max:255',
            'challenge_en' => 'required|string',
            'challenge_ar' => 'nullable|string',
            'solution_en' => 'required|string',
            'solution_ar' => 'nullable|string',
            'results_en' => 'required|string',
            'results_ar' => 'nullable|string',
            'metrics' => 'nullable|array',
            'metrics.*.label_en' => 'required_with:metrics|string|max:100',
            'metrics.*.label_ar' => 'nullable|string|max:100',
            'metrics.*.value' => 'required_with:metrics|string|max:50',
            'metrics.*.prefix' => 'nullable|string|max:10',
            'metrics.*.suffix' => 'nullable|string|max:10',
            'featured_image_url' => 'nullable|url|max:500',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'url|max:500',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['slug']);
            $validated['is_published'] = $request->boolean('is_published', false);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['sort_order'] = $validated['sort_order'] ?? CaseStudy::max('sort_order') + 1;

            if ($validated['is_published'] && !$validated['published_at']) {
                $validated['published_at'] = now();
            }

            $caseStudy = CaseStudy::create($validated);

            $this->logAction('case_study_created', 'case_study', $caseStudy->id, $caseStudy->title_en);

            if ($request->expectsJson()) {
                return $this->created($caseStudy, __('super_admin.website.case_studies.created_success'));
            }

            return redirect()
                ->route('super-admin.website.case-studies.edit', $caseStudy->id)
                ->with('success', __('super_admin.website.case_studies.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create case study', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.case_studies.create_failed'));
        }
    }

    public function edit(string $id)
    {
        $caseStudy = CaseStudy::with('seoMetadata')->findOrFail($id);
        return view('super-admin.website.case-studies.edit', compact('caseStudy'));
    }

    public function update(Request $request, string $id)
    {
        $caseStudy = CaseStudy::findOrFail($id);

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255', Rule::unique('cmis_website.case_studies', 'slug')->ignore($caseStudy->id)],
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'excerpt_en' => 'nullable|string|max:500',
            'excerpt_ar' => 'nullable|string|max:500',
            'client_name_en' => 'required|string|max:255',
            'client_name_ar' => 'nullable|string|max:255',
            'client_logo_url' => 'nullable|url|max:500',
            'industry_en' => 'required|string|max:255',
            'industry_ar' => 'nullable|string|max:255',
            'challenge_en' => 'required|string',
            'challenge_ar' => 'nullable|string',
            'solution_en' => 'required|string',
            'solution_ar' => 'nullable|string',
            'results_en' => 'required|string',
            'results_ar' => 'nullable|string',
            'metrics' => 'nullable|array',
            'featured_image_url' => 'nullable|url|max:500',
            'gallery_images' => 'nullable|array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['slug']);
            $validated['is_published'] = $request->boolean('is_published', false);
            $validated['is_featured'] = $request->boolean('is_featured', false);

            if ($validated['is_published'] && !$caseStudy->published_at && !$validated['published_at']) {
                $validated['published_at'] = now();
            }

            $caseStudy->update($validated);

            $this->logAction('case_study_updated', 'case_study', $caseStudy->id, $caseStudy->title_en);

            if ($request->expectsJson()) {
                return $this->success($caseStudy, __('super_admin.website.case_studies.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.case-studies.edit', $caseStudy->id)
                ->with('success', __('super_admin.website.case_studies.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update case study', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.case_studies.update_failed'));
        }
    }

    public function togglePublished(string $id)
    {
        $caseStudy = CaseStudy::findOrFail($id);
        $caseStudy->is_published = !$caseStudy->is_published;

        if ($caseStudy->is_published && !$caseStudy->published_at) {
            $caseStudy->published_at = now();
        }

        $caseStudy->save();

        $this->logAction($caseStudy->is_published ? 'case_study_published' : 'case_study_unpublished', 'case_study', $caseStudy->id, $caseStudy->title_en);

        return $this->success(['is_published' => $caseStudy->is_published]);
    }

    public function destroy(string $id)
    {
        $caseStudy = CaseStudy::findOrFail($id);
        $this->logAction('case_study_deleted', 'case_study', $caseStudy->id, $caseStudy->title_en);
        $caseStudy->delete();

        return redirect()
            ->route('super-admin.website.case-studies.index')
            ->with('success', __('super_admin.website.case_studies.deleted_success'));
    }

    public function preview(string $id)
    {
        $caseStudy = CaseStudy::findOrFail($id);
        return view('marketing.case-studies.show', compact('caseStudy'));
    }
}
