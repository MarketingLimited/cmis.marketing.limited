@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', 'إدارة الميزات')
@section('page-subtitle', 'تفعيل وإيقاف ميزات النظام')

@section('content')
<div x-data="featureFlagsManager()" x-init="init()">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">ميزات مفعّلة</p>
                    <p class="text-3xl font-bold" x-text="stats.enabled"></p>
                </div>
                <i class="fas fa-toggle-on text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-100 text-sm mb-1">ميزات معطّلة</p>
                    <p class="text-3xl font-bold" x-text="stats.disabled"></p>
                </div>
                <i class="fas fa-toggle-off text-5xl text-gray-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">تجريبية</p>
                    <p class="text-3xl font-bold" x-text="stats.experimental"></p>
                </div>
                <i class="fas fa-flask text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">قيد التطوير</p>
                    <p class="text-3xl font-bold" x-text="stats.inDevelopment"></p>
                </div>
                <i class="fas fa-code text-5xl text-purple-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-3">
            <select x-model="categoryFilter" @change="filterFlags"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">جميع الفئات</option>
                <option value="core">الأساسيات</option>
                <option value="ai">الذكاء الاصطناعي</option>
                <option value="integrations">التكاملات</option>
                <option value="analytics">التحليلات</option>
                <option value="automation">الأتمتة</option>
            </select>

            <select x-model="statusFilter" @change="filterFlags"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">جميع الحالات</option>
                <option value="enabled">مفعّل</option>
                <option value="disabled">معطّل</option>
                <option value="experimental">تجريبي</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button @click="enableAll"
                    class="bg-green-100 text-green-700 px-4 py-2 rounded-lg font-medium hover:bg-green-200 transition">
                <i class="fas fa-check-double ml-2"></i>
                تفعيل الكل
            </button>
            <button @click="disableAll"
                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition">
                <i class="fas fa-ban ml-2"></i>
                إيقاف الكل
            </button>
        </div>
    </div>

    <!-- Feature Flags List -->
    <div class="space-y-4">
        <template x-for="flag in filteredFlags" :key="flag.flag_key">
            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <!-- Flag Details -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-gray-900" x-text="flag.flag_name"></h3>

                                <!-- Status Badge -->
                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-800': flag.is_enabled && !flag.is_experimental,
                                          'bg-gray-100 text-gray-800': !flag.is_enabled,
                                          'bg-blue-100 text-blue-800': flag.is_experimental,
                                          'bg-purple-100 text-purple-800': flag.in_development
                                      }">
                                    <template x-if="flag.is_experimental">تجريبي</template>
                                    <template x-if="!flag.is_experimental && flag.is_enabled">مفعّل</template>
                                    <template x-if="!flag.is_experimental && !flag.is_enabled">معطّل</template>
                                </span>

                                <!-- Category -->
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"
                                      x-text="getCategoryLabel(flag.category)"></span>
                            </div>

                            <p class="text-sm text-gray-600 mb-3" x-text="flag.description"></p>

                            <!-- Technical Details -->
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center">
                                    <i class="fas fa-code text-xs ml-1"></i>
                                    <code class="bg-gray-100 px-2 py-1 rounded" x-text="flag.flag_key"></code>
                                </span>
                                <template x-if="flag.version">
                                    <span class="flex items-center">
                                        <i class="fas fa-tag text-xs ml-1"></i>
                                        <span x-text="'الإصدار ' + flag.version"></span>
                                    </span>
                                </template>
                                <template x-if="flag.last_updated">
                                    <span class="flex items-center">
                                        <i class="fas fa-clock text-xs ml-1"></i>
                                        <span x-text="formatDate(flag.last_updated)"></span>
                                    </span>
                                </template>
                            </div>

                            <!-- Dependencies -->
                            <template x-if="flag.dependencies && flag.dependencies.length > 0">
                                <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                    <p class="text-xs text-yellow-800 mb-1">
                                        <i class="fas fa-exclamation-triangle ml-1"></i>
                                        <strong>يتطلب:</strong>
                                    </p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="dep in flag.dependencies" :key="dep">
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs" x-text="dep"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Warnings -->
                            <template x-if="flag.warning_message">
                                <div class="mt-3 bg-red-50 border border-red-200 rounded-lg p-3">
                                    <p class="text-xs text-red-800">
                                        <i class="fas fa-exclamation-circle ml-1"></i>
                                        <span x-text="flag.warning_message"></span>
                                    </p>
                                </div>
                            </template>
                        </div>

                        <!-- Toggle Switch -->
                        <div class="mr-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       :checked="flag.is_enabled"
                                       @change="toggleFlag(flag.flag_key)"
                                       :disabled="flag.requires_restart"
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600 peer-disabled:opacity-50 peer-disabled:cursor-not-allowed"></div>
                            </label>
                            <template x-if="flag.requires_restart">
                                <p class="text-xs text-orange-600 mt-1 text-center">
                                    <i class="fas fa-sync-alt"></i> يتطلب إعادة تشغيل
                                </p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredFlags.length === 0">
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-flag text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد ميزات</h3>
            <p class="text-gray-600">لا توجد ميزات تطابق الفلاتر المحددة</p>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function featureFlagsManager() {
    return {
        flags: @json($flags ?? []),
        categoryFilter: 'all',
        statusFilter: 'all',
        stats: {
            enabled: 0,
            disabled: 0,
            experimental: 0,
            inDevelopment: 0
        },

        init() {
            this.calculateStats();
        },

        get filteredFlags() {
            return this.flags.filter(flag => {
                const categoryMatch = this.categoryFilter === 'all' || flag.category === this.categoryFilter;
                const statusMatch = this.statusFilter === 'all' ||
                    (this.statusFilter === 'enabled' && flag.is_enabled) ||
                    (this.statusFilter === 'disabled' && !flag.is_enabled) ||
                    (this.statusFilter === 'experimental' && flag.is_experimental);
                return categoryMatch && statusMatch;
            });
        },

        calculateStats() {
            this.stats.enabled = this.flags.filter(f => f.is_enabled && !f.is_experimental).length;
            this.stats.disabled = this.flags.filter(f => !f.is_enabled).length;
            this.stats.experimental = this.flags.filter(f => f.is_experimental).length;
            this.stats.inDevelopment = this.flags.filter(f => f.in_development).length;
        },

        filterFlags() {
            // Handled by computed property
        },

        getCategoryLabel(category) {
            const labels = {
                'core': 'الأساسيات',
                'ai': 'الذكاء الاصطناعي',
                'integrations': 'التكاملات',
                'analytics': 'التحليلات',
                'automation': 'الأتمتة'
            };
            return labels[category] || category;
        },

        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        async toggleFlag(flagKey) {
            const flag = this.flags.find(f => f.flag_key === flagKey);
            if (!flag) return;

            // Check dependencies
            if (!flag.is_enabled && flag.dependencies) {
                const missingDeps = flag.dependencies.filter(dep => {
                    const depFlag = this.flags.find(f => f.flag_key === dep);
                    return !depFlag || !depFlag.is_enabled;
                });

                if (missingDeps.length > 0) {
                    alert(`يجب تفعيل الميزات التالية أولاً: ${missingDeps.join(', ')}`);
                    return;
                }
            }

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/feature-flags/${flagKey}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        enabled: !flag.is_enabled
                    })
                });

                if (response.ok) {
                    flag.is_enabled = !flag.is_enabled;
                    flag.last_updated = new Date().toISOString();
                    this.calculateStats();

                    if (flag.requires_restart) {
                        alert('تم تحديث الميزة. يتطلب إعادة تشغيل النظام لتطبيق التغييرات.');
                    }
                }
            } catch (error) {
                console.error('Failed to toggle flag:', error);
                alert('فشل تحديث الميزة');
            }
        },

        async enableAll() {
            if (!confirm('هل تريد تفعيل جميع الميزات؟')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/feature-flags/enable-all`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.flags.forEach(flag => flag.is_enabled = true);
                    this.calculateStats();
                    alert('تم تفعيل جميع الميزات');
                }
            } catch (error) {
                console.error('Failed to enable all:', error);
            }
        },

        async disableAll() {
            if (!confirm('هل تريد إيقاف جميع الميزات؟ قد يؤثر هذا على عمل النظام.')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/feature-flags/disable-all`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.flags.forEach(flag => flag.is_enabled = false);
                    this.calculateStats();
                    alert('تم إيقاف جميع الميزات');
                }
            } catch (error) {
                console.error('Failed to disable all:', error);
            }
        }
    };
}
</script>
@endpush
