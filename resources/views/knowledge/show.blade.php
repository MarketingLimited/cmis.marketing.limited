@extends('layouts.admin')
@section('title', 'عرض المعرفة')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="knowledgeShow({{$knowledgeId}})">
    <div class="mb-6 flex justify-between"><h1 class="text-2xl font-bold" x-text="item.title"></h1><a href="{{route('knowledge.index')}}" class="px-4 py-2 border rounded-md">رجوع</a></div>
    <div class="bg-white shadow rounded-lg p-6"><div class="prose" x-html="item.content"></div></div>
</div>
@push('scripts')
<script>
function knowledgeShow(id){return{item:{},async init(){const r=await fetch(`/api/orgs/1/knowledge/${id}`);this.item=await r.json()}}}
</script>
@endpush
@endsection
