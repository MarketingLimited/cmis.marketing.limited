@extends('layouts.app')

@section('content')

<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">تفاصيل الحملة</h2>
        <p class="text-gray-600">عرض شامل للحملة المختارة بما في ذلك العروض المرتبطة وأداء المؤشرات.</p>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
            <div><span class="font-semibold">اسم الحملة:</span> {{ $campaign->name }}</div>
            <div><span class="font-semibold">الهدف:</span> {{ $campaign->objective ?? 'غير محدد' }}</div>
            <div><span class="font-semibold">الحالة:</span> {{ $campaign->status ?? 'غير معروفة' }}</div>
            <div><span class="font-semibold">الميزانية:</span> {{ $campaign->budget ? number_format($campaign->budget, 2) : 'غير محدد' }} {{ $campaign->currency ?? '' }}</div>
            <div><span class="font-semibold">تاريخ البدء:</span> {{ optional($campaign->start_date)->format('Y-m-d') ?? '—' }}</div>
            <div><span class="font-semibold">تاريخ الانتهاء:</span> {{ optional($campaign->end_date)->format('Y-m-d') ?? '—' }}</div>
            <div><span class="font-semibold">تاريخ الإنشاء:</span> {{ optional($campaign->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
            <div><span class="font-semibold">آخر تحديث:</span> {{ optional($campaign->updated_at)->diffForHumans() ?? '—' }}</div>
        </div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-indigo-700 mb-4">🎯 العروض المرتبطة</h3>
        @if($offerings->isNotEmpty())
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($offerings as $offering)
                    <li class="border border-indigo-100 rounded-lg px-4 py-3 text-sm text-gray-700">
                        <span class="font-semibold text-indigo-700">{{ $offering->name }}</span>
                        <span class="text-gray-500">({{ $offering->kind }})</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-gray-500">لا توجد منتجات أو خدمات مرتبطة بهذه الحملة.</p>
        @endif
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h3 class="text-xl font-semibold text-gray-800">📈 مؤشرات الأداء</h3>
            <label class="text-sm text-gray-600">عرض الأداء حسب
                <select id="timeRange" class="ml-2 border border-indigo-200 rounded px-2 py-1 text-sm">
                    <option value="daily">يومي</option>
                    <option value="weekly">أسبوعي</option>
                    <option value="monthly" selected>شهري</option>
                    <option value="yearly">سنوي</option>
                </select>
            </label>
        </div>
        <canvas id="performanceChart" height="320"></canvas>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-right">المؤشر</th>
                        <th class="px-4 py-3 text-right">القيمة الحالية</th>
                        <th class="px-4 py-3 text-right">المستهدف</th>
                        <th class="px-4 py-3 text-right">الانحراف</th>
                        <th class="px-4 py-3 text-right">الثقة</th>
                        <th class="px-4 py-3 text-right">آخر تحديث</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($performance as $metric)
                        <tr>
                            <td class="px-4 py-2 font-medium text-indigo-700">{{ $metric['metric_name'] }}</td>
                            <td class="px-4 py-2">{{ number_format($metric['metric_value'] ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ number_format($metric['metric_target'] ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ number_format($metric['variance'] ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ number_format($metric['confidence_level'] ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ optional($metric['collected_at'])->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500">لا توجد بيانات أداء حديثة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('performanceChart').getContext('2d');
    let chart;

    function renderChart(labels, values) {
        if (chart) chart.destroy();
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'متوسط القيمة خلال المدة',
                    data: values,
                    backgroundColor: 'rgba(99, 102, 241, 0.4)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    async function fetchPerformance(range = 'monthly') {
        const response = await fetch(`/campaigns/{{ $campaign->campaign_id }}/performance/${range}`);
        const data = await response.json();
        const labels = data.map(item => item.metric_name);
        const values = data.map(item => item.value);
        renderChart(labels, values);
    }

    document.getElementById('timeRange').addEventListener('change', (event) => {
        fetchPerformance(event.target.value);
    });

    fetchPerformance('monthly');
</script>
@endsection
