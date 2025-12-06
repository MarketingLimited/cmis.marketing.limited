@extends('layouts.admin')

@section('title', $backup->name)

@section('content')
<div x-data="backupDetails()" x-init="init()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('orgs.backup.index', ['org' => $org]) }}" class="hover:text-primary-600">
                {{ __('backup.dashboard_title') }}
            </a>
            <span class="mx-2">/</span>
            <span>{{ $backup->backup_code }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $backup->name }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $backup->backup_code }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if($backup->status === 'completed')
                    <a href="{{ route('orgs.backup.download', ['org' => $org, 'backup' => $backup->id]) }}"
                       class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                        <i class="fas fa-download me-2"></i>
                        {{ __('backup.download') }}
                    </a>
                    <a href="{{ route('orgs.backup.restore.analyze', ['org' => $org, 'backup' => $backup->id]) }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-undo me-2"></i>
                        {{ __('backup.restore') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Progress (for processing backups) -->
    @if(in_array($backup->status, ['pending', 'processing']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center me-3">
                    <i class="fas fa-spinner fa-spin text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('backup.backup_in_progress') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="statusMessage">
                        {{ __('backup.processing_' . $backup->status) }}
                    </p>
                </div>
            </div>

            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-primary-600 h-2 rounded-full transition-all duration-500"
                     :style="`width: ${progress}%`"></div>
            </div>
        </div>
    @endif

    <!-- Backup Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('backup.backup_details') }}
                </h3>

                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.status') }}</dt>
                        <dd class="mt-1">
                            @include('apps.backup.partials.status-badge', ['status' => $backup->status])
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ __('backup.type_' . $backup->type) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.created_at') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $backup->created_at->format('Y-m-d H:i') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.completed_at') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $backup->completed_at?->format('Y-m-d H:i') ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.file_size') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $backup->file_size ? formatBytes($backup->file_size) : '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.storage') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ __('backup.storage_' . $backup->storage_disk) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.encrypted') }}</dt>
                        <dd class="mt-1 text-sm">
                            @if($backup->is_encrypted)
                                <span class="text-green-600 dark:text-green-400">
                                    <i class="fas fa-lock me-1"></i> {{ __('common.yes') }}
                                </span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-lock-open me-1"></i> {{ __('common.no') }}
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.expires_at') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $backup->expires_at?->format('Y-m-d') ?? __('backup.never') }}
                        </dd>
                    </div>
                </dl>

                @if($backup->description)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.description') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $backup->description }}
                        </dd>
                    </div>
                @endif

                @if($backup->error_message)
                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-500 me-3 mt-0.5"></i>
                            <div>
                                <h4 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    {{ __('backup.error') }}
                                </h4>
                                <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                    {{ $backup->error_message }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Summary -->
            @if($backup->summary)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('backup.backup_summary') }}
                    </h3>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($backup->summary['total_records'] ?? 0) }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('backup.total_records') }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $backup->summary['total_tables'] ?? 0 }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('backup.total_tables') }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ count($backup->summary['categories'] ?? []) }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('backup.categories_label') }}
                            </p>
                        </div>
                    </div>

                    @if(isset($backup->summary['categories']))
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            {{ __('backup.by_category') }}
                        </h4>
                        <div class="space-y-2">
                            @foreach($backup->summary['categories'] as $key => $category)
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $category['label'] ?? $key }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($category['record_count'] ?? 0) }} {{ __('backup.records') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- File Info -->
            @if($backup->status === 'completed' && $backup->checksum_sha256)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('backup.file_info') }}
                    </h3>

                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                {{ __('backup.checksum') }}
                            </dt>
                            <dd class="mt-1 text-xs font-mono text-gray-900 dark:text-white break-all">
                                {{ $backup->checksum_sha256 }}
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('backup.actions') }}
                </h3>

                <div class="space-y-3">
                    @if($backup->status === 'completed')
                        <a href="{{ route('orgs.backup.download', ['org' => $org, 'backup' => $backup->id]) }}"
                           class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <i class="fas fa-download me-2"></i>
                            {{ __('backup.download') }}
                        </a>
                        <a href="{{ route('orgs.backup.restore.analyze', ['org' => $org, 'backup' => $backup->id]) }}"
                           class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <i class="fas fa-undo me-2"></i>
                            {{ __('backup.restore_from_this') }}
                        </a>
                    @endif

                    <form action="{{ route('orgs.backup.destroy', ['org' => $org, 'backup' => $backup->id]) }}"
                          method="POST"
                          onsubmit="return confirm('{{ __('backup.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full flex items-center justify-center px-4 py-2 border border-red-300 dark:border-red-600 rounded-lg text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                            <i class="fas fa-trash me-2"></i>
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
function backupDetails() {
    return {
        status: '{{ $backup->status }}',
        progress: {{ $backup->status === 'completed' ? 100 : ($backup->status === 'processing' ? 50 : 0) }},
        statusMessage: '{{ __('backup.processing_' . $backup->status) }}',
        pollInterval: null,

        init() {
            if (['pending', 'processing'].includes(this.status)) {
                this.startPolling();
            }
        },

        startPolling() {
            this.pollInterval = setInterval(() => this.checkProgress(), 3000);
        },

        async checkProgress() {
            try {
                const response = await fetch('{{ route('orgs.backup.progress', ['org' => $org, 'backup' => $backup->id]) }}');
                const data = await response.json();

                if (data.data) {
                    this.status = data.data.status;

                    if (data.data.status === 'completed') {
                        this.progress = 100;
                        clearInterval(this.pollInterval);
                        location.reload();
                    } else if (data.data.status === 'failed') {
                        clearInterval(this.pollInterval);
                        location.reload();
                    } else {
                        this.progress = Math.min(90, this.progress + 5);
                    }
                }
            } catch (error) {
                console.error('Failed to check progress:', error);
            }
        }
    };
}
</script>
@endpush
@endsection
