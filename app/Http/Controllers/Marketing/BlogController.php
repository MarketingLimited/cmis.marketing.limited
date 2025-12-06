<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Website\BlogPost;
use App\Models\Website\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Blog Controller
 *
 * Handles public blog pages.
 */
class BlogController extends Controller
{
    /**
     * Display the blog index.
     */
    public function index(Request $request)
    {
        $query = BlogPost::with('category')
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $locale = app()->getLocale();
                $q->where("title_{$locale}", 'ilike', "%{$search}%")
                  ->orWhere("content_{$locale}", 'ilike', "%{$search}%")
                  ->orWhere("excerpt_{$locale}", 'ilike', "%{$search}%");
            });
        }

        // Tag filter
        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $posts = $query->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        // Get categories for sidebar
        $categories = Cache::remember('marketing.blog_categories', 3600, function () {
            return BlogCategory::where('is_active', true)
                ->withCount(['posts' => function ($query) {
                    $query->where('is_published', true)
                          ->whereNotNull('published_at')
                          ->where('published_at', '<=', now());
                }])
                ->orderBy('sort_order')
                ->get()
                ->filter(fn($cat) => $cat->posts_count > 0);
        });

        // Get featured posts for sidebar
        $featuredPosts = Cache::remember('marketing.featured_blog_posts', 3600, function () {
            return BlogPost::where('is_published', true)
                ->where('is_featured', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->latest('published_at')
                ->limit(5)
                ->get();
        });

        // Get popular tags
        $popularTags = Cache::remember('marketing.popular_tags', 3600, function () {
            return BlogPost::where('is_published', true)
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->countBy()
                ->sortDesc()
                ->take(15)
                ->keys();
        });

        return view('marketing.blog.index', compact(
            'posts',
            'categories',
            'featuredPosts',
            'popularTags'
        ));
    }

    /**
     * Display posts by category.
     */
    public function category(BlogCategory $category)
    {
        $posts = BlogPost::with('category')
            ->where('category_id', $category->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(12);

        // Get all categories for sidebar
        $categories = BlogCategory::where('is_active', true)
            ->withCount(['posts' => function ($query) {
                $query->where('is_published', true)
                      ->whereNotNull('published_at')
                      ->where('published_at', '<=', now());
            }])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($cat) => $cat->posts_count > 0);

        return view('marketing.blog.category', compact('posts', 'category', 'categories'));
    }

    /**
     * Display a single blog post.
     */
    public function show(BlogPost $post)
    {
        // Only show published posts
        if (!$post->is_published || !$post->published_at || $post->published_at > now()) {
            abort(404);
        }

        // Increment view count (non-blocking)
        $post->increment('views');

        // Load relationships
        $post->load(['category', 'seoMetadata']);

        // Get related posts (same category or similar tags)
        $relatedPosts = BlogPost::where('id', '!=', $post->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($query) use ($post) {
                if ($post->category_id) {
                    $query->where('category_id', $post->category_id);
                }
                if ($post->tags) {
                    foreach ($post->tags as $tag) {
                        $query->orWhereJsonContains('tags', $tag);
                    }
                }
            })
            ->latest('published_at')
            ->limit(3)
            ->get();

        // Get previous and next posts
        $previousPost = BlogPost::where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<', $post->published_at)
            ->latest('published_at')
            ->first();

        $nextPost = BlogPost::where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '>', $post->published_at)
            ->oldest('published_at')
            ->first();

        return view('marketing.blog.show', compact(
            'post',
            'relatedPosts',
            'previousPost',
            'nextPost'
        ));
    }
}
