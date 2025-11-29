<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}" dir="{{ ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.one_time_report.subject') }}</title>
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
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        .content {
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box strong {
            color: #2c3e50;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #45a049;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #7f8c8d;
            text-align: center;
        }
        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('emails.one_time_report.title') }}</h1>
            <p>{{ __('emails.one_time_report.report_type', ['type' => ucfirst(str_replace('_', ' ', $reportType))]) }}</p>
        </div>

        <div class="content">
            <p>{{ __('emails.one_time_report.hello') }}</p>

            <p>{{ __('emails.one_time_report.ready_message') }}</p>

            <div class="info-box">
                <p><strong>{{ __('emails.one_time_report.report_type_label') }}:</strong> {{ ucfirst(str_replace('_', ' ', $reportType)) }}</p>
                <p><strong>{{ __('emails.one_time_report.generated') }}:</strong> {{ $generatedAt }}</p>
                @if($expiresAt)
                <p><strong>{{ __('emails.one_time_report.expires') }}:</strong> {{ $expiresAt }}</p>
                @endif
            </div>

            @if($fileUrl)
            <p>{{ __('emails.one_time_report.click_to_download') }}</p>

            <div style="text-align: center;">
                <a href="{{ $fileUrl }}" class="button">{{ __('emails.one_time_report.download_button') }}</a>
            </div>

            <p style="font-size: 14px; color: #7f8c8d; margin-top: 20px;">
                <strong>{{ __('emails.one_time_report.note') }}</strong> {{ __('emails.one_time_report.expires_note') }}
            </p>
            @endif

            <p>{{ __('emails.one_time_report.additional_reports') }}</p>
        </div>

        <div class="footer">
            <p>
                {{ __('emails.one_time_report.automated_message') }}<br>
                <a href="#">{{ __('emails.one_time_report.analytics_dashboard') }}</a> | <a href="#">{{ __('emails.one_time_report.help_center') }}</a>
            </p>
            <p style="margin-top: 10px;">
                © {{ date('Y') }} {{ __('emails.one_time_report.copyright') }}
            </p>
        </div>
    </div>
</body>
</html>
