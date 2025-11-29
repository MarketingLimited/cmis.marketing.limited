    <div x-show="showCalendar"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50"
         style="display: none;"
         @click.self="showCalendar = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden flex flex-col"
             @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('publish.scheduled_posts') }}</h3>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Month Navigation --}}
                    <button @click="calendarMonth--; if(calendarMonth < 0) { calendarMonth = 11; calendarYear--; }"
                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="text-sm font-medium text-gray-700 min-w-[120px] text-center"
                          x-text="new Date(calendarYear, calendarMonth).toLocaleDateString('{{ app()->getLocale() }}', { month: 'long', year: 'numeric' })"></span>
                    <button @click="calendarMonth++; if(calendarMonth > 11) { calendarMonth = 0; calendarYear++; }"
                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button @click="showCalendar = false" class="ms-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            {{-- Calendar Grid --}}
            <div class="flex-1 overflow-y-auto p-6">
                <div class="grid grid-cols-7 gap-2">
                    {{-- Day Headers --}}
                    <template x-for="day in ['{{ __('publish.sun') }}', '{{ __('publish.mon') }}', '{{ __('publish.tue') }}', '{{ __('publish.wed') }}', '{{ __('publish.thu') }}', '{{ __('publish.fri') }}', '{{ __('publish.sat') }}']">
                        <div class="text-center text-xs font-semibold text-gray-600 py-2" x-text="day"></div>
                    </template>

                    {{-- Calendar Days (WITH DRAG-AND-DROP SUPPORT) --}}
                    <template x-for="day in getCalendarDays()" :key="day.date">
                        <div :class="[
                                 day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
                                 dragOverDate === day.date ? 'border-blue-500 bg-blue-50 border-2' : 'border-gray-200'
                             ]"
                             class="min-h-[100px] border rounded-lg p-2 hover:border-blue-300 transition"
                             @dragover.prevent="dragOverDate = day.date"
                             @dragleave="dragOverDate = null"
                             @drop.prevent="handlePostDrop(day.date)">
                            <div class="flex items-center justify-between mb-1">
                                <span :class="day.isToday ? 'bg-blue-600 text-white px-2 py-0.5 rounded-full' : 'text-gray-700'"
                                      class="text-xs font-medium" x-text="day.dayNumber"></span>
                            </div>
                            {{-- Scheduled Posts for this day (DRAGGABLE) --}}
                            <div class="space-y-1">
                                <template x-for="post in day.posts" :key="post.id">
                                    <div class="bg-blue-100 rounded p-1.5 cursor-move hover:bg-blue-200 transition text-xs"
                                         draggable="true"
                                         @dragstart="handlePostDragStart(post)"
                                         @dragend="draggedPost = null; dragOverDate = null"
                                         @click="editScheduledPost(post)"
                                         :class="draggedPost?.id === post.id ? 'opacity-50' : ''">
                                        <div class="flex items-center gap-1 mb-0.5">
                                            <i class="fas fa-grip-vertical text-blue-400 text-[10px]"></i>
                                            <i class="fas fa-clock text-blue-600 text-[10px]"></i>
                                            <span class="text-blue-800 font-medium" x-text="post.time"></span>
                                        </div>
                                        <p class="text-gray-700 truncate text-[10px]" x-text="post.preview"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Empty State --}}
                <template x-if="scheduledPosts.length === 0">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-calendar-times text-gray-300 text-5xl mb-3"></i>
                            <p class="text-gray-500">{{ __('publish.no_scheduled_posts') }}</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- PHASE 3: Best Times Modal --}}
