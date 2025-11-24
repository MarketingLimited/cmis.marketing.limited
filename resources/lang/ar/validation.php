<?php

return [
    // Common Validation
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالح.',
    'min' => [
        'string' => 'يجب أن يكون :attribute على الأقل :min حرفاً.',
        'numeric' => 'يجب أن يكون :attribute على الأقل :min.',
    ],
    'max' => [
        'string' => 'يجب ألا يكون :attribute أكبر من :max حرفاً.',
        'numeric' => 'يجب ألا يكون :attribute أكبر من :max.',
    ],
    'unique' => ':attribute مستخدم بالفعل.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'url' => 'تنسيق :attribute غير صالح.',
    'date' => ':attribute ليس تاريخاً صالحاً.',
    'after' => 'يجب أن يكون :attribute تاريخاً بعد :date.',
    'before' => 'يجب أن يكون :attribute تاريخاً قبل :date.',
    'between' => [
        'numeric' => 'يجب أن يكون :attribute بين :min و :max.',
        'string' => 'يجب أن يكون :attribute بين :min و :max حرفاً.',
    ],
    'in' => ':attribute المحدد غير صالح.',
    'exists' => ':attribute المحدد غير صالح.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'mimes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'size' => [
        'file' => 'يجب أن يكون :attribute :size كيلوبايت.',
    ],
    'boolean' => 'يجب أن يكون حقل :attribute صحيحاً أو خاطئاً.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',

    // Custom Validation
    'alpha_dash' => 'يجب أن يحتوي :attribute على أحرف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على أحرف وأرقام فقط.',
    'digits' => 'يجب أن يكون :attribute :digits أرقام.',
    'digits_between' => 'يجب أن يكون :attribute بين :min و :max أرقام.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'regex' => 'تنسيق :attribute غير صالح.',
    'same' => 'يجب أن يتطابق :attribute و :other.',
    'timezone' => 'يجب أن يكون :attribute منطقة زمنية صالحة.',

    // Attribute Names
    'attributes' => [
        'name' => 'الاسم',
        'email' => 'عنوان البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'phone' => 'رقم الهاتف',
        'budget' => 'الميزانية',
        'campaign_name' => 'اسم الحملة',
        'start_date' => 'تاريخ البدء',
        'end_date' => 'تاريخ الانتهاء',
    ],
];
