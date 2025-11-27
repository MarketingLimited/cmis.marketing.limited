@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', 'بريفات الحملات الإبداعية')
@section('page-subtitle', 'إدارة بريفات الحملات والمشاريع التسويقية')

@section('content')
<div x-data="briefsManager()" x-init="init()">
    <!-- Header with Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">إجمالي البريفات</p>
                    <p class="text-3xl font-bold" x-text="stats.total"></p>
                </div>
                <i class="fas fa-file-alt text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">نشط</p>
                    <p class="text-3xl font-bold" x-text="stats.active"></p>
                </div>
                <i class="fas fa-check-circle text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm mb-1">قيد المراجعة</p>
                    <p class="text-3xl font-bold" x-text="stats.review"></p>
                </div>
                <i class="fas fa-eye text-5xl text-yellow-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">مكتمل</p>
                    <p class="text-3xl font-bold" x-text="stats.completed"></p>
                </div>
                <i class="fas fa-trophy text-5xl text-purple-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Header with Filters -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-3">
            <select x-model="statusFilter" @change="filterBriefs"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">جميع الحالات</option>
                <option value="draft">مسودة</option>
                <option value="review">قيد المراجعة</option>
                <option value="approved">معتمد</option>
                <option value="active">نشط</option>
                <option value="completed">مكتمل</option>
            </select>

            <select x-model="typeFilter" @change="filterBriefs"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">جميع الأنواع</option>
                <option value="campaign">حملة تسويقية</option>
                <option value="content">محتوى</option>
                <option value="design">تصميم</option>
                <option value="video">فيديو</option>
                <option value="social">سوشيال ميديا</option>
            </select>
        </div>

        <a href="{{ route('orgs.creative.briefs.create', ['org' => $currentOrg]) }}"
           class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg transition">
            <i class="fas fa-plus ml-2"></i>
            بريف جديد
        </a>
    </div>

    <!-- Briefs Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="brief in filteredBriefs" :key="brief.brief_id">
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden card-hover">
                <!-- Header -->
                <div class="p-6 border-b">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1" x-text="brief.brief_title"></h3>
                            <p class="text-sm text-gray-600" x-text="brief.brief_type"></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                              :class="{
                                  'bg-gray-100 text-gray-800': brief.status === 'draft',
                                  'bg-yellow-100 text-yellow-800': brief.status === 'review',
                                  'bg-green-100 text-green-800': brief.status === 'approved',
                                  'bg-blue-100 text-blue-800': brief.status === 'active',
                                  'bg-purple-100 text-purple-800': brief.status === 'completed'
                              }">
                            <span x-text="getStatusLabel(brief.status)"></span>
                        </span>
                    </div>

                    <!-- Campaign Info -->
                    <template x-if="brief.campaign_name">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="fas fa-bullhorn"></i>
                            <span x-text="brief.campaign_name"></span>
                        </div>
                    </template>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Objectives -->
                    <div class="mb-4">
                        <h4 class="text-sm font-bold text-gray-900 mb-2">الأهداف</h4>
                        <p class="text-sm text-gray-700 line-clamp-3" x-text="brief.objectives"></p>
                    </div>

                    <!-- Key Info -->
                    <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-gray-600 mb-1">الجمهور المستهدف</p>
                            <p class="font-medium text-gray-900" x-text="brief.target_audience || 'غير محدد'"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-gray-600 mb-1">التاريخ المستهدف</p>
                            <p class="font-medium text-gray-900" x-text="formatDate(brief.target_date)"></p>
                        </div>
                    </div>

                    <!-- Budget -->
                    <template x-if="brief.budget">
                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-3 rounded-lg mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">الميزانية</span>
                                <span class="text-lg font-bold text-indigo-600" x-text="`${brief.budget} ر.س`"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t">
                        <a :href="`/orgs/{{ $currentOrg }}/creative/briefs/${brief.brief_id}`"
                           class="flex-1 bg-indigo-50 text-indigo-600 text-center py-2 rounded-lg font-medium hover:bg-indigo-100 transition">
                            <i class="fas fa-eye ml-2"></i>
                            عرض التفاصيل
                        </a>
                        <a :href="`/orgs/{{ $currentOrg }}/creative/briefs/${brief.brief_id}/edit`"
                           class="bg-gray-50 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button @click="deleteBrief(brief.brief_id)"
                                class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredBriefs.length === 0">
        <x-empty-state
            icon="fas fa-file-alt"
            title="لا توجد بريفات"
            description="ابدأ بإنشاء بريف جديد لحملتك التسويقية"
            action-text="إنشاء بريف"
            :action-url="route('orgs.creative.briefs.create', ['org' => $currentOrg])"
        />
    </template>
</div>
@endsection

@push('scripts')
<script>
function briefsManager() {
    return {
        briefs: @json($briefs ?? []),
        statusFilter: 'all',
        typeFilter: 'all',
        stats: {
            total: 0,
            active: 0,
            review: 0,
            completed: 0
        },

        init() {
            this.calculateStats();
        },

        get filteredBriefs() {
            return this.briefs.filter(brief => {
                const statusMatch = this.statusFilter === 'all' || brief.status === this.statusFilter;
                const typeMatch = this.typeFilter === 'all' || brief.brief_type === this.typeFilter;
                return statusMatch && typeMatch;
            });
        },

        calculateStats() {
            this.stats.total = this.briefs.length;
            this.stats.active = this.briefs.filter(b => b.status === 'active').length;
            this.stats.review = this.briefs.filter(b => b.status === 'review').length;
            this.stats.completed = this.briefs.filter(b => b.status === 'completed').length;
        },

        filterBriefs() {
            // Filters are handled by computed property
        },

        getStatusLabel(status) {
            const labels = {
                'draft': 'مسودة',
                'review': 'قيد المراجعة',
                'approved': 'معتمد',
                'active': 'نشط',
                'completed': 'مكتمل'
            };
            return labels[status] || status;
        },

        formatDate(date) {
            if (!date) return 'غير محدد';
            return new Date(date).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        async deleteBrief(briefId) {
            if (!confirm('هل أنت متأكد من حذف هذا البريف؟')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/creative/briefs/${briefId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.briefs = this.briefs.filter(b => b.brief_id !== briefId);
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to delete brief:', error);
                alert('فشل حذف البريف');
            }
        }
    };
}
</script>
@endpush
