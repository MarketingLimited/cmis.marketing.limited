@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', 'تحسين الحملات')
@section('page-subtitle', 'تحسين تلقائي وذكي لأداء الحملات الإعلانية')

@section('content')
<div x-data="optimizationManager()" x-init="init()">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">التحسينات النشطة</p>
                    <p class="text-3xl font-bold" x-text="stats.activeOptimizations"></p>
                </div>
                <i class="fas fa-magic text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">التوفير هذا الشهر</p>
                    <p class="text-3xl font-bold" x-text="stats.monthlySavings.toLocaleString()"></p>
                </div>
                <i class="fas fa-piggy-bank text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">تحسين الأداء</p>
                    <p class="text-3xl font-bold" x-text="'+' + stats.performanceImprovement + '%'"></p>
                </div>
                <i class="fas fa-chart-line text-5xl text-purple-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm mb-1">الحملات المحسّنة</p>
                    <p class="text-3xl font-bold" x-text="stats.optimizedCampaigns"></p>
                </div>
                <i class="fas fa-bullseye text-5xl text-orange-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Optimization Strategies -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
        <!-- Budget Optimization -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fas fa-coins text-green-600 text-2xl"></i>
                    </div>
                    <div class="mr-3">
                        <h3 class="font-bold text-gray-900">تحسين الميزانية</h3>
                        <p class="text-sm text-gray-600">توزيع ذكي للميزانيات</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="strategies.budgetOptimization" @change="toggleStrategy('budget')" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                </label>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">التوفير المتوقع</span>
                    <span class="font-bold text-green-600">15-25%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">الحملات المؤهلة</span>
                    <span class="font-bold text-gray-900" x-text="optimization.budgetEligible || 0"></span>
                </div>
            </div>
        </div>

        <!-- Bid Optimization -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-gavel text-blue-600 text-2xl"></i>
                    </div>
                    <div class="mr-3">
                        <h3 class="font-bold text-gray-900">تحسين العروض</h3>
                        <p class="text-sm text-gray-600">عروض أسعار ديناميكية</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="strategies.bidOptimization" @change="toggleStrategy('bid')" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">تحسين CPA</span>
                    <span class="font-bold text-blue-600">20-30%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">الحملات المؤهلة</span>
                    <span class="font-bold text-gray-900" x-text="optimization.bidEligible || 0"></span>
                </div>
            </div>
        </div>

        <!-- Schedule Optimization -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i class="fas fa-clock text-purple-600 text-2xl"></i>
                    </div>
                    <div class="mr-3">
                        <h3 class="font-bold text-gray-900">تحسين المواعيد</h3>
                        <p class="text-sm text-gray-600">جدولة ذكية للإعلانات</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="strategies.scheduleOptimization" @change="toggleStrategy('schedule')" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">تحسين التفاعل</span>
                    <span class="font-bold text-purple-600">18-28%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">الحملات المؤهلة</span>
                    <span class="font-bold text-gray-900" x-text="optimization.scheduleEligible || 0"></span>
                </div>
            </div>
        </div>

        <!-- Audience Optimization -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-indigo-100 p-3 rounded-lg">
                        <i class="fas fa-users text-indigo-600 text-2xl"></i>
                    </div>
                    <div class="mr-3">
                        <h3 class="font-bold text-gray-900">تحسين الجمهور</h3>
                        <p class="text-sm text-gray-600">استهداف محسّن</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="strategies.audienceOptimization" @change="toggleStrategy('audience')" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">تحسين CVR</span>
                    <span class="font-bold text-indigo-600">25-35%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">الحملات المؤهلة</span>
                    <span class="font-bold text-gray-900" x-text="optimization.audienceEligible || 0"></span>
                </div>
            </div>
        </div>

        <!-- Creative Optimization -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-pink-100 p-3 rounded-lg">
                        <i class="fas fa-palette text-pink-600 text-2xl"></i>
                    </div>
                    <div class="mr-3">
                        <h3 class="font-bold text-gray-900">تحسين الإبداع</h3>
                        <p class="text-sm text-gray-600">دوران تلقائي للإعلانات</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="strategies.creativeOptimization" @change="toggleStrategy('creative')" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                </label>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">تحسين CTR</span>
                    <span class="font-bold text-pink-600">22-32%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">الحملات المؤهلة</span>
                    <span class="font-bold text-gray-900" x-text="optimization.creativeEligible || 0"></span>
                </div>
            </div>
        </div>

        <!-- Placement Optimization -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-lg">
                        <i class="fas fa-map-marker-alt text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="mr-3">
                        <h3 class="font-bold text-gray-900">تحسين المواضع</h3>
                        <p class="text-sm text-gray-600">مواضع عالية الأداء</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="strategies.placementOptimization" @change="toggleStrategy('placement')" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                </label>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">تحسين ROAS</span>
                    <span class="font-bold text-yellow-600">15-20%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">الحملات المؤهلة</span>
                    <span class="font-bold text-gray-900" x-text="optimization.placementEligible || 0"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Optimizations -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-history text-indigo-600 ml-2"></i>
            التحسينات الأخيرة
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحملة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">نوع التحسين</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التغيير</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التأثير</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-if="recentOptimizations.length === 0">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-magic text-3xl text-gray-300 mb-2"></i>
                                <p>لا توجد تحسينات حالياً</p>
                                <p class="text-sm">قم بتفعيل استراتيجيات التحسين أعلاه للبدء</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="opt in recentOptimizations" :key="opt.optimization_id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="opt.campaign_name"></td>
                            <td class="px-4 py-3 text-sm text-gray-600" x-text="opt.optimization_type"></td>
                            <td class="px-4 py-3 text-sm text-gray-600" x-text="opt.change_description"></td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-bold"
                                      :class="opt.impact >= 0 ? 'text-green-600' : 'text-red-600'"
                                      x-text="(opt.impact >= 0 ? '+' : '') + opt.impact + '%'"></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600" x-text="formatDate(opt.applied_at)"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-800': opt.status === 'applied',
                                          'bg-yellow-100 text-yellow-800': opt.status === 'testing',
                                          'bg-gray-100 text-gray-800': opt.status === 'reverted'
                                      }"
                                      x-text="opt.status"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function optimizationManager() {
    return {
        stats: {
            activeOptimizations: 0,
            monthlySavings: 0,
            performanceImprovement: 0,
            optimizedCampaigns: 0
        },
        strategies: {
            budgetOptimization: false,
            bidOptimization: false,
            scheduleOptimization: false,
            audienceOptimization: false,
            creativeOptimization: false,
            placementOptimization: false
        },
        optimization: {
            budgetEligible: 0,
            bidEligible: 0,
            scheduleEligible: 0,
            audienceEligible: 0,
            creativeEligible: 0,
            placementEligible: 0
        },
        recentOptimizations: @json($recentOptimizations ?? []),

        async init() {
            await this.loadStats();
            await this.loadStrategies();
        },

        async loadStats() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/optimization/stats`);
                if (response.ok) {
                    const data = await response.json();
                    this.stats = data.stats || this.stats;
                    this.optimization = data.eligible || this.optimization;
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async loadStrategies() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/optimization/strategies`);
                if (response.ok) {
                    const data = await response.json();
                    this.strategies = data.strategies || this.strategies;
                }
            } catch (error) {
                console.error('Failed to load strategies:', error);
            }
        },

        async toggleStrategy(type) {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/optimization/strategies/${type}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        enabled: this.strategies[`${type}Optimization`]
                    })
                });

                if (response.ok) {
                    await this.loadStats();
                }
            } catch (error) {
                console.error('Failed to toggle strategy:', error);
            }
        },

        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
@endpush
