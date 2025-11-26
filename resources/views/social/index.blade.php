@extends('layouts.admin')

@section('page-title', 'إدارة وسائل التواصل الاجتماعي')
@section('page-subtitle', 'جدولة ونشر المحتوى على جميع منصات التواصل')

@section('content')
<div x-data="socialManager()" x-init="init()">
    <!-- Quick Stats Dashboard (Buffer/Vista Social style) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">مجدول</p>
                    <p class="text-3xl font-bold mt-1" x-text="scheduledCount">0</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-yellow-100 text-xs">
                <i class="fas fa-calendar-alt ml-1"></i>
                <span>في انتظار النشر</span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">منشور</p>
                    <p class="text-3xl font-bold mt-1" x-text="publishedCount">0</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-green-100 text-xs">
                <i class="fas fa-chart-line ml-1"></i>
                <span>تم النشر بنجاح</span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-gray-400 to-gray-500 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-100 text-sm font-medium">مسودة</p>
                    <p class="text-3xl font-bold mt-1" x-text="draftCount">0</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-gray-100 text-xs">
                <i class="fas fa-edit ml-1"></i>
                <span>جاهز للتعديل</span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-400 to-rose-500 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">فشل</p>
                    <p class="text-3xl font-bold mt-1" x-text="failedCount">0</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-red-100 text-xs cursor-pointer hover:text-white"
                 x-show="failedCount > 0" @click="deleteAllFailed()">
                <i class="fas fa-trash ml-1"></i>
                <span>حذف الكل</span>
            </div>
            <div class="mt-3 flex items-center text-red-100 text-xs" x-show="failedCount === 0">
                <i class="fas fa-smile ml-1"></i>
                <span>لا توجد أخطاء</span>
            </div>
        </div>
    </div>

    <!-- Main Controls Panel -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <!-- Top Row: Search, View Toggle, Actions -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <!-- Search Box (Hootsuite style) -->
            <div class="relative flex-1 max-w-md">
                <input type="text"
                       x-model="searchQuery"
                       placeholder="ابحث في المنشورات..."
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <button x-show="searchQuery" @click="searchQuery = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- View Toggle (Vista Social style) -->
            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg">
                <button @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-white shadow text-indigo-600' : 'text-gray-600 hover:text-gray-800'"
                        class="p-2 rounded-md transition" title="عرض شبكي">
                    <i class="fas fa-th-large"></i>
                </button>
                <button @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-white shadow text-indigo-600' : 'text-gray-600 hover:text-gray-800'"
                        class="p-2 rounded-md transition" title="عرض قائمة">
                    <i class="fas fa-list"></i>
                </button>
                <button @click="viewMode = 'calendar'"
                        :class="viewMode === 'calendar' ? 'bg-white shadow text-indigo-600' : 'text-gray-600 hover:text-gray-800'"
                        class="p-2 rounded-md transition" title="عرض تقويم">
                    <i class="fas fa-calendar-alt"></i>
                </button>
            </div>

            <!-- Sort Dropdown -->
            <select x-model="sortBy" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="newest">الأحدث أولاً</option>
                <option value="oldest">الأقدم أولاً</option>
                <option value="scheduled">حسب الجدولة</option>
                <option value="platform">حسب المنصة</option>
            </select>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button @click="showQueueSettings = true"
                        class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-lg font-medium hover:bg-gray-50 transition flex items-center gap-2">
                    <i class="fas fa-cog"></i>
                    <span class="hidden sm:inline">إعدادات الطابور</span>
                </button>
                <button @click="showNewPostModal = true"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2 rounded-lg font-medium hover:shadow-lg transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline">منشور جديد</span>
                </button>
            </div>
        </div>

        <!-- Platform Filters (Dynamic based on connected platforms) -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="filterPlatform = 'all'"
                    :class="filterPlatform === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-full font-medium transition text-sm">
                <i class="fas fa-globe ml-1"></i>
                الكل
            </button>
            <template x-for="platform in uniquePlatforms" :key="platform">
                <button @click="filterPlatform = platform"
                        :class="filterPlatform === platform ? getPlatformFilterClass(platform, true) : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-full font-medium transition text-sm">
                    <i :class="getPlatformIcon(platform)" class="ml-1"></i>
                    <span x-text="getPlatformName(platform)"></span>
                </button>
            </template>
        </div>

        <!-- Post Type Filter -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="filterPostType = 'all'"
                    :class="filterPostType === 'all' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-full font-medium transition text-sm">
                <i class="fas fa-th-large ml-1"></i>
                الكل
            </button>
            <button @click="filterPostType = 'feed'"
                    :class="filterPostType === 'feed' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-full font-medium transition text-sm">
                <i class="fas fa-newspaper ml-1"></i>
                منشور
            </button>
            <button @click="filterPostType = 'reel'"
                    :class="filterPostType === 'reel' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-full font-medium transition text-sm">
                <i class="fas fa-video ml-1"></i>
                ريل
            </button>
            <button @click="filterPostType = 'story'"
                    :class="filterPostType === 'story' ? 'bg-pink-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-full font-medium transition text-sm">
                <i class="fas fa-circle ml-1"></i>
                قصة
            </button>
            <button @click="filterPostType = 'carousel'"
                    :class="filterPostType === 'carousel' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-full font-medium transition text-sm">
                <i class="fas fa-images ml-1"></i>
                كاروسيل
            </button>
            <button @click="filterPostType = 'thread'"
                    :class="filterPostType === 'thread' ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-full font-medium transition text-sm">
                <i class="fas fa-stream ml-1"></i>
                ثريد
            </button>
        </div>

        <!-- Status Tabs with Bulk Actions -->
        <div class="flex gap-2 border-t pt-4 items-center justify-between flex-wrap">
            <div class="flex gap-1 overflow-x-auto pb-2">
                <button @click="statusFilter = 'all'"
                        :class="statusFilter === 'all' ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition text-sm border whitespace-nowrap">
                    الكل (<span x-text="posts.length"></span>)
                </button>
                <button @click="statusFilter = 'scheduled'"
                        :class="statusFilter === 'scheduled' ? 'bg-yellow-100 text-yellow-700 border-yellow-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition text-sm border whitespace-nowrap">
                    <i class="fas fa-clock ml-1"></i>
                    مجدول (<span x-text="scheduledCount"></span>)
                </button>
                <button @click="statusFilter = 'published'"
                        :class="statusFilter === 'published' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition text-sm border whitespace-nowrap">
                    <i class="fas fa-check-circle ml-1"></i>
                    منشور (<span x-text="publishedCount"></span>)
                </button>
                <button @click="statusFilter = 'draft'"
                        :class="statusFilter === 'draft' ? 'bg-gray-200 text-gray-700 border-gray-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition text-sm border whitespace-nowrap">
                    <i class="fas fa-file ml-1"></i>
                    مسودة (<span x-text="draftCount"></span>)
                </button>
                <button @click="statusFilter = 'failed'"
                        :class="statusFilter === 'failed' ? 'bg-red-100 text-red-700 border-red-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition text-sm border whitespace-nowrap">
                    <i class="fas fa-exclamation-triangle ml-1"></i>
                    فشل (<span x-text="failedCount"></span>)
                </button>
            </div>

            <!-- Bulk Actions -->
            <div class="flex items-center gap-2" x-show="selectedPosts.length > 0">
                <span class="text-sm text-gray-600">
                    <span x-text="selectedPosts.length"></span> محدد
                </span>
                <button @click="bulkDelete()" class="text-red-600 hover:text-red-700 text-sm font-medium">
                    <i class="fas fa-trash ml-1"></i>
                    حذف
                </button>
                <button @click="selectedPosts = []" class="text-gray-600 hover:text-gray-700 text-sm">
                    إلغاء
                </button>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    <div x-show="viewMode === 'calendar'" x-cloak class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <button @click="changeMonth(-1)" class="p-2 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-chevron-right"></i>
            </button>
            <h3 class="text-lg font-bold text-gray-800" x-text="currentMonthYear"></h3>
            <button @click="changeMonth(1)" class="p-2 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-1">
            <!-- Day Headers -->
            <template x-for="day in ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت']">
                <div class="text-center py-2 text-sm font-medium text-gray-500" x-text="day"></div>
            </template>

            <!-- Calendar Days -->
            <template x-for="day in calendarDays" :key="day.date">
                <div class="min-h-[100px] border border-gray-100 rounded-lg p-1 hover:bg-gray-50 transition"
                     :class="{'bg-gray-50': !day.isCurrentMonth, 'bg-indigo-50': day.isToday}">
                    <div class="text-xs font-medium mb-1"
                         :class="day.isToday ? 'text-indigo-600' : (day.isCurrentMonth ? 'text-gray-700' : 'text-gray-400')"
                         x-text="day.dayNumber"></div>
                    <div class="space-y-1">
                        <template x-for="post in day.posts.slice(0, 2)" :key="post.post_id">
                            <div class="text-xs p-1 rounded truncate cursor-pointer hover:opacity-80"
                                 :class="{
                                     'bg-blue-100 text-blue-800': post.platform === 'facebook',
                                     'bg-pink-100 text-pink-800': post.platform === 'instagram',
                                     'bg-sky-100 text-sky-800': post.platform === 'twitter',
                                     'bg-blue-200 text-blue-900': post.platform === 'linkedin'
                                 }"
                                 @click="editPost(post)"
                                 x-text="post.post_text?.substring(0, 20) + '...'"></div>
                        </template>
                        <div x-show="day.posts.length > 2" class="text-xs text-gray-500 text-center">
                            +<span x-text="day.posts.length - 2"></span> أخرى
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Posts Grid View -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="post in sortedFilteredPosts" :key="post.post_id">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition group relative"
                 :class="{'ring-2 ring-indigo-500': selectedPosts.includes(post.post_id)}">
                <!-- Selection Checkbox -->
                <div class="absolute top-3 right-3 z-10">
                    <input type="checkbox"
                           :checked="selectedPosts.includes(post.post_id)"
                           @change="togglePostSelection(post.post_id)"
                           class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 opacity-0 group-hover:opacity-100 transition"
                           :class="{'opacity-100': selectedPosts.includes(post.post_id)}">
                </div>

                <!-- Platform Badge & Status -->
                <div class="px-4 py-3 border-b flex items-center justify-between bg-gray-50">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center"
                             :class="{
                                 'bg-blue-100 text-blue-600': post.platform === 'facebook',
                                 'bg-gradient-to-br from-purple-100 to-pink-100 text-pink-600': post.platform === 'instagram',
                                 'bg-sky-100 text-sky-500': post.platform === 'twitter',
                                 'bg-blue-100 text-blue-700': post.platform === 'linkedin'
                             }">
                            <i :class="{
                                'fab fa-facebook-f': post.platform === 'facebook',
                                'fab fa-instagram': post.platform === 'instagram',
                                'fab fa-twitter': post.platform === 'twitter',
                                'fab fa-linkedin-in': post.platform === 'linkedin'
                            }"></i>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 text-sm" x-text="post.account_username || post.platform"></span>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <span :class="{
                        'bg-yellow-100 text-yellow-800 border-yellow-200': post.status === 'scheduled',
                        'bg-green-100 text-green-800 border-green-200': post.status === 'published',
                        'bg-gray-100 text-gray-800 border-gray-200': post.status === 'draft',
                        'bg-red-100 text-red-800 border-red-200': post.status === 'failed'
                    }" class="px-2.5 py-1 rounded-full text-xs font-medium border">
                        <span x-text="getStatusLabel(post.status)"></span>
                    </span>
                </div>

                <!-- Post Content -->
                <div class="p-4">
                    <p class="text-gray-700 text-sm line-clamp-3 mb-3" x-text="post.post_text"></p>

                    <!-- Media Preview -->
                    <template x-if="post.media && post.media.length > 0">
                        <div class="relative mb-3 rounded-lg overflow-hidden">
                            <template x-if="post.media[0].type === 'video'">
                                <div class="relative">
                                    <video :src="post.media[0].url" class="w-full h-40 object-cover"></video>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                        <i class="fas fa-play-circle text-white text-4xl"></i>
                                    </div>
                                </div>
                            </template>
                            <template x-if="post.media[0].type !== 'video'">
                                <img :src="post.media[0].url" class="w-full h-40 object-cover">
                            </template>
                            <div x-show="post.media.length > 1"
                                 class="absolute bottom-2 left-2 bg-black/60 text-white text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-images ml-1"></i>
                                <span x-text="post.media.length"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Metrics (for published posts) -->
                    <template x-if="post.status === 'published'">
                        <div class="flex items-center justify-between py-2 border-t border-b mb-3 text-xs text-gray-500">
                            <div class="flex items-center gap-1" title="إعجاب">
                                <i class="far fa-heart"></i>
                                <span x-text="formatNumber(post.likes || 0)"></span>
                            </div>
                            <div class="flex items-center gap-1" title="تعليق">
                                <i class="far fa-comment"></i>
                                <span x-text="formatNumber(post.comments || 0)"></span>
                            </div>
                            <div class="flex items-center gap-1" title="مشاركة">
                                <i class="far fa-share-square"></i>
                                <span x-text="formatNumber(post.shares || 0)"></span>
                            </div>
                            <div class="flex items-center gap-1" title="وصول">
                                <i class="far fa-eye"></i>
                                <span x-text="formatNumber(post.reach || 0)"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Scheduled Time -->
                    <template x-if="post.scheduled_at && post.status === 'scheduled'">
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-3 bg-yellow-50 p-2 rounded-lg">
                            <i class="fas fa-clock text-yellow-600"></i>
                            <span x-text="formatDate(post.scheduled_at)"></span>
                        </div>
                    </template>

                    <!-- Published Time -->
                    <template x-if="post.published_at && post.status === 'published'">
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span>نُشر: <span x-text="formatDate(post.published_at)"></span></span>
                        </div>
                    </template>

                    <!-- Error Message for Failed Posts -->
                    <template x-if="post.status === 'failed' && post.error_message">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-red-800">سبب الفشل:</p>
                                    <p class="text-xs text-red-700 mt-1" x-text="post.error_message"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Quick Actions (Hootsuite style) -->
                    <div class="flex items-center gap-1 border-t pt-3">
                        <button @click="editPost(post)"
                                class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"
                                title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button @click="duplicatePost(post)"
                                class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"
                                title="نسخ">
                            <i class="fas fa-copy"></i>
                        </button>
                        <template x-if="post.status === 'scheduled' || post.status === 'draft'">
                            <button @click="publishNow(post.post_id)"
                                    class="flex-1 bg-green-100 text-green-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-green-200 transition"
                                    title="نشر الآن">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </template>
                        <template x-if="post.status === 'failed'">
                            <button @click="retryPost(post.post_id)"
                                    class="flex-1 bg-orange-100 text-orange-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-orange-200 transition"
                                    title="إعادة المحاولة">
                                <i class="fas fa-redo"></i>
                            </button>
                        </template>
                        <template x-if="post.permalink">
                            <a :href="post.permalink" target="_blank"
                               class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition text-center"
                               title="فتح المنشور">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </template>
                        <button @click="deletePost(post.post_id)"
                                class="bg-red-100 text-red-600 px-3 py-2 rounded-lg text-sm hover:bg-red-200 transition"
                                title="حذف">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Posts List View -->
    <div x-show="viewMode === 'list'" x-cloak class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        <input type="checkbox" @change="toggleAllPosts($event)" class="rounded">
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المنصة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المحتوى</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">التاريخ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="post in sortedFilteredPosts" :key="post.post_id">
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <input type="checkbox"
                                   :checked="selectedPosts.includes(post.post_id)"
                                   @change="togglePostSelection(post.post_id)"
                                   class="rounded">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i :class="{
                                    'fab fa-facebook text-blue-600': post.platform === 'facebook',
                                    'fab fa-instagram text-pink-600': post.platform === 'instagram',
                                    'fab fa-twitter text-sky-500': post.platform === 'twitter',
                                    'fab fa-linkedin text-blue-700': post.platform === 'linkedin'
                                }" class="text-lg"></i>
                                <span class="text-sm text-gray-600" x-text="post.account_username || post.platform"></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <template x-if="post.media && post.media.length > 0">
                                    <img :src="post.media[0].url" class="w-10 h-10 object-cover rounded">
                                </template>
                                <p class="text-sm text-gray-700 line-clamp-2 max-w-xs" x-text="post.post_text"></p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="{
                                'bg-yellow-100 text-yellow-800': post.status === 'scheduled',
                                'bg-green-100 text-green-800': post.status === 'published',
                                'bg-gray-100 text-gray-800': post.status === 'draft',
                                'bg-red-100 text-red-800': post.status === 'failed'
                            }" class="px-2 py-1 rounded-full text-xs font-medium" x-text="getStatusLabel(post.status)"></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <span x-text="formatDate(post.scheduled_at || post.published_at || post.created_at)"></span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <button @click="editPost(post)" class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="duplicatePost(post)" class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded" title="نسخ">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button @click="deletePost(post.post_id)" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <!-- Empty state for list view -->
        <div x-show="sortedFilteredPosts.length === 0" class="py-12 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl mb-3"></i>
            <p>لا توجد منشورات</p>
        </div>
    </div>

    <!-- Empty State -->
    <template x-if="sortedFilteredPosts.length === 0 && viewMode === 'grid'">
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-plus text-indigo-600 text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد منشورات</h3>
            <p class="text-gray-500 mb-6">ابدأ بإنشاء منشور جديد لجدولته على وسائل التواصل</p>
            <button @click="showNewPostModal = true"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg transition">
                <i class="fas fa-plus ml-2"></i>
                إنشاء منشور جديد
            </button>
        </div>
    </template>

    <!-- Enhanced New Post Modal -->
    <div x-show="showNewPostModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @click.self="showNewPostModal = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                <div>
                    <h3 class="text-xl font-bold">
                        <i class="fas fa-plus-circle ml-2"></i>
                        إنشاء منشور جديد
                    </h3>
                    <p class="text-indigo-100 text-sm mt-1">قم بإنشاء ونشر محتواك على جميع منصات التواصل</p>
                </div>
                <button @click="showNewPostModal = false" class="text-white/80 hover:text-white p-2">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body with Two Columns -->
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200 dark:divide-gray-700">
                    <!-- Left Column: Content Creation -->
                    <div class="p-6 space-y-6">
                        <!-- Loading connected platforms -->
                        <div x-show="loadingPlatforms" class="text-center py-8">
                            <i class="fas fa-spinner fa-spin text-4xl text-indigo-600"></i>
                            <p class="mt-3 text-gray-600">جاري تحميل المنصات المتصلة...</p>
                        </div>

                        <!-- No platforms connected warning -->
                        <div x-show="!loadingPlatforms && connectedPlatforms.length === 0" class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                                <div>
                                    <p class="font-medium text-yellow-800">لا توجد منصات متصلة</p>
                                    <p class="text-sm text-yellow-700 mt-1">يرجى ربط حساباتك من صفحة <a href="{{ route('orgs.settings.platform-connections.index', request()->route('org')) }}" class="underline font-medium">إعدادات المنصات</a></p>
                                </div>
                            </div>
                        </div>

                        <!-- Platform Selection -->
                        <div x-show="!loadingPlatforms && connectedPlatforms.length > 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                <i class="fas fa-share-alt ml-1 text-indigo-600"></i>
                                اختر المنصات للنشر
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="platform in connectedPlatforms" :key="platform.id">
                                    <button type="button"
                                            @click="togglePlatformSelection(platform)"
                                            class="flex items-center gap-2 px-4 py-2 rounded-full border-2 transition"
                                            :class="selectedPlatformIds.includes(platform.id)
                                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                                : 'border-gray-200 hover:border-gray-300 text-gray-600'">
                                        <i :class="{
                                            'fab fa-facebook-f text-blue-600': platform.type === 'facebook',
                                            'fab fa-instagram text-pink-600': platform.type === 'instagram',
                                            'fab fa-twitter text-sky-500': platform.type === 'twitter',
                                            'fab fa-linkedin-in text-blue-700': platform.type === 'linkedin'
                                        }"></i>
                                        <span class="text-sm font-medium" x-text="platform.name"></span>
                                        <i x-show="selectedPlatformIds.includes(platform.id)" class="fas fa-check text-indigo-600"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Post Type Selection (Feed/Reel/Story) -->
                        <div x-show="selectedPlatformIds.length > 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                <i class="fas fa-layer-group ml-1 text-indigo-600"></i>
                                نوع المنشور
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <!-- Show post types based on selected platforms -->
                                <template x-for="type in availablePostTypes" :key="type.value">
                                    <button type="button"
                                            @click="newPost.postType = type.value"
                                            class="flex items-center gap-2 px-4 py-2 rounded-lg border-2 transition"
                                            :class="newPost.postType === type.value
                                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                                : 'border-gray-200 hover:border-gray-300 text-gray-600'">
                                        <i :class="'fas ' + type.icon"></i>
                                        <span class="text-sm font-medium" x-text="type.label"></span>
                                        <i x-show="newPost.postType === type.value" class="fas fa-check text-indigo-600"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- POST TYPE SPECIFIC OPTIONS -->
                        <!-- ========================================== -->

                        <!-- ==================== REEL OPTIONS ==================== -->
                        <div x-show="newPost.postType === 'reel'" x-collapse class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 text-purple-700 dark:text-purple-300 font-medium mb-2">
                                <i class="fas fa-video"></i>
                                <span>خيارات الريل</span>
                            </div>

                            <!-- Cover Image Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-image ml-1"></i>
                                    صورة الغلاف
                                </label>
                                <div class="flex gap-3">
                                    <button type="button" @click="postOptions.reel.coverType = 'frame'"
                                            :class="postOptions.reel.coverType === 'frame' ? 'border-purple-500 bg-purple-100' : 'border-gray-200'"
                                            class="flex-1 p-3 rounded-lg border-2 text-center transition">
                                        <i class="fas fa-film text-purple-600 mb-1"></i>
                                        <p class="text-sm font-medium">إطار من الفيديو</p>
                                    </button>
                                    <button type="button" @click="postOptions.reel.coverType = 'custom'"
                                            :class="postOptions.reel.coverType === 'custom' ? 'border-purple-500 bg-purple-100' : 'border-gray-200'"
                                            class="flex-1 p-3 rounded-lg border-2 text-center transition">
                                        <i class="fas fa-upload text-purple-600 mb-1"></i>
                                        <p class="text-sm font-medium">صورة مخصصة</p>
                                    </button>
                                </div>

                                <!-- Frame Offset Slider -->
                                <div x-show="postOptions.reel.coverType === 'frame'" class="mt-3">
                                    <label class="text-xs text-gray-500">موضع الإطار (بالثانية)</label>
                                    <input type="range" x-model="postOptions.reel.coverFrameOffset" min="0" max="90000" step="1000"
                                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-purple-600">
                                    <div class="flex justify-between text-xs text-gray-400">
                                        <span>0 ث</span>
                                        <span x-text="Math.round(postOptions.reel.coverFrameOffset / 1000) + ' ث'"></span>
                                        <span>90 ث</span>
                                    </div>
                                </div>

                                <!-- Custom Cover Upload -->
                                <div x-show="postOptions.reel.coverType === 'custom'" class="mt-3">
                                    <input type="url" x-model="postOptions.reel.coverImageUrl"
                                           placeholder="رابط صورة الغلاف (9:16)"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <p class="text-xs text-gray-400 mt-1">يُفضل استخدام صورة بأبعاد 9:16</p>
                                </div>
                            </div>

                            <!-- Reel Display Options -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Share to Feed -->
                                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-700 dark:text-gray-300 text-sm">عرض في الصفحة الرئيسية</p>
                                        <p class="text-xs text-gray-500">سيظهر الريل في صفحة البروفايل</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="postOptions.reel.shareToFeed" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-purple-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                    </label>
                                </div>

                            </div>
                        </div>

                        <!-- ==================== STORY OPTIONS ==================== -->
                        <div x-show="newPost.postType === 'story'" x-collapse class="bg-pink-50 dark:bg-pink-900/20 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 text-pink-700 dark:text-pink-300 font-medium mb-2">
                                <i class="fas fa-circle"></i>
                                <span>خيارات القصة</span>
                            </div>

                            <!-- Story Duration -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-clock ml-1"></i>
                                    مدة العرض (للصور)
                                </label>
                                <div class="flex gap-2">
                                    <template x-for="duration in [3, 5, 7, 10]" :key="duration">
                                        <button type="button" @click="postOptions.story.duration = duration"
                                                :class="postOptions.story.duration === duration ? 'border-pink-500 bg-pink-100 text-pink-700' : 'border-gray-200'"
                                                class="px-4 py-2 rounded-lg border-2 text-sm font-medium transition"
                                                x-text="duration + ' ث'">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Story Stickers Info -->
                            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">ملاحظة</p>
                                        <p class="text-xs text-yellow-700 dark:text-yellow-400">إضافة الروابط والملصقات (استطلاع، سؤال، العد التنازلي) غير متاحة عبر API. يمكنك إضافتها يدوياً من التطبيق.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== CAROUSEL OPTIONS ==================== -->
                        <div x-show="newPost.postType === 'carousel'" x-collapse class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 text-orange-700 dark:text-orange-300 font-medium mb-2">
                                <i class="fas fa-images"></i>
                                <span>خيارات الكاروسيل</span>
                            </div>

                            <!-- Alt Text for Each Image -->
                            <div x-show="uploadedMedia.length > 0">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-universal-access ml-1"></i>
                                    النص البديل لكل صورة
                                </label>
                                <template x-for="(media, index) in uploadedMedia" :key="index">
                                    <div class="flex items-center gap-2 mb-2">
                                        <img :src="media.preview" class="w-10 h-10 rounded object-cover">
                                        <input type="text"
                                               x-model="postOptions.carousel.altTexts[index]"
                                               :placeholder="'وصف الصورة ' + (index + 1) + ' للقارئات الصوتية'"
                                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    </div>
                                </template>
                                <p class="text-xs text-gray-400">النص البديل يساعد ذوي الاحتياجات الخاصة على فهم محتوى الصور</p>
                            </div>

                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    <i class="fas fa-lightbulb ml-1"></i>
                                    يمكنك إضافة حتى 10 صور/فيديوهات في الكاروسيل
                                </p>
                            </div>
                        </div>

                        <!-- ==================== FEED POST OPTIONS ==================== -->
                        <div x-show="newPost.postType === 'feed' || newPost.postType === 'post'" x-collapse class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 text-green-700 dark:text-green-300 font-medium mb-2">
                                <i class="fas fa-newspaper"></i>
                                <span>خيارات المنشور</span>
                            </div>

                            <!-- Alt Text -->
                            <div x-show="uploadedMedia.length > 0">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-universal-access ml-1"></i>
                                    النص البديل
                                </label>
                                <input type="text" x-model="postOptions.instagram.altText"
                                       placeholder="وصف الصورة للقارئات الصوتية"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>

                        <!-- ==================== INSTAGRAM/FACEBOOK COMMON OPTIONS ==================== -->
                        <div x-show="(hasInstagramSelected || hasFacebookSelected) && newPost.postType !== 'story'" x-collapse class="bg-gradient-to-r from-pink-50 to-purple-50 dark:from-pink-900/20 dark:to-purple-900/20 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 text-purple-700 dark:text-purple-300 font-medium mb-2">
                                <i class="fab fa-instagram"></i>
                                <span>خيارات Meta (Instagram/Facebook)</span>
                            </div>

                            <!-- Location with Autocomplete -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-map-marker-alt ml-1 text-red-500"></i>
                                    الموقع
                                </label>

                                <!-- Selected Location Display -->
                                <div x-show="selectedLocation" class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/30 border border-green-300 rounded-lg mb-2">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-map-marker-alt text-green-600"></i>
                                        <div>
                                            <p class="font-medium text-green-800 dark:text-green-200 text-sm" x-text="selectedLocation?.name"></p>
                                            <p class="text-xs text-green-600 dark:text-green-400" x-text="selectedLocation?.address"></p>
                                        </div>
                                    </div>
                                    <button type="button" @click="clearLocation()" class="text-green-600 hover:text-red-600 transition">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <!-- Search Input -->
                                <div x-show="!selectedLocation" class="relative">
                                    <input type="text"
                                           x-model="locationQuery"
                                           @input="searchLocations()"
                                           @focus="showLocationDropdown = locationResults.length > 0"
                                           @click.away="showLocationDropdown = false"
                                           placeholder="ابحث عن موقع... (مثال: برج خليفة، دبي)"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm pr-10">

                                    <!-- Loading Spinner -->
                                    <div x-show="isSearchingLocations" class="absolute left-3 top-1/2 transform -translate-y-1/2">
                                        <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                    </div>

                                    <!-- Search Icon -->
                                    <div x-show="!isSearchingLocations" class="absolute left-3 top-1/2 transform -translate-y-1/2">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>

                                    <!-- Autocomplete Dropdown -->
                                    <div x-show="showLocationDropdown && locationResults.length > 0"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">

                                        <template x-for="location in locationResults" :key="location.id">
                                            <button type="button"
                                                    @click="selectLocation(location)"
                                                    class="w-full text-right px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition">
                                                <div class="flex items-start gap-3">
                                                    <i class="fas fa-map-marker-alt text-red-500 mt-1"></i>
                                                    <div class="flex-1">
                                                        <p class="font-medium text-gray-800 dark:text-gray-200 text-sm" x-text="location.name"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="location.address || location.category"></p>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </div>

                                    <!-- No Results Message -->
                                    <div x-show="showLocationDropdown && locationQuery.length >= 2 && locationResults.length === 0 && !isSearchingLocations"
                                         class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-4 text-center">
                                        <i class="fas fa-map-marker-alt text-gray-300 text-2xl mb-2"></i>
                                        <p class="text-sm text-gray-500">لم يتم العثور على نتائج</p>
                                    </div>
                                </div>
                            </div>

                            <!-- First Comment -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-comment ml-1"></i>
                                    أول تعليق
                                </label>
                                <input type="text" x-model="postOptions.instagram.firstComment"
                                       placeholder="أضف الهاشتاقات أو تعليق أول هنا..."
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <p class="text-xs text-gray-400 mt-1">سيتم نشر هذا كأول تعليق على المنشور</p>
                            </div>

                            <!-- Tag People -->
                            <div x-show="uploadedMedia.length > 0">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-user-tag ml-1"></i>
                                    الإشارة للأشخاص في الصورة
                                </label>
                                <input type="text" x-model="userTagInput"
                                       @keydown.enter.prevent="if(userTagInput) { postOptions.instagram.userTags.push({username: userTagInput, x: 0.5, y: 0.5}); userTagInput = ''; }"
                                       placeholder="@username ثم اضغط Enter"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <div class="flex flex-wrap gap-2 mt-2" x-show="postOptions.instagram.userTags.length > 0">
                                    <template x-for="(tag, index) in postOptions.instagram.userTags" :key="index">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                                            <i class="fas fa-user text-xs"></i>
                                            <span x-text="tag.username"></span>
                                            <button type="button" @click="postOptions.instagram.userTags.splice(index, 1)" class="hover:text-red-600">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">يمكنك الإشارة لما يصل إلى 20 شخص في الصورة</p>
                            </div>

                            <!-- Collaborators with Suggestions and Validation -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-user-friends ml-1"></i>
                                    المتعاونون (حتى 3)
                                </label>
                                <div class="relative">
                                    <input type="text" x-model="collaboratorInput"
                                           @input="searchCollaborators()"
                                           @focus="showCollaboratorSuggestions = collaboratorSuggestions.length > 0"
                                           @keydown.enter.prevent="addCollaborator(collaboratorInput)"
                                           @keydown.escape="showCollaboratorSuggestions = false"
                                           placeholder="@username ثم اضغط Enter"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm pr-10">
                                    <!-- Validation indicator -->
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                        <template x-if="isValidatingUsername">
                                            <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                        </template>
                                        <template x-if="!isValidatingUsername && usernameValidationResult === true">
                                            <i class="fas fa-check-circle text-green-500" title="اسم مستخدم صحيح"></i>
                                        </template>
                                        <template x-if="!isValidatingUsername && usernameValidationResult === false">
                                            <i class="fas fa-exclamation-circle text-yellow-500" title="لم يتم العثور على المستخدم"></i>
                                        </template>
                                    </div>
                                </div>

                                <!-- Suggestions Dropdown -->
                                <div x-show="showCollaboratorSuggestions && filteredCollaboratorSuggestions.length > 0"
                                     @click.away="showCollaboratorSuggestions = false"
                                     x-transition
                                     class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    <div class="py-1">
                                        <p class="px-3 py-1 text-xs text-gray-500 dark:text-gray-400">اقتراحات من المنشورات السابقة:</p>
                                        <template x-for="suggestion in filteredCollaboratorSuggestions" :key="suggestion">
                                            <button type="button"
                                                    @click="addCollaborator(suggestion)"
                                                    class="w-full text-right px-3 py-2 text-sm hover:bg-purple-50 dark:hover:bg-purple-900/20 flex items-center gap-2">
                                                <i class="fab fa-instagram text-pink-500"></i>
                                                <span x-text="'@' + suggestion"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Added Collaborators -->
                                <div class="flex flex-wrap gap-2 mt-2" x-show="postOptions.instagram.collaborators.length > 0">
                                    <template x-for="(collab, index) in postOptions.instagram.collaborators" :key="index">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">
                                            <span x-text="collab.startsWith('@') ? collab : '@' + collab"></span>
                                            <button type="button" @click="postOptions.instagram.collaborators.splice(index, 1)" class="hover:text-red-600">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">دعوة أشخاص آخرين لنشر المحتوى معك (يتم التحقق تلقائياً)</p>
                            </div>

                            <!-- Product Tags (for Shopping - requires Instagram Shopping setup) -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-shopping-bag ml-1 text-pink-500"></i>
                                    تفاصيل المنتج (للتسوق)
                                </label>
                                <input type="text" x-model="productTagInput"
                                       @keydown.enter.prevent="if(productTagInput) { postOptions.instagram.productTags.push(productTagInput); productTagInput = ''; }"
                                       placeholder="أدخل معرف المنتج أو الرابط ثم اضغط Enter"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <div class="flex flex-wrap gap-2 mt-2" x-show="postOptions.instagram.productTags.length > 0">
                                    <template x-for="(tag, index) in postOptions.instagram.productTags" :key="index">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-pink-100 text-pink-700 rounded-full text-sm">
                                            <i class="fas fa-tag text-xs"></i>
                                            <span x-text="tag"></span>
                                            <button type="button" @click="postOptions.instagram.productTags.splice(index, 1)" class="hover:text-red-600">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">يتطلب تفعيل Instagram Shopping في حسابك</p>
                            </div>

                            <!-- Product Details (DM-based orders - No Shopping required) -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-box-open ml-1 text-orange-500"></i>
                                        تفاصيل المنتج (للطلب عبر الرسائل)
                                    </label>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="postOptions.product.enabled" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-orange-500 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-orange-500 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                    </label>
                                </div>

                                <div x-show="postOptions.product.enabled" x-collapse class="space-y-3">
                                    <!-- Product Title -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">اسم المنتج</label>
                                        <input type="text" x-model="postOptions.product.title"
                                               placeholder="مثال: حقيبة يد جلدية فاخرة"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    </div>

                                    <!-- Price and Currency -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">السعر</label>
                                            <input type="number" x-model="postOptions.product.price"
                                                   placeholder="0.00"
                                                   step="0.01"
                                                   min="0"
                                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">العملة</label>
                                            <select x-model="postOptions.product.currency"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                                <template x-for="curr in currencies" :key="curr.code">
                                                    <option :value="curr.code" x-text="curr.symbol + ' - ' + curr.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Product Description (optional) -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">وصف المنتج (اختياري)</label>
                                        <textarea x-model="postOptions.product.description"
                                                  placeholder="وصف مختصر للمنتج..."
                                                  rows="2"
                                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                                    </div>

                                    <!-- Order CTA Message -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">رسالة الطلب</label>
                                        <input type="text" x-model="postOptions.product.orderMessage"
                                               placeholder="للطلب، أرسل رسالة مباشرة 📩"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        <p class="text-xs text-gray-400 mt-1">ستضاف هذه الرسالة لنهاية المنشور</p>
                                    </div>

                                    <!-- Preview -->
                                    <div x-show="postOptions.product.title || postOptions.product.price" class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 mt-2">
                                        <p class="text-xs font-medium text-orange-700 dark:text-orange-300 mb-1">معاينة:</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            <span x-text="postOptions.product.title"></span>
                                            <template x-if="postOptions.product.price">
                                                <span class="font-semibold">
                                                    - <span x-text="postOptions.product.price"></span>
                                                    <span x-text="currencies.find(c => c.code === postOptions.product.currency)?.symbol || postOptions.product.currency"></span>
                                                </span>
                                            </template>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1" x-text="postOptions.product.orderMessage"></p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">
                                    <i class="fas fa-lightbulb ml-1 text-yellow-500"></i>
                                    لا يتطلب Instagram Shopping - الطلبات ستصل عبر الرسائل المباشرة
                                </p>
                            </div>
                        </div>

                        <!-- ==================== TIKTOK OPTIONS ==================== -->
                        <div x-show="hasTikTokSelected" x-collapse class="bg-gray-900 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 text-white font-medium mb-2">
                                <i class="fab fa-tiktok"></i>
                                <span>خيارات تيك توك</span>
                            </div>

                            <!-- Viewer Setting -->
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">من يمكنه المشاهدة</label>
                                <select x-model="postOptions.tiktok.viewerSetting"
                                        class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white">
                                    <option value="public">الجميع (عام)</option>
                                    <option value="friends">الأصدقاء فقط</option>
                                    <option value="private">أنا فقط (خاص)</option>
                                </select>
                            </div>

                            <!-- Interaction Settings -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Allow Comments -->
                                <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                                    <span class="text-sm text-gray-300">السماح بالتعليقات</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" :checked="!postOptions.tiktok.disableComments"
                                               @change="postOptions.tiktok.disableComments = !$event.target.checked" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:ring-2 peer-focus:ring-pink-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-pink-500 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                    </label>
                                </div>

                                <!-- Allow Duet -->
                                <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                                    <span class="text-sm text-gray-300">السماح بـ Duet</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" :checked="!postOptions.tiktok.disableDuet"
                                               @change="postOptions.tiktok.disableDuet = !$event.target.checked" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:ring-2 peer-focus:ring-pink-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-pink-500 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                    </label>
                                </div>

                                <!-- Allow Stitch -->
                                <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                                    <span class="text-sm text-gray-300">السماح بـ Stitch</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" :checked="!postOptions.tiktok.disableStitch"
                                               @change="postOptions.tiktok.disableStitch = !$event.target.checked" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:ring-2 peer-focus:ring-pink-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-pink-500 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Disclosure Settings -->
                            <div class="border-t border-gray-700 pt-4">
                                <p class="text-sm font-medium text-gray-300 mb-3">
                                    <i class="fas fa-info-circle ml-1"></i>
                                    إعدادات الإفصاح
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Branded Content -->
                                    <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                                        <div>
                                            <p class="text-sm text-gray-300">محتوى ترويجي مدفوع</p>
                                            <p class="text-xs text-gray-500">إعلان أو شراكة تجارية</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="postOptions.tiktok.brandContentToggle" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:ring-2 peer-focus:ring-pink-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-pink-500 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                        </label>
                                    </div>

                                    <!-- AI Generated -->
                                    <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                                        <div>
                                            <p class="text-sm text-gray-300">مُنشأ بالذكاء الاصطناعي</p>
                                            <p class="text-xs text-gray-500">محتوى AI أو صور اصطناعية</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="postOptions.tiktok.aiGenerated" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:ring-2 peer-focus:ring-pink-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-pink-500 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== LINKEDIN OPTIONS ==================== -->
                        <div x-show="hasLinkedInSelected" x-collapse class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300 font-medium mb-2">
                                <i class="fab fa-linkedin"></i>
                                <span>خيارات LinkedIn</span>
                            </div>

                            <!-- Visibility -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">من يمكنه المشاهدة</label>
                                <select x-model="postOptions.linkedin.visibility"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="PUBLIC">الجميع (عام)</option>
                                    <option value="CONNECTIONS">جهات الاتصال فقط</option>
                                </select>
                            </div>

                            <!-- Article Options (when article type) -->
                            <div x-show="newPost.postType === 'article'" class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">عنوان المقال</label>
                                    <input type="text" x-model="postOptions.linkedin.articleTitle"
                                           placeholder="عنوان جذاب للمقال"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">وصف مختصر</label>
                                    <textarea x-model="postOptions.linkedin.articleDescription" rows="2"
                                              placeholder="وصف مختصر يظهر في المعاينة"
                                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                                </div>
                            </div>

                            <!-- Comments Toggle -->
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">السماح بالتعليقات</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="postOptions.linkedin.allowComments" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>
                        </div>

                        <!-- ==================== TWITTER/X OPTIONS ==================== -->
                        <div x-show="hasTwitterSelected" x-collapse class="bg-sky-50 dark:bg-sky-900/20 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 text-sky-700 dark:text-sky-300 font-medium mb-2">
                                <i class="fab fa-twitter"></i>
                                <span>خيارات Twitter/X</span>
                            </div>

                            <!-- Reply Restriction -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">من يمكنه الرد</label>
                                <select x-model="postOptions.twitter.replyRestriction"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="everyone">الجميع</option>
                                    <option value="following">الذين تتابعهم</option>
                                    <option value="mentioned">المذكورون فقط</option>
                                </select>
                            </div>

                            <!-- Alt Text for Media -->
                            <div x-show="uploadedMedia.length > 0">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-universal-access ml-1"></i>
                                    النص البديل للوسائط
                                </label>
                                <input type="text" x-model="postOptions.twitter.altText"
                                       placeholder="وصف الصورة/الفيديو للقارئات الصوتية"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>

                            <!-- Thread Options -->
                            <div x-show="newPost.postType === 'thread'" class="space-y-3">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-list ml-1"></i>
                                    تغريدات السلسلة
                                </p>

                                <template x-for="(tweet, index) in postOptions.twitter.threadTweets" :key="index">
                                    <div class="relative">
                                        <div class="flex items-start gap-2">
                                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-sky-600 text-white text-xs flex items-center justify-center" x-text="index + 1"></span>
                                            <textarea x-model="postOptions.twitter.threadTweets[index]" rows="2"
                                                      :placeholder="'التغريدة ' + (index + 1)"
                                                      class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                                            <button type="button" x-show="postOptions.twitter.threadTweets.length > 1"
                                                    @click="postOptions.twitter.threadTweets.splice(index, 1)"
                                                    class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="mr-8 text-xs text-gray-400 mt-1">
                                            <span x-text="280 - (postOptions.twitter.threadTweets[index]?.length || 0)"></span> حرف متبقي
                                        </div>
                                    </div>
                                </template>

                                <button type="button" @click="postOptions.twitter.threadTweets.push('')"
                                        x-show="postOptions.twitter.threadTweets.length < 25"
                                        class="w-full py-2 border-2 border-dashed border-sky-300 rounded-lg text-sky-600 hover:bg-sky-50 transition">
                                    <i class="fas fa-plus ml-1"></i>
                                    إضافة تغريدة
                                </button>
                            </div>

                            <!-- Sensitive Content -->
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg">
                                <div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">محتوى حساس</p>
                                    <p class="text-xs text-gray-500">وضع تحذير قبل عرض المحتوى</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="postOptions.twitter.sensitiveContent" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-sky-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:-translate-x-full peer-checked:bg-sky-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- END POST TYPE SPECIFIC OPTIONS -->
                        <!-- ========================================== -->

                        <!-- Post Content with AI Assistant -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-pen ml-1 text-indigo-600"></i>
                                    محتوى المنشور
                                </label>
                                <button @click="showAiAssistant = !showAiAssistant"
                                        class="text-sm text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                                    <i class="fas fa-magic"></i>
                                    مساعد AI
                                </button>
                            </div>

                            <!-- AI Assistant Panel -->
                            <div x-show="showAiAssistant" x-collapse class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-4 mb-3">
                                <div class="flex items-center gap-2 mb-3">
                                    <i class="fas fa-robot text-indigo-600"></i>
                                    <span class="font-medium text-indigo-800">مساعد الكتابة الذكي</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button @click="aiSuggest('shorter')" class="px-3 py-1.5 bg-white text-sm rounded-full border hover:bg-gray-50 transition">
                                        <i class="fas fa-compress-alt ml-1"></i> اختصر
                                    </button>
                                    <button @click="aiSuggest('longer')" class="px-3 py-1.5 bg-white text-sm rounded-full border hover:bg-gray-50 transition">
                                        <i class="fas fa-expand-alt ml-1"></i> أطول
                                    </button>
                                    <button @click="aiSuggest('formal')" class="px-3 py-1.5 bg-white text-sm rounded-full border hover:bg-gray-50 transition">
                                        <i class="fas fa-user-tie ml-1"></i> رسمي
                                    </button>
                                    <button @click="aiSuggest('casual')" class="px-3 py-1.5 bg-white text-sm rounded-full border hover:bg-gray-50 transition">
                                        <i class="fas fa-smile ml-1"></i> غير رسمي
                                    </button>
                                    <button @click="aiSuggest('hashtags')" class="px-3 py-1.5 bg-white text-sm rounded-full border hover:bg-gray-50 transition">
                                        <i class="fas fa-hashtag ml-1"></i> هاشتاقات
                                    </button>
                                    <button @click="aiSuggest('emojis')" class="px-3 py-1.5 bg-white text-sm rounded-full border hover:bg-gray-50 transition">
                                        <i class="far fa-smile ml-1"></i> إيموجي
                                    </button>
                                </div>
                            </div>

                            <textarea x-model="newPost.content" rows="6"
                                      class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl p-4 resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                      placeholder="اكتب محتوى المنشور هنا... ماذا تريد أن تشارك مع جمهورك؟"></textarea>

                            <!-- Character Counter (Buffer style) -->
                            <div class="flex justify-between items-center mt-2 text-xs">
                                <div class="flex gap-3">
                                    <span :class="newPost.content.length > 280 && hasTwitterSelected ? 'text-red-500' : 'text-gray-500'">
                                        <i class="fab fa-twitter ml-1"></i>
                                        <span x-text="280 - newPost.content.length"></span>
                                    </span>
                                    <span :class="newPost.content.length > 2200 && hasInstagramSelected ? 'text-red-500' : 'text-gray-500'">
                                        <i class="fab fa-instagram ml-1"></i>
                                        <span x-text="2200 - newPost.content.length"></span>
                                    </span>
                                    <span :class="newPost.content.length > 63206 && hasFacebookSelected ? 'text-red-500' : 'text-gray-500'">
                                        <i class="fab fa-facebook ml-1"></i>
                                        <span x-text="newPost.content.length"></span>/63206
                                    </span>
                                </div>
                                <span class="text-gray-400" x-text="newPost.content.length + ' حرف'"></span>
                            </div>
                        </div>

                        <!-- Media Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-image ml-1 text-indigo-600"></i>
                                الوسائط
                                <span x-show="hasInstagramSelected" class="text-red-500 text-xs">(مطلوب لـ Instagram)</span>
                            </label>

                            <!-- Instagram media requirement warning -->
                            <div x-show="hasInstagramSelected && uploadedMedia.length === 0" class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-3">
                                <div class="flex items-center gap-2 text-amber-800">
                                    <i class="fab fa-instagram text-lg"></i>
                                    <span class="text-sm font-medium">Instagram يتطلب صورة أو فيديو للنشر</span>
                                </div>
                            </div>

                            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-indigo-400 transition cursor-pointer"
                                 @click="$refs.mediaInput.click()"
                                 @dragover.prevent="dragOver = true"
                                 @dragleave="dragOver = false"
                                 @drop.prevent="handleFileDrop($event)"
                                 :class="[
                                     dragOver ? 'border-indigo-500 bg-indigo-50' : '',
                                     hasInstagramSelected && uploadedMedia.length === 0 ? 'border-amber-400' : ''
                                 ]">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <p class="text-gray-600 dark:text-gray-400">اسحب الملفات هنا أو اضغط للرفع</p>
                                <p class="text-xs text-gray-500 mt-1">صور (JPG, PNG) أو فيديو (MP4) - الحد الأقصى 50MB</p>
                                <input type="file" x-ref="mediaInput" class="hidden" multiple accept="image/*,video/*" @change="handleFileSelect($event)">
                            </div>

                            <!-- Preview uploaded files -->
                            <div x-show="uploadedMedia.length > 0" class="mt-4 grid grid-cols-4 gap-3">
                                <template x-for="(media, index) in uploadedMedia" :key="index">
                                    <div class="relative group">
                                        <img :src="media.preview" class="w-full h-20 object-cover rounded-lg">
                                        <button @click="removeMedia(index)"
                                                class="absolute -top-2 -left-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Schedule Options -->
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                <i class="fas fa-clock ml-1 text-indigo-600"></i>
                                وقت النشر
                            </label>
                            <div class="flex flex-wrap gap-3">
                                <label class="flex items-center gap-2 cursor-pointer bg-white px-4 py-2 rounded-lg border transition"
                                       :class="newPost.publishType === 'now' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                                    <input type="radio" x-model="newPost.publishType" value="now" class="text-indigo-600">
                                    <i class="fas fa-paper-plane text-indigo-600"></i>
                                    <span class="text-gray-700">نشر الآن</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer bg-white px-4 py-2 rounded-lg border transition"
                                       :class="newPost.publishType === 'scheduled' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                                    <input type="radio" x-model="newPost.publishType" value="scheduled" class="text-indigo-600">
                                    <i class="fas fa-calendar-alt text-indigo-600"></i>
                                    <span class="text-gray-700">جدولة</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer bg-white px-4 py-2 rounded-lg border transition"
                                       :class="newPost.publishType === 'queue' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                                    <input type="radio" x-model="newPost.publishType" value="queue" class="text-indigo-600">
                                    <i class="fas fa-list-ol text-indigo-600"></i>
                                    <span class="text-gray-700">إضافة للطابور</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer bg-white px-4 py-2 rounded-lg border transition"
                                       :class="newPost.publishType === 'draft' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                                    <input type="radio" x-model="newPost.publishType" value="draft" class="text-indigo-600">
                                    <i class="fas fa-file-alt text-indigo-600"></i>
                                    <span class="text-gray-700">مسودة</span>
                                </label>
                            </div>

                            <!-- Schedule date/time picker -->
                            <div x-show="newPost.publishType === 'scheduled'" class="mt-4 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">التاريخ</label>
                                        <input type="date" x-model="newPost.scheduledDate"
                                               :min="minDate"
                                               class="w-full border border-gray-300 rounded-lg p-2">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">الوقت</label>
                                        <input type="time" x-model="newPost.scheduledTime"
                                               class="w-full border border-gray-300 rounded-lg p-2">
                                    </div>
                                </div>

                                <!-- Best Time Suggestions (Vista Social style) -->
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                    <p class="text-sm font-medium text-blue-800 mb-2">
                                        <i class="fas fa-lightbulb ml-1"></i>
                                        أفضل أوقات النشر
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="time in bestTimes" :key="time.value">
                                            <button type="button"
                                                    @click="setBestTime(time)"
                                                    class="px-3 py-1 bg-white text-xs rounded-full border border-blue-200 hover:bg-blue-100 transition">
                                                <span x-text="time.label"></span>
                                                <span class="text-blue-600" x-text="time.engagement"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Queue Info (Buffer style) -->
                            <div x-show="newPost.publishType === 'queue'" class="mt-4">
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-sm font-medium text-purple-800">
                                            <i class="fas fa-info-circle ml-1"></i>
                                            سيتم جدولة المنشور تلقائياً
                                        </p>
                                        <button @click="showQueueSettings = true" class="text-xs text-purple-600 hover:text-purple-700 underline">
                                            تعديل الإعدادات
                                        </button>
                                    </div>
                                    <p class="text-xs text-purple-700">
                                        سيتم نشر المنشور في أقرب وقت متاح حسب الجدول المحدد للحساب
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Preview -->
                    <div class="p-6 bg-gray-50 dark:bg-gray-900/50">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                            <i class="fas fa-eye ml-1 text-indigo-600"></i>
                            معاينة المنشور
                        </h4>

                        <!-- Platform Preview Tabs -->
                        <div class="flex gap-2 mb-4">
                            <template x-for="platform in selectedPlatformsForPreview" :key="platform.id">
                                <button @click="previewPlatform = platform.type"
                                        :class="previewPlatform === platform.type ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition">
                                    <i :class="{
                                        'fab fa-facebook-f': platform.type === 'facebook',
                                        'fab fa-instagram': platform.type === 'instagram',
                                        'fab fa-twitter': platform.type === 'twitter',
                                        'fab fa-linkedin-in': platform.type === 'linkedin'
                                    }"></i>
                                </button>
                            </template>
                        </div>

                        <!-- Facebook Preview -->
                        <div x-show="previewPlatform === 'facebook'" class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="p-4 border-b">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fab fa-facebook-f text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">صفحتك</p>
                                        <p class="text-xs text-gray-500">الآن · <i class="fas fa-globe-americas"></i></p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4">
                                <p class="text-gray-800 whitespace-pre-wrap" x-text="newPost.content || 'محتوى المنشور سيظهر هنا...'"></p>
                            </div>
                            <template x-if="uploadedMedia.length > 0">
                                <div class="border-t">
                                    <img :src="uploadedMedia[0].preview" class="w-full h-48 object-cover">
                                </div>
                            </template>
                            <div class="p-3 border-t flex justify-around text-gray-500 text-sm">
                                <span><i class="far fa-thumbs-up ml-1"></i> إعجاب</span>
                                <span><i class="far fa-comment ml-1"></i> تعليق</span>
                                <span><i class="far fa-share-square ml-1"></i> مشاركة</span>
                            </div>
                        </div>

                        <!-- Instagram Preview -->
                        <div x-show="previewPlatform === 'instagram'" class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="p-3 border-b flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full"></div>
                                    <span class="font-medium text-sm">your_account</span>
                                </div>
                                <i class="fas fa-ellipsis-h text-gray-500"></i>
                            </div>
                            <template x-if="uploadedMedia.length > 0">
                                <img :src="uploadedMedia[0].preview" class="w-full aspect-square object-cover">
                            </template>
                            <template x-if="uploadedMedia.length === 0">
                                <div class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            </template>
                            <div class="p-3">
                                <div class="flex justify-between mb-2">
                                    <div class="flex gap-4">
                                        <i class="far fa-heart text-xl"></i>
                                        <i class="far fa-comment text-xl"></i>
                                        <i class="far fa-paper-plane text-xl"></i>
                                    </div>
                                    <i class="far fa-bookmark text-xl"></i>
                                </div>
                                <p class="text-sm"><span class="font-medium">your_account</span> <span x-text="newPost.content.substring(0, 100) || 'محتوى المنشور...'"></span></p>
                            </div>
                        </div>

                        <!-- Twitter Preview -->
                        <div x-show="previewPlatform === 'twitter'" class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex gap-3">
                                <div class="w-12 h-12 bg-sky-100 rounded-full flex items-center justify-center">
                                    <i class="fab fa-twitter text-sky-500"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold">حسابك</span>
                                        <span class="text-gray-500">@your_handle · الآن</span>
                                    </div>
                                    <p class="mt-1 text-gray-800" x-text="newPost.content.substring(0, 280) || 'محتوى التغريدة...'"></p>
                                    <template x-if="uploadedMedia.length > 0">
                                        <img :src="uploadedMedia[0].preview" class="mt-3 rounded-xl w-full h-40 object-cover">
                                    </template>
                                    <div class="flex justify-between mt-3 text-gray-500">
                                        <i class="far fa-comment"></i>
                                        <i class="fas fa-retweet"></i>
                                        <i class="far fa-heart"></i>
                                        <i class="fas fa-share"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LinkedIn Preview -->
                        <div x-show="previewPlatform === 'linkedin'" class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fab fa-linkedin-in text-blue-700"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">اسمك</p>
                                        <p class="text-xs text-gray-500">المسمى الوظيفي</p>
                                        <p class="text-xs text-gray-400">الآن · <i class="fas fa-globe"></i></p>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 pb-4">
                                <p class="text-gray-800" x-text="newPost.content || 'محتوى المنشور سيظهر هنا...'"></p>
                            </div>
                            <template x-if="uploadedMedia.length > 0">
                                <img :src="uploadedMedia[0].preview" class="w-full h-48 object-cover">
                            </template>
                            <div class="p-3 border-t flex justify-around text-gray-500 text-xs">
                                <span><i class="far fa-thumbs-up ml-1"></i> إعجاب</span>
                                <span><i class="far fa-comment ml-1"></i> تعليق</span>
                                <span><i class="fas fa-retweet ml-1"></i> إعادة نشر</span>
                                <span><i class="far fa-paper-plane ml-1"></i> إرسال</span>
                            </div>
                        </div>

                        <!-- No platform selected -->
                        <div x-show="selectedPlatformIds.length === 0" class="text-center py-12 text-gray-400">
                            <i class="fas fa-hand-pointer text-4xl mb-3"></i>
                            <p>اختر منصة لمعاينة المنشور</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 flex justify-between items-center">
                <button @click="showNewPostModal = false"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                    إلغاء
                </button>
                <button @click="savePost()"
                        :disabled="isSubmitting || !canSubmit"
                        class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <span x-show="!isSubmitting">
                        <i class="fas fa-paper-plane" x-show="newPost.publishType === 'now'"></i>
                        <i class="fas fa-clock" x-show="newPost.publishType === 'scheduled'"></i>
                        <i class="fas fa-save" x-show="newPost.publishType === 'draft'"></i>
                        <span x-text="newPost.publishType === 'now' ? 'نشر الآن' : (newPost.publishType === 'scheduled' ? 'جدولة النشر' : 'حفظ كمسودة')"></span>
                    </span>
                    <span x-show="isSubmitting">
                        <i class="fas fa-spinner fa-spin ml-2"></i>
                        جاري الحفظ...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Post Modal -->
    <div x-show="showEditPostModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @click.self="showEditPostModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-edit text-indigo-600 ml-2"></i>
                    تعديل المنشور
                </h3>
                <button @click="showEditPostModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Platform Info -->
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                         :class="{
                             'bg-blue-100 text-blue-600': editingPost.platform === 'facebook',
                             'bg-gradient-to-br from-purple-100 to-pink-100 text-pink-600': editingPost.platform === 'instagram',
                             'bg-sky-100 text-sky-600': editingPost.platform === 'twitter',
                             'bg-blue-100 text-blue-700': editingPost.platform === 'linkedin'
                         }">
                        <i :class="{
                            'fab fa-facebook-f': editingPost.platform === 'facebook',
                            'fab fa-instagram': editingPost.platform === 'instagram',
                            'fab fa-twitter': editingPost.platform === 'twitter',
                            'fab fa-linkedin-in': editingPost.platform === 'linkedin'
                        }" class="text-lg"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white" x-text="editingPost.account_username || editingPost.platform"></p>
                        <p class="text-xs text-gray-500">
                            <span x-text="editingPost.platform"></span>
                            •
                            <span :class="{
                                'text-yellow-600': editingPost.status === 'scheduled',
                                'text-green-600': editingPost.status === 'published',
                                'text-gray-600': editingPost.status === 'draft',
                                'text-red-600': editingPost.status === 'failed'
                            }" x-text="getStatusLabel(editingPost.status)"></span>
                        </p>
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-pen ml-1"></i>
                        محتوى المنشور
                    </label>
                    <textarea x-model="editingPost.content" rows="5"
                              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-4 resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="محتوى المنشور..."></textarea>
                    <div class="text-xs text-gray-500 mt-1">
                        <span x-text="editingPost.content.length"></span> حرف
                    </div>
                </div>

                <!-- Current Media Preview -->
                <template x-if="editingPost.media && editingPost.media.length > 0">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-image ml-1"></i>
                            الوسائط الحالية
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            <template x-for="(media, index) in editingPost.media" :key="index">
                                <div class="relative">
                                    <template x-if="media.type === 'video'">
                                        <div class="relative">
                                            <video :src="media.url" class="w-full h-20 object-cover rounded-lg"></video>
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/30 rounded-lg">
                                                <i class="fas fa-play-circle text-white text-xl"></i>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="media.type === 'image' || !media.type">
                                        <img :src="media.url" class="w-full h-20 object-cover rounded-lg">
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Schedule (for draft/scheduled posts) -->
                <template x-if="editingPost.status === 'draft' || editingPost.status === 'scheduled'">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            <i class="fas fa-clock ml-1"></i>
                            وقت النشر المجدول
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">التاريخ</label>
                                <input type="date" x-model="editingPost.scheduledDate"
                                       :min="minDate"
                                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">الوقت</label>
                                <input type="time" x-model="editingPost.scheduledTime"
                                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-2">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <button @click="showEditPostModal = false"
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    إلغاء
                </button>
                <div class="flex gap-3">
                    <button @click="updatePost()"
                            :disabled="isUpdating || !editingPost.content.trim()"
                            class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isUpdating">
                            <i class="fas fa-save ml-2"></i>
                            حفظ التغييرات
                        </span>
                        <span x-show="isUpdating">
                            <i class="fas fa-spinner fa-spin ml-2"></i>
                            جاري الحفظ...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Settings Modal (Buffer-style) -->
    <div x-show="showQueueSettings" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @click.self="showQueueSettings = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold">
                            <i class="fas fa-cog ml-2"></i>
                            إعدادات طابور النشر
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">حدد أوقات النشر التلقائي لكل حساب</p>
                    </div>
                    <button @click="showQueueSettings = false" class="text-white/80 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-3xl mx-auto space-y-6">
                    <!-- Info Banner -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-blue-600 text-lg mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-blue-900">كيف يعمل طابور النشر؟</p>
                                <p class="text-sm text-blue-700 mt-1">
                                    عند تفعيل الطابور، سيتم جدولة المنشورات تلقائياً حسب الأوقات المحددة.
                                    يمكنك تحديد أوقات متعددة في اليوم وأيام الأسبوع المفضلة.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Queue Settings -->
                    <template x-for="platform in connectedPlatforms" :key="platform.id">
                        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                            <!-- Platform Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                         :class="{
                                             'bg-blue-100': platform.type === 'facebook',
                                             'bg-pink-100': platform.type === 'instagram',
                                             'bg-sky-100': platform.type === 'twitter',
                                             'bg-blue-100': platform.type === 'linkedin'
                                         }">
                                        <i :class="{
                                            'fab fa-facebook-f text-blue-600': platform.type === 'facebook',
                                            'fab fa-instagram text-pink-600': platform.type === 'instagram',
                                            'fab fa-twitter text-sky-500': platform.type === 'twitter',
                                            'fab fa-linkedin-in text-blue-700': platform.type === 'linkedin'
                                        }" class="text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900" x-text="platform.name"></h4>
                                        <p class="text-sm text-gray-500" x-text="platform.type"></p>
                                    </div>
                                </div>

                                <!-- Enable Toggle -->
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer"
                                           :checked="getQueueSetting(platform.integrationId, 'enabled')"
                                           @change="toggleQueue(platform.integrationId)">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                    <span class="mr-3 text-sm font-medium text-gray-700">تفعيل الطابور</span>
                                </label>
                            </div>

                            <!-- Queue Settings (shown when enabled) -->
                            <div x-show="getQueueSetting(platform.integrationId, 'enabled')" class="space-y-4 pt-4 border-t border-gray-100">
                                <!-- Posting Times -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-clock ml-1"></i>
                                        أوقات النشر اليومية
                                    </label>
                                    <div class="flex flex-wrap gap-2" x-data="{times: ['09:00', '13:00', '18:00']}">
                                        <template x-for="(time, index) in times" :key="index">
                                            <div class="flex items-center gap-1 bg-purple-50 border border-purple-200 rounded-lg px-3 py-1.5">
                                                <input type="time" x-model="times[index]"
                                                       class="border-0 bg-transparent text-sm text-gray-700 focus:ring-0">
                                                <button @click="times.splice(index, 1)" class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-times text-xs"></i>
                                                </button>
                                            </div>
                                        </template>
                                        <button @click="times.push('12:00')"
                                                class="px-3 py-1.5 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-600 hover:border-purple-400 hover:text-purple-600 transition">
                                            <i class="fas fa-plus ml-1"></i>
                                            إضافة وقت
                                        </button>
                                    </div>
                                </div>

                                <!-- Days of Week -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-week ml-1"></i>
                                        أيام النشر
                                    </label>
                                    <div class="flex flex-wrap gap-2" x-data="{days: [1, 2, 3, 4, 5]}">
                                        <template x-for="day in [{v: 0, l: 'أحد'}, {v: 1, l: 'إثنين'}, {v: 2, l: 'ثلاثاء'}, {v: 3, l: 'أربعاء'}, {v: 4, l: 'خميس'}, {v: 5, l: 'جمعة'}, {v: 6, l: 'سبت'}]" :key="day.v">
                                            <button @click="days.includes(day.v) ? days.splice(days.indexOf(day.v), 1) : days.push(day.v)"
                                                    :class="days.includes(day.v) ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-600'"
                                                    class="px-4 py-2 rounded-lg text-sm font-medium transition hover:shadow-md">
                                                <span x-text="day.l"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Posts Per Day -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-layer-group ml-1"></i>
                                        عدد المنشورات في اليوم
                                    </label>
                                    <input type="number" min="1" max="20" value="3"
                                           class="w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                <button @click="showQueueSettings = false"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    إغلاق
                </button>
                <button @click="saveAllQueueSettings()"
                        class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-medium hover:shadow-lg transition">
                    <i class="fas fa-save ml-2"></i>
                    حفظ الإعدادات
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function socialManager() {
    return {
        // Posts list state
        posts: [],
        filterPlatform: 'all',
        statusFilter: 'all',
        filterPostType: 'all',
        searchQuery: '',
        sortBy: 'newest',
        viewMode: 'grid',
        scheduledCount: 0,
        publishedCount: 0,
        draftCount: 0,
        failedCount: 0,
        selectedPosts: [],

        // Calendar state
        currentDate: new Date(),

        // New post modal state
        showNewPostModal: false,
        loadingPlatforms: false,
        connectedPlatforms: [],
        selectedPlatformIds: [],
        isSubmitting: false,
        dragOver: false,
        uploadedMedia: [],
        showAiAssistant: false,
        previewPlatform: 'facebook',
        newPost: {
            content: '',
            publishType: 'now',
            scheduledDate: '',
            scheduledTime: '',
            postType: 'feed', // Default post type
            firstComment: '', // First comment for Instagram/Facebook
            location: '', // Location tag
            locationId: null, // Location ID for API
        },

        // Input helper variables
        collaboratorInput: '',
        productTagInput: '',
        userTagInput: '',

        // Location autocomplete state
        locationQuery: '',
        locationResults: [],
        showLocationDropdown: false,
        isSearchingLocations: false,
        selectedLocation: null,
        locationSearchTimeout: null,

        // Collaborator suggestions state
        collaboratorSuggestions: [],
        showCollaboratorSuggestions: false,
        isValidatingUsername: false,
        usernameValidationResult: null,
        usernameValidationTimeout: null,
        validatedUserInfo: null,

        // Product details state
        showProductDetails: false,
        currencies: [
            { code: 'SAR', symbol: 'ر.س', name: 'ريال سعودي' },
            { code: 'AED', symbol: 'د.إ', name: 'درهم إماراتي' },
            { code: 'USD', symbol: '$', name: 'دولار أمريكي' },
            { code: 'EUR', symbol: '€', name: 'يورو' },
            { code: 'GBP', symbol: '£', name: 'جنيه إسترليني' },
            { code: 'EGP', symbol: 'ج.م', name: 'جنيه مصري' },
            { code: 'KWD', symbol: 'د.ك', name: 'دينار كويتي' },
            { code: 'QAR', symbol: 'ر.ق', name: 'ريال قطري' },
            { code: 'BHD', symbol: 'د.ب', name: 'دينار بحريني' },
            { code: 'OMR', symbol: 'ر.ع', name: 'ريال عماني' },
        ],

        // Post type specific options (API-supported only)
        postOptions: {
            // Instagram/Facebook API-Supported Options
            instagram: {
                location: '', // Location name (API: location_id)
                locationId: '', // Facebook Places ID
                userTags: [], // Tagged users [{username, x, y}] (API: user_tags)
                collaborators: [], // Collaborators up to 3 (API: collaborators)
                altText: '', // Alt text for accessibility (API: alt_text)
                firstComment: '', // Auto-post first comment (API: /comments endpoint)
            },

            // Product Details (for DM-based orders - no Instagram Shopping required)
            product: {
                enabled: false,
                title: '',
                price: '',
                currency: 'SAR',
                description: '',
                orderMessage: 'للطلب، أرسل رسالة مباشرة 📩', // Default CTA
            },

            // Reel API-Supported Options
            reel: {
                coverType: 'frame', // 'frame' or 'custom'
                coverFrameOffset: 0, // milliseconds (API: thumb_offset)
                coverImageUrl: '', // custom cover (API: cover_url)
                shareToFeed: true, // Show reel in feed (API: share_to_feed)
            },

            // Story Options (limited API support)
            story: {
                duration: 5, // seconds per slide (for reference only)
            },

            // Carousel API-Supported Options
            carousel: {
                altTexts: [], // Alt text for each image (API: alt_text per child)
            },

            // TikTok Content Posting API Options
            tiktok: {
                viewerSetting: 'public', // API: privacy_level (PUBLIC_TO_EVERYONE, MUTUAL_FOLLOW_FRIENDS, SELF_ONLY)
                disableComments: false, // API: disable_comment
                disableDuet: false, // API: disable_duet
                disableStitch: false, // API: disable_stitch
                brandContentToggle: false, // API: brand_content_toggle
                aiGenerated: false, // API: ai_disclosure
            },

            // LinkedIn Posts API Options
            linkedin: {
                visibility: 'PUBLIC', // API: visibility (PUBLIC, CONNECTIONS)
                articleTitle: '', // API: article title
                articleDescription: '', // API: article description
                allowComments: true, // API: allowComments
            },

            // Twitter/X API Options
            twitter: {
                threadTweets: [''], // Multiple tweets for thread
                replyRestriction: 'everyone', // API: reply_settings (everyone, mentionedUsers, following)
                altText: '', // API: alt_text for media
            },
        },

        // Post types configuration
        allPostTypes: {
            'facebook': [
                {value: 'feed', label: 'منشور عادي (Feed Post)', icon: 'fa-newspaper'},
                {value: 'reel', label: 'ريل (Reel)', icon: 'fa-video'},
                {value: 'story', label: 'قصة (Story)', icon: 'fa-circle'}
            ],
            'instagram': [
                {value: 'feed', label: 'منشور عادي (Feed Post)', icon: 'fa-image'},
                {value: 'reel', label: 'ريل (Reel)', icon: 'fa-video'},
                {value: 'story', label: 'قصة (Story)', icon: 'fa-circle'},
                {value: 'carousel', label: 'كاروسيل (Carousel)', icon: 'fa-images'}
            ],
            'twitter': [
                {value: 'tweet', label: 'تغريدة (Tweet)', icon: 'fa-comment'},
                {value: 'thread', label: 'سلسلة (Thread)', icon: 'fa-list'}
            ],
            'linkedin': [
                {value: 'post', label: 'منشور (Post)', icon: 'fa-file-alt'},
                {value: 'article', label: 'مقال (Article)', icon: 'fa-newspaper'}
            ]
        },

        // Queue settings modal
        showQueueSettings: false,
        queueSettings: [],

        // Best times suggestions
        bestTimes: [
            { label: 'صباحاً', value: '09:00', engagement: '+23%' },
            { label: 'ظهراً', value: '12:00', engagement: '+18%' },
            { label: 'مساءً', value: '18:00', engagement: '+31%' },
            { label: 'ليلاً', value: '21:00', engagement: '+15%' }
        ],

        // Edit post modal state
        showEditPostModal: false,
        editingPost: {
            id: null,
            content: '',
            platform: '',
            status: '',
            scheduled_at: null,
            media: [],
            account_username: ''
        },
        isUpdating: false,
        isDeletingFailed: false,

        // Get the org ID from the URL
        get orgId() {
            const match = window.location.pathname.match(/\/orgs\/([^\/]+)/);
            return match ? match[1] : null;
        },

        // Minimum date for scheduling (today)
        get minDate() {
            return new Date().toISOString().split('T')[0];
        },

        // Check platform selections
        get hasInstagramSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'instagram'
            );
        },

        get hasTwitterSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'twitter'
            );
        },

        get hasFacebookSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'facebook'
            );
        },

        get hasTikTokSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'tiktok'
            );
        },

        get hasLinkedInSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'linkedin'
            );
        },

        // Get available post types based on selected platforms
        get availablePostTypes() {
            if (this.selectedPlatformIds.length === 0) {
                return [];
            }

            // Get unique platforms from selected platform IDs
            const selectedPlatforms = this.connectedPlatforms
                .filter(p => this.selectedPlatformIds.includes(p.id))
                .map(p => p.type);

            const uniquePlatforms = [...new Set(selectedPlatforms)];

            // If multiple platforms selected, show common post types
            if (uniquePlatforms.length > 1) {
                // Find common post types across all selected platforms
                const platformPostTypes = uniquePlatforms.map(platform =>
                    this.allPostTypes[platform] || []
                );

                // Get the intersection of all post types (common across all platforms)
                if (platformPostTypes.length === 0) {
                    return [{value: 'feed', label: 'منشور عادي (Feed Post)', icon: 'fa-newspaper'}];
                }

                // Find post types that exist in all platforms
                const commonPostTypes = platformPostTypes[0].filter(postType =>
                    platformPostTypes.every(types =>
                        types.some(t => t.value === postType.value)
                    )
                );

                // If no common types, default to feed
                return commonPostTypes.length > 0
                    ? commonPostTypes
                    : [{value: 'feed', label: 'منشور عادي (Feed Post)', icon: 'fa-newspaper'}];
            }

            // Single platform selected, return its specific post types
            const platform = uniquePlatforms[0];
            return this.allPostTypes[platform] || [{value: 'feed', label: 'منشور عادي', icon: 'fa-newspaper'}];
        },

        get selectedPlatformsForPreview() {
            return this.connectedPlatforms.filter(p => this.selectedPlatformIds.includes(p.id));
        },

        // Can submit the form
        get canSubmit() {
            const hasContent = this.newPost.content.trim().length > 0;
            const hasPlatforms = this.selectedPlatformIds.length > 0;
            const hasScheduleIfNeeded = this.newPost.publishType !== 'scheduled' ||
                                        (this.newPost.scheduledDate && this.newPost.scheduledTime);
            const hasMediaForInstagram = !this.hasInstagramSelected || this.uploadedMedia.length > 0;
            return hasContent && hasPlatforms && hasScheduleIfNeeded && hasMediaForInstagram;
        },

        // Unique platforms from posts (for dynamic platform filter)
        get uniquePlatforms() {
            const platforms = [...new Set(this.posts.map(p => p.platform).filter(Boolean))];
            return platforms.sort();
        },

        // Helper methods for platform display
        getPlatformIcon(platform) {
            const icons = {
                'facebook': 'fab fa-facebook',
                'instagram': 'fab fa-instagram',
                'twitter': 'fab fa-twitter',
                'x': 'fab fa-x-twitter',
                'linkedin': 'fab fa-linkedin',
                'youtube': 'fab fa-youtube',
                'tiktok': 'fab fa-tiktok',
                'pinterest': 'fab fa-pinterest',
                'reddit': 'fab fa-reddit',
                'tumblr': 'fab fa-tumblr',
                'threads': 'fab fa-at',
                'google_business': 'fab fa-google'
            };
            return icons[platform] || 'fas fa-share-alt';
        },

        getPlatformName(platform) {
            const names = {
                'facebook': 'Facebook',
                'instagram': 'Instagram',
                'twitter': 'Twitter',
                'x': 'X',
                'linkedin': 'LinkedIn',
                'youtube': 'YouTube',
                'tiktok': 'TikTok',
                'pinterest': 'Pinterest',
                'reddit': 'Reddit',
                'tumblr': 'Tumblr',
                'threads': 'Threads',
                'google_business': 'Google Business'
            };
            return names[platform] || platform;
        },

        getPlatformFilterClass(platform, active) {
            if (!active) return 'bg-gray-100 text-gray-700 hover:bg-gray-200';
            const classes = {
                'facebook': 'bg-blue-600 text-white',
                'instagram': 'bg-gradient-to-r from-purple-600 to-pink-600 text-white',
                'twitter': 'bg-sky-500 text-white',
                'x': 'bg-black text-white',
                'linkedin': 'bg-blue-700 text-white',
                'youtube': 'bg-red-600 text-white',
                'tiktok': 'bg-black text-white',
                'pinterest': 'bg-red-700 text-white',
                'reddit': 'bg-orange-600 text-white',
                'tumblr': 'bg-indigo-800 text-white',
                'threads': 'bg-black text-white',
                'google_business': 'bg-blue-500 text-white'
            };
            return classes[platform] || 'bg-gray-600 text-white';
        },

        // Sorted and filtered posts
        get sortedFilteredPosts() {
            let filtered = this.posts.filter(post => {
                const platformMatch = this.filterPlatform === 'all' || post.platform === this.filterPlatform;
                const statusMatch = this.statusFilter === 'all' || post.status === this.statusFilter;
                const postTypeMatch = this.filterPostType === 'all' || post.post_type === this.filterPostType;
                const searchMatch = !this.searchQuery ||
                    (post.post_text && post.post_text.toLowerCase().includes(this.searchQuery.toLowerCase()));
                return platformMatch && statusMatch && postTypeMatch && searchMatch;
            });

            // Sort
            switch(this.sortBy) {
                case 'oldest':
                    filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                    break;
                case 'scheduled':
                    filtered.sort((a, b) => {
                        if (!a.scheduled_at) return 1;
                        if (!b.scheduled_at) return -1;
                        return new Date(a.scheduled_at) - new Date(b.scheduled_at);
                    });
                    break;
                case 'platform':
                    filtered.sort((a, b) => (a.platform || '').localeCompare(b.platform || ''));
                    break;
                default: // newest
                    filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            }

            return filtered;
        },

        // Calendar helpers
        get currentMonthYear() {
            return this.currentDate.toLocaleDateString('ar-SA', { month: 'long', year: 'numeric' });
        },

        get calendarDays() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const days = [];

            // Previous month days
            const firstDayOfWeek = firstDay.getDay();
            for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                const date = new Date(year, month, -i);
                days.push(this.createDayObject(date, false));
            }

            // Current month days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                days.push(this.createDayObject(date, true));
            }

            // Next month days to fill grid
            const remaining = 42 - days.length;
            for (let i = 1; i <= remaining; i++) {
                const date = new Date(year, month + 1, i);
                days.push(this.createDayObject(date, false));
            }

            return days;
        },

        createDayObject(date, isCurrentMonth) {
            const dateStr = date.toISOString().split('T')[0];
            const today = new Date().toISOString().split('T')[0];
            return {
                date: dateStr,
                dayNumber: date.getDate(),
                isCurrentMonth,
                isToday: dateStr === today,
                posts: this.posts.filter(p => {
                    const postDate = p.scheduled_at || p.published_at || p.created_at;
                    return postDate && postDate.startsWith(dateStr);
                })
            };
        },

        changeMonth(delta) {
            this.currentDate = new Date(
                this.currentDate.getFullYear(),
                this.currentDate.getMonth() + delta,
                1
            );
        },

        async init() {
            // Debug: v2025.11.26.1040 - Added dynamic filters
            console.log('[CMIS Social] v1.0.2 - Initializing, orgId:', this.orgId);

            // Load posts, connected platforms, and collaborator suggestions in parallel
            await Promise.all([
                this.fetchPosts(),
                this.loadConnectedPlatforms(),
                this.loadCollaboratorSuggestions()
            ]);
            console.log('[CMIS Social] Posts loaded:', this.posts.length, 'posts');
            console.log('[CMIS Social] Platforms loaded:', this.connectedPlatforms.length, 'platforms');
            console.log('[CMIS Social] Collaborator suggestions loaded:', this.collaboratorSuggestions.length);

            // Set default schedule time to tomorrow 10 AM
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = '10:00';

            // Watch for modal open to load platforms
            this.$watch('showNewPostModal', async (value) => {
                if (value && this.connectedPlatforms.length === 0) {
                    await this.loadConnectedPlatforms();
                }
                if (value && this.selectedPlatformIds.length > 0) {
                    this.previewPlatform = this.connectedPlatforms.find(p =>
                        this.selectedPlatformIds.includes(p.id)
                    )?.type || 'facebook';
                }
            });

            // Update preview platform when selection changes
            this.$watch('selectedPlatformIds', (ids) => {
                if (ids.length > 0) {
                    const platform = this.connectedPlatforms.find(p => ids.includes(p.id));
                    if (platform) this.previewPlatform = platform.type;
                }
            });
        },

        async fetchPosts() {
            console.log('[CMIS Social] fetchPosts() called, orgId:', this.orgId);
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                console.log('[CMIS Social] API response status:', response.status);
                const result = await response.json();
                console.log('[CMIS Social] API result:', result);

                if (result.success && result.data) {
                    this.posts = result.data.data || result.data || [];
                    console.log('[CMIS Social] Posts set from result.data.data:', this.posts.length);
                } else if (Array.isArray(result.data)) {
                    this.posts = result.data;
                    console.log('[CMIS Social] Posts set from result.data array:', this.posts.length);
                } else {
                    this.posts = [];
                    console.log('[CMIS Social] Posts set to empty array');
                }
                this.updateCounts();
            } catch (error) {
                console.error('[CMIS Social] Failed to fetch posts:', error);
                this.posts = [];
            }
        },

        async loadConnectedPlatforms() {
            this.loadingPlatforms = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/accounts`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await response.json();

                if (data.success && data.data?.accounts) {
                    this.connectedPlatforms = data.data.accounts.map(account => ({
                        id: account.id,
                        type: account.type,
                        name: account.name,
                        platformId: account.platformId,
                        picture: account.picture,
                        username: account.username,
                        connectionId: account.connectionId,
                        pageId: account.type === 'facebook' ? account.platformId : null,
                        accountId: account.type === 'instagram' ? account.platformId : null,
                    }));
                } else {
                    this.connectedPlatforms = [];
                }
            } catch (error) {
                console.error('Failed to load platforms:', error);
                this.connectedPlatforms = [];
            } finally {
                this.loadingPlatforms = false;
            }
        },

        togglePlatformSelection(platform) {
            const index = this.selectedPlatformIds.indexOf(platform.id);
            if (index === -1) {
                this.selectedPlatformIds.push(platform.id);
            } else {
                this.selectedPlatformIds.splice(index, 1);
            }
        },

        togglePostSelection(postId) {
            const index = this.selectedPosts.indexOf(postId);
            if (index === -1) {
                this.selectedPosts.push(postId);
            } else {
                this.selectedPosts.splice(index, 1);
            }
        },

        toggleAllPosts(event) {
            if (event.target.checked) {
                this.selectedPosts = this.sortedFilteredPosts.map(p => p.post_id);
            } else {
                this.selectedPosts = [];
            }
        },

        async bulkDelete() {
            if (!confirm(`هل أنت متأكد من حذف ${this.selectedPosts.length} منشور؟`)) return;

            for (const postId of this.selectedPosts) {
                await this.deletePost(postId, false);
            }
            this.selectedPosts = [];
            await this.fetchPosts();
            if (window.notify) {
                window.notify('تم حذف المنشورات بنجاح', 'success');
            }
        },

        setBestTime(time) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = time.value;
        },

        async aiSuggest(type) {
            const content = this.newPost.content;
            if (!content) {
                if (window.notify) {
                    window.notify('اكتب محتوى أولاً', 'warning');
                }
                return;
            }

            // Show loading state
            const loadingMessage = {
                'shorter': 'جاري الاختصار...',
                'longer': 'جاري التوسع...',
                'formal': 'جاري تحويل الأسلوب...',
                'casual': 'جاري تحويل الأسلوب...',
                'hashtags': 'جاري إنشاء الهاشتاقات...',
                'emojis': 'جاري إضافة الإيموجي...',
            }[type] || 'جاري المعالجة...';

            if (window.notify) {
                window.notify(loadingMessage, 'info');
            }

            // Disable the button temporarily
            const originalContent = this.newPost.content;

            try {
                // Call the AI API
                const response = await fetch(`/orgs/${this.orgId}/social/ai/transform-content`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content: content,
                        type: type,
                        platform: 'general'
                    })
                });

                const data = await response.json();

                if (response.ok && data.success && data.data.transformed) {
                    this.newPost.content = data.data.transformed;
                    if (window.notify) {
                        window.notify('تم التحويل بنجاح!', 'success');
                    }
                } else {
                    // Show detailed validation errors for debugging
                    let errorMessage = data.message || 'فشل التحويل';
                    if (data.errors) {
                        const errorDetails = Object.values(data.errors).flat().join(', ');
                        errorMessage += ': ' + errorDetails;
                    }
                    throw new Error(errorMessage);
                }
            } catch (error) {
                console.error('AI transformation error:', error);
                if (window.notify) {
                    window.notify('حدث خطأ في مساعد AI: ' + error.message, 'error');
                }
                // Restore original content on error
                this.newPost.content = originalContent;
            }
        },

        duplicatePost(post) {
            this.newPost.content = post.post_text || post.content || '';
            this.showNewPostModal = true;
            if (window.notify) {
                window.notify('تم نسخ المحتوى - يمكنك تعديله ونشره', 'success');
            }
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.processFiles(files);
        },

        handleFileDrop(event) {
            this.dragOver = false;
            const files = Array.from(event.dataTransfer.files);
            this.processFiles(files);
        },

        processFiles(files) {
            files.forEach(file => {
                if (file.type.startsWith('image/') || file.type.startsWith('video/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.uploadedMedia.push({
                            file: file,
                            preview: e.target.result,
                            type: file.type.startsWith('image/') ? 'image' : 'video'
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        removeMedia(index) {
            this.uploadedMedia.splice(index, 1);
        },

        // Location Search Methods
        searchLocations() {
            // Clear previous timeout
            if (this.locationSearchTimeout) {
                clearTimeout(this.locationSearchTimeout);
            }

            // Don't search if query is too short
            if (this.locationQuery.length < 2) {
                this.locationResults = [];
                this.showLocationDropdown = false;
                return;
            }

            // Debounce search by 300ms
            this.locationSearchTimeout = setTimeout(async () => {
                this.isSearchingLocations = true;
                try {
                    const response = await fetch(`/api/orgs/${this.orgId}/social/locations?query=${encodeURIComponent(this.locationQuery)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        }
                    });

                    const result = await response.json();

                    if (response.ok && result.data) {
                        this.locationResults = result.data;
                        this.showLocationDropdown = true;
                    } else {
                        this.locationResults = [];
                        console.warn('Location search failed:', result.message);
                    }
                } catch (error) {
                    console.error('Location search error:', error);
                    this.locationResults = [];
                } finally {
                    this.isSearchingLocations = false;
                }
            }, 300);
        },

        selectLocation(location) {
            this.selectedLocation = location;
            this.postOptions.instagram.location = location.name;
            this.postOptions.instagram.locationId = location.id;
            this.locationQuery = '';
            this.locationResults = [];
            this.showLocationDropdown = false;
        },

        clearLocation() {
            this.selectedLocation = null;
            this.postOptions.instagram.location = '';
            this.postOptions.instagram.locationId = '';
            this.locationQuery = '';
        },

        // Collaborator Methods
        async loadCollaboratorSuggestions() {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/collaborators/suggestions`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    this.collaboratorSuggestions = result.data?.collaborators || [];
                }
            } catch (error) {
                console.error('Failed to load collaborator suggestions:', error);
            }
        },

        get filteredCollaboratorSuggestions() {
            const input = this.collaboratorInput?.replace('@', '').toLowerCase() || '';
            const existing = this.postOptions.instagram.collaborators.map(c => c.replace('@', '').toLowerCase());

            return this.collaboratorSuggestions.filter(s => {
                const lowerS = s.toLowerCase();
                return !existing.includes(lowerS) &&
                       (input === '' || lowerS.includes(input));
            }).slice(0, 5);
        },

        searchCollaborators() {
            // Show suggestions dropdown when typing
            this.showCollaboratorSuggestions = true;
            this.usernameValidationResult = null;

            // Debounce validation
            if (this.usernameValidationTimeout) {
                clearTimeout(this.usernameValidationTimeout);
            }

            const username = this.collaboratorInput?.replace('@', '').trim();
            if (username && username.length >= 2) {
                this.usernameValidationTimeout = setTimeout(() => {
                    this.validateUsername(username);
                }, 800);
            }
        },

        async validateUsername(username) {
            if (!username || username.length < 2) return;

            this.isValidatingUsername = true;
            this.usernameValidationResult = null;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/instagram/validate-username`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({ username: username })
                });

                if (response.ok) {
                    const result = await response.json();
                    this.usernameValidationResult = result.data?.valid || false;
                    this.validatedUserInfo = result.data?.user || null;
                } else {
                    this.usernameValidationResult = null;
                }
            } catch (error) {
                console.error('Username validation failed:', error);
                this.usernameValidationResult = null;
            } finally {
                this.isValidatingUsername = false;
            }
        },

        async addCollaborator(username) {
            if (!username) return;

            // Clean the username
            username = username.replace('@', '').trim();
            if (!username) return;

            // Check if already added
            const existing = this.postOptions.instagram.collaborators.map(c => c.replace('@', '').toLowerCase());
            if (existing.includes(username.toLowerCase())) {
                this.collaboratorInput = '';
                this.showCollaboratorSuggestions = false;
                return;
            }

            // Check limit (max 3)
            if (this.postOptions.instagram.collaborators.length >= 3) {
                if (window.notify) {
                    window.notify('يمكنك إضافة 3 متعاونين كحد أقصى', 'warning');
                }
                return;
            }

            // Add to list
            this.postOptions.instagram.collaborators.push(username);
            this.collaboratorInput = '';
            this.showCollaboratorSuggestions = false;
            this.usernameValidationResult = null;

            // Store for future suggestions (async, don't wait)
            fetch(`/api/orgs/${this.orgId}/social/collaborators`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ username: username })
            }).catch(() => {}); // Silently ignore errors
        },

        async savePost() {
            if (!this.canSubmit || this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                const formData = new FormData();
                formData.append('content', this.newPost.content);
                formData.append('publish_type', this.newPost.publishType);
                formData.append('post_type', this.newPost.postType); // Add post type

                const selectedPlatforms = this.connectedPlatforms.filter(p =>
                    this.selectedPlatformIds.includes(p.id)
                );
                formData.append('platforms', JSON.stringify(selectedPlatforms));

                // Add all post options as JSON
                formData.append('post_options', JSON.stringify(this.postOptions));

                // Add location if set
                if (this.newPost.location) {
                    formData.append('location', this.newPost.location);
                }

                // Add first comment if set
                if (this.newPost.firstComment) {
                    formData.append('first_comment', this.newPost.firstComment);
                }

                if (this.newPost.publishType === 'scheduled') {
                    formData.append('scheduled_at', `${this.newPost.scheduledDate}T${this.newPost.scheduledTime}:00`);
                }

                this.uploadedMedia.forEach((media, index) => {
                    formData.append(`media[${index}]`, media.file);
                });

                const response = await fetch(`/api/orgs/${this.orgId}/social/posts`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    const messages = {
                        'now': 'تم نشر المنشور بنجاح!',
                        'scheduled': 'تم جدولة المنشور بنجاح!',
                        'queue': 'تم إضافة المنشور للطابور بنجاح!',
                        'draft': 'تم حفظ المسودة بنجاح!'
                    };
                    if (window.notify) {
                        window.notify(messages[this.newPost.publishType], 'success');
                    }
                    this.showNewPostModal = false;
                    this.resetNewPost();
                    await this.fetchPosts();
                } else {
                    throw new Error(result.message || 'فشل في حفظ المنشور');
                }
            } catch (error) {
                console.error('Failed to save post:', error);
                if (window.notify) {
                    window.notify(error.message || 'فشل في حفظ المنشور', 'error');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        resetNewPost() {
            this.newPost = {
                content: '',
                publishType: 'now',
                scheduledDate: '',
                scheduledTime: ''
            };
            this.selectedPlatformIds = [];
            this.uploadedMedia = [];
            this.showAiAssistant = false;

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = '10:00';
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
            if (!date) return '';
            return new Date(date).toLocaleString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        },

        editPost(post) {
            this.editingPost = {
                id: post.post_id || post.id,
                content: post.content || post.post_text || '',
                platform: post.platform,
                status: post.status,
                scheduled_at: post.scheduled_at,
                media: post.media || [],
                account_username: post.account_username,
                scheduledDate: '',
                scheduledTime: ''
            };

            if (post.scheduled_at) {
                const scheduled = new Date(post.scheduled_at);
                this.editingPost.scheduledDate = scheduled.toISOString().split('T')[0];
                this.editingPost.scheduledTime = scheduled.toTimeString().slice(0, 5);
            }

            this.showEditPostModal = true;
        },

        async updatePost() {
            if (this.isUpdating || !this.editingPost.content.trim()) return;
            this.isUpdating = true;

            try {
                const updateData = {
                    content: this.editingPost.content,
                };

                if ((this.editingPost.status === 'draft' || this.editingPost.status === 'scheduled')
                    && this.editingPost.scheduledDate && this.editingPost.scheduledTime) {
                    updateData.scheduled_at = `${this.editingPost.scheduledDate}T${this.editingPost.scheduledTime}:00`;
                    updateData.status = 'scheduled';
                }

                const response = await fetch(`/orgs/${this.orgId}/social/posts/${this.editingPost.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(updateData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.showEditPostModal = false;
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify('تم تحديث المنشور بنجاح', 'success');
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || 'فشل تحديث المنشور', 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to update post:', error);
                if (window.notify) {
                    window.notify('فشل تحديث المنشور', 'error');
                }
            } finally {
                this.isUpdating = false;
            }
        },

        async publishNow(postId) {
            if (!confirm('هل تريد نشر هذا المنشور الآن؟')) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify('تم نشر المنشور بنجاح', 'success');
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || 'فشل نشر المنشور', 'error');
                    }
                    await this.fetchPosts();
                }
            } catch (error) {
                console.error('Failed to publish post:', error);
                if (window.notify) {
                    window.notify('فشل نشر المنشور', 'error');
                }
            }
        },

        async retryPost(postId) {
            if (!confirm('هل تريد إعادة محاولة نشر هذا المنشور؟')) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify('تم نشر المنشور بنجاح!', 'success');
                    }
                } else {
                    if (window.notify) {
                        window.notify('فشلت إعادة المحاولة: ' + (result.message || ''), 'error');
                    }
                    await this.fetchPosts();
                }
            } catch (error) {
                console.error('Failed to retry post:', error);
                if (window.notify) {
                    window.notify('فشلت إعادة المحاولة', 'error');
                }
            }
        },

        async deletePost(postId, showConfirm = true) {
            if (showConfirm && !confirm('هل أنت متأكد من حذف هذا المنشور؟')) return;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (showConfirm) {
                        await this.fetchPosts();
                        if (window.notify) {
                            window.notify('تم حذف المنشور بنجاح', 'success');
                        }
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || 'فشل حذف المنشور', 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to delete post:', error);
                if (window.notify) {
                    window.notify('فشل حذف المنشور', 'error');
                }
            }
        },

        async deleteAllFailed() {
            if (!confirm(`هل أنت متأكد من حذف جميع المنشورات الفاشلة (${this.failedCount})؟`)) return;

            this.isDeletingFailed = true;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts-failed`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const deletedCount = result.data?.deleted_count || 0;
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(`تم حذف ${deletedCount} منشور فاشل بنجاح`, 'success');
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || 'فشل حذف المنشورات الفاشلة', 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to delete all failed posts:', error);
                if (window.notify) {
                    window.notify('فشل حذف المنشورات الفاشلة', 'error');
                }
            } finally {
                this.isDeletingFailed = false;
            }
        },

        // Queue Settings Methods
        getQueueSetting(integrationId, key) {
            const setting = this.queueSettings.find(s => s.integration_id === integrationId);
            if (!setting) return key === 'enabled' ? false : null;

            switch(key) {
                case 'enabled': return setting.queue_enabled;
                case 'times': return setting.posting_times || [];
                case 'days': return setting.days_enabled || [1,2,3,4,5];
                case 'count': return setting.posts_per_day || 3;
                default: return null;
            }
        },

        toggleQueue(integrationId) {
            const setting = this.queueSettings.find(s => s.integration_id === integrationId);
            if (setting) {
                setting.queue_enabled = !setting.queue_enabled;
            } else {
                // Create new setting with defaults
                this.queueSettings.push({
                    integration_id: integrationId,
                    queue_enabled: true,
                    posting_times: ['09:00', '13:00', '18:00'],
                    days_enabled: [1, 2, 3, 4, 5],
                    posts_per_day: 3
                });
            }
        },

        async saveAllQueueSettings() {
            try {
                // Save each platform's queue settings
                const promises = this.queueSettings.map(setting => {
                    return fetch(`/orgs/${this.orgId}/social/queue-settings`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            integration_id: setting.integration_id,
                            queue_enabled: setting.queue_enabled,
                            posting_times: setting.posting_times,
                            days_enabled: setting.days_enabled,
                            posts_per_day: setting.posts_per_day
                        })
                    });
                });

                await Promise.all(promises);

                this.showQueueSettings = false;
                if (window.notify) {
                    window.notify('تم حفظ إعدادات الطابور بنجاح', 'success');
                }
            } catch (error) {
                console.error('Failed to save queue settings:', error);
                if (window.notify) {
                    window.notify('فشل في حفظ الإعدادات', 'error');
                }
            }
        }
    };
}
</script>
@endpush
