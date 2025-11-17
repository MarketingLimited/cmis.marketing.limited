@extends('layouts.admin')
@section('title', 'إعدادات الملف الشخصي')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="settingsProfile()">
    <h1 class="text-2xl font-bold mb-6">إعدادات الملف الشخصي</h1>
    <div class="bg-white shadow rounded-lg p-6">
        <form @submit.prevent="saveSettings">
            <div class="space-y-4">
                <div><label>الاسم</label><input type="text" x-model="settings.name" class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div><label>البريد الإلكتروني</label><input type="email" x-model="settings.email" class="mt-1 block w-full rounded-md border-gray-300"></div>
            </div>
            <div class="mt-6"><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">حفظ التغييرات</button></div>
        </form>
    </div>
</div>
@push('scripts')
<script>
function settingsProfile(){return{settings:{},async init(){const r=await fetch('/api/auth/me');this.settings=await r.json()},async saveSettings(){await fetch('/api/auth/profile',{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(this.settings)});alert('تم الحفظ بنجاح')}}}
</script>
@endpush
@endsection
