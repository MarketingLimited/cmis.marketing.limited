@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">🛍️ إدارة العروض (Offerings)</h2>
        <p class="text-gray-600">استعرض المنتجات والخدمات والباقات مباشرة من قاعدة بيانات CMIS.</p>
    </div>

    <div class="flex flex-wrap gap-4">
        <a href="/products" class="inline-flex items-center gap-2 bg-sky-100 text-sky-700 px-4 py-2 rounded-lg font-semibold hover:bg-sky-200 transition">📦 المنتجات</a>
        <a href="/services" class="inline-flex items-center gap-2 bg-sky-100 text-sky-700 px-4 py-2 rounded-lg font-semibold hover:bg-sky-200 transition">🧰 الخدمات</a>
        <a href="/bundles" class="inline-flex items-center gap-2 bg-sky-100 text-sky-700 px-4 py-2 rounded-lg font-semibold hover:bg-sky-200 transition">🎁 الباقات</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-sky-700 mb-4">المؤشرات الرئيسية</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="border border-sky-200 rounded-xl p-4 text-center bg-sky-50">
                <p class="text-sky-600 font-semibold">المنتجات</p>
                <p class="text-2xl font-bold">{{ $stats['products'] }}</p>
            </div>
            <div class="border border-sky-200 rounded-xl p-4 text-center bg-sky-50">
                <p class="text-sky-600 font-semibold">الخدمات</p>
                <p class="text-2xl font-bold">{{ $stats['services'] }}</p>
            </div>
            <div class="border border-sky-200 rounded-xl p-4 text-center bg-sky-50">
                <p class="text-sky-600 font-semibold">الباقات</p>
                <p class="text-2xl font-bold">{{ $stats['bundles'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">بحث فوري في العروض</h3>
                <p class="text-gray-500 text-sm">اكتب اسم العرض للعثور عليه بسرعة.</p>
            </div>
            <input type="text" id="searchBox" placeholder="🔍 ابحث عن عرض..." class="w-full sm:w-72 border border-sky-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <div id="searchResults" class="mt-6 divide-y divide-gray-100"></div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">أحدث العروض المضافة</h3>
        <ul class="space-y-3">
            @forelse ($recentOfferings as $offering)
                <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between border border-gray-100 rounded-lg p-4">
                    <div>
                        <p class="font-semibold text-indigo-700">{{ $offering->name }}</p>
                        <p class="text-sm text-gray-500">النوع: {{ $offering->kind }} — المؤسسة: {{ optional($offering->org)->name ?? 'غير محدد' }}</p>
                    </div>
                    <span class="text-sm text-gray-400 mt-2 sm:mt-0">{{ optional($offering->created_at)->diffForHumans() ?? 'غير متوفر' }}</span>
                </li>
            @empty
                <li class="text-gray-500">لا توجد عروض حديثة.</li>
            @endforelse
        </ul>
    </div>
</div>

<script>
    const allOfferings = @json($searchableOfferings->map(fn($offering) => [
        'name' => $offering->name,
        'kind' => $offering->kind,
    ]));

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
            row.innerHTML = `<span class="font-medium text-gray-800">${item.name}</span><span class="text-sm text-sky-600">(${item.kind})</span>`;
            resultsBox.appendChild(row);
        });
    }

    renderResults(allOfferings.slice(0, 10));

    document.getElementById('searchBox').addEventListener('input', (event) => {
        const query = event.target.value.trim().toLowerCase();
        const filtered = allOfferings.filter(item => item.name.toLowerCase().includes(query));
        renderResults(filtered.slice(0, 25));
    });
</script>

<div class="mt-12">
    <h3 class="text-lg font-semibold text-gray-800 mb-2">📋 تصفح العروض التفصيلية</h3>
    <p class="text-gray-600 mb-4">استخدم الروابط أعلاه للوصول إلى قوائم تفصيلية لكل نوع.</p>
</div>
@endsection
