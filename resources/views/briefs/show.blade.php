@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'عرض Brief')
@section('content')
<div class="container mx-auto px-4 py-6"><h1 class="text-2xl font-bold mb-6">تفاصيل البريف</h1><div class="bg-white shadow rounded-lg p-6"><p>محتوى البريف الإبداعي</p></div></div>
@endsection
