# CMIS Model Gap Analysis Report

**Generated:** 2025-11-12 22:28:18
**Schema File:** `database/schema.sql`
**Models Directory:** `app/Models/`

---

## 📊 Executive Summary

| Metric | Value |
|--------|-------|
| Total Tables | 97 |
| Models Exist | 62 |
| Models Missing | 35 |
| **Coverage** | **63.9%** |

---

## 🎯 Status Assessment
⚠️ **Status:** MODERATE - Significant gaps exist

### 🚨 Critical Findings

The following **CRITICAL** security/core models are missing:

- ⚠️ `audit_log` → `AuditLog`

---

## ❌ Missing Models (35)

The following database tables **lack corresponding Laravel models**:

| # | Table Name | Expected Model | Category | Suggested Path |
|---|------------|----------------|----------|----------------|
| 1 | `ai_models` | `AiModel` | AI | `app/Models/AI/AiModel.php` |
| 2 | `analytics_integrations` | `AnalyticsIntegration` | Analytics | `app/Models/Analytics/AnalyticsIntegration.php` |
| 3 | `anchors` | `Anchor` | Other | `app/Models/Other/Anchor.php` |
| 4 | `api_keys` | `ApiKey` | Other | `app/Models/Other/ApiKey.php` |
| 5 | `audit_log` | `AuditLog` | Security | `app/Models/Security/AuditLog.php` |
| 6 | `cache_metadata` | `CacheMetadata` | Other | `app/Models/Other/CacheMetadata.php` |
| 7 | `campaign_offerings` | `CampaignOffering` | Campaign | `app/Models/Campaign/CampaignOffering.php` |
| 8 | `contexts` | `Context` | Context | `app/Models/Context/Context.php` |
| 9 | `contexts_creative` | `ContextsCreative` | Context | `app/Models/Context/ContextsCreative.php` |
| 10 | `contexts_value` | `ContextsValue` | Context | `app/Models/Context/ContextsValue.php` |
| 11 | `data_feeds` | `DataFeed` | Other | `app/Models/Other/DataFeed.php` |
| 12 | `export_bundle_items` | `ExportBundleItem` | Other | `app/Models/Other/ExportBundleItem.php` |
| 13 | `export_bundles` | `ExportBundle` | Other | `app/Models/Other/ExportBundle.php` |
| 14 | `feed_items` | `FeedItem` | Other | `app/Models/Other/FeedItem.php` |
| 15 | `flow_steps` | `FlowStep` | Other | `app/Models/Other/FlowStep.php` |
| 16 | `flows` | `Flow` | Other | `app/Models/Other/Flow.php` |
| 17 | `logs_migration` | `LogsMigration` | Operations | `app/Models/Operations/LogsMigration.php` |
| 18 | `meta_documentation` | `MetaDocumentation` | AdPlatform | `app/Models/AdPlatform/MetaDocumentation.php` |
| 19 | `meta_field_dictionary` | `MetaFieldDictionary` | AdPlatform | `app/Models/AdPlatform/MetaFieldDictionary.php` |
| 20 | `meta_function_descriptions` | `MetaFunctionDescription` | AdPlatform | `app/Models/AdPlatform/MetaFunctionDescription.php` |
| 21 | `migrations` | `Migration` | Other | `app/Models/Other/Migration.php` |
| 22 | `modules` | `Module` | Other | `app/Models/Other/Module.php` |
| 23 | `naming_templates` | `NamingTemplate` | Other | `app/Models/Other/NamingTemplate.php` |
| 24 | `offerings_full_details` | `OfferingsFullDetail` | Other | `app/Models/Other/OfferingsFullDetail.php` |
| 25 | `offerings_old` | `OfferingsOld` | Other | `app/Models/Other/OfferingsOld.php` |
| 26 | `org_datasets` | `OrgDataset` | Core | `app/Models/Core/OrgDataset.php` |
| 27 | `output_contracts` | `OutputContract` | Other | `app/Models/Other/OutputContract.php` |
| 28 | `prompt_template_contracts` | `PromptTemplateContract` | Other | `app/Models/Other/PromptTemplateContract.php` |
| 29 | `prompt_template_presql` | `PromptTemplatePresql` | Other | `app/Models/Other/PromptTemplatePresql.php` |
| 30 | `prompt_template_required_fields` | `PromptTemplateRequiredField` | Other | `app/Models/Other/PromptTemplateRequiredField.php` |
| 31 | `prompt_templates` | `PromptTemplate` | Other | `app/Models/Other/PromptTemplate.php` |
| 32 | `reference_entities` | `ReferenceEntity` | Other | `app/Models/Other/ReferenceEntity.php` |
| 33 | `security_context_audit` | `SecurityContextAudit` | Security | `app/Models/Security/SecurityContextAudit.php` |
| 34 | `segments` | `Segment` | Other | `app/Models/Other/Segment.php` |
| 35 | `sql_snippets` | `SqlSnippet` | Other | `app/Models/Other/SqlSnippet.php` |

---

## ✅ Existing Models (62)

| # | Table Name | Model Class | File Path |
|---|------------|-------------|----------|
| 1 | `ad_accounts` | `AdAccount` | `app/Models/AdPlatform/AdAccount.php` |
| 2 | `ad_audiences` | `AdAudience` | `app/Models/AdPlatform/AdAudience.php` |
| 3 | `ad_campaigns` | `AdCampaign` | `app/Models/AdPlatform/AdCampaign.php` |
| 4 | `ad_entities` | `AdEntity` | `app/Models/AdPlatform/AdEntity.php` |
| 5 | `ad_metrics` | `AdMetric` | `app/Models/AdPlatform/AdMetric.php` |
| 6 | `ad_sets` | `AdSet` | `app/Models/AdPlatform/AdSet.php` |
| 7 | `ai_actions` | `AiAction` | `app/Models/AI/AiAction.php` |
| 8 | `ai_generated_campaigns` | `AiGeneratedCampaign` | `app/Models/AiGeneratedCampaign.php` |
| 9 | `audio_templates` | `AudioTemplate` | `app/Models/Creative/AudioTemplate.php` |
| 10 | `bundle_offerings` | `BundleOffering` | `app/Models/Offering/BundleOffering.php` |
| 11 | `campaign_context_links` | `CampaignContextLink` | `app/Models/Context/CampaignContextLink.php` |
| 12 | `campaign_performance_dashboard` | `CampaignPerformanceMetric` | `app/Models/CampaignPerformanceMetric.php` |
| 13 | `campaigns` | `Campaign` | `app/Models/Campaign.php` |
| 14 | `cognitive_tracker_template` | `CognitiveTrackerTemplate` | `app/Models/AI/CognitiveTrackerTemplate.php` |
| 15 | `cognitive_trends` | `CognitiveTrend` | `app/Models/AI/CognitiveTrend.php` |
| 16 | `compliance_audits` | `ComplianceAudit` | `app/Models/Compliance/ComplianceAudit.php` |
| 17 | `compliance_rule_channels` | `ComplianceRuleChannel` | `app/Models/Compliance/ComplianceRuleChannel.php` |
| 18 | `compliance_rules` | `ComplianceRule` | `app/Models/Compliance/ComplianceRule.php` |
| 19 | `content_items` | `ContentItem` | `app/Models/Creative/ContentItem.php` |
| 20 | `content_plans` | `ContentPlan` | `app/Models/Creative/ContentPlan.php` |
| 21 | `contexts_base` | `ContextBase` | `app/Models/Context/ContextBase.php` |
| 22 | `contexts_offering` | `OfferingContext` | `app/Models/Context/OfferingContext.php` |
| 23 | `copy_components` | `CopyComponent` | `app/Models/Creative/CopyComponent.php` |
| 24 | `creative_assets` | `CreativeAsset` | `app/Models/CreativeAsset.php` |
| 25 | `creative_briefs` | `CreativeBrief` | `app/Models/Creative/CreativeBrief.php` |
| 26 | `creative_contexts` | `CreativeContext` | `app/Models/Context/CreativeContext.php` |
| 27 | `creative_outputs` | `CreativeOutput` | `app/Models/Creative/CreativeOutput.php` |
| 28 | `dataset_files` | `DatasetFile` | `app/Models/AI/DatasetFile.php` |
| 29 | `dataset_packages` | `DatasetPackage` | `app/Models/AI/DatasetPackage.php` |
| 30 | `experiment_variants` | `ExperimentVariant` | `app/Models/Experiment/ExperimentVariant.php` |
| 31 | `experiments` | `Experiment` | `app/Models/Experiment/Experiment.php` |
| 32 | `field_aliases` | `FieldAlias` | `app/Models/Context/FieldAlias.php` |
| 33 | `field_definitions` | `FieldDefinition` | `app/Models/Context/FieldDefinition.php` |
| 34 | `field_values` | `FieldValue` | `app/Models/Context/FieldValue.php` |
| 35 | `integrations` | `Integration` | `app/Models/Core/Integration.php` |
| 36 | `ops_audit` | `OpsAudit` | `app/Models/Operations/OpsAudit.php` |
| 37 | `ops_etl_log` | `OpsEtlLog` | `app/Models/Operations/OpsEtlLog.php` |
| 38 | `org_markets` | `OrgMarket` | `app/Models/Market/OrgMarket.php` |
| 39 | `orgs` | `Org` | `app/Models/Core/Org.php` |
| 40 | `performance_metrics` | `PerformanceMetric` | `app/Models/PerformanceMetric.php` |
| 41 | `permissions` | `Permission` | `app/Models/Permission.php` |
| 42 | `permissions_cache` | `PermissionsCache` | `app/Models/Security/PermissionsCache.php` |
| 43 | `predictive_visual_engine` | `PredictiveVisualEngine` | `app/Models/AI/PredictiveVisualEngine.php` |
| 44 | `required_fields_cache` | `RequiredFieldsCache` | `app/Models/Cache/RequiredFieldsCache.php` |
| 45 | `role_permissions` | `RolePermission` | `app/Models/RolePermission.php` |
| 46 | `roles` | `Role` | `app/Models/Core/Role.php` |
| 47 | `scene_library` | `SceneLibrary` | `app/Models/AI/SceneLibrary.php` |
| 48 | `session_context` | `SessionContext` | `app/Models/Session/SessionContext.php` |
| 49 | `social_account_metrics` | `SocialAccountMetric` | `app/Models/SocialAccountMetric.php` |
| 50 | `social_accounts` | `SocialAccount` | `app/Models/SocialAccount.php` |
| 51 | `social_post_metrics` | `SocialPostMetric` | `app/Models/SocialPostMetric.php` |
| 52 | `social_posts` | `SocialPost` | `app/Models/SocialPost.php` |
| 53 | `sync_logs` | `SyncLog` | `app/Models/Operations/SyncLog.php` |
| 54 | `user_activities` | `UserActivity` | `app/Models/Operations/UserActivity.php` |
| 55 | `user_orgs` | `UserOrg` | `app/Models/Core/UserOrg.php` |
| 56 | `user_permissions` | `UserPermission` | `app/Models/UserPermission.php` |
| 57 | `user_sessions` | `UserSession` | `app/Models/Session/UserSession.php` |
| 58 | `users` | `User` | `app/Models/User.php` |
| 59 | `value_contexts` | `ValueContext` | `app/Models/Context/ValueContext.php` |
| 60 | `variation_policies` | `VariationPolicy` | `app/Models/Creative/VariationPolicy.php` |
| 61 | `video_scenes` | `VideoScene` | `app/Models/Creative/VideoScene.php` |
| 62 | `video_templates` | `VideoTemplate` | `app/Models/Creative/VideoTemplate.php` |

---

## 📂 Missing Models by Category

### AI (1)

- `ai_models` → `AiModel`

### AdPlatform (3)

- `meta_documentation` → `MetaDocumentation`
- `meta_field_dictionary` → `MetaFieldDictionary`
- `meta_function_descriptions` → `MetaFunctionDescription`

### Analytics (1)

- `analytics_integrations` → `AnalyticsIntegration`

### Campaign (1)

- `campaign_offerings` → `CampaignOffering`

### Context (3)

- `contexts` → `Context`
- `contexts_creative` → `ContextsCreative`
- `contexts_value` → `ContextsValue`

### Core (1)

- `org_datasets` → `OrgDataset`

### Operations (1)

- `logs_migration` → `LogsMigration`

### Other (22)

- `anchors` → `Anchor`
- `api_keys` → `ApiKey`
- `cache_metadata` → `CacheMetadata`
- `data_feeds` → `DataFeed`
- `export_bundle_items` → `ExportBundleItem`
- `export_bundles` → `ExportBundle`
- `feed_items` → `FeedItem`
- `flow_steps` → `FlowStep`
- `flows` → `Flow`
- `migrations` → `Migration`
- `modules` → `Module`
- `naming_templates` → `NamingTemplate`
- `offerings_full_details` → `OfferingsFullDetail`
- `offerings_old` → `OfferingsOld`
- `output_contracts` → `OutputContract`
- `prompt_template_contracts` → `PromptTemplateContract`
- `prompt_template_presql` → `PromptTemplatePresql`
- `prompt_template_required_fields` → `PromptTemplateRequiredField`
- `prompt_templates` → `PromptTemplate`
- `reference_entities` → `ReferenceEntity`
- `segments` → `Segment`
- `sql_snippets` → `SqlSnippet`

### Security (2)

- `audit_log` → `AuditLog`
- `security_context_audit` → `SecurityContextAudit`

---

## 🎯 Priority Recommendations

### 🔴 Critical Priority (Security & Core)

These models are **essential** for system security and basic functionality:

- ⚠️ **`audit_log`** → `AuditLog`

### 🟡 High Priority (Business Logic)

These models are required for core business features:

- 🔸 **`campaign_offerings`** → `CampaignOffering`
- 🔸 **`contexts`** → `Context`
- 🔸 **`contexts_creative`** → `ContextsCreative`
- 🔸 **`contexts_value`** → `ContextsValue`

---

## 🎬 Conclusion & Next Steps

⚠️ **The project has significant gaps** that need addressing.

**Recommended Actions:**
1. Focus on high-priority business logic models
2. Implement systematic testing
3. Document model-table relationships

---

*This report was generated automatically by `scripts/audit-model-gaps.php`*
