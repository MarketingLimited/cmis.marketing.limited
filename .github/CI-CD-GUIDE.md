# CMIS CI/CD Pipeline Guide

This guide explains the Continuous Integration and Continuous Deployment (CI/CD) pipelines configured for the CMIS project.

## Overview

CMIS uses automated CI/CD pipelines to ensure code quality, run tests, build Docker images, and deploy to staging and production environments.

### Supported Platforms

- **GitHub Actions** - Primary CI/CD platform
- **GitLab CI** - Alternative platform support

## GitHub Actions Workflows

Located in `.github/workflows/`, we have the following workflows:

### 1. Main CI/CD Pipeline (`ci-cd.yml`)

**Triggers:**
- Push to `main`, `develop`, or `claude/**` branches
- Pull requests to `main` or `develop`
- Manual trigger via `workflow_dispatch`

**Jobs:**

#### Code Quality Checks
- PHP CodeSniffer (PSR-12 standards)
- PHPStan static analysis
- Runs on all branches

#### Test Suite
- **Services:** PostgreSQL 16 with pgvector, Redis 7
- **PHP Version:** 8.2
- **Test Suites:**
  - Unit tests
  - Feature tests
  - Integration tests
- **Coverage:** Minimum 30% required
- **Upload:** Coverage reports to Codecov

#### Docker Build
- Builds Docker image with multi-stage Dockerfile
- Pushes to GitHub Container Registry (ghcr.io)
- **Tags:**
  - Branch name
  - Commit SHA
  - Semantic versioning (if tagged)
- **Runs:** Only on push (not PRs)

#### Deploy to Staging
- **Trigger:** Push to `develop` branch
- **Target:** https://staging.cmis.marketing
- **Steps:**
  1. SSH into staging server
  2. Pull latest Docker images
  3. Run database migrations
  4. Optimize Laravel caches
  5. Restart queue workers
- **Smoke Test:** Curl health endpoint

#### Deploy to Production
- **Trigger:** Push to `main` branch
- **Target:** https://cmis.marketing
- **Steps:** Same as staging
- **Rollback:** Automatic on failure

### 2. Dependency Updates (`dependency-updates.yml`)

**Triggers:**
- Weekly schedule (Mondays at midnight)
- Manual trigger

**Actions:**
- Updates Composer dependencies
- Updates NPM dependencies
- Creates Pull Request if changes detected

### 3. Security Scanning (`security-scan.yml`)

**Triggers:**
- Push to `main` or `develop`
- Pull requests
- Daily schedule (2 AM)
- Manual trigger

**Scans:**
- **Dependency Vulnerabilities:** Composer audit, NPM audit
- **SAST:** Trivy filesystem scan
- **Docker Image:** Trivy container scan
- **Secrets:** TruffleHog secrets detection

## GitLab CI Pipeline

Located in `.gitlab-ci.yml`, this pipeline provides the same functionality for GitLab-hosted projects.

### Stages

1. **Prepare:** Install Composer & NPM dependencies
2. **Test:** Code quality, unit, feature, integration tests
3. **Build:** Docker image build and push
4. **Deploy:** Staging and production deployment

### Key Features

- **Caching:** Vendor and node_modules cached between runs
- **Artifacts:** Test results and coverage reports
- **Services:** PostgreSQL and Redis for testing
- **Coverage:** Cobertura format for GitLab integration

## Required Secrets

### GitHub Actions Secrets

Configure these in: `Settings > Secrets and variables > Actions`

| Secret Name | Description | Used In |
|------------|-------------|---------|
| `STAGING_SSH_KEY` | SSH private key for staging server | Staging deployment |
| `STAGING_HOST` | Staging server hostname/IP | Staging deployment |
| `STAGING_USER` | SSH username for staging | Staging deployment |
| `PRODUCTION_SSH_KEY` | SSH private key for production | Production deployment |
| `PRODUCTION_HOST` | Production server hostname/IP | Production deployment |
| `PRODUCTION_USER` | SSH username for production | Production deployment |
| `GITHUB_TOKEN` | Automatically provided | Docker registry |

### GitLab CI Variables

Configure these in: `Settings > CI/CD > Variables`

| Variable Name | Description | Protected | Masked |
|--------------|-------------|-----------|--------|
| `STAGING_SSH_KEY` | SSH private key for staging | Yes | Yes |
| `STAGING_HOST` | Staging server hostname/IP | Yes | No |
| `STAGING_USER` | SSH username for staging | Yes | No |
| `PRODUCTION_SSH_KEY` | SSH private key for production | Yes | Yes |
| `PRODUCTION_HOST` | Production server hostname/IP | Yes | No |
| `PRODUCTION_USER` | SSH username for production | Yes | No |

## Server Setup

### Prerequisites

Your deployment servers (staging/production) must have:

1. **Docker & Docker Compose** installed
2. **SSH access** configured
3. **Project repository** cloned at `/var/www/cmis`
4. **Environment file** (`.env`) configured
5. **Docker Compose** file ready

### SSH Key Setup

Generate SSH keys for CI/CD:

```bash
# Generate key pair (no passphrase)
ssh-keygen -t ed25519 -C "ci-cd@cmis" -f cmis-deploy-key -N ""

# Add public key to server
ssh-copy-id -i cmis-deploy-key.pub user@server

# Add private key to GitHub/GitLab secrets
cat cmis-deploy-key | base64 -w 0
```

### Server Directory Structure

```
/var/www/cmis/
├── .env                    # Environment configuration
├── docker-compose.yml      # Production compose file
├── storage/                # Persistent storage
└── ...                     # Application files
```

## Running Workflows Manually

### GitHub Actions

1. Navigate to **Actions** tab
2. Select workflow from left sidebar
3. Click **Run workflow** button
4. Choose branch and click **Run workflow**

### GitLab CI

1. Navigate to **CI/CD > Pipelines**
2. Click **Run pipeline** button
3. Select branch/tag
4. Click **Run pipeline**

## Testing Locally

### Run Tests

```bash
# Using Docker
make test

# Using PHPUnit directly
vendor/bin/phpunit

# With coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Build Docker Image

```bash
# Using Makefile
make build

# Using Docker directly
docker build -t cmis-app:local .
```

### Simulate CI Environment

```bash
# Install dependencies
composer install --prefer-dist --no-progress --no-interaction
npm ci

# Run code quality
vendor/bin/phpcs --standard=PSR12 app/
vendor/bin/phpstan analyse

# Run tests
php artisan test
```

## Deployment Process

### Staging Deployment

1. Merge code to `develop` branch
2. CI pipeline runs automatically:
   - Code quality checks
   - Full test suite
   - Docker image build
   - Deploy to staging
3. Smoke tests verify deployment
4. Test manually on staging environment

### Production Deployment

1. Merge code to `main` branch (via PR from `develop`)
2. CI pipeline runs automatically:
   - All staging steps
   - Deploy to production
3. Smoke tests verify deployment
4. Monitor logs and metrics
5. Automatic rollback on failure

### Manual Deployment

If automated deployment fails:

```bash
# SSH into server
ssh user@server

# Navigate to project
cd /var/www/cmis

# Pull latest changes
git pull origin main

# Update containers
docker-compose pull
docker-compose up -d --build

# Run migrations
docker-compose exec app php artisan migrate --force

# Optimize
docker-compose exec app php artisan optimize

# Restart queue
docker-compose exec app php artisan queue:restart
```

## Monitoring & Debugging

### View Workflow Logs

**GitHub Actions:**
1. Go to **Actions** tab
2. Click on workflow run
3. Click on job name
4. Expand step to view logs

**GitLab CI:**
1. Go to **CI/CD > Pipelines**
2. Click on pipeline ID
3. Click on job name

### Common Issues

#### Tests Failing

- Check database connection settings
- Verify PostgreSQL extensions are installed
- Ensure Redis is running
- Check `.env.example` is up to date

#### Docker Build Failures

- Verify Dockerfile syntax
- Check all COPY paths exist
- Ensure dependencies are cached
- Review build logs for specific errors

#### Deployment Failures

- Verify SSH keys are correct
- Check server has enough resources
- Ensure Docker daemon is running on server
- Verify `.env` file exists on server

#### Coverage Too Low

- Add more tests for uncovered code
- Check coverage threshold in workflow
- Review coverage report for gaps

## Best Practices

### Branch Strategy

- **`main`** - Production-ready code
- **`develop`** - Integration branch for features
- **`claude/**`** - Claude Code feature branches
- **Feature branches** - Individual features

### Pull Request Workflow

1. Create feature branch from `develop`
2. Make changes and commit
3. Push branch and create PR
4. CI pipeline runs automatically
5. Review code and address feedback
6. Merge to `develop` when approved
7. Test on staging
8. Create PR from `develop` to `main` for production

### Testing

- Write tests for all new features
- Maintain minimum 30% coverage (aim for 70%+)
- Run tests locally before pushing
- Fix failing tests immediately

### Security

- Never commit secrets or credentials
- Use environment variables for sensitive data
- Rotate SSH keys regularly
- Review security scan results
- Update dependencies weekly

## Metrics & Reporting

### Coverage Reports

- **GitHub:** Codecov integration
- **GitLab:** Built-in coverage visualization
- **Local:** HTML coverage reports in `coverage/`

### Test Results

- JUnit XML format for CI platforms
- Displayed in PR comments (GitHub)
- Integrated in merge request view (GitLab)

### Docker Images

- Tagged by branch, commit SHA, semver
- Stored in container registries
- Automatic cleanup of old images

## Troubleshooting

### Pipeline Stuck

1. Check runner availability
2. Verify service containers are healthy
3. Review job logs for errors
4. Cancel and re-run if needed

### Deployment Not Triggering

1. Check branch protection rules
2. Verify workflow conditions (`if:` clauses)
3. Ensure secrets are configured
4. Review workflow syntax

### Test Database Issues

1. Verify PostgreSQL service is running
2. Check extension installation
3. Ensure migrations run successfully
4. Review database logs

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [GitLab CI/CD Documentation](https://docs.gitlab.com/ee/ci/)
- [Docker Documentation](https://docs.docker.com/)
- [Laravel Testing](https://laravel.com/docs/testing)
- [CMIS Docker Guide](../docker/README.md)

## Support

For CI/CD issues:
1. Check this guide
2. Review workflow/pipeline logs
3. Check GitHub Actions status page
4. Contact DevOps team
