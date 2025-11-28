@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('briefs.show_page_title'))
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">{{ __('briefs.show_page_title') }}</h1>
    <div class="bg-white shadow rounded-lg p-6">
        <p>{{ __('briefs.brief_content') }}</p>
    </div>
</div>
@endsection
