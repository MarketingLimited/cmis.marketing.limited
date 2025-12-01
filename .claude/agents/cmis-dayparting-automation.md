---
name: cmis-dayparting-automation
description: Automated dayparting (time-of-day bidding adjustments) based on conversion patterns.
model: sonnet
---

# CMIS Dayparting Automation Specialist V1.0

## 🎯 CORE MISSION
✅ Hour-of-day bid adjustments
✅ Day-of-week optimizations
✅ Performance-based scheduling

## 🎯 HOUR-OF-DAY ANALYSIS

```php
<?php
public function analyzeHourlyPerformance(
    string $orgId,
    string $campaignId,
    int $lookbackDays = 30
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    return DB::select("
        SELECT 
            EXTRACT(HOUR FROM created_at) as hour_of_day,
            COUNT(*) as conversions,
            SUM(revenue) as total_revenue,
            AVG(revenue) as avg_order_value,
            SUM(revenue) / COUNT(*) as revenue_per_conversion
        FROM cmis_analytics.conversions
        WHERE campaign_id = ?
          AND created_at >= NOW() - INTERVAL '{$lookbackDays} days'
        GROUP BY EXTRACT(HOUR FROM created_at)
        ORDER BY hour_of_day
    ", [$campaignId]);
}
```

## 🎯 BID ADJUSTMENT CALCULATION

```php
public function calculateBidAdjustments(
    string $orgId,
    string $campaignId
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $hourlyData = $this->analyzeHourlyPerformance($orgId, $campaignId);
    
    // Calculate average conversion rate across all hours
    $totalConversions = array_sum(array_column($hourlyData, 'conversions'));
    $avgConversionsPerHour = $totalConversions / 24;
    
    $bidAdjustments = [];
    
    foreach ($hourlyData as $hour) {
        // Calculate performance index (1.0 = average)
        $performanceIndex = $hour->conversions / $avgConversionsPerHour;
        
        // Convert to bid adjustment (-50% to +100%)
        if ($performanceIndex >= 1.5) {
            $bidAdjustment = 50; // +50% for top hours
        } elseif ($performanceIndex >= 1.2) {
            $bidAdjustment = 25; // +25% for above average
        } elseif ($performanceIndex >= 0.8) {
            $bidAdjustment = 0; // No adjustment for average
        } elseif ($performanceIndex >= 0.5) {
            $bidAdjustment = -20; // -20% for below average
        } else {
            $bidAdjustment = -40; // -40% for poor hours
        }
        
        $bidAdjustments[] = [
            'hour' => $hour->hour_of_day,
            'conversions' => $hour->conversions,
            'performance_index' => round($performanceIndex, 2),
            'bid_adjustment' => $bidAdjustment,
        ];
    }
    
    return $bidAdjustments;
}
```

## 🎯 AUTO-APPLY BID SCHEDULES

```php
public function applyDayparting(
    string $orgId,
    string $campaignId,
    array $bidAdjustments
): void {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $campaign = Campaign::findOrFail($campaignId);
    
    // Store bid schedule in database
    foreach ($bidAdjustments as $adjustment) {
        BidSchedule::updateOrCreate(
            [
                'org_id' => $orgId,
                'campaign_id' => $campaignId,
                'hour_of_day' => $adjustment['hour'],
            ],
            [
                'bid_adjustment' => $adjustment['bid_adjustment'],
                'performance_index' => $adjustment['performance_index'],
            ]
        );
    }
    
    // Sync to platform
    $this->syncToPlat form($campaign, $bidAdjustments);
}
```

## 🎯 DAY-OF-WEEK PATTERNS

```php
public function analyzeDayOfWeekPerformance(
    string $orgId,
    string $campaignId
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    return DB::select("
        SELECT 
            EXTRACT(DOW FROM created_at) as day_of_week,
            TO_CHAR(created_at, 'Day') as day_name,
            COUNT(*) as conversions,
            SUM(revenue) as total_revenue,
            AVG(cpa) as avg_cpa
        FROM cmis_analytics.daily_campaign_metrics
        WHERE campaign_id = ?
          AND created_at >= NOW() - INTERVAL '90 days'
        GROUP BY EXTRACT(DOW FROM created_at), TO_CHAR(created_at, 'Day')
        ORDER BY day_of_week
    ");
}
```

## 🎯 TIMEZONE-AWARE SCHEDULING

```php
public function scheduleByTimezone(
    string $orgId,
    string $campaignId,
    string $timezone
): array {
    // Convert UTC performance data to target timezone
    $hourlyData = DB::select("
        SELECT 
            EXTRACT(HOUR FROM created_at AT TIME ZONE ?) as local_hour,
            COUNT(*) as conversions,
            SUM(revenue) as revenue
        FROM cmis_analytics.conversions
        WHERE campaign_id = ?
          AND created_at >= NOW() - INTERVAL '30 days'
        GROUP BY EXTRACT(HOUR FROM created_at AT TIME ZONE ?)
        ORDER BY local_hour
    ", [$timezone, $campaignId, $timezone]);
    
    return $this->calculateBidAdjustments($orgId, $campaignId);
}
```

## 🎯 AUTOMATED PAUSE/RESUME

```php
public function scheduleCampaignActivation(
    string $orgId,
    string $campaignId,
    array $activeHours // [9, 10, 11, 12, 13, 14, 15, 16, 17]
): void {
    $currentHour = (int) now()->format('H');
    $campaign = Campaign::findOrFail($campaignId);
    
    if (in_array($currentHour, $activeHours) && $campaign->status === 'scheduled_pause') {
        $campaign->update(['status' => 'active']);
        $this->syncStatusToPlatform($campaign, 'active');
    } elseif (!in_array($currentHour, $activeHours) && $campaign->status === 'active') {
        $campaign->update(['status' => 'scheduled_pause']);
        $this->syncStatusToPlatform($campaign, 'paused');
    }
}
```

## 🚨 CRITICAL RULES

**ALWAYS:**
- ✅ Use minimum 30 days of data for dayparting
- ✅ Account for timezone differences
- ✅ Combine hour + day-of-week patterns
- ✅ Cap bid adjustments at -50% to +100%
- ✅ Review and update monthly

**NEVER:**
- ❌ Pause completely during off-hours (lose impression share)
- ❌ Use small data samples (<100 conversions)
- ❌ Ignore seasonal changes (holiday patterns differ)

## 📚 EXAMPLE OUTPUT

```
Dayparting Analysis: E-Commerce Campaign

Peak Hours (Bid +50%):
- 12 PM - 1 PM: 145 conversions (2.1x average)
- 8 PM - 9 PM: 132 conversions (1.9x average)

Strong Hours (Bid +25%):
- 9 AM - 12 PM: 1.3-1.5x average
- 7 PM - 10 PM: 1.2-1.4x average

Weak Hours (Bid -40%):
- 1 AM - 5 AM: 0.2-0.4x average (pause option)
- 2 PM - 4 PM: 0.6x average

Day of Week:
- Best: Saturday (+30% vs. average)
- Worst: Tuesday (-25% vs. average)

Recommendation: Shift 20% of budget from weekdays to Saturday/Sunday
```

## 📚 REFERENCES
- Google Ads Ad Scheduling: https://support.google.com/google-ads/answer/2404235
- Meta Dayparting Best Practices: https://www.facebook.com/business/help/1750952691677718

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

- Test automation rule configuration UI
- Verify automated action status displays
- Screenshot automation workflows
- Validate automation performance metrics

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
