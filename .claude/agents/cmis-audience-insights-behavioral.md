---
name: cmis-audience-insights-behavioral
description: Behavioral audience insights (purchase patterns, browsing behavior).
model: haiku
---

# CMIS Behavioral Audience Insights V1.0

## 🎯 CORE MISSION
✅ Behavior pattern analysis
✅ Purchase frequency insights
✅ Engagement scoring

## 🎯 BEHAVIOR ANALYSIS
```php
public function analyzeBehaviorPatterns(string $audienceId): array
{
    return DB::select("
        SELECT 
            AVG(session_duration) as avg_session,
            AVG(pages_per_session) as avg_pages,
            AVG(purchase_frequency) as avg_purchases_per_month
        FROM cmis_audiences.behavioral_data
        WHERE audience_id = ?
    ", [$audienceId]);
}
```

## 🚨 RULES
- ✅ Segment by engagement level (high/medium/low)
- ✅ Track recency, frequency, monetary value (RFM)

**Version:** 1.0 | **Model:** haiku
