@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.subscriptions.title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.subscriptions.title') }}</span>
@endsection

@section('content')
<div x-data="subscriptionsManager()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.subscriptions.title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.subscriptions.subtitle') }}</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.subscriptions.active') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['active'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.subscriptions.trial') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['trial'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.subscriptions.cancelled') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['cancelled'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.subscriptions.mrr') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['mrr'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.300ms="loadSubscriptions()"
                           placeholder="{{ __('super_admin.subscriptions.search_placeholder') }}"
                           class="w-full {{ $isRtl ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <i class="fas fa-search absolute {{ $isRtl ? 'right-3' : 'left-3' }} top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="w-full lg:w-48">
                <select x-model="filters.status"
                        @change="loadSubscriptions()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">{{ __('super_admin.subscriptions.all_statuses') }}</option>
                    <option value="active">{{ __('super_admin.subscriptions.status_active') }}</option>
                    <option value="trial">{{ __('super_admin.subscriptions.status_trial') }}</option>
                    <option value="cancelled">{{ __('super_admin.subscriptions.status_cancelled') }}</option>
                    <option value="expired">{{ __('super_admin.subscriptions.status_expired') }}</option>
                </select>
            </div>

            <!-- Plan Filter -->
            <div class="w-full lg:w-48">
                <select x-model="filters.plan_id"
                        @change="loadSubscriptions()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">{{ __('super_admin.subscriptions.all_plans') }}</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->plan_id }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.subscriptions.organization') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.subscriptions.plan') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.subscriptions.status') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.subscriptions.period') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.subscriptions.amount') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.subscriptions.next_billing') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.subscriptions.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-semibold text-sm">
                                    {{ substr($subscription->org->name ?? 'O', 0, 2) }}
                                </div>
                                <div>
                                    <a href="{{ route('super-admin.orgs.show', $subscription->org_id) }}" class="font-medium text-gray-900 dark:text-white hover:text-red-600">
                                        {{ $subscription->org->name ?? 'Unknown' }}
                                    </a>
                                    <p class="text-sm text-gray-500">{{ $subscription->org->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($subscription->plan)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $subscription->plan->code === 'enterprise' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' :
                                       ($subscription->plan->code === 'pro' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                                       ($subscription->plan->code === 'starter' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300')) }}">
                                    {{ $subscription->plan->name }}
                                </span>
                            @else
                                <span class="text-gray-400">{{ __('super_admin.subscriptions.no_plan') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                   ($subscription->status === 'trial' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                   ($subscription->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300')) }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                            @if($subscription->trial_ends_at && $subscription->status === 'trial')
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ __('super_admin.subscriptions.trial_ends') }}: {{ $subscription->trial_ends_at->format('M j, Y') }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                {{ $subscription->billing_period === 'yearly' ? __('super_admin.subscriptions.yearly') : __('super_admin.subscriptions.monthly') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $subscription->plan?->currency ?? 'USD' }} {{ number_format($subscription->amount ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            @if($subscription->next_billing_date)
                                {{ $subscription->next_billing_date->format('M j, Y') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button @click="openChangePlanModal({{ json_encode($subscription) }})"
                                        class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                                        title="{{ __('super_admin.subscriptions.change_plan') }}">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                                @if($subscription->status === 'active')
                                    <button @click="cancelSubscription('{{ $subscription->subscription_id }}')"
                                            class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                            title="{{ __('super_admin.subscriptions.cancel') }}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @elseif($subscription->status === 'cancelled')
                                    <button @click="reactivateSubscription('{{ $subscription->subscription_id }}')"
                                            class="p-2 text-gray-600 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition"
                                            title="{{ __('super_admin.subscriptions.reactivate') }}">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                @endif
                                @if($subscription->status === 'trial')
                                    <button @click="extendTrial('{{ $subscription->subscription_id }}')"
                                            class="p-2 text-gray-600 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition"
                                            title="{{ __('super_admin.subscriptions.extend_trial') }}">
                                        <i class="fas fa-clock"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-credit-card text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                            <p>{{ __('super_admin.subscriptions.no_subscriptions') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($subscriptions->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $subscriptions->links() }}
        </div>
        @endif
    </div>

    <!-- Change Plan Modal -->
    <div x-show="changePlanModal.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
         x-cloak>
        <div @click.away="changePlanModal.show = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-xl text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.subscriptions.change_plan') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="changePlanModal.subscription?.org?.name"></p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('super_admin.subscriptions.select_plan') }}
                    </label>
                    <select x-model="changePlanModal.newPlanId"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <option value="">{{ __('super_admin.subscriptions.select_plan_placeholder') }}</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->plan_id }}">
                                {{ $plan->name }} - {{ $plan->currency }} {{ number_format($plan->price_monthly, 2) }}/{{ __('super_admin.plans.month') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button @click="changePlanModal.show = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="changePlan()"
                            :disabled="!changePlanModal.newPlanId || changePlanModal.processing"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50">
                        <i x-show="changePlanModal.processing" class="fas fa-spinner fa-spin {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        {{ __('super_admin.subscriptions.change') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function subscriptionsManager() {
    return {
        filters: {
            search: '',
            status: '',
            plan_id: ''
        },
        changePlanModal: {
            show: false,
            subscription: null,
            newPlanId: '',
            processing: false
        },

        loadSubscriptions() {
            const params = new URLSearchParams(this.filters);
            window.location.href = '{{ route('super-admin.subscriptions.index') }}?' + params.toString();
        },

        openChangePlanModal(subscription) {
            this.changePlanModal = {
                show: true,
                subscription: subscription,
                newPlanId: '',
                processing: false
            };
        },

        async changePlan() {
            if (!this.changePlanModal.newPlanId || !this.changePlanModal.subscription) return;

            this.changePlanModal.processing = true;
            try {
                const response = await fetch(`{{ url('super-admin/subscriptions') }}/${this.changePlanModal.subscription.subscription_id}/change-plan`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        plan_id: this.changePlanModal.newPlanId
                    })
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error changing plan:', error);
            } finally {
                this.changePlanModal.processing = false;
            }
        },

        async cancelSubscription(subscriptionId) {
            if (!confirm('{{ __('super_admin.subscriptions.cancel_confirm') }}')) return;

            try {
                const response = await fetch(`{{ url('super-admin/subscriptions') }}/${subscriptionId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error cancelling subscription:', error);
            }
        },

        async reactivateSubscription(subscriptionId) {
            if (!confirm('{{ __('super_admin.subscriptions.reactivate_confirm') }}')) return;

            try {
                const response = await fetch(`{{ url('super-admin/subscriptions') }}/${subscriptionId}/reactivate`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error reactivating subscription:', error);
            }
        },

        async extendTrial(subscriptionId) {
            const days = prompt('{{ __('super_admin.subscriptions.extend_days_prompt') }}', '7');
            if (!days) return;

            try {
                const response = await fetch(`{{ url('super-admin/subscriptions') }}/${subscriptionId}/extend-trial`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        days: parseInt(days)
                    })
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error extending trial:', error);
            }
        }
    };
}
</script>
@endpush
