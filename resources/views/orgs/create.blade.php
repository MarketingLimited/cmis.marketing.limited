@extends('layouts.app')

@section('page-title', 'إنشاء مؤسسة جديدة')
@section('page-subtitle', 'أضف مؤسسة جديدة إلى النظام')

@section('content')
<div class="max-w-4xl mx-auto">
    <form method="POST" action="{{ route('orgs.store') }}" class="bg-white rounded-xl shadow-sm p-8">
        @csrf

        <div class="space-y-6">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4">المعلومات الأساسية</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم المؤسسة *</label>
                        <input type="text" name="org_name" value="{{ old('org_name') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('org_name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الصناعة *</label>
                        <select name="industry" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">اختر الصناعة</option>
                            <option value="تقنية">تقنية</option>
                            <option value="تجارة إلكترونية">تجارة إلكترونية</option>
                            <option value="تعليم">تعليم</option>
                            <option value="صحة">صحة</option>
                            <option value="عقارات">عقارات</option>
                            <option value="أخرى">أخرى</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">الوصف</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="pt-6 border-t">
                <h3 class="text-lg font-bold text-gray-900 mb-4">معلومات الاتصال</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">رقم الهاتف</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">الموقع الإلكتروني</label>
                    <input type="url" name="website" value="{{ old('website') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Settings -->
            <div class="pt-6 border-t">
                <h3 class="text-lg font-bold text-gray-900 mb-4">الإعدادات</h3>

                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="mr-2 text-sm text-gray-700">المؤسسة نشطة</span>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-6 border-t">
                <button type="submit"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-lg font-medium hover:shadow-lg transition">
                    <i class="fas fa-save ml-2"></i>
                    حفظ المؤسسة
                </button>
                <a href="{{ route('orgs.index') }}"
                   class="bg-gray-100 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-200 transition">
                    إلغاء
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
