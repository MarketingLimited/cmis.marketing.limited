@props(['action', 'method' => 'POST', 'hasAutoSave' => false])

{{-- Enhanced Form Component with all Group 1 improvements --}}
<div x-data="{
    ...unsavedChanges(),
    ...dateValidation(),
    ...characterCounter(),
    ...formValidation(),
    ...loadingStates(),
    formData: @js($formData ?? []),

    init() {
        this.initUnsavedChangesWarning();
        this.initValidation();
        this.watchDates();

        // Initialize character counters
        @foreach($characterLimits ?? [] as $field => $limit)
            this.initCharacterCounter('{{ $field }}', {{ $limit }});
        @endforeach
    },

    @if($hasAutoSave)
    autoSave() {
        this.withLoading('autosave', 'Auto-saving...', async () => {
            const response = await fetch('{{ $action }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    ...this.formData,
                    _draft: true
                })
            });

            if (response.ok) {
                this.markAsSaved();
                this.addMessage('success', 'Draft saved automatically', 3000);
            }
        });
    },
    @endif

    async submitForm() {
        // Validate before submit
        if (!this.validateDates() || !this.isFormValid()) {
            this.addMessage('error', 'Please fix validation errors before submitting', 5000);
            return;
        }

        this.startLoading('submit', 'Submitting...');

        // Submit via fetch or regular form
        this.$refs.form.submit();
    }
}" x-init="init()" @beforeunload.window="handleBeforeUnload($event)">

    {{-- Flash Messages Container --}}
    <div x-data="flashMessages()" class="space-y-2 mb-4">
        <template x-for="msg in messages" :key="msg.id">
            <div :class="getColorClasses(msg.type)"
                 class="border-l-4 p-4 rounded-r flex items-center justify-between"
                 x-show="true"
                 x-transition>
                <div class="flex items-center gap-3">
                    <i :class="getIcon(msg.type)" class="fas text-lg"></i>
                    <p x-text="msg.message"></p>
                </div>
                <button @click="removeMessage(msg.id)"
                        x-show="msg.dismissible"
                        class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </template>
    </div>

    {{-- Hidden data for server-side errors --}}
    <div data-validation-errors style="display: none;">@json($errors->messages() ?? [])</div>

    {{-- Actual Form --}}
    <form
        x-ref="form"
        action="{{ $action }}"
        method="{{ $method === 'GET' ? 'GET' : 'POST' }}"
        @submit.prevent="submitForm()"
        class="space-y-6"
    >
        @csrf
        @if($method !== 'GET' && $method !== 'POST')
            @method($method)
        @endif

        {{-- Auto-save indicator --}}
        @if($hasAutoSave)
        <div class="flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center gap-2">
                <span x-show="hasUnsavedChanges" class="flex items-center gap-1">
                    <i class="fas fa-circle text-orange-500 text-xs"></i>
                    Unsaved changes
                </span>
                <span x-show="!hasUnsavedChanges" class="flex items-center gap-1">
                    <i class="fas fa-check-circle text-green-500 text-xs"></i>
                    All changes saved
                </span>
            </div>
            <div x-show="lastAutoSave">
                <span>Last saved: <span x-text="lastAutoSave ? new Date(lastAutoSave).toLocaleTimeString() : ''"></span></span>
            </div>
        </div>
        @endif

        {{-- Form content --}}
        {{ $slot }}

        {{-- Submit Button with Loading State --}}
        <div class="flex items-center gap-4 pt-4">
            <button
                type="submit"
                :disabled="isLoading('submit') || !canSubmit()"
                class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
                <i class="fas fa-spinner fa-spin" x-show="isLoading('submit')"></i>
                <span x-show="!isLoading('submit')">{{ $submitText ?? 'Submit' }}</span>
                <span x-show="isLoading('submit')" x-text="getLoadingMessage('submit')"></span>
            </button>

            <button
                type="button"
                @click="window.history.back()"
                class="btn btn-secondary"
            >
                Cancel
            </button>

            @if($hasAutoSave)
            <span class="text-sm text-gray-500">
                <i class="fas fa-save"></i>
                Auto-save enabled
            </span>
            @endif
        </div>
    </form>
</div>

{{-- Include necessary scripts --}}
@push('scripts')
<script type="module">
import { unsavedChanges } from '/js/mixins/unsaved-changes.js';
import { dateValidation } from '/js/mixins/date-validation.js';
import { characterCounter } from '/js/mixins/character-counter.js';
import { flashMessages } from '/js/mixins/flash-messages.js';
import { formValidation } from '/js/mixins/form-validation.js';
import { loadingStates } from '/js/mixins/loading-states.js';

window.unsavedChanges = unsavedChanges;
window.dateValidation = dateValidation;
window.characterCounter = characterCounter;
window.flashMessages = flashMessages;
window.formValidation = formValidation;
window.loadingStates = loadingStates;
</script>
@endpush
