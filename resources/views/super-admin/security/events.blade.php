@extends('super-admin.layouts.app')

@section('title', __('super_admin.security.security_events'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.security.index') }}" class="text-gray-500 hover:text-red-600">{{ __('super_admin.security.title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.security.security_events') }}</span>
@endsection

@section('content')
<div x-data="securityEvents()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-orange-600 dark:text-orange-400"></i>
                </div>
                {{ __('super_admin.security.security_events') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.security.events_subtitle') }}</p>
        </div>

        <a href="{{ route('super-admin.security.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
            {{ __('super_admin.actions.back') }}
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-list text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.security.total_events') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($eventStats['total']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.security.unresolved') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($eventStats['unresolved']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-radiation text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.security.critical_unresolved') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($eventStats['critical']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-day text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.security.today') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($eventStats['today']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.event_type') }}</label>
                <select name="event_type" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                    <option value="">{{ __('super_admin.security.all_types') }}</option>
                    <option value="login_success" {{ request('event_type') === 'login_success' ? 'selected' : '' }}>{{ __('super_admin.security.login_success') }}</option>
                    <option value="login_failed" {{ request('event_type') === 'login_failed' ? 'selected' : '' }}>{{ __('super_admin.security.login_failed') }}</option>
                    <option value="password_reset" {{ request('event_type') === 'password_reset' ? 'selected' : '' }}>{{ __('super_admin.security.password_reset') }}</option>
                    <option value="suspicious_activity" {{ request('event_type') === 'suspicious_activity' ? 'selected' : '' }}>{{ __('super_admin.security.suspicious_activity') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.severity') }}</label>
                <select name="severity" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                    <option value="">{{ __('super_admin.security.all_severities') }}</option>
                    <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>{{ __('super_admin.security.severity_info') }}</option>
                    <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>{{ __('super_admin.security.severity_warning') }}</option>
                    <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>{{ __('super_admin.security.severity_critical') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.status') }}</label>
                <select name="is_resolved" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                    <option value="">{{ __('super_admin.security.all_statuses') }}</option>
                    <option value="false" {{ request('is_resolved') === 'false' ? 'selected' : '' }}>{{ __('super_admin.security.unresolved') }}</option>
                    <option value="true" {{ request('is_resolved') === 'true' ? 'selected' : '' }}>{{ __('super_admin.security.resolved') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('super_admin.security.search_placeholder') }}" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-search me-2"></i>{{ __('super_admin.common.filter') }}
                </button>
                <a href="{{ route('super-admin.security.events') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Events Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.timestamp') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.event_type') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.severity') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.user') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.ip_address') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.status') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.actions.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($events as $event)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($event->created_at)->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($event->event_type === 'login_success') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($event->event_type === 'login_failed') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($event->event_type === 'password_reset') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                    @else bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                    @endif">
                                    {{ __('super_admin.security.' . $event->event_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($event->severity === 'info') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                    @elseif($event->severity === 'warning') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @endif">
                                    {{ ucfirst($event->severity) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($event->user_name)
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $event->user_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $event->user_email }}</div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 font-mono">
                                {{ $event->ip_address ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($event->is_resolved)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        <i class="fas fa-check-circle"></i>
                                        {{ __('super_admin.security.resolved') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        <i class="fas fa-clock"></i>
                                        {{ __('super_admin.security.pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="showEventDetails({{ json_encode($event) }})" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if(!$event->is_resolved)
                                        <button @click="resolveEvent('{{ $event->event_id }}')" class="p-2 text-green-400 hover:text-green-600 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-shield-alt text-4xl mb-2 opacity-50"></i>
                                <p>{{ __('super_admin.security.no_events') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($events->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $events->links() }}
            </div>
        @endif
    </div>

    <!-- Event Details Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full mx-auto shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.security.event_details') }}</h3>
                    <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div x-html="modalContent" class="text-start"></div>
            </div>
        </div>
    </div>

    <!-- Resolve Event Modal -->
    <div x-show="showResolveModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-black/50" @click="showResolveModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl max-w-md w-full mx-auto shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.security.resolve_event') }}</h3>
                    <button @click="showResolveModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form @submit.prevent="submitResolve">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.resolution_notes') }}</label>
                        <textarea x-model="resolveNotes" rows="3" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm" placeholder="{{ __('super_admin.security.resolution_notes_placeholder') }}"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="showResolveModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                            {{ __('super_admin.common.cancel') }}
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" :disabled="resolving">
                            <span x-show="!resolving">{{ __('super_admin.security.mark_resolved') }}</span>
                            <span x-show="resolving"><i class="fas fa-spinner fa-spin me-2"></i>{{ __('super_admin.common.processing') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function securityEvents() {
    return {
        showModal: false,
        modalContent: '',
        showResolveModal: false,
        resolveEventId: null,
        resolveNotes: '',
        resolving: false,

        showEventDetails(event) {
            let details = '';
            if (event.details && Object.keys(event.details).length > 0) {
                details = `
                    <div class="mt-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ __('super_admin.security.additional_details') }}</h4>
                        <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-3 rounded overflow-x-auto">${JSON.stringify(event.details, null, 2)}</pre>
                    </div>
                `;
            }

            let resolution = '';
            if (event.is_resolved) {
                resolution = `
                    <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <h4 class="font-medium text-green-800 dark:text-green-300 mb-2">{{ __('super_admin.security.resolution_info') }}</h4>
                        <p class="text-sm text-green-700 dark:text-green-400">${event.resolution_notes || '{{ __('super_admin.security.no_notes') }}'}</p>
                        <p class="text-xs text-green-600 dark:text-green-500 mt-2">{{ __('super_admin.security.resolved_at') }}: ${event.resolved_at || '-'}</p>
                    </div>
                `;
            }

            this.modalContent = `
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.event_type') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${event.event_type}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.severity') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${event.severity}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.user') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${event.user_name || '-'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.ip_address') }}</p>
                            <p class="text-sm font-mono text-gray-900 dark:text-white">${event.ip_address || '-'}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.user_agent') }}</p>
                        <p class="text-sm text-gray-900 dark:text-white break-all">${event.user_agent || '-'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.location') }}</p>
                        <p class="text-sm text-gray-900 dark:text-white">${event.location_city ? event.location_city + ', ' + event.location_country : '-'}</p>
                    </div>
                    ${details}
                    ${resolution}
                </div>
            `;
            this.showModal = true;
        },

        resolveEvent(eventId) {
            this.resolveEventId = eventId;
            this.resolveNotes = '';
            this.showResolveModal = true;
        },

        async submitResolve() {
            this.resolving = true;
            try {
                const response = await fetch(`{{ url('super-admin/security/events') }}/${this.resolveEventId}/resolve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ resolution_notes: this.resolveNotes })
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('{{ __('super_admin.messages.error_occurred') }}');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('{{ __('super_admin.messages.error_occurred') }}');
            } finally {
                this.resolving = false;
            }
        }
    };
}
</script>
@endpush
