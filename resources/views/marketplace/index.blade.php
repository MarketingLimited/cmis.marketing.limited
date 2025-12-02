@extends('layouts.admin')

@section('title', __('marketplace.title'))
@section('page-title', __('marketplace.title'))
@section('page-subtitle', __('marketplace.subtitle'))

@section('content')
<div x-data="marketplaceApp()" x-cloak class="space-y-6">
    {{-- Header with Search and Filter --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            {{-- Search --}}
            <div class="relative flex-1 max-w-md">
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="{{ __('marketplace.search_placeholder') }}"
                    class="w-full ps-10 pe-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                >
                <i class="fas fa-search absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>

            {{-- Category Filter --}}
            <div class="flex flex-wrap gap-2">
                <button
                    @click="selectedCategory = null"
                    :class="selectedCategory === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-xl font-medium transition"
                >
                    {{ __('marketplace.all_categories') }}
                </button>
                @foreach($categories as $category)
                    @if($category->slug !== 'core')
                    <button
                        @click="selectedCategory = '{{ $category->slug }}'"
                        :class="selectedCategory === '{{ $category->slug }}' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-xl font-medium transition"
                    >
                        <i class="fas {{ $category->icon }} me-2"></i>
                        {{ __($category->name_key) }}
                    </button>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Premium Badge Info (if no premium access) --}}
    @unless($hasPremium)
    <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 rounded-2xl p-4">
        <div class="flex items-center gap-3">
            <div class="bg-amber-100 rounded-full p-2">
                <i class="fas fa-crown text-amber-600"></i>
            </div>
            <div class="flex-1">
                <p class="font-medium text-amber-800">{{ __('marketplace.premium_info_title') }}</p>
                <p class="text-sm text-amber-700">{{ __('marketplace.premium_info_description') }}</p>
            </div>
            <a href="#" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-medium transition">
                {{ __('marketplace.upgrade_now') }}
            </a>
        </div>
    </div>
    @endunless

    {{-- Apps by Category --}}
    @foreach($categories as $category)
        @if($category->slug !== 'core' && $category->apps->where('is_core', false)->count() > 0)
        <div
            x-show="selectedCategory === null || selectedCategory === '{{ $category->slug }}'"
            x-transition
            class="space-y-4"
        >
            {{-- Category Header --}}
            <div class="flex items-center gap-3">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl p-3">
                    <i class="fas {{ $category->icon }} text-lg"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">{{ __($category->name_key) }}</h2>
                    <p class="text-sm text-gray-500">{{ __($category->description_key) }}</p>
                </div>
            </div>

            {{-- Apps Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($category->apps->where('is_core', false) as $app)
                <div
                    x-show="searchQuery === '' || '{{ strtolower($app->name) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower(__($app->name_key)) }}'.includes(searchQuery.toLowerCase())"
                    x-transition
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition"
                >
                    <div class="p-5">
                        {{-- App Header --}}
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl p-3">
                                    <i class="fas {{ $app->icon }} text-indigo-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">{{ __($app->name_key) }}</h3>
                                    @if($app->is_premium)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        <i class="fas fa-crown me-1 text-amber-500"></i>
                                        {{ __('marketplace.premium') }}
                                    </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Status Indicator --}}
                            <div
                                x-data="{ enabled: {{ in_array($app->slug, $enabledSlugs) ? 'true' : 'false' }} }"
                                class="flex-shrink-0"
                            >
                                <span
                                    x-show="enabled"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                >
                                    <i class="fas fa-check-circle me-1"></i>
                                    {{ __('marketplace.enabled') }}
                                </span>
                                <span
                                    x-show="!enabled"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
                                >
                                    {{ __('marketplace.disabled') }}
                                </span>
                            </div>
                        </div>

                        {{-- Description --}}
                        <p class="text-sm text-gray-600 mb-4">{{ __($app->description_key) }}</p>

                        {{-- Dependencies --}}
                        @if(!empty($app->dependencies))
                        <div class="mb-4 p-3 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">{{ __('marketplace.requires') }}:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($app->dependencies as $dep)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-indigo-100 text-indigo-700">
                                    {{ __('marketplace.apps.' . str_replace('-', '_', $dep) . '.name') }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Action Button --}}
                        <div x-data="appToggle('{{ $app->slug }}', {{ in_array($app->slug, $enabledSlugs) ? 'true' : 'false' }})">
                            @if($app->is_premium && !$hasPremium)
                                <button
                                    disabled
                                    class="w-full py-2.5 rounded-xl font-medium bg-gray-100 text-gray-400 cursor-not-allowed"
                                >
                                    <i class="fas fa-lock me-2"></i>
                                    {{ __('marketplace.premium_required') }}
                                </button>
                            @else
                                <button
                                    @click="toggle()"
                                    :disabled="loading"
                                    :class="enabled ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-indigo-600 text-white hover:bg-indigo-700'"
                                    class="w-full py-2.5 rounded-xl font-medium transition disabled:opacity-50"
                                >
                                    <span x-show="!loading">
                                        <span x-show="enabled">
                                            <i class="fas fa-times-circle me-2"></i>
                                            {{ __('marketplace.disable') }}
                                        </span>
                                        <span x-show="!enabled">
                                            <i class="fas fa-plus-circle me-2"></i>
                                            {{ __('marketplace.enable') }}
                                        </span>
                                    </span>
                                    <span x-show="loading">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        {{ __('common.loading') }}...
                                    </span>
                                </button>
                            @endif
                        </div>
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
        class="text-center py-12"
    >
        <div class="bg-gray-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-search text-gray-400 text-2xl"></i>
        </div>
        <h3 class="font-medium text-gray-700 mb-1">{{ __('marketplace.no_results_title') }}</h3>
        <p class="text-sm text-gray-500">{{ __('marketplace.no_results_description') }}</p>
    </div>
</div>

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
                    // Show success toast
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));

                    // Toggle the enabled state
                    this.enabled = !this.enabled;

                    // Reload page to refresh sidebar after a short delay
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    // Show error toast
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

// Main marketplace app for search and filter
function marketplaceApp() {
    return {
        searchQuery: '',
        selectedCategory: null
    };
}
</script>
@endpush
@endsection
