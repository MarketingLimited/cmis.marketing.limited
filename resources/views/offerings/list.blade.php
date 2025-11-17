@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ $title }}</h2>
        <p class="text-gray-600">{{ $description }}</p>
    </div>

    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-3 text-right">اسم العرض</th>
                    <th class="px-4 py-3 text-right">النوع</th>
                    <th class="px-4 py-3 text-right">المؤسسة المالكة</th>
                    <th class="px-4 py-3 text-right">تاريخ الإنشاء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($offerings as $offering)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-2 font-medium text-indigo-700">{{ $offering->name }}</td>
                        <td class="px-4 py-2">{{ $offering->kind }}</td>
                        <td class="px-4 py-2">{{ optional($offering->org)->name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ optional($offering->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">لا توجد بيانات متاحة حاليًا.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
