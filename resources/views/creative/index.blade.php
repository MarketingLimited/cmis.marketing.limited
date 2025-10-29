@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">🎨 لوحة الإبداع (Creative)</h2>
        <p class="text-gray-600">بيانات فورية عن الأصول الإبداعية وحالتها التشغيلية.</p>
    </div>

    <div class="flex flex-wrap gap-4">
        <a href="/creative-assets" class="inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-4 py-2 rounded-lg font-semibold hover:bg-orange-200 transition">🖼️ الأصول الإبداعية</a>
        <a href="/ads" class="inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-4 py-2 rounded-lg font-semibold hover:bg-orange-200 transition">📢 الإعلانات</a>
        <a href="/templates" class="inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-4 py-2 rounded-lg font-semibold hover:bg-orange-200 transition">📐 القوالب</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-orange-700 mb-4">ملخص الأصول</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="border border-orange-200 rounded-xl p-4 text-center bg-orange-50">
                <p class="text-orange-600 font-semibold">إجمالي الأصول</p>
                <p class="text-2xl font-bold">{{ $stats['assets'] }}</p>
            </div>
            <div class="border border-orange-200 rounded-xl p-4 text-center bg-orange-50">
                <p class="text-orange-600 font-semibold">المعتمدة</p>
                <p class="text-2xl font-bold">{{ $stats['approved'] }}</p>
            </div>
            <div class="border border-orange-200 rounded-xl p-4 text-center bg-orange-50">
                <p class="text-orange-600 font-semibold">بانتظار المراجعة</p>
                <p class="text-2xl font-bold">{{ $stats['pending'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">بحث في الأصول الإبداعية</h3>
                <p class="text-gray-500 text-sm">ابحث بالوسم أو الحالة.</p>
            </div>
            <input type="text" id="searchBox" placeholder="🔍 ابحث عن أصل إبداعي..." class="w-full sm:w-80 border border-orange-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
        </div>
        <div id="searchResults" class="mt-6 divide-y divide-gray-100"></div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">أحدث الأصول المسجلة</h3>
        <ul class="space-y-3">
            @forelse ($recentAssets as $asset)
                <li class="border border-gray-100 rounded-lg p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="font-semibold text-orange-700">{{ $asset->variation_tag ?? 'أصل بدون وسم' }}</p>
                            <p class="text-sm text-gray-500">الحملة: {{ optional($asset->campaign)->name ?? 'غير مرتبط' }} — المؤسسة: {{ optional($asset->org)->name ?? 'غير محدد' }}</p>
                        </div>
                        <span class="text-sm text-gray-400">{{ optional($asset->created_at)->diffForHumans() ?? 'غير متوفر' }}</span>
                    </div>
                    <p class="text-sm mt-2 text-gray-600">الحالة الحالية: <span class="font-semibold">{{ $asset->status ?? 'غير محدد' }}</span></p>
                </li>
            @empty
                <li class="text-gray-500">لا توجد أصول حديثة.</li>
            @endforelse
        </ul>
    </div>
</div>

<script>
    const searchableAssets = @json($searchableAssets->map(fn($asset) => [
        'variation_tag' => $asset->variation_tag ?? 'بدون وسم',
        'status' => $asset->status ?? 'غير محدد',
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
            row.innerHTML = `<span class="font-medium text-gray-800">${item.variation_tag}</span><span class="text-sm text-orange-600">${item.status}</span>`;
            resultsBox.appendChild(row);
        });
    }

    renderResults(searchableAssets.slice(0, 10));

    document.getElementById('searchBox').addEventListener('input', (event) => {
        const query = event.target.value.trim().toLowerCase();
        const filtered = searchableAssets.filter(item =>
            item.variation_tag.toLowerCase().includes(query) || item.status.toLowerCase().includes(query)
        );
        renderResults(filtered.slice(0, 25));
    });
</script>
@endsection
