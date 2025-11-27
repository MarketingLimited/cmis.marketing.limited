@extends('layouts.admin')

@section('title', __('Create Profile Group') . ' - ' . __('Settings'))

@section('content')
<div class="max-w-3xl mx-auto">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.profile-groups.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Profile Groups') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Create') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Create Profile Group</h1>
        <p class="mt-1 text-sm text-gray-500">
            Set up a new group to organize your social media profiles.
        </p>
    </div>

    {{-- Form --}}
    <form action="{{ route('orgs.settings.profile-groups.store', $currentOrg) }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Group Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="e.g., Client ABC, Tech Brand, Marketing Team">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                              placeholder="Brief description of this profile group">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Color --}}
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700">Brand Color</label>
                    <div class="mt-1 flex items-center gap-3">
                        <input type="color" name="color" id="color" value="{{ old('color', '#3B82F6') }}"
                               class="h-10 w-20 rounded-md border-gray-300 cursor-pointer">
                        <span class="text-sm text-gray-500">Used for visual identification</span>
                    </div>
                </div>

                {{-- Logo URL --}}
                <div>
                    <label for="logo_url" class="block text-sm font-medium text-gray-700">Logo URL</label>
                    <input type="url" name="logo_url" id="logo_url" value="{{ old('logo_url') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="https://example.com/logo.png">
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Regional Settings</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                {{-- Timezone --}}
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone *</label>
                    <select name="timezone" id="timezone" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @foreach($timezones as $value => $label)
                            <option value="{{ $value }}" {{ old('timezone', 'UTC') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Language --}}
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700">Primary Language *</label>
                    <select name="language" id="language" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @foreach($languages as $value => $label)
                            <option value="{{ $value }}" {{ old('language', 'en') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Client Location --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="client_location_country" class="block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" name="client_location[country]" id="client_location_country"
                               value="{{ old('client_location.country') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="e.g., United States">
                    </div>
                    <div>
                        <label for="client_location_city" class="block text-sm font-medium text-gray-700">City</label>
                        <input type="text" name="client_location[city]" id="client_location_city"
                               value="{{ old('client_location.city') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="e.g., New York">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Brand Configuration</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                {{-- Brand Voice --}}
                <div>
                    <label for="brand_voice_id" class="block text-sm font-medium text-gray-700">Brand Voice</label>
                    <select name="brand_voice_id" id="brand_voice_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">No brand voice selected</option>
                        @foreach($brandVoices as $voice)
                            <option value="{{ $voice->voice_id }}" {{ old('brand_voice_id') == $voice->voice_id ? 'selected' : '' }}>
                                {{ $voice->name }} ({{ $voice->tone }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        AI-generated content will use this brand voice.
                        <a href="{{ route('orgs.settings.brand-voices.create', $currentOrg) }}" class="text-blue-600 hover:text-blue-700">Create new voice</a>
                    </p>
                </div>

                {{-- Brand Safety Policy --}}
                <div>
                    <label for="brand_safety_policy_id" class="block text-sm font-medium text-gray-700">Brand Safety Policy</label>
                    <select name="brand_safety_policy_id" id="brand_safety_policy_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">No safety policy selected</option>
                        @foreach($brandSafetyPolicies as $policy)
                            <option value="{{ $policy->policy_id }}" {{ old('brand_safety_policy_id') == $policy->policy_id ? 'selected' : '' }}>
                                {{ $policy->name }} ({{ $policy->risk_level }} risk)
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Content will be checked against this policy before publishing.
                        <a href="{{ route('orgs.settings.brand-safety.create', $currentOrg) }}" class="text-blue-600 hover:text-blue-700">Create new policy</a>
                    </p>
                </div>

                {{-- Link Shortener --}}
                <div>
                    <label for="default_link_shortener" class="block text-sm font-medium text-gray-700">Default Link Shortener</label>
                    <select name="default_link_shortener" id="default_link_shortener"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">None</option>
                        <option value="bitly" {{ old('default_link_shortener') == 'bitly' ? 'selected' : '' }}>Bit.ly</option>
                        <option value="rebrandly" {{ old('default_link_shortener') == 'rebrandly' ? 'selected' : '' }}>Rebrandly</option>
                        <option value="tinyurl" {{ old('default_link_shortener') == 'tinyurl' ? 'selected' : '' }}>TinyURL</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('orgs.settings.profile-groups.index', $currentOrg) }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                Create Profile Group
            </button>
        </div>
    </form>
</div>
@endsection
