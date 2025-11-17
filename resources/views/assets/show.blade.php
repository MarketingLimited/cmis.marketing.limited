@extends('layouts.admin')
@section('title', 'تفاصيل الأصل')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="assetShow({{ $assetId }})">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900" x-text="asset.name"></h1>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 bg-white shadow rounded-lg p-6">
            <img :src="asset.url" :alt="asset.name" class="w-full rounded-lg">
        </div>
        
        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">معلومات الأصل</h3>
                <dl class="space-y-3">
                    <div><dt class="text-xs text-gray-500">النوع</dt><dd class="text-sm font-medium" x-text="asset.type"></dd></div>
                    <div><dt class="text-xs text-gray-500">الحجم</dt><dd class="text-sm font-medium" x-text="asset.size"></dd></div>
                    <div><dt class="text-xs text-gray-500">تاريخ الرفع</dt><dd class="text-sm font-medium" x-text="asset.created_at"></dd></div>
                </dl>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function assetShow(assetId) {
    return {
        asset: {},
        async init() {
            const response = await fetch(`/api/orgs/1/creative/assets/${assetId}`);
            this.asset = await response.json();
        }
    }
}
</script>
@endpush
@endsection
