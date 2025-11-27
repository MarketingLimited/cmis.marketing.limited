@extends('layouts.admin')

@section('title', __('Edit') . ' ' . $policy->name . ' - ' . __('Brand Safety'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.brand-safety.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Brand Safety') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.brand-safety.show', [$currentOrg, $policy->policy_id]) }}" class="hover:text-blue-600 transition">{{ $policy->name }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Edit') }}</span>
        </nav>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Edit Brand Safety Policy</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                Update content restrictions and compliance rules.
            </p>
        </div>
    </div>

    <form action="{{ route('orgs.settings.brand-safety.update', [$currentOrg, $policy->policy_id]) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Information --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-purple-500 mr-2"></i>Basic Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Policy Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $policy->name) }}" required
                           class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">{{ old('description', $policy->description) }}</textarea>
                </div>

                <div>
                    <label for="profile_group_id" class="block text-sm font-medium text-gray-700 mb-1">Profile Group</label>
                    <select name="profile_group_id" id="profile_group_id"
                            class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                        <option value="">All Profile Groups</option>
                        @foreach($profileGroups ?? [] as $group)
                            <option value="{{ $group->group_id }}" {{ old('profile_group_id', $policy->profile_group_id) == $group->group_id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="severity_level" class="block text-sm font-medium text-gray-700 mb-1">Default Severity</label>
                    <select name="severity_level" id="severity_level"
                            class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                        <option value="warning" {{ old('severity_level', $policy->severity_level) == 'warning' ? 'selected' : '' }}>Warning (Allow with notice)</option>
                        <option value="block" {{ old('severity_level', $policy->severity_level) == 'block' ? 'selected' : '' }}>Block (Prevent publishing)</option>
                        <option value="review" {{ old('severity_level', $policy->severity_level) == 'review' ? 'selected' : '' }}>Review (Require approval)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Blocked Words --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-ban text-red-500 mr-2"></i>Blocked Words & Phrases
            </h3>
            <p class="text-sm text-gray-500 mb-4">Content containing these words will be flagged or blocked.</p>

            <div x-data="{ blockedWords: {{ json_encode(old('blocked_words', $policy->blocked_words ?? [])) }} }">
                <div class="flex flex-wrap gap-2 mb-3 min-h-[40px] p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <template x-for="(word, index) in blockedWords" :key="index">
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">
                            <span x-text="word"></span>
                            <button type="button" @click="blockedWords.splice(index, 1)" class="hover:text-red-900">
                                <i class="fas fa-times"></i>
                            </button>
                            <input type="hidden" name="blocked_words[]" :value="word">
                        </span>
                    </template>
                    <span x-show="blockedWords.length === 0" class="text-sm text-gray-400">No blocked words added</span>
                </div>

                <div class="flex gap-2">
                    <input type="text" x-ref="newBlockedWord" placeholder="Add blocked word..."
                           class="flex-1 rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500 text-sm"
                           @keydown.enter.prevent="if($refs.newBlockedWord.value.trim()) { blockedWords.push($refs.newBlockedWord.value.trim()); $refs.newBlockedWord.value = ''; }">
                    <button type="button" @click="if($refs.newBlockedWord.value.trim()) { blockedWords.push($refs.newBlockedWord.value.trim()); $refs.newBlockedWord.value = ''; }"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                        <i class="fas fa-plus mr-1"></i>Add
                    </button>
                </div>
            </div>
        </div>

        {{-- Required Elements --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>Required Elements
            </h3>
            <p class="text-sm text-gray-500 mb-4">Content must include at least one of these elements.</p>

            <div x-data="{ requiredElements: {{ json_encode(old('required_elements', $policy->required_elements ?? [])) }} }">
                <div class="flex flex-wrap gap-2 mb-3 min-h-[40px] p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <template x-for="(element, index) in requiredElements" :key="index">
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">
                            <span x-text="element"></span>
                            <button type="button" @click="requiredElements.splice(index, 1)" class="hover:text-green-900">
                                <i class="fas fa-times"></i>
                            </button>
                            <input type="hidden" name="required_elements[]" :value="element">
                        </span>
                    </template>
                    <span x-show="requiredElements.length === 0" class="text-sm text-gray-400">No required elements</span>
                </div>

                <div class="flex gap-2">
                    <input type="text" x-ref="newRequiredElement" placeholder="Add required element..."
                           class="flex-1 rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500 text-sm"
                           @keydown.enter.prevent="if($refs.newRequiredElement.value.trim()) { requiredElements.push($refs.newRequiredElement.value.trim()); $refs.newRequiredElement.value = ''; }">
                    <button type="button" @click="if($refs.newRequiredElement.value.trim()) { requiredElements.push($refs.newRequiredElement.value.trim()); $refs.newRequiredElement.value = ''; }"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                        <i class="fas fa-plus mr-1"></i>Add
                    </button>
                </div>
            </div>
        </div>

        {{-- Content Rules --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-ruler text-blue-500 mr-2"></i>Content Rules
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Block URLs</p>
                        <p class="text-xs text-gray-500">Prevent sharing external links</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="block_urls" value="1" {{ old('block_urls', $policy->rules['block_urls'] ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Block Competitor Mentions</p>
                        <p class="text-xs text-gray-500">Flag content mentioning competitor brands</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="block_competitors" value="1" {{ old('block_competitors', $policy->rules['block_competitors'] ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Require Disclosure</p>
                        <p class="text-xs text-gray-500">Ensure sponsored content includes #ad or #sponsored</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="require_disclosure" value="1" {{ old('require_disclosure', $policy->rules['require_disclosure'] ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Check Sentiment</p>
                        <p class="text-xs text-gray-500">AI analysis for negative sentiment</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="check_sentiment" value="1" {{ old('check_sentiment', $policy->rules['check_sentiment'] ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Character Limits --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-text-width text-yellow-500 mr-2"></i>Character Limits
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="min_characters" class="block text-sm font-medium text-gray-700 mb-1">Minimum Characters</label>
                    <input type="number" name="min_characters" id="min_characters" value="{{ old('min_characters', $policy->rules['min_characters'] ?? 0) }}" min="0"
                           class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="max_characters" class="block text-sm font-medium text-gray-700 mb-1">Maximum Characters</label>
                    <input type="number" name="max_characters" id="max_characters" value="{{ old('max_characters', $policy->rules['max_characters'] ?? '') }}" min="0"
                           class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="Leave empty for platform default">
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Policy Status</h3>
                    <p class="text-sm text-gray-500">Enable or disable this policy</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $policy->is_active) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <form action="{{ route('orgs.settings.brand-safety.destroy', [$currentOrg, $policy->policy_id]) }}" method="POST"
                  onsubmit="return confirm('Delete this brand safety policy? This action cannot be undone.');">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 text-red-600 hover:text-red-700 text-sm font-medium">
                    <i class="fas fa-trash mr-1"></i>Delete Policy
                </button>
            </form>

            <div class="flex items-center gap-3">
                <a href="{{ route('orgs.settings.brand-safety.show', [$currentOrg, $policy->policy_id]) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
