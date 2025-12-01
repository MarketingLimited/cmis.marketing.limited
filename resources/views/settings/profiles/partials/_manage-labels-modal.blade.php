{{-- Manage Labels Modal --}}
<div x-show="showManageLabelsModal"
     x-cloak
     @keydown.escape.window="showManageLabelsModal = false"
     class="fixed inset-0 z-[70] overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div x-show="showManageLabelsModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showManageLabelsModal = false"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

        {{-- Modal panel --}}
        <div x-show="showManageLabelsModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block w-full max-w-md my-8 overflow-hidden text-start align-middle transition-all transform bg-white rounded-lg shadow-xl">

            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ __('profiles.manage_labels') }}
                    </h3>
                    <button type="button"
                            @click="showManageLabelsModal = false"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                {{-- Search input with add button --}}
                <div class="flex items-center gap-2 mb-4">
                    <div class="relative flex-1">
                        <input type="text"
                               x-model="labelSearch"
                               placeholder="{{ __('profiles.search_labels') }}"
                               class="w-full px-4 py-2 ps-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm">
                        <svg class="absolute start-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button type="button"
                            @click="openLabelEditor(null)"
                            class="p-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>

                {{-- Labels list --}}
                <div class="space-y-2">
                    <template x-if="filteredLabels.length === 0">
                        <div class="text-center py-8">
                            <svg class="mx-auto w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <p class="text-sm text-gray-500 mb-2">{{ __('profiles.no_labels') }}</p>
                            <button type="button"
                                    @click="openLabelEditor(null)"
                                    class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                {{ __('profiles.add_label') }}
                            </button>
                        </div>
                    </template>

                    <template x-for="label in filteredLabels" :key="label.id">
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors group">
                            {{-- Label preview --}}
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1.5 rounded-full text-sm font-medium"
                                      :style="getLabelStyle(label.id)">
                                    <span x-text="label.name"></span>
                                </span>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button type="button"
                                        @click="openLabelEditor(label)"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        :title="'{{ __('profiles.edit_label') }}'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                                <button type="button"
                                        @click="confirmDeleteLabel(label)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                        :title="'{{ __('common.delete') }}'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
                <button type="button"
                        @click="showManageLabelsModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    {{ __('common.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
