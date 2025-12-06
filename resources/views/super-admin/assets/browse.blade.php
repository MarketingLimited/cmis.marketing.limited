@extends('super-admin.layouts.app')

@section('title', __('super_admin.assets.browse'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.assets.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.assets.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.assets.browse') }}</span>
@endsection

@section('content')
<div x-data="assetBrowser()" class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.assets.browse') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('super_admin.assets.browse_all_desc') }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('super_admin.assets.search_placeholder') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <!-- Organization Filter -->
            <select name="org_id" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">{{ __('super_admin.assets.all_organizations') }}</option>
                @foreach($organizations as $org)
                <option value="{{ $org->org_id }}" {{ request('org_id') == $org->org_id ? 'selected' : '' }}>
                    {{ $org->name }}
                </option>
                @endforeach
            </select>

            <!-- Type Filter -->
            <select name="type" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">{{ __('super_admin.assets.all_types') }}</option>
                @foreach($types as $type)
                <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                    {{ ucfirst($type) }}
                </option>
                @endforeach
            </select>

            <!-- Usage Filter -->
            <select name="usage" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">{{ __('super_admin.assets.all_usage') }}</option>
                <option value="used" {{ request('usage') == 'used' ? 'selected' : '' }}>{{ __('super_admin.assets.in_use') }}</option>
                <option value="unused" {{ request('usage') == 'unused' ? 'selected' : '' }}>{{ __('super_admin.assets.not_in_use') }}</option>
            </select>

            <!-- Search Button -->
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-search me-2"></i>{{ __('common.search') }}
            </button>
        </form>
    </div>

    <!-- Results Count -->
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('super_admin.assets.showing_results', ['count' => $assets->total()]) }}
        </p>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.sort_by') }}:</span>
            <select onchange="window.location.href = this.value" class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'dir' => 'desc']) }}" {{ request('sort', 'created_at') == 'created_at' && request('dir', 'desc') == 'desc' ? 'selected' : '' }}>
                    {{ __('super_admin.assets.newest') }}
                </option>
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'dir' => 'asc']) }}" {{ request('sort') == 'created_at' && request('dir') == 'asc' ? 'selected' : '' }}>
                    {{ __('super_admin.assets.oldest') }}
                </option>
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'size', 'dir' => 'desc']) }}" {{ request('sort') == 'size' && request('dir') == 'desc' ? 'selected' : '' }}>
                    {{ __('super_admin.assets.largest') }}
                </option>
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => 'asc']) }}" {{ request('sort') == 'name' ? 'selected' : '' }}>
                    {{ __('super_admin.assets.name_az') }}
                </option>
            </select>
        </div>
    </div>

    <!-- Asset Grid -->
    @if($assets->count() > 0)
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($assets as $asset)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition group">
            <!-- Preview -->
            <div class="aspect-square bg-gray-100 dark:bg-gray-700 flex items-center justify-center relative">
                @php
                    $isImage = in_array(strtolower($asset->type ?? ''), ['image', 'png', 'jpg', 'jpeg', 'gif', 'webp']);
                    $icon = match(strtolower($asset->type ?? 'file')) {
                        'image', 'png', 'jpg', 'jpeg', 'gif', 'webp' => 'fa-image',
                        'video', 'mp4', 'mov', 'avi' => 'fa-video',
                        'audio', 'mp3', 'wav' => 'fa-music',
                        'pdf' => 'fa-file-pdf',
                        'doc', 'docx' => 'fa-file-word',
                        default => 'fa-file'
                    };
                @endphp
                @if($isImage && $asset->url)
                    <img src="{{ $asset->url }}" alt="{{ $asset->name }}" class="w-full h-full object-cover">
                @else
                    <i class="fas {{ $icon }} text-gray-400 text-4xl"></i>
                @endif

                <!-- Usage Badge -->
                @if($asset->usage_count == 0)
                <span class="absolute top-2 {{ app()->getLocale() === 'ar' ? 'left-2' : 'right-2' }} px-2 py-1 bg-yellow-500 text-white text-xs rounded">
                    {{ __('super_admin.assets.unused') }}
                </span>
                @endif

                <!-- Hover Overlay -->
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                    <a href="{{ route('super-admin.assets.show', $asset->asset_id) }}" class="p-2 bg-white rounded-full hover:bg-gray-100 transition">
                        <i class="fas fa-eye text-gray-700"></i>
                    </a>
                    <button @click="confirmDelete('{{ $asset->asset_id }}', '{{ addslashes($asset->name) }}')" class="p-2 bg-red-500 rounded-full hover:bg-red-600 transition">
                        <i class="fas fa-trash text-white"></i>
                    </button>
                </div>
            </div>

            <!-- Info -->
            <div class="p-3">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $asset->name }}">
                    {{ $asset->name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $asset->org_name }}</p>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-gray-400">{{ formatBytes($asset->size) }}</span>
                    <span class="text-xs text-gray-400">{{ strtoupper($asset->type ?? 'file') }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $assets->links() }}
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <i class="fas fa-folder-open text-gray-300 dark:text-gray-600 text-6xl mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('super_admin.assets.no_assets_found') }}</h3>
        <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.try_different_filters') }}</p>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('super_admin.assets.confirm_delete') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    {{ __('super_admin.assets.delete_warning') }}
                    <span class="font-medium text-gray-900 dark:text-white" x-text="deleteAssetName"></span>
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="deleteAsset()" :disabled="deleting" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50">
                        <span x-show="!deleting">{{ __('common.delete') }}</span>
                        <span x-show="deleting"><i class="fas fa-spinner fa-spin me-2"></i>{{ __('common.deleting') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function assetBrowser() {
    return {
        showDeleteModal: false,
        deleteAssetId: null,
        deleteAssetName: '',
        deleting: false,

        confirmDelete(assetId, assetName) {
            this.deleteAssetId = assetId;
            this.deleteAssetName = assetName;
            this.showDeleteModal = true;
        },

        async deleteAsset() {
            if (!this.deleteAssetId || this.deleting) return;
            this.deleting = true;

            try {
                const response = await fetch(`/super-admin/assets/${this.deleteAssetId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error deleting asset');
                }
            } catch (e) {
                alert('Error deleting asset');
            } finally {
                this.deleting = false;
                this.showDeleteModal = false;
            }
        }
    };
}
</script>
@endpush
