@extends('layouts.admin')

@section('title', __('products.products'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="container mx-auto px-4 py-6" x-data="productsPage()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('products.products') }}</h1>
            <p class="mt-2 text-gray-600 dark:text-slate-400">{{ __('products.manage_products') }}</p>
        </div>
        @can('create', App\Models\Offering::class)
        <a href="{{ route('orgs.products.create', ['org' => $currentOrg]) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            <svg class="me-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('products.new_product') }}
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800/50 shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <input type="text" x-model="filters.search" @input.debounce="loadProducts()" placeholder="{{ __('products.search_products') }}" class="w-full rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <select x-model="filters.status" @change="loadProducts()" class="w-full rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('products.all_statuses') }}</option>
                    <option value="active">{{ __('products.status.active') }}</option>
                    <option value="inactive">{{ __('products.status.inactive') }}</option>
                    <option value="draft">{{ __('products.status.draft') }}</option>
                </select>
            </div>
            <div>
                <select x-model="filters.category" @change="loadProducts()" class="w-full rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('products.all_categories') }}</option>
                    <option value="digital">{{ __('products.digital') }}</option>
                    <option value="physical">{{ __('products.physical') }}</option>
                    <option value="subscription">{{ __('products.subscription') }}</option>
                </select>
            </div>
            <div>
                <select x-model="filters.sort" @change="loadProducts()" class="w-full rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="newest">{{ __('products.sort_options.newest') }}</option>
                    <option value="oldest">{{ __('products.sort_options.oldest') }}</option>
                    <option value="name">{{ __('products.sort_options.name_asc') }}</option>
                    <option value="price_high">{{ __('products.sort_options.price_high_low') }}</option>
                    <option value="price_low">{{ __('products.sort_options.price_low_high') }}</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Products Grid -->
    <div x-show="!loading" x-cloak>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <template x-for="product in products" :key="product.id">
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                    <!-- Product Image -->
                    <div class="relative h-48 bg-gray-200">
                        <img :src="product.image || '/images/placeholder-product.jpg'" :alt="product.name" class="w-full h-full object-cover">
                        <span class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold rounded-full"
                              :class="{
                                  'bg-green-100 text-green-800': product.status === 'active',
                                  'bg-gray-100 text-gray-800': product.status === 'inactive',
                                  'bg-yellow-100 text-yellow-800': product.status === 'draft'
                              }"
                              x-text="product.status_label">
                        </span>
                    </div>

                    <!-- Product Details -->
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 flex-1" x-text="product.name"></h3>
                        </div>

                        <p class="text-sm text-gray-600 mb-3 line-clamp-2" x-text="product.description"></p>

                        <!-- Product Meta -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-gray-500" x-text="product.category_label"></span>
                            <span class="text-lg font-bold text-indigo-600" x-text="product.price_formatted"></span>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-2 mb-3 text-xs text-gray-500 dark:text-slate-400">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span x-text="product.campaigns_count || 0">0</span> {{ __('products.campaign') }}
                            </div>
                            <div class="flex items-center">
                                <svg class="h-4 w-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0h2a2 2 0 012 2v0a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span x-text="product.sales_count || 0">0</span> {{ __('products.sales') }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a :href="'{{ route('orgs.products.show', ['org' => $currentOrg, 'product' => '']) }}' + product.id" class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-md text-sm font-medium text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                {{ __('products.view') }}
                            </a>
                            @can('update', App\Models\Offering::class)
                            <a :href="'{{ route('orgs.products.edit', ['org' => $currentOrg, 'product' => '']) }}' + product.id" class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-indigo-300 dark:border-indigo-500 rounded-md text-sm font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-500/20 hover:bg-indigo-100 dark:hover:bg-indigo-500/30">
                                {{ __('products.edit') }}
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="products.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('products.no_products') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ __('products.start_creating') }}</p>
            @can('create', App\Models\Offering::class)
            <div class="mt-6">
                <a href="{{ route('orgs.products.create', ['org' => $currentOrg]) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    <svg class="me-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('products.new_product') }}
                </a>
            </div>
            @endcan
        </div>

        <!-- Pagination -->
        <div x-show="products.length > 0" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-slate-300">
                {{ __('common.showing') }} <span class="font-medium" x-text="pagination.from"></span> {{ __('common.to') }} <span class="font-medium" x-text="pagination.to"></span> {{ __('common.of') }} <span class="font-medium" x-text="pagination.total"></span> {{ __('products.product') }}
            </div>
            <div class="flex gap-2">
                <button @click="previousPage()" :disabled="!pagination.prev_page_url" :class="!pagination.prev_page_url ? 'opacity-50 cursor-not-allowed' : ''" class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-md text-sm font-medium text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                    {{ __('products.previous') }}
                </button>
                <button @click="nextPage()" :disabled="!pagination.next_page_url" :class="!pagination.next_page_url ? 'opacity-50 cursor-not-allowed' : ''" class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-md text-sm font-medium text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                    {{ __('products.next') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productsPage() {
    return {
        loading: true,
        products: [],
        filters: {
            search: '',
            status: '',
            category: '',
            sort: 'newest'
        },
        pagination: {
            from: 0,
            to: 0,
            total: 0,
            current_page: 1,
            last_page: 1,
            prev_page_url: null,
            next_page_url: null
        },

        init() {
            this.loadProducts();
        },

        async loadProducts(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    page: page
                });

                const response = await fetch(`/api/products?${params}`);
                const data = await response.json();

                this.products = data.data || [];
                this.pagination = {
                    from: data.from || 0,
                    to: data.to || 0,
                    total: data.total || 0,
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    prev_page_url: data.prev_page_url,
                    next_page_url: data.next_page_url
                };
            } catch (error) {
                console.error('Error loading products:', error);
            } finally {
                this.loading = false;
            }
        },

        previousPage() {
            if (this.pagination.prev_page_url) {
                this.loadProducts(this.pagination.current_page - 1);
            }
        },

        nextPage() {
            if (this.pagination.next_page_url) {
                this.loadProducts(this.pagination.current_page + 1);
            }
        }
    }
}
</script>
@endpush
@endsection
