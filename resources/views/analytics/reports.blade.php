@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'التقارير')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="reportsPage()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">التقارير</h1>
        <p class="mt-2 text-gray-600">إنشاء وإدارة تقارير الأداء التفصيلية</p>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <!-- Report Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع التقرير</label>
                <select x-model="filters.type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">الكل</option>
                    <option value="campaign">حملات</option>
                    <option value="organization">مؤسسات</option>
                    <option value="channel">قنوات</option>
                    <option value="asset">أصول إبداعية</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">من تاريخ</label>
                <input type="date" x-model="filters.dateFrom" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">إلى تاريخ</label>
                <input type="date" x-model="filters.dateTo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <!-- Actions -->
            <div class="flex items-end gap-2">
                <button @click="applyFilters()" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    بحث
                </button>
                <button @click="showCreateModal = true" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    تقرير جديد
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Reports Grid -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <template x-for="report in reports" :key="report.id">
            <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                <!-- Report Header -->
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900" x-text="report.title"></h3>
                            <p class="mt-1 text-sm text-gray-500" x-text="report.description"></p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="{
                                  'bg-blue-100 text-blue-800': report.type === 'campaign',
                                  'bg-green-100 text-green-800': report.type === 'organization',
                                  'bg-purple-100 text-purple-800': report.type === 'channel',
                                  'bg-yellow-100 text-yellow-800': report.type === 'asset'
                              }"
                              x-text="report.type_label">
                        </span>
                    </div>

                    <!-- Report Stats -->
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">الفترة</p>
                            <p class="mt-1 text-sm font-medium text-gray-900" x-text="report.period"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">تم الإنشاء</p>
                            <p class="mt-1 text-sm font-medium text-gray-900" x-text="report.created_at"></p>
                        </div>
                    </div>
                </div>

                <!-- Report Actions -->
                <div class="bg-gray-50 px-6 py-3 flex items-center justify-between">
                    <div class="flex gap-2">
                        <button @click="viewReport(report.id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            عرض
                        </button>
                        <button @click="downloadReport(report.id, 'pdf')" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                            PDF
                        </button>
                        <button @click="downloadReport(report.id, 'excel')" class="text-green-600 hover:text-green-900 text-sm font-medium">
                            Excel
                        </button>
                    </div>
                    <button @click="deleteReport(report.id)" class="text-red-600 hover:text-red-900">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="reports.length === 0" class="col-span-full">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">لا توجد تقارير</h3>
                <p class="mt-1 text-sm text-gray-500">ابدأ بإنشاء تقرير جديد</p>
                <div class="mt-6">
                    <button @click="showCreateModal = true" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        إنشاء تقرير
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Report Modal -->
    <div x-show="showCreateModal" class="fixed z-10 inset-0 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="createReport()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">إنشاء تقرير جديد</h3>

                        <!-- Report Title -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">عنوان التقرير</label>
                            <input type="text" x-model="newReport.title" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Report Type -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">نوع التقرير</label>
                            <select x-model="newReport.type" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">اختر النوع</option>
                                <option value="campaign">تقرير حملة</option>
                                <option value="organization">تقرير مؤسسة</option>
                                <option value="channel">تقرير قناة</option>
                                <option value="asset">تقرير الأصول</option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">من تاريخ</label>
                                <input type="date" x-model="newReport.dateFrom" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">إلى تاريخ</label>
                                <input type="date" x-model="newReport.dateTo" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">الوصف</label>
                            <textarea x-model="newReport.description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            إنشاء التقرير
                        </button>
                        <button type="button" @click="showCreateModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:mr-3 sm:w-auto sm:text-sm">
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function reportsPage() {
    return {
        loading: true,
        showCreateModal: false,
        filters: {
            type: '',
            dateFrom: '',
            dateTo: ''
        },
        newReport: {
            title: '',
            type: '',
            dateFrom: '',
            dateTo: '',
            description: ''
        },
        reports: [],

        init() {
            this.loadReports();
        },

        async loadReports() {
            this.loading = true;
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/api/analytics/reports?${params}`);
                const data = await response.json();
                this.reports = data.reports || [];
            } catch (error) {
                console.error('Error loading reports:', error);
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            this.loadReports();
        },

        async createReport() {
            try {
                const response = await fetch('/api/analytics/reports', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newReport)
                });

                if (response.ok) {
                    this.showCreateModal = false;
                    this.loadReports();
                    // Reset form
                    this.newReport = {
                        title: '',
                        type: '',
                        dateFrom: '',
                        dateTo: '',
                        description: ''
                    };
                }
            } catch (error) {
                console.error('Error creating report:', error);
            }
        },

        viewReport(reportId) {
            window.location.href = `/analytics/reports/${reportId}`;
        },

        async downloadReport(reportId, format) {
            window.location.href = `/api/analytics/reports/${reportId}/download?format=${format}`;
        },

        async deleteReport(reportId) {
            if (!confirm('هل أنت متأكد من حذف هذا التقرير؟')) {
                return;
            }

            try {
                const response = await fetch(`/api/analytics/reports/${reportId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.loadReports();
                }
            } catch (error) {
                console.error('Error deleting report:', error);
            }
        }
    }
}
</script>
@endpush
@endsection
