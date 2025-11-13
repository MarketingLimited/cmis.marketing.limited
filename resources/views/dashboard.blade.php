@extends('layouts.admin')

@section('title', 'لوحة التحكم')

@section('content')
<div x-data="dashboardData(@json($stats ?? []), @json($campaignStatus ?? []), @json($campaignsByOrg ?? []))" x-init="init()">

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">لوحة التحكم</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">نظرة شاملة على أداء النظام والحملات التسويقية</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- Organizations -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">المؤسسات</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2" x-text="stats ? (stats.orgs || 0) : 0"></p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="fas fa-building text-blue-600 dark:text-blue-300 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Campaigns -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">الحملات النشطة</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2" x-text="stats ? (stats.campaigns || 0) : 0"></p>
                </div>
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <i class="fas fa-bullhorn text-green-600 dark:text-green-300 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Creative Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">الأصول الإبداعية</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2" x-text="stats ? (stats.creative_assets || 0) : 0"></p>
                </div>
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                    <i class="fas fa-palette text-purple-600 dark:text-purple-300 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">مؤشرات الأداء</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2" x-text="stats ? (stats.kpis || 0) : 0"></p>
                </div>
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                    <i class="fas fa-chart-line text-yellow-600 dark:text-yellow-300 text-2xl"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        <!-- Campaign Status Chart -->
        <x-ui.card title="توزيع الحملات حسب الحالة">
            <div class="h-64 flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </x-ui.card>

        <!-- Campaigns by Organization -->
        <x-ui.card title="الحملات حسب المؤسسة">
            <div class="h-64 flex items-center justify-center">
                <canvas id="orgChart"></canvas>
            </div>
        </x-ui.card>

    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Weekly Performance -->
        <x-ui.card title="الأداء الأسبوعي">
            <div class="space-y-4">
                <template x-for="metric in weeklyMetrics" :key="metric.label">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400" x-text="metric.label"></span>
                        <div class="flex items-center">
                            <div class="w-32 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mr-3">
                                <div class="h-full bg-blue-600 rounded-full" :style="'width: ' + metric.percentage + '%'"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="metric.value"></span>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <!-- Top Campaigns -->
        <x-ui.card title="أفضل الحملات">
            <div class="space-y-3">
                <template x-for="campaign in topCampaigns" :key="campaign.id">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="campaign.name"></h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="campaign.organization"></p>
                        </div>
                        <div class="text-left">
                            <span class="text-lg font-bold text-green-600" x-text="campaign.performance + '%'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <!-- Recent Activity -->
        <x-ui.card title="النشاط الأخير">
            <div class="space-y-4">
                <template x-for="activity in recentActivity" :key="activity.id">
                    <div class="flex items-start">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                             :class="{
                                 'bg-blue-100 text-blue-600': activity.type === 'campaign',
                                 'bg-green-100 text-green-600': activity.type === 'integration',
                                 'bg-purple-100 text-purple-600': activity.type === 'creative',
                                 'bg-yellow-100 text-yellow-600': activity.type === 'analytics'
                             }">
                            <i :class="activity.icon" class="text-sm"></i>
                        </div>
                        <div class="mr-3 flex-1">
                            <p class="text-sm text-gray-900 dark:text-white" x-text="activity.message"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="activity.time"></p>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

    </div>

    <!-- Quick Actions -->
    <x-ui.card title="إجراءات سريعة">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <a href="#" class="flex flex-col items-center p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg hover:shadow-lg transition" onclick="alert('قريباً'); return false;">
                <i class="fas fa-plus-circle text-3xl mb-2"></i>
                <span class="text-sm font-semibold">حملة جديدة</span>
            </a>
            <a href="#" class="flex flex-col items-center p-4 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg hover:shadow-lg transition" onclick="alert('قريباً'); return false;">
                <i class="fas fa-building text-3xl mb-2"></i>
                <span class="text-sm font-semibold">مؤسسة جديدة</span>
            </a>
            <a href="#" class="flex flex-col items-center p-4 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg hover:shadow-lg transition" onclick="alert('قريباً'); return false;">
                <i class="fas fa-palette text-3xl mb-2"></i>
                <span class="text-sm font-semibold">محتوى إبداعي</span>
            </a>
            <a href="#" class="flex flex-col items-center p-4 bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-lg hover:shadow-lg transition" onclick="alert('قريباً'); return false;">
                <i class="fas fa-chart-line text-3xl mb-2"></i>
                <span class="text-sm font-semibold">التحليلات</span>
            </a>
            <a href="#" class="flex flex-col items-center p-4 bg-gradient-to-br from-red-500 to-red-600 text-white rounded-lg hover:shadow-lg transition" onclick="alert('قريباً'); return false;">
                <i class="fas fa-plug text-3xl mb-2"></i>
                <span class="text-sm font-semibold">التكاملات</span>
            </a>
            <a href="#" class="flex flex-col items-center p-4 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-lg hover:shadow-lg transition" onclick="alert('قريباً'); return false;">
                <i class="fas fa-robot text-3xl mb-2"></i>
                <span class="text-sm font-semibold">الذكاء الاصطناعي</span>
            </a>
        </div>
    </x-ui.card>

</div>
@endsection

@push('scripts')
<script>
function dashboardData(initialStats = null, initialCampaignStatus = null, initialCampaignsByOrg = null) {
    return {
        stats: initialStats,
        campaignStatus: initialCampaignStatus,
        campaignsByOrg: initialCampaignsByOrg,
        weeklyMetrics: [],
        topCampaigns: [],
        recentActivity: [],
        statusChart: null,
        orgChart: null,

        async init() {
            // If we have initial data, render charts immediately
            if (this.stats && this.campaignStatus) {
                this.renderCharts();
            }

            // Fetch fresh data from API
            await this.fetchDashboardData();

            // Auto-refresh every 30 seconds
            setInterval(() => {
                this.fetchDashboardData();
            }, 30000);
        },

        async fetchDashboardData() {
            try {
                // Fetch real data from Laravel backend
                const response = await fetch('/dashboard/data');

                if (!response.ok) {
                    throw new Error('Failed to fetch dashboard data');
                }

                const data = await response.json();

                // Set stats from API
                this.stats = data.stats;
                this.campaignStatus = data.campaignStatus;
                this.campaignsByOrg = data.campaignsByOrg;

                // Generate weekly metrics (can be enhanced with real API data)
                this.weeklyMetrics = [
                    { label: 'الإنفاق الإعلاني', value: '45,234 ر.س', percentage: 75 },
                    { label: 'مرات الظهور', value: '1.2M', percentage: 85 },
                    { label: 'النقرات', value: '45.2K', percentage: 65 },
                    { label: 'التحويلات', value: '2,134', percentage: 90 }
                ];

                // Top campaigns (simulated - can be added to backend later)
                this.topCampaigns = [
                    { id: 1, name: 'حملة الصيف 2025', organization: 'شركة التسويق', performance: 92 },
                    { id: 2, name: 'إطلاق المنتج الجديد', organization: 'الإبداع الرقمي', performance: 88 },
                    { id: 3, name: 'عروض رمضان', organization: 'التقنية المتقدمة', performance: 85 }
                ];

                // Fetch recent activity/notifications
                const notifResponse = await fetch('/notifications/latest');
                if (notifResponse.ok) {
                    const notifications = await notifResponse.json();
                    this.recentActivity = notifications.map((notif, index) => ({
                        id: index + 1,
                        type: this.detectActivityType(notif.message),
                        icon: this.getActivityIcon(notif.message),
                        message: notif.message,
                        time: notif.time
                    }));
                }

                // Re-render charts with new data
                this.renderCharts();

            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                window.notify('فشل تحميل بيانات لوحة التحكم', 'error');
            }
        },

        detectActivityType(message) {
            if (message.includes('حملة')) return 'campaign';
            if (message.includes('تكامل') || message.includes('منصة')) return 'integration';
            if (message.includes('إبداعي') || message.includes('أصل')) return 'creative';
            if (message.includes('أداء') || message.includes('تقرير')) return 'analytics';
            return 'campaign';
        },

        getActivityIcon(message) {
            if (message.includes('حملة')) return 'fas fa-bullhorn';
            if (message.includes('تكامل') || message.includes('منصة')) return 'fas fa-plug';
            if (message.includes('إبداعي') || message.includes('أصل')) return 'fas fa-palette';
            if (message.includes('أداء') || message.includes('تقرير')) return 'fas fa-chart-line';
            return 'fas fa-info-circle';
        },

        renderCharts() {
            // Status Pie Chart
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx && this.campaignStatus) {
                if (this.statusChart) this.statusChart.destroy();

                this.statusChart = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(this.campaignStatus),
                        datasets: [{
                            data: Object.values(this.campaignStatus),
                            backgroundColor: [
                                '#10b981',
                                '#3b82f6',
                                '#8b5cf6',
                                '#ef4444'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                rtl: true
                            }
                        }
                    }
                });
            }

            // Organizations Bar Chart
            const orgCtx = document.getElementById('orgChart');
            if (orgCtx && this.campaignsByOrg && this.campaignsByOrg.length > 0) {
                if (this.orgChart) this.orgChart.destroy();

                this.orgChart = new Chart(orgCtx, {
                    type: 'bar',
                    data: {
                        labels: this.campaignsByOrg.map(x => x.org_name),
                        datasets: [{
                            label: 'عدد الحملات',
                            data: this.campaignsByOrg.map(x => x.total),
                            backgroundColor: '#3b82f6',
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        }
    };
}
</script>
@endpush
