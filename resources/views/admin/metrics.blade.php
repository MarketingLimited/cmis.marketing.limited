@extends('layouts.admin')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">📊 Knowledge Metrics Dashboard</h1>

    <div class="alert alert-info">
        هذه الصفحة تعرض الاتجاهات الدلالية المكتشفة خلال الأسبوع الماضي عبر دالة <code>semantic_analysis()</code>.
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Intent</th>
                <th>Usage Count</th>
                <th>Average Quality</th>
            </tr>
        </thead>
        <tbody>
            @forelse($metrics as $m)
                <tr>
                    <td>{{ $m->intent }}</td>
                    <td>{{ $m->usage_count }}</td>
                    <td>{{ number_format($m->avg_score, 3) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">لا توجد بيانات متاحة حاليًا.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
