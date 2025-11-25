@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'Creative Assets')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Creative Assets</h1>
        <a href="{{  route('orgs.assets.upload', ['org' => $currentOrg])  }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
            <i class="fas fa-upload mr-2"></i>
            Upload Assets
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
            {{  session('success')  }}
        </div>
    @endif

    <!-- Filters and Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <i class="fas fa-image text-2xl text-blue-600 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-500">Images</p>
                    <p class="text-xl font-bold">{{  $stats['images'] ?? 0  }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <i class="fas fa-video text-2xl text-red-600 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-500">Videos</p>
                    <p class="text-xl font-bold">{{  $stats['videos'] ?? 0  }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <i class="fas fa-file text-2xl text-green-600 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-500">Documents</p>
                    <p class="text-xl font-bold">{{  $stats['documents'] ?? 0  }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <i class="fas fa-database text-2xl text-purple-600 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-500">Total Size</p>
                    <p class="text-xl font-bold">{{  $stats['total_size'] ?? '0 MB'  }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" action="{{  route('orgs.assets.index', ['org' => $currentOrg])  }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" placeholder="Search assets..."
                       value="{{  request('search')  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="min-w-[150px]">
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Types</option>
                    <option value="image" {{  request('type') === 'image' ? 'selected' : ''  }}>Images</option>
                    <option value="video" {{  request('type') === 'video' ? 'selected' : ''  }}>Videos</option>
                    <option value="document" {{  request('type') === 'document' ? 'selected' : ''  }}>Documents</option>
                    <option value="audio" {{  request('type') === 'audio' ? 'selected' : ''  }}>Audio</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="newest" {{  request('sort', 'newest') === 'newest' ? 'selected' : ''  }}>Newest First</option>
                    <option value="oldest" {{  request('sort') === 'oldest' ? 'selected' : ''  }}>Oldest First</option>
                    <option value="name" {{  request('sort') === 'name' ? 'selected' : ''  }}>Name A-Z</option>
                    <option value="size" {{  request('sort') === 'size' ? 'selected' : ''  }}>Size</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </form>
    </div>

    <!-- Assets Gallery -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($assetS ?? [] as $asset)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden group">
                <!-- Asset Preview -->
                <div class="relative h-48 bg-gray-200">
                    @if($asset->asset_type === 'image')
                        <img src="{{  $asset->file_url ?? $asset->url  }}" alt="{{  $asset->file_name  }}"
                             class="w-full h-full object-cover">
                    @elseif($asset->asset_type === 'video')
                        <div class="w-full h-full flex items-center justify-center bg-gray-800">
                            <i class="fas fa-play-circle text-6xl text-white opacity-75"></i>
                        </div>
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="fas fa-file text-6xl text-gray-400"></i>
                        </div>
                    @endif

                    <!-- Hover Overlay -->
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <div class="flex space-x-2">
                            <a href="{{  $asset->file_url ?? $asset->url  }}" target="_blank"
                               class="p-2 bg-white rounded-full text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{  route('orgs.assets.edit', ['org' => $currentOrg, 'asset' => $asset->asset_id ?? $asset->id])  }}"
                               class="p-2 bg-white rounded-full text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{  $asset->file_url ?? $asset->url  }}" download
                               class="p-2 bg-white rounded-full text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Asset Info -->
                <div class="p-3">
                    <h3 class="font-medium text-sm text-gray-900 truncate" title="{{  $asset->file_name  }}">
                        {{  $asset->file_name  }}
                    </h3>
                    <div class="flex justify-between items-center mt-2 text-xs text-gray-500">
                        <span class="uppercase">{{  $asset->asset_type  }}</span>
                        <span>{{  $asset->file_size ?? '0 KB'  }}</span>
                    </div>
                    @if($asset->dimensions)
                        <p class="text-xs text-gray-500 mt-1">{{  $asset->dimensions  }}</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-images text-5xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No assets yet</h3>
                <p class="text-gray-600 mb-4">Upload your first creative assets to get started</p>
                <a href="{{  route('orgs.assets.upload', ['org' => $currentOrg])  }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                    <i class="fas fa-upload mr-2"></i>
                    Upload Assets
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($assetS) && method_exists($assetS, 'links'))
        <div class="mt-6">
            {{  $assetS->links()  }}
        </div>
    @endif
</div>
@endsection
