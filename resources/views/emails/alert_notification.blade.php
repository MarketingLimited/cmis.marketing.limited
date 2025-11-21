<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMIS Alert: {{ $rule->name }}</title>
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
        .severity-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .severity-badge.critical {
            background-color: #dc3545;
            color: #ffffff;
        }
        .severity-badge.high {
            background-color: #fd7e14;
            color: #ffffff;
        }
        .severity-badge.medium {
            background-color: #ffc107;
            color: #000;
        }
        .severity-badge.low {
            background-color: #17a2b8;
            color: #ffffff;
        }
        .header h1 {
            margin: 10px 0 5px 0;
            color: #2c3e50;
            font-size: 24px;
        }
        .alert-message {
            background-color: #f8f9fa;
            border-left: 4px solid;
            padding: 15px;
            margin: 20px 0;
            font-size: 16px;
        }
        .alert-message.critical { border-color: #dc3545; }
        .alert-message.high { border-color: #fd7e14; }
        .alert-message.medium { border-color: #ffc107; }
        .alert-message.low { border-color: #17a2b8; }
        .metrics {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            gap: 20px;
        }
        .metric {
            text-align: center;
            flex: 1;
        }
        .metric-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            display: block;
        }
        .metric-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
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
        <div class="header {{ $severity }}">
            <span class="severity-badge {{ $severity }}">{{ strtoupper($severity) }} ALERT</span>
            <h1>{{ $rule->name }}</h1>
            <p style="color: #7f8c8d; margin: 5px 0 0 0;">
                Triggered at {{ $triggeredAt }}
            </p>
        </div>

        <div class="alert-message {{ $severity }}">
            {{ $message }}
        </div>

        <div class="metrics">
            <div class="metric">
                <span class="metric-value">{{ number_format($actualValue, 2) }}</span>
                <span class="metric-label">Actual Value</span>
            </div>
            <div class="metric">
                <span class="metric-value">{{ number_format($threshold, 2) }}</span>
                <span class="metric-label">Threshold</span>
            </div>
        </div>

        <div class="details">
            <table>
                <tr>
                    <td>Alert Rule:</td>
                    <td>{{ $rule->name }}</td>
                </tr>
                <tr>
                    <td>Metric:</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $alert->metric)) }}</td>
                </tr>
                <tr>
                    <td>Entity Type:</td>
                    <td>{{ ucfirst($entityType) }}</td>
                </tr>
                @if($entityId)
                <tr>
                    <td>Entity ID:</td>
                    <td>{{ $entityId }}</td>
                </tr>
                @endif
                <tr>
                    <td>Condition:</td>
                    <td>{{ $alert->condition }}</td>
                </tr>
                <tr>
                    <td>Severity:</td>
                    <td>{{ ucfirst($severity) }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>{{ ucfirst($alert->status) }}</td>
                </tr>
            </table>
        </div>

        <div style="text-align: center;">
            <a href="#" class="button">View in Dashboard</a>
        </div>

        <div style="margin-top: 30px; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
            <strong>📋 Recommended Actions:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                @if($severity === 'critical')
                    <li>Immediate action required - Review entity performance</li>
                    <li>Check for any system issues or anomalies</li>
                    <li>Consider pausing affected campaigns if necessary</li>
                @elseif($severity === 'high')
                    <li>Review entity metrics within next 24 hours</li>
                    <li>Analyze recent changes that may have caused this alert</li>
                    <li>Prepare corrective action plan</li>
                @else
                    <li>Review during next scheduled check-in</li>
                    <li>Monitor for continued trend</li>
                    <li>Document findings for future reference</li>
                @endif
            </ul>
        </div>

        <div class="footer">
            <p>
                This is an automated alert from CMIS Analytics.<br>
                <a href="#" style="color: #4CAF50;">Manage Alert Settings</a> |
                <a href="#" style="color: #4CAF50;">View All Alerts</a>
            </p>
            <p style="margin-top: 10px;">
                © {{ date('Y') }} CMIS - Cognitive Marketing Information System
            </p>
        </div>
    </div>
</body>
</html>
