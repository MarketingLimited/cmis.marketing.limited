@extends('layouts.app')

@section('content')
<h2>تفاصيل الحملة</h2>

@if(isset($campaign))
  <h3>{{ $campaign->name }}</h3>
  <p><strong>الهدف:</strong> {{ $campaign->objective ?? 'غير محدد' }}</p>
  <p><strong>الحالة:</strong> {{ $campaign->status ?? 'غير معروفة' }}</p>
  <p><strong>المدة:</strong> من {{ $campaign->start_date ?? '-' }} إلى {{ $campaign->end_date ?? '-' }}</p>
  <p><strong>الميزانية:</strong> {{ $campaign->budget ? number_format($campaign->budget, 2) : '-' }} {{ $campaign->currency ?? '' }}</p>
  <p><strong>تاريخ الإنشاء:</strong> {{ $campaign->created_at }}</p>
  <p><strong>آخر تحديث:</strong> {{ $campaign->updated_at }}</p>

  <hr>

  <h3>🎯 المنتجات والخدمات المرتبطة</h3>
  @if(!empty($offerings))
    <ul>
      @foreach($offerings as $o)
        <li>{{ $o->name }} ({{ $o->kind }})</li>
      @endforeach
    </ul>
  @else
    <p>لا توجد منتجات أو خدمات مرتبطة بهذه الحملة.</p>
  @endif

  <hr>

  <h3>📈 مؤشرات الأداء</h3>

  <label for="timeRange">عرض الأداء حسب:</label>
  <select id="timeRange">
      <option value="daily">يومي</option>
      <option value="weekly">أسبوعي</option>
      <option value="monthly" selected>شهري</option>
      <option value="yearly">سنوي</option>
  </select>

  <canvas id="performanceChart" width="600" height="300"></canvas>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const ctx = document.getElementById('performanceChart').getContext('2d');
    let chart;

    function renderChart(labels, values) {
        if(chart) chart.destroy();
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'قيم الأداء',
                    data: values,
                    borderWidth: 1,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)'
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
        const res = await fetch(`/campaigns/{{ $campaign->campaign_id }}/performance/${range}`);
        const data = await res.json();
        const labels = data.map(d => d.kpi_name);
        const values = data.map(d => d.value);
        renderChart(labels, values);
    }

    document.getElementById('timeRange').addEventListener('change', (e) => {
        fetchPerformance(e.target.value);
    });

    // تحميل أولي
    fetchPerformance('monthly');
  </script>
@endif
@endsection