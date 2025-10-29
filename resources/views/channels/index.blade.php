@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">📡 إدارة القنوات (Channels)</h2>
        <p class="text-gray-600">نظرة شاملة على القنوات والمنصات المرتبطة بالنظام.</p>
    </div>

    <div class="flex flex-wrap gap-4">
        <a href="/channels" class="inline-flex items-center gap-2 bg-slate-100 text-slate-700 px-4 py-2 rounded-lg font-semibold hover:bg-slate-200 transition">📡 جميع القنوات</a>
        <a href="/channels/create" class="inline-flex items-center gap-2 bg-slate-100 text-slate-700 px-4 py-2 rounded-lg font-semibold hover:bg-slate-200 transition">➕ إضافة قناة جديدة</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">بحث فوري في القنوات</h3>
                <p class="text-gray-500 text-sm">ابحث بالاسم أو الكود أو حالة الاتصال.</p>
            </div>
            <input type="text" id="searchBox" placeholder="🔍 ابحث عن قناة بالاسم أو النوع..." class="w-full sm:w-80 border border-slate-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>
        <div id="searchResults" class="mt-6 divide-y divide-gray-100"></div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">القنوات المتاحة</h3>
        <div class="space-y-4">
            @forelse ($channels as $channel)
                <div class="border border-gray-100 rounded-lg p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-800">{{ $channel->name }} <span class="text-sm text-gray-500">({{ $channel->code }})</span></p>
                            <p class="text-sm text-gray-500">الحالة: {{ $channel->constraints['status'] ?? 'غير محدد' }}</p>
                        </div>
                        <span class="text-sm text-gray-400">التنسيقات المدعومة: {{ $channel->formats->count() }}</span>
                    </div>
                    @if($channel->formats->isNotEmpty())
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($channel->formats as $format)
                                <div class="border border-slate-100 rounded-lg px-3 py-2 text-sm text-gray-600">
                                    <strong class="text-slate-700">{{ $format->code }}</strong>
                                    <span class="block text-xs text-gray-500">النسبة: {{ $format->ratio ?? '—' }} — المدة: {{ $format->length_hint ?? '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-gray-500">لا توجد قنوات مسجلة.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    const searchableChannels = @json($searchableChannels);
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
            row.innerHTML = `<span class="font-medium text-gray-800">${item.name}</span><span class="text-sm text-slate-600">${item.code} — ${item.status}</span>`;
            resultsBox.appendChild(row);
        });
    }

    renderResults(searchableChannels.slice(0, 10));

    document.getElementById('searchBox').addEventListener('input', (event) => {
        const query = event.target.value.trim().toLowerCase();
        const filtered = searchableChannels.filter(item =>
            item.name.toLowerCase().includes(query) ||
            item.code.toLowerCase().includes(query) ||
            item.status.toLowerCase().includes(query)
        );
        renderResults(filtered.slice(0, 25));
    });
</script>
@endsection
