@extends('layouts.admin')

@section('content')
<h2>📊 حملات المؤسسة</h2>

<form id="compareForm" action="{{ url('orgs/' . $id . '/campaigns/compare') }}" method="get">
<table>
    <thead>
        <tr>
            <th>تحديد</th>
            <th>اسم الحملة</th>
            <th>الهدف</th>
            <th>الحالة</th>
            <th>تاريخ البداية</th>
            <th>تاريخ النهاية</th>
            <th>الميزانية</th>
            <th>العملة</th>
            <th>عرض التفاصيل</th>
        </tr>
    </thead>
    <tbody>
        @forelse($campaigns as $c)
        <tr>
            <td><input type="checkbox" name="campaign_ids[]" value="{{ $c->campaign_id }}"></td>
            <td>{{ $c->name }}</td>
            <td>{{ $c->objective ?? 'غير محدد' }}</td>
            <td>{{ $c->status ?? 'غير معروفة' }}</td>
            <td>{{ $c->start_date ?? '-' }}</td>
            <td>{{ $c->end_date ?? '-' }}</td>
            <td>{{ $c->budget ? number_format($c->budget, 2) : '-' }}</td>
            <td>{{ $c->currency ?? '-' }}</td>
            <td>
                <a href="/campaigns/{{ $c->campaign_id }}" class="button">عرض</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="9">لا توجد حملات مسجلة لهذه المؤسسة.</td></tr>
        @endforelse
    </tbody>
</table>

<button type="submit">قارن الحملات المختارة</button>
</form>

<script>
  document.getElementById('compareForm').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('input[name="campaign_ids[]"]:checked');
    if (checked.length < 2) {
      e.preventDefault();
      alert('يرجى اختيار حملتين على الأقل للمقارنة.');
    }
  });
</script>
@endsection