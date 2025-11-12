@extends('layouts.admin')

@section('title', 'الحملات التسويقية')

@section('content')
<div x-data="campaignsManager(@json($campaigns))" x-init="init()">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">الحملات التسويقية</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">إدارة وتتبع جميع الحملات التسويقية</p>
        </div>
        <x-ui.button @click="openModal('create-campaign-modal')" icon="fas fa-plus">
            حملة جديدة
        </x-ui.button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">إجمالي الحملات</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.total"></p>
                </div>
                <i class="fas fa-bullhorn text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">حملات نشطة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.active"></p>
                </div>
                <i class="fas fa-play-circle text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">مجدولة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.scheduled"></p>
                </div>
                <i class="fas fa-clock text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">مكتملة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.completed"></p>
                </div>
                <i class="fas fa-check-circle text-4xl opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <x-ui.card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text"
                   x-model="searchQuery"
                   @input="searchCampaigns()"
                   placeholder="بحث في الحملات..."
                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">

            <select x-model="filterStatus"
                    @change="searchCampaigns()"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">جميع الحالات</option>
                <option value="draft">مسودة</option>
                <option value="scheduled">مجدولة</option>
                <option value="active">نشطة</option>
                <option value="paused">متوقفة مؤقتاً</option>
                <option value="completed">مكتملة</option>
            </select>

            <select x-model="filterOrg"
                    @change="searchCampaigns()"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">جميع المؤسسات</option>
                <!-- Dynamic organizations list -->
            </select>

            <select x-model="sortBy"
                    @change="searchCampaigns()"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="created_at">تاريخ الإنشاء</option>
                <option value="start_date">تاريخ البدء</option>
                <option value="budget">الميزانية</option>
                <option value="performance">الأداء</option>
            </select>
        </div>
    </x-ui.card>

    <!-- Campaigns Table -->
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">الحملة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">المؤسسة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">الحالة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">الميزانية</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">الأداء</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">التاريخ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="campaign in campaigns" :key="campaign.campaign_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                    <div class="mr-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="campaign.name"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="campaign.objective"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-white" x-text="campaign.organization"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': campaign.status === 'active',
                                          'bg-blue-100 text-blue-800': campaign.status === 'scheduled',
                                          'bg-gray-100 text-gray-800': campaign.status === 'draft',
                                          'bg-yellow-100 text-yellow-800': campaign.status === 'paused',
                                          'bg-purple-100 text-purple-800': campaign.status === 'completed'
                                      }"
                                      x-text="campaign.status_label">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white" x-text="campaign.budget + ' ر.س'"></div>
                                <div class="text-xs text-gray-500">
                                    <span x-text="campaign.spent + ' ر.س'"></span> / <span x-text="Math.round((campaign.spent / campaign.budget) * 100) + '%'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500 rounded-full" :style="'width: ' + campaign.performance + '%'"></div>
                                    </div>
                                    <span class="mr-2 text-sm font-semibold text-gray-900 dark:text-white" x-text="campaign.performance + '%'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <div x-text="campaign.start_date"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <button @click="viewCampaign(campaign.campaign_id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button @click="editCampaign(campaign)" class="text-green-600 hover:text-green-900 dark:text-green-400">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="duplicateCampaign(campaign.campaign_id)" class="text-purple-600 hover:text-purple-900 dark:text-purple-400">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button @click="deleteCampaign(campaign.campaign_id)" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div x-show="campaigns.length === 0" class="text-center py-12">
            <i class="fas fa-bullhorn text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">لا توجد حملات</h3>
            <p class="text-gray-500 mb-4">ابدأ بإنشاء حملتك الأولى</p>
            <x-ui.button @click="openModal('create-campaign-modal')" icon="fas fa-plus">
                إنشاء حملة
            </x-ui.button>
        </div>
    </x-ui.card>

</div>

<!-- Create Campaign Modal -->
<x-ui.modal name="create-campaign-modal" title="حملة تسويقية جديدة" max-width="2xl">
    <form x-data="campaignForm()" @submit.prevent="submitCampaign()">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-forms.input
                label="اسم الحملة"
                name="name"
                x-model="formData.name"
                required
                placeholder="حملة الصيف 2025" />

            <x-forms.select
                label="الهدف"
                name="objective"
                x-model="formData.objective"
                required>
                <option value="">اختر الهدف</option>
                <option value="awareness">الوعي بالعلامة التجارية</option>
                <option value="traffic">زيادة الزيارات</option>
                <option value="engagement">التفاعل</option>
                <option value="leads">جمع العملاء المحتملين</option>
                <option value="conversions">التحويلات</option>
            </x-forms.select>

            <x-forms.input
                label="الميزانية (ر.س)"
                name="budget"
                type="number"
                x-model="formData.budget"
                required
                placeholder="10000" />

            <x-forms.select
                label="الحالة"
                name="status"
                x-model="formData.status"
                required>
                <option value="draft">مسودة</option>
                <option value="scheduled">مجدولة</option>
                <option value="active">نشطة</option>
            </x-forms.select>

            <x-forms.input
                label="تاريخ البدء"
                name="start_date"
                type="date"
                x-model="formData.start_date"
                required />

            <x-forms.input
                label="تاريخ الانتهاء"
                name="end_date"
                type="date"
                x-model="formData.end_date" />
        </div>

        <x-forms.textarea
            label="الوصف"
            name="description"
            x-model="formData.description"
            rows="3"
            placeholder="وصف تفصيلي للحملة..." />

        <x-slot name="footer">
            <x-ui.button type="button" variant="secondary" @click="closeModal('create-campaign-modal')">
                إلغاء
            </x-ui.button>
            <x-ui.button type="submit" icon="fas fa-rocket">
                إنشاء الحملة
            </x-ui.button>
        </x-slot>
    </form>
</x-ui.modal>

@endsection

@push('scripts')
<script>
function campaignsManager(serverCampaigns) {
    return {
        allCampaigns: [],
        campaigns: [],
        stats: { total: 0, active: 0, scheduled: 0, completed: 0 },
        searchQuery: '',
        filterStatus: '',
        filterOrg: '',
        sortBy: 'created_at',

        init() {
            // Initialize with server data
            this.processServerData(serverCampaigns || []);
            this.calculateStats();
            this.campaigns = [...this.allCampaigns];
        },

        processServerData(data) {
            // Transform server data to include display-friendly fields
            this.allCampaigns = data.map(campaign => ({
                campaign_id: campaign.campaign_id,
                name: campaign.name,
                objective: campaign.objective || 'غير محدد',
                organization: campaign.org ? campaign.org.name : 'غير محدد',
                org_id: campaign.org ? campaign.org.org_id : null,
                status: campaign.status || 'draft',
                status_label: this.getStatusLabel(campaign.status),
                budget: parseFloat(campaign.budget) || 0,
                spent: parseFloat(campaign.spent) || 0,
                performance: this.calculatePerformance(campaign),
                start_date: this.formatDate(campaign.start_date),
                end_date: this.formatDate(campaign.end_date),
                updated_at: campaign.updated_at
            }));
        },

        calculateStats() {
            this.stats = {
                total: this.allCampaigns.length,
                active: this.allCampaigns.filter(c => c.status === 'active').length,
                scheduled: this.allCampaigns.filter(c => c.status === 'scheduled').length,
                completed: this.allCampaigns.filter(c => c.status === 'completed').length
            };
        },

        getStatusLabel(status) {
            const labels = {
                'draft': 'مسودة',
                'scheduled': 'مجدولة',
                'active': 'نشطة',
                'paused': 'متوقفة مؤقتاً',
                'completed': 'مكتملة'
            };
            return labels[status] || status;
        },

        calculatePerformance(campaign) {
            // TODO: Fetch actual performance metrics from API
            // For now, return a simulated performance score
            if (campaign.status === 'scheduled' || campaign.status === 'draft') {
                return 0;
            }
            // Simulated performance calculation
            return Math.floor(Math.random() * 40) + 60; // Random 60-100
        },

        formatDate(dateString) {
            if (!dateString) return 'غير محدد';
            const date = new Date(dateString);
            return date.toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        },

        searchCampaigns() {
            let filtered = [...this.allCampaigns];

            // Apply search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(campaign =>
                    campaign.name.toLowerCase().includes(query) ||
                    campaign.organization.toLowerCase().includes(query) ||
                    campaign.objective.toLowerCase().includes(query)
                );
            }

            // Apply status filter
            if (this.filterStatus) {
                filtered = filtered.filter(campaign => campaign.status === this.filterStatus);
            }

            // Apply organization filter
            if (this.filterOrg) {
                filtered = filtered.filter(campaign => campaign.org_id === this.filterOrg);
            }

            // Apply sorting
            filtered.sort((a, b) => {
                if (this.sortBy === 'created_at' || this.sortBy === 'start_date') {
                    return new Date(b.updated_at) - new Date(a.updated_at);
                } else if (this.sortBy === 'budget') {
                    return b.budget - a.budget;
                } else if (this.sortBy === 'performance') {
                    return b.performance - a.performance;
                }
                return 0;
            });

            this.campaigns = filtered;
        },

        viewCampaign(id) {
            window.location.href = `/campaigns/${id}`;
        },

        editCampaign(campaign) {
            // TODO: Implement edit functionality with API call
            // This would populate a modal with campaign data for editing
            console.log('Editing campaign:', campaign);
            window.notify('تعديل الحملة: ' + campaign.name, 'info');
        },

        duplicateCampaign(id) {
            // TODO: Implement duplicate functionality with API call
            // POST /api/campaigns/{id}/duplicate
            console.log('Duplicating campaign:', id);
            window.notify('جاري نسخ الحملة...', 'info');
        },

        async deleteCampaign(id) {
            if (!confirm('هل أنت متأكد من حذف هذه الحملة؟ سيتم حذف جميع البيانات المرتبطة بها.')) return;

            try {
                // TODO: Implement actual API call with CSRF token
                // const response = await fetch(`/api/campaigns/${id}`, {
                //     method: 'DELETE',
                //     headers: {
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     }
                // });
                //
                // if (!response.ok) throw new Error('Failed to delete');

                window.notify('جاري حذف الحملة...', 'info');

                // Remove from local array for now
                this.allCampaigns = this.allCampaigns.filter(c => c.campaign_id !== id);
                this.searchCampaigns();
                this.calculateStats();

                window.notify('تم حذف الحملة بنجاح', 'success');
            } catch (error) {
                console.error('Error deleting campaign:', error);
                window.notify('فشل حذف الحملة', 'error');
            }
        }
    };
}

function campaignForm() {
    return {
        formData: {
            name: '',
            objective: '',
            budget: '',
            status: 'draft',
            start_date: '',
            end_date: '',
            description: ''
        },

        async submitCampaign() {
            try {
                // Validate required fields
                if (!this.formData.name || !this.formData.objective || !this.formData.budget) {
                    window.notify('الرجاء ملء جميع الحقول المطلوبة', 'warning');
                    return;
                }

                window.notify('جاري إنشاء الحملة...', 'info');

                // TODO: Implement actual API call with CSRF token
                // const response = await fetch('/api/campaigns', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     },
                //     body: JSON.stringify(this.formData)
                // });
                //
                // if (!response.ok) {
                //     const error = await response.json();
                //     throw new Error(error.message || 'Failed to create campaign');
                // }

                console.log('Submitting campaign:', this.formData);
                window.notify('تم إنشاء الحملة بنجاح', 'success');
                closeModal('create-campaign-modal');

                // Refresh the page to show new campaign
                setTimeout(() => location.reload(), 1000);
            } catch (error) {
                console.error('Error creating campaign:', error);
                window.notify(error.message || 'فشل إنشاء الحملة', 'error');
            }
        }
    };
}
</script>
@endpush
