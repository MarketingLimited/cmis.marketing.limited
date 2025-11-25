@extends('layouts.app')

@section('title', __('Select Meta Assets') . ' - Settings')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="metaAssetsPage()">
    {{-- Header --}}
    <div class="mb-8">
        <nav class="text-sm text-gray-500 mb-2">
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="hover:text-gray-700">{{ __('Platform Connections') }}</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ __('Select Assets') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Configure Meta Assets') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Select which Facebook Pages, Instagram accounts, Ad Accounts, Pixels, and Catalogs to use with this connection.') }}
        </p>
    </div>

    {{-- Connection Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fab fa-facebook text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="font-medium text-blue-900">{{ $connection->account_name }}</p>
                <p class="text-sm text-blue-700">
                    @if($connection->account_metadata['is_system_user'] ?? false)
                        <i class="fas fa-check-circle mr-1"></i>System User Token
                    @endif
                    &bull; Connected {{ $connection->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
    </div>

    {{-- Asset Selection Form --}}
    <form action="{{ route('orgs.settings.platform-connections.meta.assets.store', [$currentOrg, $connection->connection_id]) }}" method="POST">
        @csrf

        <div class="space-y-6">
            {{-- Facebook Pages --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fab fa-facebook text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Facebook Pages') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($pages) }} {{ __('page(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualPage = !showManualPage" class="text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($pages) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($pages as $page)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-blue-500 bg-blue-50': selectedPages.includes('{{ $page['id'] }}') }">
                                    <input type="checkbox" name="pages[]" value="{{ $page['id'] }}"
                                           {{ in_array($page['id'], $selectedAssets['pages'] ?? []) ? 'checked' : '' }}
                                           x-model="selectedPages"
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($page['picture'])
                                            <img src="{{ $page['picture'] }}" alt="" class="w-8 h-8 rounded-full">
                                        @else
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                <i class="fas fa-flag text-gray-400"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $page['name'] }}</span>
                                            @if($page['category'])
                                                <span class="block text-xs text-gray-500">{{ $page['category'] }}</span>
                                            @endif
                                            @if($page['has_instagram'])
                                                <span class="text-xs text-pink-600"><i class="fab fa-instagram mr-1"></i>Instagram connected</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fab fa-facebook text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Facebook Pages found') }}</p>
                        </div>
                    @endif

                    {{-- Manual Page ID Input --}}
                    <div x-show="showManualPage" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Page ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_page_id" placeholder="e.g., 123456789012345"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <button type="button" @click="showManualPage = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Instagram Accounts --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                                <i class="fab fa-instagram text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Instagram Accounts') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($instagramAccounts) }} {{ __('account(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualInstagram = !showManualInstagram" class="text-sm text-pink-600 hover:text-pink-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($instagramAccounts) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($instagramAccounts as $ig)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-pink-500 bg-pink-50': selectedInstagram.includes('{{ $ig['id'] }}') }">
                                    <input type="checkbox" name="instagram_accounts[]" value="{{ $ig['id'] }}"
                                           {{ in_array($ig['id'], $selectedAssets['instagram_accounts'] ?? []) ? 'checked' : '' }}
                                           x-model="selectedInstagram"
                                           class="h-4 w-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($ig['profile_picture'])
                                            <img src="{{ $ig['profile_picture'] }}" alt="" class="w-8 h-8 rounded-full">
                                        @else
                                            <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center">
                                                <i class="fab fa-instagram text-white text-sm"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $ig['username'] ?? $ig['name'] }}</span>
                                            @if($ig['followers_count'])
                                                <span class="block text-xs text-gray-500">{{ number_format($ig['followers_count']) }} followers</span>
                                            @endif
                                            @if($ig['connected_page_name'])
                                                <span class="text-xs text-blue-600"><i class="fab fa-facebook mr-1"></i>{{ $ig['connected_page_name'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fab fa-instagram text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Instagram accounts found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Connect Instagram to a Facebook Page first') }}</p>
                        </div>
                    @endif

                    {{-- Manual Instagram ID Input --}}
                    <div x-show="showManualInstagram" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Instagram Business Account ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_instagram_id" placeholder="e.g., 17841400000000000"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 text-sm">
                            <button type="button" @click="showManualInstagram = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ad Accounts --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-green-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Meta Ad Accounts') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($adAccounts) }} {{ __('account(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualAdAccount = !showManualAdAccount" class="text-sm text-green-600 hover:text-green-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($adAccounts) > 0)
                        <div class="space-y-2">
                            @foreach($adAccounts as $account)
                                <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-green-500 bg-green-50': selectedAdAccounts.includes('{{ $account['id'] }}') }">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="ad_accounts[]" value="{{ $account['id'] }}"
                                               {{ in_array($account['id'], $selectedAssets['ad_accounts'] ?? []) ? 'checked' : '' }}
                                               x-model="selectedAdAccounts"
                                               class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900">{{ $account['name'] }}</span>
                                            <span class="text-xs text-gray-400 ml-2">({{ $account['account_id'] }})</span>
                                            @if($account['business_name'])
                                                <span class="block text-xs text-gray-500">{{ $account['business_name'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">{{ $account['currency'] }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ $account['status'] === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $account['status'] }}
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-ad text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Ad Accounts found') }}</p>
                        </div>
                    @endif

                    {{-- Manual Ad Account ID Input --}}
                    <div x-show="showManualAdAccount" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Ad Account ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_ad_account_id" placeholder="e.g., act_123456789 or 123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <button type="button" @click="showManualAdAccount = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ __('The act_ prefix will be added automatically if missing') }}</p>
                    </div>
                </div>
            </div>

            {{-- Pixels --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-code text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Meta Pixels') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($pixels) }} {{ __('pixel(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualPixel = !showManualPixel" class="text-sm text-purple-600 hover:text-purple-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($pixels) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($pixels as $pixel)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-purple-500 bg-purple-50': selectedPixels.includes('{{ $pixel['id'] }}') }">
                                    <input type="checkbox" name="pixels[]" value="{{ $pixel['id'] }}"
                                           {{ in_array($pixel['id'], $selectedAssets['pixels'] ?? []) ? 'checked' : '' }}
                                           x-model="selectedPixels"
                                           class="h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $pixel['name'] }}</span>
                                        <span class="text-xs text-gray-400 ml-1">({{ $pixel['id'] }})</span>
                                        <span class="block text-xs text-gray-500">{{ $pixel['ad_account_name'] }}</span>
                                        @if($pixel['last_fired_time'])
                                            <span class="text-xs text-green-600"><i class="fas fa-check-circle mr-1"></i>Last fired: {{ \Carbon\Carbon::parse($pixel['last_fired_time'])->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-code text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Pixels found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Create a Pixel in Meta Events Manager') }}</p>
                        </div>
                    @endif

                    {{-- Manual Pixel ID Input --}}
                    <div x-show="showManualPixel" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Pixel ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_pixel_id" placeholder="e.g., 123456789012345"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                            <button type="button" @click="showManualPixel = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Catalogs --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-orange-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Product Catalogs') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($catalogs) }} {{ __('catalog(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualCatalog = !showManualCatalog" class="text-sm text-orange-600 hover:text-orange-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($catalogs) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($catalogs as $catalog)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-orange-500 bg-orange-50': selectedCatalogs.includes('{{ $catalog['id'] }}') }">
                                    <input type="checkbox" name="catalogs[]" value="{{ $catalog['id'] }}"
                                           {{ in_array($catalog['id'], $selectedAssets['catalogs'] ?? []) ? 'checked' : '' }}
                                           x-model="selectedCatalogs"
                                           class="h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $catalog['name'] }}</span>
                                        <span class="text-xs text-gray-400 ml-1">({{ $catalog['id'] }})</span>
                                        <span class="block text-xs text-gray-500">
                                            {{ number_format($catalog['product_count']) }} products
                                            @if($catalog['vertical'])
                                                &bull; {{ ucfirst($catalog['vertical']) }}
                                            @endif
                                        </span>
                                        @if($catalog['business_name'])
                                            <span class="text-xs text-blue-600">{{ $catalog['business_name'] }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-shopping-bag text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Product Catalogs found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Create a catalog in Meta Commerce Manager') }}</p>
                        </div>
                    @endif

                    {{-- Manual Catalog ID Input --}}
                    <div x-show="showManualCatalog" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Catalog ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_catalog_id" placeholder="e.g., 123456789012345"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                            <button type="button" @click="showManualCatalog = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary & Submit --}}
        <div class="mt-8 bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Selection Summary') }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            <span x-text="selectedPages.length"></span> {{ __('pages') }},
                            <span x-text="selectedInstagram.length"></span> {{ __('Instagram') }},
                            <span x-text="selectedAdAccounts.length"></span> {{ __('ad accounts') }},
                            <span x-text="selectedPixels.length"></span> {{ __('pixels') }},
                            <span x-text="selectedCatalogs.length"></span> {{ __('catalogs') }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>{{ __('Save Selection') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function metaAssetsPage() {
    return {
        // Manual input visibility
        showManualPage: false,
        showManualInstagram: false,
        showManualAdAccount: false,
        showManualPixel: false,
        showManualCatalog: false,

        // Selected items (pre-populate from existing selection)
        selectedPages: @json($selectedAssets['pages'] ?? []),
        selectedInstagram: @json($selectedAssets['instagram_accounts'] ?? []),
        selectedAdAccounts: @json($selectedAssets['ad_accounts'] ?? []),
        selectedPixels: @json($selectedAssets['pixels'] ?? []),
        selectedCatalogs: @json($selectedAssets['catalogs'] ?? []),
    }
}
</script>
@endpush
@endsection
