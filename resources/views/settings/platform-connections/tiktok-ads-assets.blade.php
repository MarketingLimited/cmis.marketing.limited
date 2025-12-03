@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('settings.select_tiktok_ads_assets') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="tiktokAdsAssetsPage()" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('settings.platform_connections') }}</a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <span class="text-gray-900 font-medium">{{ __('settings.tiktok_ads_assets') }}</span>
        </nav>
        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('settings.configure_tiktok_ads_assets') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('settings.select_advertiser_accounts_desc') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Connection Info --}}
    <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0 w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center">
                <i class="fab fa-tiktok text-white text-xl"></i>
            </div>
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <p class="font-medium text-white">{{ $connection->account_name ?? __('settings.tiktok_ads_manager') }}</p>
                <p class="text-sm text-gray-300">
                    <i class="fas fa-check-circle text-green-400 {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                    {{ __('settings.connected') }} {{ $connection->created_at->diffForHumans() }}
                    @if(count($advertiserIds) > 0)
                        &bull; {{ count($advertiserIds) }} {{ __('settings.advertiser_accounts_available') }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Asset Selection Form --}}
    <form action="{{ route('orgs.settings.platform-connections.tiktok-ads.assets.store', [$currentOrg, $connection->connection_id]) }}" method="POST">
        @csrf

        <div class="space-y-6">
            {{-- Advertiser Accounts Section --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-white"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('settings.advertiser_accounts') }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ count($advertisers) }} {{ __('settings.accounts_available') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAll()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-check-square"></i>{{ __('settings.select_all') }}
                            </button>
                            <button type="button" @click="deselectAll()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-square"></i>{{ __('settings.deselect_all') }}
                            </button>
                        </div>
                    </div>

                    @if(count($advertisers) === 0)
                        {{-- Empty State --}}
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <i class="fab fa-tiktok text-gray-300 text-4xl mb-3"></i>
                            <p class="text-sm text-gray-500">{{ __('settings.no_advertiser_accounts_found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('settings.tiktok_ads_no_accounts_hint') }}</p>
                        </div>
                    @else
                        {{-- Selected Count --}}
                        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                            <span class="text-sm text-gray-500" x-show="selectedAdvertisers.length > 0">
                                <span x-text="selectedAdvertisers.length"></span> {{ __('settings.selected') }}
                            </span>
                        </div>

                        {{-- Advertisers List --}}
                        <div class="max-h-96 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($advertisers as $advertiser)
                                    @php
                                        $advertiserId = $advertiser['advertiser_id'] ?? '';
                                        $advertiserName = $advertiser['advertiser_name'] ?? $advertiser['name'] ?? __('settings.advertiser_account') . ' ' . $advertiserId;
                                        $isSelected = in_array($advertiserId, $selectedAssets['advertiser_ids'] ?? []);
                                    @endphp
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}"
                                           :class="{ 'border-gray-900 bg-gray-50': selectedAdvertisers.includes('{{ $advertiserId }}') }">
                                        <input type="checkbox"
                                               name="advertiser_ids[]"
                                               value="{{ $advertiserId }}"
                                               x-model="selectedAdvertisers"
                                               @if($isSelected) checked @endif
                                               class="h-4 w-4 text-gray-900 border-gray-300 focus:ring-gray-900 flex-shrink-0">
                                        <div class="flex items-center gap-3 flex-1 min-w-0 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fab fa-tiktok text-gray-600"></i>
                                            </div>
                                            <div class="min-w-0 {{ $isRtl ? 'text-right' : '' }}">
                                                <span class="text-sm font-medium text-gray-900 block truncate">{{ $advertiserName }}</span>
                                                <span class="text-xs text-gray-500 block">ID: {{ $advertiserId }}</span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Help Section --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 {{ $isRtl ? 'text-right' : '' }}">
                <div class="flex gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-info-circle text-blue-500 flex-shrink-0 mt-0.5"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-medium mb-1">{{ __('settings.about_tiktok_advertiser_accounts') }}</p>
                        <ul class="list-disc {{ $isRtl ? 'pr-4' : 'pl-4' }} space-y-1 text-blue-600">
                            <li>{{ __('settings.tiktok_advertiser_hint_1') }}</li>
                            <li>{{ __('settings.tiktok_advertiser_hint_2') }}</li>
                            <li>{{ __('settings.tiktok_advertiser_hint_3') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                    <i class="fas fa-save {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('settings.save_assets') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function tiktokAdsAssetsPage() {
    return {
        selectedAdvertisers: @json($selectedAssets['advertiser_ids'] ?? []),
        allAdvertiserIds: @json(collect($advertisers)->pluck('advertiser_id')->toArray()),

        selectAll() {
            this.selectedAdvertisers = [...this.allAdvertiserIds];
        },

        deselectAll() {
            this.selectedAdvertisers = [];
        }
    }
}
</script>
@endpush
