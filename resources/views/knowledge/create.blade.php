@extends('layouts.app')
@section('title', 'إضافة معرفة جديدة')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="knowledgeCreate()">
    <h1 class="text-2xl font-bold mb-6">إضافة معرفة جديدة</h1>
    <form @submit.prevent="save" class="bg-white shadow rounded-lg p-6 space-y-4">
        <div><label class="block text-sm font-medium">العنوان</label><input type="text" x-model="item.title" required class="mt-1 block w-full rounded-md border-gray-300"></div>
        <div><label class="block text-sm font-medium">المحتوى</label><textarea x-model="item.content" rows="6" class="mt-1 block w-full rounded-md border-gray-300"></textarea></div>
        <div class="flex justify-end gap-3"><button type="button" onclick="window.location='{{route('knowledge.index')}}'" class="px-4 py-2 border rounded-md">إلغاء</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">حفظ</button></div>
    </form>
</div>
@push('scripts')
<script>
function knowledgeCreate(){return{item:{title:'',content:''},async save(){await fetch('/api/orgs/1/knowledge',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(this.item)});window.location='{{route('knowledge.index')}}'}}}
</script>
@endpush
@endsection
