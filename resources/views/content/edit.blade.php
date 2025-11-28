@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('content.edit_content'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{  route('orgs.content.index', ['org' => $currentOrg])  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left me-2"></i>
            {{ __('content.back_to_library') }}
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('content.edit_content') }}</h1>

        <form method="POST" action="{{  route('orgs.content.update', ['org' => $currentOrg, 'content' => $item->content_id ?? $item->id])  }}" enctype="multipart/form-data" class="space-y-6">
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
                    {{ __('content.content_type') }} <span class="text-red-500">*</span>
                </label>
                <select name="content_type" id="content_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">{{ __('content.select_type') }}</option>
                    <option value="post" {{  old('content_type', $item->content_type) === 'post' ? 'selected' : ''  }}>{{ __('content.type_labels.post') }}</option>
                    <option value="ad" {{  old('content_type', $item->content_type) === 'ad' ? 'selected' : ''  }}>{{ __('content.type_labels.ad') }}</option>
                    <option value="email" {{  old('content_type', $item->content_type) === 'email' ? 'selected' : ''  }}>{{ __('content.type_labels.email') }}</option>
                    <option value="article" {{  old('content_type', $item->content_type) === 'article' ? 'selected' : ''  }}>{{ __('content.type_labels.article') }}</option>
                    <option value="video" {{  old('content_type', $item->content_type) === 'video' ? 'selected' : ''  }}>{{ __('content.type_labels.video') }}</option>
                </select>
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.title') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" required
                       value="{{  old('title', $item->title)  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Body/Description -->
            <div>
                <label for="body" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.body') }} <span class="text-red-500">*</span>
                </label>
                <textarea name="body" id="body" rows="8" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">{{  old('body', $item->body)  }}</textarea>
            </div>

            <!-- Campaign Association -->
            <div>
                <label for="campaign_id" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.campaign_association') }}
                </label>
                <select name="campaign_id" id="campaign_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">{{ __('content.no_campaign') }}</option>
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
                    {{ __('content.target_platforms') }}
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label class="flex items-center gap-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="facebook" class="form-checkbox"
                               {{  in_array('facebook', old('platforms', $item->platforms ?? [])) ? 'checked' : ''  }}>
                        <i class="fab fa-facebook text-blue-600"></i>
                        <span>{{ __('platforms.facebook') }}</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="instagram" class="form-checkbox"
                               {{  in_array('instagram', old('platforms', $item->platforms ?? [])) ? 'checked' : ''  }}>
                        <i class="fab fa-instagram text-pink-600"></i>
                        <span>{{ __('platforms.instagram') }}</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="twitter" class="form-checkbox"
                               {{  in_array('twitter', old('platforms', $item->platforms ?? [])) ? 'checked' : ''  }}>
                        <i class="fab fa-twitter text-blue-400"></i>
                        <span>{{ __('platforms.twitter') }}</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="linkedin" class="form-checkbox"
                               {{  in_array('linkedin', old('platforms', $item->platforms ?? [])) ? 'checked' : ''  }}>
                        <i class="fab fa-linkedin text-blue-700"></i>
                        <span>{{ __('platforms.linkedin') }}</span>
                    </label>
                </div>
            </div>

            <!-- Current Image -->
            @if($item->thumbnail_url)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('content.current_image') }}</label>
                    <img src="{{  $item->thumbnail_url  }}" alt="{{ __('content.current_image') }}"
                         class="w-64 h-40 object-cover rounded-md border border-gray-300">
                </div>
            @endif

            <!-- Image Upload -->
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.update_image') }}
                </label>
                <input type="file" name="image" id="image" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <p class="mt-1 text-sm text-gray-500">{{ __('content.keep_current') }}</p>
            </div>

            <!-- Status and Schedule -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('common.status') }}
                    </label>
                    <select name="status" id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="draft" {{  old('status', $item->status) === 'draft' ? 'selected' : ''  }}>{{ __('content.status.draft') }}</option>
                        <option value="scheduled" {{  old('status', $item->status) === 'scheduled' ? 'selected' : ''  }}>{{ __('content.status.scheduled') }}</option>
                        <option value="published" {{  old('status', $item->status) === 'published' ? 'selected' : ''  }}>{{ __('content.status.published') }}</option>
                        <option value="archived" {{  old('status', $item->status) === 'archived' ? 'selected' : ''  }}>{{ __('content.status.archived') }}</option>
                    </select>
                </div>

                <div>
                    <label for="scheduled_for" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('content.schedule_optional') }}
                    </label>
                    <input type="datetime-local" name="scheduled_for" id="scheduled_for"
                           value="{{  old('scheduled_for', $item->scheduled_for?->format('Y-m-d\TH:i'))  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Tags -->
            <div>
                <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.tag_separator') }}
                </label>
                <input type="text" name="tags" id="tags"
                       value="{{  old('tags', is_array($item->tags ?? null) ? implode(', ', $item->tags) : $item->tags)  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between pt-4">
                <form method="POST" action="{{  route('orgs.content.destroy', ['org' => $currentOrg, 'content' => $item->content_id ?? $item->id])  }}"
                      onsubmit="return confirm('{{ __('confirmations.delete_content') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-700 hover:bg-red-50 rounded-md">
                        {{ __('content.delete_content') }}
                    </button>
                </form>

                <div class="flex gap-4">
                    <a href="{{  route('orgs.content.index', ['org' => $currentOrg])  }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                        {{ __('content.update_content') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
