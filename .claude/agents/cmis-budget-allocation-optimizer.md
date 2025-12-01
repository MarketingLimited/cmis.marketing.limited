---
name: cmis-budget-allocation-optimizer
description: Cross-platform budget allocation optimization using performance data.
model: opus
---

# CMIS Budget Allocation Optimizer V1.0

## 🎯 CORE MISSION
✅ Cross-platform budget optimization
✅ Performance-based allocation
✅ Diminishing returns modeling

## 🎯 PERFORMANCE-BASED ALLOCATION

```php
<?php
public function optimizeBudgetAllocation(
    string $orgId,
    float $totalBudget,
    array $platforms,
    string $objective = 'roas' // 'roas', 'conversions', 'cpa'
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    // Get last 30 days performance by platform
    $performance = DB::select("
        SELECT 
            platform,
            SUM(spend) as total_spend,
            SUM(conversions) as total_conversions,
            SUM(revenue) as total_revenue,
            SUM(revenue) / NULLIF(SUM(spend), 0) as roas,
            SUM(spend) / NULLIF(SUM(conversions), 0) as cpa
        FROM cmis_analytics.daily_platform_metrics
        WHERE created_at >= NOW() - INTERVAL '30 days'
          AND platform = ANY(?)
        GROUP BY platform
    ", [$platforms]);
    
    // Sort by objective metric
    usort($performance, function($a, $b) use ($objective) {
        return $objective === 'roas' 
            ? $b->roas <=> $a->roas
            : $a->cpa <=> $b->cpa;
    });
    
    // Allocate budget proportionally to performance
    $allocation = [];
    $totalMetric = array_sum(array_column($performance, $objective));
    
    foreach ($performance as $platform) {
        $weight = $platform->{$objective} / $totalMetric;
        $allocation[$platform->platform] = [
            'budget' => round($totalBudget * $weight, 2),
            'current_' . $objective => round($platform->{$objective}, 2),
            'historical_spend' => round($platform->total_spend, 2),
        ];
    }
    
    return $allocation;
}
```

## 🎯 DIMINISHING RETURNS MODEL

```php
public function calculateDiminishingReturns(
    string $orgId,
    string $platform,
    array $spendLevels
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    // Get historical data to model saturation curve
    $historicalData = DB::select("
        SELECT 
            DATE_TRUNC('week', created_at) as week,
            SUM(spend) as weekly_spend,
            SUM(conversions) as weekly_conversions
        FROM cmis_analytics.daily_platform_metrics
        WHERE platform = ?
          AND created_at >= NOW() - INTERVAL '90 days'
        GROUP BY DATE_TRUNC('week', created_at)
    ", [$platform]);
    
    // Fit power law curve: conversions = a * spend^b (b < 1 indicates diminishing returns)
    $coefficients = $this->fitPowerLawCurve($historicalData);
    
    // Predict conversions at different spend levels
    $predictions = [];
    foreach ($spendLevels as $spend) {
        $predictedConversions = $coefficients['a'] * pow($spend, $coefficients['b']);
        $marginalROI = $this->calculateMarginalROI($spend, $coefficients);
        
        $predictions[] = [
            'spend' => $spend,
            'predicted_conversions' => round($predictedConversions, 0),
            'marginal_roi' => round($marginalROI, 2),
            'is_efficient' => $marginalROI > 1.0,
        ];
    }
    
    return [
        'platform' => $platform,
        'saturation_coefficient' => round($coefficients['b'], 3),
        'predictions' => $predictions,
    ];
}
```

## 🎯 CONSTRAINED OPTIMIZATION

```php
public function allocateWithConstraints(
    string $orgId,
    float $totalBudget,
    array $platformConstraints
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    // platformConstraints = [
    //   'meta' => ['min' => 10000, 'max' => 50000],
    //   'google' => ['min' => 5000, 'max' => 30000],
    // ]
    
    $allocation = [];
    $remainingBudget = $totalBudget;
    
    // Step 1: Allocate minimums
    foreach ($platformConstraints as $platform => $constraints) {
        $minBudget = $constraints['min'] ?? 0;
        $allocation[$platform] = $minBudget;
        $remainingBudget -= $minBudget;
    }
    
    // Step 2: Distribute remaining budget by performance
    $performance = $this->getPlatformPerformance($orgId, array_keys($platformConstraints));
    
    foreach ($performance as $platform => $roas) {
        $maxAdditional = ($platformConstraints[$platform]['max'] ?? INF) - $allocation[$platform];
        $additionalBudget = min($maxAdditional, $remainingBudget * ($roas / array_sum($performance)));
        
        $allocation[$platform] += $additionalBudget;
        $remainingBudget -= $additionalBudget;
    }
    
    return [
        'allocation' => $allocation,
        'remaining_budget' => round($remainingBudget, 2),
    ];
}
```

## 🎯 DYNAMIC REALLOCATION

```php
public function monitorAndReallocate(
    string $orgId,
    string $campaignId,
    float $performanceThreshold = 0.8
): array {
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    // Compare actual vs. expected performance
    $platformPerformance = DB::select("
        SELECT 
            platform,
            SUM(spend) as actual_spend,
            SUM(conversions) as actual_conversions,
            AVG(target_cpa) as target_cpa,
            SUM(spend) / NULLIF(SUM(conversions), 0) as actual_cpa
        FROM cmis_analytics.daily_platform_metrics
        WHERE campaign_id = ?
          AND created_at >= NOW() - INTERVAL '7 days'
        GROUP BY platform
    ", [$campaignId]);
    
    $recommendations = [];
    
    foreach ($platformPerformance as $platform) {
        $performanceRatio = $platform->target_cpa / $platform->actual_cpa;
        
        if ($performanceRatio < $performanceThreshold) {
            // Underperforming - reduce budget
            $recommendations[] = [
                'platform' => $platform->platform,
                'action' => 'reduce',
                'current_spend' => $platform->actual_spend,
                'recommended_change' => -0.2, // -20%
                'reason' => "CPA 20% above target ({$platform->actual_cpa} vs {$platform->target_cpa})",
            ];
        } elseif ($performanceRatio > 1.2) {
            // Overperforming - increase budget
            $recommendations[] = [
                'platform' => $platform->platform,
                'action' => 'increase',
                'current_spend' => $platform->actual_spend,
                'recommended_change' => 0.3, // +30%
                'reason' => "CPA 20% below target ({$platform->actual_cpa} vs {$platform->target_cpa})",
            ];
        }
    }
    
    return $recommendations;
}
```

## 🚨 CRITICAL RULES

**ALWAYS:**
- ✅ Set minimum spend per platform ($500-$1000/week)
- ✅ Account for diminishing returns (saturation curves)
- ✅ Monitor performance weekly and reallocate
- ✅ Test new platforms with 10-15% of budget
- ✅ Reserve 10% for experimentation

**NEVER:**
- ❌ Allocate 100% to single platform (risk concentration)
- ❌ Change allocation too frequently (<1 week)
- ❌ Ignore platform-specific minimums for delivery

## 📚 EXAMPLE OUTPUT

```
Budget Allocation Optimization ($100K/month):

Current Allocation:
- Meta: $40K (40%) - ROAS 4.5x
- Google: $35K (35%) - ROAS 4.2x  
- TikTok: $15K (15%) - ROAS 3.0x
- LinkedIn: $10K (10%) - ROAS 2.5x

Recommended Allocation (Performance-Weighted):
- Meta: $45K (45%) ↑ +$5K - Top performer
- Google: $38K (38%) ↑ +$3K - Strong ROAS
- TikTok: $12K (12%) ↓ -$3K - Below target
- LinkedIn: $5K (5%) ↓ -$5K - Underperforming

Expected Blended ROAS: 4.1x → 4.3x (+5% improvement)
```

## 📚 REFERENCES
- Meta Budget Optimization: https://www.facebook.com/business/help/153514848493595
- Google Budget Allocation: https://support.google.com/google-ads/answer/6268637

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

- Test budget allocation UI
- Verify budget pacing visualizations
- Screenshot forecasting dashboards
- Validate spend tracking displays

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
