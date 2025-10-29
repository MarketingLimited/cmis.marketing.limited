@extends('layouts.app')

@section('content')
<h2>🛍️ إدارة العروض (Offerings)</h2>
<p>هنا يمكنك استعراض المنتجات والخدمات والباقات ضمن نظام CMIS.</p>

<!-- الشريط الفرعي -->
<div style="margin:15px 0; padding:10px; background:#38bdf810; border:1px solid #38bdf8; border-radius:8px;">
  <a href="/products" style="margin:0 10px; color:#38bdf8; font-weight:bold; text-decoration:none;">📦 المنتجات</a>
  <a href="/services" style="margin:0 10px; color:#38bdf8; font-weight:bold; text-decoration:none;">🧰 الخدمات</a>
  <a href="/bundles" style="margin:0 10px; color:#38bdf8; font-weight:bold; text-decoration:none;">🎁 الباقات</a>
</div>

<hr>

<!-- حقل البحث الفوري -->
<div style="margin:15px 0;">
  <input type="text" id="searchBox" placeholder="🔍 ابحث عن عرض..." style="width:100%; max-width:400px; padding:10px; border:1px solid #38bdf8; border-radius:6px;">
</div>

<div id="offeringsStats" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;"></div>
<div id="searchResults" style="margin-top:30px;"></div>

<script>
let allOfferings = [];

async function loadOfferingsStats() {
  try {
    const res = await fetch('/dashboard/data');
    const data = await res.json();
    const stats = data.offerings;

    const container = document.getElementById('offeringsStats');
    container.innerHTML = '';

    const color = '#38bdf8'; // اللون المميز للعروض

    const cards = [
      { label: 'المنتجات', value: stats.products },
      { label: 'الخدمات', value: stats.services },
      { label: 'الباقات', value: stats.bundles }
    ];

    cards.forEach(c => {
      const card = document.createElement('div');
      card.style.cssText = `background:${color}20; border:1px solid ${color}; border-radius:10px; width:220px; text-align:center; padding:15px; box-shadow:0 2px 6px rgba(0,0,0,0.1);`;
      card.innerHTML = `<h3 style='color:${color}; margin:0;'>${c.label}</h3><p style='font-size:22px; font-weight:bold;'>${c.value}</p>`;
      container.appendChild(card);
    });

    // حفظ بيانات العروض للاستخدام في البحث (محاكاة)
    allOfferings = [
      { name: 'خدمة تصميم شعار', type: 'service' },
      { name: 'حزمة ترويج على إنستغرام', type: 'bundle' },
      { name: 'منتج تحليل بيانات', type: 'product' },
      { name: 'خدمة تصوير فيديو', type: 'service' }
    ];

    renderResults(allOfferings);
  } catch (err) {
    console.error('فشل تحميل بيانات العروض', err);
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
    div.innerHTML = `<strong>${item.name}</strong> <span style='color:#38bdf8;'>(${item.type})</span>`;
    box.appendChild(div);
  });
}

document.getElementById('searchBox').addEventListener('input', (e) => {
  const query = e.target.value.toLowerCase();
  const filtered = allOfferings.filter(o => o.name.toLowerCase().includes(query));
  renderResults(filtered);
});

loadOfferingsStats();
setInterval(loadOfferingsStats, 30000);
</script>

<hr>

<h3>📋 تصفح العروض</h3>
<p>يمكنك اختيار نوع العرض لعرض تفاصيله:</p>
<ul>
  <li><a href="/products">المنتجات</a></li>
  <li><a href="/services">الخدمات</a></li>
  <li><a href="/bundles">الباقات</a></li>
</ul>
@endsection