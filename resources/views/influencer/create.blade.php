@extends('layouts.admin')

@section('title', __('influencer.create_title'))

@section('content')
<div class="container mx-auto px-4 py-6" x-data="influencerCreate()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
            <a href="{{ route('orgs.influencer.index', ['org' => $currentOrg]) }}"
               class="hover:text-gray-900 dark:hover:text-white transition-colors">
                {{ __('influencer.manage_influencers') }}
            </a>
            <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-xs"></i>
            <span class="text-gray-900 dark:text-white">{{ __('influencer.create_influencer') }}</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('influencer.create_influencer') }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('influencer.create_subtitle') }}</p>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('orgs.influencer.store', ['org' => $currentOrg]) }}" class="space-y-6">
        @csrf

        <!-- Basic Information Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('influencer.basic_info') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Full Name -->
                <div class="md:col-span-2">
                    <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('influencer.full_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="full_name"
                           name="full_name"
                           value="{{ old('full_name') }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('full_name') border-red-500 @enderror"
                           placeholder="{{ __('influencer.full_name_placeholder') }}">
                    @error('full_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('influencer.email') }}
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('email') border-red-500 @enderror"
                           placeholder="{{ __('influencer.email_placeholder') }}">
                    @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('influencer.phone') }}
                    </label>
                    <input type="text"
                           id="phone"
                           name="phone"
                           value="{{ old('phone') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('phone') border-red-500 @enderror"
                           placeholder="{{ __('influencer.phone_placeholder') }}">
                    @error('phone')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('influencer.location') }}
                    </label>
                    <input type="text"
                           id="location"
                           name="location"
                           value="{{ old('location') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('location') border-red-500 @enderror"
                           placeholder="{{ __('influencer.location_placeholder') }}">
                    @error('location')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tier -->
                <div>
                    <label for="tier" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('influencer.tier') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="tier"
                            name="tier"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('tier') border-red-500 @enderror">
                        <option value="">{{ __('influencer.select_tier') }}</option>
                        <option value="nano" {{ old('tier') === 'nano' ? 'selected' : '' }}>{{ __('influencer.tier_nano') }}</option>
                        <option value="micro" {{ old('tier') === 'micro' ? 'selected' : '' }}>{{ __('influencer.tier_micro') }}</option>
                        <option value="mid" {{ old('tier') === 'mid' ? 'selected' : '' }}>{{ __('influencer.tier_mid') }}</option>
                        <option value="macro" {{ old('tier') === 'macro' ? 'selected' : '' }}>{{ __('influencer.tier_macro') }}</option>
                        <option value="mega" {{ old('tier') === 'mega' ? 'selected' : '' }}>{{ __('influencer.tier_mega') }}</option>
                        <option value="celebrity" {{ old('tier') === 'celebrity' ? 'selected' : '' }}>{{ __('influencer.tier_celebrity') }}</option>
                    </select>
                    @error('tier')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bio -->
                <div class="md:col-span-2">
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('influencer.bio') }}
                    </label>
                    <textarea id="bio"
                              name="bio"
                              rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('bio') border-red-500 @enderror"
                              placeholder="{{ __('influencer.bio_placeholder') }}">{{ old('bio') }}</textarea>
                    @error('bio')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Social Accounts Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('influencer.social_accounts') }} <span class="text-red-500">*</span></h2>
                <button type="button"
                        @click="addSocialAccount()"
                        class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                    <i class="fas fa-plus me-2"></i>
                    {{ __('influencer.add_account') }}
                </button>
            </div>

            <div class="space-y-3" id="social-accounts-container">
                <template x-for="(account, index) in socialAccounts" :key="index">
                    <div class="flex gap-3">
                        <select x-model="account.platform"
                                :name="`social_accounts[${index}][platform]`"
                                required
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('influencer.select_platform') }}</option>
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                            <option value="youtube">YouTube</option>
                            <option value="twitter">Twitter/X</option>
                            <option value="facebook">Facebook</option>
                            <option value="snapchat">Snapchat</option>
                            <option value="linkedin">LinkedIn</option>
                        </select>
                        <input type="text"
                               x-model="account.username"
                               :name="`social_accounts[${index}][username]`"
                               required
                               class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               :placeholder="'{{ __('influencer.username_placeholder') }}'">
                        <input type="number"
                               x-model="account.followers"
                               :name="`social_accounts[${index}][followers]`"
                               class="w-32 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               :placeholder="'{{ __('influencer.followers') }}'">
                        <button type="button"
                                @click="removeSocialAccount(index)"
                                x-show="socialAccounts.length > 1"
                                class="px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>
            </div>
            @error('social_accounts')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Categories & Content Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('influencer.categories_content') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Niches -->
                <div>
                    <label for="niches" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('influencer.niches') }}
                    </label>
                    <select id="niches"
                            name="niches[]"
                            multiple
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white h-32">
                        <option value="fashion">{{ __('influencer.niche_fashion') }}</option>
                        <option value="beauty">{{ __('influencer.niche_beauty') }}</option>
                        <option value="tech">{{ __('influencer.niche_tech') }}</option>
                        <option value="gaming">{{ __('influencer.niche_gaming') }}</option>
                        <option value="food">{{ __('influencer.niche_food') }}</option>
                        <option value="travel">{{ __('influencer.niche_travel') }}</option>
                        <option value="fitness">{{ __('influencer.niche_fitness') }}</option>
                        <option value="lifestyle">{{ __('influencer.niche_lifestyle') }}</option>
                        <option value="business">{{ __('influencer.niche_business') }}</option>
                        <option value="entertainment">{{ __('influencer.niche_entertainment') }}</option>
                    </select>
                    <p class="text-gray-500 text-xs mt-1">{{ __('influencer.niches_hint') }}</p>
                </div>

                <!-- Content Types -->
                <div>
                    <label for="content_types" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('influencer.content_types') }}
                    </label>
                    <select id="content_types"
                            name="content_types[]"
                            multiple
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white h-32">
                        <option value="photo">{{ __('influencer.content_photo') }}</option>
                        <option value="video">{{ __('influencer.content_video') }}</option>
                        <option value="reel">{{ __('influencer.content_reel') }}</option>
                        <option value="story">{{ __('influencer.content_story') }}</option>
                        <option value="live">{{ __('influencer.content_live') }}</option>
                        <option value="blog">{{ __('influencer.content_blog') }}</option>
                        <option value="podcast">{{ __('influencer.content_podcast') }}</option>
                    </select>
                    <p class="text-gray-500 text-xs mt-1">{{ __('influencer.content_types_hint') }}</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button type="submit"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <i class="fas fa-save me-2"></i>
                {{ __('influencer.create_influencer') }}
            </button>
            <a href="{{ route('orgs.influencer.index', ['org' => $currentOrg]) }}"
               class="inline-flex items-center px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors">
                {{ __('common.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
function influencerCreate() {
    return {
        socialAccounts: [
            { platform: '', username: '', followers: '' }
        ],

        addSocialAccount() {
            this.socialAccounts.push({ platform: '', username: '', followers: '' });
        },

        removeSocialAccount(index) {
            if (this.socialAccounts.length > 1) {
                this.socialAccounts.splice(index, 1);
            }
        }
    };
}
</script>
@endsection
