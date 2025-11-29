---
name: cmis-auto-scale-campaigns
description: Automatically scale high-performing campaigns by increasing budgets and bids.
model: haiku
---

# CMIS Auto-Scale Campaigns Specialist V1.0

## 🎯 CORE MISSION
✅ Auto-scale high performers
✅ Dynamic budget increases
✅ Performance-based scaling

## 🎯 AUTO-SCALING LOGIC

```php
<?php
public function scaleHighPerformers(string $orgId): array
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $campaigns = Campaign::where('status', 'active')
        ->where('auto_scale_enabled', true)
        ->get();
    
    $scaledCampaigns = [];
    
    foreach ($campaigns as $campaign) {
        $scaling = $this->evaluateScalingOpportunity($campaign);
        
        if ($scaling) {
            $this->applyScaling($campaign, $scaling);
            
            $scaledCampaigns[] = [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'scaling_type' => $scaling['type'],
                'increase_pct' => $scaling['increase_pct'],
                'new_budget' => $scaling['new_budget'],
            ];
        }
    }
    
    return $scaledCampaigns;
}
```

## 🎯 SCALING CRITERIA

```php
protected function evaluateScalingOpportunity(Campaign $campaign): ?array
{
    $metrics = DB::selectOne("
        SELECT 
            SUM(spend) as total_spend,
            SUM(conversions) as total_conversions,
            SUM(revenue) as total_revenue,
            SUM(spend) / NULLIF(SUM(conversions), 0) as cpa,
            SUM(revenue) / NULLIF(SUM(spend), 0) as roas,
            AVG(impression_share) as avg_impression_share
        FROM cmis_analytics.daily_campaign_metrics
        WHERE campaign_id = ?
          AND date >= NOW() - INTERVAL '7 days'
    ", [$campaign->id]);
    
    // Criteria 1: ROAS exceeds target by 30%+
    if ($campaign->target_roas && $metrics->roas >= $campaign->target_roas * 1.3) {
        return [
            'type' => 'high_roas',
            'increase_pct' => 30, // Increase budget by 30%
            'new_budget' => $campaign->daily_budget * 1.3,
            'reason' => "ROAS {$metrics->roas}x exceeds target {$campaign->target_roas}x by 30%",
        ];
    }
    
    // Criteria 2: CPA below target by 25%+
    if ($campaign->target_cpa && $metrics->cpa <= $campaign->target_cpa * 0.75) {
        return [
            'type' => 'low_cpa',
            'increase_pct' => 25,
            'new_budget' => $campaign->daily_budget * 1.25,
            'reason' => "CPA ${$metrics->cpa} is 25% below target ${$campaign->target_cpa}",
        ];
    }
    
    // Criteria 3: Budget fully spent + high impression share
    if ($metrics->avg_impression_share > 85 && $this->isBudgetConstrained($campaign)) {
        return [
            'type' => 'budget_constrained',
            'increase_pct' => 20,
            'new_budget' => $campaign->daily_budget * 1.2,
            'reason' => 'Budget fully spent with 85%+ impression share',
        ];
    }
    
    return null;
}
```

## 🎯 GRADUAL SCALING (AVOID SHOCK)

```php
protected function applyScaling(Campaign $campaign, array $scaling): void
{
    // Gradual scaling: max 30% increase per day
    $maxIncrease = $campaign->daily_budget * 0.3;
    $proposedIncrease = $scaling['new_budget'] - $campaign->daily_budget;
    
    $actualIncrease = min($proposedIncrease, $maxIncrease);
    $newBudget = $campaign->daily_budget + $actualIncrease;
    
    // Apply budget increase
    $campaign->update([
        'daily_budget' => $newBudget,
        'last_scaled_at' => now(),
    ]);
    
    // Log scaling action
    CampaignScalingLog::create([
        'org_id' => $campaign->org_id,
        'campaign_id' => $campaign->id,
        'previous_budget' => $campaign->daily_budget,
        'new_budget' => $newBudget,
        'increase_pct' => (($newBudget / $campaign->daily_budget) - 1) * 100,
        'reason' => $scaling['reason'],
    ]);
    
    // Sync to platform
    $this->syncBudgetToPlatform($campaign);
}
```

## 🎯 PLATFORM SYNC

```php
protected function syncBudgetToPlatform(Campaign $campaign): void
{
    $connector = app(PlatformConnectorFactory::class)
        ->make($campaign->platform);
    
    $connector->updateCampaignBudget(
        $campaign->platform_campaign_id,
        $campaign->daily_budget * 100 // Convert to cents
    );
}
```

## 🎯 SCALING SAFEGUARDS

```php
protected function validateScaling(Campaign $campaign, float $newBudget): bool
{
    // Safeguard 1: Maximum daily budget limit
    if ($campaign->max_daily_budget && $newBudget > $campaign->max_daily_budget) {
        return false;
    }
    
    // Safeguard 2: Maximum total budget
    $projectedMonthlySpend = $newBudget * 30;
    if ($campaign->total_budget && $projectedMonthlySpend > $campaign->total_budget) {
        return false;
    }
    
    // Safeguard 3: Minimum time between scaling (24 hours)
    if ($campaign->last_scaled_at && $campaign->last_scaled_at->diffInHours(now()) < 24) {
        return false;
    }
    
    // Safeguard 4: Maximum scaling events per week (3x)
    $scalingEventsThisWeek = CampaignScalingLog::where('campaign_id', $campaign->id)
        ->where('created_at', '>=', now()->startOfWeek())
        ->count();
    
    if ($scalingEventsThisWeek >= 3) {
        return false;
    }
    
    return true;
}
```

## 🎯 PERFORMANCE-BASED BID INCREASES

```php
public function scaleBids(string $orgId, string $campaignId): void
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $campaign = Campaign::findOrFail($campaignId);
    
    // Get ad set performance
    $adSets = DB::select("
        SELECT 
            ad_set_id,
            SUM(conversions) as conversions,
            SUM(spend) / NULLIF(SUM(conversions), 0) as cpa
        FROM cmis_analytics.daily_ad_set_metrics
        WHERE campaign_id = ?
          AND date >= NOW() - INTERVAL '7 days'
        GROUP BY ad_set_id
        HAVING SUM(conversions) > 0
    ", [$campaignId]);
    
    foreach ($adSets as $adSet) {
        if ($adSet->cpa < $campaign->target_cpa * 0.8) {
            // Increase bid by 15% for high performers
            $this->increaseBid($adSet->ad_set_id, 1.15);
        }
    }
}
```

## 🚨 CRITICAL RULES

**ALWAYS:**
- ✅ Scale gradually (max 30% increase per day)
- ✅ Wait minimum 24 hours between scaling events
- ✅ Cap scaling at 3x per week (avoid instability)
- ✅ Set maximum budget limits
- ✅ Monitor performance for 3-7 days after scaling

**NEVER:**
- ❌ Scale during learning phase (first 7 days)
- ❌ Double budget overnight (causes delivery issues)
- ❌ Scale without sufficient conversion data (min 10 conversions)

## 📚 EXAMPLE SCENARIOS

```
Scenario 1: High ROAS Scaling
Campaign: "Best Sellers Collection"
Target ROAS: 4.0x
Actual ROAS (7 days): 5.5x (38% above target)
Current Budget: $500/day
Action: Increase to $650/day (+30%)
Expected: Maintain 4.5-5.0x ROAS at higher volume

Scenario 2: Budget-Constrained Scaling
Campaign: "Premium Product Launch"
Daily Budget: $1,000
Budget Utilization: 100% (fully spent by 3 PM daily)
Impression Share: 92%
Action: Increase to $1,250/day (+25%)
Goal: Capture additional high-intent searches

Scenario 3: Gradual Scaling Over Time
Day 1: $500 → $650 (+30%)
Day 3: $650 → $845 (+30%)
Day 5: $845 → $1,099 (+30%)
Result: 2.2x budget increase over 5 days while maintaining CPA
```

## 📚 REFERENCES
- Meta Budget Scaling Best Practices: https://www.facebook.com/business/help/214319341922580
- Google Performance Max Scaling: https://support.google.com/google-ads/answer/10724817

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
