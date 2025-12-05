@extends('super-admin.layouts.app')

@section('title', $org->name ?? __('super_admin.organization_details'))

@section('breadcrumb')
<span class="text-gray-400">/</span>
<a href="{{ route('super-admin.orgs.index') }}" class="text-gray-500 hover:text-red-600">{{ __('super_admin.organizations') }}</a>
<span class="text-gray-400">/</span>
<span class="text-gray-700 dark:text-gray-300">{{ $org->name ?? __('super_admin.details') }}</span>
@endsection

@section('content')
<div x-data="organizationDetails()" x-init="init()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl">
                {{ substr($org->name ?? 'ORG', 0, 2) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $org->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $org->email ?? __('super_admin.no_email') }}</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        @if($org->status === 'active') bg-green-100 text-green-800
                        @elseif($org->status === 'suspended') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ __('super_admin.status_' . $org->status) }}
                    </span>
                    @if($org->subscription && $org->subscription->plan)
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                        {{ $org->subscription->plan->name }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
            @if($org->status !== 'suspended')
            <button @click="showModal = 'suspend'"
                    class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition">
                <i class="fas fa-pause {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('super_admin.suspend') }}
            </button>
            @endif
            @if($org->status !== 'blocked')
            <button @click="showModal = 'block'"
                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-ban {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('super_admin.block') }}
            </button>
            @endif
            @if($org->status !== 'active')
            <form action="{{ route('super-admin.orgs.restore', $org) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                    <i class="fas fa-play {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('super_admin.restore') }}
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.total_users') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $org->users_count ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.api_calls_this_month') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($apiCallsThisMonth ?? 0) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.storage_used') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $storageUsed ?? '0 MB' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.created') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $org->created_at ? $org->created_at->format('M d, Y') : '-' }}</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'details'"
                        :class="activeTab === 'details' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition">
                    {{ __('super_admin.details') }}
                </button>
                <button @click="activeTab = 'users'"
                        :class="activeTab === 'users' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition">
                    {{ __('super_admin.users') }}
                </button>
                <button @click="activeTab = 'subscription'"
                        :class="activeTab === 'subscription' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition">
                    {{ __('super_admin.subscription') }}
                </button>
                <button @click="activeTab = 'activity'"
                        :class="activeTab === 'activity' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition">
                    {{ __('super_admin.activity') }}
                </button>
            </nav>
        </div>

        <!-- Details Tab -->
        <div x-show="activeTab === 'details'" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.organization_info') }}</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('super_admin.org_id') }}</dt>
                            <dd class="text-gray-900 dark:text-white font-mono text-sm">{{ $org->org_id }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('super_admin.currency') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $org->currency ?? 'USD' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('super_admin.timezone') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $org->timezone ?? 'UTC' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('super_admin.locale') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $org->default_locale ?? 'en' }}</dd>
                        </div>
                    </dl>
                </div>

                @if($org->status === 'suspended' && $org->suspension_reason)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-2">{{ __('super_admin.suspension_details') }}</h3>
                    <p class="text-yellow-700 dark:text-yellow-300 text-sm">{{ $org->suspension_reason }}</p>
                    <p class="text-yellow-600 dark:text-yellow-400 text-xs mt-2">
                        {{ __('super_admin.suspended_at') }}: {{ $org->suspended_at ? $org->suspended_at->format('M d, Y H:i') : '-' }}
                    </p>
                </div>
                @endif

                @if($org->status === 'blocked' && $org->block_reason)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">{{ __('super_admin.block_details') }}</h3>
                    <p class="text-red-700 dark:text-red-300 text-sm">{{ $org->block_reason }}</p>
                    <p class="text-red-600 dark:text-red-400 text-xs mt-2">
                        {{ __('super_admin.blocked_at') }}: {{ $org->blocked_at ? $org->blocked_at->format('M d, Y H:i') : '-' }}
                    </p>
                </div>
                @endif
            </div>
        </div>

        <!-- Users Tab -->
        <div x-show="activeTab === 'users'" class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="pb-3">{{ __('super_admin.user') }}</th>
                            <th class="pb-3">{{ __('super_admin.role') }}</th>
                            <th class="pb-3">{{ __('super_admin.status') }}</th>
                            <th class="pb-3">{{ __('super_admin.last_login') }}</th>
                            <th class="pb-3">{{ __('super_admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($org->users ?? [] as $user)
                        <tr>
                            <td class="py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 text-sm font-medium">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 text-gray-600 dark:text-gray-400">{{ $user->pivot->role ?? 'member' }}</td>
                            <td class="py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if(!$user->is_suspended && !$user->is_blocked) bg-green-100 text-green-800
                                    @elseif($user->is_suspended) bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $user->is_blocked ? __('super_admin.blocked') : ($user->is_suspended ? __('super_admin.suspended') : __('super_admin.active')) }}
                                </span>
                            </td>
                            <td class="py-3 text-gray-600 dark:text-gray-400 text-sm">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '-' }}
                            </td>
                            <td class="py-3">
                                <a href="{{ route('super-admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                    {{ __('super_admin.view') }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">{{ __('super_admin.no_users') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Subscription Tab -->
        <div x-show="activeTab === 'subscription'" class="p-6">
            @if($org->subscription)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.current_subscription') }}</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('super_admin.plan') }}</dt>
                            <dd class="text-gray-900 dark:text-white font-medium">{{ $org->subscription->plan->name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('super_admin.status') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $org->subscription->status }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('super_admin.started_at') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $org->subscription->starts_at ? $org->subscription->starts_at->format('M d, Y') : '-' }}</dd>
                        </div>
                        @if($org->subscription->trial_ends_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('super_admin.trial_ends') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $org->subscription->trial_ends_at->format('M d, Y') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.change_plan') }}</h3>
                    <form action="{{ route('super-admin.orgs.change-plan', $org) }}" method="POST">
                        @csrf
                        <select name="plan_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg mb-4">
                            @foreach($plans ?? [] as $plan)
                            <option value="{{ $plan->plan_id }}" @selected($org->subscription->plan_id === $plan->plan_id)>
                                {{ $plan->name }} - {{ $plan->price_monthly ? '$' . $plan->price_monthly . '/mo' : 'Free' }}
                            </option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            {{ __('super_admin.update_plan') }}
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">{{ __('super_admin.no_subscription') }}</p>
            </div>
            @endif
        </div>

        <!-- Activity Tab -->
        <div x-show="activeTab === 'activity'" class="p-6">
            <div class="space-y-4">
                @forelse($recentActivity ?? [] as $activity)
                <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-history text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-900 dark:text-white">{{ $activity->description ?? $activity->action_type }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $activity->created_at ? $activity->created_at->diffForHumans() : '-' }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-gray-500">
                    {{ __('super_admin.no_activity') }}
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Action Modals -->
    <template x-if="showModal === 'suspend' || showModal === 'block'">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div @click.away="showModal = null" class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"
                    x-text="showModal === 'suspend' ? '{{ __('super_admin.suspend_organization') }}' : '{{ __('super_admin.block_organization') }}'"></h3>

                <form :action="showModal === 'suspend' ? '{{ route('super-admin.orgs.suspend', $org) }}' : '{{ route('super-admin.orgs.block', $org) }}'" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('super_admin.reason') }}</label>
                        <textarea name="reason" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showModal = null" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-white rounded-lg transition"
                                :class="showModal === 'suspend' ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-red-500 hover:bg-red-600'">
                            <span x-text="showModal === 'suspend' ? '{{ __('super_admin.suspend') }}' : '{{ __('super_admin.block') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function organizationDetails() {
    return {
        activeTab: 'details',
        showModal: null,

        init() {
            // Check URL hash for tab
            const hash = window.location.hash.replace('#', '');
            if (['details', 'users', 'subscription', 'activity'].includes(hash)) {
                this.activeTab = hash;
            }
        }
    };
}
</script>
@endpush
