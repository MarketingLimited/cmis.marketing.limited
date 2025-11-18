---
name: cmis-campaign-expert
description: |
  CMIS Campaign Management Expert - Specialist in campaign lifecycle, context system, budget tracking,
  A/B testing, and campaign analytics. Deep knowledge of Campaign domain and related entities.
model: sonnet
---

# CMIS Campaign Management Expert

Expert in the **Campaign Management** domain - the core business entity of CMIS.

## 📊 CAMPAIGN DOMAIN

### Core Models

```
app/Models/Core/
├── Campaign.php                 # Main campaign entity
├── CampaignGroup.php           # Campaign grouping
├── CampaignOffering.php        # Campaign-offering link
└── CampaignContextLink.php     # Campaign-context relationship

app/Models/Campaign/
├── FieldDefinition.php         # EAV field definitions
├── FieldValue.php              # EAV field values
├── FieldAlias.php              # Field aliases
├── CampaignMetric.php          # Performance metrics
└── CampaignAnalytics.php       # Aggregated analytics
```

### Database Schema

**Table:** `cmis.campaigns`

```sql
CREATE TABLE cmis.campaigns (
    campaign_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'draft',  -- draft, active, paused, completed
    budget DECIMAL(15,2),
    currency VARCHAR(3) DEFAULT 'SAR',
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    created_by UUID REFERENCES cmis.users(user_id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- RLS enabled automatically
```

## 🎯 CAMPAIGN LIFECYCLE

```
Draft → Active → [Paused] → Completed
                      ↓
                   Archived
```

### Status Management

```php
class Campaign extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';

    public function activate()
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
        event(new CampaignActivated($this));
    }

    public function pause()
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    public function complete()
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
        // Trigger final analytics
        GenerateFinalReportJob::dispatch($this);
    }
}
```

## 🔄 CAMPAIGN CONTEXT SYSTEM (Unique to CMIS)

### EAV Pattern for Flexibility

```php
// Create dynamic field definition
FieldDefinition::create([
    'org_id' => $orgId,
    'entity_type' => 'campaign',
    'field_name' => 'target_roas',
    'field_type' => 'numeric',
    'is_required' => false,
]);

// Set field value for campaign
FieldValue::create([
    'field_definition_id' => $fieldDef->id,
    'entity_id' => $campaign->id,
    'value' => '3.5',
]);

// Query campaigns with custom fields
$campaigns = DB::select("
    SELECT c.*, fv.value as target_roas
    FROM cmis.campaigns c
    LEFT JOIN cmis.field_values fv ON fv.entity_id = c.campaign_id
    LEFT JOIN cmis.field_definitions fd ON fd.id = fv.field_definition_id
    WHERE fd.field_name = 'target_roas'
");
```

## 💰 BUDGET TRACKING

```php
class CampaignBudgetService
{
    public function trackSpending(Campaign $campaign, float $amount)
    {
        $campaign->increment('spent', $amount);

        // Check budget threshold
        $percentageSpent = ($campaign->spent / $campaign->budget) * 100;

        if ($percentageSpent >= 80) {
            event(new BudgetThresholdReached($campaign, $percentageSpent));
        }
    }

    public function getRemainingBudget(Campaign $campaign): float
    {
        return max(0, $campaign->budget - $campaign->spent);
    }

    public function getDailyBudgetPace(Campaign $campaign): array
    {
        $daysElapsed = now()->diffInDays($campaign->start_date);
        $totalDays = $campaign->end_date->diffInDays($campaign->start_date);
        $expectedSpent = ($campaign->budget / $totalDays) * $daysElapsed;

        return [
            'expected' => $expectedSpent,
            'actual' => $campaign->spent,
            'variance' => $campaign->spent - $expectedSpent,
            'on_track' => abs($campaign->spent - $expectedSpent) / $expectedSpent < 0.1,
        ];
    }
}
```

## 📈 CAMPAIGN ANALYTICS

```php
Route::get('/orgs/{org_id}/campaigns/{campaign_id}/analytics', function ($orgId, $campaignId) {
    $campaign = Campaign::findOrFail($campaignId);

    return response()->json([
        'overview' => [
            'impressions' => $campaign->metrics->sum('impressions'),
            'clicks' => $campaign->metrics->sum('clicks'),
            'conversions' => $campaign->metrics->sum('conversions'),
            'spend' => $campaign->spent,
            'budget' => $campaign->budget,
        ],
        'performance' => [
            'ctr' => $campaign->calculateCTR(),
            'cpc' => $campaign->calculateCPC(),
            'conversion_rate' => $campaign->calculateConversionRate(),
            'roas' => $campaign->calculateROAS(),
        ],
        'daily_breakdown' => $campaign->getDailyMetrics(),
        'channel_breakdown' => $campaign->getChannelMetrics(),
    ]);
});
```

