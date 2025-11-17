@extends('layouts.admin')

@section('title', 'Performance Analytics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">📈 إحصائيات الأداء</h1>

            @if(isset($error))
                <div class="alert alert-danger">
                    <strong>خطأ:</strong> {{ $error }}
                </div>
            @endif
        </div>
    </div>

    <!-- Statistics Overview -->
    @if(isset($stats))
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary">{{ number_format($stats['total_searches']) }}</h3>
                    <p class="mb-0">عمليات البحث (24 ساعة)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">{{ number_format($stats['avg_similarity'], 2) }}%</h3>
                    <p class="mb-0">متوسط التطابق</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info">{{ number_format($stats['total_embeddings']) }}</h3>
                    <p class="mb-0">إجمالي Embeddings</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning">{{ number_format($stats['pending_queue']) }}</h3>
                    <p class="mb-0">بانتظار المعالجة</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Search Performance Chart -->
    @if(isset($searchPerformance) && count($searchPerformance) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🔍 أداء البحث (آخر 48 ساعة)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>الساعة</th>
                                    <th>عدد عمليات البحث</th>
                                    <th>متوسط النتائج</th>
                                    <th>متوسط التطابق %</th>
                                    <th>عمليات بحث فارغة</th>
                                    <th>التقييم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($searchPerformance as $perf)
                                    <tr>
                                        <td>{{ $perf->{'الساعة'} ?? 'N/A' }}</td>
                                        <td>{{ number_format($perf->{'عدد عمليات البحث'} ?? 0) }}</td>
                                        <td>{{ number_format($perf->{'متوسط النتائج'} ?? 0, 2) }}</td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $perf->{'متوسط التطابق %'} ?? 0 }}%">
                                                    {{ number_format($perf->{'متوسط التطابق %'} ?? 0, 2) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ number_format($perf->{'عمليات بحث فارغة'} ?? 0) }}</td>
                                        <td>
                                            <span class="badge
                                                @if(($perf->{'التقييم'} ?? '') == 'ممتاز') badge-success
                                                @elseif(($perf->{'التقييم'} ?? '') == 'جيد') badge-primary
                                                @elseif(($perf->{'التقييم'} ?? '') == 'مقبول') badge-warning
                                                @else badge-danger
                                                @endif">
                                                {{ $perf->{'التقييم'} ?? '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info">
            لا توجد بيانات أداء حالياً
        </div>
    @endif
</div>
@endsection
