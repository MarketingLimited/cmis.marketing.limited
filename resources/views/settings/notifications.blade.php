@extends('layouts.admin')
@section('title', 'إعدادات الإشعارات')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">إعدادات الإشعارات</h1>
    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <div class="flex items-center justify-between"><span>إشعارات البريد الإلكتروني</span><input type="checkbox" class="rounded"></div>
        <div class="flex items-center justify-between"><span>إشعارات التطبيق</span><input type="checkbox" class="rounded"></div>
    </div>
</div>
@endsection
