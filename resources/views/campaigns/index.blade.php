@extends('layouts.admin')

@section('title', __('campaigns.campaigns'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="max-w-7xl mx-auto" dir="{{ $dir }}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8 {{ $isRtl ? 'flex-row-reverse' : '' }}">
        <div class="{{ $isRtl ? 'text-right' : '' }}">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __('campaigns.campaigns') }}</h1>
            <p class="text-gray-600">{{ __('campaigns.manage_all_campaigns') }}</p>
        </div>
        <a href="{{ route('orgs.campaigns.create', ['org' => $currentOrg]) }}"
           data-testid="new-campaign-btn"
           id="new-campaign-btn"
           class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm {{ $isRtl ? 'flex-row-reverse' : '' }} transition-colors duration-200">
            <i class="fas fa-plus {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
            <span>{{ __('campaigns.new_campaign') }}</span>
        </a>
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

    <!-- Filters Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-6">
        <form method="GET" action="{{ route('orgs.campaigns.index', ['org' => $currentOrg]) }}">
            <div class="flex flex-wrap gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <!-- Search Input -->
                <div class="flex-1 min-w-[250px]">
                    <div class="relative">
                        <div class="absolute inset-y-0 {{ $isRtl ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search"
                               placeholder="{{ __('campaigns.search_placeholder') }}"
                               value="{{ request('search') }}"
                               dir="{{ $dir }}"
                               class="w-full {{ $isRtl ? 'pr-10 pl-4 text-right' : 'pl-10 pr-4' }} py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="min-w-[180px]">
                    <select name="status"
                            dir="{{ $dir }}"
                            data-testid="status-filter"
                            id="status-filter"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $isRtl ? 'text-right' : '' }} transition-colors">
                        <option value="">{{ __('campaigns.all_statuses') }}</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('campaigns.status.draft') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('campaigns.status.active') }}</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>{{ __('campaigns.status.paused') }}</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('campaigns.status.completed') }}</option>
                    </select>
                </div>

                <!-- Filter Button -->
                <button type="submit"
                        class="px-6 py-3 bg-gray-700 hover:bg-gray-800 text-white font-medium rounded-lg inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} transition-colors duration-200 shadow-sm">
                    <i class="fas fa-filter {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    <span>{{ __('campaigns.filter') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Campaigns Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($campaigns ?? [] as $campaign)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-200 overflow-hidden">
                <!-- Card Header with Gradient -->
                <div class="px-6 pt-6 pb-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-gray-50 to-white border-b border-gray-100">
                    <div class="flex justify-between items-start mb-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <h3 class="text-xl font-bold text-gray-900 {{ $isRtl ? 'text-right flex-1' : 'flex-1' }}" dir="{{ $dir }}">
                            {{ $campaign->name }}
                        </h3>
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full {{ $isRtl ? 'mr-3' : 'ml-3' }} shadow-sm
                            @if($campaign->status === 'active') bg-green-100 text-green-800 ring-2 ring-green-200
                            @elseif($campaign->status === 'paused') bg-yellow-100 text-yellow-800 ring-2 ring-yellow-200
                            @elseif($campaign->status === 'draft') bg-gray-100 text-gray-800 ring-2 ring-gray-200
                            @else bg-blue-100 text-blue-800 ring-2 ring-blue-200
                            @endif">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isRtl ? 'ml-1.5' : 'mr-1.5' }}
                                @if($campaign->status === 'active') bg-green-600
                                @elseif($campaign->status === 'paused') bg-yellow-600
                                @elseif($campaign->status === 'draft') bg-gray-600
                                @else bg-blue-600
                                @endif"></span>
                            {{ __('campaigns.status.' . ($campaign->status ?? 'draft')) }}
                        </span>
                    </div>
                    <p class="text-gray-600 text-sm line-clamp-2 {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                        {{ $campaign->description ?? __('campaigns.no_description') }}
                    </p>
                </div>

                <!-- Card Body with Stats -->
                <div class="px-6 py-5">
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <!-- Budget -->
                        <div class="p-3 bg-green-50 rounded-lg border border-green-100">
                            <div class="flex items-center mb-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-dollar-sign text-green-600 text-xs {{ $isRtl ? 'ml-1.5' : 'mr-1.5' }}"></i>
                                <p class="text-xs text-gray-600 font-medium">{{ __('campaigns.budget') }}</p>
                            </div>
                            <p class="text-lg font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="ltr">
                                <span class="{{ $isRtl ? 'mr-0.5' : '' }}">{{ $isRtl ? 'ر.س' : '$' }}</span>{{ number_format($campaign->budget ?? 0, 2) }}
                            </p>
                        </div>

                        <!-- Spent -->
                        <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="flex items-center mb-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-chart-line text-blue-600 text-xs {{ $isRtl ? 'ml-1.5' : 'mr-1.5' }}"></i>
                                <p class="text-xs text-gray-600 font-medium">{{ __('campaigns.spent') }}</p>
                            </div>
                            <p class="text-lg font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="ltr">
                                <span class="{{ $isRtl ? 'mr-0.5' : '' }}">{{ $isRtl ? 'ر.س' : '$' }}</span>{{ number_format($campaign->spend ?? 0, 2) }}
                            </p>
                        </div>

                        <!-- Impressions -->
                        <div class="p-3 bg-purple-50 rounded-lg border border-purple-100">
                            <div class="flex items-center mb-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-eye text-purple-600 text-xs {{ $isRtl ? 'ml-1.5' : 'mr-1.5' }}"></i>
                                <p class="text-xs text-gray-600 font-medium">{{ __('campaigns.metrics.impressions') }}</p>
                            </div>
                            <p class="text-lg font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="ltr">
                                {{ number_format($campaign->impressions ?? 0) }}
                            </p>
                        </div>

                        <!-- Clicks -->
                        <div class="p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                            <div class="flex items-center mb-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-mouse-pointer text-indigo-600 text-xs {{ $isRtl ? 'ml-1.5' : 'mr-1.5' }}"></i>
                                <p class="text-xs text-gray-600 font-medium">{{ __('campaigns.metrics.clicks') }}</p>
                            </div>
                            <p class="text-lg font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" dir="ltr">
                                {{ number_format($campaign->clicks ?? 0) }}
                            </p>
                        </div>
                    </div>

                    <!-- Card Footer with Actions -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-100 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <a href="{{ route('orgs.campaigns.show', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id ?? $campaign->id]) }}"
                           class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium {{ $isRtl ? 'flex-row-reverse' : '' }} transition-colors">
                            <span>{{ __('campaigns.view_details') }}</span>
                            <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} {{ $isRtl ? 'mr-2' : 'ml-2' }} text-xs"></i>
                        </a>
                        <div class="flex {{ $isRtl ? 'space-x-reverse space-x-3' : 'space-x-3' }}">
                            <a href="{{ route('orgs.campaigns.edit', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id ?? $campaign->id]) }}"
                               class="w-8 h-8 flex items-center justify-center text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                               title="{{ __('campaigns.edit_campaign') }}">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('orgs.campaigns.destroy', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id ?? $campaign->id]) }}"
                                  onsubmit="return confirm('{{ __('campaigns.confirm_delete') }}');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center text-red-600 hover:text-red-900 hover:bg-red-50 rounded-lg transition-colors"
                                        title="{{ __('campaigns.delete_campaign') }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <!-- Empty State -->
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-100 p-16 text-center">
                <div class="max-w-md mx-auto">
                    <div class="w-24 h-24 mx-auto bg-indigo-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-bullhorn text-4xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.no_campaigns_yet') }}
                    </h3>
                    <p class="text-gray-600 mb-8 text-lg {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                        {{ __('campaigns.get_started') }}
                    </p>
                    <a href="{{ route('orgs.campaigns.create', ['org' => $currentOrg]) }}"
                       class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm {{ $isRtl ? 'flex-row-reverse' : '' }} transition-colors duration-200">
                        <i class="fas fa-plus {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        <span>{{ __('campaigns.create_campaign') }}</span>
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($campaigns) && method_exists($campaigns, 'links'))
        <div class="mt-8 {{ $isRtl ? 'flex justify-end' : '' }}">
            <div class="{{ $isRtl ? 'rtl-pagination' : '' }}">
                {{ $campaigns->links() }}
            </div>
        </div>
    @endif
</div>

<!-- Custom RTL Pagination Styles -->
@if($isRtl)
<style>
.rtl-pagination nav {
    direction: rtl;
}
.rtl-pagination .flex {
    flex-direction: row-reverse;
}
</style>
@endif
@endsection
