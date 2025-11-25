@extends('layouts.admin')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp
@section('title', 'نماذج AI')
@section('content')
<div class="container mx-auto px-4 py-6"><h1 class="text-2xl font-bold mb-6">نماذج الذكاء الاصطناعي</h1><div class="bg-white shadow rounded-lg p-6"><p class="text-gray-600">إدارة نماذج التعلم الآلي المستخدمة</p></div></div>
@endsection
