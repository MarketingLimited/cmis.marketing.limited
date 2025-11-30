{{-- AI Assistant Overlay --}}
<div x-show="showAIAssistant"
     class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-[200]"
     @click.self="showAIAssistant = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden mx-4"
         @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 flex items-center justify-center">
                    <i class="fas fa-magic text-white"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('publish.ai_assistant') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('publish.ai_assistant_subtitle') }}</p>
                </div>
            </div>
            <button @click="showAIAssistant = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-white/50 rounded-lg transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            {{-- Quick Actions --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('publish.quick_actions') }}</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button @click="improveTone()"
                            :disabled="isGenerating || !content.global.text.trim()"
                            class="px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-wand-magic-sparkles me-1"></i>
                        {{ __('publish.improve_tone') }}
                    </button>
                    <button @click="makeMoreEngaging()"
                            :disabled="isGenerating || !content.global.text.trim()"
                            class="px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-fire me-1"></i>
                        {{ __('publish.make_engaging') }}
                    </button>
                    <button @click="shortenContent()"
                            :disabled="isGenerating || !content.global.text.trim()"
                            class="px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-compress me-1"></i>
                        {{ __('publish.shorten') }}
                    </button>
                    <button @click="expandContent()"
                            :disabled="isGenerating || !content.global.text.trim()"
                            class="px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-expand me-1"></i>
                        {{ __('publish.expand') }}
                    </button>
                    <button @click="addHashtags()"
                            :disabled="isGenerating || !content.global.text.trim()"
                            class="px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-hashtag me-1"></i>
                        {{ __('publish.add_hashtags') }}
                    </button>
                    <button @click="addEmojis()"
                            :disabled="isGenerating || !content.global.text.trim()"
                            class="px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="far fa-smile me-1"></i>
                        {{ __('publish.add_emojis') }}
                    </button>
                    <button @click="translateContent('Arabic')"
                            :disabled="isGenerating || !content.global.text.trim()"
                            class="px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-language me-1"></i>
                        {{ __('publish.translate_ar') }}
                    </button>
                    <button @click="translateContent('English')"
                            :disabled="isGenerating || !content.global.text.trim()"
                            class="px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-language me-1"></i>
                        {{ __('publish.translate_en') }}
                    </button>
                </div>
            </div>

            {{-- AI Settings --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Brand Voice --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.brand_voice') }}</label>
                    <select x-model="aiSettings.brandVoice"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">{{ __('publish.select_brand_voice') }}</option>
                        <template x-for="voice in (brandVoices || [])" :key="voice.id || voice.name">
                            <option :value="voice.id" x-text="voice.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Tone --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.tone') }}</label>
                    <select x-model="aiSettings.tone"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="professional">{{ __('publish.tone_professional') }}</option>
                        <option value="casual">{{ __('publish.tone_casual') }}</option>
                        <option value="friendly">{{ __('publish.tone_friendly') }}</option>
                        <option value="formal">{{ __('publish.tone_formal') }}</option>
                        <option value="humorous">{{ __('publish.tone_humorous') }}</option>
                        <option value="inspirational">{{ __('publish.tone_inspirational') }}</option>
                    </select>
                </div>

                {{-- Length --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.length') }}</label>
                    <select x-model="aiSettings.length"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="same">{{ __('publish.length_same') }}</option>
                        <option value="short">{{ __('publish.length_short') }}</option>
                        <option value="medium">{{ __('publish.length_medium') }}</option>
                        <option value="long">{{ __('publish.length_long') }}</option>
                    </select>
                </div>
            </div>

            {{-- Custom Prompt --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.custom_instructions') }}</label>
                <textarea x-model="aiSettings.prompt"
                          rows="2"
                          class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                          placeholder="{{ __('publish.custom_instructions_placeholder') }}"></textarea>
            </div>

            {{-- Generate Button --}}
            <div class="flex justify-center">
                <button @click="generateWithAI()"
                        :disabled="isGenerating || !content.global.text.trim()"
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <template x-if="isGenerating">
                        <span class="flex items-center gap-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                            {{ __('publish.generating') }}
                        </span>
                    </template>
                    <template x-if="!isGenerating">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-sparkles"></i>
                            {{ __('publish.generate_with_ai') }}
                        </span>
                    </template>
                </button>
            </div>

            {{-- AI Suggestions --}}
            <template x-if="aiSuggestions && aiSuggestions.length > 0">
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700">{{ __('publish.ai_suggestions') }}</label>
                    <template x-for="(suggestion, index) in (aiSuggestions || [])" :key="index">
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 hover:border-blue-300 transition cursor-pointer group"
                             @click="useSuggestion(suggestion)">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap" x-text="suggestion"></p>
                            <div class="mt-2 flex items-center justify-end">
                                <span class="text-xs text-blue-600 opacity-0 group-hover:opacity-100 transition">
                                    <i class="fas fa-check me-1"></i>
                                    {{ __('publish.click_to_use') }}
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="(!aiSuggestions || aiSuggestions.length === 0) && !isGenerating">
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-robot text-2xl text-blue-500"></i>
                    </div>
                    <p class="text-gray-500 text-sm">{{ __('publish.ai_empty_state') }}</p>
                    <p class="text-gray-400 text-xs mt-1">{{ __('publish.ai_empty_state_hint') }}</p>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-end gap-3">
            <button @click="showAIAssistant = false"
                    class="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium transition">
                {{ __('publish.close') }}
            </button>
        </div>
    </div>
</div>
