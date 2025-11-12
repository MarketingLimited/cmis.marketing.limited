@extends('layouts.app')
@section('title', 'إضافة مستخدم جديد')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="userCreate()">
    <h1 class="text-2xl font-bold mb-6">إضافة مستخدم جديد</h1>
    <form @submit.prevent="saveUser" class="bg-white shadow rounded-lg p-6 space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700">الاسم *</label><input type="text" x-model="user.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
            <div><label class="block text-sm font-medium text-gray-700">البريد الإلكتروني *</label><input type="email" x-model="user.email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
        </div>
        <div class="flex justify-end gap-3"><button type="button" onclick="window.location='{{route('users.index')}}'" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700">إلغاء</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">إضافة</button></div>
    </form>
</div>
@push('scripts')
<script>
function userCreate(){return{user:{name:'',email:''},async saveUser(){await fetch('/api/orgs/1/users/invite',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(this.user)});window.location='{{route('users.index')}}'}}}
</script>
@endpush
@endsection
