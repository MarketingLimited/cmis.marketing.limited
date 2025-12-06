@extends('super-admin.layouts.app')

@section('title', __('super_admin.assets.title'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.assets.title') }}</span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.assets.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('super_admin.assets.description') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('super-admin.assets.browse') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-th-large me-2"></i>{{ __('super_admin.assets.browse') }}
            </a>
            <a href="{{ route('super-admin.assets.cleanup') }}" class="inline-flex items-center px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                <i class="fas fa-broom me-2"></i>{{ __('super_admin.assets.cleanup') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.total_assets') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_assets']) }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-images text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Storage -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.total_storage') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatBytes($stats['total_storage']) }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fas fa-database text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Organizations with Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.orgs_with_assets') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['organizations_with_assets']) }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <i class="fas fa-building text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Unused Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.unused_assets') }}</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ number_format($stats['unused_assets']) }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Assets by Type -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.by_type') }}</h3>
            @if($assetsByType->count() > 0)
            <div class="space-y-3">
                @foreach($assetsByType as $type)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-200 dark:bg-gray-600 rounded">
                            @php
                                $icon = match(strtolower($type->type ?? 'file')) {
                                    'image', 'png', 'jpg', 'jpeg', 'gif', 'webp' => 'fa-image',
                                    'video', 'mp4', 'mov', 'avi' => 'fa-video',
                                    'audio', 'mp3', 'wav' => 'fa-music',
                                    'pdf' => 'fa-file-pdf',
                                    'doc', 'docx' => 'fa-file-word',
                                    default => 'fa-file'
                                };
                            @endphp
                            <i class="fas {{ $icon }} text-gray-600 dark:text-gray-300"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($type->type ?? 'Unknown') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($type->count) }} {{ __('super_admin.assets.files') }}</p>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatBytes($type->total_size) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_assets') }}</p>
            </div>
            @endif
        </div>

        <!-- Storage by Organization -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.storage_by_org') }}</h3>
                <a href="{{ route('super-admin.assets.storage') }}" class="text-sm text-red-600 dark:text-red-400 hover:underline">
                    {{ __('common.view_all') }}
                </a>
            </div>
            @if($storageByOrg->count() > 0)
            <div class="space-y-3">
                @php $maxStorage = $storageByOrg->max('total_size') ?: 1; @endphp
                @foreach($storageByOrg as $org)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $org->org_name }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatBytes($org->total_size) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: {{ ($org->total_size / $maxStorage) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_data') }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.recent_uploads') }}</h3>
                <a href="{{ route('super-admin.assets.browse') }}" class="text-sm text-red-600 dark:text-red-400 hover:underline">
                    {{ __('common.view_all') }}
                </a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentAssets as $asset)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0">
                            @php
                                $icon = match(strtolower($asset->type ?? 'file')) {
                                    'image', 'png', 'jpg', 'jpeg', 'gif', 'webp' => 'fa-image',
                                    'video', 'mp4', 'mov', 'avi' => 'fa-video',
                                    'audio', 'mp3', 'wav' => 'fa-music',
                                    'pdf' => 'fa-file-pdf',
                                    default => 'fa-file'
                                };
                            @endphp
                            <i class="fas {{ $icon }} text-gray-500 dark:text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $asset->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $asset->org_name }}</p>
                        </div>
                        <div class="text-end">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatBytes($asset->size) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::parse($asset->created_at)->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center">
                    <i class="fas fa-inbox text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_recent') }}</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Large Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.large_assets') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.larger_than_10mb') }}</p>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($largeAssets as $asset)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file text-red-600 dark:text-red-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $asset->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $asset->org_name }}</p>
                        </div>
                        <div class="text-end">
                            <p class="text-sm font-bold text-red-600 dark:text-red-400">{{ formatBytes($asset->size) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ strtoupper($asset->type ?? 'file') }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center">
                    <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_large_files') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('super-admin.assets.browse') }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition group">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg group-hover:scale-110 transition">
                    <i class="fas fa-search text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.browse_assets') }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.browse_desc') }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('super-admin.assets.storage') }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition group">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg group-hover:scale-110 transition">
                    <i class="fas fa-chart-pie text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.storage_analytics') }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.storage_desc') }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('super-admin.assets.cleanup') }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition group">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg group-hover:scale-110 transition">
                    <i class="fas fa-trash-alt text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.assets.cleanup_tools') }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.cleanup_desc') }}</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
