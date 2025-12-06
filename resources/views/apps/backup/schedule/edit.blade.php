@extends('layouts.admin')

@section('title', __('backup.edit_schedule'))

@section('content')
<div x-data="scheduleEdit()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('orgs.backup.index', ['org' => $org]) }}" class="hover:text-primary-600">
                {{ __('backup.dashboard_title') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('orgs.backup.schedule.index', ['org' => $org]) }}" class="hover:text-primary-600">
                {{ __('backup.schedules') }}
            </a>
            <span class="mx-2">/</span>
            <span>{{ __('backup.edit_schedule') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('backup.edit_schedule') }}
        </h1>
    </div>

    <form action="{{ route('orgs.backup.schedule.update', ['org' => $org, 'schedule' => $schedule->id]) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('backup.basic_info') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.schedule_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required
                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="{{ __('backup.schedule_name_placeholder') }}"
                           value="{{ old('name', $schedule->name) }}">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.storage_location') }}
                    </label>
                    <select name="storage_disk"
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <option value="local" {{ $schedule->storage_disk === 'local' ? 'selected' : '' }}>{{ __('backup.storage_local') }}</option>
                        <option value="google" {{ $schedule->storage_disk === 'google' ? 'selected' : '' }}>{{ __('backup.storage_google') }}</option>
                        <option value="onedrive" {{ $schedule->storage_disk === 'onedrive' ? 'selected' : '' }}>{{ __('backup.storage_onedrive') }}</option>
                        <option value="dropbox" {{ $schedule->storage_disk === 'dropbox' ? 'selected' : '' }}>{{ __('backup.storage_dropbox') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Schedule Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('backup.schedule_settings') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.frequency') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="frequency" x-model="frequency" required
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @foreach($frequencies as $freq)
                            <option value="{{ $freq }}" {{ $schedule->frequency === $freq ? 'selected' : '' }}>
                                {{ __('backup.frequency_' . $freq) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.time') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="time" required
                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           value="{{ old('time', $schedule->time) }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.timezone') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="timezone" required
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}" {{ $schedule->timezone === $tz ? 'selected' : '' }}>
                                {{ $tz }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Day of Week (for weekly) -->
                <div x-show="frequency === 'weekly'" x-transition>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.day_of_week') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="day_of_week"
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @foreach($daysOfWeek as $day => $name)
                            <option value="{{ $day }}" {{ $schedule->day_of_week == $day ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Day of Month (for monthly) -->
                <div x-show="frequency === 'monthly'" x-transition>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.day_of_month') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="day_of_month"
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @for($i = 1; $i <= 31; $i++)
                            <option value="{{ $i }}" {{ $schedule->day_of_month == $i ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('backup.day_of_month_note') }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.retention_days') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="retention_days" required min="1" max="365"
                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           value="{{ old('retention_days', $schedule->retention_days) }}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('backup.retention_days_note') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('backup.status') }}
            </h2>

            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1"
                       {{ $schedule->is_active ? 'checked' : '' }}
                       class="h-4 w-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500">
                <span class="ms-3 text-sm text-gray-700 dark:text-gray-300">
                    {{ __('backup.schedule_active') }}
                </span>
            </label>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 ms-7">
                {{ __('backup.schedule_active_note') }}
            </p>

            @if($schedule->last_run_at)
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-history me-2"></i>
                        {{ __('backup.last_run') }}: {{ $schedule->last_run_at->format('Y-m-d H:i') }}
                        ({{ $schedule->last_run_at->diffForHumans() }})
                    </div>
                    @if($schedule->next_run_at && $schedule->is_active)
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <i class="fas fa-clock me-2"></i>
                            {{ __('backup.next_run') }}: {{ $schedule->next_run_at->format('Y-m-d H:i') }}
                            ({{ $schedule->next_run_at->diffForHumans() }})
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <button type="button"
                    @click="confirmDelete = true"
                    class="px-4 py-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                <i class="fas fa-trash me-2"></i>
                {{ __('backup.delete_schedule') }}
            </button>

            <div class="flex items-center gap-4">
                <a href="{{ route('orgs.backup.schedule.index', ['org' => $org]) }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    <i class="fas fa-save me-2"></i>
                    {{ __('backup.save_changes') }}
                </button>
            </div>
        </div>
    </form>

    <!-- Delete Confirmation Modal -->
    <div x-show="confirmDelete"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="confirmDelete = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('backup.confirm_delete_schedule_title') }}
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    {{ __('backup.confirm_delete_schedule_message') }}
                    <strong>{{ $schedule->name }}</strong>
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="confirmDelete = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        {{ __('common.cancel') }}
                    </button>
                    <form action="{{ route('orgs.backup.schedule.destroy', ['org' => $org, 'schedule' => $schedule->id]) }}"
                          method="POST" class="inline">
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
function scheduleEdit() {
    return {
        frequency: '{{ $schedule->frequency }}',
        confirmDelete: false
    };
}
</script>
@endpush
@endsection
