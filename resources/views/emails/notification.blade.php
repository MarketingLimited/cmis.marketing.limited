<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}" dir="{{ ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
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
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 3px solid;
        }
        .header.critical { border-color: #dc3545; }
        .header.high { border-color: #fd7e14; }
        .header.medium { border-color: #ffc107; }
        .header.low { border-color: #17a2b8; }
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .priority-badge.critical {
            background-color: #dc3545;
            color: #ffffff;
        }
        .priority-badge.high {
            background-color: #fd7e14;
            color: #ffffff;
        }
        .priority-badge.medium {
            background-color: #ffc107;
            color: #000;
        }
        .priority-badge.low {
            background-color: #17a2b8;
            color: #ffffff;
        }
        .header h1 {
            margin: 10px 0 5px 0;
            color: #2c3e50;
            font-size: 24px;
        }
        .notification-message {
            background-color: #f8f9fa;
            border-left: 4px solid;
            padding: 15px;
            margin: 20px 0;
            font-size: 16px;
        }
        .notification-message.critical { border-color: #dc3545; }
        .notification-message.high { border-color: #fd7e14; }
        .notification-message.medium { border-color: #ffc107; }
        .notification-message.low { border-color: #17a2b8; }
        .details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details td {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .details td:first-child {
            font-weight: 600;
            color: #2c3e50;
            width: 40%;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $priority ?? 'medium' }}">
            <span class="priority-badge {{ $priority ?? 'medium' }}">{{ ucfirst($priority ?? 'medium') }}</span>
            <h1>{{ $title }}</h1>
            <p style="color: #7f8c8d; margin: 5px 0 0 0;">
                {{ now()->format('M d, Y H:i:s') }}
            </p>
        </div>

        <div class="notification-message {{ $priority ?? 'medium' }}">
            {{ $message }}
        </div>

        @if(!empty($data))
        <div class="details">
            <table>
                @foreach($data as $key => $value)
                    @if(!is_array($value) && !is_object($value))
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $key)) }}:</td>
                        <td>{{ $value }}</td>
                    </tr>
                    @endif
                @endforeach
            </table>
        </div>
        @endif

        @if(!empty($actionUrl))
        <div style="text-align: center;">
            <a href="{{ url($actionUrl) }}" class="button">View Details</a>
        </div>
        @endif

        <div class="footer">
            <p>
                This is an automated notification from CMIS.<br>
                <a href="{{ url('/settings/notifications') }}" style="color: #4CAF50;">Manage Notification Settings</a>
            </p>
            <p style="margin-top: 10px;">
                &copy; {{ date('Y') }} CMIS - Cognitive Marketing Intelligence Suite
            </p>
        </div>
    </div>
</body>
</html>
