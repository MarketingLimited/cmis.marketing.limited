@extends('super-admin.layouts.app')

@section('title', __('super_admin.security.audit_logs'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.security.index') }}" class="text-gray-500 hover:text-red-600">{{ __('super_admin.security.title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.security.audit_logs') }}</span>
@endsection

@section('content')
<div x-data="auditLogs()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-history text-blue-600 dark:text-blue-400"></i>
                </div>
                {{ __('super_admin.security.audit_logs') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.security.audit_logs_subtitle') }}</p>
        </div>

        <a href="{{ route('super-admin.security.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
            {{ __('super_admin.actions.back') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.action') }}</label>
                <select name="action" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                    <option value="">{{ __('super_admin.security.all_actions') }}</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.entity_type') }}</label>
                <select name="entity_type" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                    <option value="">{{ __('super_admin.security.all_entities') }}</option>
                    @foreach($entityTypes as $type)
                        <option value="{{ $type }}" {{ request('entity_type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.date_from') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.date_to') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-search me-2"></i>{{ __('super_admin.common.filter') }}
                </button>
                <a href="{{ route('super-admin.security.audit-logs') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.timestamp') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.user') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.action') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.entity') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.orgs.organization') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.ip_address') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.actions.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->user_name ?? 'System' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->user_email ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if(str_contains($log->action, 'create')) bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif(str_contains($log->action, 'update')) bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif(str_contains($log->action, 'delete')) bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 dark:text-white">{{ ucfirst($log->entity_type) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ Str::limit($log->entity_id, 8) }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ $log->org_name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 font-mono">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="showLogDetails({{ json_encode($log) }})" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-history text-4xl mb-2 opacity-50"></i>
                                <p>{{ __('super_admin.security.no_audit_logs') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Log Details Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full mx-auto shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.security.log_details') }}</h3>
                    <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div x-html="modalContent" class="text-start"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function auditLogs() {
    return {
        showModal: false,
        modalContent: '',

        showLogDetails(log) {
            let changes = '';
            if (log.old_values || log.new_values) {
                changes = `
                    <div class="mt-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ __('super_admin.security.changes') }}</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('super_admin.security.old_values') }}</p>
                                <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-2 rounded overflow-x-auto">${JSON.stringify(log.old_values, null, 2) || '-'}</pre>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('super_admin.security.new_values') }}</p>
                                <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-2 rounded overflow-x-auto">${JSON.stringify(log.new_values, null, 2) || '-'}</pre>
                            </div>
                        </div>
                    </div>
                `;
            }

            this.modalContent = `
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.action') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${log.action}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.entity_type') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${log.entity_type}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.user') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${log.user_name || 'System'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.ip_address') }}</p>
                            <p class="text-sm font-mono text-gray-900 dark:text-white">${log.ip_address || '-'}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.user_agent') }}</p>
                        <p class="text-sm text-gray-900 dark:text-white break-all">${log.user_agent || '-'}</p>
                    </div>
                    ${changes}
                </div>
            `;
            this.showModal = true;
        }
    };
}
</script>
@endpush
