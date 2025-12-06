<!DOCTYPE html>
<html lang="{{ $user->preferred_locale ?? app()->getLocale() }}" dir="{{ ($user->preferred_locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('backup.verification_code_subject') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #DC2626;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin: 15px 0;
            color: #555;
        }
        .code-container {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #DC2626;
        }
        .verification-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #DC2626;
            font-family: monospace;
        }
        .warning {
            background-color: #FEF2F2;
            border-left: 4px solid #DC2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 5px 0;
            color: #991B1B;
            font-size: 14px;
        }
        .info {
            background-color: #EFF6FF;
            border-left: 4px solid #3B82F6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info p {
            margin: 5px 0;
            color: #1E40AF;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #7f8c8d;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('backup.verification_code_title') }}</h1>
        </div>

        <div class="content">
            <p><strong>{{ __('backup.verification_code_greeting') }}{{ $user->name ? ' ' . $user->name : '' }},</strong></p>

            <p>{{ __('backup.verification_code_intro') }}</p>

            <div class="code-container">
                <div class="verification-code">{{ $code }}</div>
            </div>

            <div class="warning">
                <p><strong>{{ __('backup.verification_code_warning_title') }}</strong></p>
                <p>{{ __('backup.verification_code_warning_body') }}</p>
            </div>

            <div class="info">
                <p><strong>{{ __('backup.verification_code_expires') }}</strong> {{ $expiresIn }} {{ __('backup.minutes') }}</p>
                <p><strong>{{ __('backup.restore_code') }}:</strong> {{ $restore->restore_code }}</p>
            </div>

            <p>{{ __('backup.verification_code_ignore') }}</p>
        </div>

        <div class="footer">
            <p>{{ __('backup.verification_code_footer') }}</p>
            <p>&copy; {{ date('Y') }} CMIS - Cognitive Marketing Intelligence Suite</p>
        </div>
    </div>
</body>
</html>
