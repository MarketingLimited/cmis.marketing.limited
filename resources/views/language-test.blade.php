<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Language Switcher Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        .container {
            background: white;
            color: #333;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .status {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .status-item {
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .status-item strong {
            display: inline-block;
            width: 200px;
            color: #667eea;
        }
        .buttons {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }
        .btn {
            flex: 1;
            padding: 20px 40px;
            font-size: 20px;
            font-weight: bold;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-arabic {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-english {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn.active {
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.3);
        }
        .instructions {
            background: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .test-text {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Language Switcher Test Page</h1>

        <div class="success">
            <strong>✅ This page is working!</strong>
            <p>If you can see this page, it means the routes are working correctly.</p>
        </div>

        <div class="test-text">
            {{ __('navigation.welcome') }} - {{ app()->isLocale('ar') ? 'النظام يعمل بالعربية' : 'System is in English' }}
        </div>

        <div class="status">
            <h3>📊 Current Language Status:</h3>
            <div class="status-item">
                <strong>App Locale:</strong>
                <span style="font-size: 18px; font-weight: bold; color: {{ app()->getLocale() === 'ar' ? '#4caf50' : '#2196f3' }}">
                    {{ app()->getLocale() }} ({{ app()->getLocale() === 'ar' ? 'العربية' : 'English' }})
                </span>
            </div>
            <div class="status-item">
                <strong>Session Locale:</strong> {{ session('locale', 'not set') }}
            </div>
            <div class="status-item">
                <strong>User Locale:</strong>
                {{ auth()->check() ? (auth()->user()->locale ?? 'not set') : 'not authenticated' }}
            </div>
            <div class="status-item">
                <strong>Direction:</strong> {{ app()->isLocale('ar') ? 'RTL (Right-to-Left)' : 'LTR (Left-to-Right)' }}
            </div>
            <div class="status-item">
                <strong>User:</strong> {{ auth()->check() ? auth()->user()->email : 'Guest' }}
            </div>
        </div>

        <div class="instructions">
            <h3>🧪 Test Instructions:</h3>
            <ol>
                <li><strong>Click one of the buttons below</strong> to switch languages</li>
                <li><strong>Watch the page reload</strong> - the status above should change</li>
                <li><strong>If successful:</strong> The "App Locale" will change and text will update</li>
                <li><strong>If nothing changes:</strong> There's a backend issue with session/database</li>
            </ol>
        </div>

        <div class="buttons">
            <a href="{{ route('language.switch.get', 'ar') }}"
               class="btn btn-arabic {{ app()->isLocale('ar') ? 'active' : '' }}"
               onclick="console.log('Switching to Arabic via GET...')">
                <span style="font-size: 30px;">🇸🇦</span>
                <span>التبديل إلى العربية</span>
            </a>

            <a href="{{ route('language.switch.get', 'en') }}"
               class="btn btn-english {{ app()->isLocale('en') ? 'active' : '' }}"
               onclick="console.log('Switching to English via GET...')">
                <span style="font-size: 30px;">🇬🇧</span>
                <span>Switch to English</span>
            </a>
        </div>

        <div class="instructions">
            <h3>🔗 Direct URLs (Copy & Paste in Browser):</h3>
            <ul style="font-family: 'Courier New', monospace; word-break: break-all;">
                <li><strong>Arabic:</strong> <code>{{ url('/language/ar') }}</code></li>
                <li><strong>English:</strong> <code>{{ url('/language/en') }}</code></li>
                <li><strong>Debug Info:</strong> <code>{{ url('/debug-locale') }}</code></li>
            </ul>
        </div>

        <div class="status">
            <h3>🔍 What Should Happen:</h3>
            <ul>
                <li><strong>Step 1:</strong> Click "Switch to English" button</li>
                <li><strong>Step 2:</strong> Page should reload</li>
                <li><strong>Step 3:</strong> "App Locale" above should change from "ar" to "en"</li>
                <li><strong>Step 4:</strong> Test text should change to English</li>
                <li><strong>Step 5:</strong> Active button should have a blue border</li>
            </ul>
        </div>

        <div class="instructions" style="background: #fce4ec; border-left-color: #e91e63;">
            <h3>📝 Report to Developer:</h3>
            <p>After testing, report the following:</p>
            <ol>
                <li>Did the "App Locale" change when you clicked the button?</li>
                <li>Did the test text update?</li>
                <li>What does <code>/debug-locale</code> show after switching?</li>
                <li>If logged in: Does language persist after navigating to other pages?</li>
            </ol>
        </div>

        <p style="text-align: center; margin-top: 40px; color: #999;">
            <a href="{{ url('/') }}" style="color: #667eea; text-decoration: none; font-weight: bold;">← Back to Dashboard</a>
        </p>
    </div>

    <script>
        console.log('=== LANGUAGE TEST PAGE ===');
        console.log('Current locale:', '{{ app()->getLocale() }}');
        console.log('Session locale:', '{{ session('locale', 'not set') }}');
        console.log('User locale:', '{{ auth()->check() ? (auth()->user()->locale ?? 'not set') : 'not authenticated' }}');
        console.log('========================');
    </script>
</body>
</html>
