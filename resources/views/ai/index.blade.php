@extends('layouts.app')

@section('content')
<h2>🤖 لوحة الذكاء الاصطناعي (AI Dashboard)</h2>
<p>تعرض هذه الصفحة أداء الأنظمة الذكية والتوصيات والنماذج المستخدمة في الحملات.</p>

<!-- الشريط الفرعي -->
<div style="margin:15px 0; padding:10px; background:#10b98110; border:1px solid #10b981; border-radius:8px;">
  <a href="/ai/campaigns" style="margin:0 10px; color:#10b981; font-weight:bold; text-decoration:none;">🎯 الحملات الذكية</a>
  <a href="/ai/recommendations" style="margin:0 10px; color:#10b981; font-weight:bold; text-decoration:none;">💡 التوصيات الذكية</a>
  <a href="/ai/models" style="margin:0 10px; color:#10b981; font-weight:bold; text-decoration:none;">🧠 النماذج</a>
</div>

<hr>

<!-- حقل البحث الفوري -->
<div style="margin:15px 0;">
  <input type="text" id="searchBox" placeholder="🔍 ابحث عن حملة أو توصية أو نموذج..." style="width:100%; max-width:400px; padding:10px; border:1px solid #10b981; border-radius:6px;">
</div>

<div id="aiStats" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;"></div>
<div id="searchResults" style="margin-top:30px;"></div>

<script>
let allAI = [];

async function loadAIStats() {
  try {
    const res = await fetch('/dashboard/data');
    const data = await res.json();
    const stats = data.ai;

    const container = document.getElementById('aiStats');
    container.innerHTML = '';

    const color = '#10b981';

    const cards = [
      { label: 'الحملات الذكية', value: stats.ai_campaigns },
      { label: 'التوصيات الذكية', value: stats.recommendations },
      { label: 'نماذج الذكاء الاصطناعي', value: stats.models }
    ];

    cards.forEach(c => {
      const card = document.createElement('div');
      card.style.cssText = `background:${color}20; border:1px solid ${color}; border-radius:10px; width:220px; text-align:center; padding:15px; box-shadow:0 2px 6px rgba(0,0,0,0.1);`;
      card.innerHTML = `<h3 style='color:${color}; margin:0;'>${c.label}</h3><p style='font-size:22px; font-weight:bold;'>${c.value}</p>`;
      container.appendChild(card);
    });

    // بيانات تجريبية للبحث
    allAI = [
      { name: 'حملة إعلانية ذكية - رمضان', type: 'حملة ذكية' },
      { name: 'نموذج توليد نصوص GPT', type: 'نموذج' },
      { name: 'توصية محتوى تلقائية', type: 'توصية' },
      { name: 'تحسين إعلان عبر الذكاء الاصطناعي', type: 'حملة ذكية' }
    ];

    renderResults(allAI);
  } catch (err) {
    console.error('فشل تحميل بيانات الذكاء الاصطناعي', err);
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
    div.innerHTML = `<strong>${item.name}</strong> <span style='color:#10b981;'>(${item.type})</span>`;
    box.appendChild(div);
  });
}

document.getElementById('searchBox').addEventListener('input', (e) => {
  const query = e.target.value.toLowerCase();
  const filtered = allAI.filter(o => o.name.toLowerCase().includes(query));
  renderResults(filtered);
});

loadAIStats();
setInterval(loadAIStats, 30000);
</script>

<hr>

<h3>🧠 التطورات الحديثة</h3>
<p>سيتم قريبًا عرض تحليلات أعمق لأداء الذكاء الاصطناعي والتوصيات الناتجة.</p>
@endsection