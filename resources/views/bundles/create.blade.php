@extends('layouts.admin')
@section('title', 'إنشاء حزمة جديدة')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="bundleCreate()">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">إنشاء حزمة جديدة</h1>
    <form @submit.prevent="saveBundle" class="bg-white shadow rounded-lg p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">اسم الحزمة *</label>
            <input type="text" x-model="bundle.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">الوصف</label>
            <textarea x-model="bundle.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">نسبة الخصم (%)</label>
            <input type="number" x-model="bundle.discount" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="window.location='{{ route('offerings.bundles') }}'" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">إلغاء</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">إنشاء الحزمة</button>
        </div>
    </form>
</div>
@push('scripts')
<script>
function bundleCreate() {
    return {
        saving: false,
        bundle: {name: '', description: '', discount: 0},
        async saveBundle() {
            this.saving = true;
            try {
                await fetch('/api/orgs/1/offerings/bundles', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify(this.bundle)
                });
                window.location = '{{ route('offerings.bundles') }}';
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush
@endsection
