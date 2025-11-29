{{-- Column 3: Preview (Left side in RTL) --}}
<div class="w-80 flex-shrink-0 bg-white flex flex-col">
    <div class="flex-shrink-0 px-4 py-3 border-b border-gray-200 bg-white">
        <h3 class="text-sm font-medium text-gray-900">{{ __('publish.preview') }}</h3>
    </div>

    <div class="flex-1 overflow-y-auto p-4">
        {{-- Platform Preview Selector --}}
        <div class="flex gap-2 mb-4">
            <template x-for="platform in getSelectedPlatforms()" :key="platform">
                <button @click="previewPlatform = platform"
                        :class="previewPlatform === platform ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                        class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 transition">
                    <i :class="getPlatformIcon(platform)"></i>
                </button>
            </template>
        </div>

        {{-- ENHANCED: Mobile/Desktop Preview Toggle --}}
        <div class="flex items-center justify-center gap-2 mb-4">
            <button @click="previewMode = 'mobile'"
                    :class="previewMode === 'mobile' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                    class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 transition">
                <i class="fas fa-mobile-alt me-1"></i>{{ __('publish.mobile_preview') }}
            </button>
            <button @click="previewMode = 'desktop'"
                    :class="previewMode === 'desktop' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                    class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 transition">
                <i class="fas fa-desktop me-1"></i>{{ __('publish.desktop_preview') }}
            </button>
        </div>

        {{-- PHASE 2: Performance Predictions --}}
        <template x-if="true">
            <div x-show="content.global.text.length > 20 || content.global.media.length > 0"
                 x-cloak
                 x-transition
                 class="mb-4 p-3 bg-gradient-to-br from-purple-50 to-blue-50 border border-purple-200 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-chart-line text-purple-600"></i>
                <h4 class="text-xs font-semibold text-purple-900">{{ __('publish.performance_prediction') }}</h4>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-600">{{ __('publish.predicted_reach') }}</span>
                    <span class="font-semibold text-gray-900" x-text="getPredictedReach()"></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-600">{{ __('publish.predicted_engagement') }}</span>
                    <span class="font-semibold text-gray-900" x-text="getPredictedEngagement()"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                    <div class="bg-gradient-to-r from-purple-600 to-blue-600 h-1.5 rounded-full transition-all duration-500"
                         :style="'width: ' + getContentQualityScore() + '%'"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-lightbulb text-yellow-500 me-1"></i>
                    <span x-text="getOptimizationTip()"></span>
                </p>
            </div>
            </div>
        </template>

        {{-- PHASE 2: Template Library --}}
        <template x-if="true">
            <div x-show="content.global.text.length > 0 || content.global.media.length > 0"
                 x-cloak
                 x-transition
                 class="mb-4 p-3 bg-gradient-to-br from-green-50 to-teal-50 border border-green-200 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <i class="fas fa-bookmark text-green-600"></i>
                    <h4 class="text-xs font-semibold text-green-900">{{ __('publish.template_library') }}</h4>
                </div>
                <button @click="showTemplateLibrary = !showTemplateLibrary"
                        class="text-xs text-green-700 hover:text-green-800 transition">
                    <i :class="showTemplateLibrary ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                </button>
            </div>

            <div x-show="showTemplateLibrary" x-transition class="space-y-2">
                {{-- Save as Template --}}
                <div class="flex gap-2">
                    <input type="text" x-model="newTemplateName"
                           placeholder="{{ __('publish.template_name') }}"
                           class="flex-1 px-2 py-1.5 text-xs border border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <button @click="saveAsTemplate()"
                            :disabled="!newTemplateName.trim()"
                            :class="newTemplateName.trim() ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed'"
                            class="px-3 py-1.5 text-xs font-medium text-white rounded-lg transition">
                        <i class="fas fa-save"></i>
                    </button>
                </div>

                {{-- Saved Templates List --}}
                <div x-show="savedTemplates.length > 0" class="space-y-1.5 mt-3">
                    <p class="text-xs text-gray-600 font-medium">{{ __('publish.saved_templates') }}</p>
                    <template x-for="(template, index) in savedTemplates" :key="template.id">
                        <div class="flex items-center justify-between p-2 bg-white border border-green-100 rounded-lg hover:border-green-300 transition group">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 truncate" x-text="template.name"></p>
                                <p class="text-xs text-gray-500" x-text="'Saved ' + formatDate(template.created_at)"></p>
                            </div>
                            <div class="flex items-center gap-1 ms-2">
                                <button @click="loadTemplate(template)"
                                        class="p-1.5 text-green-600 hover:bg-green-50 rounded transition"
                                        title="{{ __('publish.load_template') }}">
                                    <i class="fas fa-upload text-xs"></i>
                                </button>
                                <button @click="deleteTemplate(template.id)"
                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded transition opacity-0 group-hover:opacity-100"
                                        title="{{ __('publish.delete_template') }}">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- No Templates Message --}}
                <div x-show="savedTemplates.length === 0" class="text-center py-3">
                    <i class="fas fa-bookmark text-gray-300 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-500">{{ __('publish.no_templates') }}</p>
                </div>
            </div>
            </div>
        </template>

        {{-- ENHANCED: Preview with Mobile Phone Frame --}}
        <div x-show="previewMode === 'mobile'" class="flex justify-center">
            {{-- iPhone Frame --}}
            <div class="relative w-full max-w-[280px]">
                {{-- Phone Frame Border --}}
                <div class="relative bg-gray-900 rounded-[2.5rem] p-2 shadow-2xl">
                    {{-- Phone Notch --}}
                    <div class="absolute top-0 inset-x-0 h-6 flex justify-center z-10">
                        <div class="bg-gray-900 rounded-b-2xl w-36 h-5"></div>
                    </div>

                    {{-- Phone Screen --}}
                    <div class="bg-white rounded-[2rem] overflow-hidden relative" style="aspect-ratio: 9/19.5;">
                        {{-- Status Bar --}}
                        <div class="bg-white px-4 pt-2 pb-1 flex items-center justify-between text-xs">
                            <span class="font-semibold">9:41</span>
                            <div class="flex items-center gap-1">
                                <i class="fas fa-signal"></i>
                                <i class="fas fa-wifi"></i>
                                <i class="fas fa-battery-full"></i>
                            </div>
                        </div>

                        {{-- App Header (Platform-specific) --}}
                        <div class="bg-white border-b border-gray-100 px-3 py-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i :class="getPlatformIcon(previewPlatform)" class="text-lg"></i>
                                <span class="font-semibold text-sm" x-text="previewPlatform.charAt(0).toUpperCase() + previewPlatform.slice(1)"></span>
                            </div>
                            <i class="fas fa-ellipsis-h text-gray-500"></i>
                        </div>

                        {{-- Post Content in Phone --}}
                        <div class="bg-white overflow-y-auto" style="max-height: calc(100% - 80px);">
                            {{-- Profile Header --}}
                            <div class="p-3 flex items-center gap-2">
                                <img :src="getPreviewProfile()?.avatar_url || '/img/default-avatar.png'"
                                     class="w-9 h-9 rounded-full">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-900 truncate" x-text="getPreviewProfile()?.account_name || 'Account Name'"></p>
                                    <p class="text-[10px] text-gray-500" x-text="getPreviewTime()"></p>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="px-3 pb-2">
                                <p class="text-xs text-gray-800 whitespace-pre-wrap break-words" x-text="getPreviewContent()"></p>
                            </div>

                            {{-- Media Preview --}}
                            <template x-if="content.global.media.length > 0">
                                <div class="bg-gray-100" style="aspect-ratio: 1/1;">
                                    <img :src="content.global.media[0]?.preview_url" class="w-full h-full object-cover">
                                </div>
                            </template>

                            {{-- Engagement Mockup --}}
                            <div class="px-3 py-2 border-t border-gray-100 flex items-center gap-4 text-gray-500">
                                <span class="flex items-center gap-1 text-xs"><i class="far fa-heart"></i> 0</span>
                                <span class="flex items-center gap-1 text-xs"><i class="far fa-comment"></i> 0</span>
                                <span class="flex items-center gap-1 text-xs"><i class="far fa-share-square"></i> 0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Desktop Preview Mode (Original Card) --}}
        <div x-show="previewMode === 'desktop'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Profile Header --}}
            <div class="p-3 flex items-center gap-3">
                <img :src="getPreviewProfile()?.avatar_url || '/img/default-avatar.png'"
                     class="w-10 h-10 rounded-full">
                <div>
                    <p class="text-sm font-semibold text-gray-900" x-text="getPreviewProfile()?.account_name || 'Account Name'"></p>
                    <p class="text-xs text-gray-500" x-text="getPreviewTime()"></p>
                </div>
            </div>

            {{-- Content --}}
            <div class="px-3 pb-3">
                <p class="text-sm text-gray-800 whitespace-pre-wrap" x-text="getPreviewContent()"></p>
            </div>

            {{-- Media Preview --}}
            <template x-if="content.global.media.length > 0">
                <div class="aspect-square bg-gray-100">
                    <img :src="content.global.media[0]?.preview_url" class="w-full h-full object-cover">
                </div>
            </template>

            {{-- Engagement Mockup --}}
            <div class="p-3 border-t border-gray-100 flex items-center gap-4 text-gray-500">
                <span class="flex items-center gap-1 text-sm"><i class="far fa-heart"></i> 0</span>
                <span class="flex items-center gap-1 text-sm"><i class="far fa-comment"></i> 0</span>
                <span class="flex items-center gap-1 text-sm"><i class="far fa-share-square"></i> 0</span>
            </div>
        </div>

        {{-- Brand Safety Check --}}
        <div class="mt-4 p-3 rounded-lg" :class="brandSafetyStatus === 'pass' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
            <div class="flex items-center gap-2">
                <i :class="brandSafetyStatus === 'pass' ? 'fas fa-check-circle text-green-500' : 'fas fa-exclamation-triangle text-red-500'"></i>
                <span class="text-sm font-medium" :class="brandSafetyStatus === 'pass' ? 'text-green-700' : 'text-red-700'"
                      x-text="brandSafetyStatus === 'pass' ? '{{ __('publish.brand_compliant') }}' : '{{ __('publish.content_issues') }}'"></span>
            </div>
            <template x-if="brandSafetyIssues.length > 0">
                <ul class="mt-2 text-xs text-red-600 space-y-1">
                    <template x-for="issue in brandSafetyIssues" :key="issue">
                        <li class="flex items-start gap-1">
                            <i class="fas fa-times mt-0.5"></i>
                            <span x-text="issue"></span>
                        </li>
                    </template>
                </ul>
            </template>
        </div>
    </div>
</div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 bg-white">
{{-- Publish Mode Options - Segmented Controls Style --}}
<div x-show="!requiresApproval" class="mb-4 pb-3 border-b border-gray-200">
    <div class="flex flex-wrap items-start gap-3">
        <label class="flex items-center gap-2.5 cursor-pointer group px-3 py-2 rounded-lg hover:bg-indigo-50 transition-colors">
            <input type="radio" x-model="publishMode" value="publish_now" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 focus:ring-offset-0">
            <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-700">{{ __('publish.publish_now') }}</span>
        </label>
        <label class="flex items-center gap-2.5 cursor-pointer group px-3 py-2 rounded-lg hover:bg-green-50 transition-colors">
            <input type="radio" x-model="publishMode" value="schedule" @change="scheduleEnabled = true" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500 focus:ring-offset-0">
            <span class="text-sm font-medium text-gray-700 group-hover:text-green-700">{{ __('publish.schedule') }}</span>
        </label>
        <div class="flex flex-col gap-2">
            <label class="flex items-center gap-2.5 cursor-pointer group px-3 py-2 rounded-lg hover:bg-blue-50 transition-colors">
                <input type="radio" x-model="publishMode" value="add_to_queue" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-offset-0">
                <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700">{{ __('publish.add_to_queue') }}</span>
                <button type="button" class="text-gray-400 hover:text-blue-600 transition align-baseline" title="{{ __('publish.queue_help') }}">
                    <i class="fas fa-info-circle text-xs"></i>
                </button>
            </label>
            {{-- PHASE 5B: Queue Position Dropdown --}}
            <div x-show="publishMode === 'add_to_queue'" x-cloak x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="me-6">
                <select x-model="queuePosition"
                        class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gradient-to-l from-blue-50 to-white">
                    <option value="next">{{ __('publish.queue_next') }}</option>
                    <option value="available">{{ __('publish.queue_available') }}</option>
                    <option value="last">{{ __('publish.queue_last') }}</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- Validation Errors Display - Compact & User-Friendly --}}
<div x-show="validationErrors.length > 0 && !canSubmit" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     class="mb-3">
    <div class="px-3 py-2.5 bg-red-50 border border-red-300 rounded-lg flex items-start gap-2.5">
        <div class="flex-shrink-0 mt-0.5">
            <i class="fas fa-exclamation-circle text-red-600 text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <template x-for="(error, index) in validationErrors" :key="index">
                <p class="text-sm text-red-800 leading-snug" x-text="error"></p>
            </template>
        </div>
    </div>
</div>

<div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
        <template x-if="requiresApproval">
            <span class="text-sm text-yellow-600">
                <i class="fas fa-user-clock ms-1"></i>{{ __('publish.requires_approval') }}
            </span>
        </template>
    </div>
    <div class="flex items-center gap-3">
        <button @click="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            {{ __('publish.cancel') }}
        </button>
        <button @click="submitForApproval()" x-show="requiresApproval"
                :disabled="!canSubmit"
                :class="canSubmit ? 'bg-yellow-600 hover:bg-yellow-700 shadow-sm' : 'bg-gray-300 cursor-not-allowed'"
                class="px-4 py-2.5 text-sm font-medium text-white rounded-lg transition-all duration-200">
            <i class="fas fa-paper-plane ms-1"></i>{{ __('publish.submit_for_approval') }}
        </button>
        <button @click="publishNow()" x-show="!requiresApproval && publishMode === 'publish_now'"
                :disabled="!canSubmit"
                :class="canSubmit ? 'bg-gradient-to-l from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 shadow-sm' : 'bg-gray-300 cursor-not-allowed'"
                class="px-4 py-2.5 text-sm font-medium text-white rounded-lg transition-all duration-200">
            <i class="fas fa-paper-plane ms-1"></i>{{ __('publish.publish_now') }}
        </button>
        <button @click="schedulePost()" x-show="!requiresApproval && publishMode === 'schedule'"
                :disabled="!canSubmit"
                :class="canSubmit ? 'bg-green-600 hover:bg-green-700 shadow-sm' : 'bg-gray-300 cursor-not-allowed'"
                class="px-4 py-2.5 text-sm font-medium text-white rounded-lg transition-all duration-200">
            <i class="far fa-clock ms-1"></i>{{ __('publish.schedule_post') }}
        </button>
        <button @click="addToQueue()" x-show="!requiresApproval && publishMode === 'add_to_queue'"
                :disabled="!canSubmit"
                :class="canSubmit ? 'bg-blue-600 hover:bg-blue-700 shadow-sm' : 'bg-gray-300 cursor-not-allowed'"
                class="px-4 py-2.5 text-sm font-medium text-white rounded-lg transition-all duration-200">
            <i class="fas fa-stream ms-1"></i>{{ __('publish.add_to_queue') }}
        </button>
    </div>
</div>
            </div>
        </div>
    </div>

    {{-- AI Assistant Slide-over --}}
    <div x-show="showAIAssistant"
         x-cloak
         x-transition:enter="ease-out duration-300"
         class="fixed inset-y-0 start-0 w-96 bg-white shadow-2xl z-60 flex flex-col" dir="rtl">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gradient-to-l from-blue-600 to-purple-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-magic ms-2"></i>{{ __('publish.ai_assistant') }}</h3>
            <button @click="showAIAssistant = false" class="text-white/80 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            {{-- Brand Voice Selection --}}
            <div>
<label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.brand_voice') }}</label>
<select x-model="aiSettings.brandVoice" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
    <option value="">{{ __('publish.default') }}</option>
    <template x-for="voice in brandVoices" :key="voice.voice_id">
        <option :value="voice.voice_id" x-text="voice.name"></option>
    </template>
</select>
            </div>

            {{-- Tone --}}
            <div>
<label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.tone') }}</label>
<div class="grid grid-cols-2 gap-2">
    <button @click="aiSettings.tone = 'professional'"
            :class="aiSettings.tone === 'professional' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.professional') }}</button>
    <button @click="aiSettings.tone = 'friendly'"
            :class="aiSettings.tone === 'friendly' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.friendly') }}</button>
    <button @click="aiSettings.tone = 'casual'"
            :class="aiSettings.tone === 'casual' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.casual') }}</button>
    <button @click="aiSettings.tone = 'formal'"
            :class="aiSettings.tone === 'formal' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.formal') }}</button>
    <button @click="aiSettings.tone = 'humorous'"
            :class="aiSettings.tone === 'humorous' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.humorous') }}</button>
    <button @click="aiSettings.tone = 'inspirational'"
            :class="aiSettings.tone === 'inspirational' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.inspirational') }}</button>
</div>
            </div>

            {{-- Length --}}
            <div>
<label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.length') }}</label>
<div class="flex gap-2">
    <button @click="aiSettings.length = 'shorter'"
            :class="aiSettings.length === 'shorter' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="flex-1 px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.shorter') }}</button>
    <button @click="aiSettings.length = 'same'"
            :class="aiSettings.length === 'same' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="flex-1 px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.same_length') }}</button>
    <button @click="aiSettings.length = 'longer'"
            :class="aiSettings.length === 'longer' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            class="flex-1 px-3 py-2 text-sm rounded-lg border transition">{{ __('publish.longer') }}</button>
</div>
            </div>

            {{-- AI Prompt --}}
            <div>
<label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.custom_instructions') }}</label>
<textarea x-model="aiSettings.prompt" rows="3" placeholder="{{ __('publish.add_instructions') }}"
          class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"></textarea>
            </div>

            <button @click="generateWithAI()" :disabled="isGenerating"
    class="w-full px-4 py-2 bg-gradient-to-l from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition font-medium">
<span x-show="!isGenerating"><i class="fas fa-magic ms-2"></i>{{ __('publish.generate_content') }}</span>
<span x-show="isGenerating"><i class="fas fa-spinner fa-spin ms-2"></i>{{ __('publish.generating') }}</span>
            </button>

            {{-- AI Suggestions --}}
            <template x-if="aiSuggestions.length > 0">
<div class="space-y-3">
    <h4 class="text-sm font-medium text-gray-700">{{ __('publish.suggestions') }}</h4>
    <template x-for="(suggestion, index) in aiSuggestions" :key="index">
        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-300 transition cursor-pointer"
             @click="useSuggestion(suggestion)">
            <p class="text-sm text-gray-700" x-text="suggestion"></p>
        </div>
    </template>
</div>
            </template>
        </div>
    </div>

    {{-- PHASE 1: Hashtag Manager Slide-over --}}
