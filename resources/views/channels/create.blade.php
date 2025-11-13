@extends('layouts.app')
@section('title', 'إضافة قناة جديدة')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="channelCreate()">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">إضافة قناة جديدة</h1>
    <form @submit.prevent="saveChannel" class="bg-white shadow rounded-lg p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">المنصة *</label>
            <select x-model="channel.platform" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">اختر المنصة</option>
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="twitter">Twitter</option>
                <option value="linkedin">LinkedIn</option>
                <option value="tiktok">TikTok</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">اسم القناة *</label>
            <input type="text" x-model="channel.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="window.location='{{ route('channels.index') }}'" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">إلغاء</button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">إضافة القناة</button>
        </div>
    </form>
</div>
@push('scripts')
<script>
function channelCreate() {
    return {
        channel: {platform: '', name: ''},
        async saveChannel() {
            await fetch('/api/orgs/1/channels', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify(this.channel)
            });
            window.location = '{{ route('channels.index') }}';
        }
    }
}
</script>
@endpush
@endsection
