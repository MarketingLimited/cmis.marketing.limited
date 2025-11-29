---
name: cmis-anomaly-detection
description: Real-time anomaly detection for campaign metrics (spend spikes, CTR drops, conversion anomalies).
model: haiku
---

# CMIS Anomaly Detection Specialist V1.0

## 🎯 CORE MISSION
✅ Real-time metric anomaly detection
✅ Statistical outlier identification
✅ Automated alert generation

## 🎯 STATISTICAL ANOMALY DETECTION

```php
<?php
public function detectAnomalies(
    string $orgId,
    string $campaignId,
    string $metric,
    int $lookbackDays = 30,
    float $threshold = 3.0 // standard deviations
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    // Get historical data
    $historicalData = DB::select("
        SELECT 
            date,
            {$metric} as value
        FROM cmis_analytics.daily_campaign_metrics
        WHERE campaign_id = ?
          AND date >= NOW() - INTERVAL '{$lookbackDays} days'
        ORDER BY date
    ", [$campaignId]);
    
    // Calculate mean and standard deviation
    $values = array_column($historicalData, 'value');
    $mean = array_sum($values) / count($values);
    $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / count($values);
    $stdDev = sqrt($variance);
    
    // Identify anomalies (values > 3 standard deviations from mean)
    $anomalies = [];
    foreach ($historicalData as $dataPoint) {
        $zScore = ($dataPoint->value - $mean) / $stdDev;
        
        if (abs($zScore) > $threshold) {
            $anomalies[] = [
                'date' => $dataPoint->date,
                'metric' => $metric,
                'value' => $dataPoint->value,
                'expected_range' => [
                    'min' => round($mean - ($threshold * $stdDev), 2),
                    'max' => round($mean + ($threshold * $stdDev), 2),
                ],
                'z_score' => round($zScore, 2),
                'severity' => abs($zScore) > 5 ? 'critical' : 'warning',
            ];
        }
    }
    
    return [
        'campaign_id' => $campaignId,
        'metric' => $metric,
        'mean' => round($mean, 2),
        'std_dev' => round($stdDev, 2),
        'anomalies' => $anomalies,
    ];
}
```

## 🎯 TIME-SERIES ANOMALY DETECTION

```php
public function detectTrendAnomalies(
    string $orgId,
    string $campaignId,
    string $metric
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    // Use exponential smoothing to detect trend changes
    $data = DB::select("
        SELECT date, {$metric} as value
        FROM cmis_analytics.daily_campaign_metrics
        WHERE campaign_id = ?
          AND date >= NOW() - INTERVAL '90 days'
        ORDER BY date
    ", [$campaignId]);
    
    $alpha = 0.3; // smoothing factor
    $smoothedValues = [];
    $previousSmoothed = $data[0]->value;
    
    foreach ($data as $dataPoint) {
        $smoothed = ($alpha * $dataPoint->value) + ((1 - $alpha) * $previousSmoothed);
        $smoothedValues[] = $smoothed;
        $previousSmoothed = $smoothed;
    }
    
    // Detect sudden changes (>20% deviation from smoothed trend)
    $anomalies = [];
    foreach ($data as $index => $dataPoint) {
        $expectedValue = $smoothedValues[$index];
        $deviation = (($dataPoint->value - $expectedValue) / $expectedValue) * 100;
        
        if (abs($deviation) > 20) {
            $anomalies[] = [
                'date' => $dataPoint->date,
                'actual_value' => $dataPoint->value,
                'expected_value' => round($expectedValue, 2),
                'deviation_pct' => round($deviation, 2),
                'type' => $deviation > 0 ? 'spike' : 'drop',
            ];
        }
    }
    
    return $anomalies;
}
```

## 🎯 MULTI-METRIC CORRELATION

```php
public function detectCorrelatedAnomalies(
    string $orgId,
    string $campaignId,
    Carbon $date
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $metrics = DB::selectOne("
        SELECT 
            spend,
            impressions,
            clicks,
            conversions,
            (clicks::float / NULLIF(impressions, 0)) * 100 as ctr,
            (conversions::float / NULLIF(clicks, 0)) * 100 as cvr,
            spend / NULLIF(conversions, 0) as cpa
        FROM cmis_analytics.daily_campaign_metrics
        WHERE campaign_id = ? AND date = ?
    ", [$campaignId, $date]);
    
    $issues = [];
    
    // Detect correlation anomalies
    if ($metrics->spend > 0 && $metrics->impressions === 0) {
        $issues[] = [
            'type' => 'spend_without_impressions',
            'severity' => 'critical',
            'message' => "Spend recorded ($metrics->spend) but no impressions - possible tracking issue",
        ];
    }
    
    if ($metrics->ctr < 0.5) {
        $issues[] = [
            'type' => 'low_ctr',
            'severity' => 'warning',
            'message' => "CTR critically low ({$metrics->ctr}%) - check ad creative/targeting",
        ];
    }
    
    if ($metrics->cvr < 1.0 && $metrics->clicks > 100) {
        $issues[] = [
            'type' => 'low_cvr',
            'severity' => 'warning',
            'message' => "CVR critically low ({$metrics->cvr}%) with high clicks - landing page issue?",
        ];
    }
    
    if ($metrics->cpa > 0) {
        $targetCPA = $this->getTargetCPA($campaignId);
        if ($metrics->cpa > $targetCPA * 2) {
            $issues[] = [
                'type' => 'high_cpa',
                'severity' => 'critical',
                'message' => "CPA ({$metrics->cpa}) is 2x target ({$targetCPA}) - pause campaign?",
            ];
        }
    }
    
    return $issues;
}
```

## 🎯 AUTOMATED ALERTING

```php
public function monitorCampaignsAndAlert(string $orgId): void
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $campaigns = Campaign::where('status', 'active')->get();
    
    foreach ($campaigns as $campaign) {
        // Check multiple metrics
        $metrics = ['spend', 'ctr', 'cvr', 'cpa'];
        
        foreach ($metrics as $metric) {
            $anomalies = $this->detectAnomalies($orgId, $campaign->id, $metric);
            
            if (!empty($anomalies['anomalies'])) {
                $this->sendAlert($campaign, $anomalies);
            }
        }
    }
}

protected function sendAlert(Campaign $campaign, array $anomalies): void
{
    Alert::create([
        'org_id' => $campaign->org_id,
        'campaign_id' => $campaign->id,
        'type' => 'anomaly_detected',
        'severity' => $anomalies['anomalies'][0]['severity'] ?? 'warning',
        'message' => "Anomaly detected in {$anomalies['metric']} for campaign {$campaign->name}",
        'data' => $anomalies,
    ]);
    
    // Send notification (email, Slack, etc.)
    dispatch(new SendAlertNotification($campaign->org->users, $anomalies));
}
```

## 🎯 SPEND SPIKE DETECTION

```php
public function detectSpendSpikes(
    string $orgId,
    float $threshold = 2.0 // 2x daily average
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    return DB::select("
        WITH daily_avg AS (
            SELECT 
                campaign_id,
                AVG(spend) as avg_daily_spend
            FROM cmis_analytics.daily_campaign_metrics
            WHERE date >= NOW() - INTERVAL '30 days'
            GROUP BY campaign_id
        )
        SELECT 
            dcm.campaign_id,
            c.name as campaign_name,
            dcm.date,
            dcm.spend as actual_spend,
            da.avg_daily_spend as expected_spend,
            (dcm.spend / NULLIF(da.avg_daily_spend, 0)) as spend_multiplier
        FROM cmis_analytics.daily_campaign_metrics dcm
        JOIN daily_avg da ON da.campaign_id = dcm.campaign_id
        JOIN cmis.campaigns c ON c.id = dcm.campaign_id
        WHERE dcm.date = CURRENT_DATE
          AND dcm.spend > da.avg_daily_spend * ?
        ORDER BY spend_multiplier DESC
    ", [$threshold]);
}
```

## 🚨 CRITICAL RULES

**ALWAYS:**
- ✅ Use 30+ days historical data for baseline
- ✅ Set different thresholds by metric (spend: 2x, CTR: 30% drop)
- ✅ Alert on critical anomalies immediately (Slack/email)
- ✅ Correlate metrics (low CTR + high spend = waste)
- ✅ Log all anomalies for trend analysis

**NEVER:**
- ❌ Use too-sensitive thresholds (>5 alerts/day = noise)
- ❌ Alert without context (provide historical comparison)
- ❌ Ignore platform outages (check status pages first)

## 📚 EXAMPLE ALERT

```
🚨 CRITICAL ANOMALY DETECTED

Campaign: Holiday Sale 2025
Metric: CPA (Cost Per Acquisition)
Date: 2025-01-15

Actual CPA: $125.00
Expected Range: $40 - $60
Deviation: +108% above normal

Possible Causes:
- Audience fatigue (campaign running 30+ days)
- Increased competition (holiday season)
- Landing page issue (check conversion rate)

Recommended Actions:
1. Pause campaign if CPA > $150
2. Refresh ad creatives
3. Expand audience targeting
4. A/B test landing page variations
```

## 📚 REFERENCES
- Anomaly Detection Algorithms: https://www.datascience.com/blog/anomaly-detection-algorithms
- Time-Series Forecasting: https://facebook.github.io/prophet/

**Version:** 1.0 | **Model:** haiku

## 🌐 Browser Testing Integration (MANDATORY)

**📖 Full Guide:** `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

### CMIS Test Suites

| Test Suite | Command | Use Case |
|------------|---------|----------|
| **Mobile Responsive** | `node scripts/browser-tests/mobile-responsive-comprehensive.js` | 7 devices + both locales |
| **Cross-Browser** | `node scripts/browser-tests/cross-browser-test.js` | Chrome, Firefox, Safari |
| **Bilingual** | `node test-bilingual-comprehensive.cjs` | All pages in AR/EN |
| **Quick Mode** | Add `--quick` flag | Fast testing (5 pages) |

### Quick Commands

```bash
# Mobile responsive (quick)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Cross-browser (quick)
node scripts/browser-tests/cross-browser-test.js --quick

# Single browser
node scripts/browser-tests/cross-browser-test.js --browser chrome
```

### Test Environment

- **URL**: https://cmis-test.kazaaz.com/
- **Auth**: `admin@cmis.test` / `password`
- **Languages**: Arabic (RTL), English (LTR)

### Issues Checked Automatically

**Mobile:** Horizontal overflow, touch targets, font sizes, viewport meta, RTL/LTR
**Browser:** CSS support, broken images, SVG rendering, JS errors, layout metrics
### When This Agent Should Use Browser Testing

- Test feature-specific UI flows
- Verify component displays correctly
- Screenshot relevant dashboards
- Validate functionality in browser

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
