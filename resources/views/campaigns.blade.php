@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">قائمة الحملات التسويقية</h1>
    <div class="overflow-x-auto bg-white shadow-md rounded-2xl">
        <table class="min-w-full text-sm text-right">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-4 py-3">اسم الحملة</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3">الميزانية</th>
                    <th class="px-4 py-3">تاريخ البدء</th>
                    <th class="px-4 py-3">تاريخ الانتهاء</th>
                    <th class="px-4 py-3">آخر تحديث</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="px-4 py-2">
                        <a href="/campaigns/{{ $c->campaign_id }}" class="text-indigo-700 font-semibold hover:underline flex items-center gap-1">
                            <span>{{ $c->name }}</span>
                            <span class="text-gray-400">🔍</span>
                        </a>
                    </td>
                    <td class="px-4 py-2">{{ $c->status }}</td>
                    <td class="px-4 py-2">{{ $c->budget ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $c->start_date }}</td>
                    <td class="px-4 py-2">{{ $c->end_date }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $c->updated_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection