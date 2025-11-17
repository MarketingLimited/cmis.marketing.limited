@extends('layouts.admin')

@section('content')
<h2>🏢 قائمة المؤسسات</h2>
<p>تعرض هذه الصفحة جميع المؤسسات المسجلة في النظام مع إمكانية إدارتها.</p>

<div style="margin: 15px 0;">
  <a href="/orgs/create" style="background:#3b82f6; color:white; padding:8px 14px; border-radius:5px; text-decoration:none;">➕ إضافة مؤسسة جديدة</a>
</div>

<table style="width:100%; border-collapse: collapse; background:#fff; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
  <thead style="background:#1e293b; color:#fff;">
    <tr>
      <th style="padding:10px; border:1px solid #ddd;">#</th>
      <th style="padding:10px; border:1px solid #ddd;">الاسم</th>
      <th style="padding:10px; border:1px solid #ddd;">اللغة الافتراضية</th>
      <th style="padding:10px; border:1px solid #ddd;">العملة</th>
      <th style="padding:10px; border:1px solid #ddd;">تاريخ الإنشاء</th>
      <th style="padding:10px; border:1px solid #ddd;">الخيارات</th>
    </tr>
  </thead>
  <tbody>
    @forelse($orgs ?? [] as $org)
    <tr>
      <td style="padding:10px; border:1px solid #ddd;">{{ $org->org_id }}</td>
      <td style="padding:10px; border:1px solid #ddd;">{{ $org->name }}</td>
      <td style="padding:10px; border:1px solid #ddd;">{{ $org->default_locale }}</td>
      <td style="padding:10px; border:1px solid #ddd;">{{ $org->currency }}</td>
      <td style="padding:10px; border:1px solid #ddd;">{{ $org->created_at }}</td>
      <td style="padding:10px; border:1px solid #ddd;">
        <a href="/orgs/{{ $org->org_id }}" style="color:#3b82f6; text-decoration:none;">عرض</a> |
        <a href="/orgs/{{ $org->org_id }}/edit" style="color:#10b981; text-decoration:none;">تعديل</a> |
        <form action="/orgs/{{ $org->org_id }}" method="POST" style="display:inline;">
          @csrf
          @method('DELETE')
          <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer;">حذف</button>
        </form>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="6" style="text-align:center; padding:15px; color:#555;">لا توجد مؤسسات حالياً.</td>
    </tr>
    @endforelse
  </tbody>
</table>
@endsection