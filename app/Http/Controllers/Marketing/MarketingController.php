<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Website\HeroSlide;
use App\Models\Website\Feature;
use App\Models\Website\FeatureCategory;
use App\Models\Website\Testimonial;
use App\Models\Website\Partner;
use App\Models\Website\FaqItem;
use App\Models\Website\FaqCategory;
use App\Models\Website\TeamMember;
use App\Models\Website\CaseStudy;
use App\Models\Subscription\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Marketing Controller
 *
 * Handles public-facing marketing pages (homepage, features, pricing, about, faq).
 */
class MarketingController extends Controller
{
    /**
     * Display the homepage.
     */
    public function home(Request $request)
    {
        // Get active hero slides
        $heroSlides = Cache::remember('marketing.hero_slides', 3600, function () {
            return HeroSlide::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        // Get featured features (limit to 6)
        $features = Cache::remember('marketing.featured_features', 3600, function () {
            return Feature::where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit(6)
                ->get();
        });

        // Get featured testimonials
        $testimonials = Cache::remember('marketing.featured_testimonials', 3600, function () {
            return Testimonial::where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit(6)
                ->get();
        });

        // Get featured partners
        $partners = Cache::remember('marketing.featured_partners', 3600, function () {
            return Partner::where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->get();
        });

        // Get featured case studies
        $caseStudies = Cache::remember('marketing.featured_case_studies', 3600, function () {
            return CaseStudy::where('is_published', true)
                ->where('is_featured', true)
                ->latest('published_at')
                ->limit(3)
                ->get();
        });

        return view('marketing.home', compact(
            'heroSlides',
            'features',
            'testimonials',
            'partners',
            'caseStudies'
        ));
    }

    /**
     * Display the features page.
     */
    public function features(Request $request)
    {
        // Get feature categories with their features
        $categories = Cache::remember('marketing.feature_categories', 3600, function () {
            return FeatureCategory::where('is_active', true)
                ->with(['features' => function ($query) {
                    $query->where('is_active', true)
                          ->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
        });

        // Get uncategorized features
        $uncategorizedFeatures = Cache::remember('marketing.uncategorized_features', 3600, function () {
            return Feature::where('is_active', true)
                ->whereNull('category_id')
                ->orderBy('sort_order')
                ->get();
        });

        // Get testimonials for social proof
        $testimonials = Cache::remember('marketing.features_testimonials', 3600, function () {
            return Testimonial::where('is_active', true)
                ->orderBy('sort_order')
                ->limit(3)
                ->get();
        });

        return view('marketing.features', compact(
            'categories',
            'uncategorizedFeatures',
            'testimonials'
        ));
    }

    /**
     * Display the pricing page.
     *
     * Uses the existing cmis.plans table directly.
     */
    public function pricing(Request $request)
    {
        // Get active plans from existing plans table
        $plans = Cache::remember('marketing.pricing_plans', 1800, function () {
            return Plan::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        // Get FAQ items related to pricing
        $pricingFaqs = Cache::remember('marketing.pricing_faqs', 3600, function () {
            return FaqItem::where('is_active', true)
                ->whereHas('category', function ($query) {
                    $query->where('slug', 'pricing')
                          ->orWhere('slug', 'billing');
                })
                ->orderBy('sort_order')
                ->limit(6)
                ->get();
        });

        // Get testimonials for social proof
        $testimonials = Cache::remember('marketing.pricing_testimonials', 3600, function () {
            return Testimonial::where('is_active', true)
                ->where('is_featured', true)
                ->limit(3)
                ->get();
        });

        return view('marketing.pricing', compact('plans', 'pricingFaqs', 'testimonials'));
    }

    /**
     * Display the about page.
     */
    public function about(Request $request)
    {
        // Get leadership team members (featured ones are leadership)
        $leadership = Cache::remember('marketing.leadership', 3600, function () {
            return TeamMember::where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->get();
        });

        // Get all team members grouped by department
        $teamByDepartment = Cache::remember('marketing.team_by_department', 3600, function () {
            return TeamMember::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('department');
        });

        // Get partners
        $partners = Cache::remember('marketing.about_partners', 3600, function () {
            return Partner::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        // Get company stats (can be from website_settings or calculated)
        $stats = [
            'customers' => '500+',
            'countries' => '25+',
            'uptime' => '99.9%',
            'support' => '24/7',
        ];

        return view('marketing.about', compact(
            'leadership',
            'teamByDepartment',
            'partners',
            'stats'
        ));
    }

    /**
     * Display the FAQ page.
     */
    public function faq(Request $request)
    {
        // Get FAQ categories with their items
        $categories = Cache::remember('marketing.faq_categories', 3600, function () {
            return FaqCategory::where('is_active', true)
                ->with(['faqItems' => function ($query) {
                    $query->where('is_active', true)
                          ->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
        });

        // Get uncategorized FAQs
        $uncategorizedFaqs = Cache::remember('marketing.uncategorized_faqs', 3600, function () {
            return FaqItem::where('is_active', true)
                ->whereNull('category_id')
                ->orderBy('sort_order')
                ->get();
        });

        // Get featured FAQs for sidebar
        $featuredFaqs = Cache::remember('marketing.featured_faqs', 3600, function () {
            return FaqItem::where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit(5)
                ->get();
        });

        return view('marketing.faq', compact(
            'categories',
            'uncategorizedFaqs',
            'featuredFaqs'
        ));
    }
}
