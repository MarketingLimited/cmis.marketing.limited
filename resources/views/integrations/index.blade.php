@extends('layouts.app')

@section('content')
<h2>🔗 لوحة التكاملات (Integrations)</h2>
<p>هذه الصفحة تعرض جميع التكاملات المعرفة في ملفات YAML داخل النظام.</p>

<hr>

<!-- قائمة التكاملات -->
<div style="margin-top:20px; display:flex; flex-wrap:wrap; gap:20px;">
  @foreach ($integrations as $key => $integration)
    <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; width:300px; padding:15px; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
      <h3 style="margin:0; color:#1e293b;">{{ $integration['icon'] ?? '🧩' }} {{ $integration['name'] ?? ucfirst($key) }}</h3>
      <p style="color:#475569; font-size:14px; margin-top:6px;">{{ $integration['description'] ?? 'لا يوجد وصف متاح' }}</p>
      <p style="color:#64748b; font-size:13px;">الفئة: <strong>{{ $integration['category'] ?? 'غير محددة' }}</strong></p>
      <a href="/integrations/{{ $key }}" style="display:inline-block; margin-top:10px; background:#1e293b; color:white; padding:8px 14px; border-radius:6px; text-decoration:none;">عرض التفاصيل</a>
    </div>
  @endforeach
</div>
@endsection