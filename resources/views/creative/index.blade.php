@extends('layouts.app')

@section('content')
<h2>🎨 لوحة الإبداع (Creative)</h2>
<p>تعرض هذه الصفحة أداء وأعداد الأصول الإبداعية داخل النظام.</p>

<!-- الشريط الفرعي -->
<div style="margin:15px 0; padding:10px; background:#f9731610; border:1px solid #f97316; border-radius:8px;">
  <a href="/creative-assets" style="margin:0 10px; color:#f97316; font-weight:bold; text-decoration:none;">🖼️ الأصول الإبداعية</a>
  <a href="/ads" style="margin:0 10px; color:#f97316; font-weight:bold; text-decoration:none;">📢 الإعلانات</a>
  <a href="/templates" style="margin:0 10px; color:#f97316; font-weight:bold; text-decoration:none;">📐 القوالب</a>
</div>

<hr>

<!-- حقل البحث الفوري -->
<div style="margin:15px 0;">
  <input type="text" id="searchBox" placeholder="🔍 ابحث عن أصل إبداعي..." style="width:100%; max-width:400px; padding:10px; border:1px solid #f97316; border-radius:6px;">
</div>

<div id="creativeStats" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;"></div>
<div id="searchResults" style="margin-top:30px;"></div>

<script>
let allCreative = [];

async function loadCreativeStats() {
  try {
    const res = await fetch('/dashboard/data');
    const data = await res.json();
    const stats = data.creative;

    const container = document.getElementById('creativeStats');
    container.innerHTML = '';

    const color = '#f97316';

    const cards = [
      { label: 'إجمالي الأصول الإبداعية', value: stats.assets },
      { label: 'الصور', value: stats.images },
      { label: 'الفيديوهات', value: stats.videos }
    ];

    cards.forEach(c => {
      const card = document.createElement('div');
      card.style.cssText = `background:${color}20; border:1px solid ${color}; border-radius:10px; width:220px; text-align:center; padding:15px; box-shadow:0 2px 6px rgba(0,0,0,0.1);`;
      card.innerHTML = `<h3 style='color:${color}; margin:0;'>${c.label}</h3><p style='font-size:22px; font-weight:bold;'>${c.value}</p>`;
      container.appendChild(card);
    });

    // بيانات تجريبية للبحث
    allCreative = [
      { name: 'تصميم شعار CMIS', type: 'صورة' },
      { name: 'إعلان حملة الربيع', type: 'فيديو' },
      { name: 'قالب منشور إنستغرام', type: 'قالب' },
      { name: 'تصميم واجهة تطبيق', type: 'صورة' }
    ];

    renderResults(allCreative);
  } catch (err) {
    console.error('فشل تحميل بيانات الإبداع', err);
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
    div.innerHTML = `<strong>${item.name}</strong> <span style='color:#f97316;'>(${item.type})</span>`;
    box.appendChild(div);
  });
}

document.getElementById('searchBox').addEventListener('input', (e) => {
  const query = e.target.value.toLowerCase();
  const filtered = allCreative.filter(o => o.name.toLowerCase().includes(query));
  renderResults(filtered);
});

loadCreativeStats();
setInterval(loadCreativeStats, 30000);
</script>

<hr>

<h3>🖼️ عرض الأصول</h3>
<p>قريبًا سيتم عرض معاينات مباشرة للأصول الإبداعية الحديثة.</p>
@endsection