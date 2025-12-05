# Super Admin Dashboard - Comprehensive Platform Management

**Date:** 2025-12-05
**Author:** Claude Code Agent
**Status:** Implemented
**Version:** 1.0

---

## Overview

The Super Admin Dashboard provides CMIS platform owners with comprehensive management capabilities for organizations, users, subscriptions, and system monitoring. Super admins can access all platform data, perform bulk operations, and monitor system health from a centralized dashboard.

### Key Features
- **Platform-wide Statistics** - Dashboard with key metrics and alerts
- **Organization Management** - View, suspend, block, restore organizations
- **User Management** - Manage all users with impersonation capability
- **Plans & Subscriptions** - Create/edit plans, manage subscriptions
- **API Analytics** - Request tracking, error monitoring, performance metrics
- **System Health** - Database, cache, queue, storage monitoring
- **System Logs** - View and manage Laravel application logs

---

## Architecture

### Database Schema

The super admin feature extends the existing schema with:

```sql
-- Plans Table (cmis.plans)
CREATE TABLE cmis.plans (
    plan_id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10,2),
    price_yearly DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',
    max_users INTEGER,
    max_orgs INTEGER,
    max_api_calls_per_month INTEGER,
    features JSONB DEFAULT '{}',
    is_active BOOLEAN DEFAULT true,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Organization Status Fields (cmis.orgs)
ALTER TABLE cmis.orgs ADD COLUMN status VARCHAR(50) DEFAULT 'active';
ALTER TABLE cmis.orgs ADD COLUMN suspended_at TIMESTAMP;
ALTER TABLE cmis.orgs ADD COLUMN suspended_by UUID;
ALTER TABLE cmis.orgs ADD COLUMN suspension_reason TEXT;
ALTER TABLE cmis.orgs ADD COLUMN blocked_at TIMESTAMP;
ALTER TABLE cmis.orgs ADD COLUMN blocked_by UUID;
ALTER TABLE cmis.orgs ADD COLUMN block_reason TEXT;

-- User Status Fields (cmis.users)
ALTER TABLE cmis.users ADD COLUMN is_super_admin BOOLEAN DEFAULT false;
ALTER TABLE cmis.users ADD COLUMN is_suspended BOOLEAN DEFAULT false;
ALTER TABLE cmis.users ADD COLUMN suspended_at TIMESTAMP;
ALTER TABLE cmis.users ADD COLUMN suspended_by UUID;
ALTER TABLE cmis.users ADD COLUMN suspension_reason TEXT;
ALTER TABLE cmis.users ADD COLUMN is_blocked BOOLEAN DEFAULT false;
ALTER TABLE cmis.users ADD COLUMN blocked_at TIMESTAMP;
ALTER TABLE cmis.users ADD COLUMN blocked_by UUID;
ALTER TABLE cmis.users ADD COLUMN block_reason TEXT;

-- Subscription Extensions (cmis.subscriptions)
ALTER TABLE cmis.subscriptions ADD COLUMN plan_id UUID REFERENCES cmis.plans(plan_id);
ALTER TABLE cmis.subscriptions ADD COLUMN trial_ends_at TIMESTAMP;
ALTER TABLE cmis.subscriptions ADD COLUMN cancelled_at TIMESTAMP;
ALTER TABLE cmis.subscriptions ADD COLUMN cancellation_reason TEXT;
```

### File Structure

```
app/
├── Http/
│   ├── Controllers/SuperAdmin/
│   │   ├── SuperAdminController.php      # Dashboard
│   │   ├── OrgController.php             # Organizations
│   │   ├── UserController.php            # Users
│   │   ├── PlanController.php            # Plans CRUD
│   │   ├── SubscriptionController.php    # Subscriptions
│   │   ├── AnalyticsController.php       # API Analytics
│   │   └── SystemController.php          # System Health & Logs
│   └── Middleware/
│       └── SuperAdmin.php                # Access control
├── Models/
│   └── Plan.php                          # Plan model

resources/views/super-admin/
├── layouts/
│   └── app.blade.php                     # Super admin layout
├── dashboard.blade.php                   # Dashboard overview
├── organizations/
│   ├── index.blade.php                   # Org list
│   └── show.blade.php                    # Org details
├── users/
│   ├── index.blade.php                   # User list
│   └── show.blade.php                    # User details
├── plans/
│   ├── index.blade.php                   # Plans grid
│   ├── create.blade.php                  # Create plan
│   └── edit.blade.php                    # Edit plan
├── subscriptions/
│   └── index.blade.php                   # Subscriptions list
├── analytics/
│   └── index.blade.php                   # API analytics
├── system/
│   ├── health.blade.php                  # System health
│   └── logs.blade.php                    # System logs
└── partials/
    └── sidebar.blade.php                 # Navigation sidebar

routes/
└── super-admin.php                       # Super admin routes

lang/
├── en/super_admin.php                    # English translations
└── ar/super_admin.php                    # Arabic translations
```

---

## Routes

All super admin routes require authentication and `super.admin` middleware:

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| GET | `/super-admin/dashboard` | SuperAdminController@dashboard | Main dashboard |
| GET | `/super-admin/organizations` | OrgController@index | List organizations |
| GET | `/super-admin/organizations/{org}` | OrgController@show | View organization |
| POST | `/super-admin/organizations/{org}/suspend` | OrgController@suspend | Suspend org |
| POST | `/super-admin/organizations/{org}/block` | OrgController@block | Block org |
| POST | `/super-admin/organizations/{org}/restore` | OrgController@restore | Restore org |
| POST | `/super-admin/organizations/bulk-action` | OrgController@bulkAction | Bulk actions |
| GET | `/super-admin/users` | UserController@index | List users |
| GET | `/super-admin/users/{user}` | UserController@show | View user |
| POST | `/super-admin/users/{user}/suspend` | UserController@suspend | Suspend user |
| POST | `/super-admin/users/{user}/block` | UserController@block | Block user |
| POST | `/super-admin/users/{user}/restore` | UserController@restore | Restore user |
| POST | `/super-admin/users/{user}/impersonate` | UserController@impersonate | Impersonate user |
| POST | `/super-admin/users/bulk-action` | UserController@bulkAction | Bulk actions |
| GET | `/super-admin/plans` | PlanController@index | List plans |
| GET | `/super-admin/plans/create` | PlanController@create | Create plan form |
| POST | `/super-admin/plans` | PlanController@store | Store plan |
| GET | `/super-admin/plans/{plan}/edit` | PlanController@edit | Edit plan form |
| PUT | `/super-admin/plans/{plan}` | PlanController@update | Update plan |
| DELETE | `/super-admin/plans/{plan}` | PlanController@destroy | Delete plan |
| POST | `/super-admin/plans/{plan}/toggle` | PlanController@toggle | Toggle active |
| GET | `/super-admin/subscriptions` | SubscriptionController@index | List subscriptions |
| POST | `/super-admin/subscriptions/{sub}/change-plan` | SubscriptionController@changePlan | Change plan |
| POST | `/super-admin/subscriptions/{sub}/cancel` | SubscriptionController@cancel | Cancel subscription |
| POST | `/super-admin/subscriptions/{sub}/reactivate` | SubscriptionController@reactivate | Reactivate |
| POST | `/super-admin/subscriptions/{sub}/extend-trial` | SubscriptionController@extendTrial | Extend trial |
| GET | `/super-admin/analytics` | AnalyticsController@index | API analytics |
| GET | `/super-admin/api/analytics/overview` | AnalyticsController@overview | Analytics data |
| GET | `/super-admin/api/analytics/by-platform` | AnalyticsController@byPlatform | Platform breakdown |
| GET | `/super-admin/api/analytics/by-org` | AnalyticsController@byOrg | Org breakdown |
| GET | `/super-admin/api/analytics/errors` | AnalyticsController@errors | Error listing |
| GET | `/super-admin/system/health` | SystemController@health | System health |
| GET | `/super-admin/system/logs` | SystemController@logs | System logs |
| DELETE | `/super-admin/system/logs` | SystemController@clearLogs | Clear logs |

---

## Authentication & Authorization

### SuperAdmin Middleware

```php
// app/Http/Middleware/SuperAdmin.php
class SuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->is_super_admin) {
            abort(403, 'Super Admin access required');
        }

        return $next($request);
    }
}
```

### Registration in Kernel

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    // ...
    'super.admin' => \App\Http\Middleware\SuperAdmin::class,
];
```

### Login Redirect

Super admins are automatically redirected to `/super-admin/dashboard` after login:

```php
// AuthenticatedSessionController.php or LoginController.php
protected function redirectTo(): string
{
    $user = auth()->user();

    if ($user->is_super_admin) {
        return '/super-admin/dashboard';
    }

    return '/dashboard';
}
```

---

## UI Components

### Alpine.js Patterns

Each view uses Alpine.js for interactivity:

```javascript
// Organization Management
function orgsManager() {
    return {
        loading: false,
        organizations: [],
        filters: { search: '', status: '', plan: '' },
        selectedOrgs: [],
        pagination: {},

        async loadOrganizations() { ... },
        async suspendOrg(orgId, reason) { ... },
        async blockOrg(orgId, reason) { ... },
        async restoreOrg(orgId) { ... },
        async bulkAction(action) { ... }
    }
}

// Plans Management
function planEditor() {
    return {
        form: { name: '', code: '', ... },
        features: { social_publishing: false, ... },

        generateCode() {
            this.form.code = this.form.name.toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_|_$/g, '');
        },
        async savePlan() { ... }
    }
}

// Analytics Dashboard
function analyticsManager() {
    return {
        timeRange: '24h',
        stats: {},
        charts: { requests: null, platforms: null },

        async loadAnalytics() { ... },
        initCharts() { ... }
    }
}
```

### RTL/LTR Support

All views support both Arabic (RTL) and English (LTR):

```php
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<div class="{{ $isRtl ? 'text-right pr-4' : 'text-left pl-4' }}">
    <i class="fas fa-search {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
    {{ __('super_admin.search') }}
</div>

<!-- Pagination arrows flip based on direction -->
<button>
    <i class="fas {{ $isRtl ? 'fa-chevron-right' : 'fa-chevron-left' }}"></i>
</button>
```

### Dark Mode Support

All views support dark mode via Tailwind CSS:

```html
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
    <span class="text-gray-600 dark:text-gray-400">Secondary text</span>
</div>
```

---

## Translations

### English (`lang/en/super_admin.php`)

```php
return [
    'title' => 'Super Admin',
    'nav' => [
        'dashboard' => 'Dashboard',
        'organizations' => 'Organizations',
        'users' => 'Users',
        // ...
    ],
    'dashboard' => [
        'total_organizations' => 'Total Organizations',
        'api_calls_today' => 'API Calls Today',
        // ...
    ],
    // ...
];
```

### Arabic (`lang/ar/super_admin.php`)

```php
return [
    'title' => 'المشرف العام',
    'nav' => [
        'dashboard' => 'لوحة التحكم',
        'organizations' => 'المؤسسات',
        'users' => 'المستخدمين',
        // ...
    ],
    'dashboard' => [
        'total_organizations' => 'إجمالي المؤسسات',
        'api_calls_today' => 'طلبات API اليوم',
        // ...
    ],
    // ...
];
```

---

## Usage

### Creating a Super Admin User

```php
use App\Models\User;

$user = User::find($userId);
$user->update(['is_super_admin' => true]);
```

Or via migration/seeder:

```php
User::create([
    'name' => 'Super Admin',
    'email' => 'admin@cmis.test',
    'password' => bcrypt('password'),
    'is_super_admin' => true,
]);
```

### Accessing the Dashboard

1. Login with a super admin user
2. You'll be automatically redirected to `/super-admin/dashboard`
3. Or navigate directly to `https://your-domain.com/super-admin/dashboard`

### Managing Organizations

1. Go to **Super Admin > Organizations**
2. Use search and filters to find organizations
3. Click on an organization to view details
4. Actions available:
   - **Suspend** - Temporarily disable (with reason)
   - **Block** - Permanently disable (with reason)
   - **Restore** - Re-enable suspended/blocked org
   - **Change Plan** - Update subscription plan

### Managing Users

1. Go to **Super Admin > Users**
2. Filter by status, role, or organization
3. Actions available:
   - **Suspend/Block/Restore** - Manage user status
   - **Impersonate** - Login as the user for debugging
4. Bulk actions available for multiple users

### Managing Plans

1. Go to **Super Admin > Plans**
2. Click **Create Plan** to add a new plan
3. Configure:
   - Name, code, description
   - Monthly/yearly pricing
   - Limits (users, orgs, API calls)
   - Features (checkboxes)
4. Toggle plans active/inactive
5. Delete plans (only if no subscribers)

### Monitoring Analytics

1. Go to **Super Admin > API Analytics**
2. Select time range (1h, 6h, 24h, 7d, 30d)
3. View:
   - Total requests with comparison to previous period
   - Error rate and affected endpoints
   - Average response time
   - Rate limit hits by organization
   - Top organizations by usage
   - Top endpoints by request count
   - Recent errors with details

### System Health Monitoring

1. Go to **Super Admin > System Health**
2. View status of:
   - **Database** - Connections, response time
   - **Cache** - Hit rate, memory usage
   - **Queue** - Pending jobs, failed jobs
   - **Storage** - Disk usage, available space
   - **Mail** - Emails sent, last sent time
   - **Scheduler** - Last run, next scheduled run
3. Click **View All Logs** to see detailed logs

---

## Security Considerations

1. **Middleware Protection** - All routes protected by `super.admin` middleware
2. **Impersonation Logging** - All impersonation sessions are logged
3. **Action Audit Trail** - All admin actions (suspend, block, delete) are logged
4. **CSRF Protection** - All POST/PUT/DELETE requests require CSRF token
5. **RLS Bypass** - Super admins can see all data (RLS is not applied to admin queries)

---

## Testing

### Manual Testing Checklist

- [ ] Login as super admin redirects to `/super-admin/dashboard`
- [ ] Regular users get 403 when accessing `/super-admin/*`
- [ ] Organizations can be listed, filtered, searched
- [ ] Organizations can be suspended/blocked/restored
- [ ] Users can be listed, filtered by org/role/status
- [ ] Users can be suspended/blocked/restored
- [ ] User impersonation works and can be stopped
- [ ] Plans can be created, edited, deleted
- [ ] Plans with subscribers cannot be deleted
- [ ] Subscriptions can be changed, cancelled, reactivated
- [ ] Analytics charts display correctly
- [ ] System health shows accurate service status
- [ ] All views work in both Arabic and English
- [ ] Dark mode displays correctly

### Creating Test Data

```php
// Create test plans
Plan::create([
    'name' => 'Free',
    'code' => 'free',
    'price_monthly' => 0,
    'price_yearly' => 0,
    'max_users' => 5,
    'max_api_calls_per_month' => 1000,
    'is_active' => true,
]);

Plan::create([
    'name' => 'Professional',
    'code' => 'professional',
    'price_monthly' => 49,
    'price_yearly' => 490,
    'max_users' => 25,
    'max_api_calls_per_month' => 50000,
    'features' => ['ai_features' => true, 'analytics' => true],
    'is_active' => true,
]);
```

---

## Related Documentation

- [CMIS Multi-Tenancy Patterns](../knowledge/MULTI_TENANCY_PATTERNS.md)
- [i18n & RTL Requirements](../knowledge/I18N_RTL_REQUIREMENTS.md)
- [Browser Testing Guide](../knowledge/BROWSER_TESTING_GUIDE.md)
- [API Response Patterns](./api-response-patterns.md)

---

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2025-12-05 | 1.0 | Initial implementation |
