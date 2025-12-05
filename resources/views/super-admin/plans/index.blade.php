@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.plans.title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.plans.title') }}</span>
@endsection

@section('content')
<div x-data="plansManager()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.plans.title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.plans.subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.plans.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
            <i class="fas fa-plus"></i>
            {{ __('super_admin.plans.create_plan') }}
        </a>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($plans as $plan)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden
                    {{ !$plan->is_active ? 'opacity-60' : '' }}">
            <!-- Plan Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700
                        {{ $plan->code === 'enterprise' ? 'bg-gradient-to-r from-purple-500 to-purple-700 text-white' :
                           ($plan->code === 'pro' ? 'bg-gradient-to-r from-blue-500 to-blue-700 text-white' :
                           ($plan->code === 'starter' ? 'bg-gradient-to-r from-green-500 to-green-700 text-white' :
                            'bg-gray-100 dark:bg-gray-700')) }}">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xl font-bold">{{ $plan->name }}</h3>
                    @if(!$plan->is_active)
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-500 text-white">
                            {{ __('super_admin.plans.inactive') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm opacity-90">{{ $plan->description ?? __('super_admin.plans.no_description') }}</p>
            </div>

            <!-- Pricing -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $plan->currency }} {{ number_format($plan->price_monthly, 2) }}
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">/{{ __('super_admin.plans.month') }}</span>
                </div>
                @if($plan->price_yearly)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $plan->currency }} {{ number_format($plan->price_yearly, 2) }}/{{ __('super_admin.plans.year') }}
                    @if($plan->price_monthly > 0)
                    <span class="text-green-600 dark:text-green-400">
                        ({{ __('super_admin.plans.save') }} {{ round(100 - ($plan->price_yearly / ($plan->price_monthly * 12) * 100)) }}%)
                    </span>
                    @endif
                </p>
                @endif
            </div>

            <!-- Limits -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-users w-5 text-gray-400"></i>
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.plans.max_users') }}:</span>
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ $plan->max_users ? number_format($plan->max_users) : __('super_admin.plans.unlimited') }}
                    </span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-building w-5 text-gray-400"></i>
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.plans.max_orgs') }}:</span>
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ $plan->max_orgs ? number_format($plan->max_orgs) : __('super_admin.plans.unlimited') }}
                    </span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-chart-line w-5 text-gray-400"></i>
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.plans.api_calls') }}:</span>
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ $plan->max_api_calls_per_month ? number_format($plan->max_api_calls_per_month) : __('super_admin.plans.unlimited') }}
                    </span>
                </div>
            </div>

            <!-- Features -->
            @if($plan->features && count((array)$plan->features) > 0)
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('super_admin.plans.features') }}</h4>
                <div class="space-y-2">
                    @foreach((array)$plan->features as $feature => $enabled)
                    <div class="flex items-center gap-2 text-sm">
                        @if($enabled)
                            <i class="fas fa-check text-green-500"></i>
                        @else
                            <i class="fas fa-times text-red-500"></i>
                        @endif
                        <span class="{{ $enabled ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 line-through' }}">
                            {{ __(str_replace('_', ' ', ucfirst($feature))) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Stats -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.plans.subscribers') }}</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $plan->subscriptions_count ?? 0 }}</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="p-4 flex items-center justify-end gap-2">
                <a href="{{ route('super-admin.plans.edit', $plan->plan_id) }}"
                   class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                   title="{{ __('super_admin.actions.edit') }}">
                    <i class="fas fa-edit"></i>
                </a>
                <button @click="togglePlanStatus('{{ $plan->plan_id }}', {{ $plan->is_active ? 'false' : 'true' }})"
                        class="p-2 rounded-lg transition {{ $plan->is_active ? 'text-gray-600 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20' : 'text-gray-600 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' }}"
                        title="{{ $plan->is_active ? __('super_admin.actions.deactivate') : __('super_admin.actions.activate') }}">
                    <i class="fas {{ $plan->is_active ? 'fa-toggle-on text-green-600' : 'fa-toggle-off' }}"></i>
                </button>
                @if(($plan->subscriptions_count ?? 0) === 0)
                <button @click="deletePlan('{{ $plan->plan_id }}')"
                        class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                        title="{{ __('super_admin.actions.delete') }}">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <i class="fas fa-tags text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('super_admin.plans.no_plans') }}</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('super_admin.plans.no_plans_description') }}</p>
                <a href="{{ route('super-admin.plans.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    <i class="fas fa-plus"></i>
                    {{ __('super_admin.plans.create_first_plan') }}
                </a>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
function plansManager() {
    return {
        async togglePlanStatus(planId, activate) {
            const confirmMessage = activate
                ? '{{ __('super_admin.plans.activate_confirm') }}'
                : '{{ __('super_admin.plans.deactivate_confirm') }}';

            if (!confirm(confirmMessage)) return;

            try {
                const response = await fetch(`{{ url('super-admin/plans') }}/${planId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        is_active: activate
                    })
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error toggling plan status:', error);
            }
        },

        async deletePlan(planId) {
            if (!confirm('{{ __('super_admin.plans.delete_confirm') }}')) return;

            try {
                const response = await fetch(`{{ url('super-admin/plans') }}/${planId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error deleting plan:', error);
            }
        }
    };
}
</script>
@endpush
