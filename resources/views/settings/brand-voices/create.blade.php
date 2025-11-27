@extends('layouts.admin')

@section('title', __('Create Brand Voice') . ' - ' . __('Settings'))

@section('content')
<div class="max-w-3xl mx-auto">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.brand-voices.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Brand Voices') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Create') }}</span>
        </nav>
    </div>

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Create Brand Voice</h1>
        <p class="mt-1 text-sm text-gray-500">Define an AI-powered voice profile for consistent content generation.</p>
    </div>

    <form action="{{ route('orgs.settings.brand-voices.store', $currentOrg) }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Voice Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                           placeholder="e.g., Professional Tech, Friendly Lifestyle">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                              placeholder="Brief description of this voice profile">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label for="tone" class="block text-sm font-medium text-gray-700">Tone *</label>
                    <select name="tone" id="tone" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                        @foreach($tones as $value => $label)
                            <option value="{{ $value }}" {{ old('tone') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="target_audience" class="block text-sm font-medium text-gray-700">Target Audience</label>
                    <input type="text" name="target_audience" id="target_audience" value="{{ old('target_audience') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                           placeholder="e.g., Tech professionals aged 25-45">
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Personality & Style</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <label for="personality_traits" class="block text-sm font-medium text-gray-700">Personality Traits</label>
                    <input type="text" name="personality_traits_input" id="personality_traits"
                           value="{{ old('personality_traits_input', implode(', ', old('personality_traits', []))) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                           placeholder="Enter traits separated by commas (e.g., witty, insightful, approachable)">
                    <p class="mt-1 text-xs text-gray-500">Separate multiple traits with commas</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="emojis_preference" class="block text-sm font-medium text-gray-700">Emoji Usage *</label>
                        <select name="emojis_preference" id="emojis_preference" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                            @foreach($emojiOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('emojis_preference', 'moderate') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="hashtag_strategy" class="block text-sm font-medium text-gray-700">Hashtag Strategy *</label>
                        <select name="hashtag_strategy" id="hashtag_strategy" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                            @foreach($hashtagStrategies as $value => $label)
                                <option value="{{ $value }}" {{ old('hashtag_strategy', 'moderate') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="keywords_to_use" class="block text-sm font-medium text-gray-700">Keywords to Use</label>
                    <input type="text" name="keywords_to_use_input" id="keywords_to_use"
                           value="{{ old('keywords_to_use_input') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                           placeholder="Words to include (comma-separated)">
                </div>

                <div>
                    <label for="keywords_to_avoid" class="block text-sm font-medium text-gray-700">Keywords to Avoid</label>
                    <input type="text" name="keywords_to_avoid_input" id="keywords_to_avoid"
                           value="{{ old('keywords_to_avoid_input') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                           placeholder="Words to exclude (comma-separated)">
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Language Settings</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="primary_language" class="block text-sm font-medium text-gray-700">Primary Language *</label>
                        <select name="primary_language" id="primary_language" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                            <option value="en" {{ old('primary_language', 'en') == 'en' ? 'selected' : '' }}>English</option>
                            <option value="es" {{ old('primary_language') == 'es' ? 'selected' : '' }}>Spanish</option>
                            <option value="fr" {{ old('primary_language') == 'fr' ? 'selected' : '' }}>French</option>
                            <option value="de" {{ old('primary_language') == 'de' ? 'selected' : '' }}>German</option>
                            <option value="ar" {{ old('primary_language') == 'ar' ? 'selected' : '' }}>Arabic</option>
                        </select>
                    </div>
                    <div>
                        <label for="dialect_preference" class="block text-sm font-medium text-gray-700">Dialect</label>
                        <input type="text" name="dialect_preference" id="dialect_preference"
                               value="{{ old('dialect_preference') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                               placeholder="e.g., American English, British English">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">AI Configuration</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <label for="ai_system_prompt" class="block text-sm font-medium text-gray-700">Custom AI System Prompt</label>
                    <textarea name="ai_system_prompt" id="ai_system_prompt" rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm font-mono text-xs"
                              placeholder="Custom instructions for the AI when generating content with this voice...">{{ old('ai_system_prompt') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Advanced: Override the default AI behavior</p>
                </div>

                <div>
                    <label for="temperature" class="block text-sm font-medium text-gray-700">Creativity Level</label>
                    <div class="mt-1 flex items-center gap-3">
                        <input type="range" name="temperature" id="temperature" min="0" max="1" step="0.1"
                               value="{{ old('temperature', 0.7) }}"
                               class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        <span class="text-sm text-gray-600 w-12" id="temperature_value">0.7</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Lower = more consistent, Higher = more creative</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('orgs.settings.brand-voices.index', $currentOrg) }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                Create Brand Voice
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('temperature').addEventListener('input', function() {
    document.getElementById('temperature_value').textContent = this.value;
});
</script>
@endsection
