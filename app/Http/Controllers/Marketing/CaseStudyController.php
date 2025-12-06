<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Website\CaseStudy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Case Study Controller
 *
 * Handles public case study pages.
 */
class CaseStudyController extends Controller
{
    /**
     * Display the case studies index.
     */
    public function index(Request $request)
    {
        $query = CaseStudy::where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        // Industry filter
        if ($request->filled('industry')) {
            $query->where('industry_en', $request->industry);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $locale = app()->getLocale();
            $query->where(function ($q) use ($search, $locale) {
                $q->where("title_{$locale}", 'ilike', "%{$search}%")
                  ->orWhere("client_name_{$locale}", 'ilike', "%{$search}%")
                  ->orWhere("industry_{$locale}", 'ilike', "%{$search}%");
            });
        }

        $caseStudies = $query->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        // Get industries for filter
        $industries = Cache::remember('marketing.case_study_industries', 3600, function () {
            return CaseStudy::where('is_published', true)
                ->distinct()
                ->pluck('industry_en')
                ->filter()
                ->sort()
                ->values();
        });

        // Get featured case studies
        $featuredCaseStudies = Cache::remember('marketing.featured_case_studies', 3600, function () {
            return CaseStudy::where('is_published', true)
                ->where('is_featured', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderBy('sort_order')
                ->limit(3)
                ->get();
        });

        return view('marketing.case-studies.index', compact(
            'caseStudies',
            'industries',
            'featuredCaseStudies'
        ));
    }

    /**
     * Display a single case study.
     */
    public function show(CaseStudy $caseStudy)
    {
        // Only show published case studies
        if (!$caseStudy->is_published || !$caseStudy->published_at || $caseStudy->published_at > now()) {
            abort(404);
        }

        // Load SEO metadata
        $caseStudy->load('seoMetadata');

        // Get related case studies (same industry)
        $relatedCaseStudies = CaseStudy::where('id', '!=', $caseStudy->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('industry_en', $caseStudy->industry_en)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        // If not enough from same industry, get others
        if ($relatedCaseStudies->count() < 3) {
            $additionalCount = 3 - $relatedCaseStudies->count();
            $existingIds = $relatedCaseStudies->pluck('id')->push($caseStudy->id);

            $additionalStudies = CaseStudy::where('is_published', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereNotIn('id', $existingIds)
                ->orderBy('sort_order')
                ->limit($additionalCount)
                ->get();

            $relatedCaseStudies = $relatedCaseStudies->concat($additionalStudies);
        }

        return view('marketing.case-studies.show', compact(
            'caseStudy',
            'relatedCaseStudies'
        ));
    }
}
