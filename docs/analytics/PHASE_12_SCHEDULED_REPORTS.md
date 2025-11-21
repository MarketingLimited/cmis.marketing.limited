# Phase 12: Scheduled Reports & Email Delivery

**Version:** 1.0
**Last Updated:** 2025-11-21

---

## Overview

Phase 12 adds automated report generation and email delivery capabilities, building on the Report Generator Service from Phase 11. Users can schedule recurring reports (daily, weekly, monthly, quarterly) with automatic email delivery to multiple recipients.

---

## Features

### 1. Scheduled Reports

**File:** `app/Models/Analytics/ScheduledReport.php`

Automated report delivery on configurable schedules.

**Key Features:**
- Multiple frequency options (daily, weekly, monthly, quarterly)
- Timezone-aware scheduling
- Customizable delivery times
- Multiple email recipients
- Active/inactive status control
- Automatic next-run calculation

**Configuration Options:**
```json
{
  "name": "Weekly Executive Summary",
  "report_type": "organization",
  "frequency": "weekly",
  "format": "pdf",
  "recipients": ["ceo@company.com", "cmo@company.com"],
  "timezone": "America/New_York",
  "delivery_time": "09:00:00",
  "day_of_week": 1,
  "is_active": true,
  "config": {
    "include_insights": true,
    "date_range": {
      "start": "last_week_start",
      "end": "last_week_end"
    }
  }
}
```

**Frequency Types:**
- **Daily**: Runs every day at specified time
- **Weekly**: Runs on specified day of week (1=Monday, 7=Sunday)
- **Monthly**: Runs on specified day of month (1-31)
- **Quarterly**: Runs every 3 months on specified day

---

### 2. Email Report Service

**File:** `app/Services/Analytics/EmailReportService.php`

Handles report generation and email delivery.

**Methods:**

#### sendScheduledReport(ScheduledReport $schedule)
Processes a scheduled report execution:
- Generates report using ReportGeneratorService
- Creates execution log
- Sends emails to all recipients
- Updates schedule with next run time
- Handles failures gracefully

```php
$log = $emailService->sendScheduledReport($schedule);
// Returns: ReportExecutionLog with status, emails sent/failed, etc.
```

#### sendOneTimeReport(string $orgId, string $reportType, array $config, array $recipients)
Sends an ad-hoc report without creating a schedule:

```php
$result = $emailService->sendOneTimeReport(
    $orgId,
    'campaign',
    ['campaign_id' => '...', 'format' => 'pdf'],
    ['user@example.com']
);
```

#### resendReport(ReportExecutionLog $log, array $recipients)
Retries failed email deliveries:

```php
$result = $emailService->resendReport($log, ['retry@example.com']);
```

---

### 3. Report Execution Logs

**File:** `app/Models/Analytics/ReportExecutionLog.php`

Tracks every report execution with detailed metrics:

**Tracked Data:**
- Execution timestamp
- Status (success/failed/partial)
- File path and URL
- File size
- Recipients count
- Emails sent/failed counts
- Error messages
- Execution time in milliseconds
- Custom metadata

**Usage:**
```php
// Get execution history
$logs = ReportExecutionLog::where('schedule_id', $scheduleId)
    ->recent(30)  // Last 30 days
    ->successful()
    ->get();

// Calculate success rate
$successRate = $log->getSuccessRate(); // Returns percentage
```

---

### 4. Report Templates

**File:** `app/Models/Analytics/ReportTemplate.php`

Pre-configured report templates that users can apply to create schedules.

**Template Types:**
- **System Templates**: Pre-built by CMIS (cannot be deleted)
- **Public Templates**: Shared across all organizations
- **Private Templates**: User-specific configurations

**Categories:**
- **Marketing**: Campaign performance, ROI analysis
- **Sales**: Conversion tracking, lead analytics
- **Executive**: High-level summaries, KPI dashboards
- **Custom**: User-defined templates

**Example Template:**
```json
{
  "name": "Monthly Marketing Report",
  "category": "marketing",
  "report_type": "organization",
  "default_config": {
    "format": "pdf",
    "include_insights": true,
    "sections": ["overview", "campaigns", "roi", "top_performers"],
    "charts": ["performance_trend", "roi_comparison"]
  },
  "is_public": true,
  "usage_count": 47
}
```

---

### 5. Automated Job Processing

**File:** `app/Jobs/ProcessScheduledReportJob.php`

Queue job that processes individual report executions.

**Features:**
- 3 retry attempts with 5-minute delays
- Dedicated 'reports' queue
- RLS context initialization
- Detailed logging
- Failure notification hooks

**File:** `app/Console/Commands/ProcessScheduledReports.php`

Laravel command that checks for due reports and dispatches jobs.

**Usage:**
```bash
# Process all due reports
php artisan reports:process-scheduled

# Process specific schedule
php artisan reports:process-scheduled --schedule=UUID

# Force process all active schedules
php artisan reports:process-scheduled --force
```

**Laravel Scheduler Configuration:**
Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Check for due reports every minute
    $schedule->command('reports:process-scheduled')
        ->everyMinute()
        ->withoutOverlapping()
        ->runInBackground();
}
```

---

### 6. Email Templates

**Files:**
- `resources/views/emails/scheduled_report.blade.php`
- `resources/views/emails/one_time_report.blade.php`

Professional HTML email templates with:
- Responsive design
- Report metadata display
- Download button
- Expiration notice
- Branding elements

---

### 7. Frontend Component

**File:** `resources/js/components/scheduledReports.js`

Alpine.js component for managing schedules through the UI.

**Features:**
- Create/edit/delete schedules
- View execution history
- Toggle active status
- Apply templates
- Add multiple recipients
- Filter by frequency/type/status
- Pagination support

**Usage:**
```html
<div x-data="scheduledReports()" data-org-id="{{ $orgId }}">
    <!-- Schedules list -->
    <template x-for="schedule in schedules">
        <div>
            <h3 x-text="schedule.name"></h3>
            <button @click="toggleActive(schedule)">
                <span x-text="schedule.is_active ? 'Deactivate' : 'Activate'"></span>
            </button>
        </div>
    </template>

    <!-- Create modal -->
    <div x-show="showCreateModal">
        <!-- Form fields -->
    </div>
</div>
```

---

## API Reference

### Scheduled Reports Controller

**File:** `app/Http/Controllers/Analytics/ScheduledReportsController.php`

All endpoints require `auth:sanctum` middleware and respect multi-tenancy via RLS.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/orgs/{org_id}/analytics/scheduled-reports` | GET | List all schedules |
| `/api/orgs/{org_id}/analytics/scheduled-reports` | POST | Create schedule |
| `/api/orgs/{org_id}/analytics/scheduled-reports/{schedule_id}` | GET | Get schedule details |
| `/api/orgs/{org_id}/analytics/scheduled-reports/{schedule_id}` | PUT | Update schedule |
| `/api/orgs/{org_id}/analytics/scheduled-reports/{schedule_id}` | DELETE | Delete schedule |
| `/api/orgs/{org_id}/analytics/scheduled-reports/{schedule_id}/history` | GET | Get execution history |
| `/api/orgs/{org_id}/analytics/scheduled-reports/from-template/{template_id}` | POST | Create from template |
| `/api/orgs/{org_id}/analytics/send-report` | POST | Send one-time report |
| `/api/analytics/report-templates` | GET | List templates |
| `/api/analytics/report-templates` | POST | Create template |

---

## Database Schema

### scheduled_reports Table

```sql
CREATE TABLE cmis.scheduled_reports (
    schedule_id UUID PRIMARY KEY,
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    user_id UUID NOT NULL REFERENCES cmis.users(user_id),
    name VARCHAR(255) NOT NULL,
    report_type VARCHAR(50) NOT NULL,  -- campaign, organization, comparison, attribution
    frequency VARCHAR(20) NOT NULL,     -- daily, weekly, monthly, quarterly
    config JSONB NOT NULL,
    recipients JSONB NOT NULL,          -- Array of email addresses
    format VARCHAR(10) NOT NULL,        -- pdf, xlsx, csv, json
    timezone VARCHAR(50) DEFAULT 'UTC',
    delivery_time TIME DEFAULT '09:00:00',
    day_of_week INT,                    -- 1-7 for weekly
    day_of_month INT,                   -- 1-31 for monthly
    is_active BOOLEAN DEFAULT true,
    last_run_at TIMESTAMP,
    next_run_at TIMESTAMP,
    run_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### report_execution_logs Table

```sql
CREATE TABLE cmis.report_execution_logs (
    log_id UUID PRIMARY KEY,
    schedule_id UUID NOT NULL REFERENCES cmis.scheduled_reports(schedule_id),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    executed_at TIMESTAMP NOT NULL,
    status VARCHAR(20) NOT NULL,        -- success, failed, partial
    file_path TEXT,
    file_url TEXT,
    file_size INT,
    recipients_count INT DEFAULT 0,
    emails_sent INT DEFAULT 0,
    emails_failed INT DEFAULT 0,
    error_message TEXT,
    metadata JSONB,
    execution_time_ms INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### report_templates Table

```sql
CREATE TABLE cmis.report_templates (
    template_id UUID PRIMARY KEY,
    created_by UUID REFERENCES cmis.users(user_id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    report_type VARCHAR(50) NOT NULL,
    default_config JSONB NOT NULL,
    category VARCHAR(50) DEFAULT 'custom',  -- marketing, sales, executive, custom
    is_public BOOLEAN DEFAULT false,
    is_system BOOLEAN DEFAULT false,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Indexes:**
- `scheduled_reports`: org_id, user_id, (is_active, next_run_at), frequency
- `report_execution_logs`: schedule_id, org_id, status, executed_at
- `report_templates`: created_by, is_public, category

**RLS Policies:**
- Both `scheduled_reports` and `report_execution_logs` have org isolation policies
- Users can only access their organization's data

---

## Use Cases

### 1. Weekly Executive Summary

```javascript
// Create weekly report sent every Monday at 9 AM
const schedule = await fetch('/api/orgs/${orgId}/analytics/scheduled-reports', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        name: 'Weekly Executive Summary',
        report_type: 'organization',
        frequency: 'weekly',
        format: 'pdf',
        recipients: ['ceo@company.com', 'cmo@company.com', 'cfo@company.com'],
        timezone: 'America/New_York',
        delivery_time: '09:00:00',
        day_of_week: 1,  // Monday
        config: {
            include_insights: true,
            sections: ['overview', 'top_performers', 'roi_analysis']
        }
    })
});
```

### 2. Daily Campaign Monitoring

```javascript
// Monitor specific campaign with daily reports
const schedule = await fetch('/api/orgs/${orgId}/analytics/scheduled-reports', {
    method: 'POST',
    body: JSON.stringify({
        name: 'Black Friday Campaign - Daily',
        report_type: 'campaign',
        frequency: 'daily',
        format: 'xlsx',
        recipients: ['marketing@company.com'],
        delivery_time: '08:00:00',
        config: {
            campaign_id: 'campaign-uuid',
            include_insights: true,
            date_range: { start: 'yesterday', end: 'yesterday' }
        }
    })
});
```

### 3. Monthly Board Report

```javascript
// End-of-month comprehensive report
const schedule = await fetch('/api/orgs/${orgId}/analytics/scheduled-reports', {
    method: 'POST',
    body: JSON.stringify({
        name: 'Monthly Board Report',
        report_type: 'organization',
        frequency: 'monthly',
        format: 'pdf',
        recipients: ['board@company.com'],
        day_of_month: 1,  // First day of next month
        delivery_time: '10:00:00',
        config: {
            include_insights: true,
            date_range: { start: 'last_month_start', end: 'last_month_end' }
        }
    })
});
```

### 4. Ad-Hoc Report Delivery

```javascript
// Send immediate one-time report
const result = await fetch('/api/orgs/${orgId}/analytics/send-report', {
    method: 'POST',
    body: JSON.stringify({
        report_type: 'comparison',
        format: 'xlsx',
        recipients: ['analyst@company.com'],
        config: {
            campaign_ids: ['campaign1-uuid', 'campaign2-uuid'],
            date_range: { start: '2025-11-01', end: '2025-11-21' }
        }
    })
});
```

### 5. Create from Template

```javascript
// Use pre-built template
const schedule = await fetch(
    '/api/orgs/${orgId}/analytics/scheduled-reports/from-template/${templateId}',
    {
        method: 'POST',
        body: JSON.stringify({
            name: 'Q4 Marketing Report',
            frequency: 'weekly',
            format: 'pdf',
            recipients: ['team@company.com'],
            config_overrides: {
                // Override template defaults
                date_range: { start: 'last_7_days', end: 'today' }
            }
        })
    }
);
```

---

## Queue Configuration

### config/queue.php

Add dedicated reports queue:

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 600,  // 10 minutes for reports
        'block_for' => null,
    ],
],
```

### Supervisor Configuration

`/etc/supervisor/conf.d/laravel-reports-worker.conf`:

```ini
[program:laravel-reports-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=reports --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/reports-worker.log
stopwaitsecs=3600
```

---

## Error Handling

### Failed Deliveries

When emails fail to send:
1. Individual failures are logged in `emails_failed` count
2. Status set to `partial` if some succeed, `failed` if all fail
3. Error message stored in execution log
4. Job retries up to 3 times (5-minute delays)
5. After max retries, `failed()` method can trigger admin notification

### Retry Failed Deliveries

```php
// Get failed logs
$failedLogs = ReportExecutionLog::failed()
    ->where('schedule_id', $scheduleId)
    ->recent(7)
    ->get();

// Retry delivery
foreach ($failedLogs as $log) {
    $result = $emailService->resendReport($log);
}
```

### Schedule Issues

If a schedule fails consistently:
1. Check `report_execution_logs` for error patterns
2. Verify campaign/config still exists
3. Check email recipients are valid
4. Review execution time (may timeout on large reports)
5. Consider deactivating and reconfiguring

---

## Performance Considerations

### Large Reports

For organization reports with many campaigns:
- Consider generating during off-peak hours
- Use `quarterly` frequency instead of `daily`
- Generate CSV/JSON instead of PDF for better performance
- Implement pagination in report data

### Email Delivery

- Batch email sending (Laravel Mail queue)
- Use dedicated SMTP server for reports
- Monitor bounce rates
- Implement email verification before adding recipients

### Storage Management

Reports expire after 7 days (configurable):
- Storage clean up via scheduled command
- Cloud storage (S3) recommended for production
- Monitor disk usage in `storage/reports/`

---

## Security

### Access Control

- All endpoints require `auth:sanctum` authentication
- RLS policies enforce organization isolation
- Users can only manage their org's schedules
- Template creation requires authentication

### Email Security

- Recipient validation (email format check)
- Rate limiting on email sending
- Attachment size limits
- SPF/DKIM configuration recommended

### Report Data

- Generated reports stored temporarily (7-day expiration)
- URLs include random tokens
- HTTPS required for download links
- No sensitive data in email body (download only)

---

## Integration with Previous Phases

Phase 12 builds on:
- **Phase 5-7**: Backend analytics APIs
- **Phase 8**: Frontend components
- **Phase 9**: Laravel integration
- **Phase 10**: Testing & documentation
- **Phase 11**: Report Generator Service, AI Insights

**New Additions:**
- Scheduled automation
- Email delivery system
- Execution tracking
- Template library
- Queue-based processing

---

## Benefits

1. **Automation**: Eliminate manual report generation
2. **Consistency**: Reports delivered on predictable schedule
3. **Distribution**: Multiple stakeholders receive same data
4. **History**: Track all executions with metrics
5. **Flexibility**: Multiple formats and configurations
6. **Reliability**: Retry logic and error handling
7. **Scalability**: Queue-based processing handles load
8. **Templates**: Reusable configurations

---

## Monitoring & Maintenance

### Health Checks

```bash
# Check recent execution status
SELECT
    status,
    COUNT(*) as count,
    AVG(execution_time_ms) as avg_time,
    AVG(CAST(emails_sent AS FLOAT) / recipients_count * 100) as success_rate
FROM cmis.report_execution_logs
WHERE executed_at > NOW() - INTERVAL '7 days'
GROUP BY status;

# Find failing schedules
SELECT
    s.name,
    s.schedule_id,
    COUNT(l.log_id) as failures
FROM cmis.scheduled_reports s
JOIN cmis.report_execution_logs l ON s.schedule_id = l.schedule_id
WHERE l.status = 'failed'
    AND l.executed_at > NOW() - INTERVAL '7 days'
GROUP BY s.schedule_id, s.name
HAVING COUNT(l.log_id) > 3
ORDER BY failures DESC;
```

### Maintenance Tasks

1. **Weekly**: Review failed executions
2. **Monthly**: Clean up old logs (keep 90 days)
3. **Quarterly**: Review schedule effectiveness
4. **As Needed**: Update templates based on user feedback

---

## Future Enhancements

- **Conditional Delivery**: Only send if metrics exceed thresholds
- **Interactive Reports**: Embedded charts in emails
- **Slack/Teams Integration**: Post reports to channels
- **Custom Schedules**: Cron expression support
- **Report Subscriptions**: User self-service subscriptions
- **A/B Testing**: Automated winner reports
- **Predictive Alerts**: Send reports when anomalies detected

---

**Phase 12 Status:** ✅ COMPLETE

For questions or support, refer to main analytics documentation or contact the development team.
