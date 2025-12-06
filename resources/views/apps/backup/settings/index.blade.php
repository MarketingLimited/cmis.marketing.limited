@extends('layouts.admin')

@section('title', __('backup.settings'))

@section('content')
<div x-data="backupSettings()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('orgs.backup.index', ['org' => $org]) }}" class="hover:text-primary-600">
                {{ __('backup.dashboard_title') }}
            </a>
            <span class="mx-2">/</span>
            <span>{{ __('backup.settings') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('backup.settings') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('backup.settings_description') }}
        </p>
    </div>

    <form action="{{ route('orgs.backup.settings.update', ['org' => $org]) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Notification Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                <i class="fas fa-bell me-2 text-blue-500"></i>
                {{ __('backup.notification_settings') }}
            </h2>

            <div class="space-y-4">
                <!-- Backup Notifications -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('backup.backup_notifications') }}
                    </h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="email_on_backup_complete" value="1"
                                   {{ $settings->email_on_backup_complete ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500">
                            <span class="ms-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('backup.email_on_backup_complete') }}
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="email_on_backup_failed" value="1"
                                   {{ $settings->email_on_backup_failed ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500">
                            <span class="ms-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('backup.email_on_backup_failed') }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Restore Notifications -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('backup.restore_notifications') }}
                    </h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="email_on_restore_complete" value="1"
                                   {{ $settings->email_on_restore_complete ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500">
                            <span class="ms-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('backup.email_on_restore_complete') }}
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="email_on_restore_failed" value="1"
                                   {{ $settings->email_on_restore_failed ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500">
                            <span class="ms-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('backup.email_on_restore_failed') }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Recipients -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('backup.notification_recipients') }}
                    </h3>
                    <label class="flex items-center mb-4">
                        <input type="checkbox" name="notify_all_admins" value="1"
                               {{ $settings->notify_all_admins ? 'checked' : '' }}
                               class="h-4 w-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500">
                        <span class="ms-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ __('backup.notify_all_admins') }}
                        </span>
                    </label>

                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">
                            {{ __('backup.additional_emails') }}
                        </label>
                        <div class="space-y-2" x-data="{ emails: @json($settings->notification_emails ?? []) }">
                            <template x-for="(email, index) in emails" :key="index">
                                <div class="flex gap-2">
                                    <input type="email" :name="'notification_emails[' + index + ']'"
                                           x-model="emails[index]"
                                           class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm"
                                           placeholder="{{ __('backup.email_placeholder') }}">
                                    <button type="button" @click="emails.splice(index, 1)"
                                            class="px-3 py-2 text-red-600 hover:text-red-800 dark:text-red-400">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="emails.push('')"
                                    class="text-sm text-primary-600 hover:text-primary-700">
                                <i class="fas fa-plus me-1"></i>
                                {{ __('backup.add_email') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                <i class="fas fa-hdd me-2 text-purple-500"></i>
                {{ __('backup.storage_settings') }}
            </h2>

            <div class="space-y-6">
                <!-- Default Storage -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.default_storage') }}
                    </label>
                    <select name="default_storage_disk" x-model="selectedDisk"
                            class="w-full md:w-1/2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @foreach($storageDisks as $key => $label)
                            <option value="{{ $key }}" {{ $settings->default_storage_disk === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Storage Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Local Storage -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                         :class="{ 'ring-2 ring-primary-500': selectedDisk === 'local' }">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <i class="fas fa-server text-gray-500 me-2"></i>
                                <span class="font-medium text-gray-900 dark:text-white">{{ __('backup.storage_local') }}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ __('backup.connected') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('backup.storage_local_description') }}
                        </p>
                    </div>

                    <!-- Google Drive -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                         :class="{ 'ring-2 ring-primary-500': selectedDisk === 'google' }">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <i class="fab fa-google-drive text-blue-500 me-2"></i>
                                <span class="font-medium text-gray-900 dark:text-white">{{ __('backup.storage_google') }}</span>
                            </div>
                            @if(isset($settings->storage_credentials['google']))
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('backup.configured') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    {{ __('backup.not_configured') }}
                                </span>
                            @endif
                        </div>
                        <div x-show="selectedDisk === 'google'" x-transition class="space-y-3 mt-4">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Client ID</label>
                                <input type="text" name="google_client_id"
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="xxxxx.apps.googleusercontent.com">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Client Secret</label>
                                <input type="password" name="google_client_secret"
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="{{ __('backup.enter_if_changing') }}">
                            </div>
                        </div>
                    </div>

                    <!-- OneDrive -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                         :class="{ 'ring-2 ring-primary-500': selectedDisk === 'onedrive' }">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <i class="fab fa-microsoft text-blue-600 me-2"></i>
                                <span class="font-medium text-gray-900 dark:text-white">{{ __('backup.storage_onedrive') }}</span>
                            </div>
                            @if(isset($settings->storage_credentials['onedrive']))
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('backup.configured') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    {{ __('backup.not_configured') }}
                                </span>
                            @endif
                        </div>
                        <div x-show="selectedDisk === 'onedrive'" x-transition class="space-y-3 mt-4">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Client ID</label>
                                <input type="text" name="onedrive_client_id"
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Client Secret</label>
                                <input type="password" name="onedrive_client_secret"
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="{{ __('backup.enter_if_changing') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Dropbox -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                         :class="{ 'ring-2 ring-primary-500': selectedDisk === 'dropbox' }">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <i class="fab fa-dropbox text-blue-500 me-2"></i>
                                <span class="font-medium text-gray-900 dark:text-white">{{ __('backup.storage_dropbox') }}</span>
                            </div>
                            @if(isset($settings->storage_credentials['dropbox']))
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('backup.configured') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    {{ __('backup.not_configured') }}
                                </span>
                            @endif
                        </div>
                        <div x-show="selectedDisk === 'dropbox'" x-transition class="space-y-3 mt-4">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Access Token</label>
                                <input type="password" name="dropbox_token"
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="{{ __('backup.enter_if_changing') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('orgs.backup.index', ['org' => $org]) }}"
               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                {{ __('common.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="fas fa-save me-2"></i>
                {{ __('backup.save_settings') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function backupSettings() {
    return {
        selectedDisk: '{{ $settings->default_storage_disk ?? 'local' }}',
    };
}
</script>
@endpush
@endsection
