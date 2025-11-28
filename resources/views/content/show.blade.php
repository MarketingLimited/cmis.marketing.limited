@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('content.content'))
@section('content')
<div class="container mx-auto px-4 py-6" x-data="contentShow({{ $contentId }})">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900" x-text="content.title"></h1>
        <a href="{{ route('orgs.content.index', ['org' => $currentOrg]) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('common.back') }}</a>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <div class="prose max-w-none" x-html="content.body"></div>
        <div class="mt-6 pt-6 border-t flex items-center justify-between">
            <div class="text-sm text-gray-500">
                <p>{{ __('content.published_on') }}: <span x-text="content.published_at"></span></p>
            </div>
            <div class="flex gap-3">
                <a :href="`{{ route('orgs.content.edit', ['org' => $currentOrg, 'content' => '']) }}`.slice(0, -1) + content.id" class="px-4 py-2 border border-indigo-300 rounded-md text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.edit') }}</a>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function contentShow(contentId) {
    return {
        content: {},
        async init() {
            const response = await fetch(`/api/orgs/{{ $currentOrg }}/content/${contentId}`);
            this.content = await response.json();
        }
    }
}
</script>
@endpush
@endsection
