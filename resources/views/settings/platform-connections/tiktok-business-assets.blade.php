@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('settings.select_tiktok_business_assets') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="tiktokBusinessAssetsPage()" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
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
            <span class="text-gray-900 font-medium">{{ __('settings.tiktok_business_assets') }}</span>
        </nav>
        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('settings.configure_tiktok_business_assets') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('settings.select_business_assets_desc') }}
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
                <p class="font-medium text-white">{{ $connection->account_name ?? __('settings.tiktok_business_center') }}</p>
                <p class="text-sm text-gray-300">
                    <i class="fas fa-check-circle text-green-400 {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                    {{ __('settings.connected') }} {{ $connection->created_at->diffForHumans() }}
                    @if(count($advertiserIds) > 0)
                        &bull; {{ count($advertiserIds) }} {{ __('settings.ad_accounts_available') }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- TikTok Accounts for Video Publishing Section --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-cyan-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-video text-white"></i>
                    </div>
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('settings.tiktok_accounts_for_publishing') }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ __('settings.tiktok_accounts_for_publishing_desc') }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('orgs.settings.platform-connections.tiktok.authorize', ['org' => $currentOrg, 'return_url' => route('orgs.settings.platform-connections.tiktok-business.assets', [$currentOrg, $connection->connection_id])]) }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-cyan-500 hover:from-pink-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                    <i class="fab fa-tiktok {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                    {{ __('settings.connect_tiktok_account') }}
                </a>
            </div>

            @if($tiktokAccounts->count() > 0)
                {{-- Connected TikTok Accounts List --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($tiktokAccounts as $account)
                        <div class="flex items-center p-3 border rounded-lg bg-gray-50 gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-cyan-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fab fa-tiktok text-white"></i>
                            </div>
                            <div class="min-w-0 flex-1 {{ $isRtl ? 'text-right' : '' }}">
                                <span class="text-sm font-medium text-gray-900 block truncate">{{ $account->account_name }}</span>
                                <span class="text-xs text-gray-500 block">
                                    <i class="fas fa-check-circle text-green-500 {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                    {{ __('settings.connected') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-6 bg-gray-50 rounded-lg">
                    <i class="fab fa-tiktok text-gray-300 text-3xl mb-2"></i>
                    <p class="text-sm text-gray-500">{{ __('settings.no_tiktok_accounts_connected') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('settings.connect_tiktok_account_hint') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Asset Selection Form --}}
    <form action="{{ route('orgs.settings.platform-connections.tiktok-business.assets.store', [$currentOrg, $connection->connection_id]) }}" method="POST">
        @csrf

        <div class="space-y-6">
            {{-- Ad Accounts Section --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-white"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('settings.ad_accounts') }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ count($advertisers) }} {{ __('settings.accounts_available') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllAdvertisers()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-check-square"></i>{{ __('settings.select_all') }}
                            </button>
                            <button type="button" @click="deselectAllAdvertisers()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-square"></i>{{ __('settings.deselect_all') }}
                            </button>
                        </div>
                    </div>

                    @if(count($advertisers) === 0)
                        {{-- Empty State --}}
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <i class="fas fa-ad text-gray-300 text-4xl mb-3"></i>
                            <p class="text-sm text-gray-500">{{ __('settings.no_ad_accounts_found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('settings.tiktok_no_ad_accounts_hint') }}</p>
                        </div>
                    @else
                        {{-- Selected Count --}}
                        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                            <span class="text-sm text-gray-500" x-show="selectedAdvertisers.length > 0">
                                <span x-text="selectedAdvertisers.length"></span> {{ __('settings.selected') }}
                            </span>
                        </div>

                        {{-- Advertisers List --}}
                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($advertisers as $advertiser)
                                    @php
                                        $advertiserId = $advertiser['advertiser_id'] ?? '';
                                        $advertiserName = $advertiser['advertiser_name'] ?? $advertiser['name'] ?? __('settings.ad_account') . ' ' . $advertiserId;
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
                                                <i class="fas fa-ad text-gray-600"></i>
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

            {{-- Pixels Section --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-white"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('settings.pixels') }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ count($pixels) }} {{ __('settings.pixels_available') }}
                                </p>
                            </div>
                        </div>
                        @if(count($pixels) > 0)
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllPixels()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-check-square"></i>{{ __('settings.select_all') }}
                            </button>
                            <button type="button" @click="deselectAllPixels()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-square"></i>{{ __('settings.deselect_all') }}
                            </button>
                        </div>
                        @endif
                    </div>

                    @if(count($pixels) === 0)
                        {{-- Empty State --}}
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <i class="fas fa-chart-line text-gray-300 text-4xl mb-3"></i>
                            <p class="text-sm text-gray-500">{{ __('settings.no_pixels_found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('settings.tiktok_no_pixels_hint') }}</p>
                        </div>
                    @else
                        {{-- Selected Count --}}
                        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                            <span class="text-sm text-gray-500" x-show="selectedPixels.length > 0">
                                <span x-text="selectedPixels.length"></span> {{ __('settings.selected') }}
                            </span>
                        </div>

                        {{-- Pixels List --}}
                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($pixels as $pixel)
                                    @php
                                        $pixelId = $pixel['pixel_id'] ?? $pixel['id'] ?? '';
                                        $pixelName = $pixel['pixel_name'] ?? $pixel['name'] ?? __('settings.pixel') . ' ' . $pixelId;
                                        $isSelected = in_array($pixelId, $selectedAssets['pixel_ids'] ?? []);
                                    @endphp
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}"
                                           :class="{ 'border-purple-600 bg-purple-50': selectedPixels.includes('{{ $pixelId }}') }">
                                        <input type="checkbox"
                                               name="pixel_ids[]"
                                               value="{{ $pixelId }}"
                                               x-model="selectedPixels"
                                               @if($isSelected) checked @endif
                                               class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500 flex-shrink-0">
                                        <div class="flex items-center gap-3 flex-1 min-w-0 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-chart-line text-purple-600"></i>
                                            </div>
                                            <div class="min-w-0 {{ $isRtl ? 'text-right' : '' }}">
                                                <span class="text-sm font-medium text-gray-900 block truncate">{{ $pixelName }}</span>
                                                <span class="text-xs text-gray-500 block">ID: {{ $pixelId }}</span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Catalogs Section --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-th-large text-white"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('settings.catalogs') }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ count($catalogs) }} {{ __('settings.catalogs_available') }}
                                </p>
                            </div>
                        </div>
                        @if(count($catalogs) > 0)
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllCatalogs()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-check-square"></i>{{ __('settings.select_all') }}
                            </button>
                            <button type="button" @click="deselectAllCatalogs()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-square"></i>{{ __('settings.deselect_all') }}
                            </button>
                        </div>
                        @endif
                    </div>

                    @if(count($catalogs) === 0)
                        {{-- Empty State --}}
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <i class="fas fa-th-large text-gray-300 text-4xl mb-3"></i>
                            <p class="text-sm text-gray-500">{{ __('settings.no_catalogs_found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('settings.tiktok_no_catalogs_hint') }}</p>
                        </div>
                    @else
                        {{-- Selected Count --}}
                        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                            <span class="text-sm text-gray-500" x-show="selectedCatalogs.length > 0">
                                <span x-text="selectedCatalogs.length"></span> {{ __('settings.selected') }}
                            </span>
                        </div>

                        {{-- Catalogs List --}}
                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($catalogs as $catalog)
                                    @php
                                        $catalogId = $catalog['catalog_id'] ?? $catalog['id'] ?? '';
                                        $catalogName = $catalog['catalog_name'] ?? $catalog['name'] ?? __('settings.catalog') . ' ' . $catalogId;
                                        $isSelected = in_array($catalogId, $selectedAssets['catalog_ids'] ?? []);
                                    @endphp
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}"
                                           :class="{ 'border-orange-500 bg-orange-50': selectedCatalogs.includes('{{ $catalogId }}') }">
                                        <input type="checkbox"
                                               name="catalog_ids[]"
                                               value="{{ $catalogId }}"
                                               x-model="selectedCatalogs"
                                               @if($isSelected) checked @endif
                                               class="h-4 w-4 text-orange-500 border-gray-300 focus:ring-orange-500 flex-shrink-0">
                                        <div class="flex items-center gap-3 flex-1 min-w-0 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-th-large text-orange-600"></i>
                                            </div>
                                            <div class="min-w-0 {{ $isRtl ? 'text-right' : '' }}">
                                                <span class="text-sm font-medium text-gray-900 block truncate">{{ $catalogName }}</span>
                                                <span class="text-xs text-gray-500 block">ID: {{ $catalogId }}</span>
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
                        <p class="font-medium mb-1">{{ __('settings.about_tiktok_business_assets') }}</p>
                        <ul class="list-disc {{ $isRtl ? 'pe-4' : 'ps-4' }} space-y-1 text-blue-600">
                            <li>{{ __('settings.tiktok_business_hint_1') }}</li>
                            <li>{{ __('settings.tiktok_business_hint_2') }}</li>
                            <li>{{ __('settings.tiktok_business_hint_3') }}</li>
                            <li>{{ __('settings.tiktok_business_hint_4') }}</li>
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
                    <i class="fas fa-save {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                    {{ __('settings.save_assets') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function tiktokBusinessAssetsPage() {
    return {
        selectedAdvertisers: @json($selectedAssets['advertiser_ids'] ?? []),
        selectedPixels: @json($selectedAssets['pixel_ids'] ?? []),
        selectedCatalogs: @json($selectedAssets['catalog_ids'] ?? []),
        allAdvertiserIds: @json(collect($advertisers)->pluck('advertiser_id')->toArray()),
        allPixelIds: @json(collect($pixels)->pluck('pixel_id')->merge(collect($pixels)->pluck('id'))->filter()->unique()->toArray()),
        allCatalogIds: @json(collect($catalogs)->pluck('catalog_id')->merge(collect($catalogs)->pluck('id'))->filter()->unique()->toArray()),

        selectAllAdvertisers() {
            this.selectedAdvertisers = [...this.allAdvertiserIds];
        },

        deselectAllAdvertisers() {
            this.selectedAdvertisers = [];
        },

        selectAllPixels() {
            this.selectedPixels = [...this.allPixelIds];
        },

        deselectAllPixels() {
            this.selectedPixels = [];
        },

        selectAllCatalogs() {
            this.selectedCatalogs = [...this.allCatalogIds];
        },

        deselectAllCatalogs() {
            this.selectedCatalogs = [];
        }
    }
}
</script>
@endpush
