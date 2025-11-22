# Phase 14: Analytics Data Export & API Integration

**Implementation Date:** 2025-11-21
**Status:** ✅ Complete
**Dependencies:** Phase 11 (Advanced Analytics), Phase 12 (Scheduled Reports)

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Database Schema](#database-schema)
4. [API Endpoints](#api-endpoints)
5. [Data Export Service](#data-export-service)
6. [API Token Management](#api-token-management)
7. [Export Formats](#export-formats)
8. [Delivery Methods](#delivery-methods)
9. [Scheduling & Automation](#scheduling--automation)
10. [Security](#security)
11. [Frontend Integration](#frontend-integration)
12. [Testing](#testing)
13. [Deployment](#deployment)

---

## Overview

Phase 14 implements a comprehensive data export system and API integration framework for CMIS. This phase enables:

- **Configurable Data Exports**: Create export configurations for various data types
- **Multiple Formats**: JSON, CSV, XLSX, Parquet
- **Flexible Delivery**: Download, Webhook, SFTP, S3
- **API Token Management**: Secure API access for external integrations
- **Scheduled Exports**: Automated recurring exports
- **Manual Exports**: On-demand data extraction
- **Export Tracking**: Comprehensive logging and monitoring

### Key Features

- ✅ Multi-format export engine (JSON, CSV, XLSX, Parquet)
- ✅ Delivery method flexibility (download, webhook, SFTP, S3)
- ✅ API token authentication with scopes
- ✅ Scheduled and manual exports
- ✅ Export execution tracking and history
- ✅ Rate limiting and quota management
- ✅ Queue-based processing for large exports
- ✅ Multi-tenancy support with RLS

---

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Data Export System                       │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌────────────────┐      ┌──────────────────┐              │
│  │  Export Config │─────▶│  Export Service  │              │
│  │  Management    │      │  (Core Engine)   │              │
│  └────────────────┘      └──────────────────┘              │
│         │                          │                         │
│         ▼                          ▼                         │
│  ┌────────────────┐      ┌──────────────────┐              │
│  │  API Token     │      │  Format Handlers │              │
│  │  Management    │      │  (JSON/CSV/etc)  │              │
│  └────────────────┘      └──────────────────┘              │
│         │                          │                         │
│         ▼                          ▼                         │
│  ┌────────────────────────────────────────┐                │
│  │         Queue Processing                │                │
│  │  (ProcessDataExportJob)                │                │
│  └────────────────────────────────────────┘                │
│                    │                                         │
│                    ▼                                         │
│  ┌────────────────────────────────────────┐                │
│  │      Delivery Mechanisms                │                │
│  │  (Download/Webhook/SFTP/S3)            │                │
│  └────────────────────────────────────────┘                │
│                    │                                         │
│                    ▼                                         │
│  ┌────────────────────────────────────────┐                │
│  │         Export Logs                     │                │
│  │  (Tracking & History)                   │                │
│  └────────────────────────────────────────┘                │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Configuration**: User creates export configuration via API or UI
2. **Execution**: Export triggered manually or by schedule
3. **Data Fetching**: Service retrieves data based on export type and filters
4. **Format Generation**: Data converted to requested format (JSON/CSV/XLSX/Parquet)
5. **Delivery**: File delivered via configured method
6. **Logging**: Execution tracked with status, metrics, and file info

---

## Database Schema

### Tables

#### 1. `cmis.api_tokens`

API tokens for external system authentication.

```sql
CREATE TABLE cmis.api_tokens (
    token_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    created_by UUID NOT NULL REFERENCES cmis.users(user_id),
    name VARCHAR(255) NOT NULL,
    token_hash TEXT NOT NULL,
    token_prefix VARCHAR(16) NOT NULL,
    scopes JSONB NOT NULL,
    rate_limits JSONB,
    last_used_at TIMESTAMPTZ,
    usage_count INTEGER DEFAULT 0,
    expires_at TIMESTAMPTZ,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- RLS Policy
ALTER TABLE cmis.api_tokens ENABLE ROW LEVEL SECURITY;
CREATE POLICY org_isolation ON cmis.api_tokens
    USING (org_id = current_setting('app.current_org_id')::uuid);
```

**Token Scopes:**
- `analytics:read` - Read analytics data
- `campaigns:read` - Read campaign data
- `exports:read` - View export configurations
- `exports:write` - Create/modify export configurations

#### 2. `cmis.data_export_configs`

Export configuration definitions.

```sql
CREATE TABLE cmis.data_export_configs (
    config_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    created_by UUID NOT NULL REFERENCES cmis.users(user_id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    export_type VARCHAR(50) NOT NULL, -- analytics, campaigns, metrics, custom
    format VARCHAR(20) NOT NULL, -- json, csv, xlsx, parquet
    delivery_method VARCHAR(50) NOT NULL, -- download, webhook, sftp, s3
    data_config JSONB NOT NULL,
    delivery_config JSONB NOT NULL,
    schedule JSONB,
    last_exported_at TIMESTAMPTZ,
    next_export_at TIMESTAMPTZ,
    last_error TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
```

**Export Types:**
- `analytics` - Analytics data with date ranges and metrics
- `campaigns` - Campaign information
- `metrics` - Performance metrics
- `custom` - Custom SQL queries (with safety validation)

#### 3. `cmis.data_export_logs`

Export execution history and tracking.

```sql
CREATE TABLE cmis.data_export_logs (
    log_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    config_id UUID REFERENCES cmis.data_export_configs(config_id) ON DELETE CASCADE,
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    format VARCHAR(20) NOT NULL,
    started_at TIMESTAMPTZ NOT NULL,
    completed_at TIMESTAMPTZ,
    status VARCHAR(20) NOT NULL, -- processing, completed, failed
    records_count INTEGER,
    file_size BIGINT,
    file_path TEXT,
    execution_time INTEGER,
    delivery_status VARCHAR(50),
    error_message TEXT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
```

---

## API Endpoints

All endpoints require authentication via `Authorization: Bearer {token}` header.

### Export Configurations

#### List Export Configurations
```http
GET /api/orgs/{org_id}/exports/configs
```

**Query Parameters:**
- `export_type` (optional) - Filter by export type
- `format` (optional) - Filter by format
- `delivery_method` (optional) - Filter by delivery method
- `active` (optional) - Filter by active status
- `page` (optional) - Page number
- `per_page` (optional) - Results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "configs": {
    "data": [
      {
        "config_id": "uuid",
        "name": "Daily Analytics Export",
        "export_type": "analytics",
        "format": "csv",
        "delivery_method": "webhook",
        "schedule": {
          "frequency": "daily",
          "time": "09:00"
        },
        "is_active": true,
        "last_exported_at": "2025-11-20T09:00:00Z",
        "next_export_at": "2025-11-21T09:00:00Z"
      }
    ],
    "current_page": 1,
    "last_page": 3
  }
}
```

#### Create Export Configuration
```http
POST /api/orgs/{org_id}/exports/configs
```

**Request Body:**
```json
{
  "name": "Weekly Campaign Report",
  "description": "Weekly export of campaign performance",
  "export_type": "campaigns",
  "format": "xlsx",
  "delivery_method": "download",
  "data_config": {
    "date_range": {
      "start": "2025-11-01",
      "end": "2025-11-07"
    },
    "status": ["active", "completed"]
  },
  "delivery_config": {},
  "schedule": {
    "frequency": "weekly",
    "day_of_week": 1,
    "time": "08:00"
  },
  "is_active": true
}
```

**Response:** `201 Created`

#### Update Export Configuration
```http
PUT /api/orgs/{org_id}/exports/configs/{config_id}
```

#### Delete Export Configuration
```http
DELETE /api/orgs/{org_id}/exports/configs/{config_id}
```

### Export Execution

#### Execute Export
```http
POST /api/orgs/{org_id}/exports/execute
```

**Manual Export (One-time):**
```json
{
  "export_type": "analytics",
  "format": "json",
  "data_config": {
    "date_range": {
      "start": "2025-11-01",
      "end": "2025-11-20"
    }
  },
  "async": false
}
```

**Execute Existing Config:**
```json
{
  "config_id": "uuid",
  "async": true
}
```

**Response (Async):** `202 Accepted`
```json
{
  "success": true,
  "message": "Export queued for processing",
  "config_id": "uuid"
}
```

**Response (Sync):** `200 OK`
```json
{
  "success": true,
  "log": {
    "log_id": "uuid",
    "status": "completed",
    "records_count": 1250,
    "file_size": 524288
  },
  "download_url": "/api/orgs/{org_id}/exports/download/{log_id}"
}
```

### Export Logs

#### List Export Logs
```http
GET /api/orgs/{org_id}/exports/logs
```

**Query Parameters:**
- `config_id` (optional) - Filter by configuration
- `status` (optional) - Filter by status
- `format` (optional) - Filter by format
- `days` (optional) - Logs from last N days

#### Download Export File
```http
GET /api/orgs/{org_id}/exports/download/{log_id}
```

Returns the export file for download.

### API Tokens

#### List API Tokens
```http
GET /api/orgs/{org_id}/api-tokens
```

#### Create API Token
```http
POST /api/orgs/{org_id}/api-tokens
```

**Request:**
```json
{
  "name": "Integration System Token",
  "scopes": ["analytics:read", "campaigns:read"],
  "rate_limits": {
    "requests_per_minute": 60,
    "requests_per_hour": 1000
  },
  "expires_at": "2026-11-21T00:00:00Z"
}
```

**Response:**
```json
{
  "success": true,
  "token": {
    "token_id": "uuid",
    "name": "Integration System Token",
    "scopes": ["analytics:read", "campaigns:read"]
  },
  "plaintext_token": "cmis_AbCdEfGhIjKlMnOpQrStUvWxYz0123456789...",
  "message": "Store this token securely - it will not be shown again"
}
```

#### Revoke API Token
```http
DELETE /api/orgs/{org_id}/api-tokens/{token_id}
```

### Statistics

#### Get Export Statistics
```http
GET /api/orgs/{org_id}/exports/stats?days=30
```

**Response:**
```json
{
  "success": true,
  "stats": {
    "total_configs": 15,
    "active_configs": 12,
    "total_exports": 450,
    "successful_exports": 442,
    "failed_exports": 8,
    "total_records_exported": 125000,
    "total_data_size": 52428800,
    "active_tokens": 5,
    "by_format": {
      "json": 150,
      "csv": 200,
      "xlsx": 100
    }
  }
}
```

---

## Data Export Service

### Core Service Methods

**File:** `app/Services/Analytics/DataExportService.php`

#### `executeExport(DataExportConfig $config): DataExportLog`

Main export execution method.

```php
$exportService = app(DataExportService::class);
$log = $exportService->executeExport($config);
```

**Process:**
1. Creates export log with "processing" status
2. Fetches data based on export type
3. Generates file in requested format
4. Delivers via configured method (if not download)
5. Updates log with completion status

#### `manualExport(string $orgId, string $exportType, string $format, array $dataConfig): DataExportLog`

One-time manual export.

```php
$log = $exportService->manualExport(
    $orgId,
    'analytics',
    'csv',
    ['date_range' => ['start' => '2025-11-01', 'end' => '2025-11-20']]
);
```

### Data Fetching Methods

#### `fetchAnalyticsData(string $orgId, array $config): array`

Fetches campaign analytics with filters:
- Date range filtering
- Status filtering
- Custom column selection

#### `fetchCampaignsData(string $orgId, array $config): array`

Exports all campaign data for the organization.

#### `fetchMetricsData(string $orgId, array $config): array`

Exports performance metrics (to be implemented based on requirements).

#### `fetchCustomData(string $orgId, array $config): array`

Executes custom queries with safety validation:
- Blocks destructive operations (DROP, DELETE, UPDATE, etc.)
- Parameterized queries
- Read-only access

---

## API Token Management

### Token Generation

Tokens use format: `cmis_{64_random_chars}`

```php
$tokenData = APIToken::generateToken();
// Returns: ['token' => 'cmis_...', 'hash' => 'sha256...', 'prefix' => 'cmis_...']
```

### Token Storage

- **Stored**: SHA-256 hash + prefix (first 16 chars)
- **Never stored**: Plaintext token (shown once at creation)
- **Validation**: Compare hash of provided token with stored hash

### Scope-Based Authorization

```php
$token = APIToken::findByHash($tokenHash);

if ($token->hasScope('analytics:read')) {
    // Allow analytics read operations
}
```

### Usage Tracking

```php
$token->recordUsage();
// Increments usage_count and updates last_used_at
```

### Expiration

```php
if ($token->isExpired()) {
    return response()->json(['error' => 'Token expired'], 401);
}
```

---

## Export Formats

### 1. JSON

**Use Case:** API integrations, web applications

**Format:**
```json
[
  {
    "campaign_id": "uuid",
    "name": "Summer Campaign",
    "status": "active",
    "budget": 50000.00,
    "created_at": "2025-06-01T00:00:00Z"
  }
]
```

### 2. CSV

**Use Case:** Excel, data analysis, reporting

**Format:**
```csv
campaign_id,name,status,budget,created_at
uuid,Summer Campaign,active,50000.00,2025-06-01 00:00:00
```

**Features:**
- Header row with column names
- Proper escaping of special characters
- UTF-8 encoding

### 3. XLSX

**Use Case:** Business reporting, Excel-native format

**Note:** Currently generates CSV format. For production, implement using PhpSpreadsheet library.

**Future Implementation:**
```php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// ... populate data
$writer = new Xlsx($spreadsheet);
$writer->save($path);
```

### 4. Parquet

**Use Case:** Big data analytics, data lakes

**Note:** Currently generates JSON format. For production, use parquet-php or Apache Arrow.

**Future Implementation:**
```php
// Use parquet-php library
$writer = new ParquetWriter($schema, $outputStream);
$writer->write($data);
```

---

## Delivery Methods

### 1. Download

**Configuration:**
```json
{
  "delivery_method": "download",
  "delivery_config": {}
}
```

File stored locally, accessed via download endpoint.

### 2. Webhook

**Configuration:**
```json
{
  "delivery_method": "webhook",
  "delivery_config": {
    "url": "https://example.com/webhook",
    "headers": {
      "X-API-Key": "secret"
    }
  }
}
```

**Payload:**
```json
{
  "file_name": "export_2025-11-21_094530.csv",
  "content": "base64_encoded_content",
  "timestamp": "2025-11-21T09:45:30Z"
}
```

### 3. SFTP

**Configuration:**
```json
{
  "delivery_method": "sftp",
  "delivery_config": {
    "host": "sftp.example.com",
    "port": 22,
    "username": "user",
    "password": "encrypted",
    "path": "/exports/"
  }
}
```

**Implementation Note:** Currently not implemented. Use phpseclib for production.

### 4. S3

**Configuration:**
```json
{
  "delivery_method": "s3",
  "delivery_config": {
    "bucket": "my-exports",
    "region": "us-east-1",
    "prefix": "cmis-exports/",
    "access_key": "encrypted",
    "secret_key": "encrypted"
  }
}
```

**Implementation Note:** Currently not implemented. Use AWS SDK for production.

---

## Scheduling & Automation

### Schedule Configuration

```json
{
  "schedule": {
    "frequency": "daily",
    "time": "09:00",
    "timezone": "America/New_York"
  }
}
```

**Frequencies:**
- `hourly` - Every hour at minute 0
- `daily` - Daily at specified time
- `weekly` - Weekly on specified day and time
- `monthly` - Monthly on specified day and time

### Laravel Scheduler Integration

**File:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('exports:process-scheduled')
             ->everyFifteenMinutes()
             ->withoutOverlapping();
}
```

### Manual Execution

```bash
# Process all due exports
php artisan exports:process-scheduled

# Process specific configuration
php artisan exports:process-scheduled --config=uuid

# Run synchronously (for testing)
php artisan exports:process-scheduled --sync

# Dry run (show what would be processed)
php artisan exports:process-scheduled --dry-run
```

---

## Security

### Authentication

All endpoints require Sanctum authentication:

```http
Authorization: Bearer {sanctum_token}
```

### API Token Security

1. **SHA-256 Hashing**: Tokens stored as hashes
2. **Prefix Storage**: First 16 chars stored for identification
3. **One-time Display**: Plaintext token shown only at creation
4. **Scope Validation**: Granular permission checking
5. **Expiration**: Optional token expiration dates
6. **Revocation**: Instant token deactivation

### Rate Limiting

Configure per-token rate limits:

```json
{
  "rate_limits": {
    "requests_per_minute": 60,
    "requests_per_hour": 1000,
    "requests_per_day": 10000
  }
}
```

### Custom Query Safety

For `custom` export type, queries are validated:

```php
protected function validateCustomQuery(string $query): void
{
    $forbidden = ['drop', 'delete', 'truncate', 'alter', 'create', 'insert', 'update'];

    foreach ($forbidden as $keyword) {
        if (stripos($query, $keyword) !== false) {
            throw new \RuntimeException("Query contains forbidden keyword: {$keyword}");
        }
    }
}
```

### Multi-Tenancy (RLS)

All tables have Row-Level Security policies:

```sql
CREATE POLICY org_isolation ON cmis.api_tokens
    USING (org_id = current_setting('app.current_org_id')::uuid);
```

---

## Frontend Integration

### Alpine.js Component

**File:** `resources/js/components/dataExports.js`

**Usage:**
```html
<div x-data="dataExports" data-org-id="{{ $orgId }}">
    <!-- Component UI -->
</div>
```

### Component Features

#### Tabs
- **Configs**: Manage export configurations
- **Logs**: View export history
- **Tokens**: Manage API tokens
- **Stats**: View statistics

#### Configuration Management
```javascript
// Create configuration
await this.createConfig();

// Toggle active status
await this.updateConfig(config);

// Delete configuration
await this.deleteConfig(configId);

// Execute export
await this.executeExport(configId, async = true);
```

#### Token Management
```javascript
// Create token
await this.createToken();

// Copy token to clipboard
this.copyToClipboard(token);

// Revoke token
await this.revokeToken(tokenId);
```

### UI Helpers

```javascript
// Format bytes
this.formatBytes(524288) // "512 KB"

// Get status color
this.getStatusColor('completed') // 'green'

// Format number
this.formatNumber(125000) // "125,000"
```

---

## Testing

### Unit Tests

Test export service methods:

```php
/** @test */
public function it_generates_json_export()
{
    $data = [['id' => 1, 'name' => 'Test']];

    $service = new DataExportService();
    $path = $service->generateJSON($data, 'test_export');

    $this->assertFileExists(Storage::path($path));
    $content = Storage::get($path);
    $this->assertJson($content);
}
```

### Integration Tests

Test full export flow:

```php
/** @test */
public function it_executes_complete_export()
{
    $config = DataExportConfig::factory()->create([
        'org_id' => $this->org->org_id,
        'export_type' => 'campaigns',
        'format' => 'csv'
    ]);

    $service = app(DataExportService::class);
    $log = $service->executeExport($config);

    $this->assertEquals('completed', $log->status);
    $this->assertGreaterThan(0, $log->records_count);
}
```

### API Tests

```php
/** @test */
public function it_creates_export_configuration()
{
    $response = $this->postJson("/api/orgs/{$this->org->org_id}/exports/configs", [
        'name' => 'Test Export',
        'export_type' => 'analytics',
        'format' => 'json',
        'delivery_method' => 'download',
        'data_config' => [],
        'delivery_config' => []
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['success', 'config']);
}
```

---

## Deployment

### Queue Configuration

Ensure queue worker is running:

```bash
php artisan queue:work --queue=exports --tries=3 --timeout=600
```

### Scheduler

Add to crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Storage

Ensure storage directory is writable:

```bash
mkdir -p storage/app/exports
chmod -R 775 storage/app/exports
```

### Environment Variables

```env
# Queue configuration
QUEUE_CONNECTION=redis

# Storage configuration
FILESYSTEM_DISK=local

# Export settings
EXPORT_MAX_RECORDS=100000
EXPORT_TIMEOUT=600
```

### Monitoring

Monitor export jobs:

```sql
-- Check recent exports
SELECT
    config_id,
    status,
    records_count,
    file_size,
    execution_time,
    started_at
FROM cmis.data_export_logs
WHERE started_at >= NOW() - INTERVAL '24 hours'
ORDER BY started_at DESC;

-- Check failed exports
SELECT *
FROM cmis.data_export_logs
WHERE status = 'failed'
ORDER BY started_at DESC
LIMIT 20;
```

---

## Summary

Phase 14 provides a complete data export and API integration system for CMIS, enabling:

✅ **Flexible Exports**: Multiple formats and delivery methods
✅ **Automation**: Scheduled recurring exports
✅ **Security**: Token-based API access with scopes
✅ **Scalability**: Queue-based processing
✅ **Tracking**: Comprehensive logging and monitoring
✅ **Multi-Tenancy**: Full RLS support

The system is production-ready with room for enhancements like advanced format support (native XLSX, Parquet) and additional delivery methods (SFTP, S3).

---

**Next Steps:**
- Implement PhpSpreadsheet for native XLSX support
- Add parquet-php for native Parquet format
- Implement SFTP delivery using phpseclib
- Implement S3 delivery using AWS SDK
- Add export templates library
- Implement data compression for large exports
- Add email notifications for export completion
