@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __($app->name_key) . ' - ' . __('super_admin.apps.title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.apps.index') }}" class="text-red-600 hover:text-red-700">{{ __('super_admin.apps.title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __($app->name_key) }}</span>
@endsection

@section('content')
<div x-data="appManager()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <i class="fas fa-{{ $app->icon ?? 'cube' }} text-2xl text-gray-600 dark:text-gray-400"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __($app->name_key) }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __($app->description_key) }}</p>
                <div class="flex items-center gap-2 mt-2">
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
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 capitalize">
                        {{ __('apps.categories.' . $app->category) }}
                    </span>
                </div>
            </div>
        </div>
        <a href="{{ route('super-admin.apps.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>
            {{ __('super_admin.actions.back') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Plan Access Management -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-key text-gray-400 me-2"></i>
                        {{ __('super_admin.apps.plan_access') }}
                    </h2>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        @foreach($plans as $plan)
                        @php
                            $assignment = $planAssignments[$plan->plan_id] ?? null;
                            $isEnabled = $assignment['is_enabled'] ?? false;
                            $usageCount = $usageByPlan[$plan->plan_id] ?? 0;
                        @endphp
                        <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-700
                                    {{ $isEnabled ? 'bg-green-50 dark:bg-green-900/10' : 'bg-gray-50 dark:bg-gray-700/50' }}">
                            <div class="flex items-center gap-4">
                                <button @click="togglePlan('{{ $plan->plan_id }}')"
                                        class="w-12 h-6 rounded-full transition relative
                                            {{ $isEnabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="absolute top-0.5 {{ $isEnabled ? ($isRtl ? 'start-0.5' : 'end-0.5') : ($isRtl ? 'end-0.5' : 'start-0.5') }} w-5 h-5 bg-white rounded-full shadow transition-all"></span>
                                </button>
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $plan->description ?? __('super_admin.apps.no_description') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-end">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($usageCount) }} {{ __('super_admin.apps.orgs_using') }}
                                </p>
                                @if($assignment && $assignment['usage_limit'])
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('super_admin.apps.usage_limit') }}: {{ number_format($assignment['usage_limit']) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- App Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-info-circle text-gray-400 me-2"></i>
                        {{ __('super_admin.apps.details') }}
                    </h2>
                </div>
                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.slug') }}</label>
                            <p class="font-medium text-gray-900 dark:text-white font-mono">{{ $app->slug }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.route_prefix') }}</label>
                            <p class="font-medium text-gray-900 dark:text-white font-mono">{{ $app->route_prefix ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.sort_order') }}</label>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $app->sort_order }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.status') }}</label>
                            <p class="font-medium {{ $app->is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $app->is_active ? __('super_admin.apps.active') : __('super_admin.apps.inactive') }}
                            </p>
                        </div>
                    </div>

                    @if($app->dependencies && count($app->dependencies) > 0)
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.dependencies') }}</label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($app->dependencies as $dep)
                                <span class="px-2 py-1 text-sm rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ $dep }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($app->required_permissions && count($app->required_permissions) > 0)
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.required_permissions') }}</label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($app->required_permissions as $perm)
                                <span class="px-2 py-1 text-sm rounded bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">
                                    {{ $perm }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Stats Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-chart-bar text-gray-400 me-2"></i>
                        {{ __('super_admin.apps.usage_stats') }}
                    </h2>
                </div>
                <div class="p-4 space-y-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($totalOrgsUsingApp) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.apps.total_orgs') }}</p>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('super_admin.apps.by_plan') }}
                        </h3>
                        @foreach($plans as $plan)
                        @php
                            $count = $usageByPlan[$plan->plan_id] ?? 0;
                            $percentage = $totalOrgsUsingApp > 0 ? ($count / $totalOrgsUsingApp) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ $plan->name }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($count) }}</span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-bolt text-gray-400 me-2"></i>
                        {{ __('super_admin.apps.quick_actions') }}
                    </h2>
                </div>
                <div class="p-4 space-y-2">
                    <button @click="enableForAllPlans()"
                            class="w-full px-4 py-2 text-start text-sm bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition">
                        <i class="fas fa-check-double me-2"></i>
                        {{ __('super_admin.apps.enable_all_plans') }}
                    </button>
                    <button @click="disableForAllPlans()"
                            class="w-full px-4 py-2 text-start text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition">
                        <i class="fas fa-times-circle me-2"></i>
                        {{ __('super_admin.apps.disable_all_plans') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function appManager() {
    return {
        appId: '{{ $app->app_id }}',
        plans: @json($plans->map(fn($p) => ['plan_id' => $p->plan_id, 'name' => $p->name])),

        async togglePlan(planId) {
            try {
                const response = await fetch(`{{ url('super-admin/apps') }}/${this.appId}/toggle/${planId}`, {
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
                console.error('Error:', error);
            }
        },

        async enableForAllPlans() {
            if (!confirm('{{ __('super_admin.apps.confirm_enable_all') }}')) return;

            try {
                const response = await fetch('{{ route('super-admin.apps.bulk-assign') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        app_ids: [this.appId],
                        plan_ids: this.plans.map(p => p.plan_id),
                        action: 'enable'
                    })
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async disableForAllPlans() {
            if (!confirm('{{ __('super_admin.apps.confirm_disable_all') }}')) return;

            try {
                const response = await fetch('{{ route('super-admin.apps.bulk-assign') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        app_ids: [this.appId],
                        plan_ids: this.plans.map(p => p.plan_id),
                        action: 'disable'
                    })
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    };
}
</script>
@endpush
