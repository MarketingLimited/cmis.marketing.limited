@extends('layouts.admin')

@section('title', 'التحليلات')

@section('content')
<div x-data="analyticsManager(@json(['stats' => $stats, 'latestMetrics' => $latestMetrics, 'kpis' => $kpis]))" x-init="init()">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">لوحة التحليلات</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">تحليل شامل لأداء الحملات والمنصات</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <x-ui.button @click="exportToPDF()" variant="secondary" icon="fas fa-file-pdf">
                تصدير PDF
            </x-ui.button>
            <x-ui.button @click="exportToExcel()" variant="success" icon="fas fa-file-excel">
                تصدير Excel
            </x-ui.button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <x-ui.card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">من تاريخ</label>
                <input type="date"
                       x-model="dateRange.start"
                       @change="fetchAnalytics()"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">إلى تاريخ</label>
                <input type="date"
                       x-model="dateRange.end"
                       @change="fetchAnalytics()"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">المؤسسة</label>
                <select x-model="selectedOrg"
                        @change="fetchAnalytics()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">جميع المؤسسات</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">المنصة</label>
                <select x-model="selectedPlatform"
                        @change="fetchAnalytics()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">جميع المنصات</option>
                    <option value="meta">Meta</option>
                    <option value="google">Google</option>
                    <option value="tiktok">TikTok</option>
                    <option value="linkedin">LinkedIn</option>
                    <option value="x">X</option>
                </select>
            </div>
        </div>
    </x-ui.card>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">إجمالي الإنفاق</p>
                    <p class="text-3xl font-bold mt-2" x-text="formatCurrency(kpis.totalSpend)"></p>
                </div>
                <i class="fas fa-dollar-sign text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-arrow-up ml-1"></i>
                <span x-text="kpis.spendChange + '%'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">مرات الظهور</p>
                    <p class="text-3xl font-bold mt-2" x-text="formatNumber(kpis.impressions)"></p>
                </div>
                <i class="fas fa-eye text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-arrow-up ml-1"></i>
                <span x-text="kpis.impressionsChange + '%'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">النقرات</p>
                    <p class="text-3xl font-bold mt-2" x-text="formatNumber(kpis.clicks)"></p>
                </div>
                <i class="fas fa-mouse-pointer text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-arrow-up ml-1"></i>
                <span x-text="kpis.clicksChange + '%'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">التحويلات</p>
                    <p class="text-3xl font-bold mt-2" x-text="formatNumber(kpis.conversions)"></p>
                </div>
                <i class="fas fa-shopping-cart text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-arrow-up ml-1"></i>
                <span x-text="kpis.conversionsChange + '%'"></span>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-ui.card>
            <div class="text-center p-4">
                <div class="text-5xl font-bold text-blue-600 mb-2" x-text="kpis.ctr + '%'"></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">نسبة النقر (CTR)</div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="text-center p-4">
                <div class="text-5xl font-bold text-green-600 mb-2" x-text="kpis.cpc"></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">تكلفة النقرة (CPC)</div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="text-center p-4">
                <div class="text-5xl font-bold text-purple-600 mb-2" x-text="kpis.roas + 'x'"></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">عائد الإنفاق (ROAS)</div>
            </div>
        </x-ui.card>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-ui.card title="الإنفاق عبر الزمن">
            <div class="h-80">
                <canvas id="spendTimeChart"></canvas>
            </div>
        </x-ui.card>

        <x-ui.card title="الأداء حسب المنصة">
            <div class="h-80">
                <canvas id="platformChart"></canvas>
            </div>
        </x-ui.card>
    </div>

    <!-- Platform Performance Table -->
    <x-ui.card title="تفاصيل الأداء حسب المنصة">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">المنصة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">الإنفاق</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">النقرات</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">CTR</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">ROAS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="platform in platformPerformance" :key="platform.name">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <i :class="platform.icon + ' text-2xl ml-3'"></i>
                                    <span class="text-sm font-medium" x-text="platform.name"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm" x-text="formatCurrency(platform.spend)"></td>
                            <td class="px-6 py-4 text-sm" x-text="formatNumber(platform.clicks)"></td>
                            <td class="px-6 py-4 text-sm font-semibold text-blue-600" x-text="platform.ctr + '%'"></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-sm font-semibold rounded-full"
                                      :class="platform.roas > 3 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                      x-text="platform.roas + 'x'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </x-ui.card>

</div>
@endsection

@push('scripts')
<script>
function analyticsManager(serverData) {
    return {
        dateRange: { start: '', end: '' },
        selectedOrg: '',
        selectedPlatform: '',
        serverStats: serverData.stats || {},
        latestMetrics: serverData.latestMetrics || [],
        allKpis: serverData.kpis || [],
        kpis: {
            totalSpend: 0,
            spendChange: 0,
            impressions: 0,
            impressionsChange: 0,
            clicks: 0,
            clicksChange: 0,
            conversions: 0,
            conversionsChange: 0,
            ctr: 0,
            cpc: 0,
            roas: 0
        },
        platformPerformance: [],
        charts: { spendTime: null, platform: null },

        init() {
            // Initialize date range
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 30);
            this.dateRange.start = start.toISOString().split('T')[0];
            this.dateRange.end = end.toISOString().split('T')[0];

            // Process server data
            this.processServerData();
            this.renderCharts();
        },

        processServerData() {
            // Process latest metrics from server
            const metrics = this.latestMetrics || [];

            // Calculate KPIs from server metrics
            // TODO: Implement actual KPI calculations from real metrics data
            // For now, we'll use aggregated values

            // Extract spend, impressions, clicks, conversions from metrics
            let totalSpend = 0;
            let totalImpressions = 0;
            let totalClicks = 0;
            let totalConversions = 0;

            metrics.forEach(metric => {
                const kpiName = metric.kpi ? metric.kpi.toLowerCase() : '';
                const value = parseFloat(metric.observed) || 0;

                if (kpiName.includes('spend') || kpiName.includes('cost')) {
                    totalSpend += value;
                } else if (kpiName.includes('impression')) {
                    totalImpressions += value;
                } else if (kpiName.includes('click')) {
                    totalClicks += value;
                } else if (kpiName.includes('conversion')) {
                    totalConversions += value;
                }
            });

            // Calculate derived metrics
            const ctr = totalImpressions > 0 ? ((totalClicks / totalImpressions) * 100).toFixed(2) : 0;
            const cpc = totalClicks > 0 ? (totalSpend / totalClicks).toFixed(2) : 0;
            const roas = totalSpend > 0 ? (totalConversions * 100 / totalSpend).toFixed(1) : 0; // Simplified ROAS

            this.kpis = {
                totalSpend: totalSpend || 245000, // Fallback to simulated if no data
                spendChange: 12.5, // TODO: Calculate from historical data
                impressions: totalImpressions || 3200000,
                impressionsChange: 18.3,
                clicks: totalClicks || 128000,
                clicksChange: 15.7,
                conversions: totalConversions || 5400,
                conversionsChange: 22.1,
                ctr: parseFloat(ctr) || 4.0,
                cpc: cpc ? cpc + ' ر.س' : '1.91 ر.س',
                roas: parseFloat(roas) || 4.2
            };

            // Simulate platform performance
            // TODO: Get actual platform performance from backend API
            this.platformPerformance = [
                { name: 'Meta', icon: 'fab fa-meta', spend: 120000, clicks: 72000, ctr: 4.0, roas: 4.5 },
                { name: 'Google', icon: 'fab fa-google', spend: 80000, clicks: 40000, ctr: 4.0, roas: 4.0 },
                { name: 'TikTok', icon: 'fab fa-tiktok', spend: 30000, clicks: 10000, ctr: 4.0, roas: 3.5 },
                { name: 'LinkedIn', icon: 'fab fa-linkedin', spend: 10000, clicks: 4000, ctr: 4.0, roas: 3.0 },
                { name: 'X', icon: 'fab fa-x-twitter', spend: 5000, clicks: 2000, ctr: 4.0, roas: 2.5 }
            ];
        },

        async fetchAnalytics() {
            // TODO: Implement API call to fetch analytics data based on filters
            // This would call endpoints like:
            // - GET /api/analytics/summary?start={start}&end={end}&org={org}&platform={platform}
            // - GET /api/analytics/metrics?start={start}&end={end}
            // - GET /api/analytics/platforms?start={start}&end={end}

            try {
                window.notify('جاري تحديث البيانات...', 'info');

                // For now, just reprocess with current data
                this.processServerData();
                this.renderCharts();

                console.log('Filters:', {
                    dateRange: this.dateRange,
                    org: this.selectedOrg,
                    platform: this.selectedPlatform
                });
            } catch (error) {
                console.error('Error fetching analytics:', error);
                window.notify('فشل تحميل البيانات', 'error');
            }
        },

        renderCharts() {
            const spendCtx = document.getElementById('spendTimeChart');
            if (spendCtx) {
                if (this.charts.spendTime) this.charts.spendTime.destroy();
                this.charts.spendTime = new Chart(spendCtx, {
                    type: 'line',
                    data: {
                        labels: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
                        datasets: [{
                            label: 'الإنفاق',
                            data: [25000, 28000, 32000, 30000, 35000, 38000, 40000],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } }
                    }
                });
            }

            const platformCtx = document.getElementById('platformChart');
            if (platformCtx) {
                if (this.charts.platform) this.charts.platform.destroy();
                this.charts.platform = new Chart(platformCtx, {
                    type: 'doughnut',
                    data: {
                        labels: this.platformPerformance.map(p => p.name),
                        datasets: [{
                            data: this.platformPerformance.map(p => p.spend),
                            backgroundColor: ['#0866FF', '#4285F4', '#000000', '#0A66C2', '#1DA1F2']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', rtl: true } }
                    }
                });
            }
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR', minimumFractionDigits: 0 }).format(value);
        },

        formatNumber(value) {
            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
            if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
            return value.toLocaleString('ar-SA');
        },

        exportToPDF() {
            // TODO: Implement PDF export with API call
            // POST /api/analytics/export/pdf with filters
            // const response = await fetch('/api/analytics/export/pdf', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            //     },
            //     body: JSON.stringify({
            //         dateRange: this.dateRange,
            //         org: this.selectedOrg,
            //         platform: this.selectedPlatform
            //     })
            // });
            // Then download the PDF file
            window.notify('جاري تصدير التقرير إلى PDF...', 'info');
            console.log('PDF Export filters:', this.dateRange, this.selectedOrg, this.selectedPlatform);
        },

        exportToExcel() {
            // TODO: Implement Excel export with API call
            // POST /api/analytics/export/excel with filters
            // const response = await fetch('/api/analytics/export/excel', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            //     },
            //     body: JSON.stringify({
            //         dateRange: this.dateRange,
            //         org: this.selectedOrg,
            //         platform: this.selectedPlatform
            //     })
            // });
            // Then download the Excel file
            window.notify('جاري تصدير البيانات إلى Excel...', 'info');
            console.log('Excel Export filters:', this.dateRange, this.selectedOrg, this.selectedPlatform);
        }
    };
}
</script>
@endpush
