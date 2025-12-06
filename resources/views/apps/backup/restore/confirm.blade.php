@extends('layouts.admin')

@section('title', __('backup.confirm_restore'))

@section('content')
<div x-data="confirmRestore()" class="container mx-auto px-4 py-6">
    <!-- Header with Steps -->
    @include('apps.backup.restore.partials.wizard-steps', ['currentStep' => 4])

    <form action="{{ route('orgs.backup.restore.process', ['org' => $org, 'restore' => $restore->id]) }}"
          method="POST"
          @submit="handleSubmit">
        @csrf

        <!-- Restore Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-clipboard-list me-2 text-blue-500"></i>
                    {{ __('backup.restore_summary') }}
                </h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Backup Info -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            {{ __('backup.backup_info') }}
                        </h3>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.backup_name') }}:</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $backup->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.backup_code') }}:</span>
                                <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $backup->backup_code }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.created_at') }}:</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $backup->created_at->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.file_size') }}:</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ formatBytes($backup->file_size) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Restore Settings -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            {{ __('backup.restore_settings') }}
                        </h3>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.restore_type') }}:</span>
                                @include('apps.backup.partials.restore-type-badge', ['type' => $restore->type])
                            </div>
                            @if($restore->type === 'selective' && $restore->selected_categories)
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.selected_categories') }}:</span>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($restore->selected_categories as $category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ __('backup.category_' . Str::snake($category)) }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.conflict_strategy') }}:</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('backup.strategy_' . ($restore->conflict_resolution['strategy'] ?? 'skip')) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Organization Target -->
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                        {{ __('backup.target_organization') }}
                    </h3>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900">
                                <i class="fas fa-building text-blue-600 dark:text-blue-400 text-xl"></i>
                            </span>
                            <div>
                                <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                                    {{ $organization?->name ?? __('backup.current_organization') }}
                                </p>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    {{ __('backup.data_will_be_restored_here') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-shield-alt me-2 text-green-500"></i>
                    {{ __('backup.confirmation_required') }}
                </h2>
            </div>

            <div class="p-6">
                <!-- Selective Restore: Simple Confirmation -->
                @if($restore->type === 'selective')
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-green-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-green-800 dark:text-green-200">
                                {{ __('backup.selective_restore_info') }}
                            </p>
                            <p class="text-sm text-green-700 dark:text-green-300 mt-2">
                                {{ __('backup.safety_backup_created') }}
                            </p>
                        </div>
                    </div>
                </div>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" x-model="confirmed"
                           class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('backup.confirm_selective_restore') }}
                    </span>
                </label>
                @endif

                <!-- Merge Restore: Type Organization Name -->
                @if($restore->type === 'merge')
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                {{ __('backup.merge_restore_warning_title') }}
                            </p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                {{ __('backup.merge_restore_warning') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('backup.type_org_name_to_confirm') }}
                    </label>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        {{ __('backup.type_exactly') }}: <strong class="text-gray-900 dark:text-white">{{ $organization?->name }}</strong>
                    </p>
                    <input type="text"
                           name="org_name_confirmation"
                           x-model="orgNameInput"
                           :placeholder="'{{ $organization?->name }}'"
                           class="w-full max-w-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                @endif

                <!-- Full Restore: Type Organization Name + Email Verification -->
                @if($restore->type === 'full')
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ __('backup.full_restore_warning_title') }}
                            </p>
                            <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                                <li>{{ __('backup.full_restore_warning_1') }}</li>
                                <li>{{ __('backup.full_restore_warning_2') }}</li>
                                <li>{{ __('backup.full_restore_warning_3') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Organization Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('backup.type_org_name_to_confirm') }}
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                            {{ __('backup.type_exactly') }}: <strong class="text-gray-900 dark:text-white">{{ $organization?->name }}</strong>
                        </p>
                        <input type="text"
                               name="org_name_confirmation"
                               x-model="orgNameInput"
                               :placeholder="'{{ $organization?->name }}'"
                               class="w-full max-w-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Email Verification -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('backup.enter_verification_code') }}
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                            {{ __('backup.verification_code_sent_to', ['email' => auth()->user()->email]) }}
                        </p>
                        <div class="flex gap-2 max-w-md">
                            <input type="text"
                                   name="verification_code"
                                   x-model="verificationCode"
                                   placeholder="000000"
                                   maxlength="6"
                                   class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono text-center text-lg tracking-widest">
                            <button type="button"
                                    @click="sendVerificationCode"
                                    :disabled="codeSending || codeCooldown > 0"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors">
                                <span x-show="!codeSending && codeCooldown === 0">{{ __('backup.send_code') }}</span>
                                <span x-show="codeSending"><i class="fas fa-spinner fa-spin"></i></span>
                                <span x-show="codeCooldown > 0" x-text="codeCooldown + 's'"></span>
                            </button>
                        </div>
                        <p x-show="codeSent" class="mt-2 text-sm text-green-600 dark:text-green-400">
                            <i class="fas fa-check me-1"></i>
                            {{ __('backup.code_sent') }}
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Safety Backup Notice -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <i class="fas fa-shield-alt text-blue-500 mt-0.5"></i>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('backup.safety_backup_notice_title') }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('backup.safety_backup_notice') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            @php
                $backRoute = !empty($restore->conflict_resolution['preview']['total'])
                    ? route('orgs.backup.restore.conflicts', ['org' => $org, 'restore' => $restore->id])
                    : route('orgs.backup.restore.select', ['org' => $org, 'restore' => $restore->id]);
            @endphp
            <a href="{{ $backRoute }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-start me-2"></i>
                {{ __('common.back') }}
            </a>

            <button type="submit"
                    :disabled="!canProceed"
                    :class="[
                        !canProceed ? 'opacity-50 cursor-not-allowed' : '',
                        '{{ $restore->type === "full" ? "bg-red-600 hover:bg-red-700" : "bg-blue-600 hover:bg-blue-700" }}'
                    ]"
                    class="inline-flex items-center px-6 py-2 text-white rounded-lg transition-colors">
                <i class="fas fa-play me-2"></i>
                {{ __('backup.start_restore') }}
            </button>
        </div>
    </form>
</div>

<script>
function confirmRestore() {
    return {
        restoreType: '{{ $restore->type }}',
        orgName: '{{ $organization?->name }}',
        orgNameInput: '',
        verificationCode: '',
        confirmed: false,
        codeSending: false,
        codeSent: false,
        codeCooldown: 0,

        get canProceed() {
            if (this.restoreType === 'selective') {
                return this.confirmed;
            }
            if (this.restoreType === 'merge') {
                return this.orgNameInput === this.orgName;
            }
            if (this.restoreType === 'full') {
                return this.orgNameInput === this.orgName && this.verificationCode.length === 6;
            }
            return false;
        },

        async sendVerificationCode() {
            if (this.codeSending || this.codeCooldown > 0) return;

            this.codeSending = true;
            try {
                // TODO: Implement actual API call
                await new Promise(resolve => setTimeout(resolve, 1000));
                this.codeSent = true;
                this.codeCooldown = 60;

                const interval = setInterval(() => {
                    this.codeCooldown--;
                    if (this.codeCooldown <= 0) {
                        clearInterval(interval);
                    }
                }, 1000);
            } catch (error) {
                alert('{{ __("backup.code_send_failed") }}');
            } finally {
                this.codeSending = false;
            }
        },

        handleSubmit(event) {
            if (!this.canProceed) {
                event.preventDefault();
                return;
            }

            // Final confirmation for full restore
            if (this.restoreType === 'full') {
                if (!confirm('{{ __("backup.final_full_restore_confirm") }}')) {
                    event.preventDefault();
                }
            }
        }
    };
}
</script>
@endsection
