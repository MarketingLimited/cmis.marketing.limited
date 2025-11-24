@extends('layouts.admin')

@section('page-title', __('social.title'))
@section('page-subtitle', __('social.subtitle'))

@section('content')
<div x-data="socialManager()" x-init="init()">
    <!-- Header with Filters -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Platform Filters -->
            <div class="flex gap-2">
                <button @click="filterPlatform = 'all'"
                        :class="filterPlatform === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg font-medium transition">
                    <i class="fas fa-globe ml-2"></i>
                    {{ __('social.all') }}
                </button>
                <button @click="filterPlatform = 'facebook'"
                        :class="filterPlatform === 'facebook' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg font-medium transition">
                    <i class="fab fa-facebook ml-2"></i>
                    Facebook
                </button>
                <button @click="filterPlatform = 'instagram'"
                        :class="filterPlatform === 'instagram' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg font-medium transition">
                    <i class="fab fa-instagram ml-2"></i>
                    Instagram
                </button>
                <button @click="filterPlatform = 'twitter'"
                        :class="filterPlatform === 'twitter' ? 'bg-sky-500 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg font-medium transition">
                    <i class="fab fa-twitter ml-2"></i>
                    Twitter
                </button>
                <button @click="filterPlatform = 'linkedin'"
                        :class="filterPlatform === 'linkedin' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg font-medium transition">
                    <i class="fab fa-linkedin ml-2"></i>
                    LinkedIn
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button @click="showNewPostModal = true"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2 rounded-lg font-medium hover:shadow-lg transition">
                    <i class="fas fa-plus ml-2"></i>
                    {{ __('social.new_post') }}
                </button>
                <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-calendar ml-2"></i>
                    {{ __('social.content_calendar') }}
                </button>
            </div>
        </div>

        <!-- Status Tabs -->
        <div class="flex gap-2 mt-4 border-t pt-4">
            <button @click="statusFilter = 'all'"
                    :class="statusFilter === 'all' ? 'text-indigo-600 border-indigo-600' : 'text-gray-600 border-transparent'"
                    class="px-4 py-2 border-b-2 font-medium transition">
                {{ __('social.all') }}
            </button>
            <button @click="statusFilter = 'scheduled'"
                    :class="statusFilter === 'scheduled' ? 'text-indigo-600 border-indigo-600' : 'text-gray-600 border-transparent'"
                    class="px-4 py-2 border-b-2 font-medium transition">
                <i class="fas fa-clock ml-2"></i>
                {{ __('social.scheduled') }} (<span x-text="scheduledCount"></span>)
            </button>
            <button @click="statusFilter = 'published'"
                    :class="statusFilter === 'published' ? 'text-indigo-600 border-indigo-600' : 'text-gray-600 border-transparent'"
                    class="px-4 py-2 border-b-2 font-medium transition">
                <i class="fas fa-check-circle ml-2"></i>
                {{ __('social.published') }} (<span x-text="publishedCount"></span>)
            </button>
            <button @click="statusFilter = 'draft'"
                    :class="statusFilter === 'draft' ? 'text-indigo-600 border-indigo-600' : 'text-gray-600 border-transparent'"
                    class="px-4 py-2 border-b-2 font-medium transition">
                <i class="fas fa-file ml-2"></i>
                {{ __('social.draft') }} (<span x-text="draftCount"></span>)
            </button>
            <button @click="statusFilter = 'failed'"
                    :class="statusFilter === 'failed' ? 'text-indigo-600 border-indigo-600' : 'text-gray-600 border-transparent'"
                    class="px-4 py-2 border-b-2 font-medium transition">
                <i class="fas fa-exclamation-triangle ml-2"></i>
                {{ __('social.failed') }} (<span x-text="failedCount"></span>)
            </button>
        </div>
    </div>

    <!-- Posts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="post in filteredPosts" :key="post.post_id">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition card-hover">
                <!-- Platform Badge -->
                <div class="px-4 py-3 border-b flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i :class="{
                            'fab fa-facebook text-blue-600': post.platform === 'facebook',
                            'fab fa-instagram text-pink-600': post.platform === 'instagram',
                            'fab fa-twitter text-sky-500': post.platform === 'twitter',
                            'fab fa-linkedin text-blue-700': post.platform === 'linkedin'
                        }" class="text-xl"></i>
                        <span class="font-medium text-gray-700" x-text="post.platform"></span>
                    </div>

                    <!-- Status Badge -->
                    <span :class="{
                        'bg-yellow-100 text-yellow-800': post.status === 'scheduled',
                        'bg-green-100 text-green-800': post.status === 'published',
                        'bg-gray-100 text-gray-800': post.status === 'draft',
                        'bg-red-100 text-red-800': post.status === 'failed'
                    }" class="px-2 py-1 rounded-full text-xs font-medium">
                        <span x-text="getStatusLabel(post.status)"></span>
                    </span>
                </div>

                <!-- Post Content -->
                <div class="p-4">
                    <p class="text-gray-700 text-sm line-clamp-3 mb-3" x-text="post.post_text"></p>

                    <!-- Media Preview -->
                    <template x-if="post.media_urls && post.media_urls.length > 0">
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <template x-for="(media, index) in post.media_urls.slice(0, 4)" :key="index">
                                <img :src="media" class="w-full h-24 object-cover rounded-lg">
                            </template>
                        </div>
                    </template>

                    <!-- Metrics (for published posts) -->
                    <template x-if="post.status === 'published'">
                        <div class="grid grid-cols-4 gap-2 mb-3 py-2 border-t">
                            <div class="text-center">
                                <i class="fas fa-heart text-red-500 text-sm"></i>
                                <p class="text-xs font-medium text-gray-600 mt-1" x-text="post.likes || 0"></p>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-comment text-blue-500 text-sm"></i>
                                <p class="text-xs font-medium text-gray-600 mt-1" x-text="post.comments || 0"></p>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-share text-green-500 text-sm"></i>
                                <p class="text-xs font-medium text-gray-600 mt-1" x-text="post.shares || 0"></p>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-eye text-purple-500 text-sm"></i>
                                <p class="text-xs font-medium text-gray-600 mt-1" x-text="post.reach || 0"></p>
                            </div>
                        </div>
                    </template>

                    <!-- Scheduled Time -->
                    <template x-if="post.scheduled_at">
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                            <i class="fas fa-clock"></i>
                            <span x-text="formatDate(post.scheduled_at)"></span>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button @click="editPost(post)"
                                class="flex-1 bg-indigo-50 text-indigo-600 px-3 py-2 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">
                            <i class="fas fa-edit ml-1"></i>
                            {{ __('social.edit') }}
                        </button>
                        <template x-if="post.status === 'scheduled'">
                            <button @click="publishNow(post.post_id)"
                                    class="flex-1 bg-green-50 text-green-600 px-3 py-2 rounded-lg text-sm font-medium hover:bg-green-100 transition">
                                <i class="fas fa-paper-plane ml-1"></i>
                                {{ __('social.publish_now') }}
                            </button>
                        </template>
                        <button @click="deletePost(post.post_id)"
                                class="bg-red-50 text-red-600 px-3 py-2 rounded-lg text-sm hover:bg-red-100 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredPosts.length === 0">
        <x-empty-state
            icon="fas fa-calendar-alt"
            :title="__('social.no_posts')"
            :description="__('social.no_posts_description')"
            :action-text="__('social.create_post')"
            action-click="showNewPostModal = true"
        />
    </template>

    <!-- New Post Modal (will be implemented separately) -->
</div>
@endsection

@push('scripts')
<script>
function socialManager() {
    return {
        posts: [],
        filterPlatform: 'all',
        statusFilter: 'all',
        showNewPostModal: false,
        scheduledCount: 0,
        publishedCount: 0,
        draftCount: 0,
        failedCount: 0,

        async init() {
            await this.fetchPosts();
        },

        async fetchPosts() {
            try {
                const response = await fetch('/api/social/posts');
                const data = await response.json();
                this.posts = data.posts || [];
                this.updateCounts();
            } catch (error) {
                console.error('Failed to fetch posts:', error);
            }
        },

        get filteredPosts() {
            return this.posts.filter(post => {
                const platformMatch = this.filterPlatform === 'all' || post.platform === this.filterPlatform;
                const statusMatch = this.statusFilter === 'all' || post.status === this.statusFilter;
                return platformMatch && statusMatch;
            });
        },

        updateCounts() {
            this.scheduledCount = this.posts.filter(p => p.status === 'scheduled').length;
            this.publishedCount = this.posts.filter(p => p.status === 'published').length;
            this.draftCount = this.posts.filter(p => p.status === 'draft').length;
            this.failedCount = this.posts.filter(p => p.status === 'failed').length;
        },

        getStatusLabel(status) {
            const labels = {
                'scheduled': 'مجدول',
                'published': 'منشور',
                'draft': 'مسودة',
                'failed': 'فشل'
            };
            return labels[status] || status;
        },

        formatDate(date) {
            return new Date(date).toLocaleString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        editPost(post) {
            // Navigate to edit page or open modal
            window.location.href = `/social/posts/${post.post_id}/edit`;
        },

        async publishNow(postId) {
            if (!confirm('هل تريد نشر هذا المنشور الآن؟')) return;

            try {
                const response = await fetch(`/api/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    await this.fetchPosts();
                    alert('تم نشر المنشور بنجاح');
                }
            } catch (error) {
                console.error('Failed to publish post:', error);
                alert('فشل نشر المنشور');
            }
        },

        async deletePost(postId) {
            if (!confirm('هل أنت متأكد من حذف هذا المنشور؟')) return;

            try {
                const response = await fetch(`/api/social/posts/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    await this.fetchPosts();
                }
            } catch (error) {
                console.error('Failed to delete post:', error);
                alert('فشل حذف المنشور');
            }
        }
    };
}
</script>
@endpush
