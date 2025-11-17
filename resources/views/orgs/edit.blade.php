@extends('layouts.admin')

@section('page-title', 'تعديل بيانات المؤسسة')
@section('page-subtitle', 'قم بتحديث معلومات المؤسسة الحالية')

@section('content')
<div class="max-w-4xl mx-auto">
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle ml-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle ml-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('orgs.update', $org->org_id) }}" class="bg-white rounded-xl shadow-sm p-8">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4">المعلومات الأساسية</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم المؤسسة *</label>
                        <input type="text" name="name" value="{{ old('name', $org->name) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 text-xs mt-1">اسم فريد للمؤسسة</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">اللغة الافتراضية</label>
                        <select name="default_locale"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('default_locale') border-red-500 @enderror">
                            <option value="ar-BH" {{ old('default_locale', $org->default_locale) == 'ar-BH' ? 'selected' : '' }}>العربية (البحرين)</option>
                            <option value="en-US" {{ old('default_locale', $org->default_locale) == 'en-US' ? 'selected' : '' }}>English (US)</option>
                            <option value="ar-SA" {{ old('default_locale', $org->default_locale) == 'ar-SA' ? 'selected' : '' }}>العربية (السعودية)</option>
                            <option value="ar-AE" {{ old('default_locale', $org->default_locale) == 'ar-AE' ? 'selected' : '' }}>العربية (الإمارات)</option>
                        </select>
                        @error('default_locale')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">العملة</label>
                        <select name="currency"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('currency') border-red-500 @enderror">
                            <option value="BHD" {{ old('currency', $org->currency) == 'BHD' ? 'selected' : '' }}>دينار بحريني (BHD)</option>
                            <option value="SAR" {{ old('currency', $org->currency) == 'SAR' ? 'selected' : '' }}>ريال سعودي (SAR)</option>
                            <option value="AED" {{ old('currency', $org->currency) == 'AED' ? 'selected' : '' }}>درهم إماراتي (AED)</option>
                            <option value="USD" {{ old('currency', $org->currency) == 'USD' ? 'selected' : '' }}>دولار أمريكي (USD)</option>
                        </select>
                        @error('currency')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">المزود (اختياري)</label>
                        <input type="text" name="provider" value="{{ old('provider', $org->provider) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('provider') border-red-500 @enderror"
                               placeholder="مثال: manual، api، integration">
                        @error('provider')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 text-xs mt-1">مصدر إنشاء المؤسسة (اختياري)</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-6 border-t">
                <button type="submit"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-lg font-medium hover:shadow-lg transition">
                    <i class="fas fa-save ml-2"></i>
                    حفظ التعديلات
                </button>
                <a href="{{ route('orgs.show', $org->org_id) }}"
                   class="bg-gray-100 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-200 transition">
                    إلغاء
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
