<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - CMIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    CMIS - نظام التسويق الذكي
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">تسجيل الدخول إلى حسابك</p>
            </div>
            <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
                @csrf
                @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4"><div class="flex"><div class="ml-3"><h3 class="text-sm font-medium text-red-800">يوجد أخطاء في البيانات المدخلة</h3><div class="mt-2 text-sm text-red-700"><ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div></div></div>
                @endif
                <div class="rounded-md shadow-sm space-y-4">
                    <div><label for="email" class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label><input id="email" name="email" type="email" required value="{{ old('email') }}" class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="email@example.com"></div>
                    <div><label for="password" class="block text-sm font-medium text-gray-700">كلمة المرور</label><input id="password" name="password" type="password" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="كلمة المرور"></div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center"><input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"><label for="remember" class="mr-2 block text-sm text-gray-900">تذكرني</label></div>
                </div>
                <div><button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">تسجيل الدخول</button></div>
                <div class="text-center"><a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">ليس لديك حساب؟ سجل الآن</a></div>
            </form>
        </div>
    </div>
</body>
</html>
