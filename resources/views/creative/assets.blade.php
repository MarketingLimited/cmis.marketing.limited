@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'Creative Assets')

@section('content')
<div x-data="creativeAssets()">
    <!-- Page Header -->
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.creative.index', $currentOrg) }}" class="hover:text-blue-600 transition">Creative</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">Assets</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-images text-white"></i>
                    </div>
                    Creative Assets
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your creative assets, images, and media files</p>
            </div>
            <button @click="showUploadModal = true"
                    class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-lg hover:from-pink-700 hover:to-rose-700 shadow-lg shadow-pink-500/25 transition-all">
                <i class="fas fa-upload ml-2"></i>
                Upload Asset
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Assets</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-images text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Approved</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['approved'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pending Review</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['pending'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Draft</p>
                    <p class="text-2xl font-bold text-gray-600 mt-1">{{ $stats['draft'] }}</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center">
                    <i class="fas fa-pencil-alt text-gray-600 dark:text-gray-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Filter by status:</span>
                <a href="{{ route('orgs.creative.assets.index', $currentOrg) }}"
                   class="px-3 py-1.5 text-sm rounded-lg transition {{ !$currentStatus ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                    All
                </a>
                <a href="{{ route('orgs.creative.assets.index', ['org' => $currentOrg, 'status' => 'approved']) }}"
                   class="px-3 py-1.5 text-sm rounded-lg transition {{ $currentStatus === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                    Approved
                </a>
                <a href="{{ route('orgs.creative.assets.index', ['org' => $currentOrg, 'status' => 'pending_review']) }}"
                   class="px-3 py-1.5 text-sm rounded-lg transition {{ $currentStatus === 'pending_review' ? 'bg-yellow-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                    Pending
                </a>
                <a href="{{ route('orgs.creative.assets.index', ['org' => $currentOrg, 'status' => 'draft']) }}"
                   class="px-3 py-1.5 text-sm rounded-lg transition {{ $currentStatus === 'draft' ? 'bg-gray-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                    Draft
                </a>
            </div>
        </div>
    </div>

    <!-- Assets Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Assets</h3>
            <span class="text-sm text-gray-500">{{ $assets->total() }} total</span>
        </div>

        @if($assets->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-images text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No assets found</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Upload your first creative asset to get started</p>
                <button @click="showUploadModal = true"
                        class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    <i class="fas fa-upload ml-2"></i>
                    Upload Asset
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-6">
                @foreach($assets as $asset)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden hover:shadow-lg transition group">
                        <div class="aspect-video bg-gray-100 dark:bg-gray-700 flex items-center justify-center relative">
                            <i class="fas fa-image text-4xl text-gray-400"></i>
                            <div class="absolute top-2 right-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($asset->status === 'approved') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($asset->status === 'pending_review') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @elseif($asset->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $asset->status ?? 'draft')) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <h4 class="font-medium text-gray-900 dark:text-white truncate">
                                {{ $asset->variation_tag ?? 'Untitled Asset' }}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $asset->created_at?->diffForHumans() ?? 'Unknown date' }}
                            </p>
                            <div class="flex items-center gap-2 mt-3 opacity-0 group-hover:opacity-100 transition">
                                <button class="flex-1 px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-eye ml-1"></i> View
                                </button>
                                <button class="flex-1 px-3 py-1.5 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 transition">
                                    <i class="fas fa-edit ml-1"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($assets->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $assets->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Upload Modal -->
    <div x-show="showUploadModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/75 transition-opacity" @click="showUploadModal = false"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl transform transition-all sm:max-w-lg w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Asset</h3>
                    <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6">
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center">
                        <div class="w-16 h-16 bg-pink-100 dark:bg-pink-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-cloud-upload-alt text-2xl text-pink-600"></i>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 font-medium mb-2">Drag and drop files here</p>
                        <p class="text-sm text-gray-500 mb-4">or click to browse</p>
                        <input type="file" class="hidden" id="fileInput" multiple accept="image/*,video/*">
                        <button onclick="document.getElementById('fileInput').click()"
                                class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                            Select Files
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button @click="showUploadModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 transition">
                        Cancel
                    </button>
                    <button class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                        Upload
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function creativeAssets() {
    return {
        showUploadModal: false
    };
}
</script>
@endpush
