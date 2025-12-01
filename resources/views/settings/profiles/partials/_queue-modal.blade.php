{{-- Queue Settings Modal --}}
<div x-show="showQueueModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="queue-modal-title"
     role="dialog"
     aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showQueueModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             @click="showQueueModal = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="showQueueModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full max-h-[90vh] overflow-y-auto"
             x-data="queueSettingsForm()">

            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900" id="queue-modal-title">
                        {{ __('profiles.queue_settings') }}
                    </h3>
                    <button @click="showQueueModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Queue Enable Toggle --}}
                <div class="flex items-center justify-between mb-6 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('profiles.queue_enabled') }}</p>
                        <p class="text-xs text-gray-500">{{ __('profiles.queue_enabled_description') }}</p>
                    </div>
                    <button type="button"
                            @click="queueEnabled = !queueEnabled"
                            :class="queueEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span :class="queueEnabled ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </div>

                {{-- Days and Times --}}
                <div x-show="queueEnabled" class="space-y-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">{{ __('profiles.posting_times') }}</p>

                    <template x-for="(day, dayKey) in days" :key="dayKey">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox"
                                           x-model="day.enabled"
                                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-900" x-text="day.label"></span>
                                </label>
                                <button type="button"
                                        x-show="day.enabled"
                                        @click="addTimeSlot(dayKey)"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="fas fa-plus me-1"></i>
                                    {{ __('profiles.add_time') }}
                                </button>
                            </div>

                            <div x-show="day.enabled" class="flex flex-wrap gap-2">
                                <template x-for="(time, timeIndex) in day.times" :key="timeIndex">
                                    <div class="flex items-center gap-1 bg-blue-50 rounded-lg px-3 py-1.5">
                                        <input type="time"
                                               x-model="day.times[timeIndex]"
                                               class="text-sm border-0 bg-transparent p-0 focus:ring-0 w-20">
                                        <button type="button"
                                                @click="removeTimeSlot(dayKey, timeIndex)"
                                                class="text-gray-400 hover:text-red-500">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                </template>
                                <p x-show="day.times.length === 0" class="text-xs text-gray-400 italic">
                                    {{ __('profiles.no_times_set') }}
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Quick Add Times --}}
                <div x-show="queueEnabled" class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm font-medium text-gray-700 mb-3">{{ __('profiles.quick_add_times') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                                @click="addQuickTime('09:00')"
                                class="px-3 py-1.5 text-xs bg-white border border-gray-300 rounded-full hover:bg-gray-50">
                            9:00 AM
                        </button>
                        <button type="button"
                                @click="addQuickTime('12:00')"
                                class="px-3 py-1.5 text-xs bg-white border border-gray-300 rounded-full hover:bg-gray-50">
                            12:00 PM
                        </button>
                        <button type="button"
                                @click="addQuickTime('15:00')"
                                class="px-3 py-1.5 text-xs bg-white border border-gray-300 rounded-full hover:bg-gray-50">
                            3:00 PM
                        </button>
                        <button type="button"
                                @click="addQuickTime('18:00')"
                                class="px-3 py-1.5 text-xs bg-white border border-gray-300 rounded-full hover:bg-gray-50">
                            6:00 PM
                        </button>
                        <button type="button"
                                @click="addQuickTime('21:00')"
                                class="px-3 py-1.5 text-xs bg-white border border-gray-300 rounded-full hover:bg-gray-50">
                            9:00 PM
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button"
                        @click="saveQueueSettings()"
                        :disabled="saving"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm disabled:opacity-50">
                    <span x-show="saving" class="me-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                    {{ __('profiles.save') }}
                </button>
                <button type="button"
                        @click="showQueueModal = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    {{ __('profiles.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function queueSettingsForm() {
    return {
        saving: false,
        queueEnabled: {{ $queueSettings && $queueSettings->queue_enabled ? 'true' : 'false' }},
        days: {
            sunday: {
                label: '{{ __("profiles.sunday") }}',
                enabled: {{ $queueSettings && in_array('sunday', $queueSettings->days_enabled ?? []) ? 'true' : 'false' }},
                times: {!! json_encode($queueSettings->schedule['sunday'] ?? []) !!}
            },
            monday: {
                label: '{{ __("profiles.monday") }}',
                enabled: {{ $queueSettings && in_array('monday', $queueSettings->days_enabled ?? []) ? 'true' : 'false' }},
                times: {!! json_encode($queueSettings->schedule['monday'] ?? []) !!}
            },
            tuesday: {
                label: '{{ __("profiles.tuesday") }}',
                enabled: {{ $queueSettings && in_array('tuesday', $queueSettings->days_enabled ?? []) ? 'true' : 'false' }},
                times: {!! json_encode($queueSettings->schedule['tuesday'] ?? []) !!}
            },
            wednesday: {
                label: '{{ __("profiles.wednesday") }}',
                enabled: {{ $queueSettings && in_array('wednesday', $queueSettings->days_enabled ?? []) ? 'true' : 'false' }},
                times: {!! json_encode($queueSettings->schedule['wednesday'] ?? []) !!}
            },
            thursday: {
                label: '{{ __("profiles.thursday") }}',
                enabled: {{ $queueSettings && in_array('thursday', $queueSettings->days_enabled ?? []) ? 'true' : 'false' }},
                times: {!! json_encode($queueSettings->schedule['thursday'] ?? []) !!}
            },
            friday: {
                label: '{{ __("profiles.friday") }}',
                enabled: {{ $queueSettings && in_array('friday', $queueSettings->days_enabled ?? []) ? 'true' : 'false' }},
                times: {!! json_encode($queueSettings->schedule['friday'] ?? []) !!}
            },
            saturday: {
                label: '{{ __("profiles.saturday") }}',
                enabled: {{ $queueSettings && in_array('saturday', $queueSettings->days_enabled ?? []) ? 'true' : 'false' }},
                times: {!! json_encode($queueSettings->schedule['saturday'] ?? []) !!}
            }
        },

        addTimeSlot(dayKey) {
            this.days[dayKey].times.push('12:00');
        },

        removeTimeSlot(dayKey, timeIndex) {
            this.days[dayKey].times.splice(timeIndex, 1);
        },

        addQuickTime(time) {
            // Add to all enabled days
            Object.keys(this.days).forEach(dayKey => {
                if (this.days[dayKey].enabled && !this.days[dayKey].times.includes(time)) {
                    this.days[dayKey].times.push(time);
                    this.days[dayKey].times.sort();
                }
            });
        },

        async saveQueueSettings() {
            this.saving = true;

            // Build schedule object
            const schedule = {};
            const daysEnabled = [];

            Object.keys(this.days).forEach(dayKey => {
                if (this.days[dayKey].enabled) {
                    daysEnabled.push(dayKey);
                    schedule[dayKey] = this.days[dayKey].times.sort();
                }
            });

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/queue`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        queue_enabled: this.queueEnabled,
                        days_enabled: daysEnabled,
                        schedule: schedule
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.queue_settings_updated") }}', type: 'success' }
                    }));
                    this.$root.showQueueModal = false;
                    location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: data.message || 'Error', type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'An error occurred', type: 'error' }
                }));
            }
            this.saving = false;
        }
    };
}
</script>
