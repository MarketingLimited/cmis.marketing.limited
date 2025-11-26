@extends('layouts.admin')
@section('title', 'Social Posts')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="socialPostManager()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Social Media Publishing</h1>
        <button @click="showCreateModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="fas fa-plus"></i>
            Create Post
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
                    All Posts
                </button>
                <button
                    @click="filterStatus = 'draft'"
                    :class="filterStatus === 'draft' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    Drafts
                </button>
                <button
                    @click="filterStatus = 'scheduled'"
                    :class="filterStatus === 'scheduled' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    Scheduled
                </button>
                <button
                    @click="filterStatus = 'published'"
                    :class="filterStatus === 'published' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    Published
                </button>
                <button
                    @click="filterStatus = 'failed'"
                    :class="filterStatus === 'failed' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-3 border-b-2 font-medium text-sm">
                    Failed
                </button>
            </nav>
        </div>
    </div>

    <!-- Posts List -->
    <div class="bg-white rounded-lg shadow">
        <template x-if="loading">
            <div class="p-8 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">Loading posts...</p>
            </div>
        </template>

        <template x-if="!loading && filteredPosts.length === 0">
            <div class="p-8 text-center">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">No posts found</p>
                <button @click="showCreateModal = true" class="mt-4 text-blue-600 hover:text-blue-800">
                    Create your first post
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
                                    <span class="font-medium text-gray-900" x-text="post.account_username || 'Unknown Account'"></span>

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
                                        <span><i class="far fa-clock mr-1"></i> Scheduled: <span x-text="formatDate(post.scheduled_at)"></span></span>
                                    </template>
                                    <template x-if="post.published_at">
                                        <span><i class="far fa-check-circle mr-1"></i> Published: <span x-text="formatDate(post.published_at)"></span></span>
                                    </template>
                                    <template x-if="post.permalink">
                                        <a :href="post.permalink" target="_blank" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-external-link-alt mr-1"></i> View Post
                                        </a>
                                    </template>
                                </div>

                                <!-- Error Message -->
                                <template x-if="post.status === 'failed' && post.error_message">
                                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <span x-text="post.error_message"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Actions -->
                            <div class="ml-4 flex gap-2">
                                <template x-if="post.status === 'draft' || post.status === 'scheduled'">
                                    <button @click="publishPost(post.id)" class="p-2 text-green-600 hover:bg-green-50 rounded" title="Publish Now">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </template>
                                <button @click="deletePost(post.id)" class="p-2 text-red-600 hover:bg-red-50 rounded" title="Delete">
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
    <div x-show="showCreateModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showCreateModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showCreateModal = false"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form @submit.prevent="createPost">
                    <div class="bg-white px-6 pt-5 pb-4">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900">Create Social Post</h3>
                            <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <!-- Platform Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Select Platforms</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                <template x-for="platform in availablePlatforms" :key="platform.key">
                                    <div
                                        @click="togglePlatform(platform.key)"
                                        :class="selectedPlatforms.includes(platform.key) ? 'border-blue-600 bg-blue-50' : 'border-gray-300 hover:border-gray-400'"
                                        class="border-2 rounded-lg p-4 cursor-pointer transition">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" :checked="selectedPlatforms.includes(platform.key)" class="pointer-events-none">
                                            <span class="text-2xl" x-html="platform.icon"></span>
                                            <div class="flex-1">
                                                <div class="font-medium text-sm" x-text="platform.name"></div>
                                                <div class="text-xs text-gray-500" x-show="platform.accounts && platform.accounts.length > 0">
                                                    <span x-text="platform.accounts.length"></span> connected
                                                </div>
                                                <div class="text-xs text-orange-600" x-show="!platform.accounts || platform.accounts.length === 0">
                                                    Not connected
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Platform-Specific Accounts -->
                        <template x-if="selectedPlatforms.length > 0">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Select Accounts</label>
                                <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                    <template x-for="platform in selectedPlatformsData" :key="platform.key">
                                        <div class="mb-4">
                                            <div class="font-medium text-sm text-gray-700 mb-2 flex items-center gap-2">
                                                <span x-html="platform.icon"></span>
                                                <span x-text="platform.name"></span>
                                            </div>
                                            <template x-if="platform.accounts && platform.accounts.length > 0">
                                                <div class="space-y-2 ml-6">
                                                    <template x-for="account in platform.accounts" :key="account.id">
                                                        <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                                            <input
                                                                type="checkbox"
                                                                :value="account.id"
                                                                @change="toggleAccount(platform.key, account)"
                                                                :checked="isAccountSelected(account.id)"
                                                                class="rounded text-blue-600">
                                                            <template x-if="account.picture">
                                                                <img :src="account.picture" class="w-6 h-6 rounded-full" alt="">
                                                            </template>
                                                            <span class="text-sm" x-text="account.name || account.username"></span>
                                                            <template x-if="account.followers">
                                                                <span class="text-xs text-gray-500" x-text="account.followers + ' followers'"></span>
                                                            </template>
                                                        </label>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!platform.accounts || platform.accounts.length === 0">
                                                <div class="ml-6 text-sm text-orange-600">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Please connect your <span x-text="platform.name"></span> account in
                                                    <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg->org_id) }}" class="underline">Platform Connections</a>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Post Type Selection -->
                        <template x-if="selectedPlatforms.length > 0">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Post Type</label>
                                <select x-model="postData.post_type" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                                    <option value="feed">Feed Post</option>
                                    <option value="story">Story</option>
                                    <option value="reel">Reel / Short Video</option>
                                    <option value="carousel">Carousel</option>
                                    <option value="article">Article</option>
                                    <option value="poll">Poll</option>
                                </select>
                            </div>
                        </template>

                        <!-- Content -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Post Content
                                <span x-show="postData.content.length > 0" class="text-gray-500 font-normal">
                                    (<span x-text="postData.content.length"></span><span x-show="characterLimit > 0" x-text="'/' + characterLimit"></span> characters)
                                </span>
                            </label>
                            <textarea
                                x-model="postData.content"
                                rows="6"
                                :maxlength="characterLimit > 0 ? characterLimit : undefined"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2"
                                placeholder="What would you like to share?"></textarea>
                            <template x-if="characterLimit > 0 && postData.content.length > characterLimit * 0.9">
                                <p class="text-xs text-orange-600 mt-1">
                                    <span x-text="characterLimit - postData.content.length"></span> characters remaining
                                </p>
                            </template>
                            <p class="text-xs text-gray-500 mt-1">Pro tip: Use emojis, hashtags, and @mentions to increase engagement!</p>
                        </div>

                        <!-- Media Upload -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Media (Optional)</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition">
                                <input
                                    type="file"
                                    @change="handleFileUpload($event)"
                                    multiple
                                    accept="image/*,video/*"
                                    class="hidden"
                                    x-ref="fileInput">
                                <button
                                    type="button"
                                    @click="$refs.fileInput.click()"
                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="fas fa-cloud-upload-alt text-3xl mb-2"></i>
                                    <p>Click to upload images or videos</p>
                                </button>
                                <p class="text-xs text-gray-500 mt-2">JPG, PNG, GIF, MP4, MOV (Max 50MB per file)</p>
                            </div>

                            <!-- File Preview -->
                            <template x-if="postData.files.length > 0">
                                <div class="mt-4 grid grid-cols-4 gap-3">
                                    <template x-for="(file, index) in postData.files" :key="index">
                                        <div class="relative group">
                                            <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                                <template x-if="file.type.startsWith('image/')">
                                                    <img :src="getFilePreview(file)" class="w-full h-full object-cover" alt="Preview">
                                                </template>
                                                <template x-if="file.type.startsWith('video/')">
                                                    <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                                        <i class="fas fa-video text-gray-600 text-2xl"></i>
                                                    </div>
                                                </template>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeFile(index)"
                                                class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Publishing Options -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Publishing Options</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div
                                    @click="postData.publish_type = 'now'"
                                    :class="postData.publish_type === 'now' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'"
                                    class="border-2 rounded-lg p-4 cursor-pointer text-center transition">
                                    <i class="fas fa-bolt text-2xl mb-2" :class="postData.publish_type === 'now' ? 'text-blue-600' : 'text-gray-400'"></i>
                                    <p class="font-medium text-sm">Publish Now</p>
                                </div>
                                <div
                                    @click="postData.publish_type = 'scheduled'"
                                    :class="postData.publish_type === 'scheduled' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'"
                                    class="border-2 rounded-lg p-4 cursor-pointer text-center transition">
                                    <i class="far fa-clock text-2xl mb-2" :class="postData.publish_type === 'scheduled' ? 'text-blue-600' : 'text-gray-400'"></i>
                                    <p class="font-medium text-sm">Schedule</p>
                                </div>
                                <div
                                    @click="postData.publish_type = 'queue'"
                                    :class="postData.publish_type === 'queue' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'"
                                    class="border-2 rounded-lg p-4 cursor-pointer text-center transition">
                                    <i class="fas fa-stream text-2xl mb-2" :class="postData.publish_type === 'queue' ? 'text-blue-600' : 'text-gray-400'"></i>
                                    <p class="font-medium text-sm">Add to Queue</p>
                                </div>
                                <div
                                    @click="postData.publish_type = 'draft'"
                                    :class="postData.publish_type === 'draft' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'"
                                    class="border-2 rounded-lg p-4 cursor-pointer text-center transition">
                                    <i class="far fa-save text-2xl mb-2" :class="postData.publish_type === 'draft' ? 'text-blue-600' : 'text-gray-400'"></i>
                                    <p class="font-medium text-sm">Save Draft</p>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule DateTime -->
                        <template x-if="postData.publish_type === 'scheduled'">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Date & Time</label>
                                <input
                                    type="datetime-local"
                                    x-model="postData.scheduled_at"
                                    :min="minDateTime"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                        </template>
                    </div>

                    <!-- Footer Actions -->
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="!canPublish || submitting"
                            :class="canPublish && !submitting ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
                            class="px-6 py-2 text-white rounded-lg font-medium">
                            <template x-if="submitting">
                                <span><i class="fas fa-spinner fa-spin mr-2"></i> Publishing...</span>
                            </template>
                            <template x-if="!submitting">
                                <span x-text="postData.publish_type === 'now' ? 'Publish Now' : (postData.publish_type === 'scheduled' ? 'Schedule Post' : (postData.publish_type === 'queue' ? 'Add to Queue' : 'Save Draft'))"></span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function socialPostManager() {
    return {
        // State
        loading: false,
        submitting: false,
        showCreateModal: false,
        filterStatus: 'all',
        posts: [],
        selectedPlatforms: [],
        selectedAccounts: [],

        // Platform configurations from config/social-platforms.php
        platformConfigs: @json(config('social-platforms')),

        // Available platforms with connection status
        availablePlatforms: [],

        // Post data
        postData: {
            content: '',
            publish_type: 'now',
            scheduled_at: '',
            post_type: 'feed',
            files: []
        },

        // Initialize
        init() {
            this.minDateTime = new Date().toISOString().slice(0, 16);
            this.loadAvailablePlatforms();
            this.loadPosts();
        },

        // Load available platforms with connection status
        async loadAvailablePlatforms() {
            const platformsList = [
                { key: 'facebook', name: 'Facebook', icon: '<i class="fab fa-facebook text-blue-600"></i>' },
                { key: 'instagram', name: 'Instagram', icon: '<i class="fab fa-instagram text-pink-600"></i>' },
                { key: 'threads', name: 'Threads', icon: '<i class="fab fa-at text-purple-600"></i>' },
                { key: 'youtube', name: 'YouTube', icon: '<i class="fab fa-youtube text-red-600"></i>' },
                { key: 'linkedin', name: 'LinkedIn', icon: '<i class="fab fa-linkedin text-blue-700"></i>' },
                { key: 'twitter', name: 'X (Twitter)', icon: '<i class="fab fa-twitter text-sky-600"></i>' },
                { key: 'pinterest', name: 'Pinterest', icon: '<i class="fab fa-pinterest text-red-700"></i>' },
                { key: 'tiktok', name: 'TikTok', icon: '<i class="fab fa-tiktok text-gray-900"></i>' },
                { key: 'tumblr', name: 'Tumblr', icon: '<i class="fab fa-tumblr text-indigo-600"></i>' },
                { key: 'reddit', name: 'Reddit', icon: '<i class="fab fa-reddit text-orange-600"></i>' },
                { key: 'google_business', name: 'Google Business', icon: '<i class="fab fa-google text-blue-600"></i>' },
            ];

            // For now, only Meta (Facebook/Instagram) has connected accounts
            // Other platforms will be implemented in next phase
            try {
                const response = await fetch('/api/orgs/{{ $currentOrg->org_id }}/social/accounts');
                const data = await response.json();

                if (data.success && data.data.accounts) {
                    const metaAccounts = data.data.accounts;

                    // Separate Facebook and Instagram accounts
                    const fbAccounts = metaAccounts.filter(a => a.type === 'facebook');
                    const igAccounts = metaAccounts.filter(a => a.type === 'instagram');

                    this.availablePlatforms = platformsList.map(p => {
                        if (p.key === 'facebook') {
                            return { ...p, accounts: fbAccounts };
                        } else if (p.key === 'instagram') {
                            return { ...p, accounts: igAccounts };
                        }
                        return { ...p, accounts: [] };
                    });
                } else {
                    this.availablePlatforms = platformsList.map(p => ({ ...p, accounts: [] }));
                }
            } catch (error) {
                console.error('Failed to load platforms:', error);
                this.availablePlatforms = platformsList.map(p => ({ ...p, accounts: [] }));
            }
        },

        // Load posts
        async loadPosts() {
            this.loading = true;
            try {
                const response = await fetch('/api/orgs/{{ $currentOrg->org_id }}/social/posts');
                const data = await response.json();
                if (data.success) {
                    this.posts = data.data.data || data.data || [];
                }
            } catch (error) {
                console.error('Failed to load posts:', error);
            } finally {
                this.loading = false;
            }
        },

        // Filtered posts based on status
        get filteredPosts() {
            if (this.filterStatus === 'all') {
                return this.posts;
            }
            return this.posts.filter(p => p.status === this.filterStatus);
        },

        // Toggle platform selection
        togglePlatform(platformKey) {
            const index = this.selectedPlatforms.indexOf(platformKey);
            if (index > -1) {
                this.selectedPlatforms.splice(index, 1);
                // Remove all accounts from this platform
                this.selectedAccounts = this.selectedAccounts.filter(a => !a.id.startsWith(platformKey + '_'));
            } else {
                this.selectedPlatforms.push(platformKey);
            }
        },

        // Get selected platforms data
        get selectedPlatformsData() {
            return this.availablePlatforms.filter(p => this.selectedPlatforms.includes(p.key));
        },

        // Toggle account selection
        toggleAccount(platformKey, account) {
            const fullAccount = { ...account, type: platformKey };
            const index = this.selectedAccounts.findIndex(a => a.id === account.id);
            if (index > -1) {
                this.selectedAccounts.splice(index, 1);
            } else {
                this.selectedAccounts.push(fullAccount);
            }
        },

        // Check if account is selected
        isAccountSelected(accountId) {
            return this.selectedAccounts.some(a => a.id === accountId);
        },

        // Get character limit based on selected platforms
        get characterLimit() {
            if (this.selectedPlatforms.length === 0) return 5000;

            const limits = {
                'twitter': 280,
                'threads': 500,
                'facebook': 63206,
                'instagram': 2200,
                'linkedin': 3000,
                'pinterest': 500,
                'tiktok': 2200,
                'youtube': 5000,
                'tumblr': 4096,
                'reddit': 40000,
                'google_business': 1500
            };

            // Return the smallest limit if multiple platforms selected
            const selectedLimits = this.selectedPlatforms.map(p => limits[p] || 5000);
            return Math.min(...selectedLimits);
        },

        // Can publish validation
        get canPublish() {
            return this.postData.content.trim().length > 0 &&
                   this.selectedAccounts.length > 0 &&
                   (this.postData.publish_type !== 'scheduled' || this.postData.scheduled_at);
        },

        // Handle file upload
        handleFileUpload(event) {
            const files = Array.from(event.target.files);
            this.postData.files = [...this.postData.files, ...files];
        },

        // Get file preview URL
        getFilePreview(file) {
            return URL.createObjectURL(file);
        },

        // Remove file
        removeFile(index) {
            this.postData.files.splice(index, 1);
        },

        // Create post
        async createPost() {
            if (!this.canPublish || this.submitting) return;

            this.submitting = true;

            try {
                const formData = new FormData();
                formData.append('content', this.postData.content);
                formData.append('publish_type', this.postData.publish_type);
                formData.append('post_type', this.postData.post_type);

                if (this.postData.scheduled_at) {
                    formData.append('scheduled_at', this.postData.scheduled_at);
                }

                // Append selected platforms and accounts
                const platformsData = this.selectedAccounts.map(account => ({
                    type: account.type,
                    platformId: account.platformId,
                    name: account.name,
                    integrationId: account.integrationId,
                    connectionId: account.connectionId,
                }));
                formData.append('platforms', JSON.stringify(platformsData));

                // Append media files
                this.postData.files.forEach((file, index) => {
                    formData.append(`media[${index}]`, file);
                });

                const response = await fetch('/api/orgs/{{ $currentOrg->org_id }}/social/posts', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message || 'Post created successfully!');
                    this.showCreateModal = false;
                    this.resetForm();
                    this.loadPosts();
                } else {
                    alert(data.message || 'Failed to create post');
                }
            } catch (error) {
                console.error('Error creating post:', error);
                alert('An error occurred while creating the post');
            } finally {
                this.submitting = false;
            }
        },

        // Reset form
        resetForm() {
            this.postData = {
                content: '',
                publish_type: 'now',
                scheduled_at: '',
                post_type: 'feed',
                files: []
            };
            this.selectedPlatforms = [];
            this.selectedAccounts = [];
        },

        // Publish a post immediately
        async publishPost(postId) {
            if (!confirm('Are you sure you want to publish this post now?')) return;

            try {
                const response = await fetch(`/api/orgs/{{ $currentOrg->org_id }}/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('Post published successfully!');
                    this.loadPosts();
                } else {
                    alert(data.message || 'Failed to publish post');
                }
            } catch (error) {
                console.error('Error publishing post:', error);
                alert('An error occurred while publishing the post');
            }
        },

        // Delete a post
        async deletePost(postId) {
            if (!confirm('Are you sure you want to delete this post?')) return;

            try {
                const response = await fetch(`/api/orgs/{{ $currentOrg->org_id }}/social/posts/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('Post deleted successfully!');
                    this.loadPosts();
                } else {
                    alert(data.message || 'Failed to delete post');
                }
            } catch (error) {
                console.error('Error deleting post:', error);
                alert('An error occurred while deleting the post');
            }
        },

        // Get platform icon
        getPlatformIcon(platform) {
            const icons = {
                'facebook': '<i class="fab fa-facebook text-blue-600"></i>',
                'instagram': '<i class="fab fa-instagram text-pink-600"></i>',
                'threads': '<i class="fab fa-at text-purple-600"></i>',
                'youtube': '<i class="fab fa-youtube text-red-600"></i>',
                'linkedin': '<i class="fab fa-linkedin text-blue-700"></i>',
                'twitter': '<i class="fab fa-twitter text-sky-600"></i>',
                'pinterest': '<i class="fab fa-pinterest text-red-700"></i>',
                'tiktok': '<i class="fab fa-tiktok text-gray-900"></i>',
                'tumblr': '<i class="fab fa-tumblr text-indigo-600"></i>',
                'reddit': '<i class="fab fa-reddit text-orange-600"></i>',
                'google_business': '<i class="fab fa-google text-blue-600"></i>',
            };
            return icons[platform] || '<i class="fas fa-globe"></i>';
        },

        // Format date
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>

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
