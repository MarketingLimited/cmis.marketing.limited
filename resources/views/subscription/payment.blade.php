@extends('layouts.admin')

@section('title', __('subscription.billing_payment'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('subscription.status') }}" class="hover:text-blue-600 transition">
                {{ __('subscription.subscription_status') }}
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('subscription.billing_payment') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('subscription.billing_payment') }}</h1>
    </div>

    {{-- Billing Overview --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('subscription.billing_overview') }}</h2>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">{{ __('subscription.current_plan') }}</span>
                    <span class="font-medium">{{ ucfirst($billing['plan']) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">{{ __('subscription.billing_email') }}</span>
                    <span class="font-medium">{{ $billing['billing_email'] }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">{{ __('subscription.billing_period') }}</span>
                    <span class="font-medium">{{ $billing['current_period_start'] }} - {{ $billing['current_period_end'] }}</span>
                </div>
                @if($billing['next_invoice_amount'])
                <div class="flex justify-between py-2">
                    <span class="text-gray-600">{{ __('subscription.next_invoice') }}</span>
                    <span class="font-medium">${{ $billing['next_invoice_amount'] }}</span>
                </div>
                @endif
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-3">{{ __('subscription.payment_method') }}</h3>
                @if($billing['payment_method'])
                    <div class="flex items-center gap-3">
                        <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                        <div>
                            <p class="font-medium">**** **** **** {{ $billing['payment_method']['last4'] ?? '****' }}</p>
                            <p class="text-sm text-gray-500">{{ __('subscription.expires') }} {{ $billing['payment_method']['exp_month'] ?? '--' }}/{{ $billing['payment_method']['exp_year'] ?? '--' }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-600 mb-3">{{ __('subscription.no_payment_method') }}</p>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-plus me-2"></i>
                        {{ __('subscription.add_payment_method') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Invoice History --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('subscription.invoice_history') }}</h2>

        @if(count($invoices) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">{{ __('subscription.date') }}</th>
                            <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">{{ __('subscription.description') }}</th>
                            <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">{{ __('subscription.amount') }}</th>
                            <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">{{ __('subscription.status') }}</th>
                            <th class="text-end py-3 px-4 text-sm font-medium text-gray-500">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4 text-sm">{{ $invoice['date'] }}</td>
                            <td class="py-3 px-4 text-sm">{{ $invoice['description'] }}</td>
                            <td class="py-3 px-4 text-sm">${{ $invoice['amount'] }}</td>
                            <td class="py-3 px-4 text-sm">
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($invoice['status'] === 'paid') bg-green-100 text-green-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($invoice['status']) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-end">
                                <a href="#" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-download"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-file-invoice text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">{{ __('subscription.no_invoices') }}</p>
            </div>
        @endif
    </div>

    {{-- Payment Integration Notice --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
            <div>
                <h3 class="font-medium text-yellow-800">{{ __('subscription.payment_integration_notice_title') }}</h3>
                <p class="text-sm text-yellow-700 mt-1">{{ __('subscription.payment_integration_notice') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
