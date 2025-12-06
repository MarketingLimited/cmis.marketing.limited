@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.apps.title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.apps.title') }}</span>
@endsection

@section('content')
<div x-data="appsManager()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.apps.title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.apps.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 transition">
                <i class="fas fa-th-large"></i>
            </button>
            <button @click="viewMode = 'matrix'"
                    :class="viewMode === 'matrix' ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 transition">
                <i class="fas fa-table"></i>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-puzzle-piece text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.total_apps') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total_apps'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-cube text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.core_apps') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['core_apps'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="fas fa-crown text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.premium_apps') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['premium_apps'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <i class="fas fa-folder text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.categories') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['categories'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid View -->
    <template x-if="viewMode === 'grid'">
        <div>
            @foreach($categories as $categoryData)
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 capitalize">
                    <i class="fas fa-folder-open text-gray-400 me-2"></i>
                    {{ __('apps.categories.' . $categoryData['category']) }}
                    <span class="text-sm font-normal text-gray-500">({{ $categoryData['count'] }})</span>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($categoryData['apps'] as $app)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-{{ $app->icon ?? 'cube' }} text-gray-600 dark:text-gray-400"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ __($app->name_key) }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($app->is_core)
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                                {{ __('super_admin.apps.core') }}
                                            </span>
                                        @endif
                                        @if($app->is_premium)
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">
                                                {{ __('super_admin.apps.premium') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('super_admin.apps.plans_enabled') }}:
                                </div>
                                <div class="flex -space-x-1 rtl:space-x-reverse">
                                    @foreach($plans as $plan)
                                        @php
                                            $isEnabled = in_array($app->app_id, $planAppMatrix[$plan->plan_id] ?? []);
                                        @endphp
                                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium
                                            {{ $isEnabled ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400' }}"
                                            title="{{ $plan->name }}: {{ $isEnabled ? __('super_admin.apps.enabled') : __('super_admin.apps.disabled') }}">
                                            {{ strtoupper(substr($plan->code, 0, 1)) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="p-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-2">
                            <a href="{{ route('super-admin.apps.show', $app->app_id) }}"
                               class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                <i class="fas fa-cog me-1"></i>
                                {{ __('super_admin.apps.manage') }}
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </template>

    <!-- Matrix View -->
    <template x-if="viewMode === 'matrix'">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('super_admin.apps.app') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('super_admin.apps.category') }}
                            </th>
                            @foreach($plans as $plan)
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $plan->name }}
                            </th>
                            @endforeach
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('super_admin.actions.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($apps as $app)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-{{ $app->icon ?? 'cube' }} text-gray-400"></i>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ __($app->name_key) }}</span>
                                    @if($app->is_core)
                                        <span class="px-1.5 py-0.5 text-xs rounded bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                            {{ __('super_admin.apps.core') }}
                                        </span>
                                    @endif
                                    @if($app->is_premium)
                                        <span class="px-1.5 py-0.5 text-xs rounded bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">
                                            <i class="fas fa-crown text-xs"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">
                                {{ __('apps.categories.' . $app->category) }}
                            </td>
                            @foreach($plans as $plan)
                            <td class="px-4 py-3 text-center">
                                @php
                                    $isEnabled = in_array($app->app_id, $planAppMatrix[$plan->plan_id] ?? []);
                                @endphp
                                <button @click="toggleAppForPlan('{{ $app->app_id }}', '{{ $plan->plan_id }}')"
                                        class="w-8 h-8 rounded-lg transition
                                            {{ $isEnabled
                                                ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-200'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                    <i class="fas {{ $isEnabled ? 'fa-check' : 'fa-times' }}"></i>
                                </button>
                            </td>
                            @endforeach
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('super-admin.apps.show', $app->app_id) }}"
                                   class="inline-flex items-center gap-1 px-2 py-1 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition">
                                    <i class="fas fa-cog"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function appsManager() {
    return {
        viewMode: 'matrix',

        async toggleAppForPlan(appId, planId) {
            try {
                const response = await fetch(`{{ url('super-admin/apps') }}/${appId}/toggle/${planId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || '{{ __('super_admin.apps.toggle_failed') }}');
                }
            } catch (error) {
                console.error('Error toggling app:', error);
                alert('{{ __('super_admin.apps.toggle_failed') }}');
            }
        }
    };
}
</script>
@endpush
