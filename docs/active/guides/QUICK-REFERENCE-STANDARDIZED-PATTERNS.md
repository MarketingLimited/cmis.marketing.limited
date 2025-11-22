# CMIS Standardized Patterns - Quick Reference Guide

**For:** CMIS Developers
**Last Updated:** 2025-11-22
**Version:** Post Duplication Elimination

---

## 📋 Overview

This guide provides quick reference for the standardized patterns established during the duplication elimination initiative. Use these patterns for all new code.

---

## 🎯 Models

### Always Extend BaseModel

```php
<?php

namespace App\Models\YourDomain;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class YourModel extends BaseModel  // ✅ Extend BaseModel, not Model
{
    use HasFactory, SoftDeletes;
    use HasOrganization;  // ✅ For models with org_id

    protected $table = 'cmis.your_table';  // ✅ Schema-qualified
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'org_id',
        // ... other fields
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ❌ DON'T add these (BaseModel handles them):
    // - boot() method for UUIDs
    // - $connection = 'pgsql'
    // - $incrementing = false
    // - $keyType = 'string'
    // - use HasUuids (already in BaseModel)
}
```

### What BaseModel Provides

- ✅ Automatic UUID generation
- ✅ PostgreSQL connection
- ✅ Global RLS scope (OrgScope)
- ✅ `forOrg()` scope method
- ✅ `withoutOrgFilter()` scope method
- ✅ Soft deletes support
- ✅ Standard configuration

### HasOrganization Trait

```php
// Provides:
public function org(): BelongsTo                    // Get organization
public function scopeForOrganization($query, $orgId) // Filter by org
public function belongsToOrganization($orgId): bool  // Check ownership
public function getOrganizationId(): ?string         // Get org_id
```

**When to Use:**
- ✅ Model has `org_id` column
- ✅ Model needs organization relationship
- ✅ Model participates in RLS

---

## 🎮 Controllers

### Always Use ApiResponse Trait

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;  // ✅ Add this
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YourController extends Controller
{
    use ApiResponse;  // ✅ Always add this for API controllers

    public function index(): JsonResponse
    {
        $data = YourModel::all();

        // ✅ DO THIS:
        return $this->success($data, 'Data retrieved successfully');

        // ❌ DON'T DO THIS:
        // return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([/* rules */]);
        $resource = YourModel::create($validated);

        // ✅ Created response (201)
        return $this->created($resource, 'Resource created successfully');
    }

    public function show($id): JsonResponse
    {
        $resource = YourModel::find($id);

        if (!$resource) {
            // ✅ Not found response (404)
            return $this->notFound('Resource not found');
        }

        return $this->success($resource);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $resource = YourModel::findOrFail($id);
        $resource->update($request->validated());

        return $this->success($resource, 'Resource updated successfully');
    }

    public function destroy($id): JsonResponse
    {
        YourModel::findOrFail($id)->delete();

        // ✅ Deleted response (200)
        return $this->deleted('Resource deleted successfully');
    }
}
```

### ApiResponse Methods Reference

```php
// Success Responses
$this->success($data, $message, $code = 200)
$this->created($data, $message)          // 201
$this->deleted($message)                 // 200
$this->noContent()                       // 204
$this->paginated($paginator, $message)   // 200 with meta

// Error Responses
$this->error($message, $code = 400, $errors = null)
$this->notFound($message)                // 404
$this->unauthorized($message)            // 401
$this->forbidden($message)               // 403
$this->validationError($errors, $message) // 422
$this->serverError($message)             // 500
```

### Example: Handling Validation

```php
public function store(Request $request): JsonResponse
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);

        $user = User::create($validated);

        return $this->created($user, 'User created successfully');

    } catch (\Illuminate\Validation\ValidationException $e) {
        return $this->validationError(
            $e->errors(),
            'Validation failed'
        );
    } catch (\Exception $e) {
        return $this->serverError('Failed to create user');
    }
}
```

---

## 🗄️ Migrations

### Always Use HasRLSPolicies Trait

```php
<?php

use Database\Migrations\Concerns\HasRLSPolicies;  // ✅ Add this
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYourTable extends Migration
{
    use HasRLSPolicies;  // ✅ Add this

    public function up()
    {
        // 1. Create table
        Schema::create('cmis.your_table', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id')->index();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.orgs')
                ->onDelete('cascade');
        });

        // 2. Enable RLS (single line!)
        $this->enableRLS('cmis.your_table');

        // ✅ That's it! No manual SQL needed.
    }

    public function down()
    {
        $this->disableRLS('cmis.your_table');
        Schema::dropIfExists('cmis.your_table');
    }
}
```

### HasRLSPolicies Methods

```php
// Standard RLS (uses org_id)
$this->enableRLS('cmis.table_name');

// Custom RLS (custom condition)
$this->enableCustomRLS('cmis.table_name', 'user_id = current_user_id()');

// Public tables (no RLS)
$this->enablePublicRLS('cmis.public_table');

// Disable RLS
$this->disableRLS('cmis.table_name');
```

---

## 📊 Database Design Patterns

### Polymorphic Relationships

Use for entity-agnostic associations:

```php
Schema::create('cmis.unified_metrics', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('org_id')->index();

    // Polymorphic fields
    $table->string('entity_type');  // 'campaign', 'ad', 'post', etc.
    $table->uuid('entity_id');
    $table->string('platform');     // 'meta', 'google', 'tiktok', etc.

    // Metrics
    $table->jsonb('metrics');       // Flexible metrics storage
    $table->date('date')->index();  // For time-series queries

    $table->timestamps();

    // Composite index for efficient queries
    $table->index(['entity_type', 'entity_id', 'date']);
});
```

### JSONB for Flexibility

```php
Schema::create('cmis.social_posts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('org_id')->index();
    $table->string('platform');  // 'instagram', 'facebook', 'tiktok'

    // Common fields
    $table->string('caption')->nullable();
    $table->string('media_url')->nullable();

    // Platform-specific data in JSONB
    $table->jsonb('platform_data')->nullable();  // Instagram reels, TikTok duets, etc.

    $table->timestamps();
});
```

---

## 🔧 Services Pattern

### Repository + Service Pattern

```php
// Repository (Data Access)
class CampaignRepository
{
    public function findById(string $id): ?Campaign
    {
        return Campaign::find($id);
    }

    public function create(array $data): Campaign
    {
        return Campaign::create($data);
    }
}

// Service (Business Logic)
class CampaignService
{
    public function __construct(
        protected CampaignRepository $repository
    ) {}

    public function createCampaign(array $data): Campaign
    {
        // Validation, business rules, etc.
        $validated = $this->validateCampaignData($data);

        // Create campaign
        $campaign = $this->repository->create($validated);

        // Additional logic (notifications, logging, etc.)
        event(new CampaignCreated($campaign));

        return $campaign;
    }
}

// Controller (Orchestration)
class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CampaignService $service
    ) {}

    public function store(Request $request): JsonResponse
    {
        $campaign = $this->service->createCampaign($request->all());

        return $this->created($campaign, 'Campaign created successfully');
    }
}
```

---

## 🚫 Anti-Patterns (DON'T DO THIS)

### ❌ Direct Model Extension

```php
// ❌ DON'T
class YourModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'pgsql';
    protected $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }
}

// ✅ DO
class YourModel extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;
}
```

### ❌ Manual Response Formatting

```php
// ❌ DON'T
return response()->json([
    'success' => true,
    'data' => $campaigns,
    'message' => 'Success'
], 200);

// ✅ DO
return $this->success($campaigns, 'Success');
```

### ❌ Duplicate org() Methods

```php
// ❌ DON'T
public function org()
{
    return $this->belongsTo(Org::class, 'org_id');
}

// ✅ DO
use HasOrganization;  // Provides org() method automatically
```

### ❌ Manual RLS Policies in Migrations

```php
// ❌ DON'T
DB::statement("ALTER TABLE cmis.your_table ENABLE ROW LEVEL SECURITY");
DB::statement("CREATE POLICY org_isolation ON cmis.your_table
    USING (org_id = current_setting('app.current_org_id')::uuid)");

// ✅ DO
use HasRLSPolicies;
$this->enableRLS('cmis.your_table');
```

---

## 📚 Common Scenarios

### Creating a New Feature

1. **Model:**
   ```php
   class Feature extends BaseModel
   {
       use HasFactory, SoftDeletes, HasOrganization;
   }
   ```

2. **Migration:**
   ```php
   use HasRLSPolicies;
   $this->enableRLS('cmis.features');
   ```

3. **Repository:**
   ```php
   class FeatureRepository { /* CRUD methods */ }
   ```

4. **Service:**
   ```php
   class FeatureService { /* Business logic */ }
   ```

5. **Controller:**
   ```php
   class FeatureController extends Controller
   {
       use ApiResponse;
       // Use trait methods for responses
   }
   ```

### Adding Multi-Tenancy to Existing Model

```php
// 1. Add to model
use HasOrganization;

// 2. Ensure migration has RLS
use HasRLSPolicies;
$this->enableRLS('cmis.your_table');

// 3. Test isolation
$model = YourModel::forOrganization($orgId)->get();
```

---

## 🎓 Best Practices Checklist

### For New Models

- [ ] Extends `BaseModel`
- [ ] Uses `HasOrganization` if has org_id
- [ ] Table name is schema-qualified (`cmis.table_name`)
- [ ] No duplicate boot() method
- [ ] No manual UUID generation
- [ ] Fillable and casts are defined

### For New Controllers

- [ ] Uses `ApiResponse` trait
- [ ] All JSON responses use trait methods
- [ ] Consistent error handling
- [ ] Type hints on all methods
- [ ] Delegates business logic to services

### For New Migrations

- [ ] Uses `HasRLSPolicies` trait
- [ ] RLS enabled with `$this->enableRLS()`
- [ ] Foreign keys defined
- [ ] Indexes on frequently queried columns
- [ ] Schema-qualified table names

### For Services

- [ ] Repository pattern for data access
- [ ] Business logic in service layer
- [ ] Controller stays thin (orchestration only)
- [ ] Events for significant actions
- [ ] Proper exception handling

---

## 📞 Need Help?

- **Documentation:** `docs/active/guides/`
- **Examples:** Search codebase for existing implementations
- **Questions:** Review CLAUDE.md guidelines

---

**Remember:** Consistency is key! Always use these patterns for maintainable, scalable code.

---

**Last Updated:** 2025-11-22
**Version:** Post Duplication Elimination
