@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', 'إنشاء بريف جديد')
@section('page-subtitle', 'أنشئ بريف إبداعي شامل لحملتك التسويقية')

@section('content')
<div class="max-w-5xl mx-auto">
    <form method="POST" action="{{ route('orgs.briefs.store', ['org' => $currentOrg]) }}" x-data="briefForm()" @submit="validateAndSubmit">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">عنوان البريف *</label>
                        <input type="text" name="brief_title" x-model="form.brief_title" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('brief_title')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع البريف *</label>
                        <select name="brief_type" x-model="form.brief_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">اختر النوع</option>
                            <option value="campaign">حملة تسويقية</option>
                            <option value="content">محتوى</option>
                            <option value="design">تصميم</option>
                            <option value="video">فيديو</option>
                            <option value="social">سوشيال ميديا</option>
                        </select>
                        @error('brief_type')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الحملة المرتبطة</label>
                        <select name="campaign_id" x-model="form.campaign_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">بدون حملة</option>
                            @foreach($campaigns ?? [] as $campaign)
                                <option value="{{ $campaign->campaign_id }}">{{ $campaign->campaign_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">التاريخ المستهدف</label>
                        <input type="date" name="target_date" x-model="form.target_date"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الميزانية (ر.س)</label>
                        <input type="number" name="budget" x-model="form.budget" step="0.01" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Objectives & Strategy -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-bullseye text-indigo-600 ml-2"></i>
                    الأهداف والاستراتيجية
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">أهداف البريف *</label>
                        <textarea name="objectives" x-model="form.objectives" rows="4" required
                                  placeholder="اذكر الأهداف الرئيسية للحملة..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                        @error('objectives')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الرسالة الرئيسية</label>
                        <textarea name="key_message" x-model="form.key_message" rows="3"
                                  placeholder="ما هي الرسالة الأساسية التي تريد إيصالها؟"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الاستراتيجية الإبداعية</label>
                        <textarea name="creative_strategy" x-model="form.creative_strategy" rows="4"
                                  placeholder="اشرح الاستراتيجية الإبداعية..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">وصف الجمهور المستهدف *</label>
                        <textarea name="target_audience" x-model="form.target_audience" rows="3" required
                                  placeholder="حدد الفئة المستهدفة (العمر، الجنس، الاهتمامات...)"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                        @error('target_audience')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">شخصية العميل (Persona)</label>
                        <textarea name="persona" x-model="form.persona" rows="3"
                                  placeholder="صف شخصية العميل المثالي..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Deliverables & Specifications -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-clipboard-list text-indigo-600 ml-2"></i>
                    المخرجات والمواصفات
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المخرجات المطلوبة</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="logo" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="mr-2 text-sm text-gray-700">شعار</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="social_posts" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="mr-2 text-sm text-gray-700">منشورات سوشيال ميديا</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="banner" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="mr-2 text-sm text-gray-700">بنر إعلاني</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="video" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="mr-2 text-sm text-gray-700">فيديو</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="content" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="mr-2 text-sm text-gray-700">محتوى نصي</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المواصفات الفنية</label>
                        <textarea name="technical_specs" x-model="form.technical_specs" rows="3"
                                  placeholder="حدد المواصفات الفنية (أبعاد، صيغ الملفات...)"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">إرشادات العلامة التجارية</label>
                        <textarea name="brand_guidelines" x-model="form.brand_guidelines" rows="3"
                                  placeholder="ألوان العلامة، الخطوط، النبرة..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- References & Inspiration -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-lightbulb text-indigo-600 ml-2"></i>
                    المراجع والإلهام
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مراجع مشابهة</label>
                        <textarea name="references" x-model="form.references" rows="3"
                                  placeholder="أضف روابط أو أوصاف لأعمال ملهمة..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ما يجب تجنبه</label>
                        <textarea name="avoid" x-model="form.avoid" rows="2"
                                  placeholder="أشياء لا تريدها في التصميم..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex gap-3">
                    <button type="submit" name="status" value="review"
                            class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-lg font-medium hover:shadow-lg transition">
                        <i class="fas fa-paper-plane ml-2"></i>
                        إرسال للمراجعة
                    </button>
                    <button type="submit" name="status" value="draft"
                            class="flex-1 bg-gray-100 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-200 transition">
                        <i class="fas fa-save ml-2"></i>
                        حفظ كمسودة
                    </button>
                    <a href="{{ route('orgs.briefs.index', ['org' => $currentOrg]) }}"
                       class="bg-white border border-gray-300 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-50 transition">
                        إلغاء
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function briefForm() {
    return {
        form: {
            brief_title: '',
            brief_type: '',
            campaign_id: '',
            target_date: '',
            budget: '',
            objectives: '',
            key_message: '',
            creative_strategy: '',
            target_audience: '',
            persona: '',
            technical_specs: '',
            brand_guidelines: '',
            references: '',
            avoid: ''
        },

        validateAndSubmit(e) {
            // Basic validation
            if (!this.form.brief_title || !this.form.brief_type || !this.form.objectives || !this.form.target_audience) {
                alert('يرجى ملء جميع الحقول المطلوبة');
                e.preventDefault();
                return false;
            }

            // Structure validation will be done by backend using validate_brief_structure()
            return true;
        }
    };
}
</script>
@endpush
