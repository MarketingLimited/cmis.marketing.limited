{{-- Publish Modal Header Component --}}
<div class="flex-shrink-0 px-6 py-4 border-b border-gray-200 bg-gradient-to-l from-indigo-600 to-purple-600">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h2 class="text-lg font-bold text-white">
                <i class="fas fa-paper-plane text-white/80 ms-2"></i>
                <span x-text="editMode ? '{{ __('publish.edit_post') }}' : '{{ __('publish.create_post') }}'"></span>
            </h2>
        </div>
        <div class="flex items-center gap-3">
            {{-- Auto-save Indicator --}}
            <div x-show="saveIndicator"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="flex items-center gap-2 px-3 py-1.5 bg-white/20 rounded-lg text-white text-xs">
                <i class="fas fa-check-circle"></i>
                <span>{{ __('publish.changes_saved') }}</span>
                <span x-show="lastSaved" class="text-white/70" x-text="lastSaved ? new Date(lastSaved).toLocaleTimeString(document.documentElement.lang === 'ar' ? 'ar-SA' : 'en-US', { hour: '2-digit', minute: '2-digit' }) : ''"></span>
            </div>

            <button @click="saveDraft()" class="px-4 py-2.5 min-h-[44px] text-sm text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition flex items-center">
                <i class="fas fa-save ms-1"></i>{{ __('publish.save_draft') }}
            </button>
            <button @click="closeModal()" class="p-3 min-w-[44px] min-h-[44px] text-white/80 hover:text-white rounded-lg hover:bg-white/10 flex items-center justify-center">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
    </div>
</div>
