@extends('layouts.admin')

@section('title', __('Ad Sets') . ' - ' . $campaign->name)

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.show', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ $campaign->name }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Ad Sets') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Ad Sets</h1>
            <p class="mt-1 text-sm text-gray-500">
                Campaign: <span class="font-medium">{{ $campaign->name }}</span>
                @if($campaign->platform)
                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ ucfirst($campaign->platform) }}
                    </span>
                @endif
            </p>
        </div>
        <a href="{{ route('org.campaigns.ad-sets.create', [$currentOrg, $campaign->campaign_id]) }}"
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i> Create Ad Set
        </a>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Ad Sets Table --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        @if($adSets->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Budget
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Schedule
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ads
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sync
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($adSets as $adSet)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('org.campaigns.ad-sets.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                   class="text-blue-600 hover:text-blue-900 font-medium">
                                    {{ $adSet->name }}
                                </a>
                                @if($adSet->description)
                                    <p class="text-sm text-gray-500 truncate max-w-xs">{{ $adSet->description }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-800',
                                        'active' => 'bg-green-100 text-green-800',
                                        'paused' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-blue-100 text-blue-800',
                                        'archived' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$adSet->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($adSet->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($adSet->budget_type === 'daily' && $adSet->daily_budget)
                                    ${{ number_format($adSet->daily_budget, 2) }}/day
                                @elseif($adSet->lifetime_budget)
                                    ${{ number_format($adSet->lifetime_budget, 2) }} lifetime
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($adSet->start_time)
                                    {{ $adSet->start_time->format('M d, Y') }}
                                    @if($adSet->end_time)
                                        - {{ $adSet->end_time->format('M d, Y') }}
                                    @endif
                                @else
                                    <span class="text-gray-400">Not scheduled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <a href="{{ route('org.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                   class="text-blue-600 hover:text-blue-900">
                                    {{ $adSet->ads_count ?? $adSet->ads()->count() }} ads
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($adSet->external_ad_set_id && $adSet->sync_status === 'synced')
                                    <span class="text-green-600"><i class="fas fa-check-circle"></i> Synced</span>
                                @elseif($adSet->sync_status === 'error')
                                    <span class="text-red-600"><i class="fas fa-exclamation-circle"></i> Error</span>
                                @else
                                    <span class="text-gray-400"><i class="fas fa-clock"></i> Pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('org.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                       class="text-gray-400 hover:text-gray-600" title="View Ads">
                                        <i class="fas fa-ad"></i>
                                    </a>
                                    <a href="{{ route('org.campaigns.ad-sets.edit', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                       class="text-blue-600 hover:text-blue-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('org.campaigns.ad-sets.duplicate', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-gray-400 hover:text-gray-600" title="Duplicate">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('org.campaigns.ad-sets.destroy', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this ad set?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if($adSets->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $adSets->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <i class="fas fa-layer-group text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900">No ad sets yet</h3>
                <p class="mt-1 text-sm text-gray-500">Create your first ad set to start running ads.</p>
                <div class="mt-6">
                    <a href="{{ route('org.campaigns.ad-sets.create', [$currentOrg, $campaign->campaign_id]) }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Create Ad Set
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
