@extends('layouts.admin')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp
@section('title', 'تعديل معرفة')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="knowledgeEdit({{$knowledgeId}})">
    <h1 class="text-2xl font-bold mb-6">تعديل المعرفة</h1>
    <form @submit.prevent="save" class="bg-white shadow rounded-lg p-6 space-y-4">
        <div><label>العنوان</label><input type="text" x-model="item.title" class="mt-1 block w-full rounded-md border-gray-300"></div>
        <div class="flex justify-end gap-3"><button type="button" onclick="window.location='{{route('orgs.knowledge.index', ['org' => $currentOrg])}}'" class="px-4 py-2 border rounded-md">إلغاء</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">حفظ</button></div>
    </form>
</div>
@push('scripts')
<script>
function knowledgeEdit(id){return{item:{},async init(){const r=await fetch(`/api/orgs/{{ $currentOrg }}/knowledge/${id}`);this.item=await r.json()},async save(){await fetch(`/api/orgs/{{ $currentOrg }}/knowledge/${id}`,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(this.item)});window.location='{{route('orgs.knowledge.index', ['org' => $currentOrg])}}'}}}
</script>
@endpush
@endsection
