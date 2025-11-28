@extends('layouts.admin')

@section('title', __('social.schedule_posts'))

@section('content')
<div x-data="socialScheduler()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('social.schedule_posts') }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ __('social.schedule_description') }}</p>
        </div>
        <button @click="showCreateModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg flex items-center gap-2">
            <i class="fas fa-plus"></i>
            {{ __('social.new_post') }}
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('social.scheduled_count') }}</p>
                    <p class="text-3xl font-bold text-blue-600" x-text="scheduledPosts.length">0</p>
                </div>
                <i class="fas fa-clock text-3xl text-blue-600"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('social.published_today') }}</p>
                    <p class="text-3xl font-bold text-green-600" x-text="publishedToday">0</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-600"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('social.drafts') }}</p>
                    <p class="text-3xl font-bold text-yellow-600" x-text="drafts">0</p>
                </div>
                <i class="fas fa-file-alt text-3xl text-yellow-600"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('social.connected_platforms') }}</p>
                    <p class="text-3xl font-bold text-purple-600" x-text="connectedPlatforms">0</p>
                </div>
                <i class="fas fa-link text-3xl text-purple-600"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Calendar View -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('social.publish_calendar') }}</h3>
            <div class="grid grid-cols-7 gap-2 mb-4">
                <template x-for="day in ['{{ __('social.days.sunday') }}', '{{ __('social.days.monday') }}', '{{ __('social.days.tuesday') }}', '{{ __('social.days.wednesday') }}', '{{ __('social.days.thursday') }}', '{{ __('social.days.friday') }}', '{{ __('social.days.saturday') }}']">
                    <div class="text-center text-sm font-semibold text-gray-600 dark:text-gray-400 py-2" x-text="day"></div>
                </template>
            </div>
            <div class="grid grid-cols-7 gap-2">
                <template x-for="date in calendarDates" :key="date.day">
                    <div class="min-h-24 border border-gray-200 dark:border-gray-700 rounded p-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                         :class="{'bg-blue-50 dark:bg-blue-900/20': date.hasPost, 'opacity-50': !date.currentMonth}"
                         @click="selectDate(date)">
                        <div class="text-sm font-medium" x-text="date.day"></div>
                        <template x-if="date.posts && date.posts.length > 0">
                            <div class="mt-1">
                                <template x-for="post in date.posts.slice(0, 2)">
                                    <div class="text-xs bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100 rounded px-1 py-0.5 mb-1 truncate" x-text="post.title"></div>
                                </template>
                                <template x-if="date.posts.length > 2">
                                    <div class="text-xs text-gray-500" x-text="'+' + (date.posts.length - 2) + ' {{ __('social.more') }}'"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Upcoming Posts -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('social.upcoming_posts') }}</h3>
            <div class="space-y-4 max-h-96 overflow-y-auto">
                <template x-for="post in scheduledPosts" :key="post.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs px-2 py-1 rounded-full"
                                  :class="getPlatformClass(post.platform)"
                                  x-text="post.platform"></span>
                            <button @click="deletePost(post.id)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                        <p class="text-sm text-gray-900 dark:text-white mb-2 line-clamp-2" x-text="post.content"></p>
                        <div class="flex items-center text-xs text-gray-500">
                            <i class="fas fa-calendar ml-1"></i>
                            <span x-text="formatDate(post.scheduled_at)"></span>
                        </div>
                    </div>
                </template>
                <template x-if="scheduledPosts.length === 0">
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-calendar-times text-4xl mb-2"></i>
                        <p>{{ __('social.no_scheduled_posts') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Create Post Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl mx-4">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('social.create_new_post') }}</h3>
            </div>
            <div class="p-6 space-y-4">
                <!-- Platforms -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('social.platforms') }}</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="platform in availablePlatforms">
                            <button type="button"
                                    @click="togglePlatform(platform.id)"
                                    class="px-4 py-2 rounded-lg border-2 flex items-center gap-2"
                                    :class="selectedPlatforms.includes(platform.id) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'">
                                <i :class="platform.icon"></i>
                                <span x-text="platform.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('social.content') }}</label>
                    <textarea x-model="newPost.content" rows="4"
                              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-3"
                              placeholder="{{ __('social.write_content_here') }}"></textarea>
                    <div class="text-xs text-gray-500 mt-1">
                        <span x-text="newPost.content.length"></span> / 280 {{ __('social.characters') }}
                    </div>
                </div>

                <!-- Media -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('social.media') }}</label>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                        <p class="text-gray-600 dark:text-gray-400">{{ __('social.drag_files') }}</p>
                        <input type="file" multiple class="hidden" x-ref="fileInput" @change="handleFileUpload">
                        <button type="button" @click="$refs.fileInput.click()" class="mt-2 text-blue-600 hover:text-blue-800">
                            {{ __('social.choose_files') }}
                        </button>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('social.date') }}</label>
                        <input type="date" x-model="newPost.date"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('social.time') }}</label>
                        <input type="time" x-model="newPost.time"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-3">
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button @click="showCreateModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    {{ __('social.cancel') }}
                </button>
                <button @click="saveDraft()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                    {{ __('social.save_draft') }}
                </button>
                <button @click="schedulePost()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    {{ __('social.schedule_post') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function socialScheduler() {
    return {
        showCreateModal: false,
        scheduledPosts: [],
        publishedToday: 0,
        drafts: 3,
        connectedPlatforms: 4,
        calendarDates: [],
        selectedPlatforms: [],
        availablePlatforms: [
            { id: 'facebook', name: 'فيسبوك', icon: 'fab fa-facebook text-blue-600' },
            { id: 'instagram', name: 'انستغرام', icon: 'fab fa-instagram text-pink-600' },
            { id: 'twitter', name: 'تويتر', icon: 'fab fa-twitter text-sky-500' },
            { id: 'linkedin', name: 'لينكدإن', icon: 'fab fa-linkedin text-blue-700' },
            { id: 'tiktok', name: 'تيك توك', icon: 'fab fa-tiktok text-black dark:text-white' }
        ],
        newPost: {
            content: '',
            date: '',
            time: '',
            media: []
        },

        init() {
            this.generateCalendar();
            this.loadScheduledPosts();

            // Set default date/time to tomorrow 10:00 AM
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.date = tomorrow.toISOString().split('T')[0];
            this.newPost.time = '10:00';
        },

        generateCalendar() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

            const dates = [];

            // Add previous month days
            const startDay = firstDay.getDay();
            for (let i = startDay; i > 0; i--) {
                const date = new Date(firstDay);
                date.setDate(date.getDate() - i);
                dates.push({
                    day: date.getDate(),
                    currentMonth: false,
                    hasPost: false,
                    posts: []
                });
            }

            // Add current month days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                dates.push({
                    day: i,
                    currentMonth: true,
                    hasPost: Math.random() > 0.7,
                    posts: Math.random() > 0.7 ? [{ title: 'منشور مجدول' }] : []
                });
            }

            // Fill remaining days
            const remaining = 42 - dates.length;
            for (let i = 1; i <= remaining; i++) {
                dates.push({
                    day: i,
                    currentMonth: false,
                    hasPost: false,
                    posts: []
                });
            }

            this.calendarDates = dates;
        },

        loadScheduledPosts() {
            // Demo data - in production, fetch from API
            this.scheduledPosts = [
                {
                    id: 1,
                    platform: 'فيسبوك',
                    content: 'مرحباً بكم في منصتنا الجديدة! اكتشفوا أحدث العروض والخدمات المتميزة.',
                    scheduled_at: new Date(Date.now() + 86400000).toISOString()
                },
                {
                    id: 2,
                    platform: 'انستغرام',
                    content: 'صورة جديدة من وراء الكواليس لفريق العمل المتميز!',
                    scheduled_at: new Date(Date.now() + 172800000).toISOString()
                },
                {
                    id: 3,
                    platform: 'تويتر',
                    content: 'تحديث مهم: إطلاق ميزات جديدة قريباً! ترقبونا',
                    scheduled_at: new Date(Date.now() + 259200000).toISOString()
                }
            ];
        },

        togglePlatform(platformId) {
            const index = this.selectedPlatforms.indexOf(platformId);
            if (index === -1) {
                this.selectedPlatforms.push(platformId);
            } else {
                this.selectedPlatforms.splice(index, 1);
            }
        },

        getPlatformClass(platform) {
            const classes = {
                'فيسبوك': 'bg-blue-100 text-blue-800',
                'انستغرام': 'bg-pink-100 text-pink-800',
                'تويتر': 'bg-sky-100 text-sky-800',
                'لينكدإن': 'bg-blue-100 text-blue-800',
                'تيك توك': 'bg-gray-100 text-gray-800'
            };
            return classes[platform] || 'bg-gray-100 text-gray-800';
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        selectDate(date) {
            if (date.currentMonth) {
                const selectedDate = new Date();
                selectedDate.setDate(date.day);
                this.newPost.date = selectedDate.toISOString().split('T')[0];
                this.showCreateModal = true;
            }
        },

        handleFileUpload(event) {
            const files = event.target.files;
            // Handle file upload logic
            if (window.notify) {
                window.notify(`تم اختيار ${files.length} ملف`, 'info');
            }
        },

        async schedulePost() {
            if (!this.newPost.content.trim()) {
                if (window.notify) window.notify('الرجاء كتابة محتوى المنشور', 'warning');
                return;
            }
            if (this.selectedPlatforms.length === 0) {
                if (window.notify) window.notify('الرجاء اختيار منصة واحدة على الأقل', 'warning');
                return;
            }

            try {
                // In production, call the API
                // const response = await fetch('/api/orgs/{org_id}/social/posts/schedule', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                //     },
                //     body: JSON.stringify({
                //         content: this.newPost.content,
                //         platforms: this.selectedPlatforms,
                //         scheduled_at: `${this.newPost.date}T${this.newPost.time}:00`
                //     })
                // });

                // Demo: Add to local list
                const platformName = this.availablePlatforms.find(p => p.id === this.selectedPlatforms[0])?.name || 'منصة';
                this.scheduledPosts.unshift({
                    id: Date.now(),
                    platform: platformName,
                    content: this.newPost.content,
                    scheduled_at: `${this.newPost.date}T${this.newPost.time}:00`
                });

                if (window.notify) window.notify('تم جدولة المنشور بنجاح', 'success');
                this.showCreateModal = false;
                this.resetForm();
            } catch (error) {
                console.error('Error scheduling post:', error);
                if (window.notify) window.notify('فشل جدولة المنشور', 'error');
            }
        },

        saveDraft() {
            if (window.notify) window.notify('تم حفظ المسودة', 'success');
            this.showCreateModal = false;
            this.drafts++;
        },

        deletePost(postId) {
            if (!confirm('هل أنت متأكد من حذف هذا المنشور المجدول؟')) return;
            this.scheduledPosts = this.scheduledPosts.filter(p => p.id !== postId);
            if (window.notify) window.notify('تم حذف المنشور', 'success');
        },

        resetForm() {
            this.newPost = {
                content: '',
                date: '',
                time: '',
                media: []
            };
            this.selectedPlatforms = [];

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.date = tomorrow.toISOString().split('T')[0];
            this.newPost.time = '10:00';
        }
    };
}
</script>
@endpush
