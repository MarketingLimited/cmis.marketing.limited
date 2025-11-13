@props([
    'name' => 'file',
    'accept' => null,
    'multiple' => false,
    'maxSize' => '10MB',
    'label' => 'رفع ملف',
    'description' => null,
    'preview' => true
])

<div x-data="fileUpload()" class="w-full">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
        @if ($description)
        <span class="text-xs text-gray-500 font-normal block mt-1">{{ $description }}</span>
        @endif
    </label>

    <div class="relative">
        <input type="file"
               name="{{ $name }}"
               @if($accept) accept="{{ $accept }}" @endif
               @if($multiple) multiple @endif
               @change="handleFileSelect($event)"
               class="hidden"
               x-ref="fileInput"
               {{ $attributes }}>

        <div @click="$refs.fileInput.click()"
             @dragover.prevent="dragOver = true"
             @dragleave.prevent="dragOver = false"
             @drop.prevent="handleDrop($event)"
             :class="dragOver ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 bg-white'"
             class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition hover:border-indigo-400">

            <template x-if="!files.length">
                <div>
                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                    <p class="text-sm text-gray-600 mb-1">اضغط للرفع أو اسحب الملفات هنا</p>
                    <p class="text-xs text-gray-500">
                        الحد الأقصى: {{ $maxSize }}
                        @if ($accept)
                        | الأنواع المسموحة: {{ $accept }}
                        @endif
                    </p>
                </div>
            </template>

            <template x-if="files.length">
                <div class="space-y-2">
                    <template x-for="(file, index) in files" :key="index">
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                            <div class="flex items-center gap-3 flex-1">
                                <template x-if="file.type.startsWith('image/') && preview">
                                    <img :src="file.preview" class="w-12 h-12 object-cover rounded">
                                </template>
                                <template x-if="!file.type.startsWith('image/')">
                                    <i class="fas fa-file text-2xl text-gray-400"></i>
                                </template>
                                <div class="text-right flex-1">
                                    <p class="text-sm font-medium text-gray-900" x-text="file.name"></p>
                                    <p class="text-xs text-gray-500" x-text="formatFileSize(file.size)"></p>
                                </div>
                            </div>
                            <button type="button"
                                    @click.stop="removeFile(index)"
                                    class="text-red-600 hover:text-red-700 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </template>

                    <button type="button"
                            @click.stop="$refs.fileInput.click()"
                            class="w-full text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        <i class="fas fa-plus ml-1"></i>
                        إضافة ملفات أخرى
                    </button>
                </div>
            </template>
        </div>

        <template x-if="error">
            <p class="text-sm text-red-600 mt-2" x-text="error"></p>
        </template>
    </div>
</div>

@push('scripts')
<script>
function fileUpload() {
    return {
        files: [],
        dragOver: false,
        error: null,
        maxSize: '{{ $maxSize }}',

        handleFileSelect(event) {
            const selectedFiles = Array.from(event.target.files);
            this.processFiles(selectedFiles);
        },

        handleDrop(event) {
            this.dragOver = false;
            const droppedFiles = Array.from(event.dataTransfer.files);
            this.processFiles(droppedFiles);
        },

        processFiles(newFiles) {
            this.error = null;

            @if(!$multiple)
            this.files = [];
            newFiles = newFiles.slice(0, 1);
            @endif

            newFiles.forEach(file => {
                // Validate file size
                const maxSizeBytes = this.parseSize(this.maxSize);
                if (file.size > maxSizeBytes) {
                    this.error = `حجم الملف ${file.name} يتجاوز الحد الأقصى المسموح (${this.maxSize})`;
                    return;
                }

                // Create preview for images
                if (file.type.startsWith('image/') && '{{ $preview }}') {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        file.preview = e.target.result;
                        this.files.push(file);
                    };
                    reader.readAsDataURL(file);
                } else {
                    this.files.push(file);
                }
            });
        },

        removeFile(index) {
            this.files.splice(index, 1);
            this.$refs.fileInput.value = '';
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        parseSize(size) {
            const units = { 'KB': 1024, 'MB': 1024 * 1024, 'GB': 1024 * 1024 * 1024 };
            const match = size.match(/^(\d+)(KB|MB|GB)$/i);
            if (match) {
                return parseInt(match[1]) * units[match[2].toUpperCase()];
            }
            return parseInt(size);
        }
    };
}
</script>
@endpush
