<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locale Diagnostic Page</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-align: center;
        }
        .section {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .info-label {
            font-weight: bold;
            color: #667eea;
        }
        .info-value {
            color: #333;
            word-break: break-all;
        }
        .locale-ar { color: #4caf50; font-weight: bold; }
        .locale-en { color: #2196f3; font-weight: bold; }
        .status-true { color: #4caf50; }
        .status-false { color: #f44336; }
        .buttons {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: all 0.3s;
        }
        .btn-ar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-en {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .test-text {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 24px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Complete Locale Diagnostic</h1>

        {{-- Current State --}}
        <div class="section">
            <h2>📊 Current Locale State</h2>
            <div class="info-grid">
                <div class="info-label">App Locale:</div>
                <div class="info-value">
                    <span class="locale-{{ app()->getLocale() }}">{{ app()->getLocale() }}</span>
                    ({{ app()->getLocale() === 'ar' ? 'العربية - Arabic' : 'English - الإنجليزية' }})
                </div>

                <div class="info-label">Direction:</div>
                <div class="info-value">{{ app()->isLocale('ar') ? 'RTL (Right-to-Left)' : 'LTR (Left-to-Right)' }}</div>

                <div class="info-label">Session Locale:</div>
                <div class="info-value">{{ session('locale', '❌ NOT SET') }}</div>

                <div class="info-label">Config Default:</div>
                <div class="info-value">{{ config('app.locale', '❌ NOT SET') }}</div>

                <div class="info-label">Session ID:</div>
                <div class="info-value">{{ session()->getId() }}</div>
            </div>
        </div>

        {{-- Authentication Info --}}
        <div class="section">
            <h2>👤 Authentication Information</h2>
            <div class="info-grid">
                <div class="info-label">Is Authenticated:</div>
                <div class="info-value">
                    <span class="status-{{ auth()->check() ? 'true' : 'false' }}">
                        {{ auth()->check() ? '✅ YES' : '❌ NO (Guest)' }}
                    </span>
                </div>

                @if(auth()->check())
                    <div class="info-label">User ID:</div>
                    <div class="info-value">{{ auth()->id() }}</div>

                    <div class="info-label">User Email:</div>
                    <div class="info-value">{{ auth()->user()->email }}</div>

                    <div class="info-label">User Name:</div>
                    <div class="info-value">{{ auth()->user()->name }}</div>

                    <div class="info-label">User Locale (DB):</div>
                    <div class="info-value">
                        <span class="locale-{{ auth()->user()->locale ?? 'null' }}">
                            {{ auth()->user()->locale ?? '❌ NULL (not set in database)' }}
                        </span>
                    </div>

                    <div class="info-label">Current Org ID:</div>
                    <div class="info-value">{{ auth()->user()->current_org_id ?? '❌ NULL' }}</div>

                    @if(auth()->user()->current_org_id)
                        @php
                            $org = \App\Models\Core\Org::find(auth()->user()->current_org_id);
                        @endphp
                        @if($org)
                            <div class="info-label">Org Name:</div>
                            <div class="info-value">{{ $org->name }}</div>

                            <div class="info-label">Org Default Locale:</div>
                            <div class="info-value">{{ $org->default_locale ?? '❌ NULL' }}</div>
                        @endif
                    @endif
                @else
                    <div class="info-label">User:</div>
                    <div class="info-value">👤 Not logged in (Guest)</div>
                @endif
            </div>
        </div>

        {{-- Browser Info --}}
        <div class="section">
            <h2>🌐 Browser Information</h2>
            <div class="info-grid">
                <div class="info-label">Accept-Language:</div>
                <div class="info-value">{{ request()->header('Accept-Language', '❌ NOT SET') }}</div>

                <div class="info-label">User-Agent:</div>
                <div class="info-value">{{ request()->header('User-Agent', '❌ NOT SET') }}</div>

                <div class="info-label">Request URL:</div>
                <div class="info-value">{{ request()->fullUrl() }}</div>

                <div class="info-label">Request Method:</div>
                <div class="info-value">{{ request()->method() }}</div>
            </div>
        </div>

        {{-- Translation Test --}}
        <div class="section">
            <h2>🔤 Translation Test</h2>
            <div class="test-text">
                {{ __('navigation.welcome') }}
            </div>
            <p style="margin-top: 10px; color: #666;">
                ☝️ This should show:
                <strong>{{ app()->isLocale('ar') ? '"مرحباً"' : '"Welcome"' }}</strong>
                based on current locale
            </p>
        </div>

        {{-- Language Switch Buttons --}}
        <div class="buttons">
            <a href="{{ route('language.switch.get', 'ar') }}" class="btn btn-ar">
                🇸🇦 Switch to Arabic (العربية)
            </a>
            <a href="{{ route('language.switch.get', 'en') }}" class="btn btn-en">
                🇬🇧 Switch to English (الإنجليزية)
            </a>
        </div>

        {{-- Additional Debug Info --}}
        <div class="section" style="margin-top: 30px;">
            <h2>🔧 Additional Debug Information</h2>
            <div class="info-grid">
                <div class="info-label">PHP Version:</div>
                <div class="info-value">{{ phpversion() }}</div>

                <div class="info-label">Laravel Version:</div>
                <div class="info-value">{{ app()->version() }}</div>

                <div class="info-label">Environment:</div>
                <div class="info-value">{{ app()->environment() }}</div>

                <div class="info-label">Timezone:</div>
                <div class="info-value">{{ config('app.timezone') }}</div>

                <div class="info-label">Current Time:</div>
                <div class="info-value">{{ now() }}</div>
            </div>
        </div>

        <p style="text-align: center; margin-top: 40px; color: #999;">
            <a href="{{ url('/') }}" style="color: #667eea; text-decoration: none; font-weight: bold;">
                ← Back to Homepage
            </a>
        </p>
    </div>

    <script>
        console.log('=== LOCALE DIAGNOSTIC PAGE ===');
        console.log('App Locale:', '{{ app()->getLocale() }}');
        console.log('Session Locale:', '{{ session('locale', 'NOT SET') }}');
        console.log('User Authenticated:', {{ auth()->check() ? 'true' : 'false' }});
        @if(auth()->check())
        console.log('User Email:', '{{ auth()->user()->email }}');
        console.log('User Locale (DB):', '{{ auth()->user()->locale ?? 'NULL' }}');
        console.log('User Org ID:', '{{ auth()->user()->current_org_id ?? 'NULL' }}');
        @endif
        console.log('Browser Accept-Language:', navigator.language);
        console.log('=========================');
    </script>
</body>
</html>
