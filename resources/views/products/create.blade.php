@extends('layouts.app')

@section('title', 'إنشاء منتج جديد')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="productCreate()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">إنشاء منتج جديد</h1>
                <p class="mt-2 text-gray-600">أضف منتج جديد إلى قائمة منتجاتك</p>
            </div>
            <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                رجوع
            </a>
        </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="saveProduct" class="bg-white shadow rounded-lg">
        <div class="p-6 space-y-6">
            <!-- Basic Information -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">المعلومات الأساسية</h2>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Name (Arabic) -->
                    <div>
                        <label for="name_ar" class="block text-sm font-medium text-gray-700">اسم المنتج (عربي) *</label>
                        <input type="text" id="name_ar" x-model="product.name_ar" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p x-show="errors.name_ar" class="mt-1 text-sm text-red-600" x-text="errors.name_ar"></p>
                    </div>

                    <!-- Name (English) -->
                    <div>
                        <label for="name_en" class="block text-sm font-medium text-gray-700">اسم المنتج (إنجليزي)</label>
                        <input type="text" id="name_en" x-model="product.name_en" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- SKU -->
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">رمز المنتج (SKU)</label>
                        <input type="text" id="sku" x-model="product.sku" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">الفئة *</label>
                        <select id="category" x-model="product.category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">اختر الفئة</option>
                            <option value="digital">رقمية</option>
                            <option value="physical">مادية</option>
                            <option value="subscription">اشتراك</option>
                            <option value="service">خدمة</option>
                        </select>
                    </div>
                </div>

                <!-- Description (Arabic) -->
                <div class="mt-6">
                    <label for="description_ar" class="block text-sm font-medium text-gray-700">الوصف (عربي) *</label>
                    <textarea id="description_ar" x-model="product.description_ar" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <!-- Description (English) -->
                <div class="mt-6">
                    <label for="description_en" class="block text-sm font-medium text-gray-700">الوصف (إنجليزي)</label>
                    <textarea id="description_en" x-model="product.description_en" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <!-- Pricing -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">الأسعار</h2>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">السعر *</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" id="price" x-model="product.price" step="0.01" min="0" required class="block w-full rounded-md border-gray-300 pl-12 focus:border-indigo-500 focus:ring-indigo-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">ر.س</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sale Price -->
                    <div>
                        <label for="sale_price" class="block text-sm font-medium text-gray-700">سعر الخصم</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" id="sale_price" x-model="product.sale_price" step="0.01" min="0" class="block w-full rounded-md border-gray-300 pl-12 focus:border-indigo-500 focus:ring-indigo-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">ر.س</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cost -->
                    <div>
                        <label for="cost" class="block text-sm font-medium text-gray-700">التكلفة</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" id="cost" x-model="product.cost" step="0.01" min="0" class="block w-full rounded-md border-gray-300 pl-12 focus:border-indigo-500 focus:ring-indigo-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">ر.س</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock & Status -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">المخزون والحالة</h2>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <!-- Stock Quantity -->
                    <div>
                        <label for="stock_quantity" class="block text-sm font-medium text-gray-700">كمية المخزون</label>
                        <input type="number" id="stock_quantity" x-model="product.stock_quantity" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Low Stock Alert -->
                    <div>
                        <label for="low_stock_alert" class="block text-sm font-medium text-gray-700">تنبيه انخفاض المخزون</label>
                        <input type="number" id="low_stock_alert" x-model="product.low_stock_alert" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">الحالة *</label>
                        <select id="status" x-model="product.status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                            <option value="draft">مسودة</option>
                        </select>
                    </div>
                </div>

                <!-- Track Stock -->
                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="product.track_stock" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <span class="mr-2 text-sm text-gray-700">تتبع المخزون</span>
                    </label>
                </div>
            </div>

            <!-- Images -->
            <div>
                <h2 class="text-lg font-medium text-gray-900 mb-4">الصور</h2>
                
                <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                <span>رفع صورة</span>
                                <input id="file-upload" type="file" class="sr-only" accept="image/*" @change="handleImageUpload">
                            </label>
                            <p class="pr-1">أو سحب وإفلات</p>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-gray-50 px-6 py-4 flex items-center justify-between">
            <button type="button" @click="window.location='{{ route('products.index') }}'" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                إلغاء
            </button>
            <div class="flex gap-3">
                <button type="button" @click="saveProduct('draft')" :disabled="saving" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                    حفظ كمسودة
                </button>
                <button type="submit" :disabled="saving" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                    <span x-show="!saving">إنشاء المنتج</span>
                    <span x-show="saving">جاري الحفظ...</span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function productCreate() {
    return {
        saving: false,
        product: {
            name_ar: '',
            name_en: '',
            sku: '',
            category: '',
            description_ar: '',
            description_en: '',
            price: '',
            sale_price: '',
            cost: '',
            stock_quantity: 0,
            low_stock_alert: 5,
            status: 'draft',
            track_stock: true
        },
        errors: {},

        async saveProduct(status = null) {
            if (status) {
                this.product.status = status;
            }

            this.saving = true;
            this.errors = {};

            try {
                const orgId = this.getCurrentOrgId();
                const response = await fetch(`/api/orgs/${orgId}/offerings/products`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.product)
                });

                const data = await response.json();

                if (response.ok) {
                    window.location = '{{ route('products.index') }}';
                } else {
                    this.errors = data.errors || {};
                    alert(data.message || 'حدث خطأ أثناء الحفظ');
                }
            } catch (error) {
                console.error('Error saving product:', error);
                alert('حدث خطأ في الاتصال');
            } finally {
                this.saving = false;
            }
        },

        handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                // Handle image upload
                console.log('Image uploaded:', file.name);
            }
        },

        getCurrentOrgId() {
            // Get from session or user's current org
            return window.currentOrgId || 1;
        }
    }
}
</script>
@endpush
@endsection
