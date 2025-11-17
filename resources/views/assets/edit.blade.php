@extends('layouts.admin')

@section('title', 'Edit Asset')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{  route('assets.index')  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Assets
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Asset Preview -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Asset Preview</h2>
            
            <div class="mb-4">
                @if($asset->asset_type === 'image')
                    <img src="{{  $asset->file_url ?? $asset->url  }}" alt="{{  $asset->file_name  }}"
                         class="w-full rounded-lg shadow-sm">
                @elseif($asset->asset_type === 'video')
                    <video controls class="w-full rounded-lg shadow-sm">
                        <source src="{{  $asset->file_url ?? $asset->url  }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                @else
                    <div class="w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file text-6xl text-gray-400"></i>
                    </div>
                @endif
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">File Name:</span>
                    <span class="font-medium">{{  $asset->file_name  }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Type:</span>
                    <span class="font-medium uppercase">{{  $asset->asset_type  }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Size:</span>
                    <span class="font-medium">{{  $asset->file_size ?? 'N/A'  }}</span>
                </div>
                @if($asset->width && $asset->height)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Dimensions:</span>
                        <span class="font-medium">{{  $asset->width  }} x {{  $asset->height  }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-500">Uploaded:</span>
                    <span class="font-medium">{{  $asset->created_at?->format('M d, Y') ?? 'N/A'  }}</span>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="{{  $asset->file_url ?? $asset->url  }}" download
                   class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md">
                    <i class="fas fa-download mr-2"></i>
                    Download Original
                </a>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Asset Details</h2>

            <form method="POST" action="{{  route('assets.update', $asset->asset_id ?? $asset->id)  }}" class="space-y-4">
                @csrf
                @method('PUT')

                @if ($errorS->any())
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="text-sm text-red-800">
                            <ul class="list-disc list-inside">
                                @foreach ($errorS->all() as $error)
                                    <li>{{  $error  }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- File Name -->
                <div>
                    <label for="file_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Display Name
                    </label>
                    <input type="text" name="file_name" id="file_name"
                           value="{{  old('file_name', $asset->file_name)  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Alt Text -->
                <div>
                    <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">
                        Description / Alt Text
                    </label>
                    <textarea name="alt_text" id="alt_text" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">{{  old('alt_text', $asset->alt_text)  }}</textarea>
                </div>

                <!-- Campaign Association -->
                <div>
                    <label for="campaign_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Associated Campaign
                    </label>
                    <select name="campaign_id" id="campaign_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">No campaign association</option>
                        @if(isset($campaignS))
                            @foreach($campaignS as $campaign)
                                <option value="{{  $campaign->campaign_id ?? $campaign->id  }}"
                                        {{  old('campaign_id', $asset->campaign_id) == ($campaign->campaign_id ?? $campaign->id) ? 'selected' : ''  }}>
                                    {{  $campaign->name  }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Tags -->
                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
                        Tags (comma-separated)
                    </label>
                    <input type="text" name="tags" id="tags"
                           value="{{  old('tags', is_array($asset->tags ?? null) ? implode(', ', $asset->tags) : $asset->tags)  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Usage Rights -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Usage Rights
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_licensed" value="1" class="form-checkbox"
                                   {{  old('is_licensed', $asset->is_licensed ?? false) ? 'checked' : ''  }}>
                            <span class="ml-2 text-sm text-gray-700">Licensed for commercial use</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_public" value="1" class="form-checkbox"
                                   {{  old('is_public', $asset->is_public ?? false) ? 'checked' : ''  }}>
                            <span class="ml-2 text-sm text-gray-700">Available to all organization members</span>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col space-y-2 pt-4">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                        Update Asset
                    </button>
                    <a href="{{  route('assets.index')  }}"
                       class="w-full text-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                </div>
            </form>

            <!-- Delete Form -->
            <form method="POST" action="{{  route('assets.destroy', $asset->asset_id ?? $asset->id)  }}"
                  onsubmit="return confirm('Are you sure you want to delete this asset? This action cannot be undone.');"
                  class="mt-6 pt-6 border-t border-gray-200">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-700 hover:bg-red-50 rounded-md">
                    <i class="fas fa-trash mr-2"></i>
                    Delete Asset
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
