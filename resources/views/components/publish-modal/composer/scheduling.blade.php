                    {{-- PHASE 3: Enhanced Scheduling Section --}}
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="space-y-4">
                            {{-- Schedule Toggle & Calendar Button --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="scheduleEnabled" class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:end-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                    <span class="text-sm font-medium text-gray-700">{{ __('publish.schedule') }}</span>
                                </div>

                                <div x-show="scheduleEnabled"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95">
                                    <button @click="showCalendar = !showCalendar"
                                            class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-2">
                                        <i class="fas fa-calendar-alt"></i>
                                        {{ __('publish.show_calendar') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Schedule Time Inputs with smooth transition --}}
                            <div x-show="scheduleEnabled"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-2"
                                 class="flex flex-wrap items-center gap-3">
                                    <input type="date" x-model="schedule.date"
                                           class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <input type="time" x-model="schedule.time"
                                           class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <select x-model="schedule.timezone" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="UTC">UTC</option>
                                        <option value="Asia/Riyadh">{{ __('publish.timezone_riyadh') }}</option>
                                        <option value="Asia/Dubai">{{ __('publish.timezone_dubai') }}</option>
                                        <option value="Europe/London">{{ __('publish.timezone_london') }}</option>
                                        <option value="America/New_York">{{ __('publish.timezone_newyork') }}</option>
                                    </select>
                                    <button @click="showBestTimes = true"
                                            class="px-3 py-1.5 text-sm bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg font-medium transition">
                                        <i class="fas fa-clock me-1"></i>{{ __('publish.best_times') }}
                                    </button>
                                    <button @click="showBulkScheduling = !showBulkScheduling"
                                            class="px-3 py-1.5 text-sm bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-lg font-medium transition">
                                        <i class="fas fa-layer-group me-1"></i>{{ __('publish.bulk_scheduling') }}
                                    </button>
                                </div>
                            </template>

                            {{-- Bulk Scheduling Options --}}
                            <template x-if="scheduleEnabled && showBulkScheduling">
                                <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-900">{{ __('publish.bulk_scheduling') }}</h4>
                                        <button @click="showBulkScheduling = false" class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    {{-- Schedule Times List --}}
                                    <div class="space-y-2">
                                        <template x-for="(time, index) in bulkSchedule.times" :key="index">
                                            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                                                <i class="fas fa-clock text-gray-400"></i>
                                                <span class="text-sm text-gray-700 flex-1" x-text="time.date + ' ' + time.time"></span>
                                                <button @click="bulkSchedule.times.splice(index, 1)"
                                                        class="text-red-500 hover:text-red-700 text-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </template>
                                        <button @click="bulkSchedule.times.push({date: schedule.date, time: schedule.time, timezone: schedule.timezone})"
                                                class="w-full px-3 py-2 text-sm border-2 border-dashed border-gray-300 text-gray-600 hover:border-blue-400 hover:text-blue-600 rounded-lg transition">
                                            <i class="fas fa-plus me-1"></i>{{ __('publish.add_schedule_time') }}
                                        </button>
                                    </div>

                                    {{-- Recurring Options --}}
                                    <div class="border-t border-gray-200 pt-3 space-y-2">
                                        <div class="flex items-center gap-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" x-model="bulkSchedule.recurring" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm text-gray-700">{{ __('publish.recurring_post') }}</span>
                                            </label>
                                        </div>
                                        <template x-if="bulkSchedule.recurring">
                                            <select x-model="bulkSchedule.repeatType" class="w-full text-sm border-gray-300 rounded-lg">
                                                <option value="daily">{{ __('publish.daily') }}</option>
                                                <option value="weekly">{{ __('publish.weekly') }}</option>
                                                <option value="monthly">{{ __('publish.monthly') }}</option>
                                            </select>
                                        </template>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="bulkSchedule.isEvergreen" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                            <span class="text-sm text-gray-700">{{ __('publish.evergreen_post') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

