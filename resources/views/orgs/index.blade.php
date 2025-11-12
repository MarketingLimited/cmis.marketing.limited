@extends('layouts.admin')

@section('title', 'المؤسسات')

@section('content')
<div x-data="orgsManager()" x-init="init()">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">المؤسسات</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">إدارة جميع المؤسسات والعملاء</p>
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
                       @input="searchOrgs()"
                       placeholder="البحث عن مؤسسة..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <select x-model="filterStatus"
                        @change="searchOrgs()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">جميع الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            <div>
                <select x-model="sortBy"
                        @change="searchOrgs()"
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
        <template x-for="org in orgs" :key="org.org_id">
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
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-text="org.description || 'لا يوجد وصف'"></p>

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
                            <button @click="editOrg(org)" class="text-gray-600 hover:text-blue-600">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="deleteOrg(org.org_id)" class="text-gray-600 hover:text-red-600">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="orgs.length === 0" class="text-center py-12">
        <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">لا توجد مؤسسات</h3>
        <p class="text-gray-500 mb-4">ابدأ بإضافة مؤسسة جديدة</p>
        <x-ui.button @click="openModal('create-org-modal')" icon="fas fa-plus">
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
function orgsManager() {
    return {
        orgs: [],
        searchQuery: '',
        filterStatus: '',
        sortBy: 'name',

        async init() {
            await this.fetchOrgs();
        },

        async fetchOrgs() {
            try {
                // Simulated data - replace with actual API call
                this.orgs = [
                    {
                        org_id: '1',
                        name: 'شركة التسويق الرقمي',
                        description: 'متخصصون في التسويق الرقمي والإعلانات',
                        campaigns_count: 15,
                        users_count: 8,
                        assets_count: 45
                    },
                    {
                        org_id: '2',
                        name: 'الإبداع التقني',
                        description: 'حلول تقنية مبتكرة',
                        campaigns_count: 12,
                        users_count: 5,
                        assets_count: 32
                    },
                    {
                        org_id: '3',
                        name: 'المستقبل الذكي',
                        description: 'نبني المستقبل بالذكاء الاصطناعي',
                        campaigns_count: 20,
                        users_count: 12,
                        assets_count: 67
                    }
                ];

                // Replace with:
                // const response = await fetch('/api/orgs');
                // this.orgs = await response.json();
            } catch (error) {
                console.error('Error fetching organizations:', error);
                window.notify('فشل تحميل المؤسسات', 'error');
            }
        },

        searchOrgs() {
            // Implement search/filter logic
            console.log('Searching with:', this.searchQuery, this.filterStatus, this.sortBy);
        },

        editOrg(org) {
            // Implement edit logic
            console.log('Editing org:', org);
            window.notify('جاري تحميل بيانات المؤسسة...', 'info');
        },

        async deleteOrg(orgId) {
            if (!confirm('هل أنت متأكد من حذف هذه المؤسسة؟')) return;

            try {
                // Replace with actual API call
                // await fetch(`/api/orgs/${orgId}`, { method: 'DELETE' });
                this.orgs = this.orgs.filter(o => o.org_id !== orgId);
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
            status: 'active'
        },

        async submitOrg() {
            try {
                // Replace with actual API call
                // const response = await fetch('/api/orgs', {
                //     method: 'POST',
                //     headers: { 'Content-Type': 'application/json' },
                //     body: JSON.stringify(this.formData)
                // });

                window.notify('تم إنشاء المؤسسة بنجاح', 'success');
                closeModal('create-org-modal');

                // Refresh list
                location.reload();
            } catch (error) {
                console.error('Error creating organization:', error);
                window.notify('فشل إنشاء المؤسسة', 'error');
            }
        }
    };
}
</script>
@endpush
