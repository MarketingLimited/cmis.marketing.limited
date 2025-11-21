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

    {{-- AI Content Generation Helper --}}
    <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-purple-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-purple-900 mb-1">
                    {{ __('campaigns.wizard.creative.ai_helper_title') }}
                </h4>
                <p class="text-sm text-purple-700 mb-3">
                    {{ __('campaigns.wizard.creative.ai_helper_description') }}
                </p>
                <button type="button" @click="openAiHelper"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    {{ __('campaigns.generate_with_ai') }}
                </button>
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

        openAiHelper() {
            // This would open a modal or navigate to AI content generation
            alert('{{ __('campaigns.wizard.creative.ai_helper_coming_soon') }}');
            // In production, this would call: window.location.href = '/ai/generate?context=campaign';
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
