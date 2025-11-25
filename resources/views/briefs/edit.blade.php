@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'تعديل Brief')
@section('content')
<div class="container mx-auto px-4 py-6"><h1 class="text-2xl font-bold mb-6">تعديل البريف</h1><form class="bg-white shadow rounded-lg p-6"><div><label>عنوان البريف</label><input type="text" class="mt-1 block w-full rounded-md border-gray-300"></div><button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md">حفظ</button></form></div>
@endsection
