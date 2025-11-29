@extends('layouts.admin')

@section('title', __('catalogs.product_catalogs'))

@section('content')
<div class="p-6" x-data="catalogsManager()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('catalogs.product_catalogs') }}</h1>
            <p class="text-slate-400 mt-1">{{ __('catalogs.manage_catalogs') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('orgs.catalogs.import', ['org' => $orgModel->org_id]) }}"
               class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors flex items-center gap-2">
                <i class="fas fa-upload"></i>
                <span>{{ __('catalogs.import_catalog') }}</span>
            </a>
            <button @click="syncAllCatalogs()"
                    class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-blue-500/25">
                <i class="fas fa-sync" :class="{ 'animate-spin': syncing }"></i>
                <span>{{ __('catalogs.sync_catalogs') }}</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-box text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['total_products'] ?? 0) }}</p>
                    <p class="text-xs text-slate-400">{{ __('catalogs.total_products') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['active_products'] ?? 0) }}</p>
                    <p class="text-xs text-slate-400">{{ __('catalogs.active_products') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-sync text-purple-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['synced_products'] ?? 0) }}</p>
                    <p class="text-xs text-slate-400">{{ __('catalogs.synced_products') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['pending_sync'] ?? 0) }}</p>
                    <p class="text-xs text-slate-400">{{ __('catalogs.pending_sync') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['sync_errors'] ?? 0) }}</p>
                    <p class="text-xs text-slate-400">{{ __('catalogs.sync_errors') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Catalogs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($platformCatalogs as $key => $catalog)
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 overflow-hidden hover:border-slate-600/50 transition-colors">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-{{ $catalog['color'] }}-500/20 flex items-center justify-center">
                            <i class="fab {{ $catalog['icon'] }} text-{{ $catalog['color'] }}-400 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">{{ __('catalogs.' . $key . '_catalog') }}</h3>
                            <p class="text-slate-400 text-xs">{{ __('catalogs.' . $key . '_catalog_desc') }}</p>
                        </div>
                    </div>
                    @if($catalog['connected'])
                        <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400">{{ __('catalogs.synced') }}</span>
                    @else
                        <span class="px-2 py-1 text-xs rounded-full bg-slate-500/20 text-slate-400">{{ __('catalogs.never_synced') }}</span>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">{{ __('catalogs.products_count') }}</span>
                        <span class="text-white font-medium">{{ number_format($catalog['products_count'] ?? 0) }}</span>
                    </div>
                    @if($catalog['last_sync'])
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-400">{{ __('catalogs.last_sync') }}</span>
                            <span class="text-slate-300">{{ $catalog['last_sync'] }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-slate-700/50 flex gap-2">
                    @if($catalog['connected'])
                        <button @click="syncCatalog('{{ $key }}')"
                                class="flex-1 px-3 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-sync" :class="{ 'animate-spin': syncingPlatform === '{{ $key }}' }"></i>
                            {{ __('catalogs.sync_now') }}
                        </button>
                        <a href="{{ route('orgs.catalogs.show', ['org' => $orgModel->org_id, 'catalog' => $key]) }}"
                           class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-eye"></i>
                            {{ __('catalogs.view_products') }}
                        </a>
                    @else
                        <a href="{{ route('orgs.settings.platform-connections.index', ['org' => $orgModel->org_id]) }}"
                           class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-plug"></i>
                            {{ __('catalogs.go_to_connections') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Products Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50">
        <div class="p-4 border-b border-slate-700/50">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-lg font-semibold text-white">{{ __('navigation.products') }}</h2>
                <div class="flex gap-3">
                    <div class="relative">
                        <input type="text" x-model="searchQuery"
                               placeholder="{{ __('common.search') }}..."
                               class="w-64 bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 ps-10 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 text-sm">
                        <i class="fas fa-search absolute start-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    </div>
                    <select x-model="platformFilter" class="bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500 text-sm">
                        <option value="">{{ __('catalogs.all_catalogs') }}</option>
                        <option value="meta">Meta</option>
                        <option value="google">Google</option>
                        <option value="tiktok">TikTok</option>
                        <option value="snapchat">Snapchat</option>
                    </select>
                </div>
            </div>
        </div>

        @if($products->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-700/50">
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('catalogs.product_name') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('catalogs.platform') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('catalogs.product_price') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('catalogs.product_availability') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('catalogs.sync_status') }}</th>
                            <th class="text-end px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($products as $product)
                        <tr class="hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($product->image_url ?? false)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover bg-slate-700">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center text-slate-500">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-white font-medium">{{ $product->name }}</p>
                                        <p class="text-slate-400 text-xs">{{ $product->sku ?? $product->product_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-{{ $product->platform_color ?? 'blue' }}-500/20 text-{{ $product->platform_color ?? 'blue' }}-400">
                                    {{ ucfirst($product->platform ?? 'Unknown') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                ${{ number_format($product->price ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @if($product->in_stock ?? true)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400">{{ __('catalogs.in_stock') }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-400">{{ __('catalogs.out_of_stock') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($product->synced ?? false)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400">{{ __('catalogs.synced') }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-500/20 text-yellow-400">{{ __('catalogs.pending') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end">
                                <button class="p-2 hover:bg-slate-700 rounded-lg transition-colors text-slate-400 hover:text-white">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-700/50">
                {{ $products->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="w-20 h-20 rounded-full bg-slate-700/50 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-box-open text-3xl text-slate-500"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">{{ __('catalogs.no_catalogs') }}</h3>
                <p class="text-slate-400 mb-6 max-w-md mx-auto">{{ __('catalogs.no_catalogs_description') }}</p>
                <a href="{{ route('orgs.settings.platform-connections.index', ['org' => $orgModel->org_id]) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-lg transition-all shadow-lg shadow-blue-500/25">
                    <i class="fas fa-plug"></i>
                    {{ __('catalogs.connect_first_catalog') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function catalogsManager() {
    return {
        syncing: false,
        syncingPlatform: null,
        searchQuery: '',
        platformFilter: '',

        syncAllCatalogs() {
            this.syncing = true;
            // API call to sync all catalogs
            setTimeout(() => {
                this.syncing = false;
                // Show success notification
            }, 3000);
        },

        syncCatalog(platform) {
            this.syncingPlatform = platform;
            // API call to sync specific catalog
            setTimeout(() => {
                this.syncingPlatform = null;
                // Show success notification
            }, 2000);
        }
    }
}
</script>
@endpush
