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

                    {{-- Quick Actions --}}
                    <div x-show="queueEnabled" class="mb-6 flex flex-wrap gap-2">
                        <button type="button"
                                @click="addTimeToAll()"
                                class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                            <svg class="inline-block w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ __('profiles.add_time_all_days') }}
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
                                            <span x-show="day.enabled && day.times.length > 0"
                                                  class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full"
                                                  x-text="day.times.length + ' ' + (day.times.length === 1 ? '{{ __('profiles.time_slot') }}' : '{{ __('profiles.time_slots') }}')">
                                            </span>
                                        </div>
                                        {{-- Add Time Button --}}
                                        <button type="button"
                                                x-show="day.enabled"
                                                @click="addTimeSlot(dayKey)"
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
                                    <div x-show="day.times.length === 0" class="text-center py-8">
                                        <svg class="mx-auto w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm text-gray-500">{{ __('profiles.no_times_set') }}</p>
                                        <button type="button"
                                                @click="addTimeSlot(dayKey)"
                                                class="mt-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                                            {{ __('profiles.add_first_time') }}
                                        </button>
                                    </div>

                                    <div x-show="day.times.length > 0" class="flex flex-wrap gap-2">
                                        <template x-for="(time, timeIndex) in day.times" :key="timeIndex">
                                            <div class="group relative inline-flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span class="text-sm font-medium text-gray-900" x-text="time"></span>
                                                <button type="button"
                                                        @click="removeTimeSlot(dayKey, timeIndex)"
                                                        class="text-red-500 hover:text-red-700 transition-colors">
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

{{-- Time Picker Modal --}}
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
            <div class="px-6 py-4 border-b border-gray-200">
                <h4 class="text-lg font-semibold text-gray-900">{{ __('profiles.add_time_slot') }}</h4>
            </div>

            {{-- Time Picker Body --}}
            <div class="px-6 py-4">
                <input type="time"
                       x-model="newTime"
                       @keydown.enter="confirmAddTime()"
                       class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            {{-- Time Picker Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
                <button type="button"
                        @click="showTimePicker = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    {{ __('common.cancel') }}
                </button>
                <button type="button"
                        @click="confirmAddTime()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                    {{ __('common.add') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function queueSettingsForm() {
    return {
        queueEnabled: @json($profile->queueSettings->queue_enabled ?? false),
        isSaving: false,
        showTimePicker: false,
        currentDayKey: null,
        newTime: '09:00',
        days: {
            monday: {
                name: '{{ __('common.monday') }}',
                enabled: @json(in_array('monday', $profile->queueSettings->days_enabled ?? [])),
                times: @json($profile->queueSettings->schedule['monday'] ?? [])
            },
            tuesday: {
                name: '{{ __('common.tuesday') }}',
                enabled: @json(in_array('tuesday', $profile->queueSettings->days_enabled ?? [])),
                times: @json($profile->queueSettings->schedule['tuesday'] ?? [])
            },
            wednesday: {
                name: '{{ __('common.wednesday') }}',
                enabled: @json(in_array('wednesday', $profile->queueSettings->days_enabled ?? [])),
                times: @json($profile->queueSettings->schedule['wednesday'] ?? [])
            },
            thursday: {
                name: '{{ __('common.thursday') }}',
                enabled: @json(in_array('thursday', $profile->queueSettings->days_enabled ?? [])),
                times: @json($profile->queueSettings->schedule['thursday'] ?? [])
            },
            friday: {
                name: '{{ __('common.friday') }}',
                enabled: @json(in_array('friday', $profile->queueSettings->days_enabled ?? [])),
                times: @json($profile->queueSettings->schedule['friday'] ?? [])
            },
            saturday: {
                name: '{{ __('common.saturday') }}',
                enabled: @json(in_array('saturday', $profile->queueSettings->days_enabled ?? [])),
                times: @json($profile->queueSettings->schedule['saturday'] ?? [])
            },
            sunday: {
                name: '{{ __('common.sunday') }}',
                enabled: @json(in_array('sunday', $profile->queueSettings->days_enabled ?? [])),
                times: @json($profile->queueSettings->schedule['sunday'] ?? [])
            }
        },

        getTotalSlots() {
            let total = 0;
            Object.values(this.days).forEach(day => {
                if (day.enabled) {
                    total += day.times.length;
                }
            });
            return total;
        },

        addTimeSlot(dayKey) {
            this.currentDayKey = dayKey;
            this.showTimePicker = true;
        },

        confirmAddTime() {
            if (this.newTime && this.currentDayKey) {
                if (this.currentDayKey === 'all') {
                    // Add to all enabled days
                    Object.keys(this.days).forEach(dayKey => {
                        if (this.days[dayKey].enabled && !this.days[dayKey].times.includes(this.newTime)) {
                            this.days[dayKey].times.push(this.newTime);
                            this.days[dayKey].times.sort();
                        }
                    });
                } else {
                    // Add to specific day
                    if (!this.days[this.currentDayKey].times.includes(this.newTime)) {
                        this.days[this.currentDayKey].times.push(this.newTime);
                        this.days[this.currentDayKey].times.sort();
                    }
                }
                this.showTimePicker = false;
                this.newTime = '09:00';
            }
        },

        removeTimeSlot(dayKey, index) {
            this.days[dayKey].times.splice(index, 1);
        },

        addTimeToAll() {
            this.currentDayKey = 'all';
            this.showTimePicker = true;
        },

        copyToWeekdays() {
            const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            const mondayTimes = [...this.days.monday.times];
            weekdays.forEach(day => {
                this.days[day].times = [...mondayTimes];
                this.days[day].enabled = this.days.monday.enabled;
            });
        },

        clearAllTimes() {
            if (confirm('{{ __('profiles.confirm_clear_all_times') }}')) {
                Object.keys(this.days).forEach(dayKey => {
                    this.days[dayKey].times = [];
                });
            }
        },

        async saveQueueSettings() {
            this.isSaving = true;

            // Prepare data
            const schedule = {};
            const daysEnabled = [];

            Object.keys(this.days).forEach(dayKey => {
                schedule[dayKey] = this.days[dayKey].times;
                if (this.days[dayKey].enabled) {
                    daysEnabled.push(dayKey);
                }
            });

            try {
                const response = await fetch('{{ route('settings.profiles.queue.update', ['org' => $org->id, 'integration_id' => $profile->integration_id]) }}', {
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
                    // Show success message
                    this.$dispatch('notify', {
                        type: 'success',
                        message: '{{ __('profiles.queue_settings_saved') }}'
                    });

                    // Close modal and reload page to update display
                    this.showQueueModal = false;
                    setTimeout(() => location.reload(), 500);
                } else {
                    throw new Error(data.message || '{{ __('common.error_occurred') }}');
                }
            } catch (error) {
                console.error('Error saving queue settings:', error);
                this.$dispatch('notify', {
                    type: 'error',
                    message: error.message || '{{ __('common.error_occurred') }}'
                });
            } finally {
                this.isSaving = false;
            }
        }
    };
}
</script>
