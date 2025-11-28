@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('content.create_new'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{  route('orgs.content.index', ['org' => $currentOrg])  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left me-2"></i>
            {{ __('content.back_to_library') }}
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('content.create_new') }}</h1>

        <form method="POST" action="{{  route('orgs.content.store', ['org' => $currentOrg])  }}" enctype="multipart/form-data" class="space-y-6">
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

            <!-- Content Type -->
            <div>
                <label for="content_type" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.content_type') }} <span class="text-red-500">*</span>
                </label>
                <select name="content_type" id="content_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">{{ __('content.select_type') }}</option>
                    <option value="post" {{  old('content_type') === 'post' ? 'selected' : ''  }}>{{ __('content.type_labels.post') }}</option>
                    <option value="ad" {{  old('content_type') === 'ad' ? 'selected' : ''  }}>{{ __('content.type_labels.ad') }}</option>
                    <option value="email" {{  old('content_type') === 'email' ? 'selected' : ''  }}>{{ __('content.type_labels.email') }}</option>
                    <option value="article" {{  old('content_type') === 'article' ? 'selected' : ''  }}>{{ __('content.type_labels.article') }}</option>
                    <option value="video" {{  old('content_type') === 'video' ? 'selected' : ''  }}>{{ __('content.type_labels.video') }}</option>
                </select>
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.title') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" required
                       value="{{  old('title')  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="{{ __('content.enter_title') }}">
            </div>

            <!-- Body/Description -->
            <div>
                <label for="body" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.body') }} <span class="text-red-500">*</span>
                </label>
                <textarea name="body" id="body" rows="8" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="{{ __('content.enter_content') }}">{{  old('body')  }}</textarea>
                <p class="mt-1 text-sm text-gray-500">{{ __('content.html_support') }}</p>
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
                                    {{  old('campaign_id') == ($campaign->campaign_id ?? $campaign->id) ? 'selected' : ''  }}>
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
                        <input type="checkbox" name="platforms[]" value="facebook" class="form-checkbox">
                        <i class="fab fa-facebook text-blue-600"></i>
                        <span>{{ __('platforms.facebook') }}</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="instagram" class="form-checkbox">
                        <i class="fab fa-instagram text-pink-600"></i>
                        <span>{{ __('platforms.instagram') }}</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="twitter" class="form-checkbox">
                        <i class="fab fa-twitter text-blue-400"></i>
                        <span>{{ __('platforms.twitter') }}</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="platforms[]" value="linkedin" class="form-checkbox">
                        <i class="fab fa-linkedin text-blue-700"></i>
                        <span>{{ __('platforms.linkedin') }}</span>
                    </label>
                </div>
            </div>

            <!-- Image Upload -->
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.featured_image') }}
                </label>
                <input type="file" name="image" id="image" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <p class="mt-1 text-sm text-gray-500">{{ __('content.image_formats') }}</p>
            </div>

            <!-- Status and Schedule -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('common.status') }}
                    </label>
                    <select name="status" id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="draft" {{  old('status', 'draft') === 'draft' ? 'selected' : ''  }}>{{ __('content.status.draft') }}</option>
                        <option value="scheduled" {{  old('status') === 'scheduled' ? 'selected' : ''  }}>{{ __('content.status.scheduled') }}</option>
                        <option value="published" {{  old('status') === 'published' ? 'selected' : ''  }}>{{ __('content.status.published') }}</option>
                    </select>
                </div>

                <div>
                    <label for="scheduled_for" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('content.schedule_optional') }}
                    </label>
                    <input type="datetime-local" name="scheduled_for" id="scheduled_for"
                           value="{{  old('scheduled_for')  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Tags -->
            <div>
                <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('content.tag_separator') }}
                </label>
                <input type="text" name="tags" id="tags"
                       value="{{  old('tags')  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="{{ __('content.tag_example') }}">
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-4">
                <a href="{{  route('orgs.content.index', ['org' => $currentOrg])  }}"
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                    {{ __('content.create_content') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
