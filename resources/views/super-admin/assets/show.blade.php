@extends('super-admin.layouts.app')

@section('title', $asset->name)

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.assets.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.assets.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ Str::limit($asset->name, 30) }}</span>
@endsection

@section('content')
<div x-data="{ showDeleteModal: false, deleting: false }" class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('super-admin.assets.browse') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition">
            <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-2"></i>
            {{ __('super_admin.assets.back_to_browse') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Preview Section -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="aspect-video bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    @php
                        $isImage = in_array(strtolower($asset->type ?? ''), ['image', 'png', 'jpg', 'jpeg', 'gif', 'webp']);
                        $isVideo = in_array(strtolower($asset->type ?? ''), ['video', 'mp4', 'mov', 'avi']);
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
                        <img src="{{ $asset->url }}" alt="{{ $asset->name }}" class="max-w-full max-h-full object-contain">
                    @elseif($isVideo && $asset->url)
                        <video controls class="max-w-full max-h-full">
                            <source src="{{ $asset->url }}" type="video/mp4">
                            {{ __('super_admin.assets.video_not_supported') }}
                        </video>
                    @else
                        <div class="text-center">
                            <i class="fas {{ $icon }} text-gray-400 text-8xl mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.preview_unavailable') }}</p>
                        </div>
                    @endif
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white break-all">{{ $asset->name }}</h1>
                    @if($asset->url)
                    <a href="{{ $asset->url }}" target="_blank" class="text-sm text-red-600 dark:text-red-400 hover:underline break-all">
                        {{ $asset->url }}
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Details Section -->
        <div class="space-y-6">
            <!-- Asset Details Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.details') }}</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.type') }}</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ strtoupper($asset->type ?? 'Unknown') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.size') }}</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ formatBytes($asset->size) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.usage_count') }}</dt>
                        <dd class="text-sm font-medium {{ $asset->usage_count > 0 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                            {{ $asset->usage_count }}
                            @if($asset->usage_count == 0)
                            <span class="text-xs">({{ __('super_admin.assets.unused') }})</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.created') }}</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($asset->created_at)->format('M d, Y H:i') }}</dd>
                    </div>
                    @if($asset->updated_at)
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.updated') }}</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($asset->updated_at)->format('M d, Y H:i') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Organization Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.organization') }}</h3>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <i class="fas fa-building text-gray-500 dark:text-gray-400"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $asset->org_name }}</p>
                        <a href="{{ route('super-admin.orgs.show', $asset->org_id) }}" class="text-sm text-red-600 dark:text-red-400 hover:underline">
                            {{ __('super_admin.assets.view_organization') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Usage Locations -->
            @if(count($usageLocations) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.usage_locations') }}</h3>
                <div class="space-y-2">
                    @foreach($usageLocations as $location)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ ucwords(str_replace('_', ' ', $location['type'])) }}</span>
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs rounded">
                            {{ $location['count'] }} {{ __('super_admin.assets.references') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Metadata -->
            @if($asset->metadata)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.metadata') }}</h3>
                <pre class="text-xs bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg overflow-x-auto text-gray-600 dark:text-gray-300">{{ json_encode(json_decode($asset->metadata), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            @endif

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.actions') }}</h3>
                <div class="space-y-3">
                    @if($asset->url)
                    <a href="{{ $asset->url }}" target="_blank" download class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-download me-2"></i>{{ __('super_admin.assets.download') }}
                    </a>
                    @endif
                    <button @click="showDeleteModal = true" class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-trash me-2"></i>{{ __('super_admin.assets.delete_asset') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('super_admin.assets.confirm_delete') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    {{ __('super_admin.assets.delete_warning') }}
                    <span class="font-medium text-gray-900 dark:text-white">{{ $asset->name }}</span>
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <form action="{{ route('super-admin.assets.destroy', $asset->asset_id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" :disabled="deleting" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50">
                            {{ __('common.delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
