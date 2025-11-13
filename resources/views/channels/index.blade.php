@extends('layouts.admin')

@section('title', 'جدولة السوشيال ميديا')

@section('content')
<div x-data="socialSchedulerManager()" x-init="init()">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">جدولة السوشيال ميديا</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">إدارة وجدولة المنشورات عبر جميع المنصات</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <x-ui.button @click="showComposerModal = true" variant="primary" icon="fas fa-plus">
                منشور جديد
            </x-ui.button>
            <x-ui.button @click="activeView = 'calendar'" variant="secondary" icon="fas fa-calendar">
                عرض التقويم
            </x-ui.button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">منشورات مجدولة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.scheduled"></p>
                </div>
                <i class="fas fa-calendar-check text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-clock ml-1"></i>
                <span x-text="stats.nextPost"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">منشورة اليوم</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.publishedToday"></p>
                </div>
                <i class="fas fa-check-double text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-arrow-up ml-1"></i>
                <span x-text="stats.engagementChange + '% تفاعل'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">مسودات</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.drafts"></p>
                </div>
                <i class="fas fa-file-alt text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-edit ml-1"></i>
                <span x-text="stats.recentDrafts + ' حديثة'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">منصات نشطة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.activePlatforms"></p>
                </div>
                <i class="fas fa-share-alt text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-link ml-1"></i>
                <span x-text="stats.totalPlatforms + ' متصلة'"></span>
            </div>
        </div>
    </div>

    <!-- View Toggle & Filters -->
    <x-ui.card class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- View Tabs -->
            <div class="flex space-x-2 space-x-reverse border-b border-gray-200 dark:border-gray-700">
                <button @click="activeView = 'queue'"
                        :class="activeView === 'queue' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-list ml-1"></i> قائمة الانتظار
                </button>
                <button @click="activeView = 'calendar'"
                        :class="activeView === 'calendar' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-calendar ml-1"></i> التقويم
                </button>
                <button @click="activeView = 'published'"
                        :class="activeView === 'published' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-history ml-1"></i> منشورة
                </button>
                <button @click="activeView = 'drafts'"
                        :class="activeView === 'drafts' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-file-alt ml-1"></i> مسودات
                </button>
            </div>

            <!-- Platform Filter -->
            <div class="flex items-center space-x-3 space-x-reverse">
                <template x-for="platform in platforms" :key="platform.id">
                    <button @click="togglePlatform(platform.id)"
                            :class="selectedPlatforms.includes(platform.id) ? 'bg-' + platform.color + '-100 border-' + platform.color + '-500 text-' + platform.color + '-700' : 'bg-gray-100 border-gray-300 text-gray-500'"
                            class="px-3 py-1 border-2 rounded-lg text-sm font-medium transition flex items-center">
                        <i :class="platform.icon + ' ml-1'"></i>
                        <span x-text="platform.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </x-ui.card>

    <!-- Queue View -->
    <div x-show="activeView === 'queue'">
        <x-ui.card title="قائمة المنشورات المجدولة">
            <div class="space-y-3">
                <template x-for="post in scheduledPosts" :key="post.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="flex items-start space-x-4 space-x-reverse">
                            <!-- Post Preview -->
                            <div class="flex-shrink-0 w-20 h-20 bg-gray-200 dark:bg-gray-600 rounded-lg overflow-hidden">
                                <template x-if="post.image">
                                    <img :src="post.image" :alt="post.title" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!post.image">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-align-left text-2xl text-gray-400"></i>
                                    </div>
                                </template>
                            </div>

                            <!-- Post Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white" x-text="post.title"></h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="post.content"></p>
                                    </div>
                                </div>

                                <!-- Post Meta -->
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-500">
                                        <div class="flex items-center space-x-2 space-x-reverse">
                                            <template x-for="platformId in post.platforms" :key="platformId">
                                                <i :class="getPlatformIcon(platformId)" :style="'color: ' + getPlatformColor(platformId)"></i>
                                            </template>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-clock ml-1"></i>
                                            <span x-text="post.scheduledTime"></span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-user ml-1"></i>
                                            <span x-text="post.author"></span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-2 space-x-reverse">
                                        <button @click="previewPost(post.id)" class="text-blue-600 hover:text-blue-700">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button @click="editPost(post.id)" class="text-green-600 hover:text-green-700">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="reschedulePost(post.id)" class="text-amber-600 hover:text-amber-700">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                        <button @click="deletePost(post.id)" class="text-red-600 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <template x-if="scheduledPosts.length === 0">
                    <div class="text-center py-12">
                        <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">لا توجد منشورات مجدولة</p>
                        <x-ui.button @click="showComposerModal = true" variant="primary" class="mt-4">
                            إنشاء منشور جديد
                        </x-ui.button>
                    </div>
                </template>
            </div>
        </x-ui.card>
    </div>

    <!-- Calendar View -->
    <div x-show="activeView === 'calendar'">
        <x-ui.card title="تقويم المنشورات">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <button @click="previousMonth()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="currentMonthYear"></h3>
                    <button @click="nextMonth()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                </div>
                <button @click="goToToday()" class="text-sm text-cyan-600 hover:text-cyan-700 font-semibold">
                    اليوم
                </button>
            </div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-2">
                <!-- Day Headers -->
                <template x-for="day in ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']" :key="day">
                    <div class="text-center font-semibold text-sm text-gray-600 dark:text-gray-400 py-2" x-text="day"></div>
                </template>

                <!-- Calendar Days -->
                <template x-for="day in calendarDays" :key="day.date">
                    <div :class="day.isToday ? 'bg-cyan-50 dark:bg-cyan-900/20 border-cyan-500' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'"
                         class="border rounded-lg p-2 min-h-24 hover:shadow-md transition cursor-pointer"
                         @click="selectDate(day.date)">
                        <div class="text-right">
                            <span :class="day.isToday ? 'bg-cyan-600 text-white' : 'text-gray-700 dark:text-gray-300'"
                                  class="text-xs font-semibold px-2 py-1 rounded"
                                  x-text="day.dayNumber"></span>
                        </div>
                        <div class="mt-1 space-y-1">
                            <template x-for="post in getPostsForDate(day.date)" :key="post.id">
                                <div class="text-xs bg-cyan-100 dark:bg-cyan-900 text-cyan-800 dark:text-cyan-200 px-2 py-1 rounded truncate"
                                     x-text="post.title"></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>
    </div>

    <!-- Published View -->
    <div x-show="activeView === 'published'">
        <x-ui.card title="المنشورات المنشورة">
            <div class="space-y-3">
                <template x-for="post in publishedPosts" :key="post.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-start space-x-4 space-x-reverse">
                            <div class="flex-shrink-0 w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg overflow-hidden">
                                <template x-if="post.image">
                                    <img :src="post.image" :alt="post.title" class="w-full h-full object-cover">
                                </template>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white" x-text="post.title"></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-1" x-text="post.content"></p>
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-500">
                                        <span>
                                            <i class="fas fa-calendar ml-1"></i>
                                            <span x-text="post.publishedAt"></span>
                                        </span>
                                        <span>
                                            <i class="fas fa-heart ml-1"></i>
                                            <span x-text="post.likes"></span>
                                        </span>
                                        <span>
                                            <i class="fas fa-comment ml-1"></i>
                                            <span x-text="post.comments"></span>
                                        </span>
                                        <span>
                                            <i class="fas fa-share ml-1"></i>
                                            <span x-text="post.shares"></span>
                                        </span>
                                    </div>
                                    <button @click="viewAnalytics(post.id)" class="text-cyan-600 hover:text-cyan-700 text-sm font-semibold">
                                        عرض الإحصائيات
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>
    </div>

    <!-- Drafts View -->
    <div x-show="activeView === 'drafts'">
        <x-ui.card title="المسودات">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="draft in drafts" :key="draft.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-semibold text-gray-900 dark:text-white" x-text="draft.title || 'مسودة بدون عنوان'"></h4>
                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded" x-text="draft.lastEdited"></span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-3" x-text="draft.content"></p>
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-1 space-x-reverse">
                                <template x-for="platformId in draft.platforms" :key="platformId">
                                    <i :class="getPlatformIcon(platformId)" class="text-sm text-gray-400"></i>
                                </template>
                            </div>
                            <div class="flex space-x-2 space-x-reverse">
                                <button @click="editDraft(draft.id)" class="text-blue-600 hover:text-blue-700">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="deleteDraft(draft.id)" class="text-red-600 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>
    </div>

    <!-- Post Composer Modal -->
    <x-ui.modal name="composerModal" title="إنشاء منشور جديد" max-width="xl" x-show="showComposerModal" @close="showComposerModal = false">
        <div class="space-y-4">
            <!-- Platform Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">اختر المنصات</label>
                <div class="grid grid-cols-3 gap-3">
                    <template x-for="platform in platforms" :key="platform.id">
                        <button @click="toggleComposerPlatform(platform.id)"
                                :class="composerForm.platforms.includes(platform.id) ? 'bg-' + platform.color + '-100 border-' + platform.color + '-500' : 'bg-gray-50 border-gray-300'"
                                class="px-4 py-3 border-2 rounded-lg text-center transition">
                            <i :class="platform.icon + ' text-2xl mb-1'" :style="composerForm.platforms.includes(platform.id) ? 'color: ' + getPlatformColor(platform.id) : ''"></i>
                            <p class="text-xs font-semibold" x-text="platform.name"></p>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Post Content -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">محتوى المنشور</label>
                <textarea x-model="composerForm.content"
                          rows="6"
                          placeholder="اكتب محتوى المنشور هنا..."
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-cyan-500"
                          @input="updateCharCount()"></textarea>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-xs text-gray-500" x-text="composerForm.charCount + ' حرف'"></span>
                    <button @click="addEmoji()" class="text-sm text-cyan-600 hover:text-cyan-700">
                        <i class="fas fa-smile ml-1"></i> إضافة إيموجي
                    </button>
                </div>
            </div>

            <!-- Media Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">إضافة صورة/فيديو</label>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-cyan-500 transition cursor-pointer">
                    <i class="fas fa-image text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600 dark:text-gray-400">اسحب الملف هنا أو انقر للاختيار</p>
                </div>
            </div>

            <!-- Schedule Options -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تاريخ النشر</label>
                    <input type="date"
                           x-model="composerForm.scheduleDate"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-cyan-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">وقت النشر</label>
                    <input type="time"
                           x-model="composerForm.scheduleTime"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-cyan-500">
                </div>
            </div>

            <!-- Quick Schedule Buttons -->
            <div class="flex flex-wrap gap-2">
                <button @click="scheduleNow()" class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded-lg hover:bg-green-200">
                    <i class="fas fa-bolt ml-1"></i> نشر الآن
                </button>
                <button @click="scheduleInHour()" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                    <i class="fas fa-clock ml-1"></i> بعد ساعة
                </button>
                <button @click="scheduleTomorrow()" class="px-3 py-1 text-sm bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200">
                    <i class="fas fa-calendar-day ml-1"></i> غداً
                </button>
            </div>
        </div>

        <div class="mt-6 flex justify-between">
            <x-ui.button @click="saveDraft()" variant="secondary">
                <i class="fas fa-save ml-1"></i> حفظ كمسودة
            </x-ui.button>
            <div class="flex space-x-3 space-x-reverse">
                <x-ui.button @click="showComposerModal = false" variant="secondary">
                    إلغاء
                </x-ui.button>
                <x-ui.button @click="schedulePost()" variant="primary" icon="fas fa-calendar-check">
                    جدولة المنشور
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

</div>
@endsection

@push('scripts')
<script>
function socialSchedulerManager() {
    return {
        activeView: 'queue',
        showComposerModal: false,
        selectedPlatforms: [],
        currentMonthYear: '',
        calendarDays: [],
        stats: {
            scheduled: 0,
            nextPost: '',
            publishedToday: 0,
            engagementChange: 0,
            drafts: 0,
            recentDrafts: 0,
            activePlatforms: 0,
            totalPlatforms: 0
        },
        platforms: [],
        scheduledPosts: [],
        publishedPosts: [],
        drafts: [],
        composerForm: {
            platforms: [],
            content: '',
            charCount: 0,
            scheduleDate: '',
            scheduleTime: ''
        },

        async init() {
            await this.fetchData();
            this.initCalendar();
        },

        async fetchData() {
            try {
                // TODO: Backend Controller Needed - SocialSchedulerController
                // This page requires a new controller to be created for social post scheduling
                // Required API endpoints:
                // - GET /api/social/dashboard - Get stats and scheduled posts overview
                // - GET /api/social/posts/scheduled - Get all scheduled posts
                // - GET /api/social/posts/published - Get published posts with engagement
                // - GET /api/social/posts/drafts - Get draft posts
                // - POST /api/social/posts/schedule - Schedule a new post
                // - PUT /api/social/posts/{id} - Update scheduled/draft post
                // - DELETE /api/social/posts/{id} - Delete post
                // - POST /api/social/posts/{id}/publish-now - Publish immediately

                // Simulated data until backend is implemented
                this.stats = {
                    scheduled: 47,
                    nextPost: 'بعد ساعتين',
                    publishedToday: 12,
                    engagementChange: 18.5,
                    drafts: 8,
                    recentDrafts: 3,
                    activePlatforms: 5,
                    totalPlatforms: 5
                };

                this.platforms = [
                    { id: 'meta', name: 'Meta', icon: 'fab fa-meta', color: 'blue' },
                    { id: 'instagram', name: 'Instagram', icon: 'fab fa-instagram', color: 'pink' },
                    { id: 'twitter', name: 'X', icon: 'fab fa-x-twitter', color: 'gray' },
                    { id: 'linkedin', name: 'LinkedIn', icon: 'fab fa-linkedin', color: 'blue' },
                    { id: 'tiktok', name: 'TikTok', icon: 'fab fa-tiktok', color: 'gray' }
                ];

                this.selectedPlatforms = this.platforms.map(p => p.id);

                this.scheduledPosts = [
                    { id: 1, title: 'عرض الصيف الخاص', content: 'لا تفوت عروضنا الحصرية لموسم الصيف! خصومات تصل إلى 50% على جميع المنتجات.', image: 'https://via.placeholder.com/100', platforms: ['meta', 'instagram'], scheduledTime: 'اليوم 6:00 م', author: 'أحمد محمد' },
                    { id: 2, title: 'إطلاق منتج جديد', content: 'نحن متحمسون للإعلان عن إطلاق منتجنا الثوري الجديد! ابقوا معنا للمزيد.', image: 'https://via.placeholder.com/100', platforms: ['twitter', 'linkedin'], scheduledTime: 'غداً 10:00 ص', author: 'سارة أحمد' },
                    { id: 3, title: 'نصيحة الأسبوع', content: '💡 نصيحة اليوم: استخدم تحليلات البيانات لتحسين استراتيجية التسويق الخاصة بك.', image: null, platforms: ['linkedin'], scheduledTime: 'غداً 2:00 م', author: 'محمد علي' }
                ];

                this.publishedPosts = [
                    { id: 1, title: 'مرحباً بالأسبوع الجديد', content: 'بداية رائعة لأسبوع مليء بالإنجازات! كيف تخطط لتحقيق أهدافك هذا الأسبوع؟', image: 'https://via.placeholder.com/80', publishedAt: 'اليوم 9:00 ص', likes: 234, comments: 45, shares: 67 },
                    { id: 2, title: 'نجاح باهر', content: 'شكراً لكل من شارك في فعاليتنا الأخيرة! كان حدثاً رائعاً.', image: 'https://via.placeholder.com/80', publishedAt: 'أمس 4:00 م', likes: 567, comments: 89, shares: 123 }
                ];

                this.drafts = [
                    { id: 1, title: 'مسودة حملة رمضان', content: 'خطة كاملة لحملة رمضان المبارك مع عروض خاصة...', platforms: ['meta', 'instagram'], lastEdited: 'منذ ساعة' },
                    { id: 2, title: 'إعلان الشراكة', content: 'نحن فخورون بالإعلان عن شراكتنا الجديدة مع...', platforms: ['linkedin'], lastEdited: 'منذ يومين' },
                    { id: 3, title: 'مسابقة العملاء', content: 'شارك واربح! مسابقة حصرية لعملائنا الأوفياء...', platforms: ['meta', 'instagram', 'twitter'], lastEdited: 'منذ 3 أيام' }
                ];

            } catch (error) {
                console.error(error);
                window.notify('فشل تحميل البيانات', 'error');
            }
        },

        initCalendar() {
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth();
            this.currentMonthYear = now.toLocaleDateString('ar-SA', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            this.calendarDays = [];

            // Add empty cells for days before month starts
            for (let i = 0; i < firstDay; i++) {
                this.calendarDays.push({ date: null, dayNumber: '', isToday: false });
            }

            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const isToday = date.toDateString() === now.toDateString();
                this.calendarDays.push({
                    date: date.toISOString().split('T')[0],
                    dayNumber: day,
                    isToday: isToday
                });
            }
        },

        getPostsForDate(date) {
            if (!date) return [];
            // Mock: Return some posts for today and tomorrow
            const today = new Date().toISOString().split('T')[0];
            const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];

            if (date === today) {
                return [{ id: 1, title: 'عرض الصيف' }];
            } else if (date === tomorrow) {
                return [{ id: 2, title: 'إطلاق منتج' }, { id: 3, title: 'نصيحة' }];
            }
            return [];
        },

        getPlatformIcon(platformId) {
            const platform = this.platforms.find(p => p.id === platformId);
            return platform ? platform.icon : 'fas fa-share-alt';
        },

        getPlatformColor(platformId) {
            const colors = {
                'meta': '#0866FF',
                'instagram': '#E4405F',
                'twitter': '#1DA1F2',
                'linkedin': '#0A66C2',
                'tiktok': '#000000'
            };
            return colors[platformId] || '#6B7280';
        },

        togglePlatform(platformId) {
            const index = this.selectedPlatforms.indexOf(platformId);
            if (index > -1) {
                this.selectedPlatforms.splice(index, 1);
            } else {
                this.selectedPlatforms.push(platformId);
            }
        },

        toggleComposerPlatform(platformId) {
            const index = this.composerForm.platforms.indexOf(platformId);
            if (index > -1) {
                this.composerForm.platforms.splice(index, 1);
            } else {
                this.composerForm.platforms.push(platformId);
            }
        },

        updateCharCount() {
            this.composerForm.charCount = this.composerForm.content.length;
        },

        previewPost(id) {
            window.notify('معاينة المنشور #' + id, 'info');
        },

        editPost(id) {
            // TODO: Implement edit functionality
            // Fetch post data: GET /api/social/posts/{id}
            // Open composer modal with loaded data
            // Update: PUT /api/social/posts/{id}
            window.notify('تحرير المنشور #' + id, 'info');
        },

        reschedulePost(id) {
            // TODO: Implement reschedule functionality
            // PUT /api/social/posts/{id}/reschedule
            // Update scheduled_date and scheduled_time
            window.notify('إعادة جدولة المنشور #' + id, 'info');
        },

        async deletePost(id) {
            if (!confirm('هل أنت متأكد من حذف هذا المنشور؟')) return;

            try {
                // TODO: Implement actual API call
                // DELETE /api/social/posts/{id}
                // const response = await fetch(`/api/social/posts/${id}`, {
                //     method: 'DELETE',
                //     headers: {
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     }
                // });
                //
                // if (!response.ok) throw new Error('Failed to delete');

                window.notify('جاري حذف المنشور...', 'info');

                // Remove from local array for now
                this.scheduledPosts = this.scheduledPosts.filter(p => p.id !== id);

                window.notify('تم حذف المنشور', 'success');
            } catch (error) {
                console.error('Error deleting post:', error);
                window.notify('فشل حذف المنشور', 'error');
            }
        },

        editDraft(id) {
            // TODO: Load draft data and populate composer
            // GET /api/social/posts/drafts/{id}
            window.notify('تحرير المسودة #' + id, 'info');
        },

        async deleteDraft(id) {
            if (!confirm('هل أنت متأكد من حذف هذه المسودة؟')) return;

            try {
                // TODO: Implement actual API call
                // DELETE /api/social/posts/drafts/{id}

                window.notify('جاري حذف المسودة...', 'info');

                // Remove from local array for now
                this.drafts = this.drafts.filter(d => d.id !== id);

                window.notify('تم حذف المسودة', 'success');
            } catch (error) {
                console.error('Error deleting draft:', error);
                window.notify('فشل حذف المسودة', 'error');
            }
        },

        viewAnalytics(id) {
            // TODO: Navigate to analytics page or open modal
            // GET /api/social/posts/{id}/analytics
            window.notify('عرض إحصائيات المنشور #' + id, 'info');
        },

        selectDate(date) {
            if (date) {
                window.notify('تاريخ محدد: ' + date, 'info');
            }
        },

        previousMonth() {
            window.notify('الشهر السابق', 'info');
        },

        nextMonth() {
            window.notify('الشهر التالي', 'info');
        },

        goToToday() {
            this.initCalendar();
        },

        async schedulePost() {
            if (this.composerForm.platforms.length === 0) {
                window.notify('الرجاء اختيار منصة واحدة على الأقل', 'warning');
                return;
            }
            if (!this.composerForm.content) {
                window.notify('الرجاء إدخال محتوى المنشور', 'warning');
                return;
            }

            try {
                window.notify('جاري جدولة المنشور...', 'info');

                // TODO: Implement actual API call with CSRF token
                // const response = await fetch('/api/social/posts/schedule', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     },
                //     body: JSON.stringify({
                //         platforms: this.composerForm.platforms,
                //         content: this.composerForm.content,
                //         scheduled_date: this.composerForm.scheduleDate,
                //         scheduled_time: this.composerForm.scheduleTime,
                //         media: [] // Add media attachments if available
                //     })
                // });
                //
                // if (!response.ok) {
                //     const error = await response.json();
                //     throw new Error(error.message || 'Failed to schedule post');
                // }

                // Simulate API delay
                await new Promise(resolve => setTimeout(resolve, 1500));

                window.notify('تم جدولة المنشور بنجاح!', 'success');
                this.showComposerModal = false;
                this.composerForm = { platforms: [], content: '', charCount: 0, scheduleDate: '', scheduleTime: '' };
                await this.fetchData();
            } catch (error) {
                console.error('Error scheduling post:', error);
                window.notify(error.message || 'فشل جدولة المنشور', 'error');
            }
        },

        async saveDraft() {
            try {
                window.notify('جاري حفظ المسودة...', 'info');

                // TODO: Implement actual API call
                // POST /api/social/posts/drafts with same data structure as schedule
                // but without scheduled_date and scheduled_time

                // Simulate API delay
                await new Promise(resolve => setTimeout(resolve, 1000));

                window.notify('تم حفظ المسودة', 'success');
                this.showComposerModal = false;
            } catch (error) {
                console.error('Error saving draft:', error);
                window.notify('فشل حفظ المسودة', 'error');
            }
        },

        scheduleNow() {
            const now = new Date();
            this.composerForm.scheduleDate = now.toISOString().split('T')[0];
            this.composerForm.scheduleTime = now.toTimeString().slice(0, 5);
        },

        scheduleInHour() {
            const inHour = new Date(Date.now() + 3600000);
            this.composerForm.scheduleDate = inHour.toISOString().split('T')[0];
            this.composerForm.scheduleTime = inHour.toTimeString().slice(0, 5);
        },

        scheduleTomorrow() {
            const tomorrow = new Date(Date.now() + 86400000);
            this.composerForm.scheduleDate = tomorrow.toISOString().split('T')[0];
            this.composerForm.scheduleTime = '09:00';
        },

        addEmoji() {
            window.notify('فتح محدد الإيموجي', 'info');
        }
    };
}
</script>
@endpush
