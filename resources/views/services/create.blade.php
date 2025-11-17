@extends('layouts.admin')
@section('title', 'إنشاء خدمة جديدة')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="serviceCreate()">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">إنشاء خدمة جديدة</h1>
        <p class="mt-2 text-gray-600">أضف خدمة جديدة إلى قائمة خدماتك</p>
    </div>
    <form @submit.prevent="saveService" class="bg-white shadow rounded-lg p-6 space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">اسم الخدمة *</label>
                <input type="text" x-model="service.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">السعر *</label>
                <input type="number" x-model="service.price" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">الوصف</label>
                <textarea x-model="service.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="window.location='{{ route('services.index') }}'" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">إلغاء</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">إنشاء الخدمة</button>
        </div>
    </form>
</div>
@push('scripts')
<script>
function serviceCreate() {
    return {
        saving: false,
        service: {name: '', price: '', description: ''},
        async saveService() {
            this.saving = true;
            try {
                const response = await fetch('/api/orgs/1/offerings/services', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify(this.service)
                });
                if (response.ok) window.location = '{{ route('services.index') }}';
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush
@endsection
