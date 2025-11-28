@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('assets.upload_assets'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{  route('orgs.assets.index', ['org' => $currentOrg])  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left me-2"></i>
            {{ __('assets.back_to_assets') }}
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('assets.upload_creative_assets') }}</h1>

        <form method="POST" action="{{  route('orgs.assets.store', ['org' => $currentOrg])  }}" enctype="multipart/form-data" class="space-y-6">
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
                    {{ __('assets.upload_files') }} <span class="text-red-500">*</span>
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
                    <p class="text-lg text-gray-700 mb-2">{{ __('assets.drag_drop_files') }}</p>
                    <button type="button"
                            @click="$refs.fileInput.click()"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md">
                        {{ __('assets.browse_files') }}
                    </button>
                    <p class="text-sm text-gray-500 mt-4">
                        {{ __('assets.supported_formats') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-2">{{ __('assets.max_file_size') }}</p>
                    
                    <div x-ref="fileList" class="mt-4 text-sm text-gray-600"></div>
                </div>
            </div>

            <!-- Asset Type -->
            <div>
                <label for="asset_type" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('assets.asset_type') }} <span class="text-red-500">*</span>
                </label>
                <select name="asset_type" id="asset_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">{{ __('assets.select_asset_type') }}</option>
                    <option value="image" {{  old('asset_type') === 'image' ? 'selected' : ''  }}>{{ __('assets.image') }}</option>
                    <option value="video" {{  old('asset_type') === 'video' ? 'selected' : ''  }}>{{ __('assets.video') }}</option>
                    <option value="document" {{  old('asset_type') === 'document' ? 'selected' : ''  }}>{{ __('assets.document') }}</option>
                    <option value="audio" {{  old('asset_type') === 'audio' ? 'selected' : ''  }}>{{ __('assets.audio_single') }}</option>
                </select>
            </div>

            <!-- Alt Text / Description -->
            <div>
                <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('assets.description_alt_text') }}
                </label>
                <textarea name="alt_text" id="alt_text" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="{{ __('assets.describe_asset') }}">{{  old('alt_text')  }}</textarea>
            </div>

            <!-- Campaign Association -->
            <div>
                <label for="campaign_id" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('assets.associate_campaign') }}
                </label>
                <select name="campaign_id" id="campaign_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">{{ __('assets.no_campaign_association') }}</option>
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
                    {{ __('assets.tags_comma_separated') }}
                </label>
                <input type="text" name="tags" id="tags"
                       value="{{  old('tags')  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="{{ __('assets.tags_placeholder') }}">
                <p class="mt-1 text-sm text-gray-500">{{ __('assets.tags_help') }}</p>
            </div>

            <!-- Usage Rights -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('assets.usage_rights') }}
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_licensed" value="1" class="form-checkbox">
                        <span class="ms-2 text-sm text-gray-700">{{ __('assets.asset_licensed') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_public" value="1" class="form-checkbox">
                        <span class="ms-2 text-sm text-gray-700">{{ __('assets.make_available_all') }}</span>
                    </label>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="{{  route('orgs.assets.index', ['org' => $currentOrg])  }}"
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                    <i class="fas fa-upload me-2"></i>
                    {{ __('assets.upload_assets') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Upload Tips -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-blue-900 mb-2">
            <i class="fas fa-lightbulb me-2"></i>{{ __('assets.upload_tips') }}
        </h3>
        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
            <li>{{ __('assets.tip_high_resolution') }}</li>
            <li>{{ __('assets.tip_video_format') }}</li>
            <li>{{ __('assets.tip_alt_text') }}</li>
            <li>{{ __('assets.tip_naming') }}</li>
            <li>{{ __('assets.tip_licenses') }}</li>
        </ul>
    </div>
</div>
@endsection
