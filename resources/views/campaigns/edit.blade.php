@extends('layouts.app')

@section('title', 'Edit Campaign')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{  route('campaigns.show', $campaign->campaign_id ?? $campaign->id)  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Campaign
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Campaign</h1>

        <form method="POST" action="{{  route('campaigns.update', $campaign->campaign_id ?? $campaign->id)  }}" class="space-y-6">
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

            <!-- Campaign Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Campaign Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required
                       value="{{  old('name', $campaign->name)  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea name="description" id="description" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">{{  old('description', $campaign->description)  }}</textarea>
            </div>

            <!-- Campaign Type -->
            <div>
                <label for="campaign_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Campaign Type <span class="text-red-500">*</span>
                </label>
                <select name="campaign_type" id="campaign_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select campaign type</option>
                    <option value="awareness" {{  old('campaign_type', $campaign->campaign_type) === 'awareness' ? 'selected' : ''  }}>Brand Awareness</option>
                    <option value="consideration" {{  old('campaign_type', $campaign->campaign_type) === 'consideration' ? 'selected' : ''  }}>Consideration</option>
                    <option value="conversion" {{  old('campaign_type', $campaign->campaign_type) === 'conversion' ? 'selected' : ''  }}>Conversion</option>
                    <option value="retention" {{  old('campaign_type', $campaign->campaign_type) === 'retention' ? 'selected' : ''  }}>Retention</option>
                </select>
            </div>

            <!-- Budget -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="budget" class="block text-sm font-medium text-gray-700 mb-2">
                        Budget <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">$</span>
                        <input type="number" name="budget" id="budget" required step="0.01" min="0"
                               value="{{  old('budget', $campaign->budget)  }}"
                               class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select name="status" id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="draft" {{  old('status', $campaign->status) === 'draft' ? 'selected' : ''  }}>Draft</option>
                        <option value="active" {{  old('status', $campaign->status) === 'active' ? 'selected' : ''  }}>Active</option>
                        <option value="paused" {{  old('status', $campaign->status) === 'paused' ? 'selected' : ''  }}>Paused</option>
                        <option value="completed" {{  old('status', $campaign->status) === 'completed' ? 'selected' : ''  }}>Completed</option>
                    </select>
                </div>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" id="start_date" required
                           value="{{  old('start_date', $campaign->start_date?->format('Y-m-d'))  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                        End Date
                    </label>
                    <input type="date" name="end_date" id="end_date"
                           value="{{  old('end_date', $campaign->end_date?->format('Y-m-d'))  }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Objective -->
            <div>
                <label for="objective" class="block text-sm font-medium text-gray-700 mb-2">
                    Campaign Objective
                </label>
                <input type="text" name="objective" id="objective"
                       value="{{  old('objective', $campaign->objective)  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Target Audience -->
            <div>
                <label for="target_audience" class="block text-sm font-medium text-gray-700 mb-2">
                    Target Audience
                </label>
                <textarea name="target_audience" id="target_audience" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">{{  old('target_audience', $campaign->target_audience)  }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between pt-4">
                <form method="POST" action="{{  route('campaigns.destroy', $campaign->campaign_id ?? $campaign->id)  }}"
                      onsubmit="return confirm('Are you sure you want to delete this campaign?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-700 hover:bg-red-50 rounded-md">
                        Delete Campaign
                    </button>
                </form>

                <div class="flex space-x-4">
                    <a href="{{  route('campaigns.show', $campaign->campaign_id ?? $campaign->id)  }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                        Update Campaign
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
