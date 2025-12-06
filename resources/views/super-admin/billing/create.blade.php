@extends('super-admin.layouts.app')

@section('title', __('super_admin.billing.create_invoice'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.billing.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.billing.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.billing.create_invoice') }}</span>
@endsection

@section('content')
<div x-data="createInvoice()" class="max-w-3xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.billing.create_invoice') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('super_admin.billing.create_invoice_desc') }}</p>
    </div>

    <!-- Form -->
    <form @submit.prevent="submitForm" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 space-y-6">
            <!-- Organization Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('super_admin.billing.organization') }} <span class="text-red-500">*</span>
                </label>
                <select x-model="form.org_id" required
                        @change="loadSubscription()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">{{ __('super_admin.billing.select_organization') }}</option>
                    @foreach($organizations as $org)
                    <option value="{{ $org->org_id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Subscription Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('super_admin.billing.subscription') }}
                </label>
                <select x-model="form.subscription_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">{{ __('super_admin.billing.no_subscription') }}</option>
                    @foreach($subscriptions as $subscription)
                    <option value="{{ $subscription->subscription_id }}" data-org="{{ $subscription->org_id }}">
                        {{ $subscription->plan->name ?? 'Plan' }} - {{ $subscription->organization->name ?? 'Org' }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Amount -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('super_admin.billing.amount') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500">$</span>
                        <input type="number" x-model="form.amount" step="0.01" min="0" required
                               class="w-full ps-8 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('super_admin.billing.tax') }}
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500">$</span>
                        <input type="number" x-model="form.tax_amount" step="0.01" min="0"
                               class="w-full ps-8 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('super_admin.billing.discount') }}
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500">$</span>
                        <input type="number" x-model="form.discount_amount" step="0.01" min="0"
                               class="w-full ps-8 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Total Display -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-medium text-gray-700 dark:text-gray-300">{{ __('super_admin.billing.total') }}</span>
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">$<span x-text="calculateTotal()"></span></span>
                </div>
            </div>

            <!-- Currency -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('super_admin.billing.currency') }}
                </label>
                <select x-model="form.currency"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="SAR">SAR - Saudi Riyal</option>
                    <option value="AED">AED - UAE Dirham</option>
                </select>
            </div>

            <!-- Due Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('super_admin.billing.due_date') }} <span class="text-red-500">*</span>
                </label>
                <input type="date" x-model="form.due_date" required :min="today"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <!-- Billing Period -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('super_admin.billing.billing_period_start') }}
                    </label>
                    <input type="date" x-model="form.billing_period_start"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('super_admin.billing.billing_period_end') }}
                    </label>
                    <input type="date" x-model="form.billing_period_end"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('super_admin.billing.description') }}
                </label>
                <textarea x-model="form.description" rows="3"
                          placeholder="{{ __('super_admin.billing.description_placeholder') }}"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 rounded-b-xl flex justify-end gap-3">
            <a href="{{ route('super-admin.billing.invoices') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                {{ __('common.cancel') }}
            </a>
            <button type="submit" :disabled="submitting"
                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50">
                <span x-show="!submitting">{{ __('super_admin.billing.create_invoice') }}</span>
                <span x-show="submitting" x-cloak>
                    <i class="fas fa-spinner fa-spin me-2"></i>{{ __('common.processing') }}
                </span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function createInvoice() {
    return {
        submitting: false,
        today: new Date().toISOString().split('T')[0],
        form: {
            org_id: '',
            subscription_id: '',
            amount: '',
            tax_amount: '',
            discount_amount: '',
            currency: 'USD',
            due_date: '',
            billing_period_start: '',
            billing_period_end: '',
            description: ''
        },

        calculateTotal() {
            const amount = parseFloat(this.form.amount) || 0;
            const tax = parseFloat(this.form.tax_amount) || 0;
            const discount = parseFloat(this.form.discount_amount) || 0;
            return (amount + tax - discount).toFixed(2);
        },

        loadSubscription() {
            // Auto-select subscription if org is selected
            const orgId = this.form.org_id;
            if (orgId) {
                const subscriptionSelect = document.querySelector('select[x-model="form.subscription_id"]');
                const options = subscriptionSelect.querySelectorAll('option');
                options.forEach(option => {
                    if (option.dataset.org === orgId) {
                        this.form.subscription_id = option.value;
                    }
                });
            }
        },

        async submitForm() {
            if (this.submitting) return;
            this.submitting = true;

            try {
                const response = await fetch('/super-admin/billing/invoices', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = `/super-admin/billing/invoices/${data.data.invoice_id}`;
                } else {
                    alert(data.message || '{{ __('common.error') }}');
                }
            } catch (e) {
                alert('{{ __('common.error') }}');
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endpush
