# Phase 15: A/B Testing & Experimentation Framework

**Implementation Date:** 2025-11-21
**Status:** ✅ Complete
**Dependencies:** Phase 11-14 (Advanced Analytics, Scheduled Reports, Alerts, Data Export)

---

## Overview

Phase 15 implements a comprehensive A/B testing and experimentation framework for CMIS, enabling data-driven campaign optimization through controlled experiments, variant testing, and statistical significance analysis.

### Key Features

✅ **Experiment Management**: Create, start, pause, resume, and complete experiments
✅ **Multi-Variant Testing**: Support for control + multiple test variants
✅ **Statistical Analysis**: Z-test for proportions with confidence intervals
✅ **Event Tracking**: Track impressions, clicks, conversions, and custom events
✅ **Time-Series Analysis**: Daily aggregated results and trend visualization
✅ **Winner Determination**: Automated winner selection based on statistical significance
✅ **Real-Time Metrics**: Live performance tracking per variant
✅ **Multi-Tenancy Support**: Full RLS policies for organization isolation

---

## Architecture

### Database Schema

#### 1. `cmis.experiments`
Main experiment configuration and metadata.

**Key Fields:**
- `experiment_type`: campaign, content, audience, budget
- `metric`: Primary optimization metric (conversion_rate, ctr, roi, etc.)
- `status`: draft, running, paused, completed, cancelled
- `confidence_level`: Statistical confidence level (default 95%)
- `minimum_detectable_effect`: MDE threshold (default 5%)
- `traffic_allocation`: equal, weighted, adaptive
- `winner_variant_id`: Selected winner after completion
- `statistical_significance`: Final significance percentage

#### 2. `cmis.experiment_variants`
Individual variants within an experiment.

**Key Fields:**
- `is_control`: Boolean flag for control variant
- `traffic_percentage`: Traffic allocation (0-100%)
- `config`: Variant-specific configuration (JSON)
- `impressions`, `clicks`, `conversions`, `spend`, `revenue`: Aggregated metrics
- `conversion_rate`, `improvement_over_control`: Calculated performance
- `confidence_interval_lower/upper`: Statistical confidence bounds

#### 3. `cmis.experiment_results`
Daily aggregated time-series data per variant.

**Fields:** date, impressions, clicks, conversions, spend, revenue, ctr, cpc, conversion_rate, roi

####  4. `cmis.experiment_events`
Individual event tracking (impressions, clicks, conversions).

**Fields:** event_type, user_id, session_id, value, properties, occurred_at

---

## API Endpoints

All endpoints require authentication: `Authorization: Bearer {token}`

### Experiment Management

#### List Experiments
```http
GET /api/orgs/{org_id}/experiments
```

**Query Parameters:**
- `status` - Filter by status (draft, running, paused, completed)
- `experiment_type` - Filter by type (campaign, content, audience, budget)
- `entity_type`, `entity_id` - Filter by related entity
- `page`, `per_page` - Pagination

**Response:**
```json
{
  "success": true,
  "experiments": {
    "data": [
      {
        "experiment_id": "uuid",
        "name": "Campaign Budget Test",
        "experiment_type": "budget",
        "metric": "roi",
        "status": "running",
        "confidence_level": 95.00,
        "variants": [ ... ],
        "created_at": "2025-11-21T00:00:00Z"
      }
    ]
  }
}
```

#### Create Experiment
```http
POST /api/orgs/{org_id}/experiments
```

**Request Body:**
```json
{
  "name": "Headline A/B Test",
  "description": "Testing headline variations",
  "experiment_type": "content",
  "entity_type": "ad_set",
  "entity_id": "uuid",
  "metric": "conversion_rate",
  "metrics": ["ctr", "cpc", "roi"],
  "hypothesis": "Headline B will improve conversion rate by 10%",
  "duration_days": 14,
  "sample_size_per_variant": 5000,
  "confidence_level": 95.00,
  "minimum_detectable_effect": 5.00,
  "traffic_allocation": "equal",
  "control_config": {
    "headline": "Original Headline"
  }
}
```

**Response:** `201 Created`

#### Get Experiment Details
```http
GET /api/orgs/{org_id}/experiments/{experiment_id}
```

Returns full experiment details with variants and performance summary.

#### Update Experiment
```http
PUT /api/orgs/{org_id}/experiments/{experiment_id}
```

Note: Can only update draft experiments.

#### Delete Experiment
```http
DELETE /api/orgs/{org_id}/experiments/{experiment_id}
```

Note: Cannot delete running experiments.

### Experiment Actions

#### Start Experiment
```http
POST /api/orgs/{org_id}/experiments/{experiment_id}/start
```

**Requirements:**
- At least 2 variants (including control)
- Control variant must exist
- Status must be "draft"

#### Pause/Resume Experiment
```http
POST /api/orgs/{org_id}/experiments/{experiment_id}/pause
POST /api/orgs/{org_id}/experiments/{experiment_id}/resume
```

#### Complete Experiment
```http
POST /api/orgs/{org_id}/experiments/{experiment_id}/complete
```

**Response:**
```json
{
  "success": true,
  "experiment": { ... },
  "winner": {
    "variant_id": "uuid",
    "name": "Variant A",
    "improvement_over_control": 12.5
  },
  "significance_results": {
    "variant_a": {
      "p_value": 0.0123,
      "z_score": 2.45,
      "is_significant": true,
      "improvement": 12.5,
      "confidence_interval": {
        "lower": 0.0512,
        "upper": 0.0678
      }
    }
  }
}
```

### Variant Management

#### Add Variant
```http
POST /api/orgs/{org_id}/experiments/{experiment_id}/variants
```

**Request:**
```json
{
  "name": "Variant A",
  "description": "Alternative headline",
  "traffic_percentage": 50.00,
  "config": {
    "headline": "New Improved Headline"
  }
}
```

#### Update Variant
```http
PUT /api/orgs/{org_id}/experiments/{experiment_id}/variants/{variant_id}
```

### Results & Events

#### Record Event
```http
POST /api/orgs/{org_id}/experiments/{experiment_id}/events
```

**Request:**
```json
{
  "variant_id": "uuid",
  "event_type": "conversion",
  "user_id": "user123",
  "session_id": "session456",
  "value": 49.99,
  "properties": {
    "product_id": "prod789",
    "category": "electronics"
  }
}
```

#### Get Experiment Results
```http
GET /api/orgs/{org_id}/experiments/{experiment_id}/results
```

**Response:**
```json
{
  "success": true,
  "performance": {
    "experiment": {
      "id": "uuid",
      "name": "Test",
      "progress": 75.0,
      "remaining_days": 4
    },
    "variants": [
      {
        "id": "uuid",
        "name": "Control",
        "is_control": true,
        "performance": {
          "impressions": 10000,
          "clicks": 500,
          "conversions": 50,
          "ctr": 5.00,
          "conversion_rate": 0.50,
          "is_winning": false
        }
      },
      {
        "id": "uuid",
        "name": "Variant A",
        "is_control": false,
        "performance": {
          "impressions": 10000,
          "clicks": 550,
          "conversions": 65,
          "ctr": 5.50,
          "conversion_rate": 0.65,
          "improvement_over_control": 30.0,
          "is_winning": true
        }
      }
    ]
  },
  "time_series": { ... },
  "statistical_significance": { ... }
}
```

#### Get Statistics
```http
GET /api/orgs/{org_id}/experiments/stats
```

---

## Statistical Analysis

### Z-Test for Proportions

The service uses a Z-test to compare conversion rates between control and test variants:

**Formula:**
```
z = (p2 - p1) / SE

where:
p1 = control conversion rate
p2 = variant conversion rate
SE = sqrt(p_pool * (1 - p_pool) * (1/n1 + 1/n2))
p_pool = (conversions1 + conversions2) / (impressions1 + impressions2)
```

### Confidence Intervals

95% confidence interval for difference in conversion rates:

```
CI = p2 ± (z_critical * SE_diff)

where:
SE_diff = sqrt((p1*(1-p1)/n1) + (p2*(1-p2)/n2))
z_critical = 1.960 for 95% confidence
```

### Winner Determination

A variant is declared the winner if:
1. Statistical significance is achieved (p-value < 0.05)
2. Improvement ≥ minimum detectable effect
3. It has the highest improvement among all significant variants

---

## ExperimentService Methods

### Core Methods

```php
// Create experiment with control variant
$experiment = $experimentService->createExperiment($orgId, $userId, $data);

// Add test variant
$variant = $experimentService->addVariant($experiment, $variantData);

// Record events
$event = $experimentService->recordEvent($experimentId, $variantId, 'conversion', [
    'user_id' => 'user123',
    'value' => 49.99
]);

// Aggregate daily results
$experimentService->aggregateDailyResults($experiment);

// Calculate statistical significance
$results = $experimentService->calculateStatisticalSignificance($experiment);

// Determine winner
$winner = $experimentService->determineWinner($experiment);

// Get performance summary
$summary = $experimentService->getPerformanceSummary($experiment);

// Get time-series data
$timeSeries = $experimentService->getTimeSeriesData($experiment);
```

---

## Frontend Integration

### Alpine.js Component

**File:** `resources/js/components/experiments.js`

**Usage:**
```html
<div x-data="experiments" data-org-id="{{ $orgId }}">
    <!-- Component UI -->
</div>
```

### Component Features

- **List View**: Browse all experiments with filtering
- **Create Modal**: Create new experiments with variant configuration
- **Variant Management**: Add and configure test variants
- **Experiment Control**: Start, pause, resume, complete
- **Results Dashboard**: View performance metrics and significance
- **Statistics**: Organization-wide experiment stats

### Key Methods

```javascript
// Create experiment
await this.createExperiment();

// Start experiment
await this.startExperiment(experimentId);

// Complete and determine winner
await this.completeExperiment(experimentId);

// View results
await this.viewResults(experimentId);
```

---

## Use Cases

### 1. Campaign Budget Optimization

**Objective:** Test different budget allocation strategies

**Setup:**
- Experiment Type: `budget`
- Metric: `roi`
- Variants:
  - Control: Current allocation
  - Variant A: Weighted toward high performers
  - Variant B: Adaptive allocation

**Analysis:** Measure ROI improvement and select winning strategy

### 2. Ad Creative Testing

**Objective:** Test headline and image variations

**Setup:**
- Experiment Type: `content`
- Metric: `conversion_rate`
- Variants:
  - Control: Original creative
  - Variant A: New headline
  - Variant B: New image
  - Variant C: Both changes

**Analysis:** Identify creative elements that drive conversions

### 3. Audience Targeting

**Objective:** Test different audience segments

**Setup:**
- Experiment Type: `audience`
- Metric: `cpa` (Cost Per Acquisition)
- Variants:
  - Control: Broad targeting
  - Variant A: Lookalike audience
  - Variant B: Interest-based targeting

**Analysis:** Find most cost-effective audience

### 4. Bidding Strategy

**Objective:** Compare automated vs manual bidding

**Setup:**
- Experiment Type: `campaign`
- Metric: `roas` (Return on Ad Spend)
- Variants:
  - Control: Manual bidding
  - Variant A: Automated bidding

**Analysis:** Evaluate ROAS and efficiency gains

---

## Best Practices

### Experiment Design

1. **Clear Hypothesis**: Define specific, measurable hypothesis
2. **Single Variable**: Test one variable at a time for clear attribution
3. **Sufficient Sample Size**: Ensure statistical power (typically 1000+ per variant)
4. **Adequate Duration**: Run for at least 1-2 weeks to account for daily/weekly patterns
5. **Traffic Allocation**: Start with equal split, adjust only if needed

### Statistical Rigor

1. **Pre-define Success Metrics**: Set primary metric before starting
2. **Avoid Peeking**: Let experiment run full duration before checking results
3. **Check Assumptions**: Ensure enough impressions for valid z-test
4. **Consider Seasonality**: Account for holidays, events, day-of-week effects
5. **Document Learning**: Record insights regardless of outcome

### Implementation

1. **Start Small**: Begin with simple A/B tests before multi-variate
2. **Iterate**: Use learnings to inform next experiments
3. **Test Continuously**: Maintain experimentation pipeline
4. **Share Results**: Communicate findings across team
5. **Implement Winners**: Apply winning variants to campaigns

---

## Security & Multi-Tenancy

### Row-Level Security

All tables have RLS policies ensuring organization isolation:

```sql
CREATE POLICY org_isolation ON cmis.experiments
USING (org_id = current_setting('app.current_org_id')::uuid);
```

Variants, results, and events inherit isolation from experiments.

### Authentication

All endpoints require Sanctum authentication and initialize transaction context:

```php
DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
    $user->user_id,
    $orgId
]);
```

---

## Testing

### Unit Tests

Test statistical methods:

```php
/** @test */
public function it_calculates_statistical_significance()
{
    $service = new ExperimentService();

    $significance = $service->calculateStatisticalSignificance($experiment);

    $this->assertArrayHasKey('p_value', $significance);
    $this->assertArrayHasKey('z_score', $significance);
    $this->assertArrayHasKey('is_significant', $significance);
}
```

### Integration Tests

Test full experiment lifecycle:

```php
/** @test */
public function it_completes_full_experiment_lifecycle()
{
    // Create experiment
    $experiment = Experiment::factory()->create();

    // Add variants
    $control = ExperimentVariant::factory()->control()->create(...);
    $variant = ExperimentVariant::factory()->create(...);

    // Start experiment
    $experiment->start();
    $this->assertEquals('running', $experiment->status);

    // Record events
    $service->recordEvent($experiment->experiment_id, $control->variant_id, 'impression');
    $service->recordEvent($experiment->experiment_id, $variant->variant_id, 'conversion');

    // Complete experiment
    $experiment->complete();
    $this->assertEquals('completed', $experiment->status);
    $this->assertNotNull($experiment->winner_variant_id);
}
```

---

## Summary

Phase 15 provides a production-ready A/B testing framework for CMIS, enabling:

✅ **Scientific Testing**: Rigorous statistical analysis with confidence intervals
✅ **Easy Management**: Intuitive API and UI for experiment lifecycle
✅ **Real-Time Tracking**: Live performance metrics and event tracking
✅ **Automated Winner Selection**: Statistical significance-based decisions
✅ **Multi-Tenancy**: Full organizational isolation with RLS
✅ **Scalability**: Efficient time-series aggregation and event storage

The framework empowers data-driven optimization of campaigns, creative, audiences, and budgets through controlled experimentation.

---

**Next Potential Phases:**
- Phase 16: Predictive Analytics & ML Models
- Phase 17: Budget Optimization & Auto-Allocation
- Phase 18: Performance Benchmarking
- Phase 19: Advanced Attribution Modeling
- Phase 20: Executive Dashboards & Reporting
