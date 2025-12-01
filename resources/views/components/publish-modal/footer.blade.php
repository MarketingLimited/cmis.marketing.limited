{{-- Modal Footer - Full-Width Publish Controls --}}
{{-- This footer MUST be a direct child of the modal panel flex-col container --}}
{{-- z-20 ensures it stays above content but below overlays (z-50) --}}
{{-- x-init repositions footer to be last child of modal panel, fixing DOM nesting issues --}}
<div id="publish-modal-footer"
     x-init="$nextTick(() => {
         const footer = document.getElementById('publish-modal-footer');
         const panel = document.getElementById('publish-modal-panel');
         if (footer && panel && footer.parentElement !== panel) {
             panel.appendChild(footer);
         }
     })"
     class="flex-shrink-0 border-t-2 border-gray-200 bg-white px-6 py-4 relative z-20">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">

        {{-- Left Side: Publish Mode Selection --}}
        <div x-show="!requiresApproval" class="flex flex-wrap items-center gap-3">
            {{-- Publish Now Radio --}}
            <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg transition-all"
                   :class="publishMode === 'publish_now' ? 'bg-indigo-100 ring-2 ring-indigo-500' : 'hover:bg-gray-100'">
                <input type="radio" x-model="publishMode" value="publish_now"
                       class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                <span class="text-sm font-medium" :class="publishMode === 'publish_now' ? 'text-indigo-700' : 'text-gray-700'">
                    <i class="fas fa-paper-plane me-1"></i>{{ __('publish.publish_now') }}
                </span>
            </label>

            {{-- Schedule Radio --}}
            <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg transition-all"
                   :class="publishMode === 'schedule' ? 'bg-green-100 ring-2 ring-green-500' : 'hover:bg-gray-100'">
                <input type="radio" x-model="publishMode" value="schedule" @change="scheduleEnabled = true"
                       class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                <span class="text-sm font-medium" :class="publishMode === 'schedule' ? 'text-green-700' : 'text-gray-700'">
                    <i class="far fa-clock me-1"></i>{{ __('publish.schedule') }}
                </span>
            </label>

            {{-- Add to Queue Radio --}}
            <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg transition-all"
                   :class="publishMode === 'add_to_queue' ? 'bg-blue-100 ring-2 ring-blue-500' : 'hover:bg-gray-100'">
                <input type="radio" x-model="publishMode" value="add_to_queue"
                       class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <span class="text-sm font-medium" :class="publishMode === 'add_to_queue' ? 'text-blue-700' : 'text-gray-700'">
                    <i class="fas fa-stream me-1"></i>{{ __('publish.add_to_queue') }}
                </span>
            </label>

            {{-- Queue Position Dropdown --}}
            <select x-show="publishMode === 'add_to_queue'" x-cloak x-model="queuePosition"
                    class="px-3 py-1.5 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <option value="next">{{ __('publish.queue_next') }}</option>
                <option value="available">{{ __('publish.queue_available') }}</option>
                <option value="last">{{ __('publish.queue_last') }}</option>
            </select>

            {{-- Queue Label Selector --}}
            <div x-show="publishMode === 'add_to_queue' && queueLabels.length > 0" x-cloak class="flex items-center gap-2">
                <span class="text-sm text-gray-500">{{ __('publish.queue_label') }}:</span>
                <select x-model="queueLabelId"
                        class="px-3 py-1.5 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('publish.any_slot') }}</option>
                    <template x-for="label in queueLabels" :key="label.id">
                        <option :value="label.id" x-text="label.name"></option>
                    </template>
                </select>
            </div>

            {{-- Schedule Date/Time Inputs (shown when Schedule mode selected) --}}
            <div x-show="publishMode === 'schedule'" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex flex-wrap items-center gap-2">
                <input type="date" x-model="schedule.date"
                       class="px-3 py-1.5 text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <input type="time" x-model="schedule.time"
                       class="px-3 py-1.5 text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        {{-- Right Side: Action Buttons --}}
        <div class="flex items-center gap-3">
            {{-- Cancel Button --}}
            <button @click="closeModal()" type="button"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                {{ __('publish.cancel') }}
            </button>

            {{-- Requires Approval Badge --}}
            <span x-show="requiresApproval" x-cloak class="text-sm text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg">
                <i class="fas fa-user-clock me-1"></i>{{ __('publish.requires_approval') }}
            </span>

            {{-- Submit for Approval Button --}}
            <button x-show="requiresApproval" x-cloak @click="submitForApproval()" type="button"
                    :disabled="!canSubmit"
                    :class="canSubmit ? 'bg-amber-500 hover:bg-amber-600' : 'bg-gray-300 cursor-not-allowed'"
                    class="px-5 py-2.5 text-sm font-medium text-white rounded-lg transition">
                <i class="fas fa-paper-plane me-1"></i>{{ __('publish.submit_for_approval') }}
            </button>

            {{-- Publish Now Button --}}
            <button x-show="!requiresApproval && publishMode === 'publish_now'" x-cloak
                    @click="publishNow()" type="button"
                    :disabled="!canSubmit || isPublishing"
                    :class="(canSubmit && !isPublishing) ? 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700' : 'bg-gray-300 cursor-not-allowed'"
                    class="px-5 py-2.5 text-sm font-medium text-white rounded-lg transition shadow-sm flex items-center gap-2">
                <svg x-show="isPublishing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <i x-show="!isPublishing" class="fas fa-paper-plane"></i>
                <span x-text="isPublishing ? '{{ __('publish.publishing') }}' : '{{ __('publish.publish_now') }}'"></span>
            </button>

            {{-- Schedule Button --}}
            <button x-show="!requiresApproval && publishMode === 'schedule'" x-cloak
                    @click="schedulePost()" type="button"
                    :disabled="!canSubmit || isPublishing"
                    :class="(canSubmit && !isPublishing) ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed'"
                    class="px-5 py-2.5 text-sm font-medium text-white rounded-lg transition shadow-sm flex items-center gap-2">
                <svg x-show="isPublishing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <i x-show="!isPublishing" class="far fa-clock"></i>
                <span x-text="isPublishing ? '{{ __('publish.scheduling') }}' : '{{ __('publish.schedule_post') }}'"></span>
            </button>

            {{-- Add to Queue Button --}}
            <button x-show="!requiresApproval && publishMode === 'add_to_queue'" x-cloak
                    @click="addToQueue()" type="button"
                    :disabled="!canSubmit || isPublishing"
                    :class="(canSubmit && !isPublishing) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 cursor-not-allowed'"
                    class="px-5 py-2.5 text-sm font-medium text-white rounded-lg transition shadow-sm flex items-center gap-2">
                <svg x-show="isPublishing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <i x-show="!isPublishing" class="fas fa-stream"></i>
                <span x-text="isPublishing ? '{{ __('publish.adding_to_queue') }}' : '{{ __('publish.add_to_queue') }}'"></span>
            </button>
        </div>
    </div>

    {{-- Validation Errors --}}
    <div x-show="validationErrors.length > 0 && !canSubmit" x-cloak
         class="mt-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
            <div class="flex-1">
                <template x-for="(error, idx) in validationErrors" :key="idx">
                    <p class="text-sm text-red-700" x-text="error"></p>
                </template>
            </div>
        </div>
    </div>
</div>
