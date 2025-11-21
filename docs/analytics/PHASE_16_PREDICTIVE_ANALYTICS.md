# Phase 16: Predictive Analytics & Forecasting

**Implementation Date:** 2025-11-21
**Status:** ✅ Complete
**Dependencies:** Phases 0-15

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Database Schema](#database-schema)
4. [Models](#models)
5. [Services](#services)
6. [API Endpoints](#api-endpoints)
7. [Frontend Components](#frontend-components)
8. [Statistical Methods](#statistical-methods)
9. [Use Cases](#use-cases)
10. [Testing](#testing)

---

## Overview

Phase 16 introduces comprehensive predictive analytics capabilities to CMIS, enabling organizations to:

- **Forecast future performance** using multiple statistical algorithms
- **Detect anomalies** in real-time data streams
- **Analyze trends** to understand performance patterns
- **Generate intelligent recommendations** for optimization

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Predictive Analytics                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │ Forecasting │  │   Anomaly    │  │      Trend      │   │
│  │   Service   │  │   Detection  │  │    Analysis     │   │
│  └─────────────┘  └──────────────┘  └─────────────────┘   │
│         │                 │                   │            │
│         └─────────────────┴───────────────────┘            │
│                           │                                │
│                  ┌────────▼────────┐                       │
│                  │  Recommendation │                       │
│                  │     Engine      │                       │
│                  └─────────────────┘                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Features

### 1. Time-Series Forecasting

Generate predictions for future performance using:

- **Moving Average** - Simple 7-day window averaging
- **Linear Regression** - Trend-based predictions with slope calculation
- **Weighted Average** - Recent data weighted more heavily

**Key Capabilities:**
- 95% confidence intervals for all predictions
- Actual value tracking for accuracy measurement
- Multi-metric forecasting (revenue, conversions, spend, ROI)
- 1-90 day forecast horizons

### 2. Anomaly Detection

Automatically detect unusual patterns in data:

- **Z-score based detection** (threshold > 2 for 95% confidence)
- **Severity classification** (low, medium, high, critical)
- **Type classification** (spike, drop, drift, seasonal)
- **Lifecycle management** (new → acknowledged → resolved → false positive)

**Detection Features:**
- Configurable detection windows (7-90 days)
- Multi-metric monitoring
- Deviation percentage calculation
- Confidence scoring

### 3. Trend Analysis

Identify and classify performance trends:

- **Trend Types:** upward, downward, stable, seasonal, volatile
- **Statistical Rigor:** R² coefficient of determination
- **Slope Calculation:** Linear regression slope for trend strength
- **Seasonality Detection:** Pattern recognition for cyclical trends

### 4. Intelligent Recommendations

AI-powered optimization suggestions:

- **Performance Decline Detection** - Identify 20%+ drops
- **Growth Opportunity Detection** - Spot strong upward trends
- **Budget Optimization** - Suggest budget increases for high performers
- **Priority Scoring** - Critical, high, medium, low priority classification
- **Confidence Scoring** - 0-100% confidence in recommendations

**Recommendation Lifecycle:**
1. **Pending** - Newly generated, awaiting review
2. **Accepted** - User approved, ready for implementation
3. **Rejected** - User declined with optional reason
4. **Implemented** - Successfully applied with notes
5. **Expired** - 7-day expiration for time-sensitive suggestions

---

## Database Schema

### 1. Forecasts Table

```sql
CREATE TABLE cmis.forecasts (
    forecast_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    entity_type VARCHAR(50),
    entity_id UUID,
    metric VARCHAR(100) NOT NULL,
    forecast_type VARCHAR(50) NOT NULL,
    forecast_date DATE NOT NULL,
    predicted_value DECIMAL(15,2) NOT NULL,
    confidence_lower DECIMAL(15,2),
    confidence_upper DECIMAL(15,2),
    confidence_level DECIMAL(5,2) DEFAULT 95.00,
    actual_value DECIMAL(15,2),
    accuracy_percentage DECIMAL(5,2),
    generated_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- RLS Policy
ALTER TABLE cmis.forecasts ENABLE ROW LEVEL SECURITY;
CREATE POLICY org_isolation ON cmis.forecasts
USING (org_id = current_setting('app.current_org_id')::uuid);

-- Indexes
CREATE INDEX idx_forecasts_org_entity ON cmis.forecasts(org_id, entity_type, entity_id);
CREATE INDEX idx_forecasts_metric ON cmis.forecasts(metric);
CREATE INDEX idx_forecasts_date ON cmis.forecasts(forecast_date);
```

### 2. Anomalies Table

```sql
CREATE TABLE cmis.anomalies (
    anomaly_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    entity_type VARCHAR(50),
    entity_id UUID,
    metric VARCHAR(100) NOT NULL,
    anomaly_type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL,
    expected_value DECIMAL(15,2) NOT NULL,
    actual_value DECIMAL(15,2) NOT NULL,
    deviation_percentage DECIMAL(8,2),
    confidence_score DECIMAL(5,2),
    detected_date DATE NOT NULL,
    description TEXT,
    status VARCHAR(30) DEFAULT 'new',
    acknowledged_at TIMESTAMP,
    acknowledged_by UUID,
    resolved_at TIMESTAMP,
    resolution_notes TEXT,
    false_positive BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- RLS Policy
ALTER TABLE cmis.anomalies ENABLE ROW LEVEL SECURITY;
CREATE POLICY org_isolation ON cmis.anomalies
USING (org_id = current_setting('app.current_org_id')::uuid);

-- Indexes
CREATE INDEX idx_anomalies_org_entity ON cmis.anomalies(org_id, entity_type, entity_id);
CREATE INDEX idx_anomalies_status ON cmis.anomalies(status);
CREATE INDEX idx_anomalies_severity ON cmis.anomalies(severity);
CREATE INDEX idx_anomalies_date ON cmis.anomalies(detected_date);
```

### 3. Recommendations Table

```sql
CREATE TABLE cmis.recommendations (
    recommendation_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    entity_type VARCHAR(50),
    entity_id UUID,
    recommendation_type VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    priority VARCHAR(20) NOT NULL,
    confidence_score DECIMAL(5,2) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    action_details JSONB,
    status VARCHAR(30) DEFAULT 'pending',
    accepted_at TIMESTAMP,
    accepted_by UUID,
    rejected_at TIMESTAMP,
    rejected_by UUID,
    rejection_reason TEXT,
    implemented_at TIMESTAMP,
    implementation_notes TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- RLS Policy
ALTER TABLE cmis.recommendations ENABLE ROW LEVEL SECURITY;
CREATE POLICY org_isolation ON cmis.recommendations
USING (org_id = current_setting('app.current_org_id')::uuid);

-- Indexes
CREATE INDEX idx_recommendations_org_entity ON cmis.recommendations(org_id, entity_type, entity_id);
CREATE INDEX idx_recommendations_status ON cmis.recommendations(status);
CREATE INDEX idx_recommendations_priority ON cmis.recommendations(priority);
CREATE INDEX idx_recommendations_expires ON cmis.recommendations(expires_at);
```

### 4. Trend Analysis Table

```sql
CREATE TABLE cmis.trend_analysis (
    trend_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    entity_type VARCHAR(50),
    entity_id UUID,
    metric VARCHAR(100) NOT NULL,
    trend_type VARCHAR(50) NOT NULL,
    trend_strength DECIMAL(8,2),
    confidence DECIMAL(5,2),
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    data_points INTEGER,
    slope DECIMAL(12,4),
    seasonality_detected JSONB,
    pattern_details JSONB,
    interpretation TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- RLS Policy
ALTER TABLE cmis.trend_analysis ENABLE ROW LEVEL SECURITY;
CREATE POLICY org_isolation ON cmis.trend_analysis
USING (org_id = current_setting('app.current_org_id')::uuid);

-- Indexes
CREATE INDEX idx_trends_org_entity ON cmis.trend_analysis(org_id, entity_type, entity_id);
CREATE INDEX idx_trends_metric ON cmis.trend_analysis(metric);
CREATE INDEX idx_trends_period ON cmis.trend_analysis(period_end);
```

### 5. Prediction Models Table

```sql
CREATE TABLE cmis.prediction_models (
    model_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    model_name VARCHAR(100) NOT NULL,
    model_type VARCHAR(50) NOT NULL,
    algorithm VARCHAR(100) NOT NULL,
    target_metric VARCHAR(100) NOT NULL,
    features JSONB,
    hyperparameters JSONB,
    training_data_size INTEGER,
    test_data_size INTEGER,
    accuracy_score DECIMAL(5,2),
    mae DECIMAL(15,4),
    rmse DECIMAL(15,4),
    r_squared DECIMAL(5,4),
    trained_at TIMESTAMP,
    last_prediction_at TIMESTAMP,
    status VARCHAR(30) DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- RLS Policy
ALTER TABLE cmis.prediction_models ENABLE ROW LEVEL SECURITY;
CREATE POLICY org_isolation ON cmis.prediction_models
USING (org_id = current_setting('app.current_org_id')::uuid);

-- Indexes
CREATE INDEX idx_models_org ON cmis.prediction_models(org_id);
CREATE INDEX idx_models_status ON cmis.prediction_models(status);
```

---

## Models

### Forecast Model

**File:** `app/Models/Analytics/Forecast.php`

```php
class Forecast extends Model
{
    protected $table = 'cmis.forecasts';
    protected $primaryKey = 'forecast_id';

    protected $fillable = [
        'org_id', 'entity_type', 'entity_id', 'metric', 'forecast_type',
        'forecast_date', 'predicted_value', 'confidence_lower',
        'confidence_upper', 'confidence_level', 'actual_value',
        'accuracy_percentage', 'generated_at'
    ];

    protected $casts = [
        'predicted_value' => 'decimal:2',
        'confidence_lower' => 'decimal:2',
        'confidence_upper' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'accuracy_percentage' => 'decimal:2',
        'forecast_date' => 'date',
        'generated_at' => 'datetime'
    ];

    // Update with actual value
    public function updateActual(float $actualValue): void;

    // Check if prediction was accurate
    public function isAccurate(): bool;

    // Calculate accuracy percentage
    public function getAccuracyPercentage(): float;
}
```

### Anomaly Model

**File:** `app/Models/Analytics/Anomaly.php`

```php
class Anomaly extends Model
{
    protected $table = 'cmis.anomalies';
    protected $primaryKey = 'anomaly_id';

    protected $fillable = [
        'org_id', 'entity_type', 'entity_id', 'metric', 'anomaly_type',
        'severity', 'expected_value', 'actual_value', 'deviation_percentage',
        'confidence_score', 'detected_date', 'description', 'status'
    ];

    protected $casts = [
        'expected_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'deviation_percentage' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'detected_date' => 'date',
        'false_positive' => 'boolean'
    ];

    // Lifecycle methods
    public function acknowledge(string $userId, ?string $notes = null): void;
    public function resolve(string $resolutionNotes): void;
    public function markFalsePositive(): void;
}
```

### Recommendation Model

**File:** `app/Models/Analytics/Recommendation.php`

```php
class Recommendation extends Model
{
    protected $table = 'cmis.recommendations';
    protected $primaryKey = 'recommendation_id';

    protected $fillable = [
        'org_id', 'entity_type', 'entity_id', 'recommendation_type',
        'category', 'priority', 'confidence_score', 'title',
        'description', 'action_details', 'status', 'expires_at'
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'action_details' => 'array',
        'expires_at' => 'datetime'
    ];

    // Lifecycle methods
    public function accept(string $userId): void;
    public function reject(string $userId, ?string $reason = null): void;
    public function implement(?string $notes = null): void;
    public function isExpired(): bool;
}
```

### TrendAnalysis Model

**File:** `app/Models/Analytics/TrendAnalysis.php`

```php
class TrendAnalysis extends Model
{
    protected $table = 'cmis.trend_analysis';
    protected $primaryKey = 'trend_id';

    protected $fillable = [
        'org_id', 'entity_type', 'entity_id', 'metric', 'trend_type',
        'trend_strength', 'confidence', 'period_start', 'period_end',
        'data_points', 'slope', 'seasonality_detected', 'pattern_details',
        'interpretation'
    ];

    protected $casts = [
        'trend_strength' => 'decimal:2',
        'confidence' => 'decimal:2',
        'slope' => 'decimal:4',
        'period_start' => 'date',
        'period_end' => 'date',
        'seasonality_detected' => 'array',
        'pattern_details' => 'array'
    ];

    // Helper methods
    public function isPositiveTrend(): bool;
    public function isNegativeTrend(): bool;
    public function hasSeasonality(): bool;
}
```

---

## Services

### ForecastingService

**File:** `app/Services/Analytics/ForecastingService.php`

#### Forecasting Methods

**1. Moving Average Forecast**

```php
public function generateForecast(
    string $orgId,
    string $entityType,
    string $entityId,
    string $metric,
    int $days = 30,
    string $forecastType = 'moving_average'
): array
```

Uses 7-day moving average with 95% confidence intervals based on standard deviation.

**Algorithm:**
1. Calculate average of last 7 data points
2. Calculate standard deviation
3. Generate confidence interval: mean ± (1.96 × σ)
4. Project forward for requested days

**2. Linear Regression Forecast**

```php
protected function linearRegressionForecast(array $data, int $days): array
```

Least squares regression with residual standard error for confidence intervals.

**Algorithm:**
1. Calculate slope: m = (n·Σxy - Σx·Σy) / (n·Σx² - (Σx)²)
2. Calculate intercept: b = (Σy - m·Σx) / n
3. Calculate residual standard error
4. Generate predictions: y = mx + b

**3. Weighted Average Forecast**

```php
protected function weightedAverageForecast(array $data, int $days): array
```

Recent data weighted more heavily with linear weights (1, 2, 3, ..., n).

**Algorithm:**
1. Apply linear weights to last 14 data points
2. Calculate weighted average: Σ(value × weight) / Σweight
3. Calculate standard deviation for confidence
4. Project forward with weighted mean

#### Anomaly Detection

```php
public function detectAnomalies(
    string $orgId,
    string $entityType,
    string $entityId,
    string $metric,
    int $days = 30
): array
```

**Z-Score Detection:**
1. Calculate mean (μ) and standard deviation (σ)
2. Compute z-score: z = |x - μ| / σ
3. Threshold: z > 2 (95% confidence) or z > 3 (99% confidence)
4. Classify severity:
   - z > 3: critical
   - z > 2.5: high
   - z > 2: medium

#### Trend Analysis

```php
public function analyzeTrends(
    string $orgId,
    string $entityType,
    string $entityId,
    string $metric,
    int $days = 30
): TrendAnalysis
```

**Linear Regression Slope:**
1. Calculate slope using least squares
2. Determine trend strength: (slope / mean) × 100
3. Classify trend type:
   - |strength| < 1%: stable
   - strength > 5%: upward
   - strength < -5%: downward
4. Calculate R² for confidence

#### Recommendation Generation

```php
public function generateRecommendations(
    string $orgId,
    string $entityType,
    string $entityId
): array
```

**Detection Rules:**

1. **Performance Decline** (recent avg < overall avg × 0.8)
   - Priority: high
   - Action: review_settings
   - Confidence: 75%

2. **Strong Growth** (upward trend with strength > 10%)
   - Priority: medium
   - Action: increase_budget
   - Confidence: 80%
   - Suggestion: 20% budget increase

---

## API Endpoints

### Forecasting Endpoints

#### Generate Forecast

```http
POST /api/orgs/{org_id}/analytics/forecasts
```

**Request Body:**
```json
{
    "entity_type": "campaign",
    "entity_id": "uuid",
    "metric": "revenue",
    "days": 30,
    "forecast_type": "moving_average"
}
```

**Response:**
```json
{
    "success": true,
    "forecasts": [
        {
            "forecast_id": "uuid",
            "metric": "revenue",
            "forecast_date": "2025-11-22",
            "predicted_value": 1500.00,
            "confidence_lower": 1200.00,
            "confidence_upper": 1800.00,
            "confidence_level": 95.00
        }
    ],
    "count": 30
}
```

#### List Forecasts

```http
GET /api/orgs/{org_id}/analytics/forecasts?entity_type=campaign&metric=revenue&page=1
```

#### Get Forecast Details

```http
GET /api/orgs/{org_id}/analytics/forecasts/{forecast_id}
```

#### Update Forecast with Actual Value

```http
PUT /api/orgs/{org_id}/analytics/forecasts/{forecast_id}
```

**Request Body:**
```json
{
    "actual_value": 1550.00
}
```

### Anomaly Detection Endpoints

#### Detect Anomalies

```http
POST /api/orgs/{org_id}/analytics/anomalies/detect
```

**Request Body:**
```json
{
    "entity_type": "campaign",
    "entity_id": "uuid",
    "metric": "conversions",
    "days": 30
}
```

**Response:**
```json
{
    "success": true,
    "anomalies": [
        {
            "anomaly_id": "uuid",
            "anomaly_type": "spike",
            "severity": "high",
            "expected_value": 100.00,
            "actual_value": 250.00,
            "deviation_percentage": 150.00,
            "confidence_score": 95.00,
            "description": "Conversions increased significantly (150%) compared to expected baseline."
        }
    ],
    "count": 1
}
```

#### List Anomalies

```http
GET /api/orgs/{org_id}/analytics/anomalies?status=new&severity=high
```

#### Acknowledge Anomaly

```http
POST /api/orgs/{org_id}/analytics/anomalies/{anomaly_id}/acknowledge
```

**Request Body:**
```json
{
    "notes": "Investigating spike in conversions"
}
```

#### Resolve Anomaly

```http
POST /api/orgs/{org_id}/analytics/anomalies/{anomaly_id}/resolve
```

**Request Body:**
```json
{
    "resolution_notes": "Caused by Black Friday promotion. Expected behavior."
}
```

#### Mark as False Positive

```http
POST /api/orgs/{org_id}/analytics/anomalies/{anomaly_id}/false-positive
```

### Trend Analysis Endpoints

#### Analyze Trends

```http
POST /api/orgs/{org_id}/analytics/trends
```

**Request Body:**
```json
{
    "entity_type": "campaign",
    "entity_id": "uuid",
    "metric": "roi",
    "days": 30
}
```

**Response:**
```json
{
    "success": true,
    "trend": {
        "trend_id": "uuid",
        "trend_type": "upward",
        "trend_strength": 12.50,
        "confidence": 85.00,
        "slope": 0.0125,
        "data_points": 30,
        "interpretation": "ROI is showing positive growth (12.5% trend strength)."
    }
}
```

#### List Trends

```http
GET /api/orgs/{org_id}/analytics/trends?entity_type=campaign&trend_type=upward
```

### Recommendation Endpoints

#### Generate Recommendations

```http
POST /api/orgs/{org_id}/analytics/recommendations/generate
```

**Request Body:**
```json
{
    "entity_type": "campaign",
    "entity_id": "uuid"
}
```

**Response:**
```json
{
    "success": true,
    "recommendations": [
        {
            "recommendation_id": "uuid",
            "recommendation_type": "budget_increase",
            "category": "budget",
            "priority": "medium",
            "confidence_score": 80.00,
            "title": "Strong revenue growth detected",
            "description": "Consider increasing budget to capitalize on positive performance trend.",
            "action_details": {
                "action": "increase_budget",
                "metric": "revenue",
                "trend_strength": 15.5,
                "suggested_increase": "20%"
            },
            "expires_at": "2025-11-28"
        }
    ],
    "count": 1
}
```

#### List Recommendations

```http
GET /api/orgs/{org_id}/analytics/recommendations?status=pending&priority=high
```

#### Accept Recommendation

```http
POST /api/orgs/{org_id}/analytics/recommendations/{recommendation_id}/accept
```

#### Reject Recommendation

```http
POST /api/orgs/{org_id}/analytics/recommendations/{recommendation_id}/reject
```

**Request Body:**
```json
{
    "rejection_reason": "Budget already maximized for this quarter"
}
```

#### Implement Recommendation

```http
POST /api/orgs/{org_id}/analytics/recommendations/{recommendation_id}/implement
```

**Request Body:**
```json
{
    "implementation_notes": "Increased daily budget from $100 to $120"
}
```

### Statistics Endpoint

```http
GET /api/orgs/{org_id}/analytics/stats
```

**Response:**
```json
{
    "success": true,
    "stats": {
        "forecasts": {
            "total": 150,
            "with_actuals": 45,
            "accuracy_rate": 85.50
        },
        "anomalies": {
            "total": 23,
            "new": 5,
            "acknowledged": 8,
            "resolved": 9,
            "false_positives": 1
        },
        "recommendations": {
            "total": 34,
            "pending": 12,
            "accepted": 15,
            "implemented": 5,
            "rejected": 2
        },
        "trends": {
            "total": 67,
            "upward": 28,
            "downward": 15,
            "stable": 24
        },
        "recent": {
            "anomalies": [...],
            "recommendations": [...],
            "trends": [...]
        }
    }
}
```

---

## Frontend Components

### Alpine.js Component

**File:** `resources/js/components/predictiveAnalytics.js`

**Usage:**

```html
<div x-data="predictiveAnalytics()" data-org-id="{{ $orgId }}">
    <!-- Dashboard Tab -->
    <div x-show="activeTab === 'dashboard'">
        <h2>Predictive Analytics Dashboard</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Forecasts</h3>
                <p x-text="stats.forecasts.total"></p>
                <span x-text="formatPercentage(stats.forecasts.accuracy_rate)"></span>
            </div>
            <!-- More stat cards -->
        </div>
    </div>

    <!-- Forecasts Tab -->
    <div x-show="activeTab === 'forecasts'">
        <button @click="switchTab('forecast-generator')">
            Generate Forecast
        </button>

        <table>
            <template x-for="forecast in forecasts">
                <tr @click="viewForecast(forecast.forecast_id)">
                    <td x-text="forecast.metric"></td>
                    <td x-text="formatDate(forecast.forecast_date)"></td>
                    <td x-text="formatNumber(forecast.predicted_value)"></td>
                </tr>
            </template>
        </table>
    </div>

    <!-- Anomalies Tab -->
    <div x-show="activeTab === 'anomalies'">
        <button @click="switchTab('anomaly-detector')">
            Detect Anomalies
        </button>

        <div x-for="anomaly in anomalies">
            <div :class="getSeverityClass(anomaly.severity)">
                <h4 x-text="anomaly.description"></h4>
                <button @click="acknowledgeAnomaly(anomaly.anomaly_id)">
                    Acknowledge
                </button>
                <button @click="resolveAnomaly(anomaly.anomaly_id)">
                    Resolve
                </button>
            </div>
        </div>
    </div>

    <!-- Recommendations Tab -->
    <div x-show="activeTab === 'recommendations'">
        <div x-for="rec in recommendations">
            <div :class="getPriorityClass(rec.priority)">
                <h4 x-text="rec.title"></h4>
                <p x-text="rec.description"></p>
                <button @click="acceptRecommendation(rec.recommendation_id)">
                    Accept
                </button>
                <button @click="rejectRecommendation(rec.recommendation_id)">
                    Reject
                </button>
            </div>
        </div>
    </div>
</div>
```

**Component Registration:**

```javascript
// resources/js/components/index.js
import predictiveAnalytics from './predictiveAnalytics.js';

if (window.Alpine) {
    window.Alpine.data('predictiveAnalytics', predictiveAnalytics);
}
```

---

## Statistical Methods

### 1. Moving Average

**Formula:**
```
MA = (x₁ + x₂ + ... + xₙ) / n
```

**Confidence Interval:**
```
CI = μ ± (z × σ)
where z = 1.96 for 95% confidence
```

### 2. Linear Regression

**Slope Calculation:**
```
m = (n·Σxy - Σx·Σy) / (n·Σx² - (Σx)²)
```

**Intercept:**
```
b = (Σy - m·Σx) / n
```

**Prediction:**
```
ŷ = mx + b
```

### 3. Z-Score (Anomaly Detection)

**Formula:**
```
z = (x - μ) / σ

where:
- x = observed value
- μ = mean
- σ = standard deviation
```

**Interpretation:**
- |z| > 3: 99.7% confidence (critical)
- |z| > 2: 95% confidence (high)
- |z| > 1: 68% confidence (medium)

### 4. R² (Coefficient of Determination)

**Formula:**
```
R² = 1 - (SS_res / SS_tot)

where:
- SS_res = Σ(yᵢ - ŷᵢ)²  (residual sum of squares)
- SS_tot = Σ(yᵢ - ȳ)²   (total sum of squares)
```

**Interpretation:**
- R² = 1: Perfect fit
- R² = 0.7-0.9: Strong relationship
- R² = 0.5-0.7: Moderate relationship
- R² < 0.5: Weak relationship

---

## Use Cases

### 1. Revenue Forecasting

**Scenario:** Predict next 30 days of campaign revenue

```bash
curl -X POST https://api.cmis.marketing/api/orgs/{org_id}/analytics/forecasts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "entity_type": "campaign",
    "entity_id": "campaign-uuid",
    "metric": "revenue",
    "days": 30,
    "forecast_type": "linear_regression"
  }'
```

**Result:** 30-day revenue predictions with confidence intervals

### 2. Conversion Anomaly Detection

**Scenario:** Detect unusual spikes or drops in conversions

```bash
curl -X POST https://api.cmis.marketing/api/orgs/{org_id}/analytics/anomalies/detect \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "entity_type": "campaign",
    "entity_id": "campaign-uuid",
    "metric": "conversions",
    "days": 14
  }'
```

**Result:** List of detected anomalies with severity and deviation percentages

### 3. ROI Trend Analysis

**Scenario:** Analyze ROI trends over 60 days

```bash
curl -X POST https://api.cmis.marketing/api/orgs/{org_id}/analytics/trends \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "entity_type": "campaign",
    "entity_id": "campaign-uuid",
    "metric": "roi",
    "days": 60
  }'
```

**Result:** Trend classification (upward/downward/stable) with slope and confidence

### 4. Performance Optimization Recommendations

**Scenario:** Generate intelligent recommendations for campaign optimization

```bash
curl -X POST https://api.cmis.marketing/api/orgs/{org_id}/analytics/recommendations/generate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "entity_type": "campaign",
    "entity_id": "campaign-uuid"
  }'
```

**Result:** List of actionable recommendations with priorities and confidence scores

---

## Testing

### Unit Tests

**Test File:** `tests/Unit/Services/ForecastingServiceTest.php`

```php
class ForecastingServiceTest extends TestCase
{
    public function test_moving_average_forecast_generates_correct_predictions()
    {
        $service = new ForecastingService();

        $data = [
            ['date' => '2025-11-01', 'value' => 100],
            ['date' => '2025-11-02', 'value' => 110],
            ['date' => '2025-11-03', 'value' => 105],
            ['date' => '2025-11-04', 'value' => 115],
            ['date' => '2025-11-05', 'value' => 120],
            ['date' => '2025-11-06', 'value' => 125],
            ['date' => '2025-11-07', 'value' => 130]
        ];

        $forecasts = $service->movingAverageForecast($data, 7);

        $this->assertCount(7, $forecasts);
        $this->assertArrayHasKey('predicted_value', $forecasts[0]);
        $this->assertArrayHasKey('confidence_lower', $forecasts[0]);
        $this->assertArrayHasKey('confidence_upper', $forecasts[0]);
    }

    public function test_anomaly_detection_identifies_outliers()
    {
        $service = new ForecastingService();

        // Generate data with one clear outlier
        $data = array_fill(0, 29, ['date' => '2025-11-01', 'value' => 100]);
        $data[] = ['date' => '2025-11-30', 'value' => 300]; // Outlier

        $anomalies = $service->detectAnomalies(
            'org-uuid',
            'campaign',
            'entity-uuid',
            'revenue',
            30
        );

        $this->assertCount(1, $anomalies);
        $this->assertEquals('spike', $anomalies[0]->anomaly_type);
    }

    public function test_trend_analysis_classifies_trends_correctly()
    {
        $service = new ForecastingService();

        // Upward trend data
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days")),
                'value' => 100 + ($i * 5) // Increasing by 5 each day
            ];
        }

        $trend = $service->analyzeTrends(
            'org-uuid',
            'campaign',
            'entity-uuid',
            'revenue',
            30
        );

        $this->assertEquals('upward', $trend->trend_type);
        $this->assertGreaterThan(0, $trend->slope);
    }
}
```

### Feature Tests

**Test File:** `tests/Feature/PredictiveAnalyticsTest.php`

```php
class PredictiveAnalyticsTest extends TestCase
{
    public function test_can_generate_forecast_via_api()
    {
        $user = User::factory()->create();
        $org = Org::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/orgs/{$org->org_id}/analytics/forecasts", [
                'entity_type' => 'campaign',
                'entity_id' => Str::uuid(),
                'metric' => 'revenue',
                'days' => 30,
                'forecast_type' => 'moving_average'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'forecasts' => [
                    '*' => [
                        'forecast_id',
                        'predicted_value',
                        'confidence_lower',
                        'confidence_upper'
                    ]
                ],
                'count'
            ]);
    }

    public function test_can_acknowledge_anomaly()
    {
        $user = User::factory()->create();
        $org = Org::factory()->create();
        $anomaly = Anomaly::factory()->create(['org_id' => $org->org_id]);

        $response = $this->actingAs($user)
            ->postJson("/api/orgs/{$org->org_id}/analytics/anomalies/{$anomaly->anomaly_id}/acknowledge", [
                'notes' => 'Investigating the spike'
            ]);

        $response->assertStatus(200);
        $this->assertEquals('acknowledged', $anomaly->fresh()->status);
    }

    public function test_can_accept_recommendation()
    {
        $user = User::factory()->create();
        $org = Org::factory()->create();
        $recommendation = Recommendation::factory()->create(['org_id' => $org->org_id]);

        $response = $this->actingAs($user)
            ->postJson("/api/orgs/{$org->org_id}/analytics/recommendations/{$recommendation->recommendation_id}/accept");

        $response->assertStatus(200);
        $this->assertEquals('accepted', $recommendation->fresh()->status);
    }
}
```

### Multi-Tenancy Tests

```php
public function test_forecasts_respect_row_level_security()
{
    $org1 = Org::factory()->create();
    $org2 = Org::factory()->create();

    Forecast::factory()->create(['org_id' => $org1->org_id]);
    Forecast::factory()->create(['org_id' => $org2->org_id]);

    // Set org context
    DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
        auth()->id(),
        $org1->org_id
    ]);

    $forecasts = Forecast::all();

    // Should only see org1's forecasts
    $this->assertCount(1, $forecasts);
    $this->assertEquals($org1->org_id, $forecasts->first()->org_id);
}
```

---

## Performance Considerations

### 1. Database Indexing

All predictive analytics tables include optimized indexes:
- Composite indexes on (org_id, entity_type, entity_id)
- Status/severity indexes for filtering
- Date indexes for time-series queries

### 2. Query Optimization

- Use pagination for large result sets
- Implement caching for frequently accessed forecasts
- Consider materialized views for complex trend calculations

### 3. Background Processing

For resource-intensive operations:
- Queue anomaly detection jobs
- Batch forecast generation
- Asynchronous recommendation updates

---

## Security

### 1. Multi-Tenancy

All tables use Row-Level Security (RLS):
```sql
ALTER TABLE cmis.forecasts ENABLE ROW LEVEL SECURITY;
CREATE POLICY org_isolation ON cmis.forecasts
USING (org_id = current_setting('app.current_org_id')::uuid);
```

### 2. Authorization

All API endpoints require:
- Sanctum authentication
- Org membership validation
- Transaction context initialization

### 3. Input Validation

Comprehensive validation rules:
- Entity type whitelist
- Metric name validation
- Date range constraints (1-90 days)
- Numeric bounds checking

---

## Future Enhancements

### Phase 17 Considerations

1. **Advanced ML Models**
   - ARIMA time-series forecasting
   - Prophet for seasonal trends
   - Neural network predictions

2. **Real-Time Anomaly Detection**
   - Streaming anomaly detection
   - Alerting integration
   - Automated response workflows

3. **Multi-Metric Correlation**
   - Cross-metric analysis
   - Causation detection
   - Recommendation refinement

4. **Model Performance Tracking**
   - Accuracy monitoring over time
   - Model drift detection
   - Automated retraining

---

## Appendix

### Forecast Types

| Type | Algorithm | Use Case | Accuracy |
|------|-----------|----------|----------|
| Moving Average | 7-day window | Stable metrics | ★★★☆☆ |
| Linear Regression | Least squares | Trending metrics | ★★★★☆ |
| Weighted Average | Recent data priority | Changing trends | ★★★★☆ |

### Anomaly Severity Levels

| Severity | Z-Score | Description |
|----------|---------|-------------|
| Low | 1.5 - 2.0 | Minor deviation |
| Medium | 2.0 - 2.5 | Noticeable deviation |
| High | 2.5 - 3.0 | Significant deviation |
| Critical | > 3.0 | Extreme outlier |

### Recommendation Categories

| Category | Priority | Confidence | Action Type |
|----------|----------|------------|-------------|
| Performance | High | 75% | Review settings |
| Budget | Medium | 80% | Increase/decrease budget |
| Optimization | Medium | 70% | Adjust targeting |
| Alert | Critical | 90% | Immediate action required |

---

**Document Version:** 1.0
**Last Updated:** 2025-11-21
**Status:** Production Ready ✅
