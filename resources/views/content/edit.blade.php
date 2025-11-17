@extends('layouts.admin')

@section('title', 'Edit Content')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{  route('content.index')  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Content Library
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Content</h1>

        <form method="POST" action="{{  route('content.update', $item->content_id ?? $item->id)  }}" enctype="multipart/form-data" class="space-y-6">
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

            <!-- Content Type -->
            <div>
                <label for="content_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Content Type <span class="text-red-500">*</span>
                </label>
                <select name="content_type" id="content_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select content type</option>
                    <option value="post" {{  old('content_type', $item->content_type) === 'post' ? 'selected' : ''  }}>Social Media Post</option>
                    <option value="ad" {{  old('content_type', $item->content_type) === 'ad' ? 'selected' : ''  }}>Advertisement</option>
                    <option value="email" {{  old('content_type', $item->content_type) === 'email' ? 'selected' : ''  }}>Email Campaign</option>
                    <option value="article" {{  old('content_type', $item->content_type) === 'article' ? 'selected' : ''  }}>Article/Blog</option>
                    <option value="video" {{  old('content_type', $item->content_type) === 'video' ? 'selected' : ''  }}>Video Content</option>
                </select>
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" required
                       value="{{  old('title', $item->title)  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Body/Description -->
            <div>
                <label for="body" class="block text-sm font-medium text-gray-700 mb-2">
                    Content Body <span class="text-red-500">*</span>
                </label>
                <textarea name="body" id="body" rows="8" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">{{  old('body', $item->body)  }}</textarea>
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
                                    {{  old('campaign_id', $item->campaign_id) == ($campaign->campaign_id ?? $campaign->id) ? 'selected' : ''  }}>
                                {{  $campaign->name  }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Platform Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Target Platforms
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="facebook" class="form-checkbox"
                               {{  in_array('facebook', old('platforms', $item->platforms ?? [])) ? 'checked' : ''  }}>
                        <i class="fab fa-facebook text-blue-600"></i>
                        <span>Facebook</span>
                    </label>
                    <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="instagram" class="form-checkbox"
                               {{  in_array('instagram', old('platforms', $item->platforms ?? [])) ? 'checked' : ''  }}>
                        <i class="fab fa-instagram text-pink-600"></i>
                        <span>Instagram</span>
                    </label>
                    <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="twitter" class="form-checkbox"
                               {{  in_array('twitter', old('platforms', $item->platforms ?? [])) ? 'checked' : ''  }}>
                        <i class="fab fa-twitter text-blue-400"></i>
                        <span>Twitter</span>
                    </label>
                    <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="linkedin" class="form-checkbox"
                               {{  in_array('linkedin', old('platforms', $item->platforms ?? [])) ? 'checked' : ''  }}>
                        <i class="fab fa-linkedin text-blue-700"></i>
                        <span>LinkedIn</span>
                    </label>
                </div>
            </div>

            <!-- Current Image -->
            @if($item->thumbnail_url)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                    <img src="{{  $item->thumbnail_url  }}" alt="Current content image"
                         class="w-64 h-40 object-cover rounded-md border border-gray-300">
                </div>
            @endif

            <!-- Image Upload -->
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                    Update Featured Image
                </label>
                <input type="file" name="image" id="image" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <p class="mt-1 text-sm text-gray-500">Leave blank to keep current image</p>
            </div>

            <!-- Status and Schedule -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select name="status" id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="draft" {{  old('status', $item->status) === 'draft' ? 'selected' : ''  }}>Draft</option>
                        <option value="scheduled" {{  old('status', $item->status) === 'scheduled' ? 'selected' : ''  }}>Scheduled</option>
                        <option value="published" {{  old('status', $item->status) === 'published' ? 'selected' : ''  }}>Published</option>
                        <option value="archived" {{  old('status', $item->status) === 'archived' ? 'selected' : ''  }}>Archived</option>
                    </select>
                </div>

                <div>
                    <label for="scheduled_for" class="block text-sm font-medium text-gray-700 mb-2">
                        Schedule For (Optional)
                    </label>
                    <input type="datetime-local" name="scheduled_for" id="scheduled_for"
                           value="{{  old('scheduled_for', $item->scheduled_for?->format('Y-m-d\TH:i'))  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Tags -->
            <div>
                <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
                    Tags (comma-separated)
                </label>
                <input type="text" name="tags" id="tags"
                       value="{{  old('tags', is_array($item->tags ?? null) ? implode(', ', $item->tags) : $item->tags)  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between pt-4">
                <form method="POST" action="{{  route('content.destroy', $item->content_id ?? $item->id)  }}"
                      onsubmit="return confirm('Are you sure you want to delete this content?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-700 hover:bg-red-50 rounded-md">
                        Delete Content
                    </button>
                </form>

                <div class="flex space-x-4">
                    <a href="{{  route('content.index')  }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                        Update Content
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
