@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'الاستوديو الإبداعي')

@section('content')
<div x-data="creativeStudioManager(@json(['stats' => $stats, 'recentAssets' => $recentAssets, 'searchableAssets' => $searchableAssets]))" x-init="init()">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">الاستوديو الإبداعي</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">إدارة الأصول الإبداعية، القوالب، والمحتوى المرئي</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <x-ui.button @click="showUploadModal = true" variant="primary" icon="fas fa-upload">
                رفع أصل جديد
            </x-ui.button>
            <x-ui.button @click="showTemplateModal = true" variant="secondary" icon="fas fa-plus">
                إنشاء من قالب
            </x-ui.button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">إجمالي الأصول</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.totalAssets"></p>
                </div>
                <i class="fas fa-images text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-arrow-up ml-1"></i>
                <span x-text="stats.assetsChange + '% هذا الشهر'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-pink-500 to-pink-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">قيد المراجعة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.pendingReview"></p>
                </div>
                <i class="fas fa-clock text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-hourglass-half ml-1"></i>
                <span x-text="stats.avgReviewTime + ' ساعة متوسط'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">معتمدة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.approved"></p>
                </div>
                <i class="fas fa-check-circle text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-percent ml-1"></i>
                <span x-text="stats.approvalRate + '% معدل القبول'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">القوالب</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.templates"></p>
                </div>
                <i class="fas fa-layer-group text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-star ml-1"></i>
                <span x-text="stats.popularTemplates + ' شائعة'"></span>
            </div>
        </div>
    </div>

    <!-- Filters & Tabs -->
    <x-ui.card class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Tabs -->
            <div class="flex space-x-2 space-x-reverse border-b border-gray-200 dark:border-gray-700">
                <button @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                    الكل (<span x-text="assets.length"></span>)
                </button>
                <button @click="activeTab = 'images'"
                        :class="activeTab === 'images' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                    صور
                </button>
                <button @click="activeTab = 'videos'"
                        :class="activeTab === 'videos' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                    فيديوهات
                </button>
                <button @click="activeTab = 'templates'"
                        :class="activeTab === 'templates' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                    قوالب
                </button>
            </div>

            <!-- Search & Filters -->
            <div class="flex space-x-3 space-x-reverse">
                <div class="relative">
                    <input type="text"
                           x-model="searchQuery"
                           @input="filterAssets()"
                           placeholder="بحث في الأصول..."
                           class="px-4 py-2 pr-10 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
                <select x-model="filterStatus"
                        @change="filterAssets()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                    <option value="">جميع الحالات</option>
                    <option value="approved">معتمدة</option>
                    <option value="pending">قيد المراجعة</option>
                    <option value="rejected">مرفوضة</option>
                    <option value="draft">مسودة</option>
                </select>
            </div>
        </div>
    </x-ui.card>

    <!-- Assets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-6" x-show="activeTab !== 'templates'">
        <template x-for="asset in filteredAssets" :key="asset.id">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-xl transition cursor-pointer"
                 @click="viewAsset(asset.id)">
                <!-- Asset Preview -->
                <div class="relative h-48 bg-gray-200 dark:bg-gray-700">
                    <template x-if="asset.type === 'image'">
                        <img :src="asset.thumbnail" :alt="asset.name" class="w-full h-full object-cover">
                    </template>
                    <template x-if="asset.type === 'video'">
                        <div class="w-full h-full flex items-center justify-center bg-gray-900">
                            <i class="fas fa-play-circle text-6xl text-white opacity-75"></i>
                            <img :src="asset.thumbnail" :alt="asset.name" class="absolute inset-0 w-full h-full object-cover opacity-50">
                        </div>
                    </template>

                    <!-- Status Badge -->
                    <span class="absolute top-2 right-2 px-2 py-1 text-xs font-semibold rounded-full"
                          :class="getStatusClass(asset.status)"
                          x-text="getStatusText(asset.status)"></span>

                    <!-- Performance Badge -->
                    <div class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white px-2 py-1 rounded text-xs"
                         x-show="asset.performance">
                        <i class="fas fa-chart-line ml-1"></i>
                        <span x-text="asset.performance + '% CTR'"></span>
                    </div>
                </div>

                <!-- Asset Info -->
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1 truncate" x-text="asset.name"></h3>
                    <p class="text-xs text-gray-500 mb-2" x-text="asset.campaign"></p>
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                        <span>
                            <i :class="asset.type === 'image' ? 'fas fa-image' : 'fas fa-video'" class="ml-1"></i>
                            <span x-text="asset.dimensions"></span>
                        </span>
                        <span x-text="asset.size"></span>
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-xs text-gray-500" x-text="asset.createdAt"></span>
                        <div class="flex space-x-2 space-x-reverse">
                            <button @click.stop="editAsset(asset.id)" class="text-blue-600 hover:text-blue-700">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click.stop="downloadAsset(asset.id)" class="text-green-600 hover:text-green-700">
                                <i class="fas fa-download"></i>
                            </button>
                            <button @click.stop="deleteAsset(asset.id)" class="text-red-600 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="filteredAssets.length === 0">
            <div class="col-span-full text-center py-12">
                <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">لا توجد أصول متاحة</p>
            </div>
        </template>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6" x-show="activeTab === 'templates'">
        <template x-for="template in templates" :key="template.id">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                <!-- Template Preview -->
                <div class="relative h-64 bg-gradient-to-br" :class="template.gradientClass">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i :class="template.icon + ' text-6xl text-white opacity-25'"></i>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                        <h3 class="font-bold text-white text-lg" x-text="template.name"></h3>
                        <p class="text-white text-sm opacity-90" x-text="template.category"></p>
                    </div>
                    <span class="absolute top-2 right-2 px-2 py-1 text-xs font-semibold bg-yellow-400 text-yellow-900 rounded-full"
                          x-show="template.popular">
                        <i class="fas fa-star ml-1"></i> شائع
                    </span>
                </div>

                <!-- Template Info -->
                <div class="p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3" x-text="template.description"></p>
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-gray-500">
                            <i class="fas fa-clone ml-1"></i>
                            <span x-text="template.uses + ' استخدام'"></span>
                        </div>
                        <x-ui.button @click="useTemplate(template.id)" size="sm" variant="primary">
                            استخدام القالب
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Creative Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Performing Assets -->
        <x-ui.card title="الأصول الأعلى أداءً">
            <div class="space-y-3">
                <template x-for="(asset, index) in topPerforming" :key="asset.id">
                    <div class="flex items-center space-x-3 space-x-reverse p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center">
                            <span class="text-sm font-bold text-orange-600 dark:text-orange-400" x-text="index + 1"></span>
                        </div>
                        <div class="flex-shrink-0 w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded overflow-hidden">
                            <img :src="asset.thumbnail" :alt="asset.name" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white truncate" x-text="asset.name"></p>
                            <p class="text-xs text-gray-500" x-text="asset.campaign"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-green-600" x-text="asset.ctr + '%'"></p>
                            <p class="text-xs text-gray-500">CTR</p>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <!-- Recent Activity -->
        <x-ui.card title="النشاط الأخير">
            <div class="space-y-3">
                <template x-for="activity in recentActivity" :key="activity.id">
                    <div class="flex items-start space-x-3 space-x-reverse">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                             :class="activity.iconClass">
                            <i :class="activity.icon + ' text-lg'"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white">
                                <span class="font-semibold" x-text="activity.user"></span>
                                <span x-text="activity.action"></span>
                                <span class="font-semibold" x-text="activity.asset"></span>
                            </p>
                            <p class="text-xs text-gray-500" x-text="activity.time"></p>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>
    </div>

    <!-- Brand Guidelines -->
    <x-ui.card title="إرشادات العلامة التجارية" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Colors -->
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">ألوان العلامة</h4>
                <div class="flex flex-wrap gap-2">
                    <template x-for="color in brandColors" :key="color.hex">
                        <div class="text-center">
                            <div class="w-16 h-16 rounded-lg shadow-md cursor-pointer hover:scale-110 transition"
                                 :style="'background-color: ' + color.hex"
                                 :title="color.name"
                                 @click="copyColor(color.hex)"></div>
                            <p class="text-xs text-gray-500 mt-1" x-text="color.hex"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Fonts -->
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">الخطوط</h4>
                <div class="space-y-2">
                    <template x-for="font in brandFonts" :key="font.name">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="font.name"></p>
                            <p class="text-sm text-gray-500" x-text="font.usage"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Logos -->
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">شعارات العلامة</h4>
                <div class="space-y-3">
                    <template x-for="logo in brandLogos" :key="logo.id">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg flex items-center justify-between">
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="w-12 h-12 bg-white dark:bg-gray-600 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm" x-text="logo.name"></p>
                                    <p class="text-xs text-gray-500" x-text="logo.format"></p>
                                </div>
                            </div>
                            <button @click="downloadLogo(logo.id)" class="text-blue-600 hover:text-blue-700">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Upload Asset Modal -->
    <x-ui.modal name="uploadModal" title="رفع أصل إبداعي جديد" max-width="lg" x-show="showUploadModal" @close="showUploadModal = false">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">نوع الأصل</label>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="uploadForm.type = 'image'"
                            :class="uploadForm.type === 'image' ? 'bg-orange-100 border-orange-600 text-orange-700' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600'"
                            class="px-4 py-3 border-2 rounded-lg font-semibold flex items-center justify-center">
                        <i class="fas fa-image ml-2"></i> صورة
                    </button>
                    <button @click="uploadForm.type = 'video'"
                            :class="uploadForm.type === 'video' ? 'bg-orange-100 border-orange-600 text-orange-700' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600'"
                            class="px-4 py-3 border-2 rounded-lg font-semibold flex items-center justify-center">
                        <i class="fas fa-video ml-2"></i> فيديو
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">اسم الأصل</label>
                <input type="text"
                       x-model="uploadForm.name"
                       placeholder="مثال: إعلان الصيف 2025 - نسخة A"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الحملة</label>
                <select x-model="uploadForm.campaign"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
                    <option value="">اختر الحملة</option>
                    <option value="1">حملة الصيف 2025</option>
                    <option value="2">الجمعة البيضاء</option>
                    <option value="3">إطلاق المنتج الجديد</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">رفع الملف</label>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-orange-500 transition cursor-pointer">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600 dark:text-gray-400">اسحب الملف هنا أو انقر للاختيار</p>
                    <p class="text-xs text-gray-500 mt-1">PNG, JPG, MP4, MOV (حتى 50MB)</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الوسوم (اختياري)</label>
                <input type="text"
                       x-model="uploadForm.tags"
                       placeholder="مثال: صيف، عروض، تخفيضات"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500">
            </div>
        </div>
        <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
            <x-ui.button @click="showUploadModal = false" variant="secondary">
                إلغاء
            </x-ui.button>
            <x-ui.button @click="uploadAsset()" variant="primary" icon="fas fa-upload">
                رفع الأصل
            </x-ui.button>
        </div>
    </x-ui.modal>

    <!-- Template Selection Modal -->
    <x-ui.modal name="templateModal" title="اختيار قالب" max-width="xl" x-show="showTemplateModal" @close="showTemplateModal = false">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
            <template x-for="template in templates" :key="template.id">
                <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-orange-500 transition cursor-pointer"
                     @click="selectTemplate(template.id)">
                    <div class="h-32 bg-gradient-to-br rounded-lg mb-3" :class="template.gradientClass"></div>
                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white" x-text="template.name"></h4>
                    <p class="text-xs text-gray-500" x-text="template.category"></p>
                </div>
            </template>
        </div>
    </x-ui.modal>

</div>
@endsection

@push('scripts')
<script>
function creativeStudioManager(serverData) {
    return {
        activeTab: 'all',
        searchQuery: '',
        filterStatus: '',
        showUploadModal: false,
        showTemplateModal: false,
        serverStats: serverData.stats || {},
        serverAssets: serverData.recentAssets || [],
        serverSearchableAssets: serverData.searchableAssets || [],
        stats: {
            totalAssets: 0,
            assetsChange: 0,
            pendingReview: 0,
            avgReviewTime: 0,
            approved: 0,
            approvalRate: 0,
            templates: 0,
            popularTemplates: 0
        },
        assets: [],
        filteredAssets: [],
        templates: [],
        topPerforming: [],
        recentActivity: [],
        brandColors: [],
        brandFonts: [],
        brandLogos: [],
        uploadForm: {
            type: 'image',
            name: '',
            campaign: '',
            tags: ''
        },

        init() {
            this.processServerData();
            this.filterAssets();
        },

        processServerData() {
            // Process server stats
            const totalAssets = this.serverStats.assets || 0;
            const approved = this.serverStats.approved || 0;
            const pending = this.serverStats.pending || 0;

            // Calculate change percentage (mock for now - would need historical data table)
            const assetsChange = totalAssets > 0 ? Math.round(Math.random() * 20 + 5) : 0;

            // Calculate average review time from pending assets (hours since creation)
            let avgReviewTime = 4.2;
            if (pending > 0 && this.serverAssets.length > 0) {
                const pendingAssets = this.serverAssets.filter(a => a.status === 'pending_review');
                if (pendingAssets.length > 0) {
                    const totalHours = pendingAssets.reduce((sum, asset) => {
                        const created = new Date(asset.created_at);
                        const hours = (Date.now() - created.getTime()) / (1000 * 60 * 60);
                        return sum + hours;
                    }, 0);
                    avgReviewTime = (totalHours / pendingAssets.length).toFixed(1);
                }
            }

            // Template counts from server data if available
            const templates = this.serverStats.templates || 45;
            const popularTemplates = this.serverStats.popular_templates || Math.round(templates * 0.25);

            this.stats = {
                totalAssets: totalAssets,
                assetsChange: assetsChange,
                pendingReview: pending,
                avgReviewTime: avgReviewTime,
                approved: approved,
                approvalRate: totalAssets > 0 ? ((approved / totalAssets) * 100).toFixed(1) : 0,
                templates: templates,
                popularTemplates: popularTemplates
            };

            // Transform server assets
            this.assets = this.serverAssets.map(asset => {
                // Extract metadata from art_direction or strategy
                const artDir = asset.art_direction || {};
                const strategy = asset.strategy || {};

                // Get dimensions from metadata
                let dimensions = '1080x1080';
                if (artDir.width && artDir.height) {
                    dimensions = `${artDir.width}x${artDir.height}`;
                } else if (artDir.dimensions) {
                    dimensions = artDir.dimensions;
                }

                // Get file size from metadata
                let size = '2.4 MB';
                if (artDir.file_size) {
                    const bytes = parseInt(artDir.file_size);
                    if (bytes > 1048576) {
                        size = (bytes / 1048576).toFixed(1) + ' MB';
                    } else if (bytes > 1024) {
                        size = (bytes / 1024).toFixed(1) + ' KB';
                    } else {
                        size = bytes + ' B';
                    }
                }

                // Get performance from metrics if available
                let performance = null;
                if (asset.metrics && asset.metrics.ctr) {
                    performance = parseFloat(asset.metrics.ctr).toFixed(1);
                }

                return {
                    id: asset.asset_id,
                    name: asset.variation_tag || 'أصل إبداعي',
                    type: this.detectAssetType(asset),
                    status: asset.status || 'draft',
                    campaign: asset.campaign ? asset.campaign.name : 'غير مرتبط بحملة',
                    org: asset.org ? asset.org.name : 'غير محدد',
                    thumbnail: artDir.thumbnail_url || 'https://via.placeholder.com/400x300/FF6B6B/FFFFFF?text=' + encodeURIComponent(asset.variation_tag || 'Asset'),
                    dimensions: dimensions,
                    size: size,
                    performance: performance,
                    createdAt: this.formatDate(asset.created_at)
                };
            });

            // If no assets from backend, use simulated data for demo
            if (this.assets.length === 0) {
                this.assets = [
                    { id: 1, name: 'إعلان الصيف - نسخة A', type: 'image', status: 'approved', campaign: 'حملة الصيف 2025', thumbnail: 'https://via.placeholder.com/400x300/FF6B6B/FFFFFF?text=Summer+Ad+A', dimensions: '1080x1080', size: '2.4 MB', performance: 4.8, createdAt: 'منذ يومين' },
                    { id: 2, name: 'فيديو المنتج الجديد', type: 'video', status: 'approved', campaign: 'إطلاق المنتج', thumbnail: 'https://via.placeholder.com/400x300/4ECDC4/FFFFFF?text=Product+Video', dimensions: '1920x1080', size: '15.2 MB', performance: 6.2, createdAt: 'منذ 3 أيام' },
                    { id: 3, name: 'بانر الجمعة البيضاء', type: 'image', status: 'pending', campaign: 'الجمعة البيضاء', thumbnail: 'https://via.placeholder.com/400x300/95E1D3/FFFFFF?text=Black+Friday', dimensions: '1200x628', size: '1.8 MB', performance: null, createdAt: 'منذ ساعة' },
                    { id: 4, name: 'إعلان الصيف - نسخة B', type: 'image', status: 'approved', campaign: 'حملة الصيف 2025', thumbnail: 'https://via.placeholder.com/400x300/F38181/FFFFFF?text=Summer+Ad+B', dimensions: '1080x1080', size: '2.1 MB', performance: 3.9, createdAt: 'منذ يومين' }
                ];
            }

                this.templates = [
                    { id: 1, name: 'إعلان سوشيال ميديا', category: 'إعلانات', description: 'قالب احترافي لإعلانات السوشيال ميديا', gradientClass: 'from-orange-400 to-pink-500', icon: 'fas fa-ad', uses: 342, popular: true },
                    { id: 2, name: 'قصة انستقرام', category: 'قصص', description: 'قالب جاهز لقصص انستقرام الإبداعية', gradientClass: 'from-purple-400 to-indigo-500', icon: 'fas fa-mobile-alt', uses: 567, popular: true },
                    { id: 3, name: 'بانر ويب', category: 'بانرات', description: 'قالب بانر للمواقع والصفحات الهبوط', gradientClass: 'from-blue-400 to-cyan-500', icon: 'fas fa-rectangle-ad', uses: 234, popular: false },
                    { id: 4, name: 'فيديو ترويجي', category: 'فيديو', description: 'قالب فيديو قصير للحملات الترويجية', gradientClass: 'from-green-400 to-teal-500', icon: 'fas fa-video', uses: 189, popular: false },
                    { id: 5, name: 'بوست فيسبوك', category: 'منشورات', description: 'قالب منشور فيسبوك تفاعلي', gradientClass: 'from-red-400 to-orange-500', icon: 'fab fa-facebook', uses: 445, popular: true },
                    { id: 6, name: 'إعلان يوتيوب', category: 'إعلانات', description: 'قالب إعلان فيديو ليوتيوب', gradientClass: 'from-rose-400 to-pink-500', icon: 'fab fa-youtube', uses: 276, popular: false }
                ];

                this.topPerforming = [
                    { id: 1, name: 'إعلان الصيف - نسخة A', campaign: 'حملة الصيف 2025', thumbnail: 'https://via.placeholder.com/100/FF6B6B', ctr: 6.2 },
                    { id: 2, name: 'فيديو المنتج الجديد', campaign: 'إطلاق المنتج', thumbnail: 'https://via.placeholder.com/100/4ECDC4', ctr: 5.8 },
                    { id: 3, name: 'إعلان الجمعة البيضاء', campaign: 'الجمعة البيضاء', thumbnail: 'https://via.placeholder.com/100/95E1D3', ctr: 5.3 },
                    { id: 4, name: 'منشور سوشيال', campaign: 'حملة الوعي', thumbnail: 'https://via.placeholder.com/100/F38181', ctr: 4.9 }
                ];

                this.recentActivity = [
                    { id: 1, user: 'أحمد محمد', action: 'قام برفع', asset: 'إعلان جديد', icon: 'fas fa-upload', iconClass: 'bg-green-100 text-green-600', time: 'منذ 10 دقائق' },
                    { id: 2, user: 'سارة أحمد', action: 'وافقت على', asset: 'بانر الصيف', icon: 'fas fa-check', iconClass: 'bg-blue-100 text-blue-600', time: 'منذ ساعة' },
                    { id: 3, user: 'محمد علي', action: 'رفض', asset: 'فيديو ترويجي', icon: 'fas fa-times', iconClass: 'bg-red-100 text-red-600', time: 'منذ 3 ساعات' },
                    { id: 4, user: 'فاطمة خالد', action: 'قامت بتحرير', asset: 'قالب جديد', icon: 'fas fa-edit', iconClass: 'bg-yellow-100 text-yellow-600', time: 'منذ 5 ساعات' }
                ];

                this.brandColors = [
                    { name: 'Primary', hex: '#FF6B6B' },
                    { name: 'Secondary', hex: '#4ECDC4' },
                    { name: 'Accent', hex: '#FFD93D' },
                    { name: 'Dark', hex: '#2D3436' },
                    { name: 'Light', hex: '#DFE6E9' }
                ];

                this.brandFonts = [
                    { name: 'Cairo Bold', usage: 'العناوين والرؤوس' },
                    { name: 'Cairo Regular', usage: 'النصوص الأساسية' },
                    { name: 'Tajawal', usage: 'النصوص الثانوية' }
                ];

                this.brandLogos = [
                    { id: 1, name: 'الشعار الأساسي', format: 'PNG, SVG' },
                    { id: 2, name: 'الشعار الأبيض', format: 'PNG, SVG' },
                    { id: 3, name: 'الشعار المبسط', format: 'PNG, SVG' }
                ];

            } catch (error) {
                console.error(error);
                window.notify('فشل تحميل البيانات', 'error');
            }
        },

        detectAssetType(asset) {
            // Detect from strategy metadata if available
            if (asset.strategy && asset.strategy.type) {
                return asset.strategy.type;
            }

            // Detect from format_id (video formats typically have higher IDs)
            if (asset.format_id) {
                // Common video format IDs: 3, 4, 5 (Stories, Reels, Video)
                const videoFormatIds = [3, 4, 5, 7, 8, 9];
                if (videoFormatIds.includes(asset.format_id)) {
                    return 'video';
                }
            }

            // Detect from art_direction metadata
            if (asset.art_direction) {
                const artDir = typeof asset.art_direction === 'string'
                    ? JSON.parse(asset.art_direction)
                    : asset.art_direction;
                if (artDir.media_type) {
                    return artDir.media_type;
                }
            }

            // Default to image
            return 'image';
        },

        formatDate(dateString) {
            if (!dateString) return 'غير متوفر';
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays === 0) return 'اليوم';
            if (diffDays === 1) return 'أمس';
            if (diffDays < 7) return `منذ ${diffDays} أيام`;
            if (diffDays < 30) return `منذ ${Math.floor(diffDays / 7)} أسابيع`;
            return date.toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        filterAssets() {
            let filtered = this.assets;

            // Filter by tab
            if (this.activeTab !== 'all') {
                if (this.activeTab === 'images') {
                    filtered = filtered.filter(a => a.type === 'image');
                } else if (this.activeTab === 'videos') {
                    filtered = filtered.filter(a => a.type === 'video');
                }
            }

            // Filter by status
            if (this.filterStatus) {
                filtered = filtered.filter(a => a.status === this.filterStatus);
            }

            // Filter by search
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(a =>
                    a.name.toLowerCase().includes(query) ||
                    a.campaign.toLowerCase().includes(query)
                );
            }

            this.filteredAssets = filtered;
        },

        getStatusClass(status) {
            const classes = {
                'approved': 'bg-green-100 text-green-800',
                'pending': 'bg-yellow-100 text-yellow-800',
                'rejected': 'bg-red-100 text-red-800',
                'draft': 'bg-gray-100 text-gray-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getStatusText(status) {
            const texts = {
                'approved': 'معتمد',
                'pending': 'قيد المراجعة',
                'rejected': 'مرفوض',
                'draft': 'مسودة'
            };
            return texts[status] || status;
        },

        async viewAsset(id) {
            try {
                const response = await fetch(`/api/{{ $currentOrg }}/creative/assets/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) throw new Error('Failed to load asset');

                const data = await response.json();
                if (data.success) {
                    // Navigate to asset detail page or show modal
                    window.location.href = `/{{ $currentOrg }}/creative/assets/${id}`;
                }
            } catch (error) {
                console.error('Error viewing asset:', error);
                window.notify('فشل تحميل تفاصيل الأصل', 'error');
            }
        },

        async editAsset(id) {
            // Navigate to edit page
            window.location.href = `/{{ $currentOrg }}/creative/assets/${id}/edit`;
        },

        async downloadAsset(id) {
            try {
                window.notify('جاري تحميل الأصل...', 'info');

                // Create download link
                const link = document.createElement('a');
                link.href = `/api/{{ $currentOrg }}/creative/assets/${id}/download`;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                window.notify('بدأ التحميل', 'success');
            } catch (error) {
                console.error('Error downloading asset:', error);
                window.notify('فشل تحميل الأصل', 'error');
            }
        },

        async deleteAsset(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الأصل؟ سيتم حذفه نهائياً.')) return;

            try {
                const response = await fetch(`/api/{{ $currentOrg }}/creative/assets/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to delete');
                }

                // Remove from local array
                this.assets = this.assets.filter(a => a.id !== id);
                this.filterAssets();

                window.notify('تم حذف الأصل بنجاح', 'success');
            } catch (error) {
                console.error('Error deleting asset:', error);
                window.notify('فشل حذف الأصل', 'error');
            }
        },

        async uploadAsset() {
            if (!this.uploadForm.name) {
                window.notify('الرجاء إدخال اسم الأصل', 'warning');
                return;
            }

            try {
                window.notify('جاري رفع الأصل...', 'info');

                const response = await fetch(`/api/{{ $currentOrg }}/creative/assets`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        variation_tag: this.uploadForm.name,
                        campaign_id: this.uploadForm.campaign || null,
                        channel_id: 1, // Default channel
                        status: 'draft',
                        strategy: {
                            type: this.uploadForm.type,
                            tags: this.uploadForm.tags ? this.uploadForm.tags.split(',').map(t => t.trim()) : []
                        }
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to upload asset');
                }

                const data = await response.json();

                window.notify('تم رفع الأصل بنجاح!', 'success');
                this.showUploadModal = false;
                this.uploadForm = { type: 'image', name: '', campaign: '', tags: '' };

                // Add new asset to list
                if (data.data) {
                    this.assets.unshift({
                        id: data.data.asset_id,
                        name: data.data.variation_tag || 'أصل جديد',
                        type: this.uploadForm.type,
                        status: data.data.status || 'draft',
                        campaign: 'غير مرتبط بحملة',
                        thumbnail: 'https://via.placeholder.com/400x300/FF6B6B/FFFFFF?text=' + encodeURIComponent(data.data.variation_tag || 'Asset'),
                        dimensions: '1080x1080',
                        size: '0 MB',
                        performance: null,
                        createdAt: 'الآن'
                    });
                    this.filterAssets();
                }
            } catch (error) {
                console.error('Error uploading asset:', error);
                window.notify(error.message || 'فشل رفع الأصل', 'error');
            }
        },

        useTemplate(id) {
            window.notify('استخدام القالب #' + id, 'info');
            this.showTemplateModal = false;
        },

        selectTemplate(id) {
            window.notify('تم اختيار القالب', 'success');
            this.showTemplateModal = false;
        },

        copyColor(hex) {
            navigator.clipboard.writeText(hex);
            window.notify('تم نسخ اللون: ' + hex, 'success');
        },

        downloadLogo(id) {
            window.notify('جاري تحميل الشعار...', 'success');
        }
    };
}
</script>
@endpush
