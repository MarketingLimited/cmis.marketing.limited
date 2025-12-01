---
name: cmis-auto-bidding-switches
description: Automatically switch bidding strategies based on campaign performance and learning status.
model: sonnet
---

# CMIS Auto-Bidding Strategy Switcher V1.0

## 🎯 CORE MISSION
✅ Performance-based bidding strategy switches
✅ Learning phase optimization
✅ Cost efficiency automation

## 🎯 BIDDING STRATEGY EVALUATION

```php
<?php
public function evaluateBiddingStrategy(
    string $orgId,
    string $campaignId
): ?array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $campaign = Campaign::findOrFail($campaignId);
    $metrics = $this->getCampaignMetrics($campaignId, days: 14);
    
    // Exit learning phase?
    if ($campaign->bidding_strategy === 'lowest_cost' && $metrics->conversions >= 50) {
        return [
            'from' => 'lowest_cost',
            'to' => 'target_cpa',
            'reason' => 'Sufficient conversion volume (50+) to enable Target CPA',
            'target_cpa' => $metrics->avg_cpa,
        ];
    }
    
    // CPA too high?
    if ($campaign->bidding_strategy === 'target_cpa' && $metrics->cpa > $campaign->target_cpa * 1.4) {
        return [
            'from' => 'target_cpa',
            'to' => 'cost_cap',
            'reason' => 'CPA exceeded target by 40%, switching to Cost Cap for strict control',
            'cost_cap' => $campaign->target_cpa * 1.2,
        ];
    }
    
    // ROAS opportunity?
    if ($campaign->bidding_strategy === 'target_cpa' && $metrics->roas > 4.0) {
        return [
            'from' => 'target_cpa',
            'to' => 'target_roas',
            'reason' => 'High ROAS (4.0x+) detected, optimizing for revenue',
            'target_roas' => $metrics->roas * 0.8, // Conservative target
        ];
    }
    
    return null;
}
```

## 🎯 AUTO-SWITCH LOGIC

```php
public function apploBiddingStrategySwitch(
    string $orgId,
    string $campaignId,
    array $switchRecommendation
): void {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $campaign = Campaign::findOrFail($campaignId);
    
    // Update campaign bidding strategy
    $campaign->update([
        'bidding_strategy' => $switchRecommendation['to'],
        'target_cpa' => $switchRecommendation['target_cpa'] ?? null,
        'cost_cap' => $switchRecommendation['cost_cap'] ?? null,
        'target_roas' => $switchRecommendation['target_roas'] ?? null,
        'last_bidding_switch_at' => now(),
    ]);
    
    // Log switch
    BiddingStrategyLog::create([
        'org_id' => $orgId,
        'campaign_id' => $campaignId,
        'from_strategy' => $switchRecommendation['from'],
        'to_strategy' => $switchRecommendation['to'],
        'reason' => $switchRecommendation['reason'],
    ]);
    
    // Sync to platform
    $this->syncBiddingToPlatform($campaign);
    
    // Notify team
    $this->notifyBiddingChange($campaign, $switchRecommendation);
}
```

## 🎯 LEARNING PHASE DETECTION

```php
public function detectLearningPhase(string $campaignId): array
{
    $metrics = DB::selectOne("
        SELECT 
            COUNT(*) as days_active,
            SUM(conversions) as total_conversions,
            AVG(cpa) as avg_cpa,
            STDDEV(cpa) as cpa_stddev
        FROM cmis_analytics.daily_campaign_metrics
        WHERE campaign_id = ?
          AND date >= (SELECT start_date FROM cmis.campaigns WHERE id = ?)
    ", [$campaignId, $campaignId]);
    
    $isLearning = (
        $metrics->days_active < 7 ||
        $metrics->total_conversions < 50 ||
        $metrics->cpa_stddev > $metrics->avg_cpa * 0.3 // High volatility
    );
    
    return [
        'is_learning' => $isLearning,
        'days_active' => $metrics->days_active,
        'conversions' => $metrics->total_conversions,
        'stability_score' => $isLearning ? 0 : 1,
    ];
}
```

## 🎯 BIDDING STRATEGY DECISION TREE

```php
public function recommendBiddingStrategy(
    string $campaignId,
    string $objective,
    float $monthlyBudget,
    int $conversionsPerMonth
): string {
    // Decision tree based on campaign characteristics
    
    // New campaign (learning phase)
    if ($conversionsPerMonth < 50) {
        return 'lowest_cost'; // Let algorithm learn
    }
    
    // High volume + revenue tracking
    if ($conversionsPerMonth > 200 && $objective === 'SALES') {
        return 'target_roas'; // Optimize for revenue
    }
    
    // Medium volume + strict CPA goal
    if ($conversionsPerMonth >= 50 && $conversionsPerMonth < 200) {
        return 'target_cpa'; // Balance volume + efficiency
    }
    
    // Large budget + need for control
    if ($monthlyBudget > 50000) {
        return 'cost_cap'; // Strict cost control
    }
    
    // Default
    return 'lowest_cost';
}
```

## 🎯 PERFORMANCE-BASED AUTO-ADJUSTMENTS

```php
public function adjustBiddingTargets(string $orgId, string $campaignId): void
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    $campaign = Campaign::findOrFail($campaignId);
    $metrics = $this->getCampaignMetrics($campaignId, days: 7);
    
    // Target CPA adjustment
    if ($campaign->bidding_strategy === 'target_cpa') {
        $actualCPA = $metrics->cpa;
        $targetCPA = $campaign->target_cpa;
        
        if ($actualCPA < $targetCPA * 0.8) {
            // Performing well: lower target to scale
            $newTargetCPA = $actualCPA * 1.1;
            $campaign->update(['target_cpa' => $newTargetCPA]);
        } elseif ($actualCPA > $targetCPA * 1.2) {
            // Underperforming: raise target to get more volume
            $newTargetCPA = $actualCPA * 0.95;
            $campaign->update(['target_cpa' => $newTargetCPA]);
        }
    }
    
    // Target ROAS adjustment
    if ($campaign->bidding_strategy === 'target_roas') {
        $actualROAS = $metrics->roas;
        $targetROAS = $campaign->target_roas;
        
        if ($actualROAS > $targetROAS * 1.3) {
            // Beating target: increase ROAS goal
            $newTargetROAS = $actualROAS * 0.95;
            $campaign->update(['target_roas' => $newTargetROAS]);
        }
    }
}
```

## 🚨 CRITICAL RULES

**ALWAYS:**
- ✅ Wait minimum 7 days before switching strategies
- ✅ Require 50+ conversions before Target CPA/ROAS
- ✅ Monitor performance 7 days after switch
- ✅ Notify team before auto-switching
- ✅ Log all bidding changes

**NEVER:**
- ❌ Switch during learning phase (<7 days)
- ❌ Switch more than once per week
- ❌ Use Target ROAS with <100 conversions/month

## 📚 SWITCHING SCENARIOS

```
Scenario 1: Learning Phase → Target CPA
Campaign: New Product Launch
Day 1-14: Lowest Cost (gathering data)
Day 15: 65 conversions, $45 avg CPA
Action: Switch to Target CPA @ $45
Result: Volume maintained with cost predictability

Scenario 2: Target CPA → Cost Cap
Campaign: Lead Generation
Target CPA: $30
Actual CPA (14 days): $42 (40% over target)
Action: Switch to Cost Cap @ $36
Result: CPA controlled, volume reduced 20% (acceptable)

Scenario 3: Target CPA → Target ROAS
Campaign: E-Commerce
Target CPA: $50, Actual: $35
ROAS: 6.0x (consistently high)
Action: Switch to Target ROAS @ 5.0x
Result: Optimize for revenue, not just conversions

Scenario 4: Cost Cap → Target CPA
Campaign: App Install
Cost Cap: $5, delivery issues (limited reach)
Action: Switch to Target CPA @ $6
Result: Volume increased 40%, CPA acceptable
```

## 📚 REFERENCES
- Meta Bidding Strategies: https://www.facebook.com/business/help/1619591734742116
- Google Smart Bidding: https://support.google.com/google-ads/answer/7065882

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
