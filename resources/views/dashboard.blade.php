@extends('layouts.app')

@section('content')
<h1>📊 لوحة التحكم الرئيسية - CMIS</h1>
<p>مرحبًا بك في نظام إدارة التسويق الذكي (CMIS). هنا يمكنك الوصول إلى جميع أقسام المنصة بسهولة.</p>

<hr>

<h3>📈 مؤشرات سريعة</h3>
<div id="statsSection" style="display:flex; gap:20px; flex-wrap:wrap;"></div>

<hr>

<h3>📊 لوحة الإحصاءات التفاعلية</h3>
<div style="display:flex; gap:40px; flex-wrap:wrap; justify-content:center;">
  <div style="width:400px;">
    <h4 style="text-align:center;">نسبة الحملات حسب الحالة</h4>
    <canvas id="statusChart"></canvas>
  </div>

  <div style="width:500px;">
    <h4 style="text-align:center;">عدد الحملات لكل مؤسسة</h4>
    <canvas id="orgChart"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let statusChart, orgChart;

function renderStats(stats) {
  const section = document.getElementById('statsSection');
  section.innerHTML = '';
  const labels = {
    orgs: '🏢 المؤسسات',
    campaigns: '📊 الحملات',
    offerings: '🛍️ العروض',
    kpis: '🎯 مؤشرات الأداء',
    creative_assets: '🎨 الأصول الإبداعية'
  };

  Object.keys(stats).forEach(key => {
    const box = document.createElement('div');
    box.style.cssText = 'background:#f8f9fa; padding:20px; border:1px solid #ddd; border-radius:8px; width:200px; text-align:center;';
    box.innerHTML = `<h4>${labels[key]}</h4><p><strong>${stats[key]}</strong></p>`;
    section.appendChild(box);
  });
}

function renderCharts(campaignStatus, campaignsByOrg) {
  const statusCtx = document.getElementById('statusChart').getContext('2d');
  const orgCtx = document.getElementById('orgChart').getContext('2d');

  if (statusChart) statusChart.destroy();
  if (orgChart) orgChart.destroy();

  statusChart = new Chart(statusCtx, {
    type: 'pie',
    data: {
      labels: Object.keys(campaignStatus),
      datasets: [{
        data: Object.values(campaignStatus),
        backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF'],
      }]
    }
  });

  orgChart = new Chart(orgCtx, {
    type: 'bar',
    data: {
      labels: campaignsByOrg.map(x => x.org_name),
      datasets: [{
        label: 'عدد الحملات',
        data: campaignsByOrg.map(x => x.total),
        backgroundColor: '#36A2EB'
      }]
    },
    options: { scales: { y: { beginAtZero: true } } }
  });
}

async function fetchDashboardData() {
  const res = await fetch('/dashboard/data');
  const data = await res.json();
  renderStats(data.stats);
  renderCharts(data.campaignStatus, data.campaignsByOrg);
}

// التحديث التلقائي كل 30 ثانية
fetchDashboardData();
setInterval(fetchDashboardData, 30000);
</script>

<hr>

<h3>🚀 الوصول السريع</h3>
<ul>
  <li><a href="/orgs">🏢 إدارة المؤسسات</a></li>
  <li><a href="/campaigns">📊 إدارة الحملات</a></li>
  <li><a href="/offerings">🛍️ المنتجات والخدمات</a></li>
  <li><a href="/analytics">📈 التحليلات</a></li>
  <li><a href="/creative">🎨 الإبداع والمحتوى</a></li>
  <li><a href="/channels">🌐 القنوات والمنصات</a></li>
  <li><a href="/ai">🤖 الذكاء الاصطناعي</a></li>
</ul>
@endsection