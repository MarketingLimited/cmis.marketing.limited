@extends('layouts.app')

@section('content')
<h2>🖼️ إدارة الأصول الإبداعية (Creative Assets)</h2>
<p>هنا يمكنك استعراض جميع الأصول الإبداعية، البحث عنها، وإدارتها مباشرة.</p>

<!-- الشريط الفرعي -->
<div style="margin:15px 0; padding:10px; background:#f9731610; border:1px solid #f97316; border-radius:8px;">
  <a href="/creative" style="margin:0 10px; color:#f97316; font-weight:bold; text-decoration:none;">🎨 العودة للوحة الإبداع</a>
  <a href="/creative-assets/create" style="margin:0 10px; color:#f97316; font-weight:bold; text-decoration:none;">➕ إضافة أصل جديد</a>
</div>

<hr>

<!-- حقل البحث الفوري -->
<div style="margin:15px 0;">
  <input type="text" id="searchBox" placeholder="🔍 ابحث عن أصل بالاسم أو النوع..." style="width:100%; max-width:400px; padding:10px; border:1px solid #f97316; border-radius:6px;">
</div>

<div id="searchResults" style="margin-top:20px;"></div>

<script>
let allAssets = [];

async function loadAssets() {
  try {
    // في المرحلة الحالية نستخدم بيانات تجريبية (سيتم استبدالها لاحقاً ببيانات فعلية من قاعدة البيانات)
    allAssets = [
      { name: 'تصميم شعار CMIS', type: 'صورة', date: '2025-10-01' },
      { name: 'فيديو إعلان حملة الشتاء', type: 'فيديو', date: '2025-09-15' },
      { name: 'قالب منشور إنستغرام', type: 'قالب', date: '2025-10-10' },
      { name: 'مخطط عرض تقديمي', type: 'قالب', date: '2025-09-28' },
      { name: 'صورة ترويجية - منتج جديد', type: 'صورة', date: '2025-10-20' }
    ];

    renderResults(allAssets);
  } catch (err) {
    console.error('فشل تحميل بيانات الأصول', err);
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
    div.style.cssText = 'padding:12px; border-bottom:1px solid #ddd; background:#fff; border-radius:6px; margin-bottom:6px;';
    div.innerHTML = `<strong>${item.name}</strong> <span style='color:#f97316;'>(${item.type})</span><br><small style='color:#777;'>${item.date}</small>`;
    box.appendChild(div);
  });
}

document.getElementById('searchBox').addEventListener('input', (e) => {
  const query = e.target.value.toLowerCase();
  const filtered = allAssets.filter(o => o.name.toLowerCase().includes(query) || o.type.toLowerCase().includes(query));
  renderResults(filtered);
});

loadAssets();
</script>
@endsection