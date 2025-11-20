# CMIS - نظام إدارة التسويق الإدراكي
## Cognitive Marketing Information System

<div align="center">

**A comprehensive, enterprise-grade marketing management platform built with Laravel 12 and PostgreSQL**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+%20(tested%208.4)-777BB4?logo=php)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16+-336791?logo=postgresql)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

[Features](#-features) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [Architecture](#-architecture) • [Contributing](#-contributing)

</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Key Features](#-features)
- [Technology Stack](#-technology-stack)
- [Quick Start](#-quick-start)
- [System Architecture](#-architecture)
- [Database Structure](#-database-structure)
- [Platform Integrations](#-platform-integrations)
- [Security](#-security)
- [Development](#-development)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Documentation](#-documentation)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🎯 Overview

CMIS (Cognitive Marketing Information System) is an advanced, AI-powered marketing management platform designed for agencies and enterprises managing multi-tenant campaigns across various digital platforms. The system provides comprehensive tools for campaign planning, creative asset management, performance tracking, and automated content publishing.

### What Makes CMIS Special?

- **Multi-Tenancy Architecture**: Fully isolated data and permissions per organization with Row-Level Security (RLS)
- **AI-Powered Insights**: Semantic search, predictive analytics, and automated campaign recommendations
- **Platform Agnostic**: Unified interface for managing campaigns across Meta, Google, TikTok, and more
- **Cognitive Framework**: Advanced marketing frameworks, playbooks, and strategic planning tools
- **Real-Time Analytics**: Comprehensive dashboards with KPI tracking and performance visualization

---

## ✨ Features

### Core Capabilities

#### 🏢 Multi-Organization Management
- Secure multi-tenant architecture with organization-level data isolation
- Role-based access control (RBAC) with fine-grained permissions
- User invitation system and team management
- Cross-organization reporting for agency use cases

#### 📊 Campaign Management
- End-to-end campaign lifecycle management
- Multi-platform campaign orchestration
- Budget tracking and allocation
- A/B testing and experimentation frameworks
- Campaign templates and playbooks

#### 🎨 Creative Asset Management
- Centralized digital asset library
- Version control and approval workflows
- AI-powered asset tagging and organization
- Format optimization for different platforms
- Creative performance tracking

#### 🤖 AI & Machine Learning
- Semantic search using vector embeddings (pgvector)
- Predictive campaign performance modeling
- Automated content recommendations
- Sentiment analysis and tone detection
- AI-generated campaign suggestions

#### 📱 Social Media Management
- Unified social media scheduling and publishing
- Post performance tracking across platforms
- Engagement metrics and analytics
- Content calendar visualization
- Automated posting workflows

#### 📈 Analytics & Reporting
- Real-time performance dashboards
- Custom KPI definitions and tracking
- Temporal data analysis and trends
- ROI and attribution modeling
- Exportable reports and data visualization

#### 🔗 Platform Integrations
- **Meta** (Facebook & Instagram): Ads, posts, insights
- **Google**: Ads, Analytics, Search Console
- **TikTok**: Ads and organic content
- **Twitter/X**: Post management and analytics
- **LinkedIn**: Professional content and ads
- Extensible connector architecture for new platforms

---

## 🛠 Technology Stack

### Backend
- **Framework**: Laravel 12.x
- **Language**: PHP 8.2+ (tested on 8.4.14)
- **Database**: PostgreSQL 16+ with pgvector extension
- **Cache**: Redis (phpredis)
- **Queue**: Redis-backed Laravel queues
- **API**: RESTful JSON API with Laravel Sanctum authentication

### Codebase Stats
- **PHP Files**: 712 files
- **Models**: 244 Eloquent models across 51 business domains
- **Migrations**: 45 database migrations
- **Tests**: 201 test files (Unit, Feature, Integration)
- **Agents**: 26 specialized Claude Code agents

### Frontend
- **Build Tool**: Vite
- **JavaScript**: Alpine.js 3.x
- **CSS**: Tailwind CSS 3.x
- **Charts**: Chart.js 4.x
- **HTTP Client**: Axios

### DevOps & Tools
- **Testing**: PHPUnit (unit/integration), Playwright (E2E)
- **Code Quality**: Laravel Pint, PHPStan
- **Logging**: Laravel Pail for real-time log streaming
- **Development**: Laravel Sail (Docker), Artisan CLI

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.2 or higher (PHP 8.4+ recommended and tested)
- PostgreSQL 16+ with pgvector extension
- Redis server
- Composer
- Node.js 18+ & npm
- Git

### Installation

```bash
# Clone the repository
git clone https://github.com/MarketingLimited/cmis.marketing.limited.git
cd cmis.marketing.limited

# Install dependencies and setup environment
composer run setup

# Configure your environment
cp .env.example .env
# Edit .env with your database credentials and API keys

# Run migrations (includes schema and seed data)
php artisan migrate --seed

# Start development servers
composer run dev
```

The application will be available at `http://localhost:8000`

### Quick Commands

```bash
# Start all development services (web, queue, logs, vite)
composer run dev

# Run tests
composer test                    # Backend tests (PHPUnit)
npm run test:e2e                # E2E tests (Playwright)
npm run test:all                # All tests

# Database operations
php artisan migrate              # Run migrations
php artisan db:seed             # Seed database
php artisan migrate:fresh --seed # Fresh start

# Platform synchronization
php artisan sync:platform meta --org=<org-id>
php artisan sync:all            # Sync all configured platforms

# Background jobs
php artisan queue:work          # Process queue jobs
php artisan schedule:work       # Run scheduled tasks
```

### 👥 Initial Users & Access

After running the seeders, the following test accounts are available:

#### Feature Toggle Management
Access the feature toggle admin dashboard at: `http://localhost/admin/features`

**Admin Account (Feature Management):**
- Email: `admin@cmis.test`
- Password: `password`
- Role: Super Administrator
- Access: Full feature flag management

#### System Admin Account
**Super Admin:**
- Email: `admin@cmis.test`
- Password: `password`
- Role: Super Administrator
- Access: All system features and organizations

#### Test Users (Various Roles)

**Test Admin User:**
- Email: `test@cmis.test`
- Password: `password`
- Role: Administrator

**Demo Organization Users:**
- **TechVision LLC:**
  - Email: `sarah@techvision.com` / `password`
  - Email: `maria@techvision.com` / `password`

- **Arabic Marketing Hub:**
  - Email: `mohamed@arabic-marketing.com` / `password`
  - Email: `ahmed@arabic-marketing.com` / `password`

- **FashionHub Co:**
  - Email: `emma@fashionhub.com` / `password`

- **HealthWell Clinic:**
  - Email: `david@healthwell.com` / `password`

> **Note:** All demo accounts use the default password: `password`
> **Security:** Change these credentials in production environments!

#### Quick Access URLs
- **Admin Dashboard:** `http://localhost/admin`
- **Feature Toggle Dashboard:** `http://localhost/admin/features`
- **API Documentation:** `http://localhost/api/documentation`
- **Main Application:** `http://localhost`

---

## 🏗 Architecture

### System Design

CMIS follows a modular, service-oriented architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend Layer                        │
│        (Alpine.js, Tailwind, Chart.js, Vite)            │
└──────────────────┬──────────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────────┐
│                   API Layer (Laravel)                    │
│  ┌─────────────────────────────────────────────────┐   │
│  │  Controllers → Services → Repositories → Models  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                          │
│  Middleware: Auth, Context, RLS, Rate Limiting          │
└──────────────────┬──────────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────────┐
│                Data Layer (PostgreSQL)                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  12 Schemas  │  │  148+ Tables │  │  RLS Policies│ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ 244 Models   │  │ 45 Migrations│  │  201 Tests   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                          │
│  pgvector │ Triggers │ Functions │ Materialized Views  │
└─────────────────────────────────────────────────────────┘
```

### Key Architectural Patterns

#### 1. Multi-Tenancy with Row-Level Security (RLS)
Every request is executed within a secure organization context:

```php
// Automatically set by SetDatabaseContext middleware
DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$userId, $orgId]);

// All subsequent queries are automatically filtered by RLS policies
$campaigns = Campaign::all(); // Only returns campaigns for the current org
```

#### 2. Connector Pattern for Platform Integrations
Unified interface for all external platforms:

```php
// Factory pattern for connector instantiation
$connector = ConnectorFactory::make('meta');
$connector->syncCampaigns($orgId);

// Easy to add new platforms
$tiktokConnector = ConnectorFactory::make('tiktok');
```

#### 3. Command Pattern for Background Jobs
Scheduled tasks with organization context awareness:

```php
// Runs per organization with proper context
php artisan sync:instagram --org=<uuid>
php artisan embeddings:generate
php artisan cognitive:vitality-log
```

---

## 🗄 Database Structure

CMIS uses a sophisticated PostgreSQL schema with **12 specialized schemas** and **197 tables**:

### Schema Organization

| Schema | Purpose | Key Tables |
|--------|---------|------------|
| `cmis` | Core system entities | `users`, `orgs`, `user_orgs`, `roles` |
| `campaigns` | Campaign management | `campaigns`, `campaign_groups`, `targeting` |
| `creative` | Asset management | `creative_assets`, `asset_versions`, `approvals` |
| `social` | Social media | `social_accounts`, `social_posts`, `post_metrics` |
| `ads` | Advertising platforms | `ad_accounts`, `ad_campaigns`, `ad_metrics` |
| `analytics` | Performance data | `performance_metrics`, `kpi_calculations` |
| `ai` | AI/ML features | `embeddings`, `ai_recommendations`, `models` |
| `reference` | Marketing frameworks | `frameworks`, `playbooks`, `strategies`, `tones` |
| `operations` | System operations | `sync_logs`, `etl_logs`, `audit_logs` |
| `security` | Security & access | `permissions`, `session_contexts`, `audit_trails` |
| `backup` | Data backup | Backup copies of critical tables |
| `views` | Materialized views | Precomputed dashboards and reports |

### Advanced Database Features

- **Row-Level Security (RLS)**: Automatic data filtering per organization
- **pgvector Extension**: Semantic search with embedding vectors
- **Temporal Tables**: Full audit trail with `deleted_at` soft deletes
- **Materialized Views**: Performance-optimized dashboards
- **Database Functions**: Complex business logic at the DB level
- **Triggers**: Automatic audit logging and cache invalidation

---

## 🔌 Platform Integrations

### Connector Architecture

CMIS implements a flexible connector system for integrating with external platforms:

```php
// All connectors implement a common interface
interface ConnectorInterface {
    public function connect(string $orgId, array $credentials): ConnectedAccount;
    public function disconnect(string $accountId): bool;
    public function syncCampaigns(string $accountId): Collection;
    public function syncPosts(string $accountId): Collection;
    public function publishPost(string $accountId, array $content): Post;
}
```

### Supported Platforms

#### Meta (Facebook & Instagram)
- Ad campaign creation and management
- Organic post publishing and scheduling
- Audience insights and demographics
- Real-time performance metrics
- Instagram story and reel support

#### Google (Ads & Analytics)
- Search and display campaigns
- Google Analytics 4 integration
- Conversion tracking
- Keyword performance
- Search Console data

#### TikTok
- TikTok Ads API integration
- Organic video publishing
- Performance analytics
- Audience insights

### Adding New Platforms

```bash
# 1. Create connector class
php artisan make:connector TikTok

# 2. Implement ConnectorInterface
# 3. Register in ConnectorFactory
# 4. Add credentials to .env
# 5. Test and deploy
```

---

## 🔒 Security

### Security Features

- **Row-Level Security (RLS)**: PostgreSQL-native data isolation per organization
- **Authentication**: Laravel Sanctum for API token management
- **Authorization**: Fine-grained RBAC with custom permissions
- **Context Management**: Automatic user/org context setting per request
- **Audit Logging**: Comprehensive audit trail for all critical operations
- **Input Validation**: Request validation for all API endpoints
- **CSRF Protection**: Built-in Laravel CSRF middleware
- **SQL Injection Prevention**: Eloquent ORM with prepared statements
- **XSS Protection**: Output escaping in Blade templates

### Security Middleware Stack

```php
// Executed on every API request
Route::middleware([
    'auth:sanctum',           // Verify authenticated user
    'validate.org.access',    // Verify user belongs to org
    'set.db.context',         // Set RLS context
    'throttle:api',           // Rate limiting
])->group(function () {
    // Protected routes
});
```

### Environment Variables

Critical security settings in `.env`:

```env
# System User (for automated tasks)
CMIS_SYSTEM_USER_ID=00000000-0000-0000-0000-000000000000

# Row Level Security
RLS_ENABLED=true
RLS_ENFORCE_CONSOLE=true

# API Keys (never commit to git)
META_APP_ID=your-app-id
META_APP_SECRET=your-app-secret
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
```

---

## 💻 Development

### Project Structure

```
cmis.marketing.limited/
├── app/
│   ├── Console/
│   │   ├── Commands/         # Artisan commands
│   │   └── Kernel.php        # Command scheduler
│   ├── Http/
│   │   ├── Controllers/      # API controllers
│   │   ├── Middleware/       # Request middleware
│   │   └── Requests/         # Form requests
│   ├── Models/               # Eloquent models
│   │   ├── Core/            # Core entities
│   │   ├── Campaigns/       # Campaign models
│   │   ├── Social/          # Social media models
│   │   └── AI/              # AI-related models
│   └── Services/
│       ├── Connectors/      # Platform connectors
│       └── Publishing/      # Content publishing
├── database/
│   ├── migrations/          # Laravel migrations
│   ├── seeders/            # Database seeders
│   └── schema.sql          # Full PostgreSQL schema
├── resources/
│   ├── views/              # Blade templates
│   ├── js/                 # Frontend JavaScript
│   └── css/                # Stylesheets
├── routes/
│   ├── api.php            # API routes
│   └── web.php            # Web routes
├── tests/
│   ├── Feature/           # Feature tests
│   ├── Unit/             # Unit tests
│   └── E2E/              # Playwright tests
└── docs/                 # Documentation
```

### Coding Standards

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse

# Run all quality checks
composer test
```

### Development Workflow

```bash
# 1. Create feature branch
git checkout -b feature/new-feature

# 2. Make changes and test
composer test
npm run test:e2e

# 3. Commit with descriptive messages
git commit -m "feat: add TikTok connector"

# 4. Push and create PR
git push -u origin feature/new-feature
```

---

## 🧪 Testing

### Test Suite

CMIS has comprehensive test coverage across multiple levels:

#### Backend Tests (PHPUnit)

```bash
# Run all backend tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/CampaignTest.php
```

#### End-to-End Tests (Playwright)

```bash
# Install Playwright browsers
npm run playwright:install

# Run E2E tests
npm run test:e2e              # Headless mode
npm run test:e2e:headed       # Headed mode
npm run test:e2e:ui          # Interactive UI mode
npm run test:e2e:debug       # Debug mode

# View test report
npm run test:e2e:report

# Generate new tests
npm run test:e2e:codegen
```

#### Database Tests

```bash
# Test database schema and migrations
php artisan migrate:fresh --seed --env=testing

# Audit database sync
php scripts/audit-database-sync.php
```

### Test Organization

```
tests/
├── Feature/
│   ├── Auth/              # Authentication tests
│   ├── Campaign/          # Campaign management
│   ├── Social/           # Social media features
│   └── API/              # API endpoint tests
├── Unit/
│   ├── Models/           # Model tests
│   ├── Services/         # Service layer tests
│   └── Helpers/          # Utility tests
└── E2E/
    ├── auth.spec.ts      # E2E auth flows
    ├── campaigns.spec.ts # E2E campaign flows
    └── publishing.spec.ts # E2E publishing flows
```

---

## 🚢 Deployment

### Production Requirements

- PHP 8.2+ with extensions: pgsql, redis, gd, intl, mbstring
- PostgreSQL 16+ with pgvector extension
- Redis 6+
- Supervisor for queue workers
- Nginx or Apache web server
- SSL certificate (Let's Encrypt recommended)

### Deployment Steps

```bash
# 1. Clone and install
git clone <repository> /var/www/cmis
cd /var/www/cmis
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 2. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with production settings

# 3. Setup database
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder

# 4. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Setup queue workers (Supervisor)
php artisan queue:restart

# 6. Setup scheduler (Crontab)
* * * * * cd /var/www/cmis && php artisan schedule:run >> /dev/null 2>&1
```

### Environment Configuration

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=cmis_production
DB_USERNAME=cmis_user
DB_PASSWORD=secure-password

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=your-redis-host

# Platform APIs (production credentials)
META_APP_ID=prod-app-id
META_APP_SECRET=prod-app-secret
GOOGLE_CLIENT_ID=prod-client-id
GOOGLE_CLIENT_SECRET=prod-client-secret
```

---

## 📚 Documentation

### Documentation Hub

All documentation has been organized into a comprehensive, easy-to-navigate structure. Start at the **[Documentation Hub](docs/README.md)** for complete access to all documentation.

### Quick Links by Role

#### For Executives
- **[Executive Summary](docs/reports/executive-summary.md)** - High-level project overview
- **[Master Action Plan](docs/reports/master-action-plan.md)** - Strategic planning and roadmap
- **[Gap Analysis](docs/reports/gap-analysis.md)** - Current status and gaps

#### For Developers
- **[Getting Started](QUICK_START.md)** - Quick start guide
- **[API Documentation](docs/api/)** - Complete REST API reference
- **[Architecture Guide](docs/architecture/)** - System architecture and design patterns
- **[Testing Guide](docs/development/testing.md)** - Testing documentation
- **[Repository Pattern Guide](app/Repositories/README.md)** - Data access layer

#### For DevOps Engineers
- **[Deployment Guide](docs/deployment/setup-guide.md)** - Production deployment
- **[Database Setup](docs/deployment/database-setup.md)** - Database configuration
- **[System Recovery](docs/deployment/system_recovery_plan.md)** - Recovery procedures
- **[Maintenance Checklist](docs/deployment/devops_maintenance_checklist.md)** - Regular maintenance

#### For Project Managers
- **[Project Status & Roadmap](docs/reports/project-status-and-plan.md)** - Current status and plans
- **[Implementation Roadmap](docs/reports/implementation-roadmap.md)** - Implementation timeline
- **[Reports & Analysis](docs/reports/)** - All project reports

### Documentation by Topic

#### Features
- **[AI & Semantic Features](docs/features/ai-semantic/)** - AI capabilities and semantic search
- **[Social Publishing](docs/features/social-publishing/)** - Social media publishing features
- **[Frontend](docs/features/frontend/)** - Frontend architecture and components
- **[Database](docs/features/database/)** - Database architecture and optimization
- **[Campaigns](docs/features/campaigns/)** - Campaign management

#### Integrations
- **[Platform Integrations](docs/integrations/)** - All platform integrations
- **[Instagram](docs/integrations/instagram/)** - Instagram integration guide
- **[Facebook](docs/integrations/facebook/)** - Facebook integration
- **[LinkedIn](docs/integrations/linkedin/)** - LinkedIn integration
- **[TikTok](docs/integrations/tiktok/)** - TikTok integration

#### Development
- **[Testing](docs/development/testing.md)** - Comprehensive testing guide
- **[E2E Testing](docs/development/e2e-testing.md)** - End-to-end testing
- **[Test Framework](docs/development/test-framework.md)** - Testing framework overview

### Key Concepts

#### Multi-Tenancy Model
Every organization's data is completely isolated using PostgreSQL Row-Level Security. Users can belong to multiple organizations with different roles in each.

See **[Architecture Documentation](docs/architecture/)** for detailed information.

#### Cognitive Framework
CMIS includes comprehensive marketing frameworks, strategies, and playbooks that guide campaign creation and optimization.

#### AI Integration
Vector embeddings enable semantic search across campaigns, assets, and content. AI models provide predictive insights and recommendations.

See **[AI Features Documentation](docs/features/ai-semantic/)** for complete details.

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### Code Contribution Process

1. **Fork the repository** and create your feature branch
2. **Follow coding standards**: Run `./vendor/bin/pint` before committing
3. **Write tests**: Maintain or improve test coverage
4. **Update documentation**: Document new features and API changes
5. **Submit a PR**: Provide clear description of changes

### Pull Request Guidelines

- Use descriptive commit messages following [Conventional Commits](https://conventionalcommits.org/)
- Ensure all tests pass (`composer test` and `npm run test:e2e`)
- Update relevant documentation
- Keep PRs focused on a single feature or fix
- Request review from maintainers

### Reporting Issues

- Use the [GitHub issue tracker](https://github.com/MarketingLimited/cmis.marketing.limited/issues)
- Include steps to reproduce for bugs
- Provide context and use cases for feature requests
- Check existing issues before creating new ones

---

## 🔧 Git Automation

This project includes AI-friendly Git automation that reads credentials from `.env`:

```bash
# Configure .env with your GitHub credentials
GIT_USERNAME=your-github-username
GIT_EMAIL=your-email@example.com
GIT_TOKEN=your-personal-access-token
GIT_REPOSITORY=https://github.com/MarketingLimited/cmis.marketing.limited.git

# Use the helper script for authenticated Git operations
scripts/git-ai.sh status
scripts/git-ai.sh pull
scripts/git-ai.sh commit -am "Your commit message"
scripts/git-ai.sh push

# See all options
scripts/git-ai.sh --help
```

**Note**: Keep `.env` out of version control. It's already in `.gitignore`.

---

## 📄 License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.

---

## 👥 Team & Support

### Maintainers

- **Marketing Limited Team** - [GitHub](https://github.com/MarketingLimited)

### Getting Help

- **Documentation**: Check the [docs/](docs/) directory
- **Issues**: [GitHub Issues](https://github.com/MarketingLimited/cmis.marketing.limited/issues)
- **Discussions**: [GitHub Discussions](https://github.com/MarketingLimited/cmis.marketing.limited/discussions)

### Credits

Built with Laravel, PostgreSQL, and the open-source community.

Special thanks to:
- [Laravel](https://laravel.com) - The PHP Framework For Web Artisans
- [PostgreSQL](https://postgresql.org) - The World's Most Advanced Open Source Database
- [Alpine.js](https://alpinejs.dev) - Your new, lightweight, JavaScript framework

---

<div align="center">

**[⬆ Back to Top](#cmis---نظام-إدارة-التسويق-الإدراكي)**

Made with ❤️ by Marketing Limited

</div>
