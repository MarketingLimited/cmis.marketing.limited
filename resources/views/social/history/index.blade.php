@extends('layouts.admin')

@section('title', 'المحتوى التاريخي')

@section('content')
<div x-data="historicalContentManager()" x-init="init()" class="container mx-auto px-4 py-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">المحتوى التاريخي و قاعدة المعرفة</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">استيراد وتحليل المحتوى الاجتماعي التاريخي لبناء قاعدة معرفة علامتك التجارية</p>
        </div>
        <div class="flex gap-3">
            <button @click="showImportModal = true" class="btn btn-primary">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                استيراد المنشورات
            </button>
            <button @click="showKBModal = true" class="btn btn-secondary">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                قاعدة المعرفة
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">إجمالي المستوردة</p>
                    <p class="text-2xl font-semibold dark:text-white" x-text="stats.totalImported">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">تم تحليلها</p>
                    <p class="text-2xl font-semibold dark:text-white" x-text="stats.totalAnalyzed">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">في قاعدة المعرفة</p>
                    <p class="text-2xl font-semibold dark:text-white" x-text="stats.inKB">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">عالية الأداء</p>
                    <p class="text-2xl font-semibold dark:text-white" x-text="stats.highPerformers">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">الفلاتر</h3>
                <button @click="resetFilters()" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    إعادة تعيين الفلاتر
                </button>
            </div>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Profile Group Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">مجموعة الملف الشخصي</label>
                    <select x-model="filters.profile_group_id" @change="loadPosts()" class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">جميع المجموعات</option>
                        <template x-for="group in profileGroups" :key="group.id">
                            <option :value="group.id" x-text="group.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Platform Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">المنصة</label>
                    <select x-model="filters.platform" @change="loadPosts()" class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">جميع المنصات</option>
                        <option value="instagram">إنستغرام</option>
                        <option value="facebook">فيسبوك</option>
                        <option value="twitter">تويتر</option>
                        <option value="linkedin">لينكد إن</option>
                        <option value="tiktok">تيك توك</option>
                    </select>
                </div>

                <!-- Analysis Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">حالة التحليل</label>
                    <select x-model="filters.is_analyzed" @change="loadPosts()" class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">جميع المنشورات</option>
                        <option value="1">تم التحليل</option>
                        <option value="0">بانتظار التحليل</option>
                    </select>
                </div>

                <!-- KB Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">حالة قاعدة المعرفة</label>
                    <select x-model="filters.is_in_kb" @change="loadPosts()" class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">جميع المنشورات</option>
                        <option value="1">في قاعدة المعرفة</option>
                        <option value="0">ليست في قاعدة المعرفة</option>
                    </select>
                </div>

                <!-- Success Score -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الحد الأدنى لدرجة النجاح</label>
                    <input type="range" x-model="filters.min_success_score" @change="loadPosts()" min="0" max="1" step="0.1" class="w-full">
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <span>0</span>
                        <span x-text="filters.min_success_score"></span>
                        <span>1.0</span>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">البحث في المحتوى</label>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce.500ms="loadPosts()"
                    placeholder="ابحث في محتوى المنشورات..."
                    class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
            </div>
        </div>
    </div>

    <!-- Posts Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">المنشورات التاريخية</h3>
                <span class="text-sm text-gray-600 dark:text-gray-400" x-text="`${posts.length} منشور`"></span>
            </div>
            <div class="flex gap-2">
                <button @click="selectAll()" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    تحديد الكل
                </button>
                <button @click="clearSelection()" class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300">
                    إلغاء التحديد
                </button>
                <button
                    @click="bulkAddToKB()"
                    x-show="selectedPosts.length > 0"
                    class="btn btn-sm btn-primary mr-2"
                >
                    إضافة <span x-text="selectedPosts.length"></span> إلى قاعدة المعرفة
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="text-gray-600 dark:text-gray-400 mt-2">جاري تحميل المنشورات...</p>
        </div>

        <!-- Posts List -->
        <div x-show="!loading && posts.length > 0" class="divide-y divide-gray-200">
            <template x-for="post in posts" :key="post.id">
                <div class="p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex gap-4">
                        <!-- Checkbox -->
                        <div class="flex-shrink-0 pt-1">
                            <input
                                type="checkbox"
                                :value="post.id"
                                x-model="selectedPosts"
                                class="form-checkbox h-5 w-5 text-blue-600 rounded"
                            >
                        </div>

                        <!-- Post Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                              :class="{
                                                  'bg-blue-100 text-blue-800': post.platform === 'instagram',
                                                  'bg-indigo-100 text-indigo-800': post.platform === 'facebook',
                                                  'bg-sky-100 text-sky-800': post.platform === 'twitter',
                                                  'bg-purple-100 text-purple-800': post.platform === 'linkedin',
                                                  'bg-pink-100 text-pink-800': post.platform === 'tiktok'
                                              }"
                                              x-text="post.platform?.toUpperCase()">
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(post.published_at)"></span>
                                        <span x-show="post.is_in_knowledge_base" class="px-2 py-1 text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full">
                                            في قاعدة المعرفة
                                        </span>
                                    </div>
                                    <p class="text-gray-900 dark:text-gray-100 text-sm line-clamp-3" x-text="post.content"></p>

                                    <!-- Success Score -->
                                    <div x-show="post.is_analyzed" class="mt-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-600 dark:text-gray-400">درجة النجاح:</span>
                                            <div class="flex-1 max-w-xs bg-gray-200 rounded-full h-2">
                                                <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full"
                                                     :style="`width: ${(post.success_score || 0) * 100}%`">
                                                </div>
                                            </div>
                                            <span class="text-xs font-semibold" x-text="((post.success_score || 0) * 100).toFixed(0) + '%'"></span>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="{
                                                      'bg-green-100 text-green-800': post.success_label === 'high_performer',
                                                      'bg-yellow-100 text-yellow-800': post.success_label === 'average',
                                                      'bg-red-100 text-red-800': post.success_label === 'low_performer'
                                                  }"
                                                  x-text="post.success_label?.replace('_', ' ')">
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex gap-2 mt-3 flex-wrap">
                                        <button @click="viewPost(post)" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            عرض التفاصيل
                                        </button>
                                        <span class="text-gray-300 dark:text-gray-600">|</span>
                                        <button x-show="!post.is_analyzed" @click="analyzePost(post.id)" class="text-xs text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                            تحليل الآن
                                        </button>
                                        <button x-show="post.is_analyzed && !post.is_in_knowledge_base" @click="addToKB([post.id])" class="text-xs text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">
                                            إضافة إلى قاعدة المعرفة
                                        </button>
                                        <button x-show="post.is_in_knowledge_base" @click="removeFromKB([post.id])" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            إزالة من قاعدة المعرفة
                                        </button>
                                        <template x-if="post.platform && ['facebook', 'instagram', 'tiktok', 'snapchat'].includes(post.platform)">
                                            <span class="text-gray-300 dark:text-gray-600">|</span>
                                            <button @click="openBoostModal(post)" class="text-xs text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300 font-medium">
                                                <i class="fas fa-rocket ml-1"></i> ترويج المنشور
                                            </button>
                                        </template>
                                        <span class="text-gray-300 dark:text-gray-600">|</span>
                                        <button @click="openCampaignModal(post)" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                            <i class="fas fa-bullhorn ml-1"></i> إضافة إلى حملة
                                        </button>
                                        <template x-if="post.permalink">
                                            <span class="text-gray-300 dark:text-gray-600">|</span>
                                            <a :href="post.permalink" target="_blank" class="text-xs text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300">
                                                عرض الأصلي
                                            </a>
                                        </template>
                                    </div>
                                </div>

                                <!-- Media Thumbnail -->
                                <div x-show="post.media_assets && post.media_assets.length > 0" class="flex-shrink-0 mr-4">
                                    <img :src="post.media_assets[0]?.original_url"
                                         class="w-24 h-24 object-cover rounded-lg"
                                         alt="صورة المنشور">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && posts.length === 0" class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">لا توجد منشورات</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ابدأ باستيراد المحتوى التاريخي الخاص بك.</p>
            <div class="mt-6">
                <button @click="showImportModal = true" class="btn btn-primary">
                    استيراد المنشورات التاريخية
                </button>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && posts.length > 0" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700 dark:text-gray-300">
            عرض <span class="font-medium" x-text="posts.length"></span> منشور
        </div>
        <div class="flex gap-2">
            <button class="btn btn-sm btn-secondary">السابق</button>
            <button class="btn btn-sm btn-secondary">التالي</button>
        </div>
    </div>
</div>

<!-- Import Modal (placeholder) -->
<div x-show="showImportModal"
     x-cloak
     @click.away="showImportModal = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full p-6">
            <h3 class="text-lg font-semibold mb-4 dark:text-white">استيراد المنشورات التاريخية</h3>
            <p class="text-gray-600 dark:text-gray-400">سيتم تطبيق وظيفة الاستيراد هنا.</p>
            <button @click="showImportModal = false" class="mt-4 btn btn-secondary">إغلاق</button>
        </div>
    </div>
</div>

<!-- Knowledge Base Modal (placeholder) -->
<div x-show="showKBModal"
     x-cloak
     @click.away="showKBModal = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full p-6">
            <h3 class="text-lg font-semibold mb-4 dark:text-white">إدارة قاعدة المعرفة</h3>
            <p class="text-gray-600 dark:text-gray-400">سيتم تطبيق واجهة إدارة قاعدة المعرفة هنا.</p>
            <button @click="showKBModal = false" class="mt-4 btn btn-secondary">إغلاق</button>
        </div>
    </div>
</div>

<!-- Boost Post Modal -->
<div x-show="showBoostModal"
     x-cloak
     @click.away="showBoostModal = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full p-6 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-rocket text-orange-500"></i> ترويج المنشور
                </h3>
                <button @click="showBoostModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div x-show="selectedPost" class="space-y-4">
                <!-- Post Preview -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300" x-text="selectedPost?.caption?.substring(0, 150) + '...'"></p>
                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="selectedPost?.platform" class="uppercase font-medium"></span>
                        <span>•</span>
                        <span>درجة النجاح: <span x-text="(selectedPost?.success_score * 100).toFixed(0)"></span>%</span>
                    </div>
                </div>

                <!-- Boost Configuration -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">مبلغ الميزانية</label>
                        <input type="number" x-model="boostData.budget_amount" min="1"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">نوع الميزانية</label>
                        <select x-model="boostData.budget_type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                            <option value="daily">يومية</option>
                            <option value="lifetime">مدى الحياة</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">المدة (بالأيام)</label>
                        <input type="number" x-model="boostData.duration_days" min="1" max="30"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الهدف</label>
                        <select x-model="boostData.objective"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                            <option value="reach">الوصول</option>
                            <option value="engagement">التفاعل</option>
                            <option value="traffic">الزيارات</option>
                            <option value="conversions">التحويلات</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-6">
                    <button @click="boostPost()"
                            class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-medium px-6 py-3 rounded-lg transition">
                        <i class="fas fa-rocket ml-2"></i> ترويج الآن
                    </button>
                    <button @click="showBoostModal = false"
                            class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition dark:text-white">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add to Campaign Modal -->
<div x-show="showCampaignModal"
     x-cloak
     @click.away="showCampaignModal = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full p-6 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-bullhorn text-indigo-500"></i> إضافة إلى حملة
                </h3>
                <button @click="showCampaignModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div x-show="selectedPost" class="space-y-4">
                <!-- Post Preview -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300" x-text="selectedPost?.caption?.substring(0, 150) + '...'"></p>
                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="selectedPost?.platform" class="uppercase font-medium"></span>
                        <span>•</span>
                        <span>درجة النجاح: <span x-text="(selectedPost?.success_score * 100).toFixed(0)"></span>%</span>
                    </div>
                </div>

                <!-- Campaign Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">اختر الحملة</label>
                    <select x-model="selectedCampaignId"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- اختر حملة --</option>
                        <template x-for="campaign in campaigns" :key="campaign.id">
                            <option :value="campaign.id" x-text="campaign.name"></option>
                        </template>
                    </select>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">سيتم إضافة هذا المنشور كمحتوى إبداعي للحملة المحددة</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-6">
                    <button @click="addToCampaign()"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-3 rounded-lg transition">
                        <i class="fas fa-plus ml-2"></i> إضافة إلى الحملة
                    </button>
                    <button @click="showCampaignModal = false"
                            class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition dark:text-white">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function historicalContentManager() {
    return {
        posts: [],
        profileGroups: [],
        selectedPosts: [],
        campaigns: [],
        loading: false,
        showImportModal: false,
        showKBModal: false,
        showBoostModal: false,
        showCampaignModal: false,
        selectedPost: null,
        selectedCampaignId: '',
        searchQuery: '',
        filters: {
            profile_group_id: '',
            platform: '',
            is_analyzed: '',
            is_in_kb: '',
            min_success_score: 0
        },
        stats: {
            totalImported: 0,
            totalAnalyzed: 0,
            inKB: 0,
            highPerformers: 0
        },
        boostData: {
            ad_account_id: '',
            budget_amount: 10,
            budget_type: 'daily',
            duration_days: 7,
            objective: 'engagement',
            audience: {
                type: 'auto'
            }
        },

        init() {
            this.loadProfileGroups();
            this.loadStats();
            this.loadPosts();
        },

        async loadProfileGroups() {
            // TODO: Load profile groups from API
            this.profileGroups = [];
        },

        async loadStats() {
            // TODO: Load stats from API
            try {
                const orgId = '{{ session("current_org_id") }}';
                // Placeholder stats
                this.stats = {
                    totalImported: 0,
                    totalAnalyzed: 0,
                    inKB: 0,
                    highPerformers: 0
                };
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async loadPosts() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null) {
                        params.append(key, value);
                    }
                });

                const response = await fetch(`/orgs/{{ session("current_org_id") }}/social/history/api/posts?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.posts = data.data.data || data.data;
                    this.updateStatsFromPosts();
                }
            } catch (error) {
                console.error('Failed to load posts:', error);
            } finally {
                this.loading = false;
            }
        },

        updateStatsFromPosts() {
            this.stats.totalImported = this.posts.length;
            this.stats.totalAnalyzed = this.posts.filter(p => p.is_analyzed).length;
            this.stats.inKB = this.posts.filter(p => p.is_in_knowledge_base).length;
            this.stats.highPerformers = this.posts.filter(p => p.success_label === 'high_performer').length;
        },

        async analyzePost(postId) {
            try {
                const response = await fetch(`/orgs/{{ session("current_org_id") }}/social/history/api/posts/${postId}/analyze`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    alert('تم بدء تحليل المنشور. قد يستغرق هذا بضع دقائق.');
                    this.loadPosts();
                }
            } catch (error) {
                console.error('Failed to analyze post:', error);
                alert('فشل بدء التحليل');
            }
        },

        async addToKB(postIds) {
            try {
                const response = await fetch(`/orgs/{{ session("current_org_id") }}/social/history/api/kb/add`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ post_ids: postIds })
                });
                const data = await response.json();

                if (data.success) {
                    alert(`تمت إضافة ${data.data.added} منشور إلى قاعدة المعرفة`);
                    this.loadPosts();
                }
            } catch (error) {
                console.error('Failed to add to KB:', error);
                alert('فشل إضافة المنشورات إلى قاعدة المعرفة');
            }
        },

        async removeFromKB(postIds) {
            try {
                const response = await fetch(`/orgs/{{ session("current_org_id") }}/social/history/api/kb/remove`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ post_ids: postIds })
                });
                const data = await response.json();

                if (data.success) {
                    alert(`تمت إزالة ${data.data.removed} منشور من قاعدة المعرفة`);
                    this.loadPosts();
                }
            } catch (error) {
                console.error('Failed to remove from KB:', error);
                alert('فشل إزالة المنشورات من قاعدة المعرفة');
            }
        },

        bulkAddToKB() {
            this.addToKB(this.selectedPosts);
            this.selectedPosts = [];
        },

        selectAll() {
            this.selectedPosts = this.posts.map(p => p.id);
        },

        clearSelection() {
            this.selectedPosts = [];
        },

        resetFilters() {
            this.filters = {
                profile_group_id: '',
                platform: '',
                is_analyzed: '',
                is_in_kb: '',
                min_success_score: 0
            };
            this.loadPosts();
        },

        viewPost(post) {
            // TODO: Open post detail modal/page
            console.log('View post:', post);
        },

        openBoostModal(post) {
            this.selectedPost = post;
            this.showBoostModal = true;
        },

        openCampaignModal(post) {
            this.selectedPost = post;
            this.showCampaignModal = true;
            this.loadCampaigns();
        },

        async loadCampaigns() {
            try {
                const response = await fetch(`/orgs/${orgId}/campaigns/api/list`);
                const data = await response.json();
                this.campaigns = data.data || data.campaigns || [];
            } catch (error) {
                console.error('Failed to load campaigns:', error);
            }
        },

        async boostPost() {
            if (!this.boostData.ad_account_id || !this.boostData.budget_amount) {
                alert('يرجى ملء جميع الحقول المطلوبة');
                return;
            }

            try {
                const response = await fetch(`/orgs/${orgId}/social/history/api/posts/${this.selectedPost.id}/boost`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(this.boostData)
                });

                const data = await response.json();
                if (data.success) {
                    alert('تم ترويج المنشور بنجاح!');
                    this.showBoostModal = false;
                    this.loadPosts();
                } else {
                    alert(data.message || 'فشل ترويج المنشور');
                }
            } catch (error) {
                console.error('Failed to boost post:', error);
                alert('فشل ترويج المنشور');
            }
        },

        async addToCampaign() {
            if (!this.selectedCampaignId) {
                alert('يرجى اختيار حملة');
                return;
            }

            try {
                const response = await fetch(`/orgs/${orgId}/social/history/api/posts/${this.selectedPost.id}/add-to-campaign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        campaign_id: this.selectedCampaignId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('تمت إضافة المنشور إلى الحملة بنجاح!');
                    this.showCampaignModal = false;
                } else {
                    alert(data.message || 'فشل إضافة المنشور إلى الحملة');
                }
            } catch (error) {
                console.error('Failed to add to campaign:', error);
                alert('فشل إضافة المنشور إلى الحملة');
            }
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    };
}
</script>
@endpush

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
