@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">🖼️ إدارة الأصول الإبداعية (Creative Assets)</h2>
        <p class="text-gray-600">استعرض آخر الأصول الإبداعية وتعرّف على حالتها ومصدرها.</p>
    </div>

    <div class="flex flex-wrap gap-4">
        <a href="/creative" class="inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-4 py-2 rounded-lg font-semibold hover:bg-orange-200 transition">🎨 العودة للوحة الإبداع</a>
        <a href="/creative-assets/create" class="inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-4 py-2 rounded-lg font-semibold hover:bg-orange-200 transition">➕ إضافة أصل جديد</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">بحث فوري في الأصول</h3>
                <p class="text-gray-500 text-sm">ابحث بالوسم أو حالة الاعتماد أو نوع الأصل.</p>
            </div>
            <input type="text" id="searchBox" placeholder="🔍 ابحث عن أصل بالاسم أو النوع..." class="w-full sm:w-80 border border-orange-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
        </div>
        <div id="searchResults" class="mt-6 divide-y divide-gray-100"></div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">قائمة الأصول</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-right">الوسم</th>
                        <th class="px-4 py-3 text-right">الحالة</th>
                        <th class="px-4 py-3 text-right">الحملة</th>
                        <th class="px-4 py-3 text-right">المؤسسة</th>
                        <th class="px-4 py-3 text-right">تاريخ الإنشاء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($assets as $asset)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-orange-700">{{ $asset->variation_tag ?? 'أصل بدون وسم' }}</td>
                            <td class="px-4 py-2">{{ $asset->status ?? 'غير محدد' }}</td>
                            <td class="px-4 py-2">{{ optional($asset->campaign)->name ?? 'غير مرتبط' }}</td>
                            <td class="px-4 py-2">{{ optional($asset->org)->name ?? 'غير محدد' }}</td>
                            <td class="px-4 py-2">{{ optional($asset->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">لا توجد أصول مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const searchableAssets = @json($searchableAssets);
    const resultsBox = document.getElementById('searchResults');

    function renderResults(items) {
        resultsBox.innerHTML = '';

        if (!items.length) {
            resultsBox.innerHTML = '<p class="py-4 text-gray-500">لم يتم العثور على نتائج.</p>';
            return;
        }

        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'py-3 flex justify-between items-center';
            const typeLabel = item.type ? `<span class="text-xs text-orange-500 mr-2">${item.type}</span>` : '';
            row.innerHTML = `<span class="font-medium text-gray-800">${item.name}</span><span class="text-sm text-orange-600">${item.status}${typeLabel ? ' — ' + typeLabel : ''}</span>`;
            resultsBox.appendChild(row);
        });
    }

    renderResults(searchableAssets.slice(0, 15));

    document.getElementById('searchBox').addEventListener('input', (event) => {
        const query = event.target.value.trim().toLowerCase();
        const filtered = searchableAssets.filter(item =>
            item.name.toLowerCase().includes(query) ||
            item.status.toLowerCase().includes(query) ||
            (item.type ?? '').toLowerCase().includes(query)
        );
        renderResults(filtered.slice(0, 30));
    });
</script>
@endsection
