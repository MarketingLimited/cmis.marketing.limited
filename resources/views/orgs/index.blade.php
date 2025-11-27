@extends('layouts.admin')

@section('title', 'المؤسسات')

@section('content')
<div x-data="{
    ...orgsManager({{ Js::from($orgs) }}),
    orgFormData: orgForm()
}" x-cloak>

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
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <div class="flex-1">
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="filterOrgs()"
                       placeholder="البحث عن مؤسسة..."
                       aria-label="البحث عن مؤسسة"
                       class="w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>
            <div class="sm:w-48">
                <select x-model="filterStatus"
                        @change="filterOrgs()"
                        aria-label="تصفية حسب الحالة"
                        class="w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">جميع الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            <div class="sm:w-48">
                <select x-model="sortBy"
                        @change="filterOrgs()"
                        aria-label="الترتيب حسب"
                        class="w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="name">الاسم</option>
                    <option value="created_at">تاريخ الإنشاء</option>
                    <option value="campaigns_count">عدد الحملات</option>
                </select>
            </div>
        </div>
    </x-ui.card>

    <!-- Loading Skeleton (shows before Alpine.js loads) -->
    <div x-show="false" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        @for($i = 0; $i < 6; $i++)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden animate-pulse">
            <div class="h-20 sm:h-24 bg-gradient-to-br from-gray-300 to-gray-400"></div>
            <div class="pt-10 sm:pt-12 p-4 sm:p-6 space-y-3">
                <div class="h-6 bg-gray-300 rounded w-3/4"></div>
                <div class="flex gap-2">
                    <div class="h-6 bg-gray-200 rounded w-16"></div>
                    <div class="h-6 bg-gray-200 rounded w-16"></div>
                </div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                <div class="grid grid-cols-3 gap-2 pt-2">
                    <div class="h-12 bg-gray-200 rounded"></div>
                    <div class="h-12 bg-gray-200 rounded"></div>
                    <div class="h-12 bg-gray-200 rounded"></div>
                </div>
            </div>
        </div>
        @endfor
    </div>

    <!-- Organizations Grid -->
    <div x-show="filteredOrgs.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <template x-for="org in filteredOrgs" :key="org.org_id">
            <div class="org-card bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-xl transition-all duration-200 overflow-hidden">
                <!-- Header with gradient -->
                <div class="h-20 sm:h-24 bg-gradient-to-br from-blue-500 to-purple-600 relative">
                    <div class="absolute bottom-0 right-4 sm:right-6 transform translate-y-1/2">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white dark:bg-gray-800 rounded-full border-4 border-white dark:border-gray-800 flex items-center justify-center shadow-lg">
                            <i class="fas fa-building text-2xl sm:text-3xl text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="pt-10 sm:pt-12 p-4 sm:p-6">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-2 truncate" x-text="org.name" :title="org.name"></h3>
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
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
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 mb-4 text-center">
                        <div>
                            <div class="text-xl sm:text-2xl font-bold text-blue-600" x-text="org.campaigns_count || 0"></div>
                            <div class="text-[10px] sm:text-xs text-gray-500">حملة</div>
                        </div>
                        <div>
                            <div class="text-xl sm:text-2xl font-bold text-green-600" x-text="org.users_count || 0"></div>
                            <div class="text-[10px] sm:text-xs text-gray-500">مستخدم</div>
                        </div>
                        <div>
                            <div class="text-xl sm:text-2xl font-bold text-purple-600" x-text="org.assets_count || 0"></div>
                            <div class="text-[10px] sm:text-xs text-gray-500">أصل</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a :href="'/orgs/' + org.org_id"
                           class="text-blue-600 hover:text-blue-700 text-sm font-semibold inline-flex items-center gap-1 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded"
                           :aria-label="'عرض تفاصيل ' + org.name">
                            <i class="fas fa-eye"></i>
                            <span class="hidden sm:inline">عرض التفاصيل</span>
                            <span class="sm:hidden">عرض</span>
                        </a>
                        <div class="flex gap-2">
                            <button @click="editOrg(org)"
                                    class="p-3 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    :aria-label="'تعديل ' + org.name"
                                    title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="deleteOrg(org.org_id)"
                                    class="p-3 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-600 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                    :aria-label="'حذف ' + org.name"
                                    title="حذف">
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

    <!-- Create/Edit Organization Modal -->
    <x-ui.modal name="create-org-modal" title="مؤسسة جديدة" max-width="lg">
        <form @submit.prevent="orgFormData.submitOrg()">
            <x-forms.input
                label="اسم المؤسسة"
                name="name"
                x-model="orgFormData.formData.name"
                required
                placeholder="أدخل اسم المؤسسة" />

            <x-forms.textarea
                label="الوصف"
                name="description"
                x-model="orgFormData.formData.description"
                placeholder="وصف قصير عن المؤسسة" />

            <x-forms.input
                label="البريد الإلكتروني"
                name="email"
                type="email"
                x-model="orgFormData.formData.email"
                placeholder="email@example.com" />

            <x-forms.input
                label="رقم الهاتف"
                name="phone"
                x-model="orgFormData.formData.phone"
                placeholder="+966 50 000 0000" />

            <x-forms.select
                label="الحالة"
                name="status"
                x-model="orgFormData.formData.status"
                required>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
            </x-forms.select>
        </form>

        <x-slot name="footer">
            <x-ui.button type="button" variant="secondary" @click="closeModal('create-org-modal')">
                إلغاء
            </x-ui.button>
            <x-ui.button type="button" icon="fas fa-save" @click="orgFormData.submitOrg()">
                حفظ
            </x-ui.button>
        </x-slot>
    </x-ui.modal>

</div>

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
            // Initialize with server data - counts are now provided by backend
            this.filteredOrgs = this.allOrgs;
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
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'تاريخ غير صالح';
                return new Intl.DateTimeFormat('ar-SA', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    timeZone: 'Asia/Riyadh'
                }).format(date);
            } catch (error) {
                console.error('Date formatting error:', error);
                return 'خطأ في التاريخ';
            }
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
                window.notify('جاري حذف المؤسسة...', 'info');

                const response = await fetch(`/api/orgs/${orgId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'فشل حذف المؤسسة');
                }

                this.allOrgs = this.allOrgs.filter(o => o.org_id !== orgId);
                this.filterOrgs();
                window.notify(data.message || 'تم حذف المؤسسة بنجاح', 'success');
            } catch (error) {
                console.error('Error deleting organization:', error);
                window.notify(error.message || 'حدث خطأ غير متوقع', 'error');
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

        validateForm() {
            const errors = [];

            if (!this.formData.name || this.formData.name.trim().length < 3) {
                errors.push('اسم المؤسسة يجب أن يكون 3 أحرف على الأقل');
            }

            if (this.formData.email && !this.isValidEmail(this.formData.email)) {
                errors.push('البريد الإلكتروني غير صحيح');
            }

            if (this.formData.phone && !this.isValidPhone(this.formData.phone)) {
                errors.push('رقم الهاتف غير صحيح (مثال: +966 50 000 0000)');
            }

            return errors;
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        isValidPhone(phone) {
            // Saudi phone format: +966 5X XXX XXXX or 05X XXX XXXX
            const cleaned = phone.replace(/\s/g, '');
            return /^(\+966|00966|0)?5\d{8}$/.test(cleaned);
        },

        async submitOrg() {
            try {
                // Validate form
                const errors = this.validateForm();
                if (errors.length > 0) {
                    window.notify(errors[0], 'warning');
                    return;
                }

                window.notify('جاري إنشاء المؤسسة...', 'info');

                const response = await fetch('/orgs', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'فشل إنشاء المؤسسة');
                }

                window.notify(data.message || 'تم إنشاء المؤسسة بنجاح', 'success');
                closeModal('create-org-modal');

                // Redirect to the new organization page or reload
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        location.reload();
                    }
                }, 1000);
            } catch (error) {
                console.error('Error creating organization:', error);
                window.notify(error.message || 'حدث خطأ غير متوقع', 'error');
            }
        }
    };
}
</script>
@endpush
