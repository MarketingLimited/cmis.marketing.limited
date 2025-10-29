@extends('layouts.app')

@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">🤖 لوحة الذكاء الاصطناعي (AI Dashboard)</h2>
        <p class="text-gray-600">متابعة الحملات المولدة بالذكاء الاصطناعي والتوصيات والنماذج المدربة.</p>
    </div>

    <div class="flex flex-wrap gap-4">
        <a href="/ai/campaigns" class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg font-semibold hover:bg-emerald-200 transition">🎯 الحملات الذكية</a>
        <a href="/ai/recommendations" class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg font-semibold hover:bg-emerald-200 transition">💡 التوصيات الذكية</a>
        <a href="/ai/models" class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg font-semibold hover:bg-emerald-200 transition">🧠 النماذج</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-emerald-700 mb-4">مؤشرات الذكاء الاصطناعي</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="border border-emerald-200 rounded-xl p-4 text-center bg-emerald-50">
                <p class="text-emerald-600 font-semibold">الحملات المولدة</p>
                <p class="text-2xl font-bold">{{ $stats['campaigns'] }}</p>
            </div>
            <div class="border border-emerald-200 rounded-xl p-4 text-center bg-emerald-50">
                <p class="text-emerald-600 font-semibold">التوصيات</p>
                <p class="text-2xl font-bold">{{ $stats['recommendations'] }}</p>
            </div>
            <div class="border border-emerald-200 rounded-xl p-4 text-center bg-emerald-50">
                <p class="text-emerald-600 font-semibold">النماذج المتاحة</p>
                <p class="text-2xl font-bold">{{ $stats['models'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">آخر الحملات المولدة</h3>
        <ul class="space-y-3">
            @forelse ($recentCampaigns as $campaign)
                <li class="border border-gray-100 rounded-lg p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="font-semibold text-emerald-700">{{ $campaign->objective_code ?? 'هدف غير محدد' }}</p>
                            <p class="text-sm text-gray-500">{{ Str::limit($campaign->ai_summary ?? 'لا يوجد ملخص', 120) }}</p>
                        </div>
                        <span class="text-sm text-gray-400">{{ optional($campaign->created_at)->diffForHumans() ?? 'غير متوفر' }}</span>
                    </div>
                </li>
            @empty
                <li class="text-gray-500">لا توجد حملات مسجلة.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">أحدث التوصيات</h3>
        <ul class="space-y-3">
            @forelse ($recentRecommendations as $recommendation)
                <li class="border border-gray-100 rounded-lg p-4">
                    <p class="font-semibold text-emerald-700">{{ number_format($recommendation->predicted_ctr ?? 0, 2) }} CTR متوقع</p>
                    <p class="text-sm text-gray-500">{{ Str::limit($recommendation->prediction_summary ?? 'بدون ملخص', 160) }}</p>
                    <span class="text-xs text-gray-400">الثقة: {{ number_format($recommendation->confidence_level ?? 0, 2) }}</span>
                </li>
            @empty
                <li class="text-gray-500">لا توجد توصيات حديثة.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">النماذج المسجلة</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-right">الاسم</th>
                        <th class="px-4 py-3 text-right">العائلة</th>
                        <th class="px-4 py-3 text-right">الحالة</th>
                        <th class="px-4 py-3 text-right">آخر تدريب</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($models as $model)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-emerald-700">{{ $model->model_name ?? 'غير مسمى' }}</td>
                            <td class="px-4 py-2">{{ $model->model_family ?? 'غير محدد' }}</td>
                            <td class="px-4 py-2">{{ $model->status ?? 'غير محدد' }}</td>
                            <td class="px-4 py-2">{{ optional($model->trained_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">لا توجد نماذج متاحة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
