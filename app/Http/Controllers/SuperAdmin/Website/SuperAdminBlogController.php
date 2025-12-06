<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\BlogPost;
use App\Models\Website\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Super Admin Blog Controller
 *
 * Manages blog posts.
 */
class SuperAdminBlogController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    public function index(Request $request)
    {
        $query = BlogPost::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

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
                  ->orWhere('title_ar', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $posts = $query->paginate(15)->withQueryString();
        $categories = BlogCategory::orderBy('sort_order')->get();

        $stats = [
            'total' => BlogPost::count(),
            'published' => BlogPost::where('is_published', true)->count(),
            'draft' => BlogPost::where('is_published', false)->count(),
            'featured' => BlogPost::where('is_featured', true)->count(),
        ];

        if ($request->expectsJson()) {
            return $this->success(['posts' => $posts, 'categories' => $categories, 'stats' => $stats]);
        }

        return view('super-admin.website.blog.index', compact('posts', 'categories', 'stats'));
    }

    public function create()
    {
        $categories = BlogCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('super-admin.website.blog.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:cmis_website.blog_categories,id',
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255|unique:cmis_website.blog_posts,slug',
            'excerpt_en' => 'nullable|string|max:500',
            'excerpt_ar' => 'nullable|string|max:500',
            'content_en' => 'required|string',
            'content_ar' => 'nullable|string',
            'featured_image_url' => 'nullable|url|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'reading_time_minutes' => 'nullable|integer|min:1|max:60',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['slug']);
            $validated['author_id'] = Auth::id();
            $validated['is_published'] = $request->boolean('is_published', false);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['allow_comments'] = $request->boolean('allow_comments', true);
            $validated['reading_time_minutes'] = $validated['reading_time_minutes'] ?? $this->estimateReadingTime($validated['content_en']);

            if ($validated['is_published'] && !$validated['published_at']) {
                $validated['published_at'] = now();
            }

            $post = BlogPost::create($validated);

            $this->logAction('blog_post_created', 'blog_post', $post->id, $post->title_en);

            if ($request->expectsJson()) {
                return $this->created($post, __('super_admin.website.blog.created_success'));
            }

            return redirect()
                ->route('super-admin.website.blog.edit', $post->id)
                ->with('success', __('super_admin.website.blog.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create blog post', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.blog.create_failed'));
        }
    }

    public function show(string $id)
    {
        $post = BlogPost::with(['category', 'seoMetadata'])->findOrFail($id);
        return view('super-admin.website.blog.show', compact('post'));
    }

    public function edit(string $id)
    {
        $post = BlogPost::with('seoMetadata')->findOrFail($id);
        $categories = BlogCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('super-admin.website.blog.edit', compact('post', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $post = BlogPost::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:cmis_website.blog_categories,id',
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('cmis_website.blog_posts', 'slug')->ignore($post->id)],
            'excerpt_en' => 'nullable|string|max:500',
            'excerpt_ar' => 'nullable|string|max:500',
            'content_en' => 'required|string',
            'content_ar' => 'nullable|string',
            'featured_image_url' => 'nullable|url|max:500',
            'tags' => 'nullable|array',
            'reading_time_minutes' => 'nullable|integer|min:1|max:60',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['slug']);
            $validated['is_published'] = $request->boolean('is_published', false);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['allow_comments'] = $request->boolean('allow_comments', true);

            if ($validated['is_published'] && !$post->published_at && !$validated['published_at']) {
                $validated['published_at'] = now();
            }

            $post->update($validated);

            $this->logAction('blog_post_updated', 'blog_post', $post->id, $post->title_en);

            if ($request->expectsJson()) {
                return $this->success($post, __('super_admin.website.blog.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.blog.edit', $post->id)
                ->with('success', __('super_admin.website.blog.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update blog post', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.blog.update_failed'));
        }
    }

    public function togglePublished(string $id)
    {
        $post = BlogPost::findOrFail($id);
        $post->is_published = !$post->is_published;

        if ($post->is_published && !$post->published_at) {
            $post->published_at = now();
        }

        $post->save();

        $this->logAction($post->is_published ? 'blog_post_published' : 'blog_post_unpublished', 'blog_post', $post->id, $post->title_en);

        return $this->success([
            'is_published' => $post->is_published,
            'published_at' => $post->published_at,
        ]);
    }

    public function toggleFeatured(string $id)
    {
        $post = BlogPost::findOrFail($id);
        $post->is_featured = !$post->is_featured;
        $post->save();

        return $this->success(['is_featured' => $post->is_featured]);
    }

    public function duplicate(string $id)
    {
        $original = BlogPost::findOrFail($id);

        $copy = $original->replicate();
        $copy->title_en = $original->title_en . ' (Copy)';
        $copy->title_ar = $original->title_ar ? $original->title_ar . ' (نسخة)' : null;
        $copy->slug = $original->slug . '-copy-' . Str::random(5);
        $copy->is_published = false;
        $copy->published_at = null;
        $copy->views = 0;
        $copy->author_id = Auth::id();
        $copy->save();

        $this->logAction('blog_post_duplicated', 'blog_post', $copy->id, $copy->title_en, [
            'original_id' => $original->id,
        ]);

        return redirect()
            ->route('super-admin.website.blog.edit', $copy->id)
            ->with('success', __('super_admin.website.blog.duplicated_success'));
    }

    public function destroy(string $id)
    {
        $post = BlogPost::findOrFail($id);
        $this->logAction('blog_post_deleted', 'blog_post', $post->id, $post->title_en);
        $post->delete();

        return redirect()
            ->route('super-admin.website.blog.index')
            ->with('success', __('super_admin.website.blog.deleted_success'));
    }

    public function preview(string $id)
    {
        $post = BlogPost::with('category')->findOrFail($id);
        return view('marketing.blog.show', compact('post'));
    }

    /**
     * Estimate reading time based on word count.
     */
    private function estimateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $readingTime = ceil($wordCount / 200); // Average 200 words per minute
        return max(1, min(60, $readingTime));
    }
}
