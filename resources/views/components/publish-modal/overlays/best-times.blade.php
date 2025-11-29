    <div x-show="showBestTimes"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50"
         style="display: none;"
         @click.self="showBestTimes = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4"
             @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('publish.best_posting_times') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('publish.based_on_analytics') }}</p>
                </div>
                <button @click="showBestTimes = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Optimal Times List --}}
            <div class="p-6 space-y-3 max-h-[500px] overflow-y-auto">
                <template x-for="(time, index) in optimalTimes" :key="index">
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200 hover:border-blue-300 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900" x-text="time.day"></p>
                                <p class="text-lg font-bold text-blue-600" x-text="time.time"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span :class="time.engagement === 'High' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                      class="px-3 py-1 rounded-full text-xs font-medium" x-text="time.engagement"></span>
                            </div>
                            <button @click="applyOptimalTime(time)"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                                {{ __('publish.apply_time') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>

