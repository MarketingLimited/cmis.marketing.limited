@extends('layouts.admin')

@section('title', __('backup.create_backup'))

@section('content')
<div x-data="backupCreate()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('orgs.backup.index', ['org' => $org]) }}" class="hover:text-primary-600">
                {{ __('backup.dashboard_title') }}
            </a>
            <span class="mx-2">/</span>
            <span>{{ __('backup.create_backup') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('backup.create_backup') }}
        </h1>
    </div>

    <form action="{{ route('orgs.backup.store', ['org' => $org]) }}" method="POST" class="space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('backup.basic_info') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.backup_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required
                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="{{ __('backup.backup_name_placeholder') }}"
                           value="{{ old('name', __('backup.default_backup_name', ['date' => now()->format('Y-m-d')])) }}">
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

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.description') }}
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                              placeholder="{{ __('backup.description_placeholder') }}">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Data Categories -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ __('backup.select_categories') }}
                </h2>
                <button type="button" @click="toggleAllCategories()"
                        class="text-sm text-primary-600 hover:text-primary-700">
                    <span x-text="allSelected ? '{{ __('backup.deselect_all') }}' : '{{ __('backup.select_all') }}'"></span>
                </button>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                {{ __('backup.categories_description') }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($categories as $key => $category)
                    <label class="relative flex items-start p-4 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="categories[]" value="{{ $key }}"
                                   x-model="selectedCategories"
                                   class="h-4 w-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500">
                        </div>
                        <div class="ms-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ __($category['label']) }}
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $category['table_count'] ?? count($category['tables'] ?? []) }} {{ __('backup.tables') }}
                            </p>
                        </div>
                    </label>
                @endforeach
            </div>

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                <i class="fas fa-info-circle me-1"></i>
                {{ __('backup.no_selection_means_all') }}
            </p>
        </div>

        <!-- Data Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('backup.data_summary') }}
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                {{ __('backup.category') }}
                            </th>
                            <th class="px-4 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                {{ __('backup.records') }}
                            </th>
                            <th class="px-4 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                {{ __('backup.tables') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($dataSummary as $key => $summary)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                    {{ __($summary['label'] ?? 'backup.categories.' . $key) }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($summary['record_count'] ?? 0) }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $summary['table_count'] ?? 0 }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Encryption Options -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('backup.security_options') }}
            </h2>

            <label class="flex items-center">
                <input type="checkbox" name="encrypt" value="1" x-model="encrypt"
                       class="h-4 w-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500">
                <span class="ms-3 text-sm text-gray-700 dark:text-gray-300">
                    {{ __('backup.encrypt_backup') }}
                </span>
            </label>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 ms-7">
                {{ __('backup.encrypt_description') }}
            </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('orgs.backup.index', ['org' => $org]) }}"
               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                {{ __('common.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="fas fa-database me-2"></i>
                {{ __('backup.start_backup') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function backupCreate() {
    return {
        selectedCategories: [],
        encrypt: false,

        get allSelected() {
            return this.selectedCategories.length === {{ count($categories) }};
        },

        toggleAllCategories() {
            if (this.allSelected) {
                this.selectedCategories = [];
            } else {
                this.selectedCategories = @json(array_keys($categories->toArray()));
            }
        }
    };
}
</script>
@endpush
@endsection
