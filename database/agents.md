# دليل الوكلاء - Database Layer (database/)

## 1. Purpose (الغرض)

طبقة Database تحتوي على **schema definitions و data seeding** لـ CMIS:
- **84 Migrations**: إنشاء 12 schemas, 197 tables مع RLS policies
- **19 Seeders**: بيانات reference و demo data شاملة
- **30+ Factories**: لإنشاء test data
- **SQL Scripts**: raw SQL للـ functions, triggers, policies
- **HasRLSPolicies Trait**: standardized RLS policy management
- **Schema Management**: PostgreSQL multi-schema architecture

## 2. Owned Scope (النطاق المملوك)

### Database Organization

```
database/
├── migrations/                         # 84 migration files
│   ├── Concerns/
│   │   └── HasRLSPolicies.php         # RLS policy trait (NEW)
│   │
│   ├── 2025_11_14_000001_create_extensions_and_schemas.php
│   ├── 2025_11_14_000002_create_all_tables.php
│   ├── 2025_11_14_000009_create_policies.php
│   ├── 2025_11_22_000001_create_unified_metrics_table.php
│   └── ... (80 more migrations)
│
├── seeders/                            # 19 seeder files
│   ├── DatabaseSeeder.php             # Main seeder orchestrator
│   ├── OrgsSeeder.php
│   ├── UsersSeeder.php
│   ├── DemoDataSeeder.php
│   └── ...
│
├── factories/                          # 30+ factory files
│   ├── Core/
│   │   ├── OrgFactory.php
│   │   ├── UserFactory.php
│   │   └── RoleFactory.php
│   └── ...
│
├── sql/                                # Raw SQL scripts
│   ├── complete_tables.sql
│   ├── complete_policies.sql
│   ├── all_functions.sql              # 153KB
│   └── ...
│
└── schema.sql                          # Complete schema dump (475KB)
```

### Database Architecture

**12 PostgreSQL Schemas:**
1. `cmis` - Core tables
2. `cmis_meta` - Meta/Facebook data
3. `cmis_google` - Google Ads data
4. `cmis_tiktok` - TikTok data
5. `cmis_linkedin` - LinkedIn data
6. `cmis_twitter` - Twitter/X data
7. `cmis_snapchat` - Snapchat data
8. `cmis_ai` - AI embeddings
9. `cmis_analytics` - Analytics
10. `cmis_platform` - Platform connections
11. `cmis_social` - Social media
12. `cmis_audit` - Audit logs

**197 Tables Total**

## 3. Key Files

### HasRLSPolicies Trait (NEW)
Located: `database/migrations/Concerns/HasRLSPolicies.php`

**Provides 6 standardized RLS methods:**
1. `enableRLS($table, $orgColumn = 'org_id')` - Standard org isolation
2. `enableCustomRLS($table, $expression)` - Custom policy logic
3. `enableRLSWithSeparatePolicies($table, $select, $modify)` - Separate read/write
4. `enablePublicRLS($table)` - No org filtering (public tables)
5. `addAdminBypassPolicy($table)` - Admin users see all
6. `disableRLS($table, $policyNames = [])` - Rollback helper

### Main Seeder
`seeders/DatabaseSeeder.php` orchestrates:
```php
// Level 1: Reference Data
ChannelsSeeder, IndustriesSeeder, MarketsSeeder

// Level 2: Core Entities
OrgsSeeder, PermissionsSeeder, RolesSeeder, UsersSeeder

// Level 3: Demo Data
DemoDataSeeder (comprehensive interconnected data)
```

## 4. Migration Pattern (NEW)

```php
use Database\Migrations\Concerns\HasRLSPolicies;

class CreateYourTable extends Migration
{
    use HasRLSPolicies;

    public function up()
    {
        Schema::create('cmis.your_table', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            // ... columns
        });

        $this->enableRLS('cmis.your_table');
    }

    public function down()
    {
        $this->disableRLS('cmis.your_table');
        Schema::dropIfExists('cmis.your_table');
    }
}
```

## 5. Running Commands

```bash
# Fresh migration
php artisan migrate:fresh --seed

# Run specific seeder
php artisan db:seed --class=DemoDataSeeder

# Test RLS (in psql)
SET app.current_org_id = 'org-uuid-here';
SELECT * FROM cmis.campaigns;
```

## 6. Notes

- **84 Migrations** total
- **197 Tables** across 12 schemas
- **HasRLSPolicies Trait** saves ~500 lines of duplicate code
- **All tables** have RLS policies for multi-tenancy
