@extends('layouts.app')

@section('page-title', $workflow->flow_name ?? 'تفاصيل سير العمل')
@section('page-subtitle', 'تتبع تقدم وخطوات سير العمل')

@section('content')
<div x-data="workflowDetails()" x-init="init()" class="max-w-6xl mx-auto">
    <x-breadcrumb :items="[
        ['label' => 'سير العمل', 'url' => route('workflows.index')],
        ['label' => $workflow->flow_name ?? 'التفاصيل']
    ]" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Workflow Header -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $workflow->flow_name }}</h1>
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <span class="flex items-center">
                                <i class="fas fa-tag ml-1"></i>
                                {{ $workflow->entity_type }}
                            </span>
                            <span>•</span>
                            <span class="flex items-center">
                                <i class="fas fa-calendar ml-1"></i>
                                {{ $workflow->created_at->format('Y-m-d') }}
                            </span>
                        </div>
                    </div>
                    <span class="px-4 py-2 rounded-full text-sm font-medium
                        {{ $workflow->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $workflow->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $workflow->status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $workflow->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ __('workflow.status.' . $workflow->status) }}
                    </span>
                </div>

                <!-- Progress Bar -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between text-sm text-gray-700 mb-2">
                        <span class="font-medium">التقدم الكلي</span>
                        <span class="font-bold text-indigo-600">{{ $workflow->completed_steps }}/{{ $workflow->total_steps }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-full h-3 transition-all"
                             style="width: {{ ($workflow->completed_steps / $workflow->total_steps) * 100 }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-600 mt-2">
                        <span>{{ number_format(($workflow->completed_steps / $workflow->total_steps) * 100, 1) }}% مكتمل</span>
                        <span>{{ $workflow->total_steps - $workflow->completed_steps }} خطوة متبقية</span>
                    </div>
                </div>
            </div>

            <!-- Workflow Steps -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-list-ol text-indigo-600 ml-2"></i>
                    خطوات سير العمل
                </h2>

                <div class="space-y-4">
                    <template x-for="(step, index) in steps" :key="step.step_id">
                        <div class="border rounded-lg overflow-hidden"
                             :class="{
                                 'border-indigo-300 bg-indigo-50': step.status === 'in_progress',
                                 'border-gray-200': step.status !== 'in_progress'
                             }">
                            <!-- Step Header -->
                            <div class="p-4 flex items-center gap-4">
                                <!-- Step Number/Status Icon -->
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold"
                                         :class="{
                                             'bg-green-100 text-green-600': step.status === 'completed',
                                             'bg-indigo-100 text-indigo-600': step.status === 'in_progress',
                                             'bg-gray-100 text-gray-400': step.status === 'pending',
                                             'bg-red-100 text-red-600': step.status === 'skipped'
                                         }">
                                        <template x-if="step.status === 'completed'">
                                            <i class="fas fa-check"></i>
                                        </template>
                                        <template x-if="step.status === 'in_progress'">
                                            <i class="fas fa-play"></i>
                                        </template>
                                        <template x-if="step.status === 'pending'">
                                            <span x-text="step.step_number"></span>
                                        </template>
                                        <template x-if="step.status === 'skipped'">
                                            <i class="fas fa-forward"></i>
                                        </template>
                                    </div>
                                </div>

                                <!-- Step Info -->
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-900 mb-1" x-text="step.step_name"></h3>
                                    <p class="text-sm text-gray-600" x-text="step.step_description"></p>

                                    <!-- Dates -->
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <template x-if="step.started_at">
                                            <span class="flex items-center">
                                                <i class="fas fa-play-circle ml-1"></i>
                                                بدأ: <span x-text="formatDate(step.started_at)"></span>
                                            </span>
                                        </template>
                                        <template x-if="step.completed_at">
                                            <span class="flex items-center text-green-600">
                                                <i class="fas fa-check-circle ml-1"></i>
                                                أكمل: <span x-text="formatDate(step.completed_at)"></span>
                                            </span>
                                        </template>
                                        <template x-if="step.expected_duration">
                                            <span class="flex items-center">
                                                <i class="fas fa-clock ml-1"></i>
                                                <span x-text="step.expected_duration + ' دقيقة'"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex-shrink-0">
                                    <template x-if="step.status === 'in_progress'">
                                        <button @click="completeStep(step.step_id, step.step_number)"
                                                class="bg-green-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-600 transition">
                                            <i class="fas fa-check ml-2"></i>
                                            إكمال
                                        </button>
                                    </template>
                                    <template x-if="step.status === 'pending' && canStartStep(step.step_number)">
                                        <button @click="startStep(step.step_id, step.step_number)"
                                                class="bg-indigo-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-600 transition">
                                            <i class="fas fa-play ml-2"></i>
                                            ابدأ
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Step Notes (if completed) -->
                            <template x-if="step.notes && step.status === 'completed'">
                                <div class="px-4 pb-4 border-t bg-gray-50">
                                    <div class="mt-3">
                                        <p class="text-xs font-medium text-gray-700 mb-1">ملاحظات:</p>
                                        <p class="text-sm text-gray-600" x-text="step.notes"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-history text-indigo-600 ml-2"></i>
                    سجل النشاط
                </h2>

                <div class="space-y-3">
                    <template x-for="activity in activities" :key="activity.id">
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <i :class="activity.icon" class="text-gray-400"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-700" x-text="activity.description"></p>
                                <p class="text-xs text-gray-500 mt-1" x-text="formatDate(activity.created_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-4">الإحصائيات</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-white/80">إجمالي الخطوات</span>
                        <span class="text-2xl font-bold">{{ $workflow->total_steps }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/80">مكتملة</span>
                        <span class="text-2xl font-bold text-green-300">{{ $workflow->completed_steps }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/80">متبقية</span>
                        <span class="text-2xl font-bold text-yellow-300">{{ $workflow->total_steps - $workflow->completed_steps }}</span>
                    </div>
                </div>
            </div>

            <!-- Related Entity -->
            @if($workflow->entity_type && $workflow->entity_id)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">الكيان المرتبط</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">{{ $workflow->entity_type }}</p>
                    <p class="font-medium text-gray-900">{{ $workflow->entity_name ?? 'ID: ' . $workflow->entity_id }}</p>
                    <a href="#" class="text-sm text-indigo-600 hover:text-indigo-700 mt-2 inline-block">
                        عرض التفاصيل →
                    </a>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">إجراءات</h3>
                <div class="space-y-2">
                    @if($workflow->status !== 'cancelled')
                    <button @click="cancelWorkflow"
                            class="w-full bg-red-50 text-red-600 py-2 rounded-lg font-medium hover:bg-red-100 transition">
                        <i class="fas fa-times ml-2"></i>
                        إلغاء سير العمل
                    </button>
                    @endif
                    <a href="{{ route('workflows.index') }}"
                       class="block w-full bg-gray-50 text-gray-600 text-center py-2 rounded-lg font-medium hover:bg-gray-100 transition">
                        <i class="fas fa-arrow-right ml-2"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>

            <!-- Timeline Summary -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">الجدول الزمني</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاريخ البدء</span>
                        <span class="font-medium text-gray-900">{{ $workflow->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">آخر تحديث</span>
                        <span class="font-medium text-gray-900">{{ $workflow->updated_at->format('Y-m-d H:i') }}</span>
                    </div>
                    @if($workflow->status === 'completed' && $workflow->completed_at)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">تاريخ الإكمال</span>
                        <span class="font-medium text-green-600">{{ $workflow->completed_at->format('Y-m-d') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function workflowDetails() {
    return {
        steps: @json($steps ?? []),
        activities: @json($activities ?? []),
        flowId: {{ $workflow->flow_id }},

        init() {
            // Initialize
        },

        canStartStep(stepNumber) {
            // Can only start if previous step is completed
            if (stepNumber === 1) return true;
            const prevStep = this.steps.find(s => s.step_number === stepNumber - 1);
            return prevStep && prevStep.status === 'completed';
        },

        async startStep(stepId, stepNumber) {
            try {
                const response = await fetch(`/workflows/${this.flowId}/steps/${stepNumber}/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Failed to start step:', error);
                alert('فشل بدء الخطوة');
            }
        },

        async completeStep(stepId, stepNumber) {
            const notes = prompt('أضف ملاحظات (اختياري):');

            try {
                const response = await fetch(`/workflows/${this.flowId}/steps/${stepNumber}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ notes })
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Failed to complete step:', error);
                alert('فشل إكمال الخطوة');
            }
        },

        async cancelWorkflow() {
            if (!confirm('هل أنت متأكد من إلغاء سير العمل؟ لا يمكن التراجع عن هذا الإجراء.')) return;

            try {
                const response = await fetch(`/workflows/${this.flowId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Failed to cancel workflow:', error);
                alert('فشل إلغاء سير العمل');
            }
        },

        formatDate(date) {
            return new Date(date).toLocaleString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
@endpush
