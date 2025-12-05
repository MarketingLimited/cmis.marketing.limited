@extends('layouts.app')

@section('title', __('backup.audit_logs'))

@section('content')
<div x-data="auditLogViewer()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('backup.index', ['org' => $org]) }}" class="hover:text-primary-600">
                    {{ __('backup.dashboard_title') }}
                </a>
                <span class="mx-2">/</span>
                <span>{{ __('backup.audit_logs') }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('backup.audit_logs') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('backup.audit_logs_description') }}
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-download me-2"></i>
                    {{ __('backup.export') }}
                    <i class="fas fa-chevron-down ms-2 text-xs"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute end-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                    <a href="{{ route('backup.logs.export', ['org' => $org, 'format' => 'csv']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-csv me-2 text-green-500"></i>
                        {{ __('backup.export_csv') }}
                    </a>
                    <a href="{{ route('backup.logs.export', ['org' => $org, 'format' => 'json']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-code me-2 text-blue-500"></i>
                        {{ __('backup.export_json') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <!-- Action Filter -->
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ __('backup.filter_action') }}
                </label>
                <select name="action"
                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">{{ __('backup.all_actions') }}</option>
                    @foreach($actions as $key => $label)
                        <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Entity Type Filter -->
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ __('backup.filter_entity_type') }}
                </label>
                <select name="entity_type"
                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">{{ __('backup.all_entities') }}</option>
                    @foreach($entityTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('entity_type') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Range -->
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ __('backup.from_date') }}
                </label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ __('backup.to_date') }}
                </label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm">
                    <i class="fas fa-filter me-1"></i>
                    {{ __('backup.filter') }}
                </button>
                <a href="{{ route('backup.logs', ['org' => $org]) }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition text-sm">
                    {{ __('backup.clear') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Logs List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        @if($logs->isEmpty())
            <div class="p-8 text-center">
                <i class="fas fa-list text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('backup.no_logs_yet') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.date_time') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.action') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.entity') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.user') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.ip_address') }}
                            </th>
                            <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.details') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $log->created_at->format('Y-m-d') }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $log->created_at->format('H:i:s') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $actionColors = [
                                            'backup_created' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'backup_downloaded' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'backup_deleted' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'restore_started' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'restore_completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'restore_failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'restore_rolled_back' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                            'schedule_created' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                            'schedule_updated' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                            'schedule_deleted' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'settings_updated' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            'external_upload' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                                        ];
                                        $actionIcons = [
                                            'backup_created' => 'fas fa-plus',
                                            'backup_downloaded' => 'fas fa-download',
                                            'backup_deleted' => 'fas fa-trash',
                                            'restore_started' => 'fas fa-play',
                                            'restore_completed' => 'fas fa-check',
                                            'restore_failed' => 'fas fa-times',
                                            'restore_rolled_back' => 'fas fa-undo',
                                            'schedule_created' => 'fas fa-clock',
                                            'schedule_updated' => 'fas fa-edit',
                                            'schedule_deleted' => 'fas fa-trash',
                                            'settings_updated' => 'fas fa-cog',
                                            'external_upload' => 'fas fa-upload',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-800' }}">
                                        <i class="{{ $actionIcons[$log->action] ?? 'fas fa-circle' }} me-1"></i>
                                        {{ $actions[$log->action] ?? $log->action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($log->entity_type && $log->entity_id)
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $entityTypes[$log->entity_type] ?? $log->entity_type }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                            {{ Str::limit($log->entity_id, 8) }}
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($log->user)
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center me-2">
                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                    {{ strtoupper(substr($log->user->name, 0, 2)) }}
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ $log->user->name }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">{{ __('backup.system') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $log->ip_address ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-end">
                                    @if($log->details)
                                        <button @click="showDetails(@js($log->details))"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                            <i class="fas fa-eye me-1"></i>
                                            {{ __('backup.view') }}
                                        </button>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Details Modal -->
    <div x-show="showDetailsModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDetailsModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('backup.log_details') }}
                    </h3>
                    <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 overflow-auto max-h-96">
                    <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="JSON.stringify(selectedDetails, null, 2)"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function auditLogViewer() {
    return {
        showDetailsModal: false,
        selectedDetails: null,

        showDetails(details) {
            this.selectedDetails = details;
            this.showDetailsModal = true;
        }
    };
}
</script>
@endpush
@endsection
