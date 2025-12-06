<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\Page;
use App\Models\Website\BlogPost;
use App\Models\Website\CaseStudy;
use App\Models\Website\Feature;
use App\Models\Website\FaqItem;
use App\Models\Website\HeroSlide;
use App\Models\Website\Testimonial;
use App\Models\Website\TeamMember;
use App\Models\Website\Partner;
use Illuminate\Http\Request;

/**
 * Super Admin Website Dashboard Controller
 *
 * Overview dashboard for marketing website management.
 */
class SuperAdminWebsiteDashboardController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display the website management dashboard.
     */
    public function index(Request $request)
    {
        $stats = [
            'pages' => [
                'total' => Page::count(),
                'published' => Page::where('is_published', true)->count(),
                'draft' => Page::where('is_published', false)->count(),
            ],
            'blog' => [
                'total' => BlogPost::count(),
                'published' => BlogPost::where('is_published', true)->count(),
                'draft' => BlogPost::where('is_published', false)->count(),
            ],
            'case_studies' => [
                'total' => CaseStudy::count(),
                'published' => CaseStudy::where('is_published', true)->count(),
                'featured' => CaseStudy::where('is_featured', true)->count(),
            ],
            'features' => [
                'total' => Feature::count(),
                'active' => Feature::where('is_active', true)->count(),
            ],
            'faqs' => [
                'total' => FaqItem::count(),
                'active' => FaqItem::where('is_active', true)->count(),
            ],
            'hero_slides' => [
                'total' => HeroSlide::count(),
                'active' => HeroSlide::where('is_active', true)->count(),
            ],
            'testimonials' => [
                'total' => Testimonial::count(),
                'active' => Testimonial::where('is_active', true)->count(),
                'featured' => Testimonial::where('is_featured', true)->count(),
            ],
            'team' => [
                'total' => TeamMember::count(),
                'active' => TeamMember::where('is_active', true)->count(),
            ],
            'partners' => [
                'total' => Partner::count(),
                'active' => Partner::where('is_active', true)->count(),
                'featured' => Partner::where('is_featured', true)->count(),
            ],
        ];

        // Recent activity
        $recentPages = Page::latest()->take(5)->get();
        $recentPosts = BlogPost::latest()->take(5)->get();

        if ($request->expectsJson()) {
            return $this->success([
                'stats' => $stats,
                'recent_pages' => $recentPages,
                'recent_posts' => $recentPosts,
            ]);
        }

        return view('super-admin.website.dashboard', compact('stats', 'recentPages', 'recentPosts'));
    }
}
