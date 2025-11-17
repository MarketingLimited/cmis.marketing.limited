@extends('layouts.admin')
@section('title', 'تعديل منتج')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="productEdit({{ $product->offering_id }})">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">تعديل المنتج</h1>
                <p class="mt-2 text-gray-600">تحديث معلومات المنتج</p>
            </div>
            <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                رجوع
            </a>
        </div>
    </div>

    <form @submit.prevent="saveProduct" class="bg-white shadow rounded-lg">
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">اسم المنتج *</label>
                    <input type="text" x-model="product.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">السعر *</label>
                    <input type="number" x-model="product.price" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">الوصف</label>
                    <textarea x-model="product.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
            <button type="button" @click="window.location='{{ route('products.index') }}'" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">إلغاء</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">حفظ التغييرات</button>
        </div>
    </form>
</div>
@push('scripts')
<script>
function productEdit(productId) {
    return {
        saving: false,
        product: @json($product ?? []),
        async saveProduct() {
            this.saving = true;
            try {
                const response = await fetch(`/api/orgs/1/offerings/products/${productId}`, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify(this.product)
                });
                if (response.ok) window.location = '{{ route('products.index') }}';
                else alert('حدث خطأ أثناء الحفظ');
            } catch (error) {
                alert('حدث خطأ في الاتصال');
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush
@endsection
