@extends('layouts.admin')
@section('title', 'تعديل حزمة')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="bundleEdit({{ $bundle->bundle_id }})">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">تعديل الحزمة</h1>
    <form @submit.prevent="saveBundle" class="bg-white shadow rounded-lg p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">اسم الحزمة *</label>
            <input type="text" x-model="bundle.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="window.location='{{ route('offerings.bundles') }}'" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">إلغاء</button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">حفظ</button>
        </div>
    </form>
</div>
@push('scripts')
<script>
function bundleEdit(bundleId) {
    return {
        bundle: @json($bundle ?? []),
        async saveBundle() {
            await fetch(`/api/orgs/1/offerings/bundles/${bundleId}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify(this.bundle)
            });
            window.location = '{{ route('offerings.bundles') }}';
        }
    }
}
</script>
@endpush
@endsection
