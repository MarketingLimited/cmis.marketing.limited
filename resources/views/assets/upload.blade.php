@extends('layouts.admin')

@section('title', 'Upload Assets')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{  route('assets.index')  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Assets
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Upload Creative Assets</h1>

        <form method="POST" action="{{  route('assets.store')  }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

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

            <!-- File Upload Area -->
            <div x-data="{ dragging: false }" class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Upload Files <span class="text-red-500">*</span>
                </label>
                
                <div @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     :class="dragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'"
                     class="border-2 border-dashed rounded-lg p-8 text-center transition-colors">
                    <input type="file" name="files[]" multiple accept="image/*,video/*,.pdf,.doc,.docx"
                           x-ref="fileInput"
                           @change="$refs.fileList.textContent = Array.from($event.target.files).map(f => f.name).join(', ')"
                           class="hidden" id="fileInput">
                    
                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                    <p class="text-lg text-gray-700 mb-2">Drag and drop files here, or</p>
                    <button type="button" 
                            @click="$refs.fileInput.click()"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md">
                        Browse Files
                    </button>
                    <p class="text-sm text-gray-500 mt-4">
                        Supported: Images (JPG, PNG, GIF), Videos (MP4, MOV), Documents (PDF, DOC)
                    </p>
                    <p class="text-xs text-gray-500 mt-2">Maximum file size: 50MB per file</p>
                    
                    <div x-ref="fileList" class="mt-4 text-sm text-gray-600"></div>
                </div>
            </div>

            <!-- Asset Type -->
            <div>
                <label for="asset_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Asset Type <span class="text-red-500">*</span>
                </label>
                <select name="asset_type" id="asset_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select asset type</option>
                    <option value="image" {{  old('asset_type') === 'image' ? 'selected' : ''  }}>Image</option>
                    <option value="video" {{  old('asset_type') === 'video' ? 'selected' : ''  }}>Video</option>
                    <option value="document" {{  old('asset_type') === 'document' ? 'selected' : ''  }}>Document</option>
                    <option value="audio" {{  old('asset_type') === 'audio' ? 'selected' : ''  }}>Audio</option>
                </select>
            </div>

            <!-- Alt Text / Description -->
            <div>
                <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">
                    Description / Alt Text
                </label>
                <textarea name="alt_text" id="alt_text" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Describe the asset for accessibility and SEO">{{  old('alt_text')  }}</textarea>
            </div>

            <!-- Campaign Association -->
            <div>
                <label for="campaign_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Associate with Campaign
                </label>
                <select name="campaign_id" id="campaign_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">No campaign association</option>
                    @if(isset($campaignS))
                        @foreach($campaignS as $campaign)
                            <option value="{{  $campaign->campaign_id ?? $campaign->id  }}"
                                    {{  old('campaign_id') == ($campaign->campaign_id ?? $campaign->id) ? 'selected' : ''  }}>
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
                       value="{{  old('tags')  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="e.g., product, hero-image, social-media">
                <p class="mt-1 text-sm text-gray-500">Use tags to organize and find assets easily</p>
            </div>

            <!-- Usage Rights -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Usage Rights
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_licensed" value="1" class="form-checkbox">
                        <span class="ml-2 text-sm text-gray-700">This asset is properly licensed for commercial use</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_public" value="1" class="form-checkbox">
                        <span class="ml-2 text-sm text-gray-700">Make this asset available to all organization members</span>
                    </label>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="{{  route('assets.index')  }}"
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                    <i class="fas fa-upload mr-2"></i>
                    Upload Assets
                </button>
            </div>
        </form>
    </div>

    <!-- Upload Tips -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-blue-900 mb-2">
            <i class="fas fa-lightbulb mr-2"></i>Upload Tips
        </h3>
        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
            <li>For best results, use high-resolution images (at least 1920x1080 for hero images)</li>
            <li>Videos should be in MP4 format with H.264 codec for maximum compatibility</li>
            <li>Add descriptive alt text for accessibility and SEO optimization</li>
            <li>Use consistent naming conventions and tags for easy asset management</li>
            <li>Ensure you have proper rights and licenses for all uploaded assets</li>
        </ul>
    </div>
</div>
@endsection
