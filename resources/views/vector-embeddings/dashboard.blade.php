@extends('layouts.admin')

@section('title', 'Vector Embeddings Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">📊 لوحة معلومات Vector Embeddings v2.0</h1>

            @if(isset($error))
                <div class="alert alert-danger">
                    <strong>خطأ:</strong> {{ $error }}
                </div>
            @endif
        </div>
    </div>

    <!-- Embedding Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📈 حالة تغطية Embeddings</h5>
                </div>
                <div class="card-body">
                    @if(isset($embeddingStatus) && count($embeddingStatus) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>الفئة</th>
                                        <th>النطاق</th>
                                        <th>إجمالي السجلات</th>
                                        <th>مع Embedding</th>
                                        <th>التغطية %</th>
                                        <th>التقييم</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($embeddingStatus as $status)
                                        <tr>
                                            <td>{{ $status->{'الفئة'} ?? 'N/A' }}</td>
                                            <td>{{ $status->{'النطاق'} ?? 'N/A' }}</td>
                                            <td>{{ number_format($status->{'إجمالي السجلات'} ?? 0) }}</td>
                                            <td>{{ number_format($status->{'السجلات مع Embedding'} ?? 0) }}</td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"
                                                         style="width: {{ $status->{'نسبة التغطية %'} ?? 0 }}%">
                                                        {{ number_format($status->{'نسبة التغطية %'} ?? 0, 2) }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge
                                                    @if(($status->{'التقييم'} ?? '') == 'ممتاز') badge-success
                                                    @elseif(($status->{'التقييم'} ?? '') == 'جيد') badge-primary
                                                    @elseif(($status->{'التقييم'} ?? '') == 'مقبول') badge-warning
                                                    @else badge-danger
                                                    @endif">
                                                    {{ $status->{'التقييم'} ?? '—' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">لا توجد بيانات</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">⏳ حالة قائمة الانتظار</h5>
                </div>
                <div class="card-body">
                    @if(isset($queueStatus) && count($queueStatus) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>الحالة</th>
                                        <th>العدد</th>
                                        <th>متوسط المحاولات</th>
                                        <th>متوسط الانتظار (دقيقة)</th>
                                        <th>الوصف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($queueStatus as $status)
                                        <tr>
                                            <td>{{ $status->{'الحالة'} ?? 'N/A' }}</td>
                                            <td>{{ number_format($status->{'العدد'} ?? 0) }}</td>
                                            <td>{{ number_format($status->{'متوسط المحاولات'} ?? 0, 2) }}</td>
                                            <td>{{ number_format($status->{'متوسط وقت الانتظار (دقيقة)'} ?? 0, 2) }}</td>
                                            <td>{{ $status->{'الوصف'} ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">قائمة الانتظار فارغة</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- System Report -->
    @if(isset($reportData))
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📋 تقرير النظام</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3">{{ json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
