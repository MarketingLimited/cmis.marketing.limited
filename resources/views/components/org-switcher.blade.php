{{-- Organization Switcher Component --}}
<div x-data="orgSwitcher()" x-init="init()" class="relative">
    {{-- Trigger Button --}}
    <button
        @click="open = !open"
        class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-all duration-200 text-right"
        :class="{ 'ring-2 ring-white/30': open }"
    >
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="bg-white/20 rounded-lg p-2 flex-shrink-0">
                    <i class="fas fa-building text-white text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white/60 text-xs">المنظمة الحالية</p>
                    <p class="text-white font-medium text-sm truncate" x-text="activeOrg?.name || 'جاري التحميل...'"></p>
                </div>
            </div>
            <i class="fas fa-chevron-down text-white/60 text-xs transition-transform duration-200"
               :class="{ 'rotate-180': open }"></i>
        </div>
    </button>

    {{-- Dropdown Menu --}}
    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden z-50"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-500 border-b border-gray-200">
            <p class="text-white text-sm font-semibold">اختر منظمة</p>
            <p class="text-white/80 text-xs mt-0.5">تبديل بين المنظمات الخاصة بك</p>
        </div>

        {{-- Organizations List --}}
        <div class="max-h-64 overflow-y-auto">
            <template x-if="loading">
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                    <p class="text-gray-500 text-sm mt-2">جاري التحميل...</p>
                </div>
            </template>

            <template x-if="!loading && organizations.length === 0">
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-building text-gray-300 text-3xl"></i>
                    <p class="text-gray-500 text-sm mt-2">لا توجد منظمات</p>
                </div>
            </template>

            <template x-if="!loading && organizations.length > 0">
                <div class="py-1">
                    <template x-for="org in organizations" :key="org.org_id">
                        <button
                            @click="switchToOrg(org.org_id)"
                            class="w-full px-4 py-3 hover:bg-gray-50 transition-colors duration-150 text-right flex items-center gap-3"
                            :class="{ 'bg-purple-50': org.org_id === activeOrg?.org_id }"
                            :disabled="switching"
                        >
                            {{-- Org Icon --}}
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                :class="org.org_id === activeOrg?.org_id ? 'bg-purple-100' : 'bg-gray-100'"
                            >
                                <i
                                    class="fas fa-building"
                                    :class="org.org_id === activeOrg?.org_id ? 'text-purple-600' : 'text-gray-500'"
                                ></i>
                            </div>

                            {{-- Org Info --}}
                            <div class="flex-1 min-w-0">
                                <p
                                    class="font-medium text-sm truncate"
                                    :class="org.org_id === activeOrg?.org_id ? 'text-purple-900' : 'text-gray-900'"
                                    x-text="org.name"
                                ></p>
                                <p class="text-xs text-gray-500 truncate" x-text="org.slug || 'لا يوجد رمز'"></p>
                            </div>

                            {{-- Active Indicator --}}
                            <div class="flex-shrink-0">
                                <template x-if="org.org_id === activeOrg?.org_id">
                                    <i class="fas fa-check-circle text-purple-600"></i>
                                </template>
                            </div>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        {{-- Switching Indicator --}}
        <template x-if="switching">
            <div class="absolute inset-0 bg-white/90 backdrop-blur-sm flex items-center justify-center">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-purple-600 text-2xl"></i>
                    <p class="text-gray-700 text-sm mt-2 font-medium">جاري التبديل...</p>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function orgSwitcher() {
    return {
        open: false,
        loading: true,
        switching: false,
        organizations: [],
        activeOrg: null,

        async init() {
            await this.loadOrganizations();
        },

        async loadOrganizations() {
            this.loading = true;

            try {
                const response = await fetch('/api/user/organizations', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Failed to load organizations');
                }

                const data = await response.json();
                this.organizations = data.organizations || [];

                // Find and set active org
                const activeOrgId = data.active_org_id;
                this.activeOrg = this.organizations.find(org => org.org_id === activeOrgId) || this.organizations[0];

            } catch (error) {
                console.error('Error loading organizations:', error);
                // Optionally show error notification
            } finally {
                this.loading = false;
            }
        },

        async switchToOrg(orgId) {
            if (this.switching || orgId === this.activeOrg?.org_id) {
                return;
            }

            this.switching = true;

            try {
                const response = await fetch('/api/user/switch-organization', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ org_id: orgId }),
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to switch organization');
                }

                const data = await response.json();

                // Update active org
                this.activeOrg = data.active_org;
                this.open = false;

                // Reload page to refresh all org-specific data
                window.location.reload();

            } catch (error) {
                console.error('Error switching organization:', error);
                alert('فشل تبديل المنظمة. الرجاء المحاولة مرة أخرى.');
            } finally {
                this.switching = false;
            }
        },
    };
}
</script>
