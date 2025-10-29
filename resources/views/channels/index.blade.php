@extends('layouts.app')

@section('content')
<h2>📡 إدارة القنوات (Channels)</h2>
<p>تعرض هذه الصفحة جميع القنوات المتصلة مثل Meta وInstagram وFTP وغيرها، مع إمكانية البحث الفوري.</p>

<!-- الشريط الفرعي -->
<div style="margin:15px 0; padding:10px; background:#1e293b10; border:1px solid #1e293b; border-radius:8px;">
  <a href="/channels" style="margin:0 10px; color:#1e293b; font-weight:bold; text-decoration:none;">📡 جميع القنوات</a>
  <a href="/channels/create" style="margin:0 10px; color:#1e293b; font-weight:bold; text-decoration:none;">➕ إضافة قناة جديدة</a>
</div>

<hr>

<!-- حقل البحث الفوري -->
<div style="margin:15px 0;">
  <input type="text" id="searchBox" placeholder="🔍 ابحث عن قناة بالاسم أو النوع..." style="width:100%; max-width:400px; padding:10px; border:1px solid #1e293b; border-radius:6px;">
</div>

<div id="searchResults" style="margin-top:20px;"></div>

<script>
let allChannels = [];

async function loadChannels() {
  try {
    // بيانات تجريبية (سيتم ربطها لاحقاً بقاعدة البيانات)
    allChannels = [
      { name: 'Meta Ads', type: 'إعلان رقمي', status: 'نشطة' },
      { name: 'Instagram', type: 'وسائل تواصل', status: 'متصلة' },
      { name: 'FTP Server', type: 'تخزين ملفات', status: 'متاحة' },
      { name: 'Google Analytics', type: 'تحليلات', status: 'قيد الاتصال' },
      { name: 'TikTok Ads', type: 'إعلان رقمي', status: 'غير متصلة' }
    ];

    renderResults(allChannels);
  } catch (err) {
    console.error('فشل تحميل بيانات القنوات', err);
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
    div.innerHTML = `<strong>${item.name}</strong> <span style='color:#1e293b;'>(${item.type})</span><br><small style='color:#777;'>الحالة: ${item.status}</small>`;
    box.appendChild(div);
  });
}

document.getElementById('searchBox').addEventListener('input', (e) => {
  const query = e.target.value.toLowerCase();
  const filtered = allChannels.filter(o => o.name.toLowerCase().includes(query) || o.type.toLowerCase().includes(query));
  renderResults(filtered);
});

loadChannels();
</script>
@endsection