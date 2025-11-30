{{-- Column 3: Preview (Left side in RTL) --}}
{{-- Width is controlled by parent wrapper, this just fills it --}}
<div class="w-full h-full bg-white flex flex-col overflow-hidden">
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
