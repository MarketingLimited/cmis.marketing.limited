# CMIS Architecture Documentation

This directory contains comprehensive documentation for the CMIS system architecture, design patterns, and structural analysis.

---

## Quick Navigation

- **[Overview](overview.md)** - High-level architecture overview
- **[Structure Analysis](structure-analysis.md)** - Comprehensive structural analysis
- **[Architecture Improvements](improvements.md)** - Planned and completed improvements

---

## System Overview

CMIS is built using a modern, scalable architecture based on Laravel and PostgreSQL:

```
┌─────────────────────────────────────────────────────┐
│                   Frontend Layer                     │
│  (Blade, Alpine.js, Livewire, Tailwind CSS)        │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│                  Application Layer                   │
│              (Laravel Controllers)                   │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│                   Service Layer                      │
│  (Business Logic, AI Services, Platform Services)   │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│                  Repository Layer                    │
│         (Data Access, Query Building)                │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│                   Data Layer                         │
│      (PostgreSQL + RLS, Redis, File Storage)        │
└─────────────────────────────────────────────────────┘
```

---

## Core Architectural Principles

### 1. Multi-Tenancy First
- Row-Level Security (RLS) for data isolation
- Tenant context in every request
- Automatic tenant filtering
- Secure by default

### 2. Service-Oriented Design
- Business logic in service classes
- Reusable service components
- Clear separation of concerns
- Testable architecture

### 3. Repository Pattern
- Abstract data access layer
- Query builder encapsulation
- Consistent data operations
- Easy to test and mock

### 4. Event-Driven Architecture
- Laravel events and listeners
- Asynchronous processing
- Decoupled components
- Scalable design

### 5. API-First Approach
- RESTful API design
- Versioned endpoints
- OpenAPI documentation
- Platform integrations

---

## Key Components

### 1. Multi-Tenancy Layer

**Implementation:**
- PostgreSQL Row-Level Security (RLS)
- Tenant context middleware
- Organization-based isolation
- User-organization relationships

**Benefits:**
- Automatic data isolation
- No application-level filtering
- Database-enforced security
- Simplified queries

**See:** [Multi-Tenancy Patterns](.claude/knowledge/MULTI_TENANCY_PATTERNS.md)

### 2. AI & Semantic Layer

**Components:**
- OpenAI GPT-4 integration
- Vector embeddings (pgvector)
- Semantic search engine
- Knowledge base management
- RAG (Retrieval-Augmented Generation)

**See:** [AI Features Documentation](../features/ai-semantic/)

### 3. Platform Integration Layer

**Supported Platforms:**
- Meta (Facebook, Instagram)
- LinkedIn
- TikTok
- Google Ads
- Twitter/X

**Architecture:**
- Platform adapter pattern
- Unified interface
- Platform-specific implementations
- Error handling and retries

**See:** [Platform Integrations](../integrations/)

### 4. Campaign Management Core

**Modules:**
- Campaign planning
- Content creation
- Creative management
- Copy components
- Multi-platform publishing

**Flow:**
```
Campaign → Content Plan → Content Items →
Creative Assets + Copy Components →
Platform Adaptation → Publishing
```

### 5. Analytics & Reporting

**Features:**
- Real-time metrics collection
- Cross-platform analytics
- Custom report builder
- Data visualization
- Export capabilities

---

## Design Patterns

### Repository Pattern

```php
interface CampaignRepositoryInterface
{
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}

class CampaignRepository implements CampaignRepositoryInterface
{
    // Implementation with tenant awareness
}
```

### Service Pattern

```php
class CampaignService
{
    public function __construct(
        private CampaignRepository $campaigns,
        private ContentService $content,
        private AIService $ai
    ) {}

    public function createWithAI(array $data): Campaign
    {
        // Complex business logic
    }
}
```

### Adapter Pattern (Platform Integration)

```php
interface PlatformAdapterInterface
{
    public function publish(Post $post): PublishResult;
    public function getMetrics(string $postId): Metrics;
}

class FacebookAdapter implements PlatformAdapterInterface
{
    // Platform-specific implementation
}
```

### Observer Pattern (Events)

```php
// Event
class CampaignPublished
{
    public function __construct(public Campaign $campaign) {}
}

// Listener
class UpdateCampaignMetrics
{
    public function handle(CampaignPublished $event)
    {
        // Update metrics
    }
}
```

---

## Data Flow

### Request Lifecycle

```
1. User Request
   ↓
2. Middleware (Auth, Tenant Context)
   ↓
3. Controller (Validation)
   ↓
4. Service Layer (Business Logic)
   ↓
5. Repository Layer (Data Access)
   ↓
6. Database (with RLS)
   ↓
7. Response
```

### Background Job Flow

```
1. Job Dispatched (e.g., Publish Campaign)
   ↓
2. Queue System (Redis/Database)
   ↓
3. Worker Processes Job
   ↓
4. Service Layer Execution
   ↓
5. Platform API Call
   ↓
6. Result Stored
   ↓
7. Event Fired (Success/Failure)
```

---

## Technology Stack

### Backend
- **Laravel 10** - PHP framework
- **PHP 8.1+** - Programming language
- **PostgreSQL 14+** - Primary database
- **Redis** - Caching and queues
- **pgvector** - Vector similarity search

### Frontend
- **Blade Templates** - Server-side rendering
- **Alpine.js 3.x** - Reactive UI
- **Livewire 3.x** - Full-stack framework
- **Tailwind CSS 3.x** - Utility-first CSS
- **Vue.js** - Interactive components

### Infrastructure
- **Nginx/Apache** - Web server
- **Supervisor** - Queue worker management
- **Cron** - Scheduled tasks
- **Docker** - Containerization (optional)

### External Services
- **OpenAI API** - AI capabilities
- **Meta Graph API** - Facebook/Instagram
- **LinkedIn API** - LinkedIn integration
- **TikTok API** - TikTok integration
- **Google Ads API** - Google advertising

---

## Security Architecture

### Authentication & Authorization
- Laravel Sanctum for API tokens
- Role-Based Access Control (RBAC)
- Permission-based authorization
- Multi-factor authentication support

### Data Security
- Row-Level Security (RLS)
- Encrypted sensitive data
- SQL injection prevention
- XSS protection
- CSRF protection

### API Security
- Rate limiting
- API key management
- OAuth 2.0 for platform integrations
- Webhook signature verification

---

## Scalability Considerations

### Horizontal Scaling
- Stateless application design
- Shared session storage (Redis)
- Load balancer ready
- Database connection pooling

### Vertical Scaling
- Optimized queries
- Efficient indexing
- Caching strategy
- Background job processing

### Performance Optimization
- Query optimization
- Eager loading relationships
- Redis caching
- Asset optimization
- CDN for static assets

---

## Module Organization

```
app/
├── Http/
│   ├── Controllers/        # Request handling
│   └── Middleware/         # Request/response filtering
├── Services/               # Business logic
│   ├── AI/                # AI services
│   ├── Campaign/          # Campaign services
│   ├── Platform/          # Platform integrations
│   └── Analytics/         # Analytics services
├── Repositories/          # Data access layer
├── Models/                # Eloquent models
│   ├── Core/             # Core models (User, Org)
│   ├── Campaign/         # Campaign models
│   ├── Platform/         # Platform models
│   └── Analytics/        # Analytics models
├── Events/               # Event definitions
├── Listeners/            # Event handlers
└── Jobs/                 # Background jobs
```

---

## Documentation by Topic

### For Architects
- [Structure Analysis](structure-analysis.md) - Deep architectural analysis
- [Architecture Improvements](improvements.md) - Enhancement proposals

### For Developers
- [Structure Analysis](structure-analysis.md) - Code organization
- [Repository Pattern Guide](../../app/Repositories/README.md) - Repository usage
- [Laravel Conventions](.claude/knowledge/LARAVEL_CONVENTIONS.md) - Coding standards

### For DevOps
- [Deployment Guide](../deployment/) - Infrastructure setup
- [Performance Optimization](improvements.md) - Performance tips

---

## Related Documentation

- **[Database Architecture](../features/database/)** - Database design
- **[Multi-Tenancy Patterns](.claude/knowledge/MULTI_TENANCY_PATTERNS.md)** - Multi-tenancy implementation
- **[API Documentation](../api/)** - API architecture
- **[AI Integration](../features/ai-semantic/)** - AI architecture

---

## Architecture Decision Records (ADRs)

Key architectural decisions and their rationale:

### 1. Multi-Tenancy via RLS
**Decision:** Use PostgreSQL Row-Level Security for multi-tenancy

**Rationale:**
- Database-enforced security
- Simpler application code
- Performance benefits
- Reduced risk of data leaks

### 2. Repository Pattern
**Decision:** Implement repository pattern for data access

**Rationale:**
- Abstraction of data layer
- Easier testing
- Consistent data operations
- Future database flexibility

### 3. Service Layer
**Decision:** Extract business logic to service classes

**Rationale:**
- Thin controllers
- Reusable logic
- Better testability
- Clear separation of concerns

### 4. Event-Driven Architecture
**Decision:** Use Laravel events for cross-module communication

**Rationale:**
- Decoupled modules
- Asynchronous processing
- Extensibility
- Audit trail

---

## Future Architecture Considerations

### Microservices
- Consider microservices for:
  - AI processing
  - Platform integrations
  - Analytics processing

### GraphQL
- Evaluate GraphQL for:
  - Flexible queries
  - Frontend efficiency
  - Mobile applications

### Real-time Features
- WebSocket implementation for:
  - Live analytics
  - Real-time collaboration
  - Instant notifications

---

## Support

- **Architecture Questions** → See [Structure Analysis](structure-analysis.md)
- **Design Patterns** → See [Laravel Conventions](.claude/knowledge/LARAVEL_CONVENTIONS.md)
- **Improvements** → See [Architecture Improvements](improvements.md)
- **Database Design** → See [Database Documentation](../features/database/)

---

**Last Updated:** 2025-11-18
**Maintained by:** CMIS Architecture Team
