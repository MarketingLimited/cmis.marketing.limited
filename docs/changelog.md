### 📝 [2025-11-03 20:44:05 +0300] Commit Log
**Commit ID:** 2e4fbe3
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- app/Console/Commands/CognitiveStateNow.php
- app/Console/Commands/CognitiveVitalityLog.php
- app/Console/Commands/CognitiveVitalityLog.php.bak
- app/Console/Commands/CognitiveVitalityWatch.php
- app/Console/Commands/DbExecuteSql.php
- app/Console/Commands/GenerateGeminiEmbeddings.php
- app/Console/Commands/ProcessEmbeddings.php
- app/Console/Commands/SearchKnowledge.php
- app/Console/Commands/SyncInstagramData.php
- app/Console/Kernel.php
- app/Console/Kernel.php.bak
- app/Http/Controllers/API/CMISEmbeddingController.php
- app/Models/CMIS/KnowledgeItem.php
- app/Providers/CMISEmbeddingServiceProvider.php
- app/Services/CMIS/GeminiEmbeddingService.php
- app/Services/CMIS/KnowledgeEmbeddingProcessor.php
- app/Services/CMIS/SemanticSearchService.php
- app/Services/Gemini/EmbeddingService.php
- app/Services/Social/InstagramSyncService.backup.php
- app/Services/Social/InstagramSyncService.php
- backups/2025-10-30_22-28-16/backup_httpdocs.tar.gz
- backups/2025-10-30_22-34-59/backup_httpdocs.tar.gz
- backups/2025-10-30_22-37-47/backup_httpdocs.tar.gz
- backups/changelog_backup_2025-10-31.json
- backups/changelog_backup_2025-10-31.md
- composer.json
- composer.lock
- config/cmis-embeddings.php
- docs/changelog.json
- docs/changelog.md
- docs/instagram_api_Instructions.json
- routes/api.php
- scripts/cmis_vector_migration.sql
- scripts/cmis_vector_migration_fixed.sql
- scripts/completion_script.sql
- scripts/create_batch_update_embeddings.sql
- scripts/create_cleanup_old_embeddings.sql
- scripts/create_embedding_update_queue.sql
- scripts/create_embeddings_cache_table.sql
- scripts/create_generate_system_report.sql
- scripts/create_intent_direction_purpose_tables.sql
- scripts/create_semantic_search_logs.sql
- scripts/create_semantic_search_results_cache.sql
- scripts/create_v_embedding_queue_status.sql
- scripts/create_v_search_performance.sql
- scripts/fix_embedding_norm.sql
- scripts/fix_embedding_norm_final.sql
- scripts/fix_lang_insertion_safe.php
- scripts/generate_system_report.sql
- scripts/run_batch_update_embeddings.sql
- scripts/run_cleanup_old_embeddings.sql
- scripts/run_generate_system_report.sql
- scripts/show_embedding_queue_status.sql
- scripts/show_v_embedding_queue_status.sql
- scripts/show_v_search_performance.sql
- scripts/update_embedding_norm.sql
- scripts/verify_installation.sql
- system/gpt_runtime_audit.md
- system/gpt_runtime_bootstrap.sql
- system/gpt_runtime_dashboard.md
- system/gpt_runtime_errors.md
- system/gpt_runtime_examples.md
- system/gpt_runtime_flow.md
- system/gpt_runtime_map.md
- system/gpt_runtime_optimize.sql
- system/gpt_runtime_performance_tracker.sql
- system/gpt_runtime_readme.md
- system/gpt_runtime_repair.sql
- system/gpt_runtime_repair_silent.sql
- system/gpt_runtime_security.md
- system/install_artisan_cron.sh
- system/optimize_embeddings_tables.sql
**Message:** Auto-sync from server
---

### 📝 [2025-11-01 00:12:23 +0300] Commit Log
**Commit ID:** d5451ca
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- app/Services/Social/InstagramAccountSyncService.php
- app/Services/Social/InstagramSyncService.php
- backups/changelog_backup_2025-10-31.json
- backups/changelog_backup_2025-10-31.md
- database/schema.sql
- docs/changelog.json
- docs/changelog.md
**Message:** إصلاح مزامنة حسابات Instagram وحذف الحقول غير المدعومة
---

### 📝 [2025-10-31 23:38:27 +0300] Commit Log
**Commit ID:** b52d8ae
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- app/Console/Commands/SyncInstagramData.php
- app/Services/Social/InstagramSyncService.php
- backups/changelog_backup_2025-10-31.json
- backups/changelog_backup_2025-10-31.md
- docs/changelog.json
- docs/changelog.md
**Message:** ربط InstagramSyncService بعملية مزامنة فعلية وتخزين الأداء
---

### 📝 [2025-10-31 21:57:02 +0300] Commit Log
**Commit ID:** 210ee54
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- app/Services/Social/InstagramSyncService.php
- backups/changelog_backup_2025-10-31.json
- backups/changelog_backup_2025-10-31.md
- docs/changelog.json
- docs/changelog.md
**Message:** تعديل InstagramSyncService لإضافة org_id عند حفظ المنشورات
---

### 📝 [2025-10-31 21:06:51 +0300] Commit Log
**Commit ID:** d7fe521
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- app/Console/Kernel.php
- app/Services/Social/InstagramSyncService.php
- app/docs/git/git-ai-instructions.md
- backups/changelog_backup_2025-10-31.json
- backups/changelog_backup_2025-10-31.md
- docs/changelog.json
- docs/changelog.md
**Message:** رفع التعديلات المرفقة عبر CMIS Assistant
---

### 📝 [2025-10-31 16:40:23 +0300] Commit Log
**Commit ID:** 64a2c17
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- app/Console/Commands/InstagramApiCommand.php
- app/Console/Commands/InstagramApiCommand.php.bak
- app/Console/Commands/SyncInstagramData.php
- app/Console/Commands/SyncInstagramData.php.bak
- backups/changelog_backup_2025-10-31.json
- backups/changelog_backup_2025-10-31.md
- docs/changelog.json
- docs/changelog.md
- docs/index.php
**Message:** Auto-sync from server
---

### 📝 [2025-10-31 15:23:31 +0300] Commit Log
**Commit ID:** 2f93e9c
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- docs/instagram/02_artisan_commands.md
**Message:** docs: update Instagram artisan command docs to include --by=id and account_id usage
---

### 📝 [2025-10-31 15:13:16 +0300] Commit Log
**Commit ID:** 91d2455
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- app/Console/Commands/InstagramApiCommand.php
**Message:** fix: allow instagram:api command to fetch via account_id when using --by=id
---

### 📝 [2025-10-31 13:18:18 +0300] Commit Log
**Commit ID:** 0d3e47f
**Branch:** main
**Committed by:** MarketingLimited
**Modified Files:**
- app/Console/Commands/FacebookApiCommand.php
- app/Console/Commands/InstagramApiCommand.php
- app/Console/Commands/LinkedinApiCommand.php
- app/Console/Commands/SyncInstagramData.php
- app/Console/Commands/TiktokApiCommand.php
- app/Models/Integration.php
- app/Services/InstagramService.php
- backups/2025-10-30_22-28-16/backup_httpdocs.tar.gz
- backups/2025-10-30_22-34-59/backup_httpdocs.tar.gz
- backups/2025-10-30_22-37-47/backup_httpdocs.tar.gz
- database/schema.sql
- docs/changelog.md
- docs/database-setup.md
- docs/instagram/01_overview.md
- docs/instagram/02_artisan_commands.md
- docs/instagram/03_ai_integration.md
- docs/instagram/04_debugging_and_logs.md
- docs/instagram/05_examples.md
- docs/instagram/help_en.md
- docs/social/facebook/help_ar.md
- docs/social/facebook/help_en.md
- docs/social/instagram/help_en.md
- docs/social/linkedin/help_ar.md
- docs/social/linkedin/help_en.md
- docs/social/tiktok/help_ar.md
- docs/social/tiktok/help_en.md
- scripts/fix_lang_insertion_safe.php
- scripts/git-changelog-helper.php
**Message:** Auto-sync from server
---

# 🧾 CMIS Social Commands Changelog

## [2025-10-31] Multi-language Integration & Stability Fixes

### ✅ Overview
This update finalizes the **multi-language (English/Arabic)** support and structural cleanup for all CMIS social Artisan commands.

### 🛠 Fixed & Improved
- **FacebookApiCommand.php** rebuilt with proper `--lang` support and clean syntax.
- **LinkedinApiCommand.php** fully reconstructed following the new architecture.
- **TiktokApiCommand.php** standardized to match Facebook & LinkedIn structure.
- All commands now correctly read from localized help files:
  - `/docs/social/<platform>/help_en.md`
  - `/docs/social/<platform>/help_ar.md`
- Dynamic rendering for Sync Command sections across all languages.
- Full syntax validation and cleanup of duplicate braces and broken lines.
- Verified operational stability through multiple SSH-based executions.

### 🧩 New Features
- Added `--lang` option to switch between **English** and **Arabic** help documentation.
- Automatic language detection fallback to English when Arabic file is missing.
- Unified Markdown documentation for Facebook, LinkedIn, and TikTok.

### 🧼 Maintenance
- All temporary and repair scripts removed from `/httpdocs/scripts/`.
- Directories fully cleaned and tested for integrity.

### 🚀 Status
**System Verified & Stable** — All Artisan social commands functional and bilingual.

---
**Generated automatically via CMIS operational automation layer.**
