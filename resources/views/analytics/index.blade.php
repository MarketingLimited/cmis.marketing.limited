@extends('layouts.app')

@section('content')
<h2>📈 لوحة التحليلات (Analytics)</h2>
<p>تعرض هذه الصفحة مؤشرات الأداء والبيانات الإحصائية المسجلة في النظام.</p>

<!-- الشريط الفرعي -->
<div style="margin:15px 0; padding:10px; background:#8b5cf610; border:1px solid #8b5cf6; border-radius:8px;">
  <a href="/kpis" style="margin:0 10px; color:#8b5cf6; font-weight:bold; text-decoration:none;">🎯 مؤشرات الأداء</a>
  <a href="/reports" style="margin:0 10px; color:#8b5cf6; font-weight:bold; text-decoration:none;">📊 التقارير</a>
  <a href="/metrics" style="margin:0 10px; color:#8b5cf6; font-weight:bold; text-decoration:none;">📈 المقاييس</a>
</div>

<hr>

<!-- حقل البحث الفوري -->
<div style="margin:15px 0;">
  <input type="text" id="searchBox" placeholder="🔍 ابحث عن مؤشر أو تقرير..." style="width:100%; max-width:400px; padding:10px; border:1px solid #8b5cf6; border-radius:6px;">
</div>

<div id="analyticsStats" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;"></div>
<div id="searchResults" style="margin-top:30px;"></div>

<script>
let allAnalytics = [];

async function loadAnalyticsStats() {
  try {
    const res = await fetch('/dashboard/data');
    const data = await res.json();
    const stats = data.analytics;

    const container = document.getElementById('analyticsStats');
    container.innerHTML = '';

    const color = '#8b5cf6';

    const cards = [
      { label: 'مؤشرات الأداء (KPIs)', value: stats.kpis },
      { label: 'القياسات المسجلة (Metrics)', value: stats.metrics }
    ];

    cards.forEach(c => {
      const card = document.createElement('div');
      card.style.cssText = `background:${color}20; border:1px solid ${color}; border-radius:10px; width:250px; text-align:center; padding:15px; box-shadow:0 2px 6px rgba(0,0,0,0.1);`;
      card.innerHTML = `<h3 style='color:${color}; margin:0;'>${c.label}</h3><p style='font-size:22px; font-weight:bold;'>${c.value}</p>`;
      container.appendChild(card);
    });

    // بيانات تجريبية للبحث
    allAnalytics = [
      { name: 'مؤشر رضا العملاء', type: 'KPI' },
      { name: 'تقرير المبيعات الشهري', type: 'Report' },
      { name: 'قياس التفاعل على وسائل التواصل', type: 'Metric' },
      { name: 'مؤشر النمو السنوي', type: 'KPI' }
    ];

    renderResults(allAnalytics);
  } catch (err) {
    console.error('فشل تحميل بيانات التحليلات', err);
  }
}

function renderResults(results) {
  const box = document.getElementById('searchResults');
  box.innerHTML = '';

  if (results.length === 0) {
    box.innerHTML = '<p style="color:#555;">لم يتم العثور على نتائج.</p>';
    return;
  }

  results.forEach(item => {
    const div = document.createElement('div');
    div.style.cssText = 'padding:10px; border-bottom:1px solid #ddd;';
    div.innerHTML = `<strong>${item.name}</strong> <span style='color:#8b5cf6;'>(${item.type})</span>`;
    box.appendChild(div);
  });
}

document.getElementById('searchBox').addEventListener('input', (e) => {
  const query = e.target.value.toLowerCase();
  const filtered = allAnalytics.filter(o => o.name.toLowerCase().includes(query));
  renderResults(filtered);
});

loadAnalyticsStats();
setInterval(loadAnalyticsStats, 30000);
</script>

<hr>

<h3>📊 التحليل المفصل</h3>
<p>قريبًا سيتم إضافة رسوم بيانية ديناميكية لمؤشرات الأداء.</p>
@endsection