{{-- Step 3: Creative Content --}}
<div class="space-y-6" x-data="creativeStep()">
    {{-- Ad Format Selection --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">
            {{ __('campaigns.ad_format') }} <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($step_data['ad_formats'] as $key => $label)
                <label class="relative flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer hover:border-blue-300
                    {{ old('ad_format', $session['data']['ad_format'] ?? '') == $key ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                    <input type="radio" name="ad_format" value="{{ $key }}" required
                           {{ old('ad_format', $session['data']['ad_format'] ?? '') == $key ? 'checked' : '' }}
                           @change="adFormat = '{{ $key }}'"
                           class="sr-only">

                    {{-- Format Icon --}}
                    <div class="mb-2">
                        @if($key === 'single_image')
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @elseif($key === 'carousel')
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        @elseif($key === 'video')
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        @elseif($key === 'collection')
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        @endif
                    </div>

                    <span class="text-sm font-medium text-gray-900 text-center">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Primary Text --}}
    <div>
        <label for="primary_text" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.primary_text') }} <span class="text-red-500">*</span>
        </label>
        <textarea name="primary_text" id="primary_text" rows="4" required
                  maxlength="500"
                  x-model="primaryText"
                  @input="updateCharCount"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="{{ __('campaigns.wizard.creative.primary_text_placeholder') }}">{{ old('primary_text', $session['data']['primary_text'] ?? '') }}</textarea>
        <div class="mt-1 flex justify-between text-sm">
            <p class="text-gray-500">{{ __('campaigns.wizard.creative.primary_text_help') }}</p>
            <p class="text-gray-600">
                <span x-text="primaryText.length"></span> / 500
            </p>
        </div>
    </div>

    {{-- Headline --}}
    <div>
        <label for="headline" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.headline') }}
        </label>
        <input type="text" name="headline" id="headline"
               value="{{ old('headline', $session['data']['headline'] ?? '') }}"
               maxlength="100"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
               placeholder="{{ __('campaigns.wizard.creative.headline_placeholder') }}">
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.creative.headline_help') }}</p>
    </div>

    {{-- Description --}}
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.description') }}
        </label>
        <textarea name="description" id="description" rows="2"
                  maxlength="200"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="{{ __('campaigns.wizard.creative.description_placeholder') }}">{{ old('description', $session['data']['description'] ?? '') }}</textarea>
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.creative.description_help') }}</p>
    </div>

    {{-- Call to Action --}}
    <div>
        <label for="call_to_action" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.call_to_action') }}
        </label>
        <select name="call_to_action" id="call_to_action"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">{{ __('common.select') }}</option>
            @php
                $ctas = [
                    'learn_more' => __('campaigns.cta.learn_more'),
                    'shop_now' => __('campaigns.cta.shop_now'),
                    'sign_up' => __('campaigns.cta.sign_up'),
                    'download' => __('campaigns.cta.download'),
                    'get_offer' => __('campaigns.cta.get_offer'),
                    'contact_us' => __('campaigns.cta.contact_us'),
                    'book_now' => __('campaigns.cta.book_now'),
                    'apply_now' => __('campaigns.cta.apply_now'),
                ];
            @endphp
            @foreach($ctas as $key => $label)
                <option value="{{ $key }}" {{ old('call_to_action', $session['data']['call_to_action'] ?? '') == $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.creative.cta_help') }}</p>
    </div>

    {{-- Media Upload --}}
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <div class="mt-4">
                <label for="media_upload" class="cursor-pointer">
                    <span class="mt-2 block text-sm font-medium text-blue-600 hover:text-blue-500">
                        {{ __('campaigns.upload_media') }}
                    </span>
                    <input id="media_upload" name="media_upload" type="file"
                           accept="image/*,video/*"
                           multiple
                           @change="handleFileUpload"
                           class="sr-only">
                </label>
                <p class="mt-1 text-xs text-gray-500">
                    {{ __('campaigns.wizard.creative.media_help') }}
                </p>
            </div>
        </div>

        {{-- Uploaded Files Preview --}}
        <div x-show="uploadedFiles.length > 0" class="mt-4 grid grid-cols-3 gap-2">
            <template x-for="(file, index) in uploadedFiles" :key="index">
                <div class="relative group">
                    <img :src="file.preview" :alt="file.name" class="w-full h-24 object-cover rounded">
                    <button type="button" @click="removeFile(index)"
                            class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- Media URLs (alternative) --}}
        <div class="mt-4">
            <label for="media_urls_text" class="block text-sm font-medium text-gray-700">
                {{ __('campaigns.or_provide_urls') }}
            </label>
            <textarea name="media_urls_text" id="media_urls_text" rows="2"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                      placeholder="{{ __('campaigns.wizard.creative.media_urls_placeholder') }}">{{ old('media_urls_text', implode("\n", $session['data']['media_urls'] ?? [])) }}</textarea>
        </div>
    </div>

    {{-- AI Content Generation & Boost Post Options --}}
    <div class="grid md:grid-cols-2 gap-4">
        {{-- AI Content Generation --}}
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-purple-600 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-purple-900 mb-1">
                        {{ __('campaigns.wizard.creative.ai_helper_title') }}
                    </h4>
                    <p class="text-sm text-purple-700 mb-3">
                        {{ __('campaigns.wizard.creative.ai_helper_description') }}
                    </p>
                    <button type="button" @click="openAiGenerationModal"
                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200">
                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        {{ __('campaigns.generate_with_ai') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Boost Existing Post --}}
        <div class="bg-gradient-to-r from-green-50 to-teal-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-green-600 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-green-900 mb-1">
                        {{ __('campaigns.boost_post.title') }}
                    </h4>
                    <p class="text-sm text-green-700 mb-3">
                        {{ __('campaigns.boost_post.description') }}
                    </p>
                    <button type="button" @click="openBoostPostModal"
                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200">
                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        {{ __('campaigns.boost_post.browse_posts') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Generation Modal --}}
    <div x-show="showAiModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeAiModal"></div>

            <div class="relative bg-white rounded-lg max-w-2xl w-full p-6 shadow-xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('campaigns.ai_modal.title') }}</h3>
                    <button @click="closeAiModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Generation Type Tabs --}}
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button @click="aiGenType = 'copy'" type="button"
                                    :class="aiGenType === 'copy' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm">
                                {{ __('campaigns.ai_modal.ad_copy') }}
                            </button>
                            <button @click="aiGenType = 'design'" type="button"
                                    :class="aiGenType === 'design' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm">
                                {{ __('campaigns.ai_modal.ad_design') }}
                            </button>
                        </nav>
                    </div>

                    {{-- Ad Copy Generation --}}
                    <div x-show="aiGenType === 'copy'" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('campaigns.ai_modal.product_description') }}</label>
                            <textarea x-model="aiPrompt" rows="3" required
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                      placeholder="{{ __('campaigns.ai_modal.product_description_placeholder') }}"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('campaigns.ai_modal.target_audience') }}</label>
                            <input type="text" x-model="aiTargetAudience"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="{{ __('campaigns.ai_modal.target_audience_placeholder') }}">
                        </div>

                        <button @click="generateAdCopy" type="button"
                                :disabled="aiGenerating"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                            <span x-show="!aiGenerating">{{ __('campaigns.ai_modal.generate_ad_copy') }}</span>
                            <span x-show="aiGenerating">
                                <svg class="animate-spin h-5 w-5 text-white me-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('campaigns.ai_modal.generating') }}
                            </span>
                        </button>

                        {{-- Generated Results --}}
                        <div x-show="generatedCopy" class="mt-4 space-y-3 bg-gray-50 rounded-lg p-4">
                            <div>
                                <p class="text-xs font-medium text-gray-700 mb-1">{{ __('campaigns.ai_modal.headlines') }}</p>
                                <template x-for="(headline, index) in generatedCopy?.headlines || []" :key="index">
                                    <div class="flex items-start justify-between p-2 bg-white rounded mb-1">
                                        <p class="text-sm" x-text="headline"></p>
                                        <button @click="applyHeadline(headline)" type="button" class="text-blue-600 hover:text-blue-700 text-xs ms-2">{{ __('campaigns.ai_modal.use') }}</button>
                                    </div>
                                </template>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-700 mb-1">{{ __('campaigns.ai_modal.primary_text') }}</p>
                                <div class="p-2 bg-white rounded">
                                    <p class="text-sm" x-text="generatedCopy?.primary_text"></p>
                                    <button @click="applyPrimaryText(generatedCopy?.primary_text)" type="button"
                                            class="mt-2 text-blue-600 hover:text-blue-700 text-xs">{{ __('campaigns.ai_modal.use_this_text') }}</button>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-700 mb-1">{{ __('campaigns.ai_modal.ctas') }}</p>
                                <template x-for="(cta, index) in generatedCopy?.call_to_actions || []" :key="index">
                                    <span class="inline-block px-2 py-1 bg-100 text-blue-800 text-xs rounded me-2 mb-1" x-text="cta"></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Ad Design Generation --}}
                    <div x-show="aiGenType === 'design'" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('campaigns.ai_modal.brand_guidelines') }}</label>
                            <textarea x-model="aiBrandGuidelines" rows="2"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                      placeholder="{{ __('campaigns.ai_modal.brand_guidelines_placeholder') }}"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('campaigns.ai_modal.design_requirements') }}</label>
                            <input type="text" x-model="aiDesignRequirement1"
                                   class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="{{ __('campaigns.ai_modal.design_req_placeholder_1') }}">
                            <input type="text" x-model="aiDesignRequirement2"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="{{ __('campaigns.ai_modal.design_req_placeholder_2') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('campaigns.ai_modal.variations') }}</label>
                            <select x-model="aiVariations" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option value="2">{{ __('campaigns.ai_modal.variations_2') }}</option>
                                <option value="3" selected>{{ __('campaigns.ai_modal.variations_3') }}</option>
                                <option value="4">{{ __('campaigns.ai_modal.variations_4') }}</option>
                            </select>
                        </div>

                        <button @click="generateAdDesign" type="button"
                                :disabled="aiGenerating"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                            <span x-show="!aiGenerating">{{ __('campaigns.ai_modal.generate_designs') }}</span>
                            <span x-show="aiGenerating">
                                <svg class="animate-spin h-5 w-5 text-white me-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('campaigns.ai_modal.generating_designs') }}
                            </span>
                        </button>

                        {{-- Generated Designs --}}
                        <div x-show="generatedDesigns?.length" class="mt-4 grid grid-cols-3 gap-2">
                            <template x-for="(design, index) in generatedDesigns" :key="index">
                                <div class="relative group">
                                    <img :src="design.url" :alt="'Design ' + (index + 1)" class="w-full h-32 object-cover rounded">
                                    <button @click="useDesign(design)" type="button"
                                            class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 text-white text-xs font-medium rounded">
                                        {{ __('campaigns.ai_modal.use_this') }}
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Boost Post Modal --}}
    <div x-show="showBoostModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeBoostModal"></div>

            <div class="relative bg-white rounded-lg max-w-4xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('campaigns.boost_post.title') }}</h3>
                    <button @click="closeBoostModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Platform Filter --}}
                <div class="mb-4 flex gap-2">
                    <button @click="fetchPosts('all')" type="button"
                            :class="postsPlatform === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'"
                            class="px-3 py-1 rounded-md text-sm font-medium">
                        {{ __('campaigns.boost_post.all_posts') }}
                    </button>
                    <button @click="fetchPosts('facebook')" type="button"
                            :class="postsPlatform === 'facebook' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'"
                            class="px-3 py-1 rounded-md text-sm font-medium">
                        {{ __('campaigns.platforms.facebook') }}
                    </button>
                    <button @click="fetchPosts('instagram')" type="button"
                            :class="postsPlatform === 'instagram' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'"
                            class="px-3 py-1 rounded-md text-sm font-medium">
                        {{ __('campaigns.platforms.instagram') }}
                    </button>
                </div>

                {{-- Loading State --}}
                <div x-show="postsLoading" class="text-center py-8">
                    <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-600 mt-2">{{ __('campaigns.boost_post.loading_posts') }}</p>
                </div>

                {{-- Posts Grid --}}
                <div x-show="!postsLoading" class="grid md:grid-cols-2 gap-4">
                    <template x-for="post in availablePosts" :key="post.id">
                        <div class="border rounded-lg p-4 hover:border-blue-500 cursor-pointer transition"
                             @click="selectPost(post)">
                            {{-- Post Media --}}
                            <div x-show="post.media_url" class="aspect-video bg-gray-100 rounded mb-3 overflow-hidden">
                                <img :src="post.media_url" :alt="post.message" class="w-full h-full object-cover">
                            </div>

                            {{-- Post Content --}}
                            <p class="text-sm text-gray-900 mb-2 line-clamp-3" x-text="post.message"></p>

                            {{-- Engagement Stats --}}
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 me-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                    </svg>
                                    <span x-text="post.engagement.likes"></span>
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 me-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                    </svg>
                                    <span x-text="post.engagement.comments"></span>
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 me-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"/>
                                    </svg>
                                    <span x-text="post.engagement.shares || 0"></span>
                                </span>
                            </div>

                            {{-- Platform Badge --}}
                            <div class="mt-2">
                                <span :class="post.platform === 'facebook' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize">
                                    <span x-text="post.platform"></span>
                                </span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- No Posts Message --}}
                <div x-show="!postsLoading && availablePosts.length === 0" class="text-center py-8">
                    <p class="text-gray-600">{{ __('campaigns.boost_post.no_posts_found') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview Section --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">{{ __('campaigns.preview') }}</h4>
        <div class="bg-white border border-gray-200 rounded-lg p-4 max-w-md">
            <div class="aspect-video bg-gray-100 rounded mb-3 flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-900 mb-2" x-text="primaryText || '{{ __('campaigns.wizard.creative.preview_placeholder') }}'"></p>
            <p class="text-xs text-gray-500">{{ __('campaigns.wizard.creative.preview_note') }}</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function creativeStep() {
    return {
        adFormat: '{{ old('ad_format', $session['data']['ad_format'] ?? '') }}',
        primaryText: '{{ old('primary_text', $session['data']['primary_text'] ?? '') }}',
        uploadedFiles: [],

        // AI Generation Modal
        showAiModal: false,
        aiGenType: 'copy',
        aiGenerating: false,
        aiPrompt: '',
        aiTargetAudience: '',
        aiBrandGuidelines: '',
        aiDesignRequirement1: '',
        aiDesignRequirement2: '',
        aiVariations: 3,
        generatedCopy: null,
        generatedDesigns: [],

        // Boost Post Modal
        showBoostModal: false,
        postsLoading: false,
        postsPlatform: 'all',
        availablePosts: [],

        updateCharCount() {
            // Character count is handled by x-model
        },

        handleFileUpload(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.uploadedFiles.push({
                            name: file.name,
                            preview: e.target.result,
                            file: file
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        removeFile(index) {
            this.uploadedFiles.splice(index, 1);
        },

        // AI Generation Functions
        openAiGenerationModal() {
            this.showAiModal = true;
            this.aiGenType = 'copy';
        },

        closeAiModal() {
            this.showAiModal = false;
        },

        async generateAdCopy() {
            if (!this.aiPrompt || !this.aiTargetAudience) {
                alert('{{ __("campaigns.ai_modal.fill_required_fields") }}');
                return;
            }

            this.aiGenerating = true;
            this.generatedCopy = null;

            try {
                const response = await fetch('/api/ai/generate-ad-copy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    },
                    body: JSON.stringify({
                        objective: '{{ $session['data']['objective'] ?? 'awareness' }}',
                        target_audience: this.aiTargetAudience,
                        product_description: this.aiPrompt,
                        requirements: []
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.generatedCopy = data.ad_copy;
                } else {
                    alert(data.message || '{{ __("campaigns.ai_modal.generate_failed") }}');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('{{ __("campaigns.ai_modal.generate_failed") }}');
            } finally {
                this.aiGenerating = false;
            }
        },

        async generateAdDesign() {
            if (!this.aiBrandGuidelines) {
                alert('{{ __("campaigns.ai_modal.provide_brand_guidelines") }}');
                return;
            }

            this.aiGenerating = true;
            this.generatedDesigns = [];

            const requirements = [this.aiDesignRequirement1, this.aiDesignRequirement2].filter(r => r);

            try {
                const response = await fetch('/api/ai/generate-ad-design', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    },
                    body: JSON.stringify({
                        objective: '{{ $session['data']['objective'] ?? 'awareness' }}',
                        brand_guidelines: this.aiBrandGuidelines,
                        design_requirements: requirements,
                        variation_count: parseInt(this.aiVariations),
                        resolution: 'high'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.generatedDesigns = data.designs;
                } else {
                    alert(data.message || '{{ __("campaigns.ai_modal.design_failed") }}');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('{{ __("campaigns.ai_modal.design_failed") }}');
            } finally {
                this.aiGenerating = false;
            }
        },

        applyHeadline(headline) {
            document.getElementById('headline').value = headline;
            this.closeAiModal();
        },

        applyPrimaryText(text) {
            this.primaryText = text;
            document.getElementById('primary_text').value = text;
            this.closeAiModal();
        },

        useDesign(design) {
            // Add design URL to media URLs
            const mediaUrlsField = document.getElementById('media_urls_text');
            const currentUrls = mediaUrlsField.value ? mediaUrlsField.value.split('\n') : [];
            currentUrls.push(design.url);
            mediaUrlsField.value = currentUrls.join('\n');
            this.closeAiModal();
            alert('{{ __("campaigns.boost_post.design_added") }}');
        },

        // Boost Post Functions
        openBoostPostModal() {
            this.showBoostModal = true;
            this.fetchPosts('all');
        },

        closeBoostModal() {
            this.showBoostModal = false;
        },

        async fetchPosts(platform) {
            this.postsPlatform = platform;
            this.postsLoading = true;
            this.availablePosts = [];

            try {
                const response = await fetch(`/api/meta-posts?platform=${platform}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Combine Facebook and Instagram posts
                    const allPosts = [
                        ...(data.posts.facebook || []),
                        ...(data.posts.instagram || [])
                    ];
                    this.availablePosts = allPosts;
                } else {
                    console.error('Failed to fetch posts:', data);
                }
            } catch (error) {
                console.error('Error fetching posts:', error);
            } finally {
                this.postsLoading = false;
            }
        },

        selectPost(post) {
            // Fill form with post content
            this.primaryText = post.message;
            document.getElementById('primary_text').value = post.message;

            // Add post media to URLs
            if (post.media_url) {
                const mediaUrlsField = document.getElementById('media_urls_text');
                const currentUrls = mediaUrlsField.value ? mediaUrlsField.value.split('\n') : [];
                currentUrls.push(post.media_url);
                mediaUrlsField.value = currentUrls.join('\n');
            }

            this.closeBoostModal();
            alert('{{ __("campaigns.boost_post.post_loaded") }}');
        }
    }
}

// Convert media URLs to array on form submit
document.querySelector('form').addEventListener('submit', function(e) {
    const mediaUrlsText = document.getElementById('media_urls_text').value;
    if (mediaUrlsText) {
        const urls = mediaUrlsText.split('\n').map(u => u.trim()).filter(u => u);
        urls.forEach(url => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'media_urls[]';
            input.value = url;
            this.appendChild(input);
        });
    }
});
</script>
@endpush
