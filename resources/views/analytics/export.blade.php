@extends('layouts.admin')

@section('title', 'تصدير البيانات')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="exportPage()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">تصدير البيانات</h1>
        <p class="mt-2 text-gray-600">تصدير بياناتك التسويقية بتنسيقات متعددة</p>
    </div>

    <!-- Export Options -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <!-- Quick Export -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">تصدير سريع</h3>
            <p class="text-sm text-gray-600 mb-6">تصدير البيانات المعدّة مسبقاً بنقرة واحدة</p>

            <div class="space-y-3">
                <button @click="quickExport('campaigns')" class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center ml-3">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">جميع الحملات</p>
                            <p class="text-xs text-gray-500">بيانات شاملة للحملات</p>
                        </div>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <button @click="quickExport('performance')" class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center ml-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0h2a2 2 0 012 2v0a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">مقاييس الأداء</p>
                            <p class="text-xs text-gray-500">جميع المقاييس والإحصائيات</p>
                        </div>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <button @click="quickExport('assets')" class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center ml-3">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">الأصول الإبداعية</p>
                            <p class="text-xs text-gray-500">قائمة الأصول والميتاداتا</p>
                        </div>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <button @click="quickExport('analytics')" class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center ml-3">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">التحليلات المتقدمة</p>
                            <p class="text-xs text-gray-500">رؤى واتجاهات مفصلة</p>
                        </div>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Custom Export -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">تصدير مخصص</h3>
            <p class="text-sm text-gray-600 mb-6">قم بتخصيص البيانات والحقول التي تريد تصديرها</p>

            <form @submit.prevent="customExport()">
                <!-- Data Type -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">نوع البيانات</label>
                    <select x-model="exportConfig.dataType" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">اختر نوع البيانات</option>
                        <option value="campaigns">الحملات</option>
                        <option value="performance">الأداء</option>
                        <option value="assets">الأصول</option>
                        <option value="channels">القنوات</option>
                        <option value="analytics">التحليلات</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">الفترة الزمنية</label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="date" x-model="exportConfig.dateFrom" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="من">
                        <input type="date" x-model="exportConfig.dateTo" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="إلى">
                    </div>
                </div>

                <!-- Format -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">التنسيق</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="relative flex items-center justify-center px-4 py-3 border-2 rounded-lg cursor-pointer transition-all" :class="exportConfig.format === 'excel' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" x-model="exportConfig.format" value="excel" class="sr-only">
                            <div class="text-center">
                                <svg class="mx-auto h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="mt-1 text-xs font-medium">Excel</span>
                            </div>
                        </label>

                        <label class="relative flex items-center justify-center px-4 py-3 border-2 rounded-lg cursor-pointer transition-all" :class="exportConfig.format === 'pdf' ? 'border-red-500 bg-red-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" x-model="exportConfig.format" value="pdf" class="sr-only">
                            <div class="text-center">
                                <svg class="mx-auto h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span class="mt-1 text-xs font-medium">PDF</span>
                            </div>
                        </label>

                        <label class="relative flex items-center justify-center px-4 py-3 border-2 rounded-lg cursor-pointer transition-all" :class="exportConfig.format === 'csv' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" x-model="exportConfig.format" value="csv" class="sr-only">
                            <div class="text-center">
                                <svg class="mx-auto h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="mt-1 text-xs font-medium">CSV</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Include Options -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">تضمين</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="exportConfig.includeMetrics" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="mr-2 text-sm text-gray-700">المقاييس والإحصائيات</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="exportConfig.includeCharts" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="mr-2 text-sm text-gray-700">الرسوم البيانية (PDF فقط)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="exportConfig.includeComments" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="mr-2 text-sm text-gray-700">التعليقات والملاحظات</span>
                        </label>
                    </div>
                </div>

                <!-- Export Button -->
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span x-show="!exporting">تصدير البيانات</span>
                    <span x-show="exporting">جاري التصدير...</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Export History -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">سجل التصدير</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الملف</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النوع</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التنسيق</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحجم</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">إجراءات</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="export_item in exportHistory" :key="export_item.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="export_item.filename"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="export_item.type_label"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': export_item.format === 'excel',
                                          'bg-red-100 text-red-800': export_item.format === 'pdf',
                                          'bg-blue-100 text-blue-800': export_item.format === 'csv'
                                      }"
                                      x-text="export_item.format.toUpperCase()">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="export_item.created_at"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="export_item.size"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                <a :href="export_item.download_url" class="text-indigo-600 hover:text-indigo-900 ml-3">تحميل</a>
                                <button @click="deleteExport(export_item.id)" class="text-red-600 hover:text-red-900">حذف</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="exportHistory.length === 0">
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            لا توجد عمليات تصدير سابقة
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportPage() {
    return {
        exporting: false,
        exportConfig: {
            dataType: '',
            dateFrom: '',
            dateTo: '',
            format: 'excel',
            includeMetrics: true,
            includeCharts: false,
            includeComments: false
        },
        exportHistory: [],

        init() {
            this.loadExportHistory();
        },

        async quickExport(type) {
            const format = 'excel';
            window.location.href = `/api/analytics/export/quick?type=${type}&format=${format}`;
        },

        async customExport() {
            if (!this.exportConfig.dataType) {
                alert('الرجاء اختيار نوع البيانات');
                return;
            }

            this.exporting = true;
            try {
                const params = new URLSearchParams(this.exportConfig);
                window.location.href = `/api/analytics/export/custom?${params}`;

                // Reload history after a delay
                setTimeout(() => this.loadExportHistory(), 2000);
            } catch (error) {
                console.error('Error exporting data:', error);
            } finally {
                this.exporting = false;
            }
        },

        async loadExportHistory() {
            try {
                const response = await fetch('/api/analytics/export/history');
                const data = await response.json();
                this.exportHistory = data.exports || [];
            } catch (error) {
                console.error('Error loading export history:', error);
            }
        },

        async deleteExport(exportId) {
            if (!confirm('هل أنت متأكد من حذف هذا التصدير؟')) {
                return;
            }

            try {
                const response = await fetch(`/api/analytics/export/${exportId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.loadExportHistory();
                }
            } catch (error) {
                console.error('Error deleting export:', error);
            }
        }
    }
}
</script>
@endpush
@endsection
