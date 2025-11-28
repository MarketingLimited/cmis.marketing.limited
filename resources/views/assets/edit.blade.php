@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('assets.edit_asset'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{  route('orgs.assets.index', ['org' => $currentOrg])  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left me-2"></i>
            {{ __('assets.back_to_assets') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Asset Preview -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('assets.asset_preview') }}</h2>
            
            <div class="mb-4">
                @if($asset->asset_type === 'image')
                    <img src="{{  $asset->file_url ?? $asset->url  }}" alt="{{  $asset->file_name  }}"
                         class="w-full rounded-lg shadow-sm">
                @elseif($asset->asset_type === 'video')
                    <video controls class="w-full rounded-lg shadow-sm">
                        <source src="{{  $asset->file_url ?? $asset->url  }}" type="video/mp4">
                        {{ __('assets.video_not_supported') }}
                    </video>
                @else
                    <div class="w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file text-6xl text-gray-400"></i>
                    </div>
                @endif
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('assets.file_name') }}:</span>
                    <span class="font-medium">{{  $asset->file_name  }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('assets.type') }}:</span>
                    <span class="font-medium uppercase">{{  $asset->asset_type  }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('assets.size') }}:</span>
                    <span class="font-medium">{{  $asset->file_size ?? __('common.not_available')  }}</span>
                </div>
                @if($asset->width && $asset->height)
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('assets.dimensions') }}:</span>
                        <span class="font-medium">{{  $asset->width  }} x {{  $asset->height  }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('assets.uploaded') }}:</span>
                    <span class="font-medium">{{  $asset->created_at?->format('M d, Y') ?? __('common.not_available')  }}</span>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="{{  $asset->file_url ?? $asset->url  }}" download
                   class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md">
                    <i class="fas fa-download me-2"></i>
                    {{ __('assets.download_original') }}
                </a>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('assets.asset_details') }}</h2>

            <form method="POST" action="{{  route('orgs.assets.update', ['org' => $currentOrg, 'asset' => $asset->asset_id ?? $asset->id])  }}" class="space-y-4">
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
                        {{ __('assets.display_name') }}
                    </label>
                    <input type="text" name="file_name" id="file_name"
                           value="{{  old('file_name', $asset->file_name)  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Alt Text -->
                <div>
                    <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('assets.description_alt_text') }}
                    </label>
                    <textarea name="alt_text" id="alt_text" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">{{  old('alt_text', $asset->alt_text)  }}</textarea>
                </div>

                <!-- Campaign Association -->
                <div>
                    <label for="campaign_id" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('assets.associated_campaign') }}
                    </label>
                    <select name="campaign_id" id="campaign_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">{{ __('assets.no_campaign_association') }}</option>
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
                        {{ __('assets.tags_comma_separated') }}
                    </label>
                    <input type="text" name="tags" id="tags"
                           value="{{  old('tags', is_array($asset->tags ?? null) ? implode(', ', $asset->tags) : $asset->tags)  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Usage Rights -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('assets.usage_rights') }}
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_licensed" value="1" class="form-checkbox"
                                   {{  old('is_licensed', $asset->is_licensed ?? false) ? 'checked' : ''  }}>
                            <span class="ms-2 text-sm text-gray-700">{{ __('assets.licensed_commercial') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_public" value="1" class="form-checkbox"
                                   {{  old('is_public', $asset->is_public ?? false) ? 'checked' : ''  }}>
                            <span class="ms-2 text-sm text-gray-700">{{ __('assets.available_all_members') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col space-y-2 pt-4">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                        {{ __('assets.update_asset') }}
                    </button>
                    <a href="{{  route('orgs.assets.index', ['org' => $currentOrg])  }}"
                       class="w-full text-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        {{ __('common.cancel') }}
                    </a>
                </div>
            </form>

            <!-- Delete Form -->
            <form method="POST" action="{{  route('orgs.assets.destroy', ['org' => $currentOrg, 'asset' => $asset->asset_id ?? $asset->id])  }}"
                  onsubmit="return confirm('{{ __('assets.confirm_delete') }}');"
                  class="mt-6 pt-6 border-t border-gray-200">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-700 hover:bg-red-50 rounded-md">
                    <i class="fas fa-trash me-2"></i>
                    {{ __('assets.delete_asset') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
