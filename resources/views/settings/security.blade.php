@extends('layouts.admin')
@section('title', 'إعدادات الأمان')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">إعدادات الأمان</h1>
    <div class="bg-white shadow rounded-lg p-6"><form class="space-y-4"><div><label>كلمة المرور الحالية</label><input type="password" class="mt-1 block w-full rounded-md border-gray-300"></div><div><label>كلمة المرور الجديدة</label><input type="password" class="mt-1 block w-full rounded-md border-gray-300"></div><div><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">تحديث كلمة المرور</button></div></form></div>
</div>
@endsection
