<!DOCTYPE html>
<html lang="{{ $locale ?? 'ar' }}" dir="{{ ($locale ?? 'ar') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('reports.analytics_report') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            background-color: #fff;
            direction: {{ ($locale ?? 'ar') === 'ar' ? 'rtl' : 'ltr' }};
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #4F46E5;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            color: #1F2937;
            margin-bottom: 10px;
        }

        .header .subtitle {
            color: #6B7280;
            font-size: 14px;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            background-color: #F3F4F6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .meta-info div {
            text-align: center;
        }

        .meta-info .label {
            color: #6B7280;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-info .value {
            color: #1F2937;
            font-size: 14px;
            font-weight: 600;
            margin-top: 4px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            color: #1F2937;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .stats-row {
            display: table-row;
        }

        .stat-card {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: 700;
            color: #4F46E5;
        }

        .stat-card .label {
            font-size: 11px;
            color: #6B7280;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th {
            background-color: #4F46E5;
            color: #fff;
            padding: 12px 8px;
            text-align: {{ ($locale ?? 'ar') === 'ar' ? 'right' : 'left' }};
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            padding: 10px 8px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 11px;
        }

        table tr:nth-child(even) {
            background-color: #F9FAFB;
        }

        .platform-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .platform-meta { background-color: #1877F2; color: #fff; }
        .platform-instagram { background-color: #E4405F; color: #fff; }
        .platform-twitter { background-color: #1DA1F2; color: #fff; }
        .platform-linkedin { background-color: #0A66C2; color: #fff; }
        .platform-tiktok { background-color: #000; color: #fff; }
        .platform-google { background-color: #4285F4; color: #fff; }
        .platform-snapchat { background-color: #FFFC00; color: #000; }

        .status-active { color: #059669; font-weight: 600; }
        .status-paused { color: #D97706; font-weight: 600; }
        .status-completed { color: #6B7280; font-weight: 600; }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            color: #9CA3AF;
            font-size: 10px;
        }

        .footer p {
            margin-bottom: 5px;
        }

        .chart-placeholder {
            background-color: #F3F4F6;
            border: 2px dashed #D1D5DB;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            color: #9CA3AF;
            margin-bottom: 20px;
        }

        .summary-box {
            background-color: #EEF2FF;
            border: 1px solid #C7D2FE;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .summary-box h4 {
            color: #4338CA;
            margin-bottom: 10px;
        }

        .summary-box ul {
            margin: 0;
            padding-{{ ($locale ?? 'ar') === 'ar' ? 'right' : 'left' }}: 20px;
        }

        .summary-box li {
            margin-bottom: 5px;
            color: #4B5563;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ __('reports.analytics_report') }}</h1>
            <p class="subtitle">{{ __('reports.comprehensive_performance_analysis') }}</p>
        </div>

        <!-- Meta Information -->
        <div class="meta-info">
            <div>
                <div class="label">{{ __('reports.report_period') }}</div>
                <div class="value">{{ $period }} {{ __('reports.days') }}</div>
            </div>
            <div>
                <div class="label">{{ __('reports.platform_filter') }}</div>
                <div class="value">{{ $platform ?? __('reports.all_platforms') }}</div>
            </div>
            <div>
                <div class="label">{{ __('reports.generated_at') }}</div>
                <div class="value">{{ $generatedAt->format('Y-m-d H:i') }}</div>
            </div>
        </div>

        <!-- Overview Section -->
        <div class="section">
            <h2 class="section-title">{{ __('reports.overview') }}</h2>

            <div class="stats-grid">
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="number">{{ number_format($data['overview']['total_posts'] ?? 0) }}</div>
                        <div class="label">{{ __('reports.total_posts') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">{{ number_format($data['overview']['total_comments'] ?? 0) }}</div>
                        <div class="label">{{ __('reports.total_comments') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">{{ number_format($data['overview']['total_messages'] ?? 0) }}</div>
                        <div class="label">{{ __('reports.total_messages') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">{{ number_format($data['overview']['active_campaigns'] ?? 0) }}</div>
                        <div class="label">{{ __('reports.active_campaigns') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts by Platform -->
        @if(!empty($data['posts_by_platform']))
        <div class="section">
            <h2 class="section-title">{{ __('reports.posts_by_platform') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('reports.platform') }}</th>
                        <th>{{ __('reports.post_count') }}</th>
                        <th>{{ __('reports.percentage') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalPosts = array_sum(array_column($data['posts_by_platform'], 'count'));
                    @endphp
                    @foreach($data['posts_by_platform'] as $platform)
                    <tr>
                        <td>
                            <span class="platform-badge platform-{{ strtolower($platform->platform ?? $platform['platform'] ?? 'unknown') }}">
                                {{ ucfirst($platform->platform ?? $platform['platform'] ?? 'Unknown') }}
                            </span>
                        </td>
                        <td>{{ number_format($platform->count ?? $platform['count'] ?? 0) }}</td>
                        <td>{{ $totalPosts > 0 ? number_format((($platform->count ?? $platform['count'] ?? 0) / $totalPosts) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Campaign Performance -->
        @if(!empty($data['campaigns']))
        <div class="section page-break">
            <h2 class="section-title">{{ __('reports.campaign_performance') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('reports.campaign_name') }}</th>
                        <th>{{ __('reports.platform') }}</th>
                        <th>{{ __('reports.status') }}</th>
                        <th>{{ __('reports.impressions') }}</th>
                        <th>{{ __('reports.clicks') }}</th>
                        <th>{{ __('reports.ctr') }}</th>
                        <th>{{ __('reports.spend') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['campaigns'] as $campaign)
                    @php
                        $impressions = $campaign->impressions ?? $campaign['impressions'] ?? 0;
                        $clicks = $campaign->clicks ?? $campaign['clicks'] ?? 0;
                        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
                    @endphp
                    <tr>
                        <td>{{ $campaign->campaign_name ?? $campaign['campaign_name'] ?? 'N/A' }}</td>
                        <td>
                            <span class="platform-badge platform-{{ strtolower($campaign->platform ?? $campaign['platform'] ?? 'unknown') }}">
                                {{ ucfirst($campaign->platform ?? $campaign['platform'] ?? 'Unknown') }}
                            </span>
                        </td>
                        <td>
                            <span class="status-{{ strtolower($campaign->status ?? $campaign['status'] ?? 'unknown') }}">
                                {{ ucfirst($campaign->status ?? $campaign['status'] ?? 'Unknown') }}
                            </span>
                        </td>
                        <td>{{ number_format($impressions) }}</td>
                        <td>{{ number_format($clicks) }}</td>
                        <td>{{ number_format($ctr, 2) }}%</td>
                        <td>${{ number_format($campaign->spend ?? $campaign['spend'] ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Top Performing Posts -->
        @if(!empty($data['top_posts']))
        <div class="section">
            <h2 class="section-title">{{ __('reports.top_performing_posts') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">{{ __('reports.content') }}</th>
                        <th>{{ __('reports.platform') }}</th>
                        <th>{{ __('reports.likes') }}</th>
                        <th>{{ __('reports.comments') }}</th>
                        <th>{{ __('reports.shares') }}</th>
                        <th>{{ __('reports.engagement') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['top_posts'] as $post)
                    @php
                        $likes = $post->likes ?? $post['likes'] ?? 0;
                        $comments = $post->comments ?? $post['comments'] ?? 0;
                        $shares = $post->shares ?? $post['shares'] ?? 0;
                        $engagement = $likes + $comments + $shares;
                    @endphp
                    <tr>
                        <td>{{ \Illuminate\Support\Str::limit($post->content ?? $post['content'] ?? '', 80) }}</td>
                        <td>
                            <span class="platform-badge platform-{{ strtolower($post->platform ?? $post['platform'] ?? 'unknown') }}">
                                {{ ucfirst($post->platform ?? $post['platform'] ?? 'Unknown') }}
                            </span>
                        </td>
                        <td>{{ number_format($likes) }}</td>
                        <td>{{ number_format($comments) }}</td>
                        <td>{{ number_format($shares) }}</td>
                        <td><strong>{{ number_format($engagement) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Daily Trends -->
        @if(!empty($data['daily_trends']))
        <div class="section">
            <h2 class="section-title">{{ __('reports.daily_trends') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('reports.date') }}</th>
                        <th>{{ __('reports.posts') }}</th>
                        <th>{{ __('reports.likes') }}</th>
                        <th>{{ __('reports.comments') }}</th>
                        <th>{{ __('reports.shares') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['daily_trends'] as $day)
                    <tr>
                        <td>{{ $day->date ?? $day['date'] ?? 'N/A' }}</td>
                        <td>{{ number_format($day->posts ?? $day['posts'] ?? 0) }}</td>
                        <td>{{ number_format($day->likes ?? $day['likes'] ?? 0) }}</td>
                        <td>{{ number_format($day->comments ?? $day['comments'] ?? 0) }}</td>
                        <td>{{ number_format($day->shares ?? $day['shares'] ?? 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>{{ __('reports.generated_by_cmis') }}</p>
            <p>&copy; {{ date('Y') }} CMIS - Cognitive Marketing Intelligence Suite</p>
            <p>{{ __('reports.confidential_report') }}</p>
        </div>
    </div>
</body>
</html>
