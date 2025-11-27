@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('Campaign Analytics'))

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.analytics.enterprise', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Analytics') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Campaigns') }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Campaign Analytics') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('Performance analysis for all campaigns') }}</p>
            </div>
            <a href="{{ route('orgs.campaigns.create', ['org' => $currentOrg]) }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>{{ __('New Campaign') }}</span>
            </a>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
                <div class="relative">
                    <input type="text" placeholder="{{ __('Search campaigns...') }}"
                           class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <div class="flex gap-3">
                <select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="paused">{{ __('Paused') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="draft">{{ __('Draft') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Campaigns Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($campaigns as $campaign)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition">
            {{-- Campaign Header --}}
            <div class="p-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-lg line-clamp-2">{{ $campaign->name }}</h3>
                    <span class="px-2 py-1 bg-white/20 backdrop-blur-sm text-xs rounded-full">
                        {{ ucfirst($campaign->status) }}
                    </span>
                </div>
                @if($campaign->description)
                <p class="text-sm text-white/80 line-clamp-2">{{ $campaign->description }}</p>
                @endif
            </div>

            {{-- Campaign Body --}}
            <div class="p-6 space-y-4">
                {{-- Date Range --}}
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="fas fa-calendar text-gray-400"></i>
                    <span>
                        {{ \Carbon\Carbon::parse($campaign->start_date)->format('M d, Y') }} -
                        {{ $campaign->end_date ? \Carbon\Carbon::parse($campaign->end_date)->format('M d, Y') : __('Ongoing') }}
                    </span>
                </div>

                {{-- Budget --}}
                @if($campaign->budget)
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="fas fa-dollar-sign text-gray-400"></i>
                    <span>{{ __('Budget') }}: ${{ number_format($campaign->budget, 2) }}</span>
                </div>
                @endif

                {{-- Quick Stats --}}
                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-xs text-gray-600">{{ __('Impressions') }}</p>
                        <p class="text-lg font-bold text-gray-900">--</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600">{{ __('CTR') }}</p>
                        <p class="text-lg font-bold text-blue-600">--</p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 pt-3 border-t border-gray-200">
                    <a href="{{ route('orgs.analytics.campaign', ['org' => $currentOrg, 'campaign_id' => $campaign->campaign_id]) }}"
                       class="flex-1 text-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg text-sm font-medium transition">
                        <i class="fas fa-chart-line mr-1"></i>{{ __('Analytics') }}
                    </a>
                    <a href="{{ route('orgs.campaigns.show', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}"
                       class="flex-1 text-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-eye mr-1"></i>{{ __('View') }}
                    </a>
                    <a href="{{ route('orgs.campaigns.edit', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}"
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        {{-- Empty State --}}
        <div class="col-span-full text-center py-16">
            <div class="bg-gray-50 rounded-2xl p-12 inline-block">
                <i class="fas fa-bullhorn text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-bold text-gray-700 mb-2">{{ __('No Campaigns Found') }}</h3>
                <p class="text-gray-500 mb-6">{{ __('Get started by creating your first campaign') }}</p>
                <a href="{{ route('orgs.campaigns.create', ['org' => $currentOrg]) }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition">
                    <i class="fas fa-plus mr-2"></i>
                    <span>{{ __('Create New Campaign') }}</span>
                </a>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($campaigns->hasPages())
    <div class="bg-white rounded-xl shadow-sm p-6">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>
@endsection
