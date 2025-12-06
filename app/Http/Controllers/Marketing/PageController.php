<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Website\Page;
use Illuminate\Support\Facades\Cache;

/**
 * Page Controller
 *
 * Handles CMS pages including legal pages and dynamic pages.
 */
class PageController extends Controller
{
    /**
     * Display the Terms of Service page.
     */
    public function terms()
    {
        $page = Cache::remember('marketing.page.terms', 3600, function () {
            return Page::where('is_published', true)
                ->where(function ($query) {
                    $query->where('slug', 'terms')
                          ->orWhere('slug', 'terms-of-service');
                })
                ->first();
        });

        if (!$page) {
            abort(404);
        }

        $page->load('seoMetadata');

        return view('marketing.pages.terms', compact('page'));
    }

    /**
     * Display the Privacy Policy page.
     */
    public function privacy()
    {
        $page = Cache::remember('marketing.page.privacy', 3600, function () {
            return Page::where('is_published', true)
                ->where(function ($query) {
                    $query->where('slug', 'privacy')
                          ->orWhere('slug', 'privacy-policy');
                })
                ->first();
        });

        if (!$page) {
            abort(404);
        }

        $page->load('seoMetadata');

        return view('marketing.pages.privacy', compact('page'));
    }

    /**
     * Display the Cookies Policy page.
     */
    public function cookies()
    {
        $page = Cache::remember('marketing.page.cookies', 3600, function () {
            return Page::where('is_published', true)
                ->where(function ($query) {
                    $query->where('slug', 'cookies')
                          ->orWhere('slug', 'cookie-policy');
                })
                ->first();
        });

        if (!$page) {
            abort(404);
        }

        $page->load('seoMetadata');

        return view('marketing.pages.cookies', compact('page'));
    }

    /**
     * Display a dynamic CMS page by slug.
     *
     * This is the catch-all route for marketing pages.
     */
    public function show(string $slug)
    {
        // Try to get from cache first
        $page = Cache::remember("marketing.page.{$slug}", 3600, function () use ($slug) {
            return Page::where('slug', $slug)
                ->where('is_published', true)
                ->with(['sections' => function ($query) {
                    $query->where('is_active', true)
                          ->orderBy('sort_order');
                }, 'seoMetadata'])
                ->first();
        });

        if (!$page) {
            abort(404);
        }

        // Determine which template to use
        $template = $page->template ?? 'default';
        $viewName = "marketing.pages.{$template}";

        // Fallback to default if template view doesn't exist
        if (!view()->exists($viewName)) {
            $viewName = 'marketing.pages.default';
        }

        return view($viewName, compact('page'));
    }
}
