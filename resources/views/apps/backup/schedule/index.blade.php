@extends('layouts.admin')

@section('title', __('backup.manage_schedules'))

@section('content')
<div x-data="scheduleManager()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('orgs.backup.index', ['org' => $org]) }}" class="hover:text-primary-600">
                    {{ __('backup.dashboard_title') }}
                </a>
                <span class="mx-2">/</span>
                <span>{{ __('backup.schedules') }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('backup.manage_schedules') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('backup.schedules_description') }}
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('orgs.backup.schedule.create', ['org' => $org]) }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="fas fa-plus me-2"></i>
                {{ __('backup.create_schedule') }}
            </a>
        </div>
    </div>

    <!-- Schedules List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        @if($schedules->isEmpty())
            <div class="p-8 text-center">
                <i class="fas fa-clock text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('backup.no_schedules_yet') }}</p>
                <a href="{{ route('orgs.backup.schedule.create', ['org' => $org]) }}"
                   class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    {{ __('backup.create_first_schedule') }}
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.schedule_name') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.frequency') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.next_run') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.last_run') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.status') }}
                            </th>
                            <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('backup.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($schedules as $schedule)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center me-3">
                                            <i class="fas fa-clock text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $schedule->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $schedule->storage_disk }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ __('backup.frequency_' . $schedule->frequency) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $schedule->time }} ({{ $schedule->timezone }})
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($schedule->is_active && $schedule->next_run_at)
                                        <span title="{{ $schedule->next_run_at->format('Y-m-d H:i') }}">
                                            {{ $schedule->next_run_at->diffForHumans() }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($schedule->last_run_at)
                                        <span title="{{ $schedule->last_run_at->format('Y-m-d H:i') }}">
                                            {{ $schedule->last_run_at->diffForHumans() }}
                                        </span>
                                    @else
                                        {{ __('backup.never') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($schedule->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            <i class="fas fa-check me-1"></i>
                                            {{ __('backup.active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            <i class="fas fa-pause me-1"></i>
                                            {{ __('backup.inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-end text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Toggle Active -->
                                        <form action="{{ route('orgs.backup.schedule.toggle', ['org' => $org, 'schedule' => $schedule->id]) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="{{ $schedule->is_active ? 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300' : 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300' }}"
                                                    title="{{ $schedule->is_active ? __('backup.deactivate') : __('backup.activate') }}">
                                                <i class="fas {{ $schedule->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                        </form>

                                        <!-- Edit -->
                                        <a href="{{ route('orgs.backup.schedule.edit', ['org' => $org, 'schedule' => $schedule->id]) }}"
                                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                           title="{{ __('backup.edit') }}">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <!-- Delete -->
                                        <button @click="confirmDelete('{{ $schedule->id }}', '{{ $schedule->name }}')"
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
                {{ $schedules->links() }}
            </div>
        @endif
    </div>

    <!-- Schedule Info Card -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <div class="flex">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 me-3 mt-0.5"></i>
            <div>
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    {{ __('backup.schedule_info_title') }}
                </h4>
                <div class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc list-inside space-y-1">
                        <li>{{ __('backup.schedule_info_1') }}</li>
                        <li>{{ __('backup.schedule_info_2') }}</li>
                        <li>{{ __('backup.schedule_info_3') }}</li>
                    </ul>
                </div>
            </div>
        </div>
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
                    {{ __('backup.confirm_delete_schedule_title') }}
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    {{ __('backup.confirm_delete_schedule_message') }}
                    <strong x-text="deleteScheduleName"></strong>
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
function scheduleManager() {
    return {
        showDeleteModal: false,
        deleteScheduleId: null,
        deleteScheduleName: '',
        deleteUrl: '',

        confirmDelete(id, name) {
            this.deleteScheduleId = id;
            this.deleteScheduleName = name;
            this.deleteUrl = `{{ route('orgs.backup.schedule.index', ['org' => $org]) }}/${id}`;
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection
