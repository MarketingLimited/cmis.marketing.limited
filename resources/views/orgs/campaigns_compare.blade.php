@extends('layouts.admin')

@section('content')
<h2>📊 مقارنة الحملات</h2>

@if(!empty($campaigns))
  <div style="margin-bottom: 1em;">
    <label for="kpiSelector">اختر المؤشر لعرضه:</label>
    <select id="kpiSelector">
      @foreach($kpiLabels as $kpi)
        <option value="{{ $kpi }}">{{ $kpi }}</option>
      @endforeach
    </select>
  </div>

  <div style="margin-bottom: 1em;">
    <form id="exportPdfForm" method="POST" action="{{ url('orgs/' . $org_id . '/campaigns/export/pdf') }}" style="display:inline">
      @csrf
      <input type="hidden" name="campaigns" id="pdfCampaigns">
      <input type="hidden" name="kpiLabels" id="pdfKpis">
      <input type="hidden" name="datasets" id="pdfDatasets">
      <button type="submit">📄 تصدير PDF</button>
    </form>

    <form id="exportExcelForm" method="POST" action="{{ url('orgs/' . $org_id . '/campaigns/export/excel') }}" style="display:inline">
      @csrf
      <input type="hidden" name="campaigns" id="excelCampaigns">
      <input type="hidden" name="kpiLabels" id="excelKpis">
      <input type="hidden" name="datasets" id="excelDatasets">
      <button type="submit">📊 تصدير Excel</button>
    </form>
  </div>

  <canvas id="compareChart" width="900" height="450"></canvas>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const ctx = document.getElementById('compareChart').getContext('2d');

    const kpiData = @json($datasets);
    const kpis = @json($kpiLabels);

    const colors = [
      'rgba(75, 192, 192, 0.7)',
      'rgba(255, 99, 132, 0.7)',
      'rgba(255, 206, 86, 0.7)',
      'rgba(54, 162, 235, 0.7)',
      'rgba(153, 102, 255, 0.7)'
    ];

    function buildChart(selectedKpi) {
      const labels = kpiData.map(d => d.label);
      const values = kpiData.map(d => {
        const idx = kpis.indexOf(selectedKpi);
        return d.data[idx] ?? 0;
      });

      if(window.compareChart) window.compareChart.destroy();

      window.compareChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: selectedKpi,
            data: values,
            backgroundColor: colors.slice(0, labels.length),
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true } },
          plugins: {
            legend: { display: false },
            title: { display: true, text: `مقارنة الحملات حسب ${selectedKpi}` }
          }
        }
      });
    }

    document.getElementById('kpiSelector').addEventListener('change', (e) => buildChart(e.target.value));

    // تحميل أول رسم بياني افتراضي
    buildChart(kpis[0]);

    // إعداد بيانات التصدير
    const jsonData = {
      campaigns: @json($campaigns),
      kpiLabels: @json($kpiLabels),
      datasets: @json($datasets)
    };

    document.getElementById('pdfCampaigns').value = JSON.stringify(jsonData.campaigns);
    document.getElementById('pdfKpis').value = JSON.stringify(jsonData.kpiLabels);
    document.getElementById('pdfDatasets').value = JSON.stringify(jsonData.datasets);

    document.getElementById('excelCampaigns').value = JSON.stringify(jsonData.campaigns);
    document.getElementById('excelKpis').value = JSON.stringify(jsonData.kpiLabels);
    document.getElementById('excelDatasets').value = JSON.stringify(jsonData.datasets);
  </script>
@else
  <p>لم يتم العثور على بيانات للمقارنة.</p>
@endif

<div style="margin-top: 1em;">
  <a href="{{ url('orgs/' . $org_id . '/campaigns') }}" class="button">⬅ العودة إلى قائمة الحملات</a>
</div>
@endsection