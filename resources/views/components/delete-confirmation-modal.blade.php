@props([
    'name' => 'delete-confirmation',
    'title' => null,
    'resourceName' => null,
    'resourceType' => 'item',
    'deleteUrl' => null,
    'redirectUrl' => null,
    'warning' => null,
    'cascadeInfo' => null,
])

@php
    $defaultTitle = __('components.delete_modal.title');
@endphp

<div x-data="{
        showDeleteModal: false,
        deleteUrl: null,
        resourceName: null,
        cascadeInfo: null,
        isDeleting: false,

        openDeleteModal(url, name, cascade = null) {
            this.deleteUrl = url;
            this.resourceName = name;
            this.cascadeInfo = cascade;
            this.showDeleteModal = true;
        },

        async confirmDelete() {
            if (!this.deleteUrl) return;

            this.isDeleting = true;

            try {
                const response = await fetch(this.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    // Show success message
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            message: data.message || @json(__('components.delete_modal.success')),
                            type: 'success'
                        }
                    }));

                    // Redirect or reload
                    @if($redirectUrl)
                        window.location.href = '{{ $redirectUrl }}';
                    @else
                        // Reload the page after a short delay
                        setTimeout(() => window.location.reload(), 1000);
                    @endif
                } else {
                    // Show error message
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            message: data.message || @json(__('components.delete_modal.failed')),
                            type: 'error'
                        }
                    }));
                }
            } catch (error) {
                console.error('Delete error:', error);
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        message: @json(__('components.delete_modal.error')),
                        type: 'error'
                    }
                }));
            } finally {
                this.isDeleting = false;
                this.showDeleteModal = false;
            }
        }
    }"
    x-on:open-delete-modal.window="openDeleteModal($event.detail.url, $event.detail.name, $event.detail.cascade)"
    class="hidden"
>
    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true"
    >
        <!-- Background overlay -->
        <div x-show="showDeleteModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"
             @click="showDeleteModal = false"
        ></div>

        <!-- Modal panel -->
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                 @click.stop
            >
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <!-- Warning Icon -->
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="mt-3 text-center sm:{{ app()->getLocale() === 'ar' ? 'me' : 'ms' }}-4 sm:mt-0 sm:text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                {{ $title ?? $defaultTitle }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">
                                    {{ __('components.delete_modal.confirm_message') }}
                                    <span class="font-semibold text-gray-900" x-text="resourceName || '{{ $resourceName }}'"></span>؟
                                </p>

                                @if($warning)
                                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm text-yellow-800">
                                        <i class="fas fa-exclamation-triangle text-yellow-600 {{ app()->getLocale() === 'ar' ? 'ms' : 'me' }}-2"></i>
                                        {{ $warning }}
                                    </p>
                                </div>
                                @endif

                                <!-- Cascade Info (dynamic) -->
                                <template x-if="cascadeInfo">
                                    <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p class="text-sm text-blue-800 font-medium mb-1">
                                            <i class="fas fa-info-circle text-blue-600 {{ app()->getLocale() === 'ar' ? 'ms' : 'me' }}-2"></i>
                                            {{ __('components.delete_modal.cascade_info') }}
                                        </p>
                                        <ul class="text-sm text-blue-700 list-disc list-inside {{ app()->getLocale() === 'ar' ? 'me' : 'ms' }}-4" x-html="cascadeInfo"></ul>
                                    </div>
                                </template>

                                <p class="mt-3 text-sm text-gray-500">
                                    <i class="fas fa-undo text-gray-400 {{ app()->getLocale() === 'ar' ? 'ms' : 'me' }}-1"></i>
                                    {{ __('components.delete_modal.can_restore', ['item' => $resourceType]) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-3">
                    <button type="button"
                            @click="confirmDelete()"
                            :disabled="isDeleting"
                            class="inline-flex w-full justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 sm:{{ app()->getLocale() === 'ar' ? 'me' : 'ms' }}-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        <span x-show="!isDeleting">
                            <i class="fas fa-trash-alt {{ app()->getLocale() === 'ar' ? 'ms' : 'me' }}-2"></i>
                            {{ __('components.delete_modal.delete_button') }}
                        </span>
                        <span x-show="isDeleting">
                            <i class="fas fa-spinner fa-spin {{ app()->getLocale() === 'ar' ? 'ms' : 'me' }}-2"></i>
                            {{ __('components.delete_modal.deleting') }}
                        </span>
                    </button>
                    <button type="button"
                            @click="showDeleteModal = false"
                            :disabled="isDeleting"
                            class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        <i class="fas fa-times {{ app()->getLocale() === 'ar' ? 'ms' : 'me' }}-2"></i>
                        {{ __('components.delete_modal.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
