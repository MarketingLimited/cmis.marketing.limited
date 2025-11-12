@extends('layouts.app')
@section('title', 'إعدادات التكاملات')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">إعدادات التكاملات</h1>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="bg-white shadow rounded-lg p-6"><h3 class="font-bold mb-2">Facebook</h3><button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md">ربط</button></div>
        <div class="bg-white shadow rounded-lg p-6"><h3 class="font-bold mb-2">Instagram</h3><button class="mt-4 px-4 py-2 bg-pink-600 text-white rounded-md">ربط</button></div>
        <div class="bg-white shadow rounded-lg p-6"><h3 class="font-bold mb-2">Twitter</h3><button class="mt-4 px-4 py-2 bg-sky-500 text-white rounded-md">ربط</button></div>
    </div>
</div>
@endsection
