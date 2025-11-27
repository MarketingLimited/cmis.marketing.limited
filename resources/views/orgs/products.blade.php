@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('Products') . ' - ' . ($org->name ?? __('Organization')))

@section('content')
<div class="space-y-6" x-data="productsPage()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Products') }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Products') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('Manage your organization\'s products and offerings') }}</p>
            </div>
            <button @click="showAddModal = true"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition duration-200">
                <i class="fas fa-plus mr-2"></i>
                {{ __('Add Product') }}
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">{{ __('Total Products') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $products->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bullhorn text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">{{ __('In Campaigns') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $products->filter(fn($p) => $p->campaigns_count ?? 0)->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-layer-group text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">{{ __('In Bundles') }}</p>
                    <p class="text-2xl font-bold text-gray-900">0</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <input type="text" x-model="searchQuery" placeholder="{{ __('Search products...') }}"
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            <div class="flex gap-2">
                <button @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'"
                        class="p-2 rounded-lg transition">
                    <i class="fas fa-th-large"></i>
                </button>
                <button @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'"
                        class="p-2 rounded-lg transition">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Products Grid --}}
    <div x-show="viewMode === 'grid'">
        @if($products->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($products as $product)
                    <div x-show="matchesSearch('{{ $product->name }}')"
                         class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition duration-200 overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                    {{ strtoupper(substr($product->name, 0, 2)) }}
                                </div>
                                <div class="flex items-center gap-1">
                                    <button @click="editProduct('{{ $product->offering_id }}', '{{ addslashes($product->name) }}', '{{ addslashes($product->description ?? '') }}', '{{ addslashes($product->provider ?? '') }}')"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="confirmDelete('{{ $product->offering_id }}', '{{ addslashes($product->name) }}')"
                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
                                @if($product->description)
                                    <p class="text-gray-500 text-sm mt-1 line-clamp-2">{{ $product->description }}</p>
                                @else
                                    <p class="text-gray-400 text-sm mt-1 italic">{{ __('No description') }}</p>
                                @endif
                            </div>
                            @if($product->provider)
                                <div class="mt-4 flex items-center text-sm text-gray-500">
                                    <i class="fas fa-building mr-2"></i>
                                    {{ $product->provider }}
                                </div>
                            @endif
                            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-400">
                                <span><i class="fas fa-clock mr-1"></i> {{ $product->created_at ? $product->created_at->diffForHumans() : '-' }}</span>
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">{{ __('Product') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-box-open text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('No products yet') }}</h3>
                <p class="text-gray-500 mb-6">{{ __('Get started by adding your first product') }}</p>
                <button @click="showAddModal = true"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>
                    {{ __('Add Product') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Products List --}}
    <div x-show="viewMode === 'list'" x-cloak>
        @if($products->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Product') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Provider') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Created') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($products as $product)
                            <tr x-show="matchesSearch('{{ $product->name }}')" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold">
                                            {{ strtoupper(substr($product->name, 0, 2)) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                            @if($product->description)
                                                <div class="text-sm text-gray-500 truncate max-w-xs">{{ Str::limit($product->description, 50) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $product->provider ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $product->created_at ? $product->created_at->format('M d, Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button @click="editProduct('{{ $product->offering_id }}', '{{ addslashes($product->name) }}', '{{ addslashes($product->description ?? '') }}', '{{ addslashes($product->provider ?? '') }}')"
                                            class="text-blue-600 hover:text-blue-900 mr-3">{{ __('Edit') }}</button>
                                    <button @click="confirmDelete('{{ $product->offering_id }}', '{{ addslashes($product->name) }}')"
                                            class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Add/Edit Modal --}}
    <div x-show="showAddModal || showEditModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showAddModal || showEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="closeModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showAddModal || showEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <form :action="showEditModal ? '/orgs/{{ $currentOrg }}/products/' + editId : '{{ route('orgs.products.store', $currentOrg) }}'" method="POST">
                    @csrf
                    <input type="hidden" name="_method" :value="showEditModal ? 'PUT' : 'POST'">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10"
                                 :class="showEditModal ? 'bg-blue-100' : 'bg-green-100'">
                                <i class="fas" :class="showEditModal ? 'fa-edit text-blue-600' : 'fa-plus text-green-600'"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" x-text="showEditModal ? '{{ __('Edit Product') }}' : '{{ __('Add New Product') }}'"></h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Product Name') }} *</label>
                                        <input type="text" name="name" id="name" x-model="formData.name" required
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                               placeholder="{{ __('Enter product name') }}">
                                    </div>
                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
                                        <textarea name="description" id="description" x-model="formData.description" rows="3"
                                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                  placeholder="{{ __('Enter product description') }}"></textarea>
                                    </div>
                                    <div>
                                        <label for="provider" class="block text-sm font-medium text-gray-700">{{ __('Provider/Manufacturer') }}</label>
                                        <input type="text" name="provider" id="provider" x-model="formData.provider"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                               placeholder="{{ __('Enter provider name') }}">
                                    </div>
                                    <input type="hidden" name="kind" value="product">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <span x-text="showEditModal ? '{{ __('Update') }}' : '{{ __('Create') }}'"></span>
                        </button>
                        <button type="button" @click="closeModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showDeleteModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('Delete Product') }}</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    {{ __('Are you sure you want to delete') }} "<span x-text="deleteName" class="font-semibold"></span>"?
                                    {{ __('This action cannot be undone.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form :action="'/orgs/{{ $currentOrg }}/products/' + deleteId" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Delete') }}
                        </button>
                    </form>
                    <button type="button" @click="showDeleteModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productsPage() {
    return {
        searchQuery: '',
        viewMode: 'grid',
        showAddModal: false,
        showEditModal: false,
        showDeleteModal: false,
        editId: null,
        deleteId: null,
        deleteName: '',
        formData: {
            name: '',
            description: '',
            provider: ''
        },

        matchesSearch(name) {
            if (!this.searchQuery) return true;
            return name.toLowerCase().includes(this.searchQuery.toLowerCase());
        },

        editProduct(id, name, description, provider) {
            this.editId = id;
            this.formData.name = name;
            this.formData.description = description;
            this.formData.provider = provider;
            this.showEditModal = true;
        },

        confirmDelete(id, name) {
            this.deleteId = id;
            this.deleteName = name;
            this.showDeleteModal = true;
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.editId = null;
            this.formData = { name: '', description: '', provider: '' };
        }
    }
}
</script>
@endpush
@endsection
