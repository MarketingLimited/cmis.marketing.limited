# Phase 13: Real-Time Alerts & Notifications

**Version:** 1.0
**Last Updated:** 2025-11-21

---

## Overview

Phase 13 adds real-time alert and notification system that proactively monitors campaign metrics and triggers notifications when conditions are met. Users can configure custom alert rules, receive notifications through multiple channels, and manage alert history.

---

## Key Features

### 1. Configurable Alert Rules

Create custom alert rules with:
- **Metrics**: Any campaign/entity metric (CTR, ROI, spend, impressions, etc.)
- **Conditions**: Greater than, less than, equal to, percentage change
- **Thresholds**: Numeric values to trigger alerts
- **Severity Levels**: Critical, high, medium, low
- **Cool down Periods**: Prevent alert fatigue
- **Entity Targeting**: Apply to specific entities or all of a type

### 2. Multi-Channel Notifications

Deliver alerts through:
- **Email**: Professional HTML templates with severity styling
- **In-App**: Real-time notifications in dashboard
- **Slack**: Rich formatted messages with attachments
- **Webhooks**: Custom integrations with external systems

### 3. Alert Management

- Acknowledge alerts to mark as seen
- Resolve alerts when action taken
- Snooze alerts for temporary suppression
- View complete alert history
- Track notification delivery status

### 4. Alert Templates

Pre-built templates for common scenarios:
- Budget overspend detection
- Performance degradation alerts
- Conversion rate drops
- CTR decline warnings
- Anomaly detection

---

## Database Schema

### alert_rules Table
```sql
CREATE TABLE cmis.alert_rules (
    rule_id UUID PRIMARY KEY,
    org_id UUID NOT NULL,
    created_by UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    entity_type VARCHAR(50) NOT NULL,  -- campaign, ad, post
    entity_id UUID,                     -- NULL = all entities
    metric VARCHAR(100) NOT NULL,       -- ctr, roi, spend, etc.
    condition VARCHAR(20) NOT NULL,     -- gt, lt, eq, change_pct
    threshold DECIMAL(20,4) NOT NULL,
    time_window_minutes INT DEFAULT 60,
    severity VARCHAR(20) DEFAULT 'medium',
    notification_channels JSONB NOT NULL,
    notification_config JSONB NOT NULL,
    cooldown_minutes INT DEFAULT 60,
    is_active BOOLEAN DEFAULT true,
    last_triggered_at TIMESTAMP,
    trigger_count INT DEFAULT 0
);
```

### alert_history Table
```sql
CREATE TABLE cmis.alert_history (
    alert_id UUID PRIMARY KEY,
    rule_id UUID NOT NULL,
    org_id UUID NOT NULL,
    triggered_at TIMESTAMP NOT NULL,
    entity_type VARCHAR(50),
    entity_id UUID,
    metric VARCHAR(100),
    actual_value DECIMAL(20,4),
    threshold_value DECIMAL(20,4),
    condition VARCHAR(20),
    severity VARCHAR(20),
    message TEXT,
    metadata JSONB,
    status VARCHAR(20) DEFAULT 'new',  -- new, acknowledged, resolved, snoozed
    acknowledged_by UUID,
    acknowledged_at TIMESTAMP,
    resolved_at TIMESTAMP,
    snoozed_until TIMESTAMP,
    resolution_notes TEXT
);
```

### alert_notifications Table
```sql
CREATE TABLE cmis.alert_notifications (
    notification_id UUID PRIMARY KEY,
    alert_id UUID NOT NULL,
    org_id UUID NOT NULL,
    channel VARCHAR(50),               -- email, in_app, slack, webhook
    recipient TEXT,
    sent_at TIMESTAMP,
    status VARCHAR(20),                -- pending, sent, failed, delivered, read
    error_message TEXT,
    retry_count INT DEFAULT 0,
    delivered_at TIMESTAMP,
    read_at TIMESTAMP,
    metadata JSONB
);
```

**Indexes:**
- `alert_rules`: org_id, entity_type/entity_id, is_active/last_triggered_at
- `alert_history`: rule_id, org_id, triggered_at, status, severity
- `alert_notifications`: alert_id, channel/status

---

## API Reference

### Alert Rules Endpoints

**List Rules**
`GET /api/orgs/{org_id}/alerts/rules`

Query Parameters:
- `entity_type` - Filter by entity type
- `severity` - Filter by severity level
- `active` - Filter by active status
- `per_page` - Pagination limit (default: 15)

**Create Rule**
`POST /api/orgs/{org_id}/alerts/rules`

Request Body:
```json
{
  "name": "Campaign CTR Drop Alert",
  "description": "Alert when CTR drops below 2%",
  "entity_type": "campaign",
  "entity_id": "campaign-uuid-or-null",
  "metric": "ctr",
  "condition": "lt",
  "threshold": 2.0,
  "time_window_minutes": 60,
  "severity": "high",
  "notification_channels": ["email", "slack"],
  "notification_config": {
    "email": {
      "recipients": ["marketing@example.com"]
    },
    "slack": {
      "webhook_url": "https://hooks.slack.com/..."
    }
  },
  "cooldown_minutes": 120,
  "is_active": true
}
```

**Update Rule**
`PUT /api/orgs/{org_id}/alerts/rules/{rule_id}`

**Delete Rule**
`DELETE /api/orgs/{org_id}/alerts/rules/{rule_id}`

**Test Rule**
`POST /api/orgs/{org_id}/alerts/rules/{rule_id}/test`

Manually triggers rule evaluation.

### Alert History Endpoints

**List Alerts**
`GET /api/orgs/{org_id}/alerts/history`

Query Parameters:
- `status` - Filter by status (new, acknowledged, resolved, snoozed)
- `severity` - Filter by severity
- `rule_id` - Filter by specific rule
- `days` - Filter by recent days (default: 7)

**Acknowledge Alert**
`POST /api/orgs/{org_id}/alerts/{alert_id}/acknowledge`

```json
{
  "notes": "Investigating the issue"
}
```

**Resolve Alert**
`POST /api/orgs/{org_id}/alerts/{alert_id}/resolve`

```json
{
  "notes": "Fixed by adjusting targeting"
}
```

**Snooze Alert**
`POST /api/orgs/{org_id}/alerts/{alert_id}/snooze`

```json
{
  "minutes": 60
}
```

### Alert Templates Endpoints

**List Templates**
`GET /api/alerts/templates`

**Create from Template**
`POST /api/orgs/{org_id}/alerts/rules/from-template/{template_id}`

---

## Services

### AlertEvaluationService

Evaluates alert rules against current metrics.

**Key Methods:**
- `evaluateEntityRules(string $entityType, string $entityId, array $metrics)` - Evaluate all rules for an entity
- `evaluateRule(AlertRule $rule, array $metrics, ?string $entityId)` - Evaluate single rule
- `evaluateAllDueRules()` - Batch evaluate all active rules

**Usage:**
```php
$evaluationService = app(AlertEvaluationService::class);

// Evaluate specific entity
$alerts = $evaluationService->evaluateEntityRules('campaign', $campaignId, [
    'ctr' => 1.5,
    'roi' => 85.0,
    'spend' => 1200.00
]);

// Evaluate all due rules (run periodically)
$stats = $evaluationService->evaluateAllDueRules();
```

### NotificationDeliveryService

Handles multi-channel notification delivery.

**Key Methods:**
- `deliverAlert(AlertHistory $alert, AlertRule $rule)` - Deliver notifications for alert
- `retryNotification(AlertNotification $notification)` - Retry failed notification

**Supported Channels:**
- **Email**: Uses Laravel Mail with Blade templates
- **In-App**: Creates records in user_notifications table
- **Slack**: Posts to Slack webhooks with rich formatting
- **Webhook**: HTTP POST with HMAC signature verification

---

## Jobs & Commands

### ProcessAlertsJob

Queue job for alert processing.

**Modes:**
- `'all'` - Evaluate all due rules
- `'rule'` - Evaluate specific rule
- `'entity'` - Evaluate rules for specific entity

**Usage:**
```php
// Evaluate all due rules
ProcessAlertsJob::dispatch('all');

// Evaluate specific rule
ProcessAlertsJob::dispatch('rule', ['rule_id' => $ruleId]);

// Evaluate entity
ProcessAlertsJob::dispatch('entity', [
    'entity_type' => 'campaign',
    'entity_id' => $campaignId,
    'metrics' => $currentMetrics
]);
```

**Queue Configuration:**
- Queue: `alerts`
- Tries: 2
- Retry After: 120 seconds

### EvaluateAlerts Command

Artisan command for periodic evaluation.

**Usage:**
```bash
# Evaluate all due rules
php artisan alerts:evaluate

# Evaluate specific rule
php artisan alerts:evaluate --rule=rule-uuid

# Evaluate entity
php artisan alerts:evaluate --entity-type=campaign --entity-id=uuid

# Run synchronously (no queue)
php artisan alerts:evaluate --sync
```

**Laravel Scheduler:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Evaluate alerts every 5 minutes
    $schedule->command('alerts:evaluate')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}
```

---

## Frontend Component

### alertsManagement.js

Alpine.js component for alert management UI.

**Features:**
- View/create/edit/delete alert rules
- View alert history with filtering
- Acknowledge/resolve/snooze alerts
- Apply alert templates
- Real-time status updates

**Usage:**
```html
<div x-data="alertsManagement()" data-org-id="{{ $orgId }}">
    <!-- Tabs -->
    <div class="tabs">
        <button @click="switchTab('rules')"
                :class="{ 'active': activeTab === 'rules' }">
            Rules
        </button>
        <button @click="switchTab('history')"
                :class="{ 'active': activeTab === 'history' }">
            History
        </button>
    </div>

    <!-- Rules Tab -->
    <div x-show="activeTab === 'rules'">
        <template x-for="rule in rules">
            <div>
                <h3 x-text="rule.name"></h3>
                <span x-text="rule.severity"></span>
                <button @click="toggleActive(rule)">
                    Toggle Active
                </button>
            </div>
        </template>
    </div>

    <!-- History Tab -->
    <div x-show="activeTab === 'history'">
        <template x-for="alert in alerts">
            <div>
                <span x-text="alert.message"></span>
                <button @click="acknowledgeAlert(alert.alert_id)">
                    Acknowledge
                </button>
            </div>
        </template>
    </div>
</div>
```

---

## Use Cases

### 1. Budget Overspend Alert

```javascript
// Create alert for budget exceeding threshold
const rule = await fetch('/api/orgs/${orgId}/alerts/rules', {
    method: 'POST',
    body: JSON.stringify({
        name: 'Budget Overspend Alert',
        entity_type: 'campaign',
        entity_id: campaignId,
        metric: 'spend',
        condition: 'gt',
        threshold: 5000.00,
        severity: 'critical',
        notification_channels: ['email', 'slack'],
        notification_config: {
            email: { recipients: ['finance@company.com'] },
            slack: { webhook_url: process.env.SLACK_WEBHOOK }
        },
        cooldown_minutes: 60
    })
});
```

### 2. Performance Degradation Detection

```javascript
// Alert when CTR drops by 20% or more
const rule = await fetch('/api/orgs/${orgId}/alerts/rules', {
    method: 'POST',
    body: JSON.stringify({
        name: 'CTR Drop Alert',
        entity_type: 'campaign',
        metric: 'ctr',
        condition: 'change_pct',
        threshold: 20.0,  // 20% change
        time_window_minutes: 120,  // Compare to 2 hours ago
        severity: 'high',
        notification_channels: ['email', 'in_app'],
        cooldown_minutes: 240  // 4 hours between alerts
    })
});
```

### 3. Conversion Rate Monitoring

```javascript
// Alert when conversion rate falls below target
const rule = await fetch('/api/orgs/${orgId}/alerts/rules', {
    method: 'POST',
    body: JSON.stringify({
        name: 'Low Conversion Rate',
        entity_type: 'campaign',
        metric: 'conversion_rate',
        condition: 'lt',
        threshold: 2.5,
        severity: 'medium',
        notification_channels: ['email'],
        notification_config: {
            email: { recipients: ['performance@company.com'] }
        }
    })
});
```

### 4. Real-Time Alert Processing (Webhook Integration)

```javascript
// When metric updated, evaluate alerts
app.post('/webhooks/metrics-updated', async (req, res) => {
    const { entity_type, entity_id, metrics } = req.body;

    // Dispatch alert evaluation
    await fetch(`http://internal-api/jobs`, {
        method: 'POST',
        body: JSON.stringify({
            job: 'ProcessAlertsJob',
            mode: 'entity',
            params: { entity_type, entity_id, metrics }
        })
    });

    res.json({ success: true });
});
```

---

## Security & Best Practices

### Authentication
- All endpoints require `auth:sanctum`
- RLS policies enforce organization isolation
- Users can only manage their org's alerts

### Notification Security
- **Email**: SPF/DKIM configuration recommended
- **Slack**: Webhook URLs stored encrypted
- **Webhooks**: HMAC signature verification

### Rate Limiting
- Alert evaluation: Every 1-5 minutes via scheduler
- Cooldown periods prevent alert fatigue
- Notification retries: Max 3 attempts

### Performance
- Queue-based processing for scalability
- Batch evaluation of rules
- Caching of frequently accessed data
- Index optimization on alert tables

---

## Monitoring & Maintenance

### Health Checks

```sql
-- Check recent alert activity
SELECT
    DATE(triggered_at) as date,
    severity,
    COUNT(*) as alert_count,
    COUNT(DISTINCT rule_id) as rules_triggered
FROM cmis.alert_history
WHERE triggered_at >= NOW() - INTERVAL '7 days'
GROUP BY DATE(triggered_at), severity
ORDER BY date DESC, severity;

-- Find noisy rules (triggering too often)
SELECT
    r.name,
    r.rule_id,
    COUNT(h.alert_id) as trigger_count,
    MAX(h.triggered_at) as last_triggered
FROM cmis.alert_rules r
JOIN cmis.alert_history h ON r.rule_id = h.rule_id
WHERE h.triggered_at >= NOW() - INTERVAL '24 hours'
GROUP BY r.rule_id, r.name
HAVING COUNT(h.alert_id) > 10
ORDER BY trigger_count DESC;

-- Check notification delivery success rate
SELECT
    channel,
    status,
    COUNT(*) as count,
    ROUND(AVG(retry_count), 2) as avg_retries
FROM cmis.alert_notifications
WHERE sent_at >= NOW() - INTERVAL '7 days'
GROUP BY channel, status
ORDER BY channel, status;
```

### Maintenance Tasks

**Daily:**
- Review critical/high severity alerts
- Check notification delivery failures
- Monitor rule trigger frequencies

**Weekly:**
- Analyze alert trends and patterns
- Adjust thresholds based on historical data
- Review and update alert templates

**Monthly:**
- Archive old alert history (>90 days)
- Review cooldown periods effectiveness
- Update notification configurations

---

## Integration with Previous Phases

Phase 13 builds on:
- **Phase 5-7**: Backend analytics APIs (metrics source)
- **Phase 8**: Frontend components (UI framework)
- **Phase 11**: AI Insights Service (intelligent thresholds)
- **Phase 12**: Email infrastructure (notification delivery)

**New Additions:**
- Proactive monitoring system
- Multi-channel notifications
- Alert lifecycle management
- Template library

---

## Future Enhancements

- **Machine Learning**: Auto-adjust thresholds based on historical patterns
- **Anomaly Detection**: AI-powered anomaly identification
- **Escalation Policies**: Multi-tier notification escalation
- **Alert Correlations**: Group related alerts
- **Mobile Push**: iOS/Android push notifications
- **SMS**: Text message alerts for critical issues
- **Voice Calls**: Automated calls for critical alerts
- **Predictive Alerts**: Forecast potential issues before they occur

---

**Phase 13 Status:** ✅ COMPLETE

For questions or support, refer to main analytics documentation or contact the development team.
