@extends('layouts.admin')

@section('title', __('marketplace.title'))
@section('page-title', __('marketplace.title'))
@section('page-subtitle', __('marketplace.subtitle'))

@push('styles')
<style>
/* Hide scrollbar for category tabs */
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush

@section('content')
<div x-data="marketplaceApp()" x-cloak class="space-y-4">
    {{-- Sticky Header with Search and Scrollable Category Tabs --}}
    <div class="bg-white rounded-2xl shadow-sm p-4 sticky top-0 z-10">
        <div class="flex items-center gap-4">
            {{-- Search (compact) --}}
            <div class="relative w-48 lg:w-64 flex-shrink-0">
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="{{ __('marketplace.search_placeholder') }}"
                    class="w-full ps-9 pe-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                >
                <i class="fas fa-search absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            </div>

            {{-- Scrollable Category Tabs --}}
            <div class="flex-1 overflow-x-auto scrollbar-hide">
                <div class="flex gap-2 pb-1">
                    <button
                        @click="selectedCategory = null"
                        :class="selectedCategory === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="flex-shrink-0 px-3 py-1.5 text-sm rounded-lg font-medium whitespace-nowrap transition"
                    >
                        {{ __('marketplace.all_categories') }}
                    </button>
                    @foreach($categories as $category)
                        <button
                            @click="selectedCategory = '{{ $category->slug }}'"
                            :class="selectedCategory === '{{ $category->slug }}' ? getCategoryButtonActiveClass('{{ $category->slug }}') : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="flex-shrink-0 px-3 py-1.5 text-sm rounded-lg font-medium whitespace-nowrap transition"
                        >
                            <i class="fas {{ $category->icon }} me-1.5"></i>
                            {{ __($category->name_key) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Bulk Selection Toggle --}}
            <button
                @click="bulkMode = !bulkMode; if(!bulkMode) selectedApps = []"
                :class="bulkMode ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="flex-shrink-0 px-3 py-1.5 text-sm rounded-lg font-medium transition hidden sm:flex items-center gap-1.5"
            >
                <i class="fas fa-check-double"></i>
                <span class="hidden md:inline">{{ __('marketplace.bulk_select') }}</span>
            </button>
        </div>
    </div>

    {{-- Premium Badge Info (if no premium access) --}}
    @unless($hasPremium)
    <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 rounded-xl p-3">
        <div class="flex items-center gap-3">
            <div class="bg-amber-100 rounded-full p-2">
                <i class="fas fa-crown text-amber-600 text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-amber-800 text-sm">{{ __('marketplace.premium_info_title') }}</p>
                <p class="text-xs text-amber-700 truncate">{{ __('marketplace.premium_info_description') }}</p>
            </div>
            <a href="#" class="flex-shrink-0 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium text-sm transition">
                {{ __('marketplace.upgrade_now') }}
            </a>
        </div>
    </div>
    @endunless

    {{-- Apps by Category --}}
    @foreach($categories as $category)
        @if($category->apps->count() > 0)
        <div
            x-show="selectedCategory === null || selectedCategory === '{{ $category->slug }}'"
            x-transition
            class="space-y-3"
        >
            {{-- Category Header (Compact) --}}
            <div class="flex items-center gap-2.5">
                <div class="bg-gradient-to-r {{ getCategoryGradient($category->slug) }} text-white rounded-lg p-2">
                    <i class="fas {{ $category->icon }} text-sm"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-gray-800">{{ __($category->name_key) }}</h2>
                    <p class="text-xs text-gray-500 truncate">{{ __($category->description_key) }}</p>
                </div>
            </div>

            {{-- Apps Grid: 1 col mobile, 2 tablet, 3 laptop, 4 desktop --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                @foreach($category->apps as $app)
                <div
                    x-show="searchQuery === '' || '{{ strtolower($app->name ?? '') }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower(__($app->name_key)) }}'.includes(searchQuery.toLowerCase())"
                    x-transition
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition group relative flex flex-col h-full min-h-[180px]"
                >
                    {{-- Bulk Select Checkbox --}}
                    <div x-show="bulkMode && !{{ $app->is_core ? 'true' : 'false' }}" class="absolute top-2 end-2 z-10">
                        <input
                            type="checkbox"
                            :value="'{{ $app->slug }}'"
                            x-model="selectedApps"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                    </div>

                    {{-- Card Content (grows to fill space) --}}
                    <div class="flex-1">
                        {{-- Compact Header --}}
                        <div class="flex items-center gap-2.5 mb-2">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br {{ getCategoryGradient($category->slug) }}">
                                <i class="fas {{ $app->icon }} text-white text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-800 text-sm truncate">{{ __($app->name_key) }}</h3>
                                <div class="flex items-center gap-1.5">
                                    @if($app->is_premium)
                                        <span class="text-xs text-amber-600 font-medium">
                                            <i class="fas fa-crown me-0.5 text-amber-500"></i>
                                            {{ __('marketplace.premium') }}
                                        </span>
                                    @endif
                                    @if($app->is_core)
                                        <span class="text-xs text-green-600 font-medium">{{ __('marketplace.core_feature') }}</span>
                                    @endif
                                </div>
                            </div>
                            {{-- Status Dot --}}
                            @if($app->is_core)
                                <div class="w-2.5 h-2.5 rounded-full bg-green-500 flex-shrink-0" title="{{ __('marketplace.enabled') }}"></div>
                            @else
                                <div x-data="{ enabled: {{ in_array($app->slug, $enabledSlugs) ? 'true' : 'false' }} }">
                                    <div
                                        :class="enabled ? 'bg-green-500' : 'bg-gray-300'"
                                        class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                        :title="enabled ? '{{ __('marketplace.enabled') }}' : '{{ __('marketplace.disabled') }}'"
                                    ></div>
                                </div>
                            @endif
                        </div>

                        {{-- Short Description (2 lines max) --}}
                        <p class="text-xs text-gray-500 line-clamp-2">{{ __($app->description_key) }}</p>

                        {{-- Dependencies (compact) --}}
                        @if(!empty($app->dependencies))
                        <div class="mt-2 text-xs text-gray-400">
                            <i class="fas fa-link me-1"></i>
                            {{ __('marketplace.requires') }}: {{ count($app->dependencies) }} {{ __('marketplace.apps_count') }}
                        </div>
                        @endif
                    </div>

                    {{-- Action Button (always at bottom with mt-auto) --}}
                    <div class="mt-auto pt-3">
                        @if($app->is_core)
                            <div class="w-full py-2 text-xs rounded-lg font-medium bg-green-50 text-green-600 text-center">
                                <i class="fas fa-check-circle me-1.5"></i>
                                {{ __('marketplace.core_feature') }}
                            </div>
                        @else
                            <div x-data="appToggle('{{ $app->slug }}', {{ in_array($app->slug, $enabledSlugs) ? 'true' : 'false' }})">
                                @if($app->is_premium && !$hasPremium)
                                    <button
                                        disabled
                                        class="w-full py-2 text-xs rounded-lg font-medium bg-gray-100 text-gray-400 cursor-not-allowed"
                                    >
                                        <i class="fas fa-lock me-1.5"></i>
                                        {{ __('marketplace.premium_required') }}
                                    </button>
                                @else
                                    <button
                                        @click="toggle()"
                                        :disabled="loading"
                                        :class="enabled ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-indigo-600 text-white hover:bg-indigo-700'"
                                        class="w-full py-2 text-xs rounded-lg font-medium transition disabled:opacity-50"
                                    >
                                        <span x-show="!loading">
                                            <span x-show="enabled">
                                                <i class="fas fa-times-circle me-1.5"></i>
                                                {{ __('marketplace.disable') }}
                                            </span>
                                            <span x-show="!enabled">
                                                <i class="fas fa-plus-circle me-1.5"></i>
                                                {{ __('marketplace.enable') }}
                                            </span>
                                        </span>
                                        <span x-show="loading">
                                            <i class="fas fa-spinner fa-spin me-1.5"></i>
                                        </span>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

    {{-- Empty State --}}
    <div
        x-show="searchQuery !== '' && document.querySelectorAll('[x-show*=searchQuery]:not([style*=\'display: none\'])').length === 0"
        class="text-center py-8"
    >
        <div class="bg-gray-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-search text-gray-400 text-lg"></i>
        </div>
        <h3 class="font-medium text-gray-700 mb-1 text-sm">{{ __('marketplace.no_results_title') }}</h3>
        <p class="text-xs text-gray-500">{{ __('marketplace.no_results_description') }}</p>
    </div>

    {{-- Bulk Action Bar (shows when apps selected) --}}
    <div
        x-show="selectedApps.length > 0"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 inset-x-0 flex justify-center z-50 px-4"
    >
        <div class="bg-gray-900 text-white rounded-xl px-4 py-3 flex items-center gap-4 shadow-xl">
            <span class="text-sm">
                <span x-text="selectedApps.length"></span> {{ __('marketplace.selected') }}
            </span>
            <div class="h-4 w-px bg-gray-700"></div>
            <button
                @click="bulkEnable()"
                :disabled="bulkLoading"
                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-medium transition disabled:opacity-50"
            >
                <i class="fas fa-check-circle me-1.5"></i>
                {{ __('marketplace.enable_all') }}
            </button>
            <button
                @click="bulkDisable()"
                :disabled="bulkLoading"
                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium transition disabled:opacity-50"
            >
                <i class="fas fa-times-circle me-1.5"></i>
                {{ __('marketplace.disable_all') }}
            </button>
            <button
                @click="selectedApps = []; bulkMode = false"
                class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium transition"
            >
                {{ __('common.cancel') }}
            </button>
        </div>
    </div>
</div>

@php
// Category color gradients helper
function getCategoryGradient($categorySlug) {
    $colors = [
        'core' => 'from-slate-500 to-slate-700',
        'marketing' => 'from-blue-500 to-indigo-600',
        'analytics' => 'from-emerald-500 to-teal-600',
        'ai' => 'from-violet-500 to-purple-600',
        'automation' => 'from-orange-500 to-amber-600',
        'system' => 'from-gray-500 to-gray-700',
    ];
    return $colors[$categorySlug] ?? 'from-indigo-500 to-purple-600';
}
@endphp

@push('scripts')
<script>
// App toggle component for enable/disable buttons
function appToggle(appSlug, initialEnabled) {
    return {
        appSlug: appSlug,
        enabled: initialEnabled,
        loading: false,
        csrfToken: '{{ csrf_token() }}',
        orgId: '{{ $orgId }}',

        async toggle() {
            this.loading = true;

            const endpoint = this.enabled
                ? `/orgs/${this.orgId}/marketplace/apps/${this.appSlug}/disable`
                : `/orgs/${this.orgId}/marketplace/apps/${this.appSlug}/enable`;

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
                    this.enabled = !this.enabled;
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error toggling app:', error);
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: '{{ __("common.error_occurred") }}', type: 'error' }
                }));
            } finally {
                this.loading = false;
            }
        }
    };
}

// Main marketplace app for search, filter, and bulk actions
function marketplaceApp() {
    return {
        searchQuery: '',
        selectedCategory: null,
        bulkMode: false,
        selectedApps: [],
        bulkLoading: false,
        csrfToken: '{{ csrf_token() }}',
        orgId: '{{ $orgId }}',

        getCategoryButtonActiveClass(category) {
            const colors = {
                'core': 'bg-slate-600 text-white',
                'marketing': 'bg-blue-600 text-white',
                'analytics': 'bg-emerald-600 text-white',
                'ai': 'bg-violet-600 text-white',
                'automation': 'bg-orange-600 text-white',
                'system': 'bg-gray-600 text-white',
            };
            return colors[category] || 'bg-indigo-600 text-white';
        },

        async bulkEnable() {
            if (this.selectedApps.length === 0) return;
            this.bulkLoading = true;

            try {
                const response = await fetch(`/orgs/${this.orgId}/marketplace/bulk-enable`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ slugs: this.selectedApps }),
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error bulk enabling apps:', error);
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: '{{ __("common.error_occurred") }}', type: 'error' }
                }));
            } finally {
                this.bulkLoading = false;
            }
        },

        async bulkDisable() {
            if (this.selectedApps.length === 0) return;
            this.bulkLoading = true;

            try {
                const response = await fetch(`/orgs/${this.orgId}/marketplace/bulk-disable`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ slugs: this.selectedApps }),
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error bulk disabling apps:', error);
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: '{{ __("common.error_occurred") }}', type: 'error' }
                }));
            } finally {
                this.bulkLoading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
