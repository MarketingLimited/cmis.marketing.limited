@extends('layouts.admin')

@section('title', __('webhooks.title') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="webhooksManager()">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('webhooks.title') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('webhooks.title') }}</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                {{ __('webhooks.subtitle') }}
            </p>
        </div>
        <a href="{{ route('orgs.settings.webhooks.create', $currentOrg) }}"
           class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            <i class="fas fa-plus me-2"></i>{{ __('webhooks.create') }}
        </a>
    </div>

    {{-- Flash Messages --}}
    <template x-if="message">
        <div class="mb-6 rounded-md p-4" :class="messageType === 'success' ? 'bg-green-50' : 'bg-red-50'">
            <div class="flex">
                <i class="fas me-3" :class="messageType === 'success' ? 'fa-check-circle text-green-400' : 'fa-exclamation-circle text-red-400'"></i>
                <p class="text-sm font-medium" :class="messageType === 'success' ? 'text-green-800' : 'text-red-800'" x-text="message"></p>
            </div>
        </div>
    </template>

    @if($webhooks->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($webhooks as $webhook)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $webhook->is_active ? 'bg-green-100' : 'bg-gray-100' }}">
                                    <i class="fas fa-plug {{ $webhook->is_active ? 'text-green-600' : 'text-gray-400' }}"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="text-base font-semibold text-gray-900">{{ $webhook->name }}</h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($webhook->is_verified)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle me-1"></i>{{ __('webhooks.verified') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-exclamation-circle me-1"></i>{{ __('webhooks.unverified') }}
                                            </span>
                                        @endif
                                        @if($webhook->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ __('webhooks.active') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                {{ __('webhooks.inactive') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="p-1 text-gray-400 hover:text-gray-600 rounded">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div x-show="open" @click.away="open = false"
                                     x-transition
                                     class="absolute end-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10">
                                    <div class="py-1">
                                        <a href="{{ route('orgs.settings.webhooks.show', [$currentOrg, $webhook->id]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-eye w-4 me-2"></i>{{ __('webhooks.details_title') }}
                                        </a>
                                        <a href="{{ route('orgs.settings.webhooks.edit', [$currentOrg, $webhook->id]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-edit w-4 me-2"></i>{{ __('webhooks.edit') }}
                                        </a>
                                        @if(!$webhook->is_verified)
                                            <button @click="verifyWebhook('{{ $webhook->id }}')"
                                                    class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-check-double w-4 me-2"></i>{{ __('webhooks.verify') }}
                                            </button>
                                        @endif
                                        @if($webhook->is_verified)
                                            <button @click="testWebhook('{{ $webhook->id }}')"
                                                    class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-paper-plane w-4 me-2"></i>{{ __('webhooks.test') }}
                                            </button>
                                            <button @click="toggleWebhook('{{ $webhook->id }}')"
                                                    class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-{{ $webhook->is_active ? 'pause' : 'play' }} w-4 me-2"></i>{{ $webhook->is_active ? __('webhooks.deactivate') : __('webhooks.activate') }}
                                            </button>
                                        @endif
                                        <button @click="deleteWebhook('{{ $webhook->id }}')"
                                                class="w-full text-start px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <i class="fas fa-trash w-4 me-2"></i>{{ __('webhooks.delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Callback URL (truncated) --}}
                        <div class="text-xs text-gray-500 mb-3 font-mono truncate" title="{{ $webhook->callback_url }}">
                            {{ $webhook->callback_url }}
                        </div>

                        {{-- Platform Filter --}}
                        @if($webhook->platform)
                            <div class="mb-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fab fa-{{ $webhook->platform === 'meta' ? 'facebook' : $webhook->platform }} me-1"></i>
                                    {{ $platforms[$webhook->platform] ?? ucfirst($webhook->platform) }}
                                </span>
                            </div>
                        @endif

                        {{-- Subscribed Events --}}
                        @if($webhook->subscribed_events && count($webhook->subscribed_events) > 0)
                            <div class="flex flex-wrap gap-1 mb-3">
                                @foreach(array_slice($webhook->subscribed_events, 0, 3) as $event)
                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded-full">
                                        {{ $eventTypes[$event] ?? $event }}
                                    </span>
                                @endforeach
                                @if(count($webhook->subscribed_events) > 3)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">
                                        +{{ count($webhook->subscribed_events) - 3 }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <div class="mb-3">
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">
                                    {{ __('webhooks.all_events') }}
                                </span>
                            </div>
                        @endif

                        {{-- Stats --}}
                        <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                            <span title="{{ __('webhooks.success_rate') }}">
                                <i class="fas fa-chart-pie me-1"></i>{{ number_format($webhook->success_rate, 1) }}%
                            </span>
                            <span title="{{ __('webhooks.total_deliveries') }}">
                                <i class="fas fa-paper-plane me-1"></i>{{ $webhook->success_count + $webhook->failure_count }}
                            </span>
                            @if($webhook->last_triggered_at)
                                <span title="{{ __('webhooks.last_triggered') }}">
                                    <i class="fas fa-clock me-1"></i>{{ $webhook->last_triggered_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
                            <a href="{{ route('orgs.settings.webhooks.show', [$currentOrg, $webhook->id]) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                {{ __('webhooks.view_logs') }}
                            </a>
                            <a href="{{ route('orgs.settings.webhooks.edit', [$currentOrg, $webhook->id]) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                {{ __('webhooks.edit') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="w-16 h-16 mx-auto bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-plug text-indigo-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('webhooks.no_webhooks') }}</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                {{ __('webhooks.no_webhooks_description') }}
            </p>
            <a href="{{ route('orgs.settings.webhooks.create', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-plus me-2"></i>{{ __('webhooks.create') }}
            </a>
        </div>
    @endif
</div>

<script>
function webhooksManager() {
    return {
        message: null,
        messageType: 'success',

        async verifyWebhook(webhookId) {
            try {
                const response = await fetch(`{{ route('orgs.settings.webhooks.index', $currentOrg) }}/${webhookId}/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.message = data.message;
                this.messageType = data.success ? 'success' : 'error';
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                this.message = 'An error occurred';
                this.messageType = 'error';
            }
        },

        async testWebhook(webhookId) {
            try {
                const response = await fetch(`{{ route('orgs.settings.webhooks.index', $currentOrg) }}/${webhookId}/test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.message = data.message;
                this.messageType = data.success ? 'success' : 'error';
            } catch (error) {
                this.message = 'An error occurred';
                this.messageType = 'error';
            }
        },

        async toggleWebhook(webhookId) {
            try {
                const response = await fetch(`{{ route('orgs.settings.webhooks.index', $currentOrg) }}/${webhookId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.message = data.message;
                this.messageType = data.success ? 'success' : 'error';
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                this.message = 'An error occurred';
                this.messageType = 'error';
            }
        },

        async deleteWebhook(webhookId) {
            if (!confirm('{{ __('webhooks.confirm_delete') }}')) return;

            try {
                const response = await fetch(`{{ route('orgs.settings.webhooks.index', $currentOrg) }}/${webhookId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.message = data.message;
                this.messageType = data.success ? 'success' : 'error';
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                this.message = 'An error occurred';
                this.messageType = 'error';
            }
        }
    }
}
</script>
@endsection
