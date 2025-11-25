@extends('layouts.admin')

@section('title', $org->name)

@section('content')
<div x-data="orgDetails({{ Js::from($org) }}, {{ Js::from($stats) }}, {{ Js::from($recentCampaigns) }}, {{ Js::from($teamMembers) }}, {{ Js::from($activities) }}, {{ Js::from($performanceData) }})" x-init="init()">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-700 rounded-2xl p-8 mb-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-32 translate-x-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-24 -translate-x-24"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                        <i class="fas fa-building text-5xl"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold mb-2" x-text="org.name">{{ $org->name }}</h1>
                        <div class="flex items-center gap-4 text-white/80">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-globe"></i>
                                <span x-text="org.default_locale || 'ar'">{{ $org->default_locale }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <i class="fas fa-coins"></i>
                                <span x-text="org.currency || 'SAR'">{{ $org->currency }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <i class="fas fa-calendar"></i>
                                <span x-text="formatDate(org.created_at)"></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('orgs.edit', ['org' => $org->org_id]) }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-6 py-3 rounded-xl flex items-center gap-2 transition">
                        <i class="fas fa-edit"></i>
                        تعديل
                    </a>
                    <button @click="showSettings = true"
                            class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-6 py-3 rounded-xl flex items-center gap-2 transition">
                        <i class="fas fa-cog"></i>
                        الإعدادات
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-bullhorn text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1" x-text="stats.campaigns_count">{{ $stats['campaigns_count'] }}</h3>
            <p class="text-gray-600 dark:text-gray-400">الحملات</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-users text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1" x-text="stats.team_members_count">{{ $stats['team_members_count'] }}</h3>
            <p class="text-gray-600 dark:text-gray-400">أعضاء الفريق</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-palette text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1" x-text="stats.assets_count">{{ $stats['assets_count'] }}</h3>
            <p class="text-gray-600 dark:text-gray-400">الأصول الإبداعية</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <i class="fas fa-coins text-2xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1" x-text="formatBudget(stats.total_budget)">{{ number_format($stats['total_budget'], 0) }}</h3>
            <p class="text-gray-600 dark:text-gray-400">إجمالي الميزانية</p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Quick Actions & Campaigns -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">الإجراءات السريعة</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="{{ route('orgs.campaigns.index', ['org' => $org->org_id]) }}"
                       class="flex flex-col items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/40 transition group">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="fas fa-bullhorn text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">الحملات</span>
                    </a>
                    <a href="{{ route('orgs.products', ['org' => $org->org_id]) }}"
                       class="flex flex-col items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl hover:bg-green-100 dark:hover:bg-green-900/40 transition group">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="fas fa-box text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">المنتجات</span>
                    </a>
                    <a href="{{ route('orgs.services', ['org' => $org->org_id]) }}"
                       class="flex flex-col items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/40 transition group">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="fas fa-concierge-bell text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">الخدمات</span>
                    </a>
                    <button @click="createCampaign()"
                            class="flex flex-col items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition group">
                        <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="fas fa-plus text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">حملة جديدة</span>
                    </button>
                </div>
            </div>

            <!-- Recent Campaigns -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">أحدث الحملات</h3>
                    <a href="{{ route('orgs.campaigns.index', ['org' => $org->org_id]) }}" class="text-blue-600 hover:text-blue-700 text-sm">
                        عرض الكل <i class="fas fa-arrow-left mr-1"></i>
                    </a>
                </div>
                <div class="space-y-4">
                    <template x-for="campaign in recentCampaigns" :key="campaign.id">
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                     :class="getCampaignStatusColor(campaign.status)">
                                    <i class="fas fa-bullhorn text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white" x-text="campaign.name"></h4>
                                    <p class="text-xs text-gray-500" x-text="campaign.budget"></p>
                                </div>
                            </div>
                            <div class="text-left">
                                <span class="text-xs px-2 py-1 rounded-full"
                                      :class="getStatusBadgeClass(campaign.status)"
                                      x-text="getStatusLabel(campaign.status)"></span>
                                <p class="text-xs text-gray-500 mt-1">
                                    <span class="font-semibold" x-text="campaign.performance + '%'"></span> أداء
                                </p>
                            </div>
                        </div>
                    </template>
                    <template x-if="recentCampaigns.length === 0">
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-bullhorn text-4xl mb-2 opacity-30"></i>
                            <p>لا توجد حملات بعد</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">أداء المؤسسة</h3>
                <div class="h-64">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Right Column - Team & Activity -->
        <div class="space-y-8">
            <!-- Team Members -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">فريق العمل</h3>
                    <button @click="inviteMember()" class="text-blue-600 hover:text-blue-700 text-sm">
                        <i class="fas fa-user-plus"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <template x-for="member in teamMembers" :key="member.id">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                <span x-text="member.name.charAt(0)"></span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="member.name"></h4>
                                <p class="text-xs text-gray-500" x-text="member.role"></p>
                            </div>
                            <span class="w-2 h-2 rounded-full" :class="member.online ? 'bg-green-500' : 'bg-gray-400'"></span>
                        </div>
                    </template>
                    <template x-if="teamMembers.length === 0">
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-users text-2xl mb-2 opacity-30"></i>
                            <p class="text-sm">لا يوجد أعضاء</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">النشاط الأخير</h3>
                <div class="space-y-4">
                    <template x-for="activity in activities" :key="activity.id">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm"
                                 :class="getActivityIconClass(activity.action)">
                                <i :class="getActivityIcon(activity.action)"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 dark:text-white" x-text="activity.message"></p>
                                <p class="text-xs text-gray-500" x-text="activity.time"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="activities.length === 0">
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-history text-2xl mb-2 opacity-30"></i>
                            <p class="text-sm">لا يوجد نشاط</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Organization Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">معلومات المؤسسة</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">المعرف</span>
                        <span class="text-sm font-mono text-gray-900 dark:text-white" x-text="org.org_id?.substring(0, 8) + '...'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">اللغة الافتراضية</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="org.default_locale"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">العملة</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="org.currency"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">تاريخ الإنشاء</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatDate(org.created_at)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function orgDetails(serverOrg, serverStats, serverCampaigns, serverTeamMembers, serverActivities, serverPerformanceData) {
    return {
        org: serverOrg || {},
        stats: serverStats || { campaigns_count: 0, team_members_count: 0, assets_count: 0, total_budget: 0 },
        recentCampaigns: serverCampaigns || [],
        teamMembers: serverTeamMembers || [],
        activities: serverActivities || [],
        performanceData: serverPerformanceData || { labels: [], impressions: [], clicks: [], conversions: [] },
        showSettings: false,
        performanceChart: null,

        init() {
            this.$nextTick(() => {
                this.renderPerformanceChart();
            });
        },

        renderPerformanceChart() {
            const ctx = document.getElementById('performanceChart');
            if (!ctx || !ctx.getContext) return;

            this.performanceChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: this.performanceData.labels,
                    datasets: [
                        {
                            label: 'الانطباعات',
                            data: this.performanceData.impressions,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'النقرات',
                            data: this.performanceData.clicks,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'التحويلات',
                            data: this.performanceData.conversions,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            rtl: true
                        }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },

        formatDate(dateString) {
            if (!dateString) return 'غير متوفر';
            return new Date(dateString).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        formatBudget(amount) {
            if (!amount) return '0';
            return new Intl.NumberFormat('ar-SA').format(amount);
        },

        getCampaignStatusColor(status) {
            const colors = {
                'active': 'bg-green-500',
                'planning': 'bg-blue-500',
                'draft': 'bg-gray-500',
                'completed': 'bg-purple-500',
                'paused': 'bg-yellow-500'
            };
            return colors[status] || 'bg-gray-500';
        },

        getStatusBadgeClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-800',
                'planning': 'bg-blue-100 text-blue-800',
                'draft': 'bg-gray-100 text-gray-800',
                'completed': 'bg-purple-100 text-purple-800',
                'paused': 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getStatusLabel(status) {
            const labels = {
                'active': 'نشط',
                'planning': 'تخطيط',
                'draft': 'مسودة',
                'completed': 'مكتمل',
                'paused': 'متوقف'
            };
            return labels[status] || status;
        },

        getActivityIcon(action) {
            const icons = {
                'create': 'fas fa-plus',
                'update': 'fas fa-edit',
                'delete': 'fas fa-trash',
                'login': 'fas fa-sign-in-alt',
                'logout': 'fas fa-sign-out-alt'
            };
            return icons[action] || 'fas fa-info';
        },

        getActivityIconClass(action) {
            const classes = {
                'create': 'bg-green-100 text-green-600',
                'update': 'bg-blue-100 text-blue-600',
                'delete': 'bg-red-100 text-red-600',
                'login': 'bg-purple-100 text-purple-600',
                'logout': 'bg-yellow-100 text-yellow-600'
            };
            return classes[action] || 'bg-gray-100 text-gray-600';
        },

        createCampaign() {
            window.location.href = '/campaigns/create';
        },

        inviteMember() {
            if (window.notify) window.notify('ميزة دعوة الأعضاء قريباً', 'info');
        }
    };
}
</script>
@endpush
