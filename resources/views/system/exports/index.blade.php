@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', 'التصدير والتقارير')
@section('page-subtitle', 'تصدير البيانات والتقارير بصيغ متعددة')

@section('content')
<div x-data="exportsManager()" x-init="init()">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-sm mb-1">إجمالي التصديرات</p>
                    <p class="text-3xl font-bold" x-text="stats.totalExports"></p>
                </div>
                <i class="fas fa-download text-5xl text-indigo-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">مكتملة</p>
                    <p class="text-3xl font-bold" x-text="stats.completed"></p>
                </div>
                <i class="fas fa-check-circle text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm mb-1">قيد المعالجة</p>
                    <p class="text-3xl font-bold" x-text="stats.processing"></p>
                </div>
                <i class="fas fa-spinner text-5xl text-yellow-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">هذا الشهر</p>
                    <p class="text-3xl font-bold" x-text="stats.thisMonth"></p>
                </div>
                <i class="fas fa-calendar text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Quick Export Templates -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-file-export text-indigo-600 ml-2"></i>
            قوالب التصدير السريع
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Campaign Performance -->
            <button @click="createExport('campaign-performance')"
                    class="bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 border border-purple-200 rounded-xl p-4 text-right transition group">
                <div class="flex items-center justify-between mb-2">
                    <div class="bg-purple-600 p-2 rounded-lg group-hover:scale-110 transition">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <i class="fas fa-arrow-left text-purple-600 opacity-0 group-hover:opacity-100 transition"></i>
                </div>
                <h4 class="font-bold text-gray-900 mb-1">أداء الحملات</h4>
                <p class="text-sm text-gray-600">Excel, PDF, CSV</p>
            </button>

            <!-- Financial Report -->
            <button @click="createExport('financial')"
                    class="bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border border-green-200 rounded-xl p-4 text-right transition group">
                <div class="flex items-center justify-between mb-2">
                    <div class="bg-green-600 p-2 rounded-lg group-hover:scale-110 transition">
                        <i class="fas fa-dollar-sign text-white"></i>
                    </div>
                    <i class="fas fa-arrow-left text-green-600 opacity-0 group-hover:opacity-100 transition"></i>
                </div>
                <h4 class="font-bold text-gray-900 mb-1">التقرير المالي</h4>
                <p class="text-sm text-gray-600">Excel, PDF</p>
            </button>

            <!-- Audience Data -->
            <button @click="createExport('audience')"
                    class="bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border border-blue-200 rounded-xl p-4 text-right transition group">
                <div class="flex items-center justify-between mb-2">
                    <div class="bg-blue-600 p-2 rounded-lg group-hover:scale-110 transition">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <i class="fas fa-arrow-left text-blue-600 opacity-0 group-hover:opacity-100 transition"></i>
                </div>
                <h4 class="font-bold text-gray-900 mb-1">بيانات الجمهور</h4>
                <p class="text-sm text-gray-600">CSV, JSON</p>
            </button>

            <!-- Custom Export -->
            <button @click="showCustomModal = true"
                    class="bg-gradient-to-br from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 border border-indigo-200 rounded-xl p-4 text-right transition group">
                <div class="flex items-center justify-between mb-2">
                    <div class="bg-indigo-600 p-2 rounded-lg group-hover:scale-110 transition">
                        <i class="fas fa-cog text-white"></i>
                    </div>
                    <i class="fas fa-arrow-left text-indigo-600 opacity-0 group-hover:opacity-100 transition"></i>
                </div>
                <h4 class="font-bold text-gray-900 mb-1">تصدير مخصص</h4>
                <p class="text-sm text-gray-600">جميع الصيغ</p>
            </button>
        </div>
    </div>

    <!-- Export History -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">
                <i class="fas fa-history text-indigo-600 ml-2"></i>
                سجل التصدير
            </h3>
            <select x-model="statusFilter" @change="filterExports"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">جميع الحالات</option>
                <option value="completed">مكتمل</option>
                <option value="processing">قيد المعالجة</option>
                <option value="failed">فشل</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">اسم التصدير</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الصيغة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحجم</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-if="filteredExports.length === 0">
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                                <p>لا توجد تصديرات</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="export_item in filteredExports" :key="export_item.export_id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900" x-text="export_item.export_name"></p>
                                    <p class="text-xs text-gray-500" x-text="export_item.description"></p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600" x-text="getTypeLabel(export_item.export_type)"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium uppercase"
                                      x-text="export_item.format"></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600" x-text="formatFileSize(export_item.file_size)"></td>
                            <td class="px-4 py-3 text-sm text-gray-600" x-text="formatDate(export_item.created_at)"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-800': export_item.status === 'completed',
                                          'bg-yellow-100 text-yellow-800': export_item.status === 'processing',
                                          'bg-red-100 text-red-800': export_item.status === 'failed'
                                      }">
                                    <span x-text="getStatusLabel(export_item.status)"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <template x-if="export_item.status === 'completed'">
                                        <a :href="export_item.download_url"
                                           class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-lg hover:bg-indigo-100 transition text-sm">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </template>
                                    <button @click="deleteExport(export_item.export_id)"
                                            class="bg-red-50 text-red-600 px-3 py-1 rounded-lg hover:bg-red-100 transition text-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Custom Export Modal -->
    <div x-show="showCustomModal" @click.away="showCustomModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">تصدير مخصص</h3>
            <form @submit.prevent="createCustomExport">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم التصدير</label>
                        <input type="text" x-model="customExport.name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع البيانات</label>
                        <select x-model="customExport.type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">اختر النوع</option>
                            <option value="campaign-performance">أداء الحملات</option>
                            <option value="financial">تقرير مالي</option>
                            <option value="audience">بيانات الجمهور</option>
                            <option value="analytics">تحليلات</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الصيغة</label>
                        <select x-model="customExport.format" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                            <option value="json">JSON (.json)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">من تاريخ</label>
                            <input type="date" x-model="customExport.startDate" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">إلى تاريخ</label>
                            <input type="date" x-model="customExport.endDate" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                                class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition">
                            <i class="fas fa-download ml-2"></i>
                            بدء التصدير
                        </button>
                        <button type="button" @click="showCustomModal = false"
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
function exportsManager() {
    return {
        exports: @json($exports ?? []),
        statusFilter: 'all',
        showCustomModal: false,
        stats: {
            totalExports: 0,
            completed: 0,
            processing: 0,
            thisMonth: 0
        },
        customExport: {
            name: '',
            type: '',
            format: 'excel',
            startDate: '',
            endDate: ''
        },

        init() {
            this.calculateStats();
        },

        get filteredExports() {
            if (this.statusFilter === 'all') {
                return this.exports;
            }
            return this.exports.filter(exp => exp.status === this.statusFilter);
        },

        calculateStats() {
            this.stats.totalExports = this.exports.length;
            this.stats.completed = this.exports.filter(e => e.status === 'completed').length;
            this.stats.processing = this.exports.filter(e => e.status === 'processing').length;

            const thisMonth = new Date().getMonth();
            this.stats.thisMonth = this.exports.filter(e => {
                const exportMonth = new Date(e.created_at).getMonth();
                return exportMonth === thisMonth;
            }).length;
        },

        filterExports() {
            // Handled by computed property
        },

        getTypeLabel(type) {
            const labels = {
                'campaign-performance': 'أداء الحملات',
                'financial': 'تقرير مالي',
                'audience': 'بيانات الجمهور',
                'analytics': 'تحليلات'
            };
            return labels[type] || type;
        },

        getStatusLabel(status) {
            const labels = {
                'completed': 'مكتمل',
                'processing': 'قيد المعالجة',
                'failed': 'فشل'
            };
            return labels[status] || status;
        },

        formatFileSize(bytes) {
            if (!bytes) return '-';
            const kb = bytes / 1024;
            if (kb < 1024) return kb.toFixed(1) + ' KB';
            return (kb / 1024).toFixed(1) + ' MB';
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
        },

        async createExport(template) {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/exports`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ template })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.exports.unshift(data.export);
                    this.calculateStats();
                    alert('تم بدء التصدير');
                }
            } catch (error) {
                console.error('Failed to create export:', error);
                alert('فشل بدء التصدير');
            }
        },

        async createCustomExport() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/exports/custom`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.customExport)
                });

                if (response.ok) {
                    const data = await response.json();
                    this.exports.unshift(data.export);
                    this.showCustomModal = false;
                    this.customExport = { name: '', type: '', format: 'excel', startDate: '', endDate: '' };
                    this.calculateStats();
                    alert('تم بدء التصدير المخصص');
                }
            } catch (error) {
                console.error('Failed to create custom export:', error);
                alert('فشل بدء التصدير');
            }
        },

        async deleteExport(exportId) {
            if (!confirm('هل تريد حذف هذا التصدير؟')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/exports/${exportId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.exports = this.exports.filter(e => e.export_id !== exportId);
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to delete export:', error);
            }
        }
    };
}
</script>
@endpush
