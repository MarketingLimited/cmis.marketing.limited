<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تقرير مقارنة الحملات</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; direction: rtl; text-align: right; }
    header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #444; padding-bottom: 10px; margin-bottom: 20px; }
    header img { height: 60px; }
    header div { text-align: left; }
    h2 { text-align: center; color: #2b2b2b; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #aaa; padding: 8px; text-align: center; }
    th { background-color: #eee; }
  </style>
</head>
<body>
  <header>
    <img src="{{ public_path('images/logo.png') }}" alt="شعار المؤسسة">
    <div>
      <strong>{{ $org_name ?? 'مؤسسة غير محددة' }}</strong><br>
      <span>العملة: {{ $org_currency ?? 'غير محددة' }}</span><br>
      <span>تاريخ التقرير: {{ date('Y-m-d H:i') }}</span>
    </div>
  </header>

  <h2>📊 تقرير مقارنة الحملات</h2>

  <p><strong>عدد الحملات المقارنة:</strong> {{ count($campaigns) }}</p>

  <table>
    <thead>
      <tr>
        <th>KPI</th>
        @foreach($datasets as $d)
          <th>{{ $d->label }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($kpiLabels as $i => $kpi)
        <tr>
          <td>{{ $kpi }}</td>
          @foreach($datasets as $d)
            <td>{{ number_format($d->data[$i] ?? 0, 2) }}</td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>

  <p style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
    تم توليد هذا التقرير تلقائيًا عبر نظام CMIS في {{ date('Y-m-d H:i') }}
  </p>
</body>
</html>