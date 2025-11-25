@extends('layouts.admin')
@section('title', 'تفاصيل القناة')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp
@section('content')
<div class="container mx-auto px-4 py-6" x-data="channelShow({{ $channelId }})">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900" x-text="channel.name"></h1>
        <a href="{{ route('orgs.channels.index', ['org' => $currentOrg]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">رجوع</a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium mb-4">معلومات القناة</h2>
            <dl class="space-y-4">
                <div><dt class="text-sm font-medium text-gray-500">المنصة</dt><dd class="mt-1 text-sm text-gray-900" x-text="channel.platform"></dd></div>
                <div><dt class="text-sm font-medium text-gray-500">الحالة</dt><dd class="mt-1" x-html="channel.status_badge"></dd></div>
                <div><dt class="text-sm font-medium text-gray-500">عدد المتابعين</dt><dd class="mt-1 text-sm text-gray-900" x-text="channel.followers_count"></dd></div>
            </dl>
        </div>
        
        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-4">الإحصائيات</h3>
                <div class="space-y-3">
                    <div><span class="text-2xl font-bold text-indigo-600" x-text="channel.posts_count"></span><p class="text-sm text-gray-500">منشور</p></div>
                    <div><span class="text-2xl font-bold text-green-600" x-text="channel.engagement_rate + '%'"></span><p class="text-sm text-gray-500">معدل التفاعل</p></div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function channelShow(channelId) {
    return {
        channel: {},
        async init() {
            const response = await fetch(`/api/orgs/1/channels/${channelId}`);
            this.channel = await response.json();
        }
    }
}
</script>
@endpush
@endsection
