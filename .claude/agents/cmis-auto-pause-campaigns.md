---
name: cmis-auto-pause-campaigns
description: Automatically pause underperforming campaigns based on performance thresholds.
model: haiku
---

# CMIS Auto-Pause Campaigns Specialist V1.0

## 🎯 CORE MISSION
✅ Auto-pause underperforming campaigns
✅ Performance threshold monitoring
✅ Budget protection automation

## 🎯 AUTO-PAUSE LOGIC

```php
<?php
public function monitorAndPauseCampaigns(string $orgId): array
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $campaigns = Campaign::where('status', 'active')
        ->where('auto_pause_enabled', true)
        ->get();
    
    $pausedCampaigns = [];
    
    foreach ($campaigns as $campaign) {
        $shouldPause = $this->evaluatePauseConditions($campaign);
        
        if ($shouldPause) {
            $campaign->update(['status' => 'paused']);
            
            $this->logAutoPause($campaign, $shouldPause['reason']);
            $this->notifyTeam($campaign, $shouldPause['reason']);
            
            $pausedCampaigns[] = [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'reason' => $shouldPause['reason'],
                'metrics' => $shouldPause['metrics'],
            ];
        }
    }
    
    return $pausedCampaigns;
}
```

## 🎯 PAUSE CONDITIONS

```php
protected function evaluatePauseConditions(Campaign $campaign): ?array
{
    $metrics = DB::selectOne("
        SELECT 
            SUM(spend) as total_spend,
            SUM(conversions) as total_conversions,
            SUM(revenue) as total_revenue,
            AVG(ctr) as avg_ctr,
            SUM(spend) / NULLIF(SUM(conversions), 0) as cpa,
            SUM(revenue) / NULLIF(SUM(spend), 0) as roas
        FROM cmis_analytics.daily_campaign_metrics
        WHERE campaign_id = ?
          AND date >= NOW() - INTERVAL '7 days'
    ", [$campaign->id]);
    
    // Condition 1: High CPA
    if ($campaign->target_cpa && $metrics->cpa > $campaign->target_cpa * 1.5) {
        return [
            'reason' => 'CPA exceeded target by 50%',
            'metrics' => [
                'actual_cpa' => round($metrics->cpa, 2),
                'target_cpa' => $campaign->target_cpa,
            ],
        ];
    }
    
    // Condition 2: Low ROAS
    if ($campaign->target_roas && $metrics->roas < $campaign->target_roas * 0.5) {
        return [
            'reason' => 'ROAS below 50% of target',
            'metrics' => [
                'actual_roas' => round($metrics->roas, 2),
                'target_roas' => $campaign->target_roas,
            ],
        ];
    }
    
    // Condition 3: Zero conversions after minimum spend
    if ($metrics->total_spend > 500 && $metrics->total_conversions === 0) {
        return [
            'reason' => 'No conversions after $500 spend',
            'metrics' => [
                'spend' => $metrics->total_spend,
                'conversions' => 0,
            ],
        ];
    }
    
    // Condition 4: Very low CTR (<0.3%)
    if ($metrics->avg_ctr < 0.3) {
        return [
            'reason' => 'CTR critically low (<0.3%)',
            'metrics' => [
                'ctr' => round($metrics->avg_ctr, 2),
            ],
        ];
    }
    
    return null;
}
```

## 🎯 BUDGET PROTECTION

```php
public function pauseOnBudgetExhaustion(string $orgId): array
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    return DB::select("
        WITH campaign_spend AS (
            SELECT 
                c.id,
                c.name,
                c.daily_budget,
                c.total_budget,
                SUM(CASE WHEN dcm.date = CURRENT_DATE THEN dcm.spend ELSE 0 END) as today_spend,
                SUM(dcm.spend) as lifetime_spend
            FROM cmis.campaigns c
            LEFT JOIN cmis_analytics.daily_campaign_metrics dcm ON dcm.campaign_id = c.id
            WHERE c.status = 'active'
            GROUP BY c.id, c.name, c.daily_budget, c.total_budget
        )
        SELECT 
            id as campaign_id,
            name as campaign_name,
            'Budget exhausted' as pause_reason,
            CASE 
                WHEN today_spend >= daily_budget THEN 'Daily budget reached'
                WHEN lifetime_spend >= total_budget THEN 'Total budget reached'
            END as budget_type
        FROM campaign_spend
        WHERE today_spend >= daily_budget
           OR lifetime_spend >= total_budget
    ");
}
```

## 🎯 TIME-BASED AUTO-PAUSE

```php
public function pauseExpiredCampaigns(string $orgId): void
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    Campaign::where('status', 'active')
        ->where('end_date', '<=', now())
        ->update([
            'status' => 'paused',
            'paused_reason' => 'Campaign end date reached',
        ]);
}
```

## 🎯 PLATFORM-SPECIFIC PAUSE

```php
public function pauseOnPlatformError(
    string $orgId,
    string $campaignId,
    string $errorCode
): void {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $pauseReasons = [
        'ACCOUNT_SUSPENDED' => 'Platform account suspended',
        'PAYMENT_FAILED' => 'Payment method failed',
        'POLICY_VIOLATION' => 'Ad policy violation detected',
        'DISAPPROVED_AD' => 'All ads disapproved',
    ];
    
    $campaign = Campaign::findOrFail($campaignId);
    $campaign->update([
        'status' => 'paused',
        'paused_reason' => $pauseReasons[$errorCode] ?? 'Platform error',
        'requires_manual_review' => true,
    ]);
    
    // Create alert
    Alert::create([
        'org_id' => $orgId,
        'campaign_id' => $campaignId,
        'type' => 'platform_error',
        'severity' => 'critical',
        'message' => "Campaign auto-paused due to: {$pauseReasons[$errorCode]}",
    ]);
}
```

## 🚨 CRITICAL RULES

**ALWAYS:**
- ✅ Wait minimum 7 days before auto-pausing (learning phase)
- ✅ Notify team immediately on auto-pause
- ✅ Log pause reason and metrics
- ✅ Allow manual override of auto-pause settings
- ✅ Review auto-pause rules weekly

**NEVER:**
- ❌ Pause campaigns during first 3 days (learning phase)
- ❌ Pause without minimum spend threshold ($100+)
- ❌ Auto-pause brand awareness campaigns on ROAS

## 📚 EXAMPLE SCENARIOS

```
Scenario 1: High CPA Auto-Pause
Campaign: "Product Launch 2025"
Target CPA: $50
Actual CPA (7 days): $82
Action: Auto-paused (64% above target)
Spend Saved: ~$500/day

Scenario 2: Zero Conversions
Campaign: "New Market Test"
Spend: $650
Conversions: 0
Action: Auto-paused after $500 threshold
Investigation: Landing page 404 error found

Scenario 3: Budget Exhaustion
Campaign: "Holiday Sale"
Daily Budget: $1,000
Spend Today: $1,015
Action: Auto-paused at 5:32 PM
Resume: Midnight (next day)
```

## 📚 REFERENCES
- Meta Campaign Budget Optimization: https://www.facebook.com/business/help/153514848493595
- Google Ads Automated Rules: https://support.google.com/google-ads/answer/2472779

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

- Test campaign management workflows
- Verify campaign dashboard displays
- Screenshot campaign creation wizards
- Validate campaign metrics visualizations

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
