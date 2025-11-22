# دليل الوكلاء - Models Layer (app/Models/)

## 1. Purpose (الغرض)

طبقة النماذج (Models) تمثل البيانات وعلاقاتها في CMIS:
- **244 Models** موزعة عبر **51 business domain**
- **Multi-tenancy Support**: كل model يدعم RLS isolation via `BaseModel`
- **UUID Primary Keys**: جميع النماذج تستخدم UUIDs
- **Soft Deletes**: جميع النماذج تدعم الحذف الآمن
- **Eloquent ORM**: استخدام كامل لإمكانيات Laravel Eloquent

## 2. Owned Scope (النطاق المملوك)

### Domain Organization (51 Domains)

```
app/Models/
├── Core/                 # Organization, User, Permission, Role
├── Campaign/             # Campaign, CampaignBudget, CampaignStatus
├── Platform/             # AdAccount, AdCampaign, AdSet, Ad
├── Social/               # SocialPost, SocialAccount, SocialMetric
├── AI/                   # Embedding, SemanticSearch, AIQuota
├── Analytics/            # Metric, Report, Dashboard
├── Creative/             # ContentPlan, CreativeAsset, Template
├── Audience/             # Segment, TargetAudience, CustomAudience
├── Budget/               # BudgetAllocation, BudgetLimit
├── Content/              # Content, ContentItem, ContentVersion
├── Metric/               # UnifiedMetric (consolidated metrics)
├── Marketing/            # MarketingStrategy, MarketingGoal
├── Automation/           # Workflow, Trigger, Action
├── Integration/          # PlatformConnection, SyncLog
├── Billing/              # Invoice, Payment, Subscription
├── Lead/                 # Lead, LeadSource, LeadStatus
├── Contact/              # Contact, ContactList
├── Tag/                  # Tag, Taggable
├── Asset/                # MediaAsset, AssetLibrary
├── Template/             # EmailTemplate, AdTemplate
├── Schedule/             # ScheduledPost, PublishingSchedule
├── Experiment/           # ABTest, Variant, ExperimentResult
├── Optimization/         # OptimizationRule, OptimizationResult
├── Listening/            # SocialListening, Mention, Sentiment
├── Influencer/           # Influencer, InfluencerCampaign
├── Workflow/             # ApprovalWorkflow, WorkflowStep
├── Notification/         # Notification, NotificationPreference
├── Log/                  # ActivityLog, AuditLog, ErrorLog
├── Report/               # CustomReport, ReportSchedule
├── Setting/              # OrganizationSetting, UserPreference
├── Webhook/              # WebhookEndpoint, WebhookLog
├── Security/             # ApiKey, AccessToken, LoginAttempt
├── Session/              # UserSession, SessionLog
├── Compliance/           # ComplianceRule, ComplianceCheck
├── Knowledge/            # KnowledgeBase, Article
├── Market/               # MarketSegment, MarketTrend
├── Offering/             # ProductOffering, ServiceOffering
├── Subscription/         # Subscription, SubscriptionPlan
├── Team/                 # Team, TeamMember, TeamRole
├── Channel/              # MarketingChannel, ChannelMetric
├── Operations/           # OperationalMetric, OperationalLog
├── Strategic/            # Strategy, StrategicGoal
├── Publishing/           # PublishingQueue, PublishingStatus
├── Context/              # OrganizationContext, ContextMetadata
├── Orchestration/        # OrchestrationFlow, FlowStep
├── CustomField/          # CustomField, FieldValue
├── Comment/              # Comment, CommentThread
├── AdPlatform/           # Platform-specific models
├── User/                 # UserProfile, UserActivity
├── Permission/           # Permission, PermissionGroup
├── Role/                 # Role, RolePermission
├── Other/                # Miscellaneous models
├── CMIS/                 # Legacy CMIS models
├── Cache/                # CacheEntry, CacheMetadata
└── Concerns/             # Traits & Scopes
    ├── HasOrganization.php
    └── Scopes/
        └── OrgScope.php
```

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Base Classes
- `BaseModel.php`: Base model for all domain models
  - UUID primary keys
  - Soft deletes
  - RLS via `OrgScope`
  - PostgreSQL connection

### Core Traits
- `Concerns/HasOrganization.php`: Multi-tenancy relationship trait
  ```php
  // Provides:
  // - org() relationship
  // - scopeForOrganization($orgId)
  // - belongsToOrganization($orgId)
  // - getOrganizationId()
  ```

### Global Scopes
- `Scopes/OrgScope.php`: Automatic RLS filtering
  - Auto-applies `WHERE org_id = current_setting('app.current_org_id')`
  - Can be disabled with `withoutOrgFilter()`

### Critical Models
- `Core/Organization.php`: Root entity for multi-tenancy
- `Core/User.php`: User authentication & permissions
- `Campaign/Campaign.php`: Central campaign entity
- `AI/Embedding.php`: Vector embeddings for semantic search
- `Metric/UnifiedMetric.php`: Consolidated metrics table (replaces 10 legacy metric tables)
- `Social/SocialPost.php`: Unified social posts (replaces 5 platform-specific tables)

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### Internal Dependencies
```
BaseModel (abstract)
    ↓
Domain Models (extend BaseModel)
    ↓
Use HasOrganization trait (for org relationships)
    ↓
OrgScope automatically applied (for RLS)
```

### External Dependencies
- **Laravel Eloquent ORM**: Base functionality
- **PostgreSQL**: Database driver (pgsql connection)
- **Laravel HasUuids**: UUID generation
- **Laravel SoftDeletes**: Soft delete functionality

### Relationships Patterns

#### Polymorphic Relationships
```php
// UnifiedMetric (polymorphic to multiple entities)
public function metricable()
{
    return $this->morphTo();
}

// Usage:
$campaign->metrics()  // morphMany relationship
```

#### Multi-tenancy Relationships
```php
// All models with org_id
public function org()
{
    return $this->belongsTo(Organization::class, 'org_id');
}
```

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### Model Creation Rules

#### ✅ ALWAYS Do This:
```php
namespace App\Models\YourDomain;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;

class YourModel extends BaseModel
{
    use HasOrganization;  // If table has org_id

    // Schema-qualified table name
    protected $table = 'cmis.your_table';

    // UUID key (inherited from BaseModel)
    public $incrementing = false;
    protected $keyType = 'string';

    // Mass assignment protection
    protected $fillable = [
        'name',
        'org_id',
        // ... other fields
    ];

    // Cast attributes
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
```

#### ❌ NEVER Do This:
```php
// ❌ Don't extend Model directly
use Illuminate\Database\Eloquent\Model;
class YourModel extends Model { }

// ❌ Don't use unqualified table names
protected $table = 'campaigns';  // Missing schema prefix

// ❌ Don't use auto-increment IDs
public $incrementing = true;  // Should be false (UUIDs)

// ❌ Don't forget org_id in fillable
protected $fillable = ['name'];  // Missing 'org_id'

// ❌ Don't bypass RLS
YourModel::withoutGlobalScope(OrgScope::class)->get();  // Dangerous!
```

### Relationship Patterns

#### One-to-Many
```php
// Parent
public function children()
{
    return $this->hasMany(Child::class);
}

// Child
public function parent()
{
    return $this->belongsTo(Parent::class);
}
```

#### Many-to-Many
```php
public function tags()
{
    return $this->belongsToMany(Tag::class, 'cmis.taggables')
                ->withTimestamps();
}
```

#### Polymorphic
```php
// Morphable
public function comments()
{
    return $this->morphMany(Comment::class, 'commentable');
}

// Comment model
public function commentable()
{
    return $this->morphTo();
}
```

### Query Scopes

```php
// Local scopes (reusable query filters)
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function scopeRecent($query, $days = 7)
{
    return $query->where('created_at', '>=', now()->subDays($days));
}

// Usage
YourModel::active()->recent(30)->get();
```

### Accessors & Mutators

```php
// Accessor (get value)
protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => "{$this->first_name} {$this->last_name}",
    );
}

// Mutator (set value)
protected function email(): Attribute
{
    return Attribute::make(
        set: fn ($value) => strtolower($value),
    );
}
```

## 6. How to Run / Test (كيفية التشغيل والاختبار)

### Testing Models

```bash
# Run all model tests
vendor/bin/phpunit tests/Unit/Models/

# Test specific domain
vendor/bin/phpunit tests/Unit/Models/Campaign/

# Test with coverage
vendor/bin/phpunit --coverage-html build/coverage tests/Unit/Models/
```

### Tinker (REPL Testing)

```bash
php artisan tinker

# Test model creation
>>> $org = App\Models\Core\Organization::first();
>>> $campaign = App\Models\Campaign\Campaign::create([
...     'name' => 'Test Campaign',
...     'org_id' => $org->id,
... ]);

# Test relationships
>>> $campaign->org;
>>> $campaign->contentPlans;

# Test scopes
>>> App\Models\Campaign\Campaign::active()->get();
```

### Database Inspection

```bash
# Check model table
php artisan tinker
>>> App\Models\Campaign\Campaign::getModel()->getTable();
=> "cmis.campaigns"

# Check fillable fields
>>> App\Models\Campaign\Campaign::getModel()->getFillable();

# Check relationships
>>> $campaign = App\Models\Campaign\Campaign::first();
>>> $campaign->getRelations();
```

## 7. Common Tasks for Agents (المهام الشائعة للوكلاء)

### Create New Model

1. **Determine domain** (existing or new)
2. **Create model file**:
   ```bash
   app/Models/YourDomain/YourModel.php
   ```

3. **Implement model**:
   ```php
   namespace App\Models\YourDomain;

   use App\Models\BaseModel;
   use App\Models\Concerns\HasOrganization;

   class YourModel extends BaseModel
   {
       use HasOrganization;

       protected $table = 'cmis.your_table';
       protected $fillable = ['name', 'org_id', ...];
       protected $casts = [...];

       // Relationships
   }
   ```

4. **Create migration** (see `database/agents.md`)
5. **Create factory** (for testing):
   ```php
   database/factories/YourDomain/YourModelFactory.php
   ```

6. **Create tests**:
   ```php
   tests/Unit/Models/YourDomain/YourModelTest.php
   ```

### Add Relationship

```php
// In model file
public function relatedModel()
{
    return $this->hasMany(RelatedModel::class);
    // OR
    return $this->belongsTo(RelatedModel::class);
    // OR
    return $this->belongsToMany(RelatedModel::class, 'pivot_table');
}

// Add inverse relationship in related model
```

### Add Query Scope

```php
public function scopeYourScope($query, $param)
{
    return $query->where('field', $param);
}

// Usage:
YourModel::yourScope($value)->get();
```

### Add Accessor/Mutator

```php
protected function yourAttribute(): Attribute
{
    return Attribute::make(
        get: fn ($value) => // transform value,
        set: fn ($value) => // transform value,
    );
}
```

## 8. Notes / Gotchas (ملاحظات ومحاذير)

### ⚠️ Critical Warnings

1. **RLS Scope Always Active**
   - `OrgScope` is applied globally via `BaseModel`
   - Only bypass with extreme caution using `withoutOrgFilter()`
   - System-level operations may need `withoutOrgFilter()`

2. **UUID Generation**
   - UUIDs are auto-generated via `HasUuids` trait
   - Don't manually set `id` unless you have a specific reason
   - Always use `$keyType = 'string'` and `$incrementing = false`

3. **Soft Deletes**
   - All models use `SoftDeletes` trait
   - Use `forceDelete()` only when absolutely necessary
   - Restore with `restore()` method

4. **Schema-Qualified Names**
   - Always use `cmis.table_name`, not just `table_name`
   - PostgreSQL requires schema qualification for RLS

5. **Mass Assignment**
   - Always define `$fillable` or `$guarded`
   - Never use `$guarded = []` (allows mass assignment of all fields)
   - Include `org_id` in `$fillable` for multi-tenant models

### 🎯 Best Practices

1. **Keep Models Focused**
   - Models should represent data and relationships
   - Business logic belongs in Services, not Models
   - Complex queries belong in Repositories

2. **Use Eager Loading**
   ```php
   // ✅ Good (1 query)
   $campaigns = Campaign::with(['org', 'contentPlans'])->get();

   // ❌ Bad (N+1 queries)
   $campaigns = Campaign::all();
   foreach ($campaigns as $campaign) {
       $campaign->org;  // Extra query each iteration
   }
   ```

3. **Cast Attributes Properly**
   ```php
   protected $casts = [
       'metadata' => 'array',      // JSON → array
       'is_active' => 'boolean',   // 1/0 → true/false
       'created_at' => 'datetime', // string → Carbon instance
   ];
   ```

4. **Document Relationships**
   - Add PHPDoc for relationship methods
   - Specify return types for IDE support

### 📊 Statistics

- **Total Models**: 244
- **Total Domains**: 51
- **Models with org_id**: ~200 (using `HasOrganization` trait)
- **Polymorphic Models**: ~15 (UnifiedMetric, Comment, etc.)
- **System Models (no org_id)**: ~44 (logs, system configs, etc.)

### 🔗 Related Modules

- **Migrations**: `database/agents.md` - Schema definitions with RLS
- **Factories**: `database/factories/` - Model factories for testing
- **Repositories**: `app/Repositories/agents.md` - Data access layer
- **Tests**: `tests/agents.md` - Model unit tests
