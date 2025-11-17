@extends('layouts.admin')
@section('title', 'تفاصيل الحزمة')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="bundleShow({{ $bundle->bundle_id }})">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900" x-text="bundle.name">الحزمة</h1>
        <div class="flex gap-3">
            <a :href="'/bundles/' + bundle.bundle_id + '/edit'" class="inline-flex items-center px-4 py-2 border border-indigo-300 rounded-md text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100">تعديل</a>
            <a href="{{ route('offerings.bundles') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">رجوع</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">الوصف</h2>
                <p class="text-gray-600" x-text="bundle.description"></p>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">المنتجات المضمنة</h2>
                <div class="space-y-3">
                    <template x-for="item in bundle.items" :key="item.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <span x-text="item.name"></span>
                            <span class="text-sm text-gray-500" x-text="item.quantity + ' × ' + item.price"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">السعر الإجمالي</h3>
                <p class="text-3xl font-bold text-indigo-600" x-text="bundle.total_price + ' ر.س'"></p>
                <p class="mt-2 text-sm text-green-600" x-text="'توفير ' + bundle.discount + '%'"></p>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function bundleShow(bundleId) {
    return {
        bundle: {name: '', description: '', total_price: 0, discount: 0, items: []},
        async init() {
            const response = await fetch(`/api/orgs/1/offerings/bundles/${bundleId}`);
            this.bundle = await response.json();
        }
    }
}
</script>
@endpush
@endsection
