@extends('layouts.app')
@section('title', 'الملف الشخصي')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="userProfile()">
    <h1 class="text-2xl font-bold mb-6">الملف الشخصي</h1>
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center space-x-4 mb-6">
            <img :src="user.avatar||'/images/default-avatar.png'" class="w-20 h-20 rounded-full">
            <div><h2 class="text-xl font-bold" x-text="user.name"></h2><p class="text-gray-600" x-text="user.email"></p></div>
        </div>
        <div class="border-t pt-6"><h3 class="text-lg font-medium mb-4">معلومات إضافية</h3><dl class="space-y-3"><div><dt class="text-sm text-gray-500">الدور</dt><dd class="text-sm font-medium" x-text="user.role"></dd></div></dl></div>
    </div>
</div>
@push('scripts')
<script>
function userProfile(){return{user:{},async init(){const r=await fetch('/api/auth/me');this.user=await r.json()}}}
</script>
@endpush
@endsection
