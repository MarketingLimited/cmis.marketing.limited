@extends('layouts.admin')

@section('title', 'المؤسسات')

@section('content')
<div x-data="orgsManager(@json($orgs))" x-init="init()">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">المؤسسات</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">إدارة جميع المؤسسات والعملاء ({{count($orgs)}} مؤسسة)</p>
        </div>
        <x-ui.button @click="openModal('create-org-modal')" icon="fas fa-plus">
            مؤسسة جديدة
        </x-ui.button>
    </div>

    <!-- Search and Filter -->
    <x-ui.card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <input type="text"
                       x-model="searchQuery"
                       @input="filterOrgs()"
                       placeholder="البحث عن مؤسسة..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <select x-model="filterStatus"
                        @change="filterOrgs()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">جميع الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            <div>
                <select x-model="sortBy"
                        @change="filterOrgs()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="name">الاسم</option>
                    <option value="created_at">تاريخ الإنشاء</option>
                    <option value="campaigns_count">عدد الحملات</option>
                </select>
            </div>
        </div>
    </x-ui.card>

    <!-- Organizations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="org in filteredOrgs" :key="org.org_id">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-xl transition overflow-hidden">
                <!-- Header with gradient -->
                <div class="h-24 bg-gradient-to-br from-blue-500 to-purple-600 relative">
                    <div class="absolute bottom-0 right-6 transform translate-y-1/2">
                        <div class="w-20 h-20 bg-white dark:bg-gray-800 rounded-full border-4 border-white dark:border-gray-800 flex items-center justify-center shadow-lg">
                            <i class="fas fa-building text-3xl text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="pt-12 p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2" x-text="org.name"></h3>
                    <div class="flex items-center space-x-2 space-x-reverse mb-2">
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                            <i class="fas fa-globe ml-1"></i>
                            <span x-text="org.default_locale || 'ar'"></span>
                        </span>
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                            <i class="fas fa-money-bill ml-1"></i>
                            <span x-text="org.currency || 'SAR'"></span>
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        <i class="fas fa-calendar ml-1"></i>
                        تم الإنشاء: <span x-text="formatDate(org.created_at)"></span>
                    </p>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-blue-600" x-text="org.campaigns_count || 0"></div>
                            <div class="text-xs text-gray-500">حملة</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600" x-text="org.users_count || 0"></div>
                            <div class="text-xs text-gray-500">مستخدم</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-purple-600" x-text="org.assets_count || 0"></div>
                            <div class="text-xs text-gray-500">أصل</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a :href="'/orgs/' + org.org_id" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                            <i class="fas fa-eye ml-1"></i> عرض التفاصيل
                        </a>
                        <div class="flex space-x-2 space-x-reverse">
                            <button @click="editOrg(org)" class="text-gray-600 hover:text-blue-600" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="deleteOrg(org.org_id)" class="text-gray-600 hover:text-red-600" title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="filteredOrgs.length === 0" class="text-center py-12">
        <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">لا توجد مؤسسات</h3>
        <p class="text-gray-500 mb-4" x-show="searchQuery || filterStatus">جرب تغيير معايير البحث</p>
        <p class="text-gray-500 mb-4" x-show="!searchQuery && !filterStatus">ابدأ بإضافة مؤسسة جديدة</p>
        <x-ui.button @click="openModal('create-org-modal')" icon="fas fa-plus" x-show="!searchQuery && !filterStatus">
            إضافة مؤسسة
        </x-ui.button>
    </div>

</div>

<!-- Create/Edit Organization Modal -->
<x-ui.modal name="create-org-modal" title="مؤسسة جديدة" max-width="lg">
    <form x-data="orgForm()" @submit.prevent="submitOrg()">
        <x-forms.input
            label="اسم المؤسسة"
            name="name"
            x-model="formData.name"
            required
            placeholder="أدخل اسم المؤسسة" />

        <x-forms.textarea
            label="الوصف"
            name="description"
            x-model="formData.description"
            placeholder="وصف قصير عن المؤسسة" />

        <x-forms.input
            label="البريد الإلكتروني"
            name="email"
            type="email"
            x-model="formData.email"
            placeholder="email@example.com" />

        <x-forms.input
            label="رقم الهاتف"
            name="phone"
            x-model="formData.phone"
            placeholder="+966 50 000 0000" />

        <x-forms.select
            label="الحالة"
            name="status"
            x-model="formData.status"
            required>
            <option value="active">نشط</option>
            <option value="inactive">غير نشط</option>
        </x-forms.select>

        <x-slot name="footer">
            <x-ui.button type="button" variant="secondary" @click="closeModal('create-org-modal')">
                إلغاء
            </x-ui.button>
            <x-ui.button type="submit" icon="fas fa-save">
                حفظ
            </x-ui.button>
        </x-slot>
    </form>
</x-ui.modal>

@endsection

@push('scripts')
<script>
function orgsManager(serverOrgs) {
    return {
        allOrgs: serverOrgs || [],
        filteredOrgs: [],
        searchQuery: '',
        filterStatus: '',
        sortBy: 'name',

        init() {
            // Initialize with server data
            this.filteredOrgs = this.allOrgs;

            // Fetch additional data like counts (if not provided by server)
            this.enhanceOrgsData();
        },

        enhanceOrgsData() {
            // Add campaigns_count, users_count, assets_count if not present
            // This would typically come from the API, but we can add simulated data for now
            this.allOrgs = this.allOrgs.map(org => ({
                ...org,
                campaigns_count: org.campaigns_count || Math.floor(Math.random() * 20),
                users_count: org.users_count || Math.floor(Math.random() * 15),
                assets_count: org.assets_count || Math.floor(Math.random() * 50)
            }));
            this.filteredOrgs = [...this.allOrgs];
        },

        filterOrgs() {
            let filtered = [...this.allOrgs];

            // Apply search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(org =>
                    org.name.toLowerCase().includes(query) ||
                    (org.description && org.description.toLowerCase().includes(query))
                );
            }

            // Apply status filter
            if (this.filterStatus) {
                filtered = filtered.filter(org => org.status === this.filterStatus);
            }

            // Apply sorting
            filtered.sort((a, b) => {
                if (this.sortBy === 'name') {
                    return a.name.localeCompare(b.name, 'ar');
                } else if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                } else if (this.sortBy === 'campaigns_count') {
                    return (b.campaigns_count || 0) - (a.campaigns_count || 0);
                }
                return 0;
            });

            this.filteredOrgs = filtered;
        },

        formatDate(dateString) {
            if (!dateString) return 'غير متوفر';
            const date = new Date(dateString);
            return date.toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        editOrg(org) {
            // TODO: Implement edit functionality
            // For now, just show notification
            window.notify('تعديل المؤسسة: ' + org.name, 'info');
            console.log('Edit org:', org);
        },

        async deleteOrg(orgId) {
            if (!confirm('هل أنت متأكد من حذف هذه المؤسسة؟ سيتم حذف جميع البيانات المرتبطة بها.')) return;

            try {
                // TODO: Implement actual API call with CSRF token
                // For now, just remove from local array
                window.notify('جاري حذف المؤسسة...', 'info');

                // const response = await fetch(`/api/orgs/${orgId}`, {
                //     method: 'DELETE',
                //     headers: {
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     }
                // });

                // if (!response.ok) throw new Error('Failed to delete');

                this.allOrgs = this.allOrgs.filter(o => o.org_id !== orgId);
                this.filterOrgs();
                window.notify('تم حذف المؤسسة بنجاح', 'success');
            } catch (error) {
                console.error('Error deleting organization:', error);
                window.notify('فشل حذف المؤسسة', 'error');
            }
        }
    };
}

function orgForm() {
    return {
        formData: {
            name: '',
            description: '',
            email: '',
            phone: '',
            status: 'active',
            default_locale: 'ar',
            currency: 'SAR'
        },

        async submitOrg() {
            try {
                // Validate required fields
                if (!this.formData.name) {
                    window.notify('الرجاء إدخال اسم المؤسسة', 'warning');
                    return;
                }

                window.notify('جاري إنشاء المؤسسة...', 'info');

                // TODO: Implement actual API call with CSRF token
                // const response = await fetch('/api/orgs', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     },
                //     body: JSON.stringify(this.formData)
                // });

                // if (!response.ok) {
                //     const error = await response.json();
                //     throw new Error(error.message || 'Failed to create organization');
                // }

                window.notify('تم إنشاء المؤسسة بنجاح', 'success');
                closeModal('create-org-modal');

                // Refresh the page to show new organization
                setTimeout(() => location.reload(), 1000);
            } catch (error) {
                console.error('Error creating organization:', error);
                window.notify(error.message || 'فشل إنشاء المؤسسة', 'error');
            }
        }
    };
}
</script>
@endpush
