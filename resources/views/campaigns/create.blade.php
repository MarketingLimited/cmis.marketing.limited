@extends('layouts.admin')

@section('page-title', 'إنشاء حملة تسويقية جديدة')
@section('page-subtitle', 'أنشئ حملة تسويقية متكاملة مع أهداف وميزانية')

@section('content')
<div class="max-w-5xl mx-auto">
    <form method="POST" action="{{ route('campaigns.store') }}" x-data="campaignForm()" @submit="validateForm">
        @csrf

        <div class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-indigo-600 ml-2"></i>
                    المعلومات الأساسية
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم الحملة *</label>
                        <input type="text" name="campaign_name" x-model="form.campaign_name" required
                               value="{{ old('campaign_name') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('campaign_name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع الحملة *</label>
                        <select name="campaign_type" x-model="form.campaign_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">اختر النوع</option>
                            <option value="awareness">توعية</option>
                            <option value="consideration">اهتمام</option>
                            <option value="conversion">تحويل</option>
                            <option value="retention">استبقاء</option>
                            <option value="engagement">تفاعل</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المؤسسة *</label>
                        <select name="org_id" x-model="form.org_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">اختر المؤسسة</option>
                            @foreach($organizations ?? [] as $org)
                                <option value="{{ $org->org_id }}">{{ $org->org_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">الوصف</label>
                        <textarea name="description" x-model="form.description" rows="3"
                                  value="{{ old('description') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Goals & KPIs -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-bullseye text-indigo-600 ml-2"></i>
                    الأهداف ومؤشرات الأداء
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">أهداف الحملة *</label>
                        <textarea name="goals" x-model="form.goals" rows="3" required
                                  placeholder="حدد الأهداف الرئيسية للحملة..."
                                  value="{{ old('goals') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('goals') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">مؤشر الأداء الرئيسي</label>
                            <select name="primary_kpi" x-model="form.primary_kpi"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">اختر المؤشر</option>
                                <option value="impressions">الانطباعات</option>
                                <option value="clicks">النقرات</option>
                                <option value="conversions">التحويلات</option>
                                <option value="engagement">التفاعل</option>
                                <option value="reach">الوصول</option>
                                <option value="roi">العائد على الاستثمار</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">القيمة المستهدفة</label>
                            <input type="number" name="target_value" x-model="form.target_value"
                                   value="{{ old('target_value') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">وحدة القياس</label>
                            <input type="text" name="measurement_unit" x-model="form.measurement_unit"
                                   placeholder="مثال: عدد، نسبة، ر.س"
                                   value="{{ old('measurement_unit') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget & Timeline -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-wallet text-indigo-600 ml-2"></i>
                    الميزانية والجدول الزمني
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الميزانية الكلية (ر.س) *</label>
                        <input type="number" name="budget" x-model="form.budget" step="0.01" min="0" required
                               value="{{ old('budget') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">العملة</label>
                        <select name="currency" x-model="form.currency"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                            <option value="EUR">يورو (EUR)</option>
                            <option value="AED">درهم إماراتي (AED)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ البدء *</label>
                        <input type="date" name="start_date" x-model="form.start_date" required
                               value="{{ old('start_date') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ الانتهاء *</label>
                        <input type="date" name="end_date" x-model="form.end_date" required
                               value="{{ old('end_date') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="md:col-span-2">
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-indigo-900">المدة المتوقعة</p>
                                    <p class="text-xs text-indigo-700 mt-1">سيتم حسابها تلقائياً بناءً على التواريخ</p>
                                </div>
                                <template x-if="form.start_date && form.end_date">
                                    <span class="text-2xl font-bold text-indigo-600" x-text="calculateDuration() + ' يوم'"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Target Audience -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-users text-indigo-600 ml-2"></i>
                    الجمهور المستهدف
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">وصف الجمهور المستهدف</label>
                        <textarea name="target_audience" x-model="form.target_audience" rows="3"
                                  placeholder="حدد الفئة العمرية، الاهتمامات، الموقع الجغرافي..."
                                  value="{{ old('target_audience') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('target_audience') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">الفئة العمرية</label>
                            <select name="age_range" x-model="form.age_range"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">اختر الفئة</option>
                                <option value="18-24">18-24</option>
                                <option value="25-34">25-34</option>
                                <option value="35-44">35-44</option>
                                <option value="45-54">45-54</option>
                                <option value="55+">55+</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">الجنس</label>
                            <select name="gender" x-model="form.gender"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">الكل</option>
                                <option value="male">ذكور</option>
                                <option value="female">إناث</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">الموقع</label>
                            <input type="text" name="location" x-model="form.location"
                                   placeholder="مثال: الرياض، جدة"
                                   value="{{ old('location') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Settings -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-cog text-indigo-600 ml-2"></i>
                    الحالة والإعدادات
                </h3>

                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="mr-2 text-sm text-gray-700">الحملة نشطة</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="enable_workflow" value="1" checked
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="mr-2 text-sm text-gray-700">إنشاء سير عمل تلقائي</span>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-lg font-medium hover:shadow-lg transition">
                    <i class="fas fa-rocket ml-2"></i>
                    إطلاق الحملة
                </button>
                <a href="{{ route('campaigns.index') }}"
                   class="bg-gray-100 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-200 transition">
                    إلغاء
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function campaignForm() {
    return {
        form: {
            campaign_name: '',
            campaign_type: '',
            org_id: '',
            description: '',
            goals: '',
            primary_kpi: '',
            target_value: '',
            measurement_unit: '',
            budget: '',
            currency: 'SAR',
            start_date: '',
            end_date: '',
            target_audience: '',
            age_range: '',
            gender: '',
            location: ''
        },

        validateForm(e) {
            // Check dates
            if (this.form.start_date && this.form.end_date) {
                const start = new Date(this.form.start_date);
                const end = new Date(this.form.end_date);

                if (end <= start) {
                    alert('تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء');
                    e.preventDefault();
                    return false;
                }
            }

            // Check budget
            if (this.form.budget && parseFloat(this.form.budget) <= 0) {
                alert('الميزانية يجب أن تكون أكبر من صفر');
                e.preventDefault();
                return false;
            }

            return true;
        },

        calculateDuration() {
            if (!this.form.start_date || !this.form.end_date) return 0;

            const start = new Date(this.form.start_date);
            const end = new Date(this.form.end_date);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            return diffDays;
        }
    };
}
</script>
@endpush
