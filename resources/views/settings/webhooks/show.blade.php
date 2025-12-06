@extends('layouts.admin')

@section('title', $webhook->name . ' - ' . __('webhooks.title'))

@section('content')
<div class="space-y-6" x-data="webhookDetails()">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.webhooks.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('webhooks.title') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $webhook->name }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center">
            <div class="w-14 h-14 rounded-lg flex items-center justify-center {{ $webhook->is_active ? 'bg-green-100' : 'bg-gray-100' }}">
                <i class="fas fa-plug text-2xl {{ $webhook->is_active ? 'text-green-600' : 'text-gray-400' }}"></i>
            </div>
            <div class="ms-4">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $webhook->name }}</h1>
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
        <div class="flex items-center gap-2 flex-wrap">
            @if(!$webhook->is_verified)
                <button @click="verifyWebhook"
                        class="inline-flex items-center px-4 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-white hover:bg-green-50">
                    <i class="fas fa-check-double me-2"></i>{{ __('webhooks.verify') }}
                </button>
            @else
                <button @click="testWebhook"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-paper-plane me-2"></i>{{ __('webhooks.test') }}
                </button>
                <button @click="toggleWebhook"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-{{ $webhook->is_active ? 'pause' : 'play' }} me-2"></i>{{ $webhook->is_active ? __('webhooks.deactivate') : __('webhooks.activate') }}
                </button>
            @endif
            <a href="{{ route('orgs.settings.webhooks.edit', [$currentOrg, $webhook->id]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-edit me-2"></i>{{ __('webhooks.edit') }}
            </a>
            <button @click="deleteWebhook"
                    class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                <i class="fas fa-trash me-2"></i>{{ __('webhooks.delete') }}
            </button>
        </div>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Callback URL & Credentials --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">{{ __('webhooks.callback_url') }}</h3>
                <div class="bg-gray-50 p-3 rounded font-mono text-sm break-all">
                    {{ $webhook->callback_url }}
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('webhooks.verify_token') }}</label>
                        <div class="flex">
                            <input type="text" readonly :type="showVerifyToken ? 'text' : 'password'"
                                   value="{{ $webhook->verify_token }}"
                                   class="block w-full rounded-s-md border-gray-300 bg-gray-50 font-mono text-sm">
                            <button type="button" @click="showVerifyToken = !showVerifyToken"
                                    class="px-3 border border-s-0 border-gray-300 bg-gray-50 text-gray-500 hover:text-gray-700">
                                <i class="fas" :class="showVerifyToken ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                            <button type="button" @click="copyToClipboard('{{ $webhook->verify_token }}')"
                                    class="px-3 border border-s-0 border-gray-300 rounded-e-md bg-gray-50 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('webhooks.verify_token_help') }}</p>
                        <button type="button" @click="regenerateVerifyToken"
                                class="mt-2 text-xs text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-sync-alt me-1"></i>{{ __('webhooks.regenerate_token') }}
                        </button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('webhooks.secret_key') }}</label>
                        <div class="flex">
                            <input type="text" readonly :type="showSecretKey ? 'text' : 'password'"
                                   value="{{ $webhook->secret_key }}"
                                   class="block w-full rounded-s-md border-gray-300 bg-gray-50 font-mono text-sm">
                            <button type="button" @click="showSecretKey = !showSecretKey"
                                    class="px-3 border border-s-0 border-gray-300 bg-gray-50 text-gray-500 hover:text-gray-700">
                                <i class="fas" :class="showSecretKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                            <button type="button" @click="copyToClipboard('{{ $webhook->secret_key }}')"
                                    class="px-3 border border-s-0 border-gray-300 rounded-e-md bg-gray-50 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('webhooks.secret_key_help') }}</p>
                        <button type="button" @click="regenerateSecretKey"
                                class="mt-2 text-xs text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-sync-alt me-1"></i>{{ __('webhooks.regenerate_secret') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Signature Verification Instructions --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">{{ __('webhooks.signature_verification') }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ __('webhooks.signature_instructions') }}</p>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-medium">1</span>
                        <p class="ms-3 text-gray-600">{{ __('webhooks.signature_step1') }}</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-medium">2</span>
                        <p class="ms-3 text-gray-600">{{ __('webhooks.signature_step2') }}</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-medium">3</span>
                        <p class="ms-3 text-gray-600">{{ __('webhooks.signature_step3') }}</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-medium">4</span>
                        <p class="ms-3 text-gray-600">{{ __('webhooks.signature_step4') }}</p>
                    </div>
                </div>
            </div>

            {{-- Delivery Logs --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-medium text-gray-900">{{ __('webhooks.delivery_logs') }}</h3>
                    <select x-model="logFilter" @change="filterLogs" class="text-sm border-gray-300 rounded-md">
                        <option value="">All</option>
                        <option value="success">{{ __('webhooks.success') }}</option>
                        <option value="failed">{{ __('webhooks.failed') }}</option>
                        <option value="pending">{{ __('webhooks.pending') }}</option>
                    </select>
                </div>
                @if($recentLogs->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($recentLogs as $log)
                            <div class="px-6 py-4" x-data="{ expanded: false }">
                                <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                                    <div class="flex items-center gap-4">
                                        @if($log->status === 'success')
                                            <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class="fas fa-check text-green-600 text-sm"></i>
                                            </span>
                                        @elseif($log->status === 'failed')
                                            <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                                <i class="fas fa-times text-red-600 text-sm"></i>
                                            </span>
                                        @else
                                            <span class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                                <i class="fas fa-clock text-yellow-600 text-sm"></i>
                                            </span>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $eventTypes[$log->event_type] ?? $log->event_type }}</div>
                                            <div class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        @if($log->response_time_ms)
                                            <span class="text-xs text-gray-500">{{ $log->response_time_ms }}ms</span>
                                        @endif
                                        @if($log->response_status)
                                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $log->response_status >= 200 && $log->response_status < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                HTTP {{ $log->response_status }}
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-400">{{ __('webhooks.attempt') }} {{ $log->attempt_number }}</span>
                                        <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="expanded ? 'rotate-180' : ''"></i>
                                    </div>
                                </div>
                                <div x-show="expanded" x-collapse class="mt-4 space-y-3">
                                    @if($log->error_message)
                                        <div>
                                            <span class="text-xs font-medium text-red-700">{{ __('webhooks.error_message') }}:</span>
                                            <p class="text-sm text-red-600 mt-1">{{ $log->error_message }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <span class="text-xs font-medium text-gray-700">{{ __('webhooks.payload') }}:</span>
                                        <pre class="mt-1 text-xs bg-gray-50 p-3 rounded overflow-x-auto max-h-40">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                    @if($log->response_body)
                                        <div>
                                            <span class="text-xs font-medium text-gray-700">{{ __('webhooks.response') }}:</span>
                                            <pre class="mt-1 text-xs bg-gray-50 p-3 rounded overflow-x-auto max-h-40">{{ $log->response_body }}</pre>
                                        </div>
                                    @endif
                                    @if($log->canRetry())
                                        <button @click.stop="retryDelivery('{{ $log->id }}')"
                                                class="text-xs text-indigo-600 hover:text-indigo-800">
                                            <i class="fas fa-redo me-1"></i>{{ __('webhooks.retry') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
                        <p class="text-sm text-gray-500">{{ __('webhooks.no_logs') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Statistics --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">{{ __('webhooks.statistics') }}</h3>
                <dl class="space-y-4">
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-500">{{ __('webhooks.total_deliveries') }}</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $webhook->success_count + $webhook->failure_count }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-500">{{ __('webhooks.success_rate') }}</dt>
                        <dd class="text-lg font-semibold {{ $webhook->success_rate >= 90 ? 'text-green-600' : ($webhook->success_rate >= 70 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($webhook->success_rate, 1) }}%
                        </dd>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <dt class="text-sm text-gray-500 mb-1">{{ __('webhooks.last_triggered') }}</dt>
                        <dd class="text-sm font-medium text-gray-900">
                            {{ $webhook->last_triggered_at ? $webhook->last_triggered_at->diffForHumans() : '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 mb-1">{{ __('webhooks.last_success') }}</dt>
                        <dd class="text-sm font-medium text-green-600">
                            {{ $webhook->last_success_at ? $webhook->last_success_at->diffForHumans() : '-' }}
                        </dd>
                    </div>
                    @if($webhook->last_failure_at)
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('webhooks.last_failure') }}</dt>
                            <dd class="text-sm font-medium text-red-600">
                                {{ $webhook->last_failure_at->diffForHumans() }}
                            </dd>
                        </div>
                        @if($webhook->last_error)
                            <div>
                                <dt class="text-sm text-gray-500 mb-1">{{ __('webhooks.last_error') }}</dt>
                                <dd class="text-xs text-red-600 break-words">
                                    {{ Str::limit($webhook->last_error, 100) }}
                                </dd>
                            </div>
                        @endif
                    @endif
                </dl>
            </div>

            {{-- Settings --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">{{ __('Settings') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('webhooks.platform') }}</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $webhook->platform ? ($platforms[$webhook->platform] ?? ucfirst($webhook->platform)) : __('webhooks.all_platforms') }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('webhooks.timeout') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $webhook->timeout_seconds }}s</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('webhooks.max_retries') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $webhook->max_retries }}</dd>
                    </div>
                    @if($webhook->verified_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('webhooks.verified') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $webhook->verified_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Subscribed Events --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">{{ __('webhooks.subscribed_events') }}</h3>
                @if($webhook->subscribed_events && count($webhook->subscribed_events) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($webhook->subscribed_events as $event)
                            <span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs rounded-full">
                                {{ $eventTypes[$event] ?? $event }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">{{ __('webhooks.all_events') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function webhookDetails() {
    return {
        message: null,
        messageType: 'success',
        showVerifyToken: false,
        showSecretKey: false,
        logFilter: '',

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.message = '{{ __('webhooks.copied') }}';
                this.messageType = 'success';
                setTimeout(() => this.message = null, 2000);
            });
        },

        async verifyWebhook() {
            try {
                const response = await fetch('{{ route('orgs.settings.webhooks.verify', [$currentOrg, $webhook->id]) }}', {
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

        async testWebhook() {
            try {
                const response = await fetch('{{ route('orgs.settings.webhooks.test', [$currentOrg, $webhook->id]) }}', {
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
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                this.message = 'An error occurred';
                this.messageType = 'error';
            }
        },

        async toggleWebhook() {
            try {
                const response = await fetch('{{ route('orgs.settings.webhooks.toggle', [$currentOrg, $webhook->id]) }}', {
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

        async deleteWebhook() {
            if (!confirm('{{ __('webhooks.confirm_delete') }}')) return;

            try {
                const response = await fetch('{{ route('orgs.settings.webhooks.destroy', [$currentOrg, $webhook->id]) }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = '{{ route('orgs.settings.webhooks.index', $currentOrg) }}';
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            } catch (error) {
                this.message = 'An error occurred';
                this.messageType = 'error';
            }
        },

        async regenerateVerifyToken() {
            if (!confirm('{{ __('webhooks.confirm_regenerate_token') }}')) return;

            try {
                const response = await fetch('{{ route('orgs.settings.webhooks.regenerate-token', [$currentOrg, $webhook->id]) }}', {
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

        async regenerateSecretKey() {
            if (!confirm('{{ __('webhooks.confirm_regenerate_secret') }}')) return;

            try {
                const response = await fetch('{{ route('orgs.settings.webhooks.regenerate-secret', [$currentOrg, $webhook->id]) }}', {
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

        async retryDelivery(logId) {
            try {
                const response = await fetch('{{ route('orgs.settings.webhooks.index', $currentOrg) }}/{{ $webhook->id }}/logs/' + logId + '/retry', {
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

        filterLogs() {
            // For now, just reload with filter param - could be made more dynamic
            if (this.logFilter) {
                window.location.href = '{{ route('orgs.settings.webhooks.show', [$currentOrg, $webhook->id]) }}?status=' + this.logFilter;
            } else {
                window.location.href = '{{ route('orgs.settings.webhooks.show', [$currentOrg, $webhook->id]) }}';
            }
        }
    }
}
</script>
@endsection
