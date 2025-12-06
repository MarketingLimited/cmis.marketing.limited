@extends('super-admin.layouts.app')

@section('title', __('super_admin.assets.cleanup'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.assets.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.assets.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.assets.cleanup') }}</span>
@endsection

@section('content')
<div x-data="cleanupManager()" class="space-y-6">
    <!-- Page Header -->
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
            <div>
                <h3 class="font-semibold text-yellow-800 dark:text-yellow-300">{{ __('super_admin.assets.cleanup_warning_title') }}</h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-400">{{ __('super_admin.assets.cleanup_warning_desc') }}</p>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.unused_assets') }}</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $unusedAssets->count() }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('super_admin.assets.older_than_30_days') }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <i class="fas fa-box-open text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.potential_savings') }}</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ formatBytes($potentialSavings) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('super_admin.assets.if_cleaned') }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fas fa-coins text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.deleted_pending_purge') }}</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $deletedAssets->count() }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('super_admin.assets.soft_deleted') }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <i class="fas fa-trash text-red-600 dark:text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Unused Assets Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.unused_assets') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.unused_30_days_desc') }}</p>
            </div>
            @if($unusedAssets->count() > 0)
            <button @click="showBulkDeleteModal = true" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                <i class="fas fa-broom me-2"></i>{{ __('super_admin.assets.bulk_delete_unused') }}
            </button>
            @endif
        </div>
        <div class="overflow-x-auto">
            @if($unusedAssets->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.name') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.organization') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.type') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.size') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.created') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($unusedAssets as $asset)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                    <i class="fas fa-file text-gray-400"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $asset->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $asset->org_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ strtoupper($asset->type ?? '-') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatBytes($asset->size) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($asset->created_at)->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <button @click="deleteAsset('{{ $asset->asset_id }}')" class="text-red-600 dark:text-red-400 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-8 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_unused_assets') }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Potential Duplicates -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.potential_duplicates') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.duplicates_desc') }}</p>
        </div>
        <div class="overflow-x-auto">
            @if($potentialDuplicates->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.file_name') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.organization') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.copies') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.wasted_space') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($potentialDuplicates as $dup)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $dup->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $dup->org_name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 text-xs rounded">
                                {{ $dup->duplicate_count }} {{ __('super_admin.assets.copies') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-red-600 dark:text-red-400">{{ formatBytes($dup->wasted_space - $dup->size) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-8 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_duplicates') }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Soft-Deleted Assets (Pending Purge) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.deleted_assets') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.deleted_desc') }}</p>
            </div>
            @if($deletedAssets->count() > 0)
            <button @click="showPurgeModal = true" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-fire me-2"></i>{{ __('super_admin.assets.purge_all') }}
            </button>
            @endif
        </div>
        <div class="overflow-x-auto">
            @if($deletedAssets->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.name') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.organization') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.size') }}</th>
                        <th class="text-start px-4 py-3">{{ __('super_admin.assets.deleted_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($deletedAssets as $asset)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 opacity-60">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white truncate max-w-xs">{{ $asset->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $asset->org_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatBytes($asset->size) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($asset->deleted_at)->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-8 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_deleted_assets') }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Bulk Delete Unused Modal -->
    <div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showBulkDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('super_admin.assets.bulk_delete_confirm') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('super_admin.assets.bulk_delete_warning') }}</p>
                <div class="flex justify-end gap-3">
                    <button @click="showBulkDeleteModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="bulkDeleteUnused()" :disabled="processing" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition disabled:opacity-50">
                        <span x-show="!processing">{{ __('super_admin.assets.delete_unused') }}</span>
                        <span x-show="processing"><i class="fas fa-spinner fa-spin me-2"></i>{{ __('common.processing') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Purge Modal -->
    <div x-show="showPurgeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showPurgeModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.purge_confirm') }}</h3>
                </div>
                <p class="text-gray-500 dark:text-gray-400 mb-4 text-center">{{ __('super_admin.assets.purge_warning') }}</p>
                <div class="flex justify-center gap-3">
                    <button @click="showPurgeModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="purgeDeleted()" :disabled="processing" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50">
                        <span x-show="!processing">{{ __('super_admin.assets.purge_permanently') }}</span>
                        <span x-show="processing"><i class="fas fa-spinner fa-spin me-2"></i>{{ __('common.processing') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cleanupManager() {
    return {
        showBulkDeleteModal: false,
        showPurgeModal: false,
        processing: false,

        async deleteAsset(assetId) {
            if (!confirm('{{ __("super_admin.assets.confirm_delete_single") }}')) return;

            try {
                const response = await fetch(`/super-admin/assets/${assetId}`, {
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
                    alert(data.message);
                }
            } catch (e) {
                alert('Error deleting asset');
            }
        },

        async bulkDeleteUnused() {
            this.processing = true;
            try {
                const response = await fetch('/super-admin/assets/bulk-delete-unused', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ confirm: 'yes', older_than: 30 })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Error performing bulk delete');
            } finally {
                this.processing = false;
                this.showBulkDeleteModal = false;
            }
        },

        async purgeDeleted() {
            this.processing = true;
            try {
                const response = await fetch('/super-admin/assets/purge', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ confirm: 'yes', older_than: 30 })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Error purging assets');
            } finally {
                this.processing = false;
                this.showPurgeModal = false;
            }
        }
    };
}
</script>
@endpush
