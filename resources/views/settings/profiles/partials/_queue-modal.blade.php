{{-- Queue Settings Component Wrapper --}}
<div x-data="queueSettingsForm()">
{{-- Queue Settings Modal --}}
<div x-show="showQueueModal"
     x-cloak
     @keydown.escape.window="showQueueModal = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div x-show="showQueueModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showQueueModal = false"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

        {{-- Modal panel --}}
        <div x-show="showQueueModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block w-full max-w-4xl my-8 overflow-hidden text-start align-middle transition-all transform bg-white rounded-lg shadow-xl">

            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ __('profiles.queue_settings') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('profiles.queue_settings_description') }}
                        </p>
                    </div>
                    <button type="button"
                            @click="showQueueModal = false"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                <form @submit.prevent="saveQueueSettings">
                    {{-- Enable Queue Toggle --}}
                    <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox"
                                               x-model="queueEnabled"
                                               class="sr-only peer">
                                        <div class="w-14 h-8 bg-gray-300 rounded-full peer peer-checked:bg-blue-600 transition-colors"></div>
                                        <div class="absolute start-1 top-1 w-6 h-6 bg-white rounded-full transition-transform peer-checked:translate-x-6 rtl:peer-checked:-translate-x-6"></div>
                                    </div>
                                    <span class="ms-3 text-base font-semibold text-gray-900">
                                        {{ __('profiles.enable_publishing_queue') }}
                                    </span>
                                </label>
                                <p class="mt-2 ms-17 text-sm text-gray-600">
                                    {{ __('profiles.queue_enabled_description') }}
                                </p>
                            </div>
                            <div x-show="queueEnabled" class="text-end ms-4">
                                <div class="text-2xl font-bold text-blue-600" x-text="getTotalSlots()"></div>
                                <div class="text-xs text-gray-600 uppercase tracking-wide">{{ __('profiles.time_slots') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions with Labels Filter --}}
                    <div x-show="queueEnabled" class="mb-6 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    @click="openEnhancedTimePicker()"
                                    class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                                <svg class="inline-block w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ __('profiles.add_time_slot') }}
                            </button>
                            <button type="button"
                                    @click="copyToWeekdays()"
                                    class="px-3 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-200">
                                <svg class="inline-block w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                {{ __('profiles.apply_to_weekdays') }}
                            </button>
                            <button type="button"
                                    @click="clearAllTimes()"
                                    class="px-3 py-2 text-sm font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors border border-red-200">
                                <svg class="inline-block w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                {{ __('profiles.clear_all') }}
                            </button>
                        </div>

                        {{-- Labels Filter --}}
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">{{ __('profiles.labels') }}</span>
                            <select x-model="selectedLabelFilter"
                                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('profiles.any_labels') }}</option>
                                <template x-for="label in queueLabels" :key="label.id">
                                    <option :value="label.id" x-text="label.name"></option>
                                </template>
                            </select>
                            {{-- Manage Labels Button --}}
                            <button type="button"
                                    @click="showManageLabelsModal = true"
                                    class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded transition-colors"
                                    title="{{ __('profiles.manage_labels') }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Days and Time Slots --}}
                    <div x-show="queueEnabled" class="space-y-4">
                        <template x-for="(day, dayKey) in days" :key="dayKey">
                            <div class="border border-gray-200 rounded-lg overflow-hidden hover:border-blue-300 transition-colors"
                                 :class="{'bg-gray-50 opacity-60': !day.enabled, 'bg-white': day.enabled}">
                                {{-- Day Header --}}
                                <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            {{-- Day Toggle --}}
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                       x-model="day.enabled"
                                                       class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-green-500 transition-colors"></div>
                                                <div class="absolute start-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></div>
                                            </label>
                                            {{-- Day Name --}}
                                            <span class="text-base font-semibold text-gray-900" x-text="day.name"></span>
                                            {{-- Time Count Badge --}}
                                            <span x-show="day.enabled && getFilteredSlots(dayKey).length > 0"
                                                  class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full"
                                                  x-text="getFilteredSlots(dayKey).length + ' ' + (getFilteredSlots(dayKey).length === 1 ? '{{ __('profiles.time_slot') }}' : '{{ __('profiles.time_slots') }}')">
                                            </span>
                                        </div>
                                        {{-- Add Time Button --}}
                                        <button type="button"
                                                x-show="day.enabled"
                                                @click="openEnhancedTimePicker(dayKey)"
                                                class="px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                                            <svg class="inline-block w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            {{ __('profiles.add_time') }}
                                        </button>
                                    </div>
                                </div>

                                {{-- Time Slots --}}
                                <div x-show="day.enabled" class="px-4 py-3">
                                    <div x-show="getFilteredSlots(dayKey).length === 0" class="text-center py-8">
                                        <svg class="mx-auto w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm text-gray-500">{{ __('profiles.no_times_set') }}</p>
                                        <button type="button"
                                                @click="openEnhancedTimePicker(dayKey)"
                                                class="mt-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                                            {{ __('profiles.add_first_time') }}
                                        </button>
                                    </div>

                                    <div x-show="getFilteredSlots(dayKey).length > 0" class="flex flex-wrap gap-2">
                                        <template x-for="(slot, slotIndex) in getFilteredSlots(dayKey)" :key="slotIndex">
                                            <div class="group relative inline-flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                                {{-- Clock Icon --}}
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{-- Time --}}
                                                <span class="text-sm font-medium text-gray-900" x-text="getSlotTime(slot)"></span>
                                                {{-- Label Badge --}}
                                                <template x-if="getSlotLabelId(slot)">
                                                    <span class="px-2 py-0.5 text-xs rounded-full font-medium"
                                                          :style="getLabelStyle(getSlotLabelId(slot))">
                                                        <span x-text="getLabelName(getSlotLabelId(slot))"></span>
                                                    </span>
                                                </template>
                                                {{-- Evergreen Indicator --}}
                                                <template x-if="isSlotEvergreen(slot)">
                                                    <span class="text-green-500" title="{{ __('profiles.evergreen') }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                        </svg>
                                                    </span>
                                                </template>
                                                {{-- Remove Button --}}
                                                <button type="button"
                                                        @click="removeTimeSlot(dayKey, slot)"
                                                        class="text-red-500 hover:text-red-700 transition-colors opacity-0 group-hover:opacity-100">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Disabled State Message --}}
                    <div x-show="!queueEnabled" class="text-center py-12">
                        <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-lg font-medium text-gray-600 mb-2">{{ __('profiles.queue_disabled') }}</p>
                        <p class="text-sm text-gray-500">{{ __('profiles.queue_disabled_description') }}</p>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="mt-6 pt-4 border-t border-gray-200 flex items-center justify-between">
                        <button type="button"
                                @click="showQueueModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                :disabled="isSaving"
                                class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <svg x-show="isSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isSaving ? '{{ __('common.saving') }}' : '{{ __('common.save_changes') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Enhanced Time Picker Modal --}}
<div x-show="showTimePicker"
     x-cloak
     @keydown.escape.window="showTimePicker = false"
     class="fixed inset-0 z-[60] overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div x-show="showTimePicker"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showTimePicker = false"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

        {{-- Modal panel --}}
        <div x-show="showTimePicker"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block w-full max-w-md my-8 overflow-hidden text-start align-middle transition-all transform bg-white rounded-lg shadow-xl">

            {{-- Time Picker Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h4 class="text-lg font-semibold text-gray-900">{{ __('profiles.create_time_slot') }}</h4>
                <button type="button"
                        @click="showTimePicker = false"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Time Picker Body --}}
            <div class="px-6 py-4 space-y-4">
                {{-- Time Input --}}
                <div class="flex items-center gap-3">
                    <select x-model="newSlot.hour" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <template x-for="h in hours" :key="h">
                            <option :value="h" x-text="h"></option>
                        </template>
                    </select>
                    <span class="text-xl font-bold text-gray-500">:</span>
                    <select x-model="newSlot.minute" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <template x-for="m in minutes" :key="m">
                            <option :value="m" x-text="m"></option>
                        </template>
                    </select>
                    <select x-model="newSlot.period" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                    <span class="text-sm text-gray-500">({{ $profile->effective_timezone ?? config('app.timezone', 'UTC') }})</span>
                </div>

                {{-- Day Selection --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('profiles.select_days') }}</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(day, dayKey) in days" :key="dayKey">
                            <button type="button"
                                    @click="toggleDaySelection(dayKey)"
                                    :class="newSlot.days.includes(dayKey) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:border-blue-300'"
                                    class="w-10 h-10 rounded-full border-2 font-medium text-sm transition-colors flex items-center justify-center"
                                    x-text="day.shortName">
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Label Selection --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">{{ __('profiles.select_label') }}</label>
                        <button type="button"
                                @click="showManageLabelsModal = true"
                                class="text-xs text-purple-600 hover:text-purple-700 font-medium">
                            {{ __('profiles.manage_labels') }}
                        </button>
                    </div>
                    <div class="relative">
                        <select x-model="newSlot.label_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('profiles.no_label') }}</option>
                            <template x-for="label in queueLabels" :key="label.id">
                                <option :value="label.id" x-text="label.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Evergreen Toggle --}}
                <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div>
                        <label class="text-sm font-medium text-gray-900">{{ __('profiles.mark_as_evergreen') }}</label>
                        <p class="text-xs text-gray-600 mt-0.5">{{ __('profiles.evergreen_description') }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               x-model="newSlot.is_evergreen"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-green-500 transition-colors"></div>
                        <div class="absolute start-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></div>
                    </label>
                </div>
            </div>

            {{-- Time Picker Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
                <button type="button"
                        @click="showTimePicker = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    {{ __('common.cancel') }}
                </button>
                <button type="button"
                        @click="confirmAddTimeSlot()"
                        :disabled="newSlot.days.length === 0"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('common.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Include Label Management Modals --}}
@include('settings.profiles.partials._manage-labels-modal')
@include('settings.profiles.partials._label-editor-modal')

<script>
function queueSettingsForm() {
    return {
        // Core state
        queueEnabled: @json($profile->queueSettings->queue_enabled ?? false),
        isSaving: false,
        showTimePicker: false,
        selectedLabelFilter: '',

        // Label management state
        showManageLabelsModal: false,
        showLabelEditorModal: false,
        editingLabel: null,
        labelSearch: '',
        isSavingLabel: false,
        queueLabels: @json($queueLabels ?? []),

        // Label editor data
        labelEditorData: {
            name: '',
            background_color: '#3B82F6',
            text_color: '#FFFFFF',
            color_type: 'solid',
            gradient_start: '#F97316',
            gradient_end: '#EC4899',
        },

        // Color presets
        solidColors: [
            {name: 'Blue', value: '#3B82F6'},
            {name: 'Green', value: '#10B981'},
            {name: 'Purple', value: '#8B5CF6'},
            {name: 'Pink', value: '#EC4899'},
            {name: 'Orange', value: '#F97316'},
            {name: 'Red', value: '#EF4444'},
            {name: 'Yellow', value: '#F59E0B'},
            {name: 'Cyan', value: '#06B6D4'},
            {name: 'Indigo', value: '#6366F1'},
            {name: 'Gray', value: '#6B7280'},
        ],
        gradientPresets: [
            {name: 'Sunset', start: '#F97316', end: '#EC4899'},
            {name: 'Ocean', start: '#3B82F6', end: '#06B6D4'},
            {name: 'Forest', start: '#10B981', end: '#84CC16'},
            {name: 'Berry', start: '#8B5CF6', end: '#EC4899'},
            {name: 'Fire', start: '#EF4444', end: '#F97316'},
        ],
        textColors: [
            {name: 'White', value: '#FFFFFF'},
            {name: 'Black', value: '#1F2937'},
            {name: 'Gray', value: '#6B7280'},
        ],

        // Time picker options
        hours: ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
        minutes: ['00', '15', '30', '45'],

        // New slot form
        newSlot: {
            hour: '09',
            minute: '00',
            period: 'AM',
            days: [],
            label_id: '',
            is_evergreen: false,
        },

        // Days data with schedule
        days: {
            monday: {
                name: '{{ __('common.monday') }}',
                shortName: '{{ __('common.mon_short') ?: 'Mo' }}',
                enabled: @json(in_array('monday', $profile->queueSettings->days_enabled ?? [])),
                slots: @json($profile->queueSettings->schedule['monday'] ?? [])
            },
            tuesday: {
                name: '{{ __('common.tuesday') }}',
                shortName: '{{ __('common.tue_short') ?: 'Tu' }}',
                enabled: @json(in_array('tuesday', $profile->queueSettings->days_enabled ?? [])),
                slots: @json($profile->queueSettings->schedule['tuesday'] ?? [])
            },
            wednesday: {
                name: '{{ __('common.wednesday') }}',
                shortName: '{{ __('common.wed_short') ?: 'We' }}',
                enabled: @json(in_array('wednesday', $profile->queueSettings->days_enabled ?? [])),
                slots: @json($profile->queueSettings->schedule['wednesday'] ?? [])
            },
            thursday: {
                name: '{{ __('common.thursday') }}',
                shortName: '{{ __('common.thu_short') ?: 'Th' }}',
                enabled: @json(in_array('thursday', $profile->queueSettings->days_enabled ?? [])),
                slots: @json($profile->queueSettings->schedule['thursday'] ?? [])
            },
            friday: {
                name: '{{ __('common.friday') }}',
                shortName: '{{ __('common.fri_short') ?: 'Fr' }}',
                enabled: @json(in_array('friday', $profile->queueSettings->days_enabled ?? [])),
                slots: @json($profile->queueSettings->schedule['friday'] ?? [])
            },
            saturday: {
                name: '{{ __('common.saturday') }}',
                shortName: '{{ __('common.sat_short') ?: 'Sa' }}',
                enabled: @json(in_array('saturday', $profile->queueSettings->days_enabled ?? [])),
                slots: @json($profile->queueSettings->schedule['saturday'] ?? [])
            },
            sunday: {
                name: '{{ __('common.sunday') }}',
                shortName: '{{ __('common.sun_short') ?: 'Su' }}',
                enabled: @json(in_array('sunday', $profile->queueSettings->days_enabled ?? [])),
                slots: @json($profile->queueSettings->schedule['sunday'] ?? [])
            }
        },

        // Computed: filtered labels
        get filteredLabels() {
            if (!this.labelSearch) return this.queueLabels;
            return this.queueLabels.filter(l =>
                l.name.toLowerCase().includes(this.labelSearch.toLowerCase())
            );
        },

        // Slot helpers
        getSlotTime(slot) {
            if (typeof slot === 'string') return slot;
            return slot.time || '';
        },

        getSlotLabelId(slot) {
            if (typeof slot === 'string') return null;
            return slot.label_id || null;
        },

        isSlotEvergreen(slot) {
            if (typeof slot === 'string') return false;
            return slot.is_evergreen || false;
        },

        getFilteredSlots(dayKey) {
            const slots = this.days[dayKey].slots || [];
            if (!this.selectedLabelFilter) return slots;
            return slots.filter(slot => this.getSlotLabelId(slot) === this.selectedLabelFilter);
        },

        getTotalSlots() {
            let total = 0;
            Object.values(this.days).forEach(day => {
                if (day.enabled) {
                    total += (day.slots || []).length;
                }
            });
            return total;
        },

        // Label helpers
        getLabelById(id) {
            return this.queueLabels.find(l => l.id === id);
        },

        getLabelName(labelId) {
            const label = this.getLabelById(labelId);
            return label ? label.name : '';
        },

        getLabelStyle(labelId) {
            const label = this.getLabelById(labelId);
            if (!label) return {};

            const bg = label.color_type === 'gradient' && label.gradient_start && label.gradient_end
                ? `linear-gradient(135deg, ${label.gradient_start}, ${label.gradient_end})`
                : label.background_color;

            return {
                background: bg,
                color: label.text_color || '#FFFFFF',
            };
        },

        getEditorLabelStyle() {
            const bg = this.labelEditorData.color_type === 'gradient'
                ? `linear-gradient(135deg, ${this.labelEditorData.gradient_start}, ${this.labelEditorData.gradient_end})`
                : this.labelEditorData.background_color;

            return {
                background: bg,
                color: this.labelEditorData.text_color || '#FFFFFF',
            };
        },

        // Time slot methods
        openEnhancedTimePicker(dayKey = null) {
            this.newSlot = {
                hour: '09',
                minute: '00',
                period: 'AM',
                days: dayKey ? [dayKey] : [],
                label_id: '',
                is_evergreen: false,
            };
            this.showTimePicker = true;
        },

        toggleDaySelection(dayKey) {
            const index = this.newSlot.days.indexOf(dayKey);
            if (index > -1) {
                this.newSlot.days.splice(index, 1);
            } else {
                this.newSlot.days.push(dayKey);
            }
        },

        confirmAddTimeSlot() {
            if (this.newSlot.days.length === 0) return;

            // Convert to 24-hour format
            let hour = parseInt(this.newSlot.hour);
            if (this.newSlot.period === 'PM' && hour !== 12) hour += 12;
            if (this.newSlot.period === 'AM' && hour === 12) hour = 0;
            const time24 = String(hour).padStart(2, '0') + ':' + this.newSlot.minute;

            // Create slot object
            const newSlotObj = {
                time: time24,
                label_id: this.newSlot.label_id || null,
                is_evergreen: this.newSlot.is_evergreen,
            };

            // Add to selected days
            this.newSlot.days.forEach(dayKey => {
                if (!this.days[dayKey].slots) {
                    this.days[dayKey].slots = [];
                }

                // Check for duplicate time
                const exists = this.days[dayKey].slots.some(s => this.getSlotTime(s) === time24);
                if (!exists) {
                    this.days[dayKey].slots.push({...newSlotObj});
                    this.days[dayKey].slots.sort((a, b) => {
                        const timeA = this.getSlotTime(a);
                        const timeB = this.getSlotTime(b);
                        return timeA.localeCompare(timeB);
                    });
                }
            });

            this.showTimePicker = false;
        },

        removeTimeSlot(dayKey, slot) {
            const time = this.getSlotTime(slot);
            this.days[dayKey].slots = this.days[dayKey].slots.filter(s => this.getSlotTime(s) !== time);
        },

        copyToWeekdays() {
            const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            const mondaySlots = JSON.parse(JSON.stringify(this.days.monday.slots || []));
            weekdays.forEach(day => {
                this.days[day].slots = JSON.parse(JSON.stringify(mondaySlots));
                this.days[day].enabled = this.days.monday.enabled;
            });
        },

        clearAllTimes() {
            if (confirm('{{ __('profiles.confirm_clear_all_times') }}')) {
                Object.keys(this.days).forEach(dayKey => {
                    this.days[dayKey].slots = [];
                });
            }
        },

        // Label management methods
        openLabelEditor(label) {
            if (label) {
                this.editingLabel = label;
                this.labelEditorData = {
                    name: label.name,
                    background_color: label.background_color,
                    text_color: label.text_color,
                    color_type: label.color_type || 'solid',
                    gradient_start: label.gradient_start || '#F97316',
                    gradient_end: label.gradient_end || '#EC4899',
                };
            } else {
                this.editingLabel = null;
                this.labelEditorData = {
                    name: '',
                    background_color: '#3B82F6',
                    text_color: '#FFFFFF',
                    color_type: 'solid',
                    gradient_start: '#F97316',
                    gradient_end: '#EC4899',
                };
            }
            this.showLabelEditorModal = true;
        },

        async saveLabel() {
            if (!this.labelEditorData.name) return;

            this.isSavingLabel = true;

            try {
                const url = this.editingLabel
                    ? '{{ route('orgs.settings.queue-labels.update', ['org' => $currentOrg, 'label_id' => '__ID__']) }}'.replace('__ID__', this.editingLabel.id)
                    : '{{ route('orgs.settings.queue-labels.store', ['org' => $currentOrg]) }}';

                const method = this.editingLabel ? 'PATCH' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.labelEditorData)
                });

                const data = await response.json();

                if (data.success) {
                    if (this.editingLabel) {
                        // Update existing label
                        const index = this.queueLabels.findIndex(l => l.id === this.editingLabel.id);
                        if (index > -1) {
                            this.queueLabels[index] = data.data;
                        }
                    } else {
                        // Add new label
                        this.queueLabels.push(data.data);
                    }

                    this.showLabelEditorModal = false;

                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: this.editingLabel ? '{{ __('profiles.label_updated') }}' : '{{ __('profiles.label_created') }}',
                            type: 'success'
                        }
                    }));
                } else {
                    throw new Error(data.message || '{{ __('common.error_occurred') }}');
                }
            } catch (error) {
                console.error('Error saving label:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: error.message || '{{ __('common.error_occurred') }}',
                        type: 'error'
                    }
                }));
            } finally {
                this.isSavingLabel = false;
            }
        },

        async confirmDeleteLabel(label) {
            if (!confirm('{{ __('profiles.confirm_delete_label') }}')) return;

            try {
                const response = await fetch('{{ route('orgs.settings.queue-labels.destroy', ['org' => $currentOrg, 'label_id' => '__ID__']) }}'.replace('__ID__', label.id), {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.queueLabels = this.queueLabels.filter(l => l.id !== label.id);
                    this.showLabelEditorModal = false;

                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: '{{ __('profiles.label_deleted') }}',
                            type: 'success'
                        }
                    }));
                } else {
                    throw new Error(data.message || '{{ __('common.error_occurred') }}');
                }
            } catch (error) {
                console.error('Error deleting label:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: error.message || '{{ __('common.error_occurred') }}',
                        type: 'error'
                    }
                }));
            }
        },

        // Save queue settings
        async saveQueueSettings() {
            this.isSaving = true;

            // Prepare data
            const schedule = {};
            const daysEnabled = [];

            Object.keys(this.days).forEach(dayKey => {
                schedule[dayKey] = this.days[dayKey].slots || [];
                if (this.days[dayKey].enabled) {
                    daysEnabled.push(dayKey);
                }
            });

            try {
                const response = await fetch('{{ route('orgs.settings.profiles.queue.update', ['org' => $currentOrg, 'integration_id' => $profile->integration_id]) }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        queue_enabled: this.queueEnabled,
                        schedule: schedule,
                        days_enabled: daysEnabled
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: '{{ __('profiles.queue_settings_saved') }}',
                            type: 'success'
                        }
                    }));

                    setTimeout(() => location.reload(), 500);
                } else {
                    throw new Error(data.message || '{{ __('common.error_occurred') }}');
                }
            } catch (error) {
                console.error('Error saving queue settings:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: error.message || '{{ __('common.error_occurred') }}',
                        type: 'error'
                    }
                }));
            } finally {
                this.isSaving = false;
            }
        }
    };
}
</script>
</div>
{{-- End Queue Settings Component Wrapper --}}
