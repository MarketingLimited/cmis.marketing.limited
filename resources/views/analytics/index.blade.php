@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">📈 لوحة التحليلات (Analytics)</h2>
        <p class="text-gray-600">بيانات محدثة حول مؤشرات الأداء والقياسات المسجلة للحملات.</p>
    </div>

    <div class="flex flex-wrap gap-4">
        <a href="/kpis" class="inline-flex items-center gap-2 bg-violet-100 text-violet-700 px-4 py-2 rounded-lg font-semibold hover:bg-violet-200 transition">🎯 مؤشرات الأداء</a>
        <a href="/reports" class="inline-flex items-center gap-2 bg-violet-100 text-violet-700 px-4 py-2 rounded-lg font-semibold hover:bg-violet-200 transition">📊 التقارير</a>
        <a href="/metrics" class="inline-flex items-center gap-2 bg-violet-100 text-violet-700 px-4 py-2 rounded-lg font-semibold hover:bg-violet-200 transition">📈 المقاييس</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-violet-700 mb-4">المؤشرات العامة</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="border border-violet-200 rounded-xl p-4 text-center bg-violet-50">
                <p class="text-violet-600 font-semibold">مؤشرات الأداء (KPIs)</p>
                <p class="text-2xl font-bold">{{ $stats['kpis'] }}</p>
            </div>
            <div class="border border-violet-200 rounded-xl p-4 text-center bg-violet-50">
                <p class="text-violet-600 font-semibold">القياسات المسجلة</p>
                <p class="text-2xl font-bold">{{ $stats['metrics'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">بحث فوري في مؤشرات الأداء</h3>
                <p class="text-gray-500 text-sm">اكتب اسم المؤشر أو جزءًا منه للعثور عليه.</p>
            </div>
            <input type="text" id="searchBox" placeholder="🔍 ابحث عن مؤشر أو تقرير..." class="w-full sm:w-80 border border-violet-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-violet-400">
        </div>
        <div id="searchResults" class="mt-6 divide-y divide-gray-100"></div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">أحدث القياسات المسجلة</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-right">المؤشر</th>
                        <th class="px-4 py-3 text-right">القيمة المرصودة</th>
                        <th class="px-4 py-3 text-right">المستهدف</th>
                        <th class="px-4 py-3 text-right">الأساس</th>
                        <th class="px-4 py-3 text-right">وقت الرصد</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($latestMetrics as $metric)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-violet-700">{{ $metric->kpi }}</td>
                            <td class="px-4 py-2">{{ number_format($metric->observed, 2) }}</td>
                            <td class="px-4 py-2">{{ number_format($metric->target ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ number_format($metric->baseline ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ optional($metric->observed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">لا توجد قياسات حديثة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const searchableItems = @json($kpis->map(fn($kpi) => [
        'name' => $kpi->kpi,
        'description' => $kpi->description,
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
            row.className = 'py-3';
            row.innerHTML = `<p class="font-medium text-gray-800">${item.name}</p><p class="text-sm text-gray-500">${item.description ?? ''}</p>`;
            resultsBox.appendChild(row);
        });
    }

    renderResults(searchableItems.slice(0, 10));

    document.getElementById('searchBox').addEventListener('input', (event) => {
        const query = event.target.value.trim().toLowerCase();
        const filtered = searchableItems.filter(item =>
            item.name.toLowerCase().includes(query) || (item.description ?? '').toLowerCase().includes(query)
        );
        renderResults(filtered.slice(0, 25));
    });
</script>
@endsection
