# Pattern Recognition for AI Agents
## Teaching Agents to Identify Architectural Patterns Dynamically

**Purpose:** Train AI agents to recognize and apply common patterns in CMIS codebase without memorizing specific implementations.

**Last Updated:** 2025-11-18
**Framework:** META_COGNITIVE_FRAMEWORK v2.0

---

## 🎯 PHILOSOPHY: PATTERNS OVER EXAMPLES

**Wrong Approach:**
```
❌ Memorize: "CampaignController uses CampaignService"
❌ Memorize: "Campaign model has status constants"
❌ Memorize: "Use Repository pattern for data access"
```

**Correct Approach:**
```
✅ Recognize: "Controllers delegate to services (pattern detection)"
✅ Recognize: "Models define constants for enums (pattern recognition)"
✅ Recognize: "Repository pattern exists when X files present (discovery)"
```

---

## 📚 CORE PATTERN CATEGORIES

### 1. Architectural Patterns

#### Pattern: Repository Pattern

**Recognition Signals:**
```bash
# Signal 1: Repositories directory exists
test -d app/Repositories && echo "Repository pattern detected"

# Signal 2: Interface + Implementation pairing
find app/Repositories -name "*Interface.php" | wc -l
find app/Repositories -name "*Repository.php" | wc -l

# Signal 3: Service provider binds interfaces
grep -A 5 "bind\|singleton" app/Providers/RepositoryServiceProvider.php
```

**Pattern Template:**
```php
// If pattern exists, follow it:

// 1. Interface defines contract
interface CampaignRepositoryInterface {
    public function find(string $id): ?Campaign;
    public function create(array $data): Campaign;
}

// 2. Implementation
class CampaignRepository implements CampaignRepositoryInterface {
    public function find(string $id): ?Campaign {
        return Campaign::find($id);
    }
}

// 3. Service provider binds
$this->app->bind(
    CampaignRepositoryInterface::class,
    CampaignRepository::class
);

// 4. Controllers inject interface
public function __construct(
    private CampaignRepositoryInterface $campaigns
) {}
```

**Discovery:**
```bash
# Check if repository pattern is actually used
grep -r "RepositoryInterface" app/Http/Controllers/ | wc -l

# If > 0, pattern is active. If 0, pattern is not used.
```

#### Pattern: Service Layer

**Recognition:**
```bash
# Signal: Services directory with business logic
find app/Services -name "*Service.php" | head -10

# Pattern: Controllers are thin, services are fat
wc -l app/Http/Controllers/API/CampaignController.php
wc -l app/Services/CampaignService.php

# If service is larger, pattern is being followed
```

**Pattern Template:**
```php
// Service handles business logic
class CampaignService {
    public function createCampaign(array $data): Campaign {
        // Validation
        // Business rules
        // Data transformation
        // Persistence
        // Event dispatching
        return $campaign;
    }
}

// Controller delegates to service
class CampaignController {
    public function store(Request $request, CampaignService $service) {
        $validated = $request->validate([...]);
        $campaign = $service->createCampaign($validated);
        return response()->json($campaign, 201);
    }
}
```

#### Pattern: Factory Pattern

**Recognition:**
```bash
# Signal: Factory classes exist
find app -name "*Factory.php" | grep -v database/factories

# Example: AdPlatformFactory creates platform connectors
cat app/Services/AdPlatforms/AdPlatformFactory.php | grep -A 10 "public static function make"
```

**Pattern Template:**
```php
class AdPlatformFactory {
    public static function make(string $platform): PlatformConnectorInterface {
        return match($platform) {
            'meta' => app(MetaConnector::class),
            'google' => app(GoogleConnector::class),
            'tiktok' => app(TikTokConnector::class),
            default => throw new UnsupportedPlatformException($platform)
        };
    }
}

// Usage
$connector = AdPlatformFactory::make('meta');
```

**When to Apply:**
- Multiple implementations of same interface
- Need to instantiate based on runtime value
- Strategy pattern with dynamic selection

### 2. Laravel Patterns

#### Pattern: Resource Controllers

**Recognition:**
```php
// Signal: Standard RESTful methods
grep -A 1 "public function index\|public function store\|public function show" app/Http/Controllers/API/*.php
```

**Pattern Template:**
```php
class CampaignController extends Controller {
    public function index()        // GET    /campaigns
    public function store()        // POST   /campaigns
    public function show($id)      // GET    /campaigns/{id}
    public function update($id)    // PUT    /campaigns/{id}
    public function destroy($id)   // DELETE /campaigns/{id}
}
```

#### Pattern: Form Request Validation

**Recognition:**
```bash
# Signal: Requests directory with custom requests
find app/Http/Requests -name "*.php" | head -5
```

**Pattern Template:**
```php
// If pattern exists:
class StoreCampaignRequest extends FormRequest {
    public function rules(): array {
        return [
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
        ];
    }
}

// Controller uses it:
public function store(StoreCampaignRequest $request) {
    // Automatically validated
    $validated = $request->validated();
}
```

**Discovery:**
```bash
# Check if pattern is used
grep -r "FormRequest" app/Http/Controllers/ | wc -l
# If 0, validation is inline. If >0, pattern is used.
```

#### Pattern: API Resources

**Recognition:**
```bash
# Signal: Resources directory
find app/Http/Resources -name "*Resource.php" | head -5
```

**Pattern Template:**
```php
// If resources exist, use them for responses:
class CampaignResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'budget' => $this->budget,
            'status' => $this->status,
        ];
    }
}

// Controller returns resource:
return CampaignResource::collection($campaigns);
```

### 3. Database Patterns

#### Pattern: UUID Primary Keys

**Recognition:**
```sql
-- Signal: Tables use UUID not BIGINT
SELECT
    table_name,
    column_name,
    data_type
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND column_name LIKE '%_id'
  AND is_nullable = 'NO'
ORDER BY table_name
LIMIT 10;

-- If data_type = 'uuid', pattern is used
```

**Pattern Application:**
```php
// Models use UUID
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Campaign extends Model {
    use HasUuids;

    protected $primaryKey = 'campaign_id';
    public $incrementing = false;
    protected $keyType = 'string';
}
```

**Discovery:**
```bash
# Check model patterns
grep -A 3 "class.*extends Model" app/Models/Core/Campaign.php | grep -i uuid
```

#### Pattern: Soft Deletes

**Recognition:**
```sql
-- Signal: deleted_at columns
SELECT
    table_name,
    COUNT(*) as soft_delete_tables
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND column_name = 'deleted_at'
GROUP BY table_name;
```

**Pattern Application:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model {
    use SoftDeletes;
}

// Queries automatically exclude soft-deleted
Campaign::all();  // WHERE deleted_at IS NULL

// Include soft-deleted
Campaign::withTrashed()->get();
```

#### Pattern: Timestamps

**Recognition:**
```sql
-- Signal: created_at, updated_at columns
SELECT table_name
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND column_name = 'created_at'
ORDER BY table_name;
```

**Pattern Application:**
```php
class Campaign extends Model {
    // Laravel adds timestamps automatically
    public $timestamps = true;  // Default
}
```

### 4. Event-Driven Patterns

#### Pattern: Model Events

**Recognition:**
```bash
# Signal: Event classes exist
find app/Events -name "*.php" | wc -l

# Signal: Listeners exist
find app/Listeners -name "*.php" | wc -l

# Signal: EventServiceProvider maps events
cat app/Providers/EventServiceProvider.php | grep -A 20 "protected \$listen"
```

**Pattern Template:**
```php
// 1. Event
class CampaignCreated {
    public function __construct(public Campaign $campaign) {}
}

// 2. Listener
class NotifyTeamOfNewCampaign {
    public function handle(CampaignCreated $event) {
        // Send notification
    }
}

// 3. Register in EventServiceProvider
protected $listen = [
    CampaignCreated::class => [
        NotifyTeamOfNewCampaign::class,
    ],
];

// 4. Dispatch in code
event(new CampaignCreated($campaign));
```

**Discovery:**
```bash
# Check if events are actually dispatched
grep -r "event(" app/Models app/Services | head -10
```

### 5. Queue Patterns

#### Pattern: Job Classes

**Recognition:**
```bash
# Signal: Jobs directory
find app/Jobs -name "*.php" | wc -l

# Signal: implements ShouldQueue
grep -l "implements ShouldQueue" app/Jobs/*.php | wc -l
```

**Pattern Template:**
```php
class ProcessCampaignData implements ShouldQueue {
    use Queueable, InteractsWithQueue;

    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(
        public Campaign $campaign
    ) {}

    public function handle() {
        // Process campaign
    }
}

// Dispatch
ProcessCampaignData::dispatch($campaign);
ProcessCampaignData::dispatch($campaign)->delay(now()->addMinutes(5));
```

**Discovery:**
```bash
# Check queue configuration
cat config/queue.php | jq '.default'

# Verify queue workers running
ps aux | grep "queue:work"
```

### 6. Security Patterns

#### Pattern: Middleware Chain

**Recognition:**
```bash
# Signal: Middleware applied to routes
grep -A 5 "middleware(" routes/api.php | head -20

# Signal: Custom middleware exist
ls -la app/Http/Middleware/
```

**Pattern Template:**
```php
// Route with middleware chain
Route::middleware(['auth:sanctum', 'validate.org.access', 'set.db.context'])
    ->group(function () {
        Route::get('/campaigns', [CampaignController::class, 'index']);
    });
```

**Discovery:**
```bash
# Find middleware order
cat app/Http/Kernel.php | grep -A 30 "middlewareGroups"
```

#### Pattern: Policy-Based Authorization

**Recognition:**
```bash
# Signal: Policies exist
ls -la app/Policies/

# Signal: AuthServiceProvider registers policies
cat app/Providers/AuthServiceProvider.php | grep -A 10 "protected \$policies"
```

**Pattern Template:**
```php
// Policy
class CampaignPolicy {
    public function update(User $user, Campaign $campaign): bool {
        return $user->belongsToOrg($campaign->org_id);
    }
}

// Controller uses policy
$this->authorize('update', $campaign);
```

---

## 🔍 PATTERN DETECTION WORKFLOW

### Step 1: Identify Pattern Category

```bash
# Question: "Is there a service layer?"
find app/Services -name "*Service.php" | wc -l
# Answer: Yes if count > 0

# Question: "Are repositories used?"
find app/Repositories -name "*Repository.php" | wc -l
# Answer: Yes if count > 0

# Question: "Is event-driven architecture used?"
find app/Events -name "*.php" | wc -l
# Answer: Yes if count > 0
```

### Step 2: Study Existing Implementation

```bash
# Find representative example
find app/Http/Controllers -name "CampaignController.php"

# Read structure
cat app/Http/Controllers/API/CampaignController.php | grep -A 3 "public function"

# Identify dependencies
grep "__construct" app/Http/Controllers/API/CampaignController.php
```

### Step 3: Extract Pattern

```bash
# What pattern is being used?
# - Does controller inject service? → Service layer pattern
# - Does controller inject repository? → Repository pattern
# - Does controller have thin methods? → Good separation of concerns
# - Does controller validate inline? → No Form Request pattern
```

### Step 4: Apply Pattern Consistently

```php
// If CampaignController uses CampaignService,
// Then SocialPostController should use SocialPostService

// Pattern consistency check:
ls -la app/Services/ | grep Service.php
ls -la app/Http/Controllers/API/ | grep Controller.php

// Should be 1:1 mapping if pattern is consistent
```

---

## 🎓 PATTERN RECOGNITION EXAMPLES

### Example 1: Recognizing State Machine Pattern

**Signals:**
```php
// Model has status constants
grep "const STATUS" app/Models/Core/Campaign.php

// Methods for state transitions
grep "function activate\|function pause" app/Models/Core/Campaign.php

// Events dispatched on transitions
grep "event(" app/Models/Core/Campaign.php
```

**Recognized Pattern:** Finite State Machine
**Application:** Follow same pattern for other stateful entities

### Example 2: Recognizing EAV Pattern

**Signals:**
```sql
-- Tables for entity-attribute-value
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'cmis'
  AND (table_name LIKE '%field_definition%' OR table_name LIKE '%field_value%');
```

```php
// Models for EAV
ls -la app/Models/Campaign/FieldDefinition.php
ls -la app/Models/Campaign/FieldValue.php
```

**Recognized Pattern:** EAV (Entity-Attribute-Value)
**Application:** Use for flexible schema extensions

### Example 3: Recognizing Webhook Pattern

**Signals:**
```bash
# Webhook controller
find app/Http/Controllers -name "*Webhook*"

# Signature verification
grep -A 10 "verify.*signature" app/Http/Controllers/WebhookController.php

# Job dispatching
grep -A 5 "dispatch" app/Http/Controllers/WebhookController.php
```

**Recognized Pattern:** Webhook Handler with Signature Verification
**Application:** Apply to all webhook endpoints

---

## 🚀 QUICK PATTERN CHECKS

```bash
# Architecture patterns
test -d app/Repositories && echo "Repository pattern: YES" || echo "Repository pattern: NO"
test -d app/Services && echo "Service layer: YES" || echo "Service layer: NO"

# Laravel patterns
test -d app/Http/Resources && echo "API Resources: YES" || echo "API Resources: NO"
test -d app/Http/Requests && echo "Form Requests: YES" || echo "Form Requests: NO"
test -d app/Policies && echo "Policies: YES" || echo "Policies: NO"

# Event patterns
test -d app/Events && echo "Events: YES" || echo "Events: NO"
test -d app/Listeners && echo "Listeners: YES" || echo "Listeners: NO"

# Queue patterns
test -d app/Jobs && echo "Jobs: YES" || echo "Jobs: NO"
```

---

**Remember:** Recognize patterns by observing the code structure, don't assume patterns exist. Every project implements patterns differently.

**Version:** 2.0 - Dynamic Pattern Recognition
**Framework:** META_COGNITIVE_FRAMEWORK
**Approach:** Observe > Recognize > Apply

*"Patterns are discovered, not memorized."*
