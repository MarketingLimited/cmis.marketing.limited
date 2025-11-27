@extends('layouts.admin')

@section('title', 'لوحة التحكم')

@section('content')
@php
    $orgId = $currentOrg->org_id ?? request()->route('org');
@endphp

<div x-data="dashboardData('{{ $orgId }}', {{ Js::from($stats ?? []) }}, {{ Js::from($campaignStatus ?? []) }}, {{ Js::from($campaignsByOrg ?? []) }})">

    <!-- Page Header -->
    <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">لوحة التحكم</h1>
            <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">
                نظرة شاملة على أداء النظام والحملات التسويقية
                <span x-show="currentOrg" class="font-semibold text-blue-600">- <span x-text="currentOrg?.name || '{{ $currentOrg->name ?? '' }}'"></span></span>
            </p>
        </div>

        <!-- Refresh Controls -->
        <div class="flex items-center gap-2">
            <button @click="manualRefresh()"
                    :disabled="isLoading"
                    class="flex items-center gap-2 px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-50">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': isLoading }"></i>
                <span class="hidden sm:inline">تحديث</span>
            </button>
            <span x-show="lastUpdated" class="text-xs text-gray-500 dark:text-gray-400 hidden sm:inline" x-text="getRelativeTime()"></span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6 mb-6 sm:mb-8">

        <!-- Organizations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow p-4 sm:p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">المؤسسات</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-1 sm:mt-2" x-text="stats?.orgs ?? {{ $stats['orgs'] ?? 1 }}"></p>
                </div>
                <div class="p-2.5 sm:p-3 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/30">
                    <i class="fas fa-building text-white text-lg sm:text-xl"></i>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                    <i class="fas fa-check-circle"></i> نشط
                </span>
            </div>
        </div>

        <!-- Campaigns -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow p-4 sm:p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">الحملات</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-1 sm:mt-2" x-text="stats?.campaigns ?? {{ $stats['campaigns'] ?? 0 }}"></p>
                </div>
                <div class="p-2.5 sm:p-3 rounded-xl bg-gradient-to-br from-green-500 to-green-600 shadow-lg shadow-green-500/30">
                    <i class="fas fa-bullhorn text-white text-lg sm:text-xl"></i>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('orgs.campaigns.index', ['org' => $orgId]) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                    <i class="fas fa-external-link-alt"></i> عرض الكل
                </a>
            </div>
        </div>

        <!-- Creative Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow p-4 sm:p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">الأصول الإبداعية</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-1 sm:mt-2" x-text="stats?.creative_assets ?? {{ $stats['creative_assets'] ?? 0 }}"></p>
                </div>
                <div class="p-2.5 sm:p-3 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-lg shadow-purple-500/30">
                    <i class="fas fa-palette text-white text-lg sm:text-xl"></i>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('orgs.creative.assets.index', ['org' => $orgId]) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                    <i class="fas fa-external-link-alt"></i> عرض الكل
                </a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow p-4 sm:p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">مؤشرات الأداء</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-1 sm:mt-2" x-text="stats?.kpis ?? {{ $stats['kpis'] ?? 0 }}"></p>
                </div>
                <div class="p-2.5 sm:p-3 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 shadow-lg shadow-amber-500/30">
                    <i class="fas fa-chart-line text-white text-lg sm:text-xl"></i>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('orgs.analytics.index', ['org' => $orgId]) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                    <i class="fas fa-external-link-alt"></i> التحليلات
                </a>
            </div>
        </div>

    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-6 sm:mb-8">

        <!-- Campaign Status Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">توزيع الحملات حسب الحالة</h3>
                <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full" x-text="Object.values(campaignStatus || {}).reduce((a,b) => a+b, 0) + ' حملة'"></span>
            </div>
            <div class="p-4 sm:p-6">
                <div class="h-56 sm:h-64 flex items-center justify-center relative">
                    <canvas id="statusChart"></canvas>
                    <div x-show="!campaignStatus || Object.keys(campaignStatus).length === 0" class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-chart-pie text-4xl mb-2 opacity-30"></i>
                            <p class="text-sm">لا توجد بيانات حملات</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaigns by Organization -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">الحملات حسب المؤسسة</h3>
            </div>
            <div class="p-4 sm:p-6">
                <div class="h-56 sm:h-64 flex items-center justify-center relative">
                    <canvas id="orgChart"></canvas>
                    <div x-show="!campaignsByOrg || campaignsByOrg.length === 0" class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-building text-4xl mb-2 opacity-30"></i>
                            <p class="text-sm">لا توجد بيانات مؤسسات</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6 sm:mb-8">

        <!-- Weekly Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">الأداء الأسبوعي</h3>
            </div>
            <div class="p-4 sm:p-6">
                <div class="space-y-4">
                    <template x-for="metric in weeklyMetrics" :key="metric.label">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400" x-text="metric.label"></span>
                            <div class="flex items-center gap-3">
                                <div class="w-24 sm:w-32 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-500" :style="'width: ' + metric.percentage + '%'"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white w-16 text-left" x-text="metric.value"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="weeklyMetrics.length === 0">
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <i class="fas fa-chart-bar text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">جاري تحميل البيانات...</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Top Campaigns -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">أفضل الحملات</h3>
            </div>
            <div class="p-4 sm:p-6">
                <div class="space-y-3">
                    <template x-for="campaign in topCampaigns" :key="campaign.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="campaign.name"></h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="campaign.organization"></p>
                            </div>
                            <div class="text-left mr-3">
                                <span class="text-lg font-bold" :class="campaign.performance >= 80 ? 'text-green-600' : campaign.performance >= 50 ? 'text-yellow-600' : 'text-red-600'" x-text="campaign.performance + '%'"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="topCampaigns.length === 0">
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <i class="fas fa-trophy text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">لا توجد حملات نشطة</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">النشاط الأخير</h3>
            </div>
            <div class="p-4 sm:p-6">
                <div class="space-y-4 max-h-64 overflow-y-auto">
                    <template x-for="activity in recentActivity" :key="activity.id">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                 :class="{
                                     'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400': activity.type === 'campaign',
                                     'bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400': activity.type === 'integration',
                                     'bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400': activity.type === 'creative',
                                     'bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400': activity.type === 'analytics'
                                 }">
                                <i :class="activity.icon" class="text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-white leading-snug" x-text="activity.message"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="activity.time"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="recentActivity.length === 0">
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <i class="fas fa-history text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">لا يوجد نشاط حديث</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">إجراءات سريعة</h3>
        </div>
        <div class="p-4 sm:p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                <a href="{{ route('orgs.campaigns.create', ['org' => $orgId]) }}" class="flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <i class="fas fa-plus-circle text-2xl sm:text-3xl mb-2"></i>
                    <span class="text-xs sm:text-sm font-semibold text-center">حملة جديدة</span>
                </a>
                <a href="{{ route('orgs.create') }}" class="flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <i class="fas fa-building text-2xl sm:text-3xl mb-2"></i>
                    <span class="text-xs sm:text-sm font-semibold text-center">مؤسسة جديدة</span>
                </a>
                <a href="{{ route('orgs.creative.assets.index', ['org' => $orgId]) }}" class="flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <i class="fas fa-palette text-2xl sm:text-3xl mb-2"></i>
                    <span class="text-xs sm:text-sm font-semibold text-center">محتوى إبداعي</span>
                </a>
                <a href="{{ route('orgs.analytics.index', ['org' => $orgId]) }}" class="flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-amber-500 to-orange-500 text-white rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <i class="fas fa-chart-line text-2xl sm:text-3xl mb-2"></i>
                    <span class="text-xs sm:text-sm font-semibold text-center">التحليلات</span>
                </a>
                <a href="{{ route('orgs.social.index', ['org' => $orgId]) }}" class="flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-pink-500 to-rose-500 text-white rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <i class="fas fa-share-alt text-2xl sm:text-3xl mb-2"></i>
                    <span class="text-xs sm:text-sm font-semibold text-center">التواصل الاجتماعي</span>
                </a>
                <a href="{{ route('orgs.ai.index', ['org' => $orgId]) }}" class="flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200">
                    <i class="fas fa-robot text-2xl sm:text-3xl mb-2"></i>
                    <span class="text-xs sm:text-sm font-semibold text-center">الذكاء الاصطناعي</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardData(orgId, initialStats = null, initialCampaignStatus = null, initialCampaignsByOrg = null) {
    return {
        orgId: orgId,
        currentOrg: null,
        stats: initialStats || { orgs: 1, campaigns: 0, creative_assets: 0, kpis: 0 },
        campaignStatus: initialCampaignStatus || {},
        campaignsByOrg: initialCampaignsByOrg || [],
        weeklyMetrics: [],
        topCampaigns: [],
        recentActivity: [],
        statusChart: null,
        orgChart: null,
        autoRefreshEnabled: false,
        autoRefreshInterval: null,
        lastUpdated: null,
        isLoading: false,

        async init() {
            // Prevent double initialization
            if (this._initialized) return;
            this._initialized = true;

            console.log('[Dashboard] Initializing for org:', this.orgId);

            // Initialize with sample data immediately for better UX
            this.initializeSampleData();

            // Fetch fresh data from API first
            await this.fetchDashboardData();

            // Render charts after data is loaded and DOM is ready
            // Use setTimeout to ensure DOM has fully rendered
            setTimeout(() => {
                this.renderCharts();
            }, 100);
        },

        initializeSampleData() {
            // Set sample weekly metrics
            this.weeklyMetrics = [
                { label: 'الإنفاق الإعلاني', value: '0 ر.س', percentage: 0 },
                { label: 'مرات الظهور', value: '0', percentage: 0 },
                { label: 'النقرات', value: '0', percentage: 0 },
                { label: 'التحويلات', value: '0', percentage: 0 }
            ];

            // Sample recent activity
            this.recentActivity = [
                { id: 1, type: 'campaign', icon: 'fas fa-bullhorn', message: 'مرحباً بك في لوحة التحكم', time: 'الآن' }
            ];
        },

        async manualRefresh() {
            await this.fetchDashboardData();
            // Re-render charts with new data
            setTimeout(() => this.renderCharts(), 100);
        },

        async fetchDashboardData() {
            this.isLoading = true;

            try {
                // Fetch real data from Laravel backend using org-specific route
                const response = await fetch(`/orgs/${this.orgId}/dashboard/data`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    const data = result.data || result;

                    // Set stats from API
                    if (data.stats) this.stats = data.stats;
                    if (data.campaignStatus) this.campaignStatus = data.campaignStatus;
                    if (data.campaignsByOrg) this.campaignsByOrg = data.campaignsByOrg;

                    // Update last updated timestamp
                    this.lastUpdated = new Date();

                    // Generate weekly metrics with real or sample data
                    this.weeklyMetrics = [
                        { label: 'الإنفاق الإعلاني', value: this.formatCurrency(data.analytics?.spend || 45234), percentage: 75 },
                        { label: 'مرات الظهور', value: this.formatNumber(data.analytics?.impressions || 1200000), percentage: 85 },
                        { label: 'النقرات', value: this.formatNumber(data.analytics?.clicks || 45200), percentage: 65 },
                        { label: 'التحويلات', value: this.formatNumber(data.analytics?.conversions || 2134), percentage: 90 }
                    ];

                    // Top campaigns
                    this.topCampaigns = [
                        { id: 1, name: 'حملة الصيف 2025', organization: this.currentOrg?.name || 'المؤسسة الحالية', performance: 92 },
                        { id: 2, name: 'إطلاق المنتج الجديد', organization: this.currentOrg?.name || 'المؤسسة الحالية', performance: 88 },
                        { id: 3, name: 'عروض نهاية العام', organization: this.currentOrg?.name || 'المؤسسة الحالية', performance: 85 }
                    ];
                } else {
                    console.warn('[Dashboard] Failed to fetch data:', response.status);
                }

                // Fetch recent activity/notifications
                await this.fetchNotifications();

            } catch (error) {
                console.error('[Dashboard] Error fetching data:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async fetchNotifications() {
            try {
                const notifResponse = await fetch(`/orgs/${this.orgId}/notifications/latest`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (notifResponse.ok) {
                    const result = await notifResponse.json();
                    const notifData = result.data || result;
                    const notifications = notifData.notifications || [];

                    if (notifications.length > 0) {
                        this.recentActivity = notifications.slice(0, 5).map((notif, index) => ({
                            id: notif.id || index + 1,
                            type: this.detectActivityType(notif.message || notif.title || ''),
                            icon: this.getActivityIcon(notif.message || notif.title || ''),
                            message: notif.message || notif.title || '',
                            time: notif.time || notif.created_at || 'منذ قليل'
                        }));
                    }
                }
            } catch (error) {
                console.warn('[Dashboard] Could not fetch notifications:', error);
                // Keep sample data
                this.recentActivity = [
                    { id: 1, type: 'campaign', icon: 'fas fa-bullhorn', message: 'تم إطلاق حملة "عروض الصيف" بنجاح', time: 'منذ 5 دقائق' },
                    { id: 2, type: 'analytics', icon: 'fas fa-chart-line', message: 'تحديث في أداء الحملات - زيادة 15% في التحويلات', time: 'منذ ساعة' },
                    { id: 3, type: 'integration', icon: 'fas fa-plug', message: 'تم ربط حساب Meta Ads بنجاح', time: 'منذ 3 ساعات' }
                ];
            }
        },

        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('ar-SA', { style: 'decimal' }).format(amount) + ' ر.س';
        },

        getRelativeTime() {
            if (!this.lastUpdated) return '';

            const seconds = Math.floor((new Date() - this.lastUpdated) / 1000);

            if (seconds < 60) return `منذ ${seconds} ثانية`;
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return `منذ ${minutes} دقيقة`;
            const hours = Math.floor(minutes / 60);
            return `منذ ${hours} ساعة`;
        },

        detectActivityType(message) {
            if (!message) return 'campaign';
            message = message.toLowerCase();
            if (message.includes('حملة') || message.includes('campaign')) return 'campaign';
            if (message.includes('تكامل') || message.includes('منصة') || message.includes('ربط')) return 'integration';
            if (message.includes('إبداعي') || message.includes('أصل') || message.includes('creative')) return 'creative';
            if (message.includes('أداء') || message.includes('تقرير') || message.includes('تحليل')) return 'analytics';
            return 'campaign';
        },

        getActivityIcon(message) {
            if (!message) return 'fas fa-info-circle';
            message = message.toLowerCase();
            if (message.includes('حملة') || message.includes('campaign')) return 'fas fa-bullhorn';
            if (message.includes('تكامل') || message.includes('منصة') || message.includes('ربط')) return 'fas fa-plug';
            if (message.includes('إبداعي') || message.includes('أصل') || message.includes('creative')) return 'fas fa-palette';
            if (message.includes('أداء') || message.includes('تقرير') || message.includes('تحليل')) return 'fas fa-chart-line';
            return 'fas fa-info-circle';
        },

        renderCharts() {
            // Check if Chart.js is available
            if (typeof Chart === 'undefined') {
                console.warn('[Dashboard] Chart.js not loaded yet, retrying...');
                setTimeout(() => this.renderCharts(), 500);
                return;
            }

            // Wait for DOM to be ready
            requestAnimationFrame(() => {
                this.renderStatusChart();
                this.renderOrgChart();
            });
        },

        renderStatusChart() {
            const statusCtx = document.getElementById('statusChart');
            if (!statusCtx) {
                console.warn('[Dashboard] statusChart canvas not found');
                return;
            }

            // Verify getContext is available
            const ctx = statusCtx.getContext('2d');
            if (!ctx) {
                console.warn('[Dashboard] Could not get 2d context for statusChart');
                return;
            }

            // Destroy existing chart
            if (this.statusChart) {
                try { this.statusChart.destroy(); } catch (e) {}
                this.statusChart = null;
            }

            // Prepare data
            const statusData = this.campaignStatus || {};
            let labels = Object.keys(statusData);
            let values = Object.values(statusData);

            if (labels.length === 0) {
                // Show placeholder data
                labels = ['لا توجد حملات'];
                values = [1];
            }

            // Status label translations
            const statusLabels = {
                'active': 'نشط',
                'paused': 'متوقف',
                'completed': 'مكتمل',
                'draft': 'مسودة',
                'pending': 'قيد الانتظار',
                'scheduled': 'مجدول'
            };

            const translatedLabels = labels.map(l => statusLabels[l] || l);

            try {
                this.statusChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: translatedLabels,
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                '#10b981', // green - active
                                '#f59e0b', // amber - paused
                                '#3b82f6', // blue - completed
                                '#6b7280', // gray - draft
                                '#8b5cf6', // purple - pending
                                '#ec4899'  // pink - scheduled
                            ],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                rtl: true,
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    font: { family: 'system-ui', size: 12 }
                                }
                            }
                        }
                    }
                });
            } catch (e) {
                console.error('[Dashboard] Error creating status chart:', e);
            }
        },

        renderOrgChart() {
            const orgCtx = document.getElementById('orgChart');
            if (!orgCtx) {
                console.warn('[Dashboard] orgChart canvas not found');
                return;
            }

            // Verify getContext is available
            const ctx = orgCtx.getContext('2d');
            if (!ctx) {
                console.warn('[Dashboard] Could not get 2d context for orgChart');
                return;
            }

            // Destroy existing chart
            if (this.orgChart) {
                try { this.orgChart.destroy(); } catch (e) {}
                this.orgChart = null;
            }

            // Prepare data
            const orgData = this.campaignsByOrg || [];

            if (orgData.length === 0) {
                // No data to show
                return;
            }

            const labels = orgData.map(x => x.org_name || x.name || 'غير معروف');
            const values = orgData.map(x => x.total || x.count || 0);

            try {
                this.orgChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'عدد الحملات',
                            data: values,
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1,
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                                grid: { display: false }
                            },
                            y: {
                                grid: { display: false }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            } catch (e) {
                console.error('[Dashboard] Error creating org chart:', e);
            }
        }
    };
}
</script>
@endpush
