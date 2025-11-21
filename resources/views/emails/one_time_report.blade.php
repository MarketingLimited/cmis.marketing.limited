<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMIS Analytics Report</title>
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
            <h1>Analytics Report</h1>
            <p>{{ ucfirst(str_replace('_', ' ', $reportType)) }} Report</p>
        </div>

        <div class="content">
            <p>Hello,</p>

            <p>Your requested analytics report has been generated and is ready for download.</p>

            <div class="info-box">
                <p><strong>Report Type:</strong> {{ ucfirst(str_replace('_', ' ', $reportType)) }}</p>
                <p><strong>Generated:</strong> {{ $generatedAt }}</p>
                @if($expiresAt)
                <p><strong>Expires:</strong> {{ $expiresAt }}</p>
                @endif
            </div>

            @if($fileUrl)
            <p>Click the button below to download your report:</p>

            <div style="text-align: center;">
                <a href="{{ $fileUrl }}" class="button">Download Report</a>
            </div>

            <p style="font-size: 14px; color: #7f8c8d; margin-top: 20px;">
                <strong>Note:</strong> This download link will expire in 7 days for security purposes.
            </p>
            @endif

            <p>If you need to generate additional reports or schedule automated delivery, please visit the Analytics Dashboard.</p>
        </div>

        <div class="footer">
            <p>
                This report was generated on request from CMIS Analytics.<br>
                <a href="#">Analytics Dashboard</a> | <a href="#">Help Center</a>
            </p>
            <p style="margin-top: 10px;">
                © {{ date('Y') }} CMIS - Cognitive Marketing Information System
            </p>
        </div>
    </div>
</body>
</html>
