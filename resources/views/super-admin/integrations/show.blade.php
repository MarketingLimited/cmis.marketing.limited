@extends('super-admin.layouts.app')

@section('title', $connection->account_name ?? __('super_admin.integrations.connection_details'))
@section('breadcrumb')
    <a href="{{ route('super-admin.integrations.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">{{ __('super_admin.integrations.title') }}</a>
    <span class="mx-2 text-gray-400">/</span>
    <span>{{ $connection->account_name ?? ucfirst($connection->platform) }}</span>
@endsection

@section('content')
<div x-data="connectionDetails()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $connection->account_name ?? ucfirst($connection->platform) }}</h1>
                @switch($connection->status)
                    @case('active')
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                            {{ __('super_admin.integrations.status_active') }}
                        </span>
                        @break
                    @case('pending')
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                            {{ __('super_admin.integrations.status_pending') }}
                        </span>
                        @break
                    @case('error')
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                            {{ __('super_admin.integrations.status_error') }}
                        </span>
                        @break
                @endswitch
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ ucfirst($connection->platform) }} · {{ $connection->org_name }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="forceRefresh()"
                    :disabled="refreshing"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg disabled:opacity-50 transition-colors">
                <svg class="w-4 h-4 me-2" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ __('super_admin.integrations.force_refresh') }}
            </button>
            <button @click="showDisconnectModal = true"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                {{ __('super_admin.integrations.disconnect') }}
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Connection Details -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.connection_details') }}</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.connection_id') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $connection->connection_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.platform') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ $connection->platform }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.account_id') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $connection->external_account_id ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.organization') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                <a href="{{ route('super-admin.orgs.show', $connection->org_id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                                    {{ $connection->org_name }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.created') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($connection->created_at)->format('M d, Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.last_sync') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $connection->last_sync_at ? \Carbon\Carbon::parse($connection->last_sync_at)->diffForHumans() : '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Token Status -->
            @if($connection->token_expires_at)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.token_status') }}</h2>
                </div>
                <div class="p-6">
                    @php
                        $tokenExpires = \Carbon\Carbon::parse($connection->token_expires_at);
                        $isExpired = $tokenExpires->isPast();
                        $expiresInDays = now()->diffInDays($tokenExpires, false);
                    @endphp
                    <div class="flex items-center gap-4">
                        @if($isExpired)
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                {{ __('super_admin.integrations.token_expired') }}
                            </span>
                        @elseif($expiresInDays < 7)
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                                {{ __('super_admin.integrations.token_expiring_soon') }}
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                {{ __('super_admin.integrations.token_valid') }}
                            </span>
                        @endif
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $isExpired ? __('super_admin.integrations.expired') : __('super_admin.integrations.expires') }}
                            {{ $tokenExpires->diffForHumans() }}
                        </span>
                    </div>
                    @if($connection->last_refreshed_at)
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('super_admin.integrations.last_refreshed') }}: {{ \Carbon\Carbon::parse($connection->last_refreshed_at)->diffForHumans() }}
                        @if($connection->refresh_attempts > 0)
                            · {{ $connection->refresh_attempts }} {{ __('super_admin.integrations.refresh_attempts') }}
                        @endif
                    </p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Sync History -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.sync_history') }}</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                    @forelse($syncHistory as $sync)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @switch($sync->status)
                                @case('success')
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    @break
                                @case('error')
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    @break
                                @case('in_progress')
                                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                    @break
                                @default
                                    <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                            @endswitch
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                    {{ $sync->sync_type ?? 'sync' }}
                                    <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $sync->status }}</span>
                                </p>
                                @if($sync->records_synced)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($sync->records_synced) }} {{ __('super_admin.integrations.records') }}
                                    @if($sync->duration_ms)
                                    · {{ number_format($sync->duration_ms) }}ms
                                    @endif
                                </p>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($sync->created_at)->diffForHumans() }}
                        </span>
                    </div>
                    @empty
                    <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        {{ __('super_admin.integrations.no_sync_history') }}
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Metrics -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.metrics_7d') }}</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.total_syncs') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($metrics['sync_stats']->total_syncs ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.success_rate') }}</span>
                        <span class="font-medium text-green-600 dark:text-green-400">{{ $metrics['success_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.avg_duration') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($metrics['sync_stats']->avg_duration_ms ?? 0) }}ms</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.records_synced') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($metrics['sync_stats']->total_records_synced ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Ad Accounts -->
            @if($adAccounts->count() > 0)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.ad_accounts') }}</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($adAccounts as $account)
                    <div class="px-6 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $account->name ?? $account->external_id }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $account->external_id }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Webhook Activity -->
            @if($webhookActivity->count() > 0)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.recent_webhooks') }}</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-64 overflow-y-auto">
                    @foreach($webhookActivity->take(5) as $webhook)
                    <div class="px-6 py-3">
                        <div class="flex justify-between items-start">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $webhook->event_type ?? 'unknown' }}</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($webhook->received_at)->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Disconnect Modal -->
    <div x-show="showDisconnectModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" @click="showDisconnectModal = false"></div>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.disconnect_confirm_title') }}</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.disconnect_confirm_message') }}</p>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                    <button @click="showDisconnectModal = false"
                            class="px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500">
                        {{ __('super_admin.actions.cancel') }}
                    </button>
                    <button @click="disconnect()"
                            :disabled="disconnecting"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                        <span x-show="!disconnecting">{{ __('super_admin.integrations.disconnect') }}</span>
                        <span x-show="disconnecting">{{ __('super_admin.integrations.disconnecting') }}...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function connectionDetails() {
    return {
        refreshing: false,
        showDisconnectModal: false,
        disconnecting: false,

        async forceRefresh() {
            this.refreshing = true;
            try {
                const response = await fetch('{{ route("super-admin.integrations.refresh", $connection->connection_id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Refresh failed:', error);
            } finally {
                this.refreshing = false;
            }
        },

        async disconnect() {
            this.disconnecting = true;
            try {
                const response = await fetch('{{ route("super-admin.integrations.disconnect", $connection->connection_id) }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = '{{ route("super-admin.integrations.index") }}';
                }
            } catch (error) {
                console.error('Disconnect failed:', error);
            } finally {
                this.disconnecting = false;
            }
        }
    };
}
</script>
@endpush
@endsection
