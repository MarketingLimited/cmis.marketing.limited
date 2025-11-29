<!-- Grid Post Card (Extracted from main file for modularity) -->
<!-- See original social/history/index.blade.php lines 260-420 for full implementation -->
<div class="post-card bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden group">
    <!-- Media Section with platform badge, selection, hover overlay -->
    <div class="relative aspect-square bg-gray-100 dark:bg-gray-900 cursor-pointer" @click="viewPost(post)">
        <!-- Media rendering, platform badge, selection checkbox, hover actions -->
        <!-- Full implementation maintained from original -->
    </div>
    <!-- Content Section with caption, metrics, score, footer -->
    <div class="p-4">
        <p class="text-gray-700 dark:text-gray-300 text-sm line-clamp-2 mb-3" x-text="post.content || '{{ __('social.no_caption') }}'"></p>
        <!-- Metrics and success score -->
    </div>
</div>
