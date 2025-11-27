@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', 'اختبارات A/B')
@section('page-subtitle', 'تجارب ومقارنات الحملات والإعلانات')

@section('content')
<div x-data="experimentsManager()" x-init="init()">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">إجمالي التجارب</p>
                    <p class="text-3xl font-bold" x-text="stats.total"></p>
                </div>
                <i class="fas fa-flask text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">قيد التشغيل</p>
                    <p class="text-3xl font-bold" x-text="stats.running"></p>
                </div>
                <i class="fas fa-play-circle text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">مكتملة</p>
                    <p class="text-3xl font-bold" x-text="stats.completed"></p>
                </div>
                <i class="fas fa-check-circle text-5xl text-purple-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm mb-1">معدل النجاح</p>
                    <p class="text-3xl font-bold" x-text="stats.successRate + '%'"></p>
                </div>
                <i class="fas fa-trophy text-5xl text-orange-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-3">
            <select x-model="statusFilter" @change="filterExperiments"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">جميع الحالات</option>
                <option value="draft">مسودة</option>
                <option value="running">قيد التشغيل</option>
                <option value="completed">مكتمل</option>
                <option value="paused">متوقف</option>
            </select>

            <select x-model="typeFilter" @change="filterExperiments"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">جميع الأنواع</option>
                <option value="creative">إبداع</option>
                <option value="audience">جمهور</option>
                <option value="bidding">عرض سعر</option>
                <option value="placement">موضع</option>
            </select>
        </div>

        <button @click="showCreateModal = true"
                class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg transition">
            <i class="fas fa-plus ml-2"></i>
            تجربة جديدة
        </button>
    </div>

    <!-- Experiments Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="experiment in filteredExperiments" :key="experiment.experiment_id">
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden">
                <!-- Header -->
                <div class="p-6 border-b">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1" x-text="experiment.experiment_name"></h3>
                            <p class="text-sm text-gray-600" x-text="getTypeLabel(experiment.experiment_type)"></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                              :class="{
                                  'bg-gray-100 text-gray-800': experiment.status === 'draft',
                                  'bg-green-100 text-green-800': experiment.status === 'running',
                                  'bg-purple-100 text-purple-800': experiment.status === 'completed',
                                  'bg-yellow-100 text-yellow-800': experiment.status === 'paused'
                              }">
                            <span x-text="getStatusLabel(experiment.status)"></span>
                        </span>
                    </div>

                    <!-- Campaign Info -->
                    <template x-if="experiment.campaign_name">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="fas fa-bullhorn"></i>
                            <span x-text="experiment.campaign_name"></span>
                        </div>
                    </template>
                </div>

                <!-- Variants -->
                <div class="p-6">
                    <h4 class="text-sm font-bold text-gray-900 mb-3">المتغيرات</h4>

                    <template x-if="experiment.variants && experiment.variants.length > 0">
                        <div class="space-y-2">
                            <template x-for="(variant, index) in experiment.variants" :key="index">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold"
                                                  x-text="String.fromCharCode(65 + index)"></span>
                                            <span class="font-medium text-gray-900" x-text="variant.name"></span>
                                        </div>
                                        <template x-if="variant.is_winner">
                                            <i class="fas fa-crown text-yellow-500"></i>
                                        </template>
                                    </div>

                                    <!-- Metrics -->
                                    <template x-if="experiment.status === 'running' || experiment.status === 'completed'">
                                        <div class="grid grid-cols-3 gap-2 text-xs">
                                            <div>
                                                <p class="text-gray-600">التحويلات</p>
                                                <p class="font-bold text-gray-900" x-text="variant.conversions || 0"></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-600">CVR</p>
                                                <p class="font-bold text-gray-900" x-text="(variant.cvr || 0).toFixed(2) + '%'"></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-600">التكلفة</p>
                                                <p class="font-bold text-gray-900" x-text="(variant.cost || 0).toLocaleString()"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Statistical Significance -->
                    <template x-if="experiment.status === 'running' || experiment.status === 'completed'">
                        <div class="mt-4 bg-gradient-to-r from-indigo-50 to-purple-50 p-3 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-700">الدلالة الإحصائية</span>
                                <template x-if="experiment.confidence_level >= 95">
                                    <span class="text-xs font-bold text-green-600">
                                        <i class="fas fa-check-circle ml-1"></i>
                                        موثوق
                                    </span>
                                </template>
                                <template x-if="experiment.confidence_level < 95">
                                    <span class="text-xs font-bold text-orange-600">
                                        <i class="fas fa-clock ml-1"></i>
                                        يحتاج بيانات
                                    </span>
                                </template>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 h-2 rounded-full transition-all"
                                     :style="`width: ${experiment.confidence_level || 0}%`"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1 text-center"
                               x-text="`${(experiment.confidence_level || 0).toFixed(1)}% مستوى الثقة`"></p>
                        </div>
                    </template>

                    <!-- Duration -->
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-gray-50 p-2 rounded">
                            <p class="text-gray-600 text-xs">تاريخ البدء</p>
                            <p class="font-medium text-gray-900" x-text="formatDate(experiment.start_date)"></p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded">
                            <p class="text-gray-600 text-xs">تاريخ الانتهاء</p>
                            <p class="font-medium text-gray-900" x-text="formatDate(experiment.end_date)"></p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 mt-4 pt-4 border-t">
                        <a :href="`/orgs/{{ $currentOrg }}/experiments/${experiment.experiment_id}`"
                           class="flex-1 bg-indigo-50 text-indigo-600 text-center py-2 rounded-lg font-medium hover:bg-indigo-100 transition text-sm">
                            <i class="fas fa-chart-bar ml-2"></i>
                            النتائج
                        </a>
                        <template x-if="experiment.status === 'running'">
                            <button @click="pauseExperiment(experiment.experiment_id)"
                                    class="bg-yellow-50 text-yellow-600 px-4 py-2 rounded-lg hover:bg-yellow-100 transition text-sm">
                                <i class="fas fa-pause"></i>
                            </button>
                        </template>
                        <template x-if="experiment.status === 'paused'">
                            <button @click="resumeExperiment(experiment.experiment_id)"
                                    class="bg-green-50 text-green-600 px-4 py-2 rounded-lg hover:bg-green-100 transition text-sm">
                                <i class="fas fa-play"></i>
                            </button>
                        </template>
                        <button @click="deleteExperiment(experiment.experiment_id)"
                                class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredExperiments.length === 0">
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-flask text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد تجارب</h3>
            <p class="text-gray-600 mb-6">ابدأ بإنشاء تجربة A/B لتحسين أداء حملاتك</p>
            <button @click="showCreateModal = true"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg transition">
                <i class="fas fa-plus ml-2"></i>
                إنشاء تجربة جديدة
            </button>
        </div>
    </template>

    <!-- Create Modal -->
    <div x-show="showCreateModal" @click.away="showCreateModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">إنشاء تجربة A/B جديدة</h3>
            <form @submit.prevent="createExperiment">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم التجربة</label>
                        <input type="text" x-model="newExperiment.name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع التجربة</label>
                        <select x-model="newExperiment.type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">اختر النوع</option>
                            <option value="creative">اختبار الإبداع</option>
                            <option value="audience">اختبار الجمهور</option>
                            <option value="bidding">اختبار عرض السعر</option>
                            <option value="placement">اختبار الموضع</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الوصف</label>
                        <textarea x-model="newExperiment.description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ البدء</label>
                            <input type="date" x-model="newExperiment.startDate" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ الانتهاء</label>
                            <input type="date" x-model="newExperiment.endDate" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                                class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition">
                            إنشاء التجربة
                        </button>
                        <button type="button" @click="showCreateModal = false"
                                class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                            إلغاء
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function experimentsManager() {
    return {
        experiments: @json($experiments ?? []),
        statusFilter: 'all',
        typeFilter: 'all',
        showCreateModal: false,
        stats: {
            total: 0,
            running: 0,
            completed: 0,
            successRate: 0
        },
        newExperiment: {
            name: '',
            type: '',
            description: '',
            startDate: '',
            endDate: ''
        },

        init() {
            this.calculateStats();
        },

        get filteredExperiments() {
            return this.experiments.filter(exp => {
                const statusMatch = this.statusFilter === 'all' || exp.status === this.statusFilter;
                const typeMatch = this.typeFilter === 'all' || exp.experiment_type === this.typeFilter;
                return statusMatch && typeMatch;
            });
        },

        calculateStats() {
            this.stats.total = this.experiments.length;
            this.stats.running = this.experiments.filter(e => e.status === 'running').length;
            this.stats.completed = this.experiments.filter(e => e.status === 'completed').length;

            const completedWithWinner = this.experiments.filter(e =>
                e.status === 'completed' && e.variants?.some(v => v.is_winner)
            ).length;
            this.stats.successRate = this.stats.completed > 0
                ? Math.round((completedWithWinner / this.stats.completed) * 100)
                : 0;
        },

        filterExperiments() {
            // Handled by computed property
        },

        getStatusLabel(status) {
            const labels = {
                'draft': 'مسودة',
                'running': 'قيد التشغيل',
                'completed': 'مكتمل',
                'paused': 'متوقف'
            };
            return labels[status] || status;
        },

        getTypeLabel(type) {
            const labels = {
                'creative': 'اختبار الإبداع',
                'audience': 'اختبار الجمهور',
                'bidding': 'اختبار عرض السعر',
                'placement': 'اختبار الموضع'
            };
            return labels[type] || type;
        },

        formatDate(date) {
            if (!date) return 'غير محدد';
            return new Date(date).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        async createExperiment() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/experiments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newExperiment)
                });

                if (response.ok) {
                    const data = await response.json();
                    this.experiments.unshift(data.experiment);
                    this.showCreateModal = false;
                    this.newExperiment = { name: '', type: '', description: '', startDate: '', endDate: '' };
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to create experiment:', error);
                alert('فشل إنشاء التجربة');
            }
        },

        async pauseExperiment(experimentId) {
            if (!confirm('هل تريد إيقاف هذه التجربة مؤقتًا؟')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/experiments/${experimentId}/pause`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    const exp = this.experiments.find(e => e.experiment_id === experimentId);
                    if (exp) exp.status = 'paused';
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to pause experiment:', error);
            }
        },

        async resumeExperiment(experimentId) {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/experiments/${experimentId}/resume`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    const exp = this.experiments.find(e => e.experiment_id === experimentId);
                    if (exp) exp.status = 'running';
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to resume experiment:', error);
            }
        },

        async deleteExperiment(experimentId) {
            if (!confirm('هل أنت متأكد من حذف هذه التجربة؟')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/experiments/${experimentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.experiments = this.experiments.filter(e => e.experiment_id !== experimentId);
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to delete experiment:', error);
                alert('فشل حذف التجربة');
            }
        }
    };
}
</script>
@endpush
