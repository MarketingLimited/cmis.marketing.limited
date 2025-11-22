# دليل الوكلاء - Repositories Layer (app/Repositories/)

## 1. Purpose

طبقة Repositories توفر **Data Access Layer**:
- **50+ Repository Classes**
- **Contracts/Interfaces**: Repository patterns
- **Encapsulates Database Logic**: No SQL in Services

## 2. Owned Scope

```
app/Repositories/
├── Contracts/           # Repository interfaces
│   └── RepositoryInterface.php
│
├── CMIS/               # Core repositories
│   └── OrganizationRepository.php
│
├── Analytics/          # Analytics data access
├── Publishing/         # Social publishing
├── Knowledge/          # Knowledge base
└── Operations/         # Operational data
```

## 3. Patterns

### Repository Pattern
```php
namespace App\Repositories\Campaign;

use App\Models\Campaign\Campaign;

class CampaignRepository
{
    public function all()
    {
        return Campaign::with(['org', 'contentPlans'])->get();
    }

    public function find(string $id): ?Campaign
    {
        return Campaign::find($id);
    }

    public function create(array $data): Campaign
    {
        return Campaign::create($data);
    }

    public function update(string $id, array $data): Campaign
    {
        $campaign = $this->find($id);
        $campaign->update($data);
        return $campaign->fresh();
    }

    public function delete(string $id): bool
    {
        return Campaign::destroy($id) > 0;
    }

    // Custom queries
    public function getActiveCampaigns()
    {
        return Campaign::where('status', 'active')
                       ->with('metrics')
                       ->get();
    }
}
```

## 4. Rules

- ✅ All database queries in Repositories
- ✅ Return Models or Collections
- ✅ Use Eloquent relationships
- ❌ **Never** put business logic here (that's for Services)
