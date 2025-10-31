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
