<!DOCTYPE html>
<html lang="{{ $user->preferred_locale ?? app()->getLocale() }}" dir="{{ ($user->preferred_locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.password_reset_email_title') }}</title>
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
            border-bottom: 3px solid #4F46E5;
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
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 35px;
            background-color: #4F46E5;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .button:hover {
            background-color: #4338CA;
        }
        .warning {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 5px 0;
            color: #92400E;
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
        .url-fallback {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            word-break: break-all;
        }
        .url-fallback p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        .url-fallback a {
            color: #4F46E5;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('auth.password_reset_email_title') }}</h1>
        </div>

        <div class="content">
            <p><strong>{{ __('auth.password_reset_email_greeting') }}{{ $user->name ? ' ' . $user->name : '' }},</strong></p>

            <p>{{ __('auth.password_reset_email_body') }}</p>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">{{ __('auth.password_reset_email_button') }}</a>
            </div>

            <div class="warning">
                <p><strong>⏰</strong> {{ __('auth.password_reset_email_expire') }}</p>
            </div>

            <p>{{ __('auth.password_reset_email_ignore') }}</p>

            <div class="url-fallback">
                <p>{{ __('auth.password_reset_email_footer') }}</p>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} CMIS - Cognitive Marketing Intelligence Suite</p>
        </div>
    </div>
</body>
</html>
