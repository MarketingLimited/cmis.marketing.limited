@extends('layouts.admin')

@section('title', __('Select Meta Assets') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="metaAssetsPage()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Platform Connections') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Meta Assets') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Configure Meta Assets') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Select multiple assets: Facebook Pages, Instagram accounts, Threads accounts, Ad Accounts, Pixels, and Catalogs for this organization.') }}
        </p>
        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
            <i class="fas fa-info-circle mr-1"></i>
            {{ __('You can select multiple accounts per asset type (e.g., multiple Pages, Ad Accounts).') }}
        </div>
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
                                <p class="text-sm text-gray-500" x-text="filteredPagesCount + ' ' + '{{ __("page(s) available") }}'"></p>
                            </div>
                        </div>
                        <button type="button" @click="showManualPage = !showManualPage" class="text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($pages) > 0)
                        {{-- Search & Bulk Actions --}}
                        <div class="mb-4 space-y-2">
                            <input type="text" x-model="pagesSearch" placeholder="{{ __('Search pages by name...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <div class="flex gap-2">
                                <button type="button" @click="selectAllPages" class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-check-square mr-1"></i>{{ __('Select All Visible') }}
                                </button>
                                <button type="button" @click="deselectAllPages" class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-square mr-1"></i>{{ __('Deselect All') }}
                                </button>
                                <span class="text-xs text-gray-500" x-show="selectedPages.length > 0">
                                    (<span x-text="selectedPages.length"></span> {{ __('selected') }})
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($pages as $page)
                                <label x-show="matchesPagesSearch('{{ $page['name'] }}')" class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-blue-500 bg-blue-50': selectedPages.includes('{{ $page['id'] }}' }">
                                    <input type="checkbox" name="page[]" value="{{ $page['id'] }}"
                                           {{ in_array($page['id'], (array) ($selectedAssets['page'] ?? [])) ? 'checked' : '' }}
                                           x-model="selectedPage"
                                           class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
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
                                <p class="text-sm text-gray-500" x-text="filteredInstagramCount + ' ' + '{{ __("account(s) available") }}'"></p>
                            </div>
                        </div>
                        <button type="button" @click="showManualInstagram = !showManualInstagram" class="text-sm text-pink-600 hover:text-pink-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($instagramAccounts) > 0)
                        {{-- Search & Bulk Actions --}}
                        <div class="mb-4 space-y-2">
                            <input type="text" x-model="instagramSearch" placeholder="{{ __('Search Instagram accounts by username...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 text-sm">
                            <div class="flex gap-2">
                                <button type="button" @click="selectAllInstagram" class="text-xs text-pink-600 hover:text-pink-800">
                                    <i class="fas fa-check-square mr-1"></i>{{ __('Select All Visible') }}
                                </button>
                                <button type="button" @click="deselectAllInstagram" class="text-xs text-pink-600 hover:text-pink-800">
                                    <i class="fas fa-square mr-1"></i>{{ __('Deselect All') }}
                                </button>
                                <span class="text-xs text-gray-500" x-show="selectedInstagrams.length > 0">
                                    (<span x-text="selectedInstagrams.length"></span> {{ __('selected') }})
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($instagramAccounts as $ig)
                                <label x-show="matchesInstagramSearch('{{ $ig['username'] }}')" class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-pink-500 bg-pink-50': selectedInstagrams.includes('{{ $ig['id'] }}' }">
                                    <input type="checkbox" name="instagram_account[]" value="{{ $ig['id'] }}"
                                           {{ in_array($ig['id'], (array) ($selectedAssets['instagram_account'] ?? [])) ? 'checked' : '' }}
                                           x-model="selectedInstagram"
                                           class="h-4 w-4 text-pink-600 border-gray-300 focus:ring-pink-500">
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

            {{-- Threads Accounts --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.332-3.023.85-.706 2.017-1.122 3.381-1.206.934-.057 1.9-.004 2.86.156.04-.61.04-1.185-.002-1.725-.075-.98-.378-1.735-.902-2.243-.524-.509-1.303-.776-2.317-.796-1.037.018-1.9.283-2.49.766-.49.4-.773.9-.87 1.53l-2.095-.36c.173-1.14.69-2.074 1.54-2.772 1.008-.826 2.397-1.263 4.018-1.264h.02c1.656 0 2.97.478 3.905 1.422.92.927 1.412 2.256 1.463 3.948.016.532.005 1.09-.033 1.668 1.244.834 2.174 1.96 2.683 3.27.707 1.82.645 4.315-1.612 6.528-1.877 1.838-4.2 2.646-7.543 2.668zm.535-8.39c-1.34.082-2.387.778-2.387 1.588 0 .343.144.665.417.932.37.363.955.548 1.745.548.018 0 .036 0 .055-.001 1.163-.063 2.024-.556 2.489-1.133.35-.434.548-.995.595-1.673-.927-.18-1.9-.298-2.914-.261z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Threads Accounts') }}</h3>
                                <p class="text-sm text-gray-500">
                                    @if(count($instagramAccounts) > 0)
                                        {{ __('Select from your Instagram accounts (same ID)') }}
                                    @else
                                        {{ count($threadsAccounts ?? []) }} {{ __('account(s) available') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualThreads = !showManualThreads" class="text-sm text-gray-600 hover:text-gray-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Info banner explaining Threads = Instagram ID --}}
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                            <div class="text-xs text-blue-700">
                                <p class="font-medium">{{ __('Threads uses the same ID as Instagram Business accounts') }}</p>
                                <p class="mt-1">{{ __('Select an Instagram account below to use for Threads, or enter a Threads ID manually.') }}</p>
                            </div>
                        </div>
                    </div>

                    @if(count($instagramAccounts) > 0)
                        {{-- Show Instagram accounts for Threads selection (they share the same ID) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($instagramAccounts as $ig)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-gray-700 bg-gray-100': selectedThreadsAccounts.includes('{{ $ig['id'] }}' }">
                                    <input type="checkbox" name="threads_account[]" value="{{ $ig['id'] }}"
                                           {{ ($selectedAssets['threads_account'] ?? null) === $ig['id'] ? 'checked' : '' }}
                                           x-model="selectedThreads"
                                           class="h-4 w-4 text-gray-700 border-gray-300 focus:ring-gray-500">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($ig['profile_picture'])
                                            <div class="relative">
                                                <img src="{{ $ig['profile_picture'] }}" alt="" class="w-8 h-8 rounded-full">
                                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-gray-900 rounded-full flex items-center justify-center">
                                                    <svg class="w-2.5 h-2.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.332-3.023.85-.706 2.017-1.122 3.381-1.206.934-.057 1.9-.004 2.86.156.04-.61.04-1.185-.002-1.725-.075-.98-.378-1.735-.902-2.243-.524-.509-1.303-.776-2.317-.796-1.037.018-1.9.283-2.49.766-.49.4-.773.9-.87 1.53l-2.095-.36c.173-1.14.69-2.074 1.54-2.772 1.008-.826 2.397-1.263 4.018-1.264h.02c1.656 0 2.97.478 3.905 1.422.92.927 1.412 2.256 1.463 3.948.016.532.005 1.09-.033 1.668 1.244.834 2.174 1.96 2.683 3.27.707 1.82.645 4.315-1.612 6.528-1.877 1.838-4.2 2.646-7.543 2.668zm.535-8.39c-1.34.082-2.387.778-2.387 1.588 0 .343.144.665.417.932.37.363.955.548 1.745.548.018 0 .036 0 .055-.001 1.163-.063 2.024-.556 2.489-1.133.35-.434.548-.995.595-1.673-.927-.18-1.9-.298-2.914-.261z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        @else
                                            <div class="w-8 h-8 bg-gray-900 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.332-3.023.85-.706 2.017-1.122 3.381-1.206.934-.057 1.9-.004 2.86.156.04-.61.04-1.185-.002-1.725-.075-.98-.378-1.735-.902-2.243-.524-.509-1.303-.776-2.317-.796-1.037.018-1.9.283-2.49.766-.49.4-.773.9-.87 1.53l-2.095-.36c.173-1.14.69-2.074 1.54-2.772 1.008-.826 2.397-1.263 4.018-1.264h.02c1.656 0 2.97.478 3.905 1.422.92.927 1.412 2.256 1.463 3.948.016.532.005 1.09-.033 1.668 1.244.834 2.174 1.96 2.683 3.27.707 1.82.645 4.315-1.612 6.528-1.877 1.838-4.2 2.646-7.543 2.668zm.535-8.39c-1.34.082-2.387.778-2.387 1.588 0 .343.144.665.417.932.37.363.955.548 1.745.548.018 0 .036 0 .055-.001 1.163-.063 2.024-.556 2.489-1.133.35-.434.548-.995.595-1.673-.927-.18-1.9-.298-2.914-.261z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $ig['username'] ?? $ig['name'] }}</span>
                                            <span class="ml-1 text-xs text-gray-400">({{ __('Threads') }})</span>
                                            @if($ig['followers_count'])
                                                <span class="block text-xs text-gray-500">{{ number_format($ig['followers_count']) }} followers</span>
                                            @endif
                                            <span class="text-xs text-pink-600"><i class="fab fa-instagram mr-1"></i>{{ __('Instagram ID') }}: {{ $ig['id'] }}</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @elseif(count($threadsAccounts ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($threadsAccounts as $threads)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-gray-700 bg-gray-50': selectedThreadsAccounts.includes('{{ $threads['id'] }}' }">
                                    <input type="checkbox" name="threads_account[]" value="{{ $threads['id'] }}"
                                           {{ in_array($threads['id'], (array) ($selectedAssets['threads_account'] ?? [])) ? 'checked' : '' }}
                                           x-model="selectedThreads"
                                           class="h-4 w-4 text-gray-700 border-gray-300 focus:ring-gray-500">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($threads['profile_picture'] ?? null)
                                            <img src="{{ $threads['profile_picture'] }}" alt="" class="w-8 h-8 rounded-full">
                                        @else
                                            <div class="w-8 h-8 bg-gray-900 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.332-3.023.85-.706 2.017-1.122 3.381-1.206.934-.057 1.9-.004 2.86.156.04-.61.04-1.185-.002-1.725-.075-.98-.378-1.735-.902-2.243-.524-.509-1.303-.776-2.317-.796-1.037.018-1.9.283-2.49.766-.49.4-.773.9-.87 1.53l-2.095-.36c.173-1.14.69-2.074 1.54-2.772 1.008-.826 2.397-1.263 4.018-1.264h.02c1.656 0 2.97.478 3.905 1.422.92.927 1.412 2.256 1.463 3.948.016.532.005 1.09-.033 1.668 1.244.834 2.174 1.96 2.683 3.27.707 1.82.645 4.315-1.612 6.528-1.877 1.838-4.2 2.646-7.543 2.668zm.535-8.39c-1.34.082-2.387.778-2.387 1.588 0 .343.144.665.417.932.37.363.955.548 1.745.548.018 0 .036 0 .055-.001 1.163-.063 2.024-.556 2.489-1.133.35-.434.548-.995.595-1.673-.927-.18-1.9-.298-2.914-.261z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $threads['username'] ?? $threads['name'] }}</span>
                                            @if($threads['followers_count'] ?? null)
                                                <span class="block text-xs text-gray-500">{{ number_format($threads['followers_count']) }} followers</span>
                                            @endif
                                            @if($threads['connected_instagram'] ?? null)
                                                <span class="text-xs text-pink-600"><i class="fab fa-instagram mr-1"></i>{{ $threads['connected_instagram'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <div class="w-12 h-12 mx-auto bg-gray-900 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.332-3.023.85-.706 2.017-1.122 3.381-1.206.934-.057 1.9-.004 2.86.156.04-.61.04-1.185-.002-1.725-.075-.98-.378-1.735-.902-2.243-.524-.509-1.303-.776-2.317-.796-1.037.018-1.9.283-2.49.766-.49.4-.773.9-.87 1.53l-2.095-.36c.173-1.14.69-2.074 1.54-2.772 1.008-.826 2.397-1.263 4.018-1.264h.02c1.656 0 2.97.478 3.905 1.422.92.927 1.412 2.256 1.463 3.948.016.532.005 1.09-.033 1.668 1.244.834 2.174 1.96 2.683 3.27.707 1.82.645 4.315-1.612 6.528-1.877 1.838-4.2 2.646-7.543 2.668zm.535-8.39c-1.34.082-2.387.778-2.387 1.588 0 .343.144.665.417.932.37.363.955.548 1.745.548.018 0 .036 0 .055-.001 1.163-.063 2.024-.556 2.489-1.133.35-.434.548-.995.595-1.673-.927-.18-1.9-.298-2.914-.261z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-600 font-medium">{{ __('No Instagram accounts to use for Threads') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Connect an Instagram Business account first, then you can use it for Threads') }}</p>
                            <button type="button" @click="showManualThreads = true" class="mt-3 text-sm text-gray-700 hover:text-gray-900 underline">
                                <i class="fas fa-keyboard mr-1"></i>{{ __('Or enter Threads ID manually') }}
                            </button>
                        </div>
                    @endif

                    {{-- Manual Threads ID Input --}}
                    <div x-show="showManualThreads" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Threads User ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_threads_id" x-model="manualThreadsId" placeholder="e.g., 17841400000000000"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 text-sm">
                            <button type="button" @click="showManualThreads = false; manualThreadsId = ''" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                            {{ __('Tip: Your Threads ID is the same as your Instagram Business account ID') }}
                        </p>
                        @if(count($instagramAccounts) > 0)
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <p class="text-xs text-gray-600 mb-2">{{ __('Quick fill from Instagram accounts:') }}</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($instagramAccounts as $ig)
                                        <button type="button"
                                                @click="manualThreadsId = '{{ $ig['id'] }}'; selectedThreads = '{{ $ig['id'] }}'"
                                                class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-100 transition">
                                            <i class="fab fa-instagram text-pink-500 mr-1"></i>{{ $ig['username'] ?? $ig['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
                                       :class="{ 'border-green-500 bg-green-50': selectedAdAccounts.includes('{{ $account['id'] }}' }">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="ad_account[]" value="{{ $account['id'] }}"
                                               {{ ($selectedAssets['ad_account'] ?? null) === $account['id'] ? 'checked' : '' }}
                                               x-model="selectedAdAccount"
                                               class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
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
                                       :class="{ 'border-purple-500 bg-purple-50': selectedPixels.includes('{{ $pixel['id'] }}' }">
                                    <input type="checkbox" name="pixel[]" value="{{ $pixel['id'] }}"
                                           {{ in_array($pixel['id'], (array) ($selectedAssets['pixel'] ?? [])) ? 'checked' : '' }}
                                           x-model="selectedPixel"
                                           class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500">
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
                                       :class="{ 'border-orange-500 bg-orange-50': selectedCatalogs.includes('{{ $catalog['id'] }}' }">
                                    <input type="checkbox" name="catalog[]" value="{{ $catalog['id'] }}"
                                           {{ in_array($catalog['id'], (array) ($selectedAssets['catalog'] ?? [])) ? 'checked' : '' }}
                                           x-model="selectedCatalog"
                                           class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500">
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
                            <span :class="{ 'text-green-600 font-medium': selectedPage }">
                                <i class="fas" :class="selectedPage ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Page') }}
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedInstagram }">
                                <i class="fas" :class="selectedInstagram ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Instagram') }}
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedThreads }">
                                <i class="fas" :class="selectedThreads ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Threads') }}
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedAdAccount }">
                                <i class="fas" :class="selectedAdAccount ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Ad Account') }}
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedPixel }">
                                <i class="fas" :class="selectedPixel ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Pixel') }}
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedCatalog }">
                                <i class="fas" :class="selectedCatalog ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Catalog') }}
                            </span>
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
        showManualThreads: false,
        showManualAdAccount: false,
        showManualPixel: false,
        showManualCatalog: false,

        // Search queries
        pagesSearch: '',
        instagramSearch: '',
        threadsSearch: '',
        adAccountsSearch: '',
        pixelsSearch: '',
        catalogsSearch: '',

        // Manual input values
        manualThreadsId: '',

        // Selected items (multiple values per type - arrays)
        selectedPages: @json((array) ($selectedAssets['page'] ?? [])),
        selectedInstagrams: @json((array) ($selectedAssets['instagram_account'] ?? [])),
        selectedThreadsAccounts: @json((array) ($selectedAssets['threads_account'] ?? [])),
        selectedAdAccounts: @json((array) ($selectedAssets['ad_account'] ?? [])),
        selectedPixels: @json((array) ($selectedAssets['pixel'] ?? [])),
        selectedCatalogs: @json((array) ($selectedAssets['catalog'] ?? [])),

        // All available assets (for search/filtering)
        allPages: @json($pages ?? []),
        allInstagram: @json($instagramAccounts ?? []),
        allThreads: @json($threadsAccounts ?? []),
        allAdAccounts: @json($adAccounts ?? []),
        allPixels: @json($pixels ?? []),
        allCatalogs: @json($catalogs ?? []),

        // Search matching methods
        matchesPagesSearch(name) {
            if (!this.pagesSearch) return true;
            return name.toLowerCase().includes(this.pagesSearch.toLowerCase());
        },

        matchesInstagramSearch(username) {
            if (!this.instagramSearch) return true;
            return username.toLowerCase().includes(this.instagramSearch.toLowerCase());
        },

        // Computed filtered counts
        get filteredPagesCount() {
            if (!this.pagesSearch) return this.allPages.length;
            return this.allPages.filter(p => p.name.toLowerCase().includes(this.pagesSearch.toLowerCase())).length;
        },

        get filteredInstagramCount() {
            if (!this.instagramSearch) return this.allInstagram.length;
            return this.allInstagram.filter(ig => ig.username.toLowerCase().includes(this.instagramSearch.toLowerCase())).length;
        },

        // Bulk selection methods
        selectAllPages() {
            this.allPages.forEach(page => {
                if (this.matchesPagesSearch(page.name) && !this.selectedPages.includes(page.id)) {
                    this.selectedPages.push(page.id);
                }
            });
        },

        deselectAllPages() {
            if (this.pagesSearch) {
                this.selectedPages = this.selectedPages.filter(id => {
                    const page = this.allPages.find(p => p.id === id);
                    return page && !this.matchesPagesSearch(page.name);
                });
            } else {
                this.selectedPages = [];
            }
        },

        selectAllInstagram() {
            this.allInstagram.forEach(ig => {
                if (this.matchesInstagramSearch(ig.username) && !this.selectedInstagrams.includes(ig.id)) {
                    this.selectedInstagrams.push(ig.id);
                }
            });
        },

        deselectAllInstagram() {
            if (this.instagramSearch) {
                this.selectedInstagrams = this.selectedInstagrams.filter(id => {
                    const ig = this.allInstagram.find(i => i.id === id);
                    return ig && !this.matchesInstagramSearch(ig.username);
                });
            } else {
                this.selectedInstagrams = [];
            }
        },
    }
}
</script>
@endpush
@endsection
