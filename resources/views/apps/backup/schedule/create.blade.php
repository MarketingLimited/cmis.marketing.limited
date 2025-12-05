@extends('layouts.app')

@section('title', __('backup.create_schedule'))

@section('content')
<div x-data="scheduleCreate()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('backup.index', ['org' => $org]) }}" class="hover:text-primary-600">
                {{ __('backup.dashboard_title') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('backup.schedule.index', ['org' => $org]) }}" class="hover:text-primary-600">
                {{ __('backup.schedules') }}
            </a>
            <span class="mx-2">/</span>
            <span>{{ __('backup.create_schedule') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('backup.create_schedule') }}
        </h1>
    </div>

    <form action="{{ route('backup.schedule.store', ['org' => $org]) }}" method="POST" class="space-y-6">
        @csrf

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
                           value="{{ old('name') }}">
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
                        <option value="local">{{ __('backup.storage_local') }}</option>
                        <option value="google">{{ __('backup.storage_google') }}</option>
                        <option value="onedrive">{{ __('backup.storage_onedrive') }}</option>
                        <option value="dropbox">{{ __('backup.storage_dropbox') }}</option>
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
                            <option value="{{ $freq }}">{{ __('backup.frequency_' . $freq) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.time') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="time" required
                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           value="{{ old('time', '02:00') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.timezone') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="timezone" required
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}" {{ $tz === 'Asia/Riyadh' ? 'selected' : '' }}>
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
                            <option value="{{ $day }}">{{ $name }}</option>
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
                            <option value="{{ $i }}">{{ $i }}</option>
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
                           value="{{ old('retention_days', 30) }}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('backup.retention_days_note') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('backup.schedule_preview') }}
            </h2>

            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-calendar-alt text-2xl text-primary-600 me-4"></i>
                    <div>
                        <p class="text-sm text-gray-900 dark:text-white font-medium" x-text="scheduleDescription">
                            {{ __('backup.select_frequency') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('backup.schedule_preview_note') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('backup.schedule.index', ['org' => $org]) }}"
               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                {{ __('common.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="fas fa-clock me-2"></i>
                {{ __('backup.create_schedule') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function scheduleCreate() {
    return {
        frequency: 'daily',

        get scheduleDescription() {
            const descriptions = {
                hourly: '{{ __('backup.schedule_desc_hourly') }}',
                daily: '{{ __('backup.schedule_desc_daily') }}',
                weekly: '{{ __('backup.schedule_desc_weekly') }}',
                monthly: '{{ __('backup.schedule_desc_monthly') }}'
            };
            return descriptions[this.frequency] || '{{ __('backup.select_frequency') }}';
        }
    };
}
</script>
@endpush
@endsection
