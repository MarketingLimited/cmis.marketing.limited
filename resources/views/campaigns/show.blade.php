@extends('layouts.admin')

@section('title', 'Campaign Details')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{  route('orgs.campaigns.index', ['org' => $currentOrg])  }}" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Campaigns
        </a>
        <div class="flex space-x-3">
            <a href="{{  route('orgs.campaigns.edit', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id ?? $campaign->id])  }}"
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                <i class="fas fa-edit mr-2"></i>
                Edit Campaign
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
            {{  session('success')  }}
        </div>
    @endif

    <!-- Campaign Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{  $campaign->name  }}</h1>
                <p class="text-gray-600">{{  $campaign->description ?? 'No description provided.'  }}</p>
            </div>
            <span class="px-3 py-1 text-sm font-semibold rounded-full
                @if($campaign->status === 'active') bg-green-100 text-green-800
                @elseif($campaign->status === 'paused') bg-yellow-100 text-yellow-800
                @elseif($campaign->status === 'draft') bg-gray-100 text-gray-800
                @else bg-blue-100 text-blue-800
                @endif">
                {{  ucfirst($campaign->status ?? 'draft')  }}
            </span>
        </div>
    </div>

    <!-- Campaign Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-dollar-sign text-3xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Budget</p>
                    <p class="text-2xl font-bold text-gray-900">${{  number_format($campaign->budget ?? 0, 2)  }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-line text-3xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Spent</p>
                    <p class="text-2xl font-bold text-gray-900">${{  number_format($campaign->spend ?? 0, 2)  }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-eye text-3xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Impressions</p>
                    <p class="text-2xl font-bold text-gray-900">{{  number_format($campaign->impressions ?? 0)  }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-mouse-pointer text-3xl text-indigo-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Clicks</p>
                    <p class="text-2xl font-bold text-gray-900">{{  number_format($campaign->clicks ?? 0)  }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Details & Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Campaign Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Campaign Information</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Campaign Type</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{  ucfirst($campaign->campaign_type ?? 'N/A')  }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Objective</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{  $campaign->objective ?? 'N/A'  }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{  $campaign->start_date?->format('M d, Y') ?? 'N/A'  }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">End Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{  $campaign->end_date?->format('M d, Y') ?? 'Ongoing'  }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Target Audience</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{  $campaign->target_audience ?? 'N/A'  }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{  $campaign->created_at?->format('M d, Y h:i A') ?? 'N/A'  }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{  $campaign->updated_at?->format('M d, Y h:i A') ?? 'N/A'  }}</dd>
                </div>
            </dl>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Performance Metrics</h2>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Budget Utilization</span>
                        <span class="text-sm font-medium text-gray-700">
                            {{  $campaign->budget > 0 ? number_format(($campaign->spend / $campaign->budget) * 100, 1) : '0'  }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{  $campaign->budget > 0 ? min((($campaign->spend / $campaign->budget) * 100), 100) : 0  }}%"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-500 mb-1">CTR</p>
                        <p class="text-xl font-bold text-gray-900">
                            {{  $campaign->impressions > 0 ? number_format(($campaign->clicks / $campaign->impressions) * 100, 2) : '0.00'  }}%
                        </p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-500 mb-1">CPC</p>
                        <p class="text-xl font-bold text-gray-900">
                            ${{  $campaign->clicks > 0 ? number_format($campaign->spend / $campaign->clicks, 2) : '0.00'  }}
                        </p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-500 mb-1">Conversions</p>
                        <p class="text-xl font-bold text-gray-900">{{  number_format($campaign->conversions ?? 0)  }}</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-500 mb-1">Conv. Rate</p>
                        <p class="text-xl font-bold text-gray-900">
                            {{  $campaign->clicks > 0 ? number_format((($campaign->conversions ?? 0) / $campaign->clicks) * 100, 2) : '0.00'  }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity / Content -->
    <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Campaign Assets</h2>
        <p class="text-gray-600">No assets uploaded yet. Create content items to start managing campaign creative.</p>
    </div>
</div>
@endsection
