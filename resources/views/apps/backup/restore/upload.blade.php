@extends('layouts.app')

@section('title', __('backup.upload_external'))

@section('content')
<div x-data="uploadBackup()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3 rtl:space-x-reverse">
                <li>
                    <a href="{{ route('backup.index', ['org' => $org]) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        {{ __('backup.backups') }}
                    </a>
                </li>
                <li>
                    <span class="mx-2 text-gray-400">/</span>
                    <a href="{{ route('backup.restore.index', ['org' => $org]) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        {{ __('backup.restore') }}
                    </a>
                </li>
                <li>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 dark:text-white">{{ __('backup.upload') }}</span>
                </li>
            </ol>
        </nav>

        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('backup.upload_external') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('backup.upload_external_description') }}
        </p>
    </div>

    <!-- Upload Form -->
    <div class="max-w-2xl">
        <form action="{{ route('backup.restore.upload.store', ['org' => $org]) }}"
              method="POST"
              enctype="multipart/form-data"
              @submit="handleSubmit"
              class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">

            @csrf

            <div class="p-6">
                <!-- Instructions -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                {{ __('backup.upload_requirements') }}
                            </h3>
                            <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                                <li>{{ __('backup.upload_req_format') }}</li>
                                <li>{{ __('backup.upload_req_size') }}</li>
                                <li>{{ __('backup.upload_req_manifest') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- File Upload Area -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.backup_file') }} <span class="text-red-500">*</span>
                    </label>

                    <div x-ref="dropzone"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleDrop($event)"
                         :class="isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'"
                         class="relative border-2 border-dashed rounded-lg p-8 text-center transition-colors">

                        <template x-if="!file">
                            <div>
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('backup.drag_drop_or') }}
                                    <label class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 cursor-pointer">
                                        {{ __('backup.browse_files') }}
                                        <input type="file"
                                               name="backup_file"
                                               accept=".zip,.enc"
                                               @change="handleFileSelect($event)"
                                               class="hidden">
                                    </label>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                    {{ __('backup.max_file_size', ['size' => '500MB']) }}
                                </p>
                            </div>
                        </template>

                        <template x-if="file">
                            <div class="flex items-center justify-center gap-4">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-900">
                                        <i class="fas fa-file-archive text-green-600 dark:text-green-400 text-xl"></i>
                                    </span>
                                </div>
                                <div class="text-start">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="file.name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatFileSize(file.size)"></p>
                                </div>
                                <button type="button"
                                        @click="removeFile"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    @error('backup_file')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Upload Progress -->
                <div x-show="uploading" class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('backup.uploading') }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="progress + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                             :style="'width: ' + progress + '%'"></div>
                    </div>
                </div>

                <!-- Warning for encrypted files -->
                <div x-show="isEncrypted" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                        </div>
                        <div class="ms-3">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                {{ __('backup.encrypted_file_warning') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 rounded-b-lg flex items-center justify-end gap-3">
                <a href="{{ route('backup.restore.index', ['org' => $org]) }}"
                   class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit"
                        :disabled="!file || uploading"
                        :class="(!file || uploading) ? 'opacity-50 cursor-not-allowed' : ''"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-upload me-2"></i>
                    {{ __('backup.upload_and_analyze') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function uploadBackup() {
    return {
        file: null,
        isDragging: false,
        uploading: false,
        progress: 0,
        isEncrypted: false,

        handleFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                this.setFile(files[0]);
            }
        },

        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.setFile(files[0]);
            }
        },

        setFile(file) {
            // Validate file type
            const validTypes = ['.zip', '.enc'];
            const extension = '.' + file.name.split('.').pop().toLowerCase();

            if (!validTypes.includes(extension)) {
                alert('{{ __("backup.invalid_file_type") }}');
                return;
            }

            // Validate file size (500MB)
            if (file.size > 512 * 1024 * 1024) {
                alert('{{ __("backup.file_too_large") }}');
                return;
            }

            this.file = file;
            this.isEncrypted = extension === '.enc';

            // Update the file input
            const input = this.$refs.dropzone.querySelector('input[type="file"]');
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
        },

        removeFile() {
            this.file = null;
            this.isEncrypted = false;
            const input = this.$refs.dropzone.querySelector('input[type="file"]');
            if (input) {
                input.value = '';
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        handleSubmit(event) {
            if (!this.file) {
                event.preventDefault();
                return;
            }

            this.uploading = true;

            // Simulate progress (actual progress would require XHR)
            const interval = setInterval(() => {
                if (this.progress < 90) {
                    this.progress += Math.random() * 10;
                }
            }, 500);
        }
    };
}
</script>
@endsection
