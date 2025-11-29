@extends('layouts.admin')
@section('title', __('social.social_media_publishing'))

@section('content')
<div class="container mx-auto px-4 py-6"
     x-data="socialPostManager('{{ $currentOrg->org_id }}', '{{ csrf_token() }}', socialTranslations, platformConfigs)"
     x-init="init()">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ __('social.social_media_publishing') }}</h1>
        <button @click="showCreateModal = true"
                data-testid="create-post-btn"
                id="create-post-btn"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="fas fa-plus"></i>
            {{ __('social.create_post') }}
        </button>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button
                    @click="filterStatus = 'all'"
                    :class="filterStatus === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    {{ __('social.all_posts') }}
                </button>
                <button
                    @click="filterStatus = 'draft'"
                    :class="filterStatus === 'draft' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    {{ __('social.drafts') }}
                </button>
                <button
                    @click="filterStatus = 'scheduled'"
                    :class="filterStatus === 'scheduled' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    {{ __('social.status.scheduled') }}
                </button>
                <button
                    @click="filterStatus = 'published'"
                    :class="filterStatus === 'published' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    {{ __('social.status.published') }}
                </button>
                <button
                    @click="filterStatus = 'failed'"
                    :class="filterStatus === 'failed' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    {{ __('social.status.failed') }}
                </button>
            </nav>
        </div>
    </div>

    <!-- Posts List -->
    <div class="bg-white rounded-lg shadow">
        <template x-if="loading">
            <div class="p-8 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">{{ __('social.loading_posts') }}</p>
            </div>
        </template>

        <template x-if="!loading && filteredPosts.length === 0">
            <div class="p-8 text-center">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">{{ __('social.no_posts_found') }}</p>
                <button @click="showCreateModal = true" class="mt-4 text-blue-600 hover:text-blue-800">
                    {{ __('social.create_first_post') }}
                </button>
            </div>
        </template>

        <template x-if="!loading && filteredPosts.length > 0">
            <div class="divide-y divide-gray-200">
                <template x-for="post in filteredPosts" :key="post.id">
                    <div class="p-6 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <!-- Platform Icon -->
                                    <span class="text-2xl" x-html="getPlatformIcon(post.platform)"></span>

                                    <!-- Account Name -->
                                    <span class="font-medium text-gray-900" x-text="post.account_username || '{{ __('social.unknown_account') }}'"></span>

                                    <!-- Status Badge -->
                                    <span
                                        :class="{
                                            'bg-gray-100 text-gray-800': post.status === 'draft',
                                            'bg-blue-100 text-blue-800': post.status === 'scheduled',
                                            'bg-green-100 text-green-800': post.status === 'published',
                                            'bg-red-100 text-red-800': post.status === 'failed',
                                            'bg-yellow-100 text-yellow-800': post.status === 'publishing'
                                        }"
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                        x-text="post.status">
                                    </span>

                                    <!-- Post Type -->
                                    <span class="text-xs text-gray-500" x-text="post.post_type"></span>
                                </div>

                                <!-- Content Preview -->
                                <p class="text-gray-700 mb-3 line-clamp-3" x-text="post.content"></p>

                                <!-- Media Preview -->
                                <template x-if="post.media && post.media.length > 0">
                                    <div class="flex gap-2 mb-3">
                                        <template x-for="(media, index) in post.media.slice(0, 4)" :key="index">
                                            <div class="relative w-20 h-20 rounded overflow-hidden bg-gray-100">
                                                <template x-if="media.type === 'image'">
                                                    <img :src="media.url" class="w-full h-full object-cover" alt="Media">
                                                </template>
                                                <template x-if="media.type === 'video'">
                                                    <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                                        <i class="fas fa-play text-gray-600"></i>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="post.media.length > 4">
                                            <div class="w-20 h-20 rounded bg-gray-100 flex items-center justify-center text-gray-600 font-semibold">
                                                +<span x-text="post.media.length - 4"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Meta Info -->
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    <template x-if="post.scheduled_at">
                                        <span><i class="far fa-clock me-1"></i> {{ __('social.status.scheduled') }}: <span x-text="formatDate(post.scheduled_at)"></span></span>
                                    </template>
                                    <template x-if="post.published_at">
                                        <span><i class="far fa-check-circle me-1"></i> {{ __('social.status.published') }}: <span x-text="formatDate(post.published_at)"></span></span>
                                    </template>
                                    <template x-if="post.permalink">
                                        <a :href="post.permalink" target="_blank" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-external-link-alt me-1"></i> {{ __('social.view_post') }}
                                        </a>
                                    </template>
                                </div>

                                <!-- Error Message -->
                                <template x-if="post.status === 'failed' && post.error_message">
                                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <span x-text="post.error_message"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Actions -->
                            <div class="ms-4 flex gap-2">
                                <template x-if="post.status === 'draft' || post.status === 'scheduled'">
                                    <button @click="publishPost(post.id)" class="p-2 text-green-600 hover:bg-green-50 rounded" title="{{ __('social.publish_now') }}">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </template>
                                <button @click="deletePost(post.id)" class="p-2 text-red-600 hover:bg-red-50 rounded" title="{{ __('social.delete') }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Create Post Modal -->
    @include('social.posts.components.create-modal')
</div>

@push('scripts')
<script>
// Translations for JavaScript
const socialTranslations = {
    post_created_success: '{{ __('social.post_created_success') }}',
    post_create_failed: '{{ __('social.post_create_failed') }}',
    error_creating_post: '{{ __('social.error_creating_post') }}',
    publish_confirm: '{{ __('social.publish_confirm') }}',
    publish_success: '{{ __('social.publish_success') }}',
    publish_failed: '{{ __('social.publish_failed') }}',
    error_publishing_post: '{{ __('social.error_publishing_post') }}',
    delete_confirm: '{{ __('social.delete_confirm') }}',
    delete_success: '{{ __('social.delete_success') }}',
    delete_failed: '{{ __('social.delete_failed') }}',
    error_deleting_post: '{{ __('social.error_deleting_post') }}',
};

// Platform configurations
const platformConfigs = @json(config('social-platforms'));
</script>

<script type="module">
import { socialPostManager } from '/resources/js/components/social/social-post-manager.js';
window.socialPostManager = socialPostManager;
</script>
@endpush

<style>
[x-cloak] { display: none !important; }
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
