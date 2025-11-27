@extends('layouts.admin')

@section('title', __('campaigns.campaign_details'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="max-w-7xl mx-auto" dir="{{ $dir }}">
    <!-- Navigation & Actions Header -->
    <div class="mb-6 flex justify-between items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
        <a href="{{ route('orgs.campaigns.index', ['org' => $currentOrg]) }}"
           class="text-indigo-600 hover:text-indigo-800 inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} transition-colors duration-200">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }} {{ $isRtl ? 'ml-2' : 'mr-2' }} text-sm"></i>
            <span class="font-medium">{{ __('campaigns.back_to_campaigns') }}</span>
        </a>
        <div class="flex {{ $isRtl ? 'space-x-reverse space-x-3' : 'space-x-3' }}">
            <a href="{{ route('orgs.campaigns.edit', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id ?? $campaign->id]) }}"
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} transition-colors duration-200 shadow-sm">
                <i class="fas fa-edit {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                <span>{{ __('campaigns.edit_campaign') }}</span>
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-800 px-6 py-4 rounded-lg {{ $isRtl ? 'text-right border-r-4 border-l-0' : '' }} shadow-sm">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-check-circle {{ $isRtl ? 'ml-3' : 'mr-3' }} text-green-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Campaign Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mb-6">
        <div class="flex justify-between items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }} flex-1">
                <h1 class="text-3xl font-bold text-gray-900 mb-3" dir="{{ $dir }}">{{ $campaign->name }}</h1>
                <p class="text-gray-600 text-lg leading-relaxed" dir="{{ $dir }}">
                    {{ $campaign->description ?? __('campaigns.no_description') }}
                </p>
            </div>
            <div class="{{ $isRtl ? 'mr-6' : 'ml-6' }}">
                <span class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-full shadow-sm
                    @if($campaign->status === 'active') bg-green-100 text-green-800 ring-2 ring-green-200
                    @elseif($campaign->status === 'paused') bg-yellow-100 text-yellow-800 ring-2 ring-yellow-200
                    @elseif($campaign->status === 'draft') bg-gray-100 text-gray-800 ring-2 ring-gray-200
                    @else bg-blue-100 text-blue-800 ring-2 ring-blue-200
                    @endif">
                    <span class="w-2 h-2 rounded-full {{ $isRtl ? 'ml-2' : 'mr-2' }}
                        @if($campaign->status === 'active') bg-green-600
                        @elseif($campaign->status === 'paused') bg-yellow-600
                        @elseif($campaign->status === 'draft') bg-gray-600
                        @else bg-blue-600
                        @endif"></span>
                    {{ __('campaigns.status.' . ($campaign->status ?? 'draft')) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Campaign Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Budget Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-2xl text-green-600"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-4 text-right flex-1' : 'ml-4 flex-1' }}">
                    <p class="text-sm font-medium text-gray-500 mb-1">{{ __('campaigns.budget') }}</p>
                    <p class="text-2xl font-bold text-gray-900" dir="ltr">
                        <span class="{{ $isRtl ? 'mr-1' : '' }}">{{ $isRtl ? 'ر.س' : '$' }}</span>{{ number_format($campaign->budget ?? 0, 2) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Spent Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-4 text-right flex-1' : 'ml-4 flex-1' }}">
                    <p class="text-sm font-medium text-gray-500 mb-1">{{ __('campaigns.spent') }}</p>
                    <p class="text-2xl font-bold text-gray-900" dir="ltr">
                        <span class="{{ $isRtl ? 'mr-1' : '' }}">{{ $isRtl ? 'ر.س' : '$' }}</span>{{ number_format($campaign->spend ?? 0, 2) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Impressions Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-eye text-2xl text-purple-600"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-4 text-right flex-1' : 'ml-4 flex-1' }}">
                    <p class="text-sm font-medium text-gray-500 mb-1">{{ __('campaigns.metrics.impressions') }}</p>
                    <p class="text-2xl font-bold text-gray-900" dir="ltr">{{ number_format($campaign->impressions ?? 0) }}</p>
                </div>
            </div>
        </div>

        <!-- Clicks Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 w-14 h-14 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-mouse-pointer text-2xl text-indigo-600"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-4 text-right flex-1' : 'ml-4 flex-1' }}">
                    <p class="text-sm font-medium text-gray-500 mb-1">{{ __('campaigns.metrics.clicks') }}</p>
                    <p class="text-2xl font-bold text-gray-900" dir="ltr">{{ number_format($campaign->clicks ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Details & Performance Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Campaign Information Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <h2 class="text-xl font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">
                    {{ __('campaigns.campaign_information') }}
                </h2>
                <i class="fas fa-info-circle text-gray-400"></i>
            </div>
            <dl class="space-y-4">
                <div class="pb-4 border-b border-gray-100">
                    <dt class="text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.campaign_type_label') }}
                    </dt>
                    <dd class="text-base font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                        {{ ucfirst($campaign->campaign_type ?? __('campaigns.na')) }}
                    </dd>
                </div>
                <div class="pb-4 border-b border-gray-100">
                    <dt class="text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.objective') }}
                    </dt>
                    <dd class="text-base font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                        {{ $campaign->objective ? __('campaigns.objectives.' . $campaign->objective) : __('campaigns.na') }}
                    </dd>
                </div>
                <div class="pb-4 border-b border-gray-100">
                    <dt class="text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.start_date') }}
                    </dt>
                    <dd class="text-base font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="ltr">
                        {{ $campaign->start_date?->format('M d, Y') ?? __('campaigns.na') }}
                    </dd>
                </div>
                <div class="pb-4 border-b border-gray-100">
                    <dt class="text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.end_date') }}
                    </dt>
                    <dd class="text-base font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="ltr">
                        {{ $campaign->end_date?->format('M d, Y') ?? __('campaigns.ongoing') }}
                    </dd>
                </div>
                <div class="pb-4 border-b border-gray-100">
                    <dt class="text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.target_audience') }}
                    </dt>
                    <dd class="text-base font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                        {{ $campaign->target_audience ?? __('campaigns.na') }}
                    </dd>
                </div>
                <div class="pb-4 border-b border-gray-100">
                    <dt class="text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.created') }}
                    </dt>
                    <dd class="text-base font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="ltr">
                        {{ $campaign->created_at?->format('M d, Y h:i A') ?? __('campaigns.na') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.last_updated') }}
                    </dt>
                    <dd class="text-base font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="ltr">
                        {{ $campaign->updated_at?->format('M d, Y h:i A') ?? __('campaigns.na') }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Performance Metrics Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <h2 class="text-xl font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">
                    {{ __('campaigns.performance_metrics') }}
                </h2>
                <i class="fas fa-chart-bar text-gray-400"></i>
            </div>

            <!-- Budget Utilization Progress -->
            <div class="mb-6 pb-6 border-b border-gray-100">
                <div class="flex justify-between items-center mb-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <span class="text-sm font-medium text-gray-700">{{ __('campaigns.budget_utilization') }}</span>
                    <span class="text-sm font-bold text-indigo-600" dir="ltr">
                        {{ $campaign->budget > 0 ? number_format(($campaign->spend / $campaign->budget) * 100, 1) : '0' }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden" dir="ltr">
                    @php
                        $utilization = $campaign->budget > 0 ? min((($campaign->spend / $campaign->budget) * 100), 100) : 0;
                    @endphp
                    <div class="bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-indigo-500 to-indigo-600 h-3 rounded-full transition-all duration-500 shadow-sm"
                         style="width: {{ $utilization }}%"></div>
                </div>
            </div>

            <!-- Performance Metrics Grid -->
            <div class="grid grid-cols-2 gap-4">
                <!-- CTR Card -->
                <div class="text-center p-5 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-percentage text-indigo-600 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        <p class="text-sm font-medium text-gray-600">{{ __('campaigns.metrics.ctr') }}</p>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" dir="ltr">
                        {{ $campaign->impressions > 0 ? number_format(($campaign->clicks / $campaign->impressions) * 100, 2) : '0.00' }}%
                    </p>
                </div>

                <!-- CPC Card -->
                <div class="text-center p-5 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-money-bill-wave text-green-600 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        <p class="text-sm font-medium text-gray-600">{{ __('campaigns.metrics.cost_per_click') }}</p>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" dir="ltr">
                        <span class="{{ $isRtl ? 'mr-1' : '' }}">{{ $isRtl ? 'ر.س' : '$' }}</span>{{ $campaign->clicks > 0 ? number_format($campaign->spend / $campaign->clicks, 2) : '0.00' }}
                    </p>
                </div>

                <!-- Conversions Card -->
                <div class="text-center p-5 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-check-circle text-purple-600 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        <p class="text-sm font-medium text-gray-600">{{ __('campaigns.metrics.conversions') }}</p>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" dir="ltr">{{ number_format($campaign->conversions ?? 0) }}</p>
                </div>

                <!-- Conversion Rate Card -->
                <div class="text-center p-5 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-chart-line text-blue-600 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        <p class="text-sm font-medium text-gray-600">{{ __('campaigns.conversion_rate_label') }}</p>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" dir="ltr">
                        {{ $campaign->clicks > 0 ? number_format((($campaign->conversions ?? 0) / $campaign->clicks) * 100, 2) : '0.00' }}%
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Assets Section -->
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <div class="flex items-center justify-between mb-6 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <h2 class="text-xl font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">
                {{ __('campaigns.campaign_assets') }}
            </h2>
            <i class="fas fa-images text-gray-400"></i>
        </div>
        <div class="text-center py-12">
            <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-image text-3xl text-gray-400"></i>
            </div>
            <p class="text-gray-600 text-lg {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                {{ __('campaigns.no_assets_yet') }}
            </p>
            <p class="text-gray-500 text-sm mt-2 {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                {{ __('campaigns.upload_assets_to_get_started') }}
            </p>
        </div>
    </div>
</div>
@endsection
