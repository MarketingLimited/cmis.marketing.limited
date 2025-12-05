@extends('layouts.app')

@section('title', __('backup.dashboard_title'))

@section('content')
<div x-data="backupDashboard()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('backup.dashboard_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('backup.dashboard_description') }}
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('backup.create', ['org' => $org]) }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="fas fa-plus me-2"></i>
                {{ __('backup.create_backup') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="fas fa-database text-blue-600 dark:text-blue-400"></i>
                </div>
                <div class="ms-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.total_backups') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <i class="fas fa-calendar text-green-600 dark:text-green-400"></i>
                </div>
                <div class="ms-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.this_month') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['this_month'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                    <i class="fas fa-hdd text-purple-600 dark:text-purple-400"></i>
                </div>
                <div class="ms-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.storage_used') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ formatBytes($stats['storage_used']) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div class="ms-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.last_backup') }}</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        @if($stats['last_backup'])
                            {{ $stats['last_backup']->completed_at->diffForHumans() }}
                        @else
                            {{ __('backup.never') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('backup.quick_actions') }}</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('backup.schedule.index', ['org' => $org]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-clock me-2"></i>
                {{ __('backup.manage_schedules') }}
            </a>
            <a href="{{ route('backup.restore.index', ['org' => $org]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-undo me-2"></i>
                {{ __('backup.restore_data') }}
            </a>
            <a href="{{ route('backup.settings', ['org' => $org]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-cog me-2"></i>
                {{ __('backup.settings') }}
            </a>
            <a href="{{ route('backup.logs', ['org' => $org]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-list me-2"></i>
                {{ __('backup.audit_logs') }}
            </a>
        </div>
    </div>

    <!-- Backups List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('backup.recent_backups') }}</h3>
        </div>

        @if($backups->isEmpty())
            <div class="p-8 text-center">
                <i class="fas fa-database text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('backup.no_backups_yet') }}</p>
                <a href="{{ route('backup.create', ['org' => $org]) }}"
                   class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    {{ __('backup.create_first_backup') }}
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.backup_name') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.type') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.status') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.size') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.created_at') }}
                            </th>
                            <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($backups as $backup)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $backup->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $backup->backup_code }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $backup->type === 'manual' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                        {{ $backup->type === 'scheduled' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                        {{ $backup->type === 'pre_restore' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}">
                                        {{ __('backup.type_' . $backup->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @include('apps.backup.partials.status-badge', ['status' => $backup->status])
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $backup->file_size ? formatBytes($backup->file_size) : '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $backup->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-end text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('backup.show', ['org' => $org, 'backup' => $backup->id]) }}"
                                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                                           title="{{ __('backup.view') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($backup->status === 'completed')
                                            <a href="{{ route('backup.download', ['org' => $org, 'backup' => $backup->id]) }}"
                                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                               title="{{ __('backup.download') }}">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="{{ route('backup.restore.analyze', ['org' => $org, 'backup' => $backup->id]) }}"
                                               class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                               title="{{ __('backup.restore') }}">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                        @endif
                                        <button @click="confirmDelete('{{ $backup->id }}', '{{ $backup->backup_code }}')"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="{{ __('backup.delete') }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $backups->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('backup.confirm_delete_title') }}
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    {{ __('backup.confirm_delete_message') }}
                    <strong x-text="deleteBackupCode"></strong>
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        {{ __('common.cancel') }}
                    </button>
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            {{ __('backup.delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function backupDashboard() {
    return {
        showDeleteModal: false,
        deleteBackupId: null,
        deleteBackupCode: '',
        deleteUrl: '',

        confirmDelete(id, code) {
            this.deleteBackupId = id;
            this.deleteBackupCode = code;
            this.deleteUrl = `{{ route('backup.index', ['org' => $org]) }}/${id}`;
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection
