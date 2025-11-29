{{-- Facebook Platform Options --}}
<div x-show="platform === 'facebook'" class="space-y-4">
    {{-- POST TYPE SELECTION --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fab fa-facebook text-blue-600 me-1"></i>{{ __('publish.post_type') }}
        </label>
        <select x-model="content.platforms.facebook.post_type" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium">
            <option value="single">
                <i class="fas fa-image"></i> {{ __('publish.facebook_single_post') }}
            </option>
            <template x-if="content.global.media.length >= 2">
                <option value="multiple_photos">
                    <i class="fas fa-images"></i> {{ __('publish.facebook_multiple_photos') }}
                </option>
            </template>
        </select>
        <p class="text-xs text-gray-500 mt-2" x-show="content.platforms.facebook.post_type === 'multiple_photos'">
            <i class="fas fa-info-circle me-1"></i>{{ __('publish.facebook_multiple_photos_info') }}
        </p>
    </div>

    {{-- PHASE 5A: FACEBOOK TARGETING (VISTASOCIAL PARITY) --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-3">
        <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-semibold text-gray-800">
                <i class="fab fa-facebook text-blue-600 me-1"></i>{{ __('publish.audience_targeting') }}
            </label>
            <button @click="content.platforms.facebook.targeting_enabled = !content.platforms.facebook.targeting_enabled"
                    class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                <span x-text="content.platforms.facebook.targeting_enabled ? '{{ __('publish.disable') }}' : '{{ __('publish.enable') }}'"></span>
            </button>
        </div>

        <div x-show="content.platforms.facebook.targeting_enabled" class="space-y-3">
            {{-- Country Targeting --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.country_targeting') }}</label>
                <select x-model="content.platforms.facebook.target_country" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">{{ __('publish.all_countries') }}</option>
                    <option value="US">🇺🇸 United States</option>
                    <option value="GB">🇬🇧 United Kingdom</option>
                    <option value="SA">🇸🇦 Saudi Arabia</option>
                    <option value="AE">🇦🇪 United Arab Emirates</option>
                    <option value="EG">🇪🇬 Egypt</option>
                </select>
            </div>

            {{-- Gender Targeting --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gender') }}</label>
                <select x-model="content.platforms.facebook.target_gender" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="all">{{ __('publish.all_genders') }}</option>
                    <option value="male">{{ __('publish.male') }}</option>
                    <option value="female">{{ __('publish.female') }}</option>
                </select>
            </div>

            {{-- Age Range --}}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.min_age') }}</label>
                    <input type="number" x-model="content.platforms.facebook.target_min_age" min="13" max="65" placeholder="18" class="w-full rounded-lg border-gray-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.max_age') }}</label>
                    <input type="number" x-model="content.platforms.facebook.target_max_age" min="13" max="65" placeholder="65" class="w-full rounded-lg border-gray-300 text-xs">
                </div>
            </div>

            {{-- Relationship Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.relationship_status') }}</label>
                <select x-model="content.platforms.facebook.target_relationship" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">{{ __('publish.all_statuses') }}</option>
                    <option value="single">{{ __('publish.single') }}</option>
                    <option value="in_relationship">{{ __('publish.in_relationship') }}</option>
                    <option value="married">{{ __('publish.married') }}</option>
                    <option value="engaged">{{ __('publish.engaged') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Apply to All Facebook Profiles --}}
    <div class="mt-4 pt-3 border-t border-gray-200">
        <button @click="applyToAllProfiles('facebook')"
                class="w-full text-center text-sm text-blue-600 hover:text-blue-700 font-medium py-2 hover:bg-blue-50 rounded transition">
            <i class="fas fa-copy me-1"></i>{{ __('publish.apply_to_all_facebook') }}
        </button>
    </div>
</div>
