{{-- Label Editor Modal --}}
<div x-show="showLabelEditorModal"
     x-cloak
     @keydown.escape.window="showLabelEditorModal = false"
     class="fixed inset-0 z-[80] overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div x-show="showLabelEditorModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showLabelEditorModal = false"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

        {{-- Modal panel --}}
        <div x-show="showLabelEditorModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block w-full max-w-lg my-8 overflow-hidden text-start align-middle transition-all transform bg-white rounded-lg shadow-xl">

            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    <span x-text="editingLabel ? '{{ __('profiles.edit_label') }}' : '{{ __('profiles.add_label') }}'"></span>
                </h3>
                {{-- Live Preview --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">{{ __('profiles.label_preview') }}:</span>
                    <span class="px-3 py-1.5 rounded-full text-sm font-medium transition-all"
                          :style="getEditorLabelStyle()">
                        <span x-text="labelEditorData.name || '{{ __('profiles.label_name') }}'"></span>
                    </span>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                {{-- Label Name --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('profiles.label_name') }}
                    </label>
                    <input type="text"
                           x-model="labelEditorData.name"
                           placeholder="{{ __('profiles.label_name_placeholder') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           maxlength="100">
                </div>

                {{-- Color Type Tabs --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('profiles.select_background_color') }}
                    </label>
                    <div class="flex border-b border-gray-200 mb-4">
                        <button type="button"
                                @click="labelEditorData.color_type = 'solid'"
                                :class="labelEditorData.color_type === 'solid' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors">
                            {{ __('profiles.solid_color') }}
                        </button>
                        <button type="button"
                                @click="labelEditorData.color_type = 'gradient'"
                                :class="labelEditorData.color_type === 'gradient' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors">
                            {{ __('profiles.gradient_color') }}
                        </button>
                    </div>

                    {{-- Solid Color Swatches --}}
                    <div x-show="labelEditorData.color_type === 'solid'" class="grid grid-cols-5 gap-3">
                        <template x-for="color in solidColors" :key="color.value">
                            <button type="button"
                                    @click="labelEditorData.background_color = color.value"
                                    :class="labelEditorData.background_color === color.value ? 'ring-2 ring-offset-2 ring-purple-500' : ''"
                                    :style="{ backgroundColor: color.value }"
                                    class="w-12 h-12 rounded-lg transition-all hover:scale-105 focus:outline-none"
                                    :title="color.name">
                                <span x-show="labelEditorData.background_color === color.value" class="flex items-center justify-center h-full">
                                    <svg class="w-5 h-5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            </button>
                        </template>
                    </div>

                    {{-- Gradient Swatches --}}
                    <div x-show="labelEditorData.color_type === 'gradient'" class="grid grid-cols-5 gap-3">
                        <template x-for="gradient in gradientPresets" :key="gradient.name">
                            <button type="button"
                                    @click="labelEditorData.gradient_start = gradient.start; labelEditorData.gradient_end = gradient.end"
                                    :class="labelEditorData.gradient_start === gradient.start && labelEditorData.gradient_end === gradient.end ? 'ring-2 ring-offset-2 ring-purple-500' : ''"
                                    :style="{ background: 'linear-gradient(135deg, ' + gradient.start + ', ' + gradient.end + ')' }"
                                    class="w-12 h-12 rounded-lg transition-all hover:scale-105 focus:outline-none"
                                    :title="gradient.name">
                                <span x-show="labelEditorData.gradient_start === gradient.start && labelEditorData.gradient_end === gradient.end" class="flex items-center justify-center h-full">
                                    <svg class="w-5 h-5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Text Color --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('profiles.select_text_color') }}
                    </label>
                    <div class="flex gap-3">
                        <template x-for="color in textColors" :key="color.value">
                            <button type="button"
                                    @click="labelEditorData.text_color = color.value"
                                    :class="[
                                        labelEditorData.text_color === color.value ? 'ring-2 ring-offset-2 ring-purple-500' : '',
                                        color.value === '#FFFFFF' ? 'border border-gray-300' : ''
                                    ]"
                                    :style="{ backgroundColor: color.value }"
                                    class="w-10 h-10 rounded-full transition-all hover:scale-105 focus:outline-none"
                                    :title="color.name">
                                <span x-show="labelEditorData.text_color === color.value" class="flex items-center justify-center h-full">
                                    <svg class="w-4 h-4" :class="color.value === '#FFFFFF' ? 'text-gray-800' : 'text-white'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                <div>
                    <button x-show="editingLabel"
                            type="button"
                            @click="confirmDeleteLabel(editingLabel)"
                            class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 transition-colors">
                        {{ __('common.delete') }}
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="showLabelEditorModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button"
                            @click="saveLabel()"
                            :disabled="!labelEditorData.name || isSavingLabel"
                            class="px-6 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg x-show="isSavingLabel" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isSavingLabel ? '{{ __('common.saving') }}' : '{{ __('common.save') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
