@extends('layouts.admin')

@section('title', 'Intent Analysis')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">🎯 تحليل النوايا</h1>

            @if(isset($error))
                <div class="alert alert-danger">
                    <strong>خطأ:</strong> {{ $error }}
                </div>
            @endif
        </div>
    </div>

    @if(isset($intentAnalysis) && count($intentAnalysis) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📊 تحليل استخدام النوايا</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>النية</th>
                                    <th>عدد الاستخدامات</th>
                                    <th>عمليات البحث (30 يوم)</th>
                                    <th>متوسط الصلة %</th>
                                    <th>التقييم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($intentAnalysis as $item)
                                    <tr>
                                        <td><strong>{{ $item->{'النية'} ?? 'N/A' }}</strong></td>
                                        <td>{{ number_format($item->{'عدد الاستخدامات'} ?? 0) }}</td>
                                        <td>{{ number_format($item->{'عمليات البحث (30 يوم)'} ?? 0) }}</td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-info" role="progressbar"
                                                     style="width: {{ $item->{'متوسط الصلة %'} ?? 0 }}%">
                                                    {{ number_format($item->{'متوسط الصلة %'} ?? 0, 2) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge
                                                @if(($item->{'التقييم'} ?? '') == 'ممتاز') badge-success
                                                @elseif(($item->{'التقييم'} ?? '') == 'جيد') badge-primary
                                                @else badge-warning
                                                @endif">
                                                {{ $item->{'التقييم'} ?? '—' }}
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
            لا توجد بيانات تحليلية حالياً
        </div>
    @endif
</div>
@endsection
