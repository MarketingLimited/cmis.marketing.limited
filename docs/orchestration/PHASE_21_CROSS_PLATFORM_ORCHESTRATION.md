# Phase 21: Cross-Platform Campaign Orchestration

**Implementation Date:** 2025-11-21
**Status:** ✅ Complete
**Dependencies:** Phases 17 (Automation), 18 (Platform Integration), 20 (AI Optimization)

---

## Overview

Phase 21 implements a comprehensive cross-platform campaign orchestration system that enables unified campaign management across multiple advertising platforms (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat). This phase ties together platform integration, automation, and optimization into a cohesive campaign lifecycle management system.

### Key Features

✅ **Campaign Templates** - Reusable templates for multi-platform campaigns
✅ **Unified Deployment** - Deploy campaigns across multiple platforms simultaneously
✅ **Cross-Platform Sync** - Bidirectional synchronization of campaign data
✅ **Workflow Engine** - Automated multi-step campaign workflows
✅ **Budget Management** - Unified budget allocation across platforms
✅ **Performance Aggregation** - Combined performance metrics from all platforms
✅ **Orchestration Rules** - Coordination rules for multi-platform optimization
✅ **Complete Audit Trail** - Full sync and workflow logging

---

## Database Schema (6 Tables + 1 View)

### 1. campaign_templates
Reusable multi-platform campaign templates.

**Key Fields:**
- `category`: awareness, consideration, conversion, retention
- `objective`: brand_awareness, traffic, conversions, lead_gen, etc.
- `platforms`: Array of target platforms
- `base_config`: Base campaign configuration
- `platform_specific_config`: Platform-specific overrides
- `budget_template`: Default budget distribution
- `is_global`: Available to all orgs
- `usage_count`: Template usage tracking

### 2. campaign_orchestrations
Master orchestration records coordinating multi-platform campaigns.

**Key Fields:**
- `status`: draft, scheduled, active, paused, completed, failed
- `platforms`: Array of platforms this spans
- `total_budget`: Combined budget across all platforms
- `budget_allocation`: Budget split per platform
- `sync_strategy`: manual, auto, scheduled
- `platform_count` / `active_platform_count`: Platform tracking
- Scheduling: `scheduled_start_at`, `scheduled_end_at`

### 3. orchestration_platforms
Platform-specific campaign mappings and performance.

**Key Fields:**
- `platform`: meta, google, tiktok, linkedin, twitter, snapchat
- `platform_campaign_id`: ID on the platform
- `status`: pending, creating, active, paused, failed
- `allocated_budget`: Budget for this platform
- Performance metrics: `spend`, `impressions`, `clicks`, `conversions`, `revenue`
- `last_synced_at`: Sync timestamp
- `sync_metadata`: Platform-specific sync data

### 4. orchestration_workflows
Multi-step workflow execution tracking.

**Key Fields:**
- `workflow_type`: creation, activation, optimization, sync, deactivation
- `status`: pending, running, completed, failed
- `steps`: Array of workflow steps
- `current_step` / `total_steps`: Progress tracking
- `execution_log`: Step-by-step execution details
- `rollback_data`: Data for rollback if needed
- `duration_seconds`: Workflow execution time

### 5. orchestration_rules
Coordination rules for multi-platform optimization.

**Key Fields:**
- `rule_type`: budget_reallocation, pause_underperforming, scale_winners, creative_rotation
- `trigger`: schedule, performance, manual
- `trigger_conditions`: Conditions that activate the rule
- `actions`: Actions to execute when triggered
- `priority`: low, medium, high, critical
- `execution_count` / `success_count`: Rule performance tracking

### 6. orchestration_sync_logs
Complete audit trail of sync operations.

**Key Fields:**
- `sync_type`: full, incremental, settings, performance, creative
- `direction`: push, pull, bidirectional
- `status`: running, completed, failed, partial
- `changes_detected` / `changes_applied`: Sync changes
- `entities_synced` / `entities_failed`: Sync results
- `duration_ms`: Sync execution time
- `error_details`: Failure diagnostics

### 7. v_orchestration_performance (View)
Aggregated cross-platform performance view.

**Aggregated Metrics:**
- `total_spend`, `total_impressions`, `total_clicks`
- `total_conversions`, `total_revenue`
- `roas`, `ctr`, `cpa`
- `platform_count`, `active_platforms`

---

## Models

### CampaignTemplate
Reusable campaign templates with platform-specific configurations.

**Key Methods:**
```php
public function incrementUsage(): void
public function getPlatformConfig(string $platform): array
public function supportsPlatform(string $platform): bool
public function getBudgetDistribution(): array
public function getCategoryLabel(): string
public function getObjectiveLabel(): string
```

### CampaignOrchestration
Master orchestration coordinating multi-platform campaigns.

**Key Methods:**
```php
// Status Management
public function activate(): void
public function pause(): void
public function resume(): void
public function complete(): void
public function schedule(string $startDate, ?string $endDate = null): void

// Sync Management
public function shouldSync(): bool
public function enableAutoSync(int $frequencyMinutes = 15): void

// Platform Management
public function updatePlatformCounts(): void
public function isActiveOnPlatform(string $platform): bool

// Budget Management
public function getBudgetForPlatform(string $platform): ?float
public function updateBudgetAllocation(array $allocation): void
public function getTotalAllocatedBudget(): float

// Performance
public function getTotalSpend(): float
public function getROAS(): float
public function getBudgetUtilization(): float
```

### OrchestrationPlatform
Platform-specific campaign mappings.

**Key Methods:**
```php
public function markAsCreating(): void
public function markAsActive(string $platformCampaignId): void
public function updateMetrics(array $metrics): void
public function getCTR(): float
public function getConversionRate(): float
public function getCPA(): float
public function getROAS(): float
```

### OrchestrationWorkflow
Workflow execution tracking.

**Key Methods:**
```php
public function start(): void
public function advanceStep(): void
public function logStep(string $stepName, string $status, ?array $details = null): void
public function complete(): void
public function fail(string $errorMessage): void
public function getProgress(): float
```

---

## Services

### CampaignOrchestrationService
Main orchestration service coordinating all operations.

**Methods:**
```php
// Create orchestration from template
public function createFromTemplate(
    string $orgId,
    string $userId,
    string $templateId,
    array $overrides = []
): CampaignOrchestration

// Deploy to all platforms
public function deploy(CampaignOrchestration $orchestration): OrchestrationWorkflow

// Sync with all platforms
public function sync(CampaignOrchestration $orchestration, string $syncType = 'full'): array

// Pause/Resume
public function pause(CampaignOrchestration $orchestration): void
public function resume(CampaignOrchestration $orchestration): void

// Performance aggregation
public function getAggregatedPerformance(CampaignOrchestration $orchestration): array
```

### CrossPlatformSyncService
Handles bidirectional synchronization with platforms.

**Methods:**
```php
// Sync platform mapping
public function syncPlatformMapping(OrchestrationPlatform $mapping, string $syncType): OrchestrationSyncLog

// Platform operations
public function pausePlatformCampaign(OrchestrationPlatform $mapping): void
public function resumePlatformCampaign(OrchestrationPlatform $mapping): void
public function createPlatformCampaign(OrchestrationPlatform $mapping): string
public function updatePlatformCampaign(OrchestrationPlatform $mapping, array $updates): void
```

### WorkflowEngine
Executes multi-step workflows for orchestration operations.

**Methods:**
```php
// Deployment workflow (4 steps)
public function executeDeploymentWorkflow(CampaignOrchestration $orchestration): OrchestrationWorkflow

// Optimization workflow (4 steps)
public function executeOptimizationWorkflow(CampaignOrchestration $orchestration): OrchestrationWorkflow
```

---

## API Endpoints (13 Routes)

All endpoints require authentication: `Authorization: Bearer {token}`

### Templates

**GET /api/orchestration/templates**
Get all campaign templates (org-specific + global).

**POST /api/orchestration/templates**
Create new campaign template.

Request:
```json
{
  "name": "Multi-Platform Awareness Campaign",
  "category": "awareness",
  "objective": "brand_awareness",
  "platforms": ["meta", "google", "tiktok"],
  "base_config": {
    "targeting": {"age_min": 18, "age_max": 65}
  },
  "platform_specific_config": {
    "meta": {"placement": ["facebook", "instagram"]},
    "google": {"network": "display"}
  },
  "budget_template": {
    "distribution": "custom",
    "custom_distribution": {
      "meta": 50,
      "google": 30,
      "tiktok": 20
    }
  }
}
```

### Orchestrations

**POST /api/orchestration/from-template**
Create orchestration from template.

Request:
```json
{
  "template_id": "uuid",
  "name": "Q4 Brand Campaign",
  "total_budget": 10000,
  "platforms": ["meta", "google"]
}
```

**GET /api/orchestration**
List all orchestrations (with filters).

**GET /api/orchestration/{orchestrationId}**
Get orchestration details with performance.

**POST /api/orchestration/{orchestrationId}/deploy**
Deploy orchestration to all platforms.

Creates campaigns on each platform via deployment workflow.

**POST /api/orchestration/{orchestrationId}/sync**
Sync orchestration with platforms.

Request:
```json
{
  "sync_type": "full" // or "incremental", "settings", "performance"
}
```

**POST /api/orchestration/{orchestrationId}/pause**
Pause orchestration on all platforms.

**POST /api/orchestration/{orchestrationId}/resume**
Resume orchestration on all platforms.

**PUT /api/orchestration/{orchestrationId}/budget**
Update budget allocation.

Request:
```json
{
  "total_budget": 15000,
  "budget_allocation": {
    "meta": 7500,
    "google": 4500,
    "tiktok": 3000
  }
}
```

**GET /api/orchestration/{orchestrationId}/performance**
Get aggregated performance across all platforms.

Response:
```json
{
  "success": true,
  "performance": {
    "total_spend": 8543.21,
    "total_conversions": 456,
    "total_revenue": 34567.89,
    "roas": 4.05,
    "budget_utilization": 85.43,
    "platform_performance": [
      {
        "platform": "meta",
        "status": "active",
        "spend": 4271.60,
        "conversions": 234,
        "revenue": 17890.45,
        "roas": 4.19,
        "ctr": 3.45,
        "cpa": 18.25
      },
      {
        "platform": "google",
        "status": "active",
        "spend": 2562.34,
        "conversions": 145,
        "revenue": 10987.23,
        "roas": 4.29,
        "ctr": 2.87,
        "cpa": 17.67
      }
    ]
  }
}
```

---

## Workflow Types

### 1. Deployment Workflow (4 Steps)

**Steps:**
1. **Validate Configuration** - Check connections, budget allocation
2. **Create Platform Campaigns** - Create campaigns on each platform via API
3. **Sync Settings** - Push campaign settings to platforms
4. **Activate Campaigns** - Activate orchestration and update status

**Execution:**
```php
$workflow = $orchestrationService->deploy($orchestration);

// Track progress
$workflow->getProgress(); // 75.0 (3 of 4 steps complete)
$workflow->execution_log; // Step-by-step execution details
```

### 2. Optimization Workflow (4 Steps)

**Steps:**
1. **Fetch Performance** - Pull latest metrics from all platforms
2. **Analyze Performance** - Identify optimization opportunities
3. **Generate Recommendations** - Create optimization insights
4. **Apply Optimizations** - Execute approved optimizations

---

## Integration with Other Phases

### Phase 17 (Automation)
- Orchestration rules trigger automation rules
- Auto-pause underperforming platforms
- Auto-scale winning platforms
- Automated creative rotation

### Phase 18 (Platform Integration)
- Uses platform connections for OAuth
- Creates campaigns via platform APIs
- Syncs settings and performance
- Handles platform-specific configurations

### Phase 20 (AI Optimization)
- Budget allocation uses Bayesian optimization
- Performance predictions inform platform selection
- Attribution data guides budget distribution
- Creative insights applied across platforms

---

## Use Cases

### 1. Multi-Platform Product Launch

**Objective:** Launch new product across Meta, Google, and TikTok

**Setup:**
- Template: "Product Launch - Conversion"
- Budget: $50,000 (Meta: 40%, Google: 35%, TikTok: 25%)
- Duration: 30 days

**Workflow:**
1. Create orchestration from template
2. Deploy to all platforms simultaneously
3. Auto-sync performance every 15 minutes
4. Apply cross-platform optimization daily
5. Reallocate budget based on ROAS

### 2. Brand Awareness Campaign

**Objective:** Maximize reach across all platforms

**Setup:**
- Template: "Brand Awareness - Multi-Platform"
- Budget: $100,000 (equal distribution)
- Platforms: Meta, Google, TikTok, LinkedIn, Twitter

**Orchestration Rules:**
- Scale platforms with CTR > 3%
- Pause platforms with CPM > $15
- Rotate creative every 7 days

### 3. Seasonal Sale Campaign

**Objective:** Drive conversions during holiday season

**Setup:**
- Scheduled deployment: Black Friday 00:00
- Scheduled end: Cyber Monday 23:59
- Budget: $200,000
- Auto-pause when budget reached

---

## Best Practices

### Template Design
1. **Clear Objective** - Define specific campaign goal
2. **Platform Selection** - Choose platforms based on audience
3. **Budget Distribution** - Allocate based on historical performance
4. **Targeting Consistency** - Maintain consistent targeting across platforms

### Orchestration Management
1. **Test Before Deploy** - Validate configuration in draft mode
2. **Monitor Closely** - Watch first 24 hours after deployment
3. **Auto-Sync Enabled** - Keep platforms synchronized
4. **Budget Alerts** - Set alerts at 75%, 90%, 100% utilization

### Performance Optimization
1. **Cross-Platform Analysis** - Compare platform performance
2. **Budget Reallocation** - Shift budget to top performers
3. **Creative Testing** - Test different creative per platform
4. **Audience Refinement** - Adjust targeting based on results

---

## Security & Multi-Tenancy

### Row-Level Security
All tables have RLS policies ensuring organization isolation:

```sql
CREATE POLICY org_isolation ON cmis.campaign_orchestrations
USING (org_id = current_setting('app.current_org_id')::uuid);
```

### Platform Connection Security
- OAuth tokens encrypted at rest (Laravel Crypt)
- Token refresh handled automatically
- Scope validation before API calls
- Rate limiting per platform

---

## Files Created/Modified

**Created (19 files):**
- `database/migrations/2025_11_21_000010_create_campaign_orchestration_tables.php`
- `app/Models/Orchestration/CampaignTemplate.php`
- `app/Models/Orchestration/CampaignOrchestration.php`
- `app/Models/Orchestration/OrchestrationPlatform.php`
- `app/Models/Orchestration/OrchestrationWorkflow.php`
- `app/Models/Orchestration/OrchestrationRule.php`
- `app/Models/Orchestration/OrchestrationSyncLog.php`
- `app/Services/Orchestration/CampaignOrchestrationService.php`
- `app/Services/Orchestration/CrossPlatformSyncService.php`
- `app/Services/Orchestration/WorkflowEngine.php`
- `app/Http/Controllers/Api/OrchestrationController.php`
- `docs/orchestration/PHASE_21_CROSS_PLATFORM_ORCHESTRATION.md`

**Modified (1 file):**
- `routes/api.php` (added 13 orchestration endpoints)

---

## Summary

Phase 21 provides a complete cross-platform campaign orchestration system for CMIS, enabling:

✅ **Unified Campaign Management** - Single interface for multi-platform campaigns
✅ **Automated Deployment** - Deploy to multiple platforms simultaneously
✅ **Real-Time Sync** - Keep campaigns synchronized across platforms
✅ **Workflow Automation** - Multi-step workflows with progress tracking
✅ **Budget Optimization** - Intelligent budget allocation across platforms
✅ **Performance Aggregation** - Combined metrics from all platforms
✅ **Complete Audit Trail** - Full logging of all sync and workflow operations

The orchestration system empowers marketers to manage complex multi-platform campaigns efficiently while maintaining full control and visibility across all advertising channels.

---

**Next Potential Phases:**
- Phase 22: Advanced Reporting & Business Intelligence
- Phase 23: Campaign Performance Benchmarking
- Phase 24: AI-Powered Creative Generation
- Phase 25: Competitive Analysis & Market Intelligence

---

**Document Version:** 1.0 (Complete)
**Last Updated:** 2025-11-21
**Status:** ✅ Complete - Ready for Testing
