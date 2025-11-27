@extends('layouts.admin')

@section('title', __('campaigns.ad_sets') . ' - ' . $campaign->name)

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="space-y-6" dir="{{ $dir }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('campaigns.campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.show', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ $campaign->name }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('campaigns.ad_sets') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6 {{ $isRtl ? 'flex-row-reverse' : '' }}">
        <div class="{{ $isRtl ? 'text-right' : '' }}">
            <h1 class="text-2xl font-bold text-gray-900">{{ __('campaigns.ad_sets') }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('campaigns.campaign_label') }}: <span class="font-medium">{{ $campaign->name }}</span>
                @if($campaign->platform)
                    <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ ucfirst($campaign->platform) }}
                    </span>
                @endif
            </p>
        </div>
        <a href="{{ route('org.campaigns.ad-sets.create', [$currentOrg, $campaign->campaign_id]) }}"
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <i class="fas fa-plus {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.create_ad_set') }}
        </a>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-3 text-right' : 'ml-3' }}">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Ad Sets Table --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        @if($adSets->count() > 0)
            <table class="min-w-full divide-y divide-gray-200" dir="{{ $dir }}">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('campaigns.name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('campaigns.status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('campaigns.budget') }}
                        </th>
                        <th scope="col" class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('campaigns.schedule') }}
                        </th>
                        <th scope="col" class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('campaigns.ads') }}
                        </th>
                        <th scope="col" class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('campaigns.sync') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('campaigns.actions') }}</span>
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
                                    {{ __('campaigns.status.' . $adSet->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($adSet->budget_type === 'daily' && $adSet->daily_budget)
                                    {{ $isRtl ? 'ر.س' : '$' }}{{ number_format($adSet->daily_budget, 2) }}{{ __('campaigns.per_day') }}
                                @elseif($adSet->lifetime_budget)
                                    {{ $isRtl ? 'ر.س' : '$' }}{{ number_format($adSet->lifetime_budget, 2) }} {{ __('campaigns.lifetime') }}
                                @else
                                    <span class="text-gray-400">{{ __('campaigns.not_set') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($adSet->start_time)
                                    {{ $adSet->start_time->format('M d, Y') }}
                                    @if($adSet->end_time)
                                        - {{ $adSet->end_time->format('M d, Y') }}
                                    @endif
                                @else
                                    <span class="text-gray-400">{{ __('campaigns.not_scheduled') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <a href="{{ route('org.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                   class="text-blue-600 hover:text-blue-900">
                                    {{ $adSet->ads_count ?? $adSet->ads()->count() }} {{ __('campaigns.ads_count') }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($adSet->external_ad_set_id && $adSet->sync_status === 'synced')
                                    <span class="text-green-600 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> {{ __('campaigns.synced') }}
                                    </span>
                                @elseif($adSet->sync_status === 'error')
                                    <span class="text-red-600 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ __('campaigns.error') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                                        <i class="fas fa-clock"></i> {{ __('campaigns.pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap {{ $isRtl ? 'text-left' : 'text-right' }} text-sm font-medium">
                                <div class="flex items-center {{ $isRtl ? 'justify-start space-x-reverse space-x-2' : 'justify-end space-x-2' }}">
                                    <a href="{{ route('org.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                       class="text-gray-400 hover:text-gray-600" title="{{ __('campaigns.view_ads') }}">
                                        <i class="fas fa-ad"></i>
                                    </a>
                                    <a href="{{ route('org.campaigns.ad-sets.edit', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                       class="text-blue-600 hover:text-blue-900" title="{{ __('campaigns.edit') }}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('org.campaigns.ad-sets.duplicate', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-gray-400 hover:text-gray-600" title="{{ __('campaigns.duplicate') }}">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('org.campaigns.ad-sets.destroy', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('{{ __('campaigns.confirm_delete_ad_set') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="{{ __('campaigns.delete') }}">
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
            <div class="text-center py-12 {{ $isRtl ? 'text-right' : '' }}">
                <i class="fas fa-layer-group text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900">{{ __('campaigns.no_ad_sets_yet') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.create_first_ad_set') }}</p>
                <div class="mt-6">
                    <a href="{{ route('org.campaigns.ad-sets.create', [$currentOrg, $campaign->campaign_id]) }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-plus {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.create_ad_set') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
