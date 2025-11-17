@extends('layouts.admin')

@section('content')
<h2>🧩 تفاصيل التكامل (Integration Details)</h2>
<p>في هذه الصفحة يمكنك الاطلاع على تفاصيل التكامل وإدارة مفاتيح الاتصال ومراجعة سجل الأنشطة.</p>

<!-- القسم الأساسي -->
<div style="margin:20px 0; padding:20px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px;">
  <h3 style="color:#1e293b;">📘 معلومات التكامل</h3>
  <table style="width:100%; border-collapse:collapse; margin-top:10px;">
    <tr><td style="padding:8px; font-weight:bold;">الاسم:</td><td id="intName">Meta Business</td></tr>
    <tr><td style="padding:8px; font-weight:bold;">النوع:</td><td id="intType">منصة إعلانات</td></tr>
    <tr><td style="padding:8px; font-weight:bold;">الحالة:</td><td id="intStatus">نشط ✅</td></tr>
    <tr><td style="padding:8px; font-weight:bold;">تاريخ الإنشاء:</td><td id="intCreated">2025-09-20</td></tr>
    <tr><td style="padding:8px; font-weight:bold;">آخر تحديث:</td><td id="intUpdated">2025-10-25</td></tr>
  </table>
</div>

<!-- مفاتيح الاتصال -->
<div style="margin:20px 0; padding:20px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:10px;">
  <h3 style="color:#1e293b;">🔐 مفاتيح الاتصال</h3>
  <p>يمكنك عرض أو إخفاء المفاتيح الحساسة.</p>
  <div style="margin-top:10px;">
    <div style="margin-bottom:8px;">
      <strong>App ID:</strong> <span id="appId">•••••••••••••••</span>
    </div>
    <div style="margin-bottom:8px;">
      <strong>App Secret:</strong> <span id="appSecret">•••••••••••••••</span>
    </div>
    <div style="margin-bottom:8px;">
      <strong>Access Token:</strong> <span id="accessToken">•••••••••••••••</span>
    </div>
    <button id="toggleKeys" style="margin-top:10px; background:#1e293b; color:white; padding:8px 14px; border:none; border-radius:6px; cursor:pointer;">👁️ إظهار المفاتيح</button>
  </div>
</div>

<!-- سجل الأنشطة -->
<div style="margin:20px 0; padding:20px; background:#ffffff; border:1px solid #cbd5e1; border-radius:10px;">
  <h3 style="color:#1e293b;">🕓 سجل الأنشطة</h3>
  <table style="width:100%; border-collapse:collapse; margin-top:10px;">
    <thead>
      <tr style="background:#e2e8f0;">
        <th style="padding:8px; text-align:right;">التاريخ</th>
        <th style="padding:8px; text-align:right;">العملية</th>
        <th style="padding:8px; text-align:right;">النتيجة</th>
      </tr>
    </thead>
    <tbody id="activityLog">
      <tr><td style="padding:8px;">2025-10-25</td><td>تحديث الحملة الإعلانية</td><td>✅ نجاح</td></tr>
      <tr><td style="padding:8px;">2025-10-24</td><td>تجديد رمز الوصول</td><td>✅ نجاح</td></tr>
      <tr><td style="padding:8px;">2025-10-23</td><td>استيراد بيانات من Meta</td><td>⚠️ تحذير (تأخر في الاستجابة)</td></tr>
    </tbody>
  </table>
</div>

<!-- أزرار التحكم -->
<div style="display:flex; gap:10px; margin-top:20px;">
  <a href="/integrations" style="background:#475569; color:white; padding:10px 15px; border-radius:6px; text-decoration:none;">🔙 العودة</a>
  <button style="background:#10b981; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer;">🔄 تحديث الاتصال</button>
  <button style="background:#ef4444; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer;">❌ حذف التكامل</button>
</div>

<script>
  const toggleBtn = document.getElementById('toggleKeys');
  const appId = document.getElementById('appId');
  const appSecret = document.getElementById('appSecret');
  const accessToken = document.getElementById('accessToken');
  let visible = false;

  toggleBtn.addEventListener('click', () => {
    visible = !visible;
    if (visible) {
      appId.textContent = '1234567890';
      appSecret.textContent = 'ABCD-EFGH-IJKL-MNOP';
      accessToken.textContent = 'EAAG8gZ1234TOKEN';
      toggleBtn.textContent = '🙈 إخفاء المفاتيح';
    } else {
      appId.textContent = appSecret.textContent = accessToken.textContent = '•••••••••••••••';
      toggleBtn.textContent = '👁️ إظهار المفاتيح';
    }
  });
</script>
@endsection