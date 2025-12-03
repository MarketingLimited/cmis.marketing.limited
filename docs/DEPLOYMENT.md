# CMIS Deployment Guide

Comprehensive guide for deploying CMIS (Cognitive Marketing Intelligence Suite) to staging and production environments.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Setup](#server-setup)
3. [Application Deployment](#application-deployment)
4. [Environment Configuration](#environment-configuration)
5. [Database Setup](#database-setup)
6. [SSL/HTTPS Configuration](#sslhttps-configuration)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Troubleshooting](#troubleshooting)
9. [Rollback Procedures](#rollback-procedures)

## Prerequisites

### Server Requirements

**Minimum Specifications:**
- **OS:** Ubuntu 20.04 LTS or later / Debian 11 or later
- **CPU:** 2 cores
- **RAM:** 4 GB
- **Disk:** 20 GB SSD
- **Network:** Static IP address

**Recommended Specifications:**
- **OS:** Ubuntu 22.04 LTS
- **CPU:** 4 cores
- **RAM:** 8 GB
- **Disk:** 50 GB SSD
- **Network:** Static IP + DNS configured

### Software Requirements

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git 2.30+
- curl, wget
- SSH access

### Domain & DNS

- Domain name configured
- DNS A record pointing to server IP
- SSL certificate (Let's Encrypt recommended)

## Server Setup

### 1. Automated Server Setup

Use the provided setup script for automated configuration:

```bash
# Download and run setup script
wget https://raw.githubusercontent.com/MarketingLimited/cmis/main/scripts/deployment/setup-server.sh
chmod +x setup-server.sh
sudo ./setup-server.sh
```

The script will:
- Update system packages
- Install Docker and Docker Compose
- Create application user and directories
- Configure firewall
- Set up log rotation
- Enable automatic security updates

### 2. Manual Server Setup

If you prefer manual setup:

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Create application user
sudo useradd -m -s /bin/bash -G docker cmis

# Create directories
sudo mkdir -p /var/www/cmis /var/backups/cmis /var/log/cmis
sudo chown -R cmis:cmis /var/www/cmis /var/backups/cmis /var/log/cmis
```

### 3. Security Hardening

```bash
# Configure firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Install fail2ban
sudo apt-get install -y fail2ban
sudo systemctl enable fail2ban

# Disable root SSH login
sudo sed -i 's/PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sudo systemctl restart sshd
```

## Application Deployment

### 1. Initial Deployment

```bash
# Switch to application user
su - cmis

# Clone repository
cd /var/www/cmis
git clone https://github.com/MarketingLimited/cmis.marketing.limited.git .

# Checkout appropriate branch
git checkout main  # For production
# OR
git checkout develop  # For staging

# Copy environment file
cp .env.docker.example .env

# Edit environment file
nano .env
# Configure:
# - APP_KEY (generate with: docker-compose run --rm app php artisan key:generate)
# - Database credentials
# - Redis configuration
# - Platform API keys
# - Email settings

# Deploy application
./scripts/deployment/deploy.sh production
# OR
./scripts/deployment/deploy.sh staging
```

### 2. Subsequent Deployments

```bash
# Standard deployment
cd /var/www/cmis
./scripts/deployment/deploy.sh production

# The script will:
# 1. Create database backup
# 2. Pull latest code from Git
# 3. Stop current services
# 4. Build new Docker images
# 5. Start updated services
# 6. Run migrations
# 7. Optimize caches
# 8. Run health checks
```

## Environment Configuration

### Required Environment Variables

Edit `.env` file and configure:

```bash
# Application
APP_NAME=CMIS
APP_ENV=production
APP_KEY=base64:XXXXXX  # Generate with: php artisan key:generate
APP_DEBUG=false
APP_URL=https://cmis.marketing

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=cmis
DB_USERNAME=cmis
DB_PASSWORD=STRONG_PASSWORD_HERE

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@cmis.marketing
MAIL_PASSWORD=MAIL_PASSWORD_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@cmis.marketing
MAIL_FROM_NAME="CMIS Platform"

# Platform API Keys
META_APP_ID=your_meta_app_id
META_APP_SECRET=your_meta_app_secret
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
# ... (add all platform credentials)

# AI & Embeddings
GEMINI_API_KEY=your_gemini_api_key
```

### Environment-Specific Settings

**Production:**
```bash
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

**Staging:**
```bash
APP_ENV=staging
APP_DEBUG=true
LOG_LEVEL=debug
```

## Database Setup

### Initial Migration

```bash
# Run migrations
docker-compose exec app php artisan migrate --force

# Seed initial data (optional, staging only)
docker-compose exec app php artisan db:seed
```

### PostgreSQL Extensions

The database initialization script automatically creates:
- pgvector extension
- uuid-ossp extension
- pg_trgm extension
- btree_gin extension

Verify extensions:

```bash
docker-compose exec postgres psql -U cmis -d cmis -c '\dx'
```

### Database Backups

```bash
# Manual backup
./scripts/deployment/backup.sh --database-only

# Automated backups (add to crontab)
crontab -e
# Add:
0 2 * * * /var/www/cmis/scripts/deployment/backup.sh --full
```

## SSL/HTTPS Configuration

### Using Let's Encrypt

```bash
# Install certbot
sudo apt-get install -y certbot

# Generate certificate
sudo certbot certonly --standalone -d cmis.marketing -d www.cmis.marketing

# Certificates will be in:
# /etc/letsencrypt/live/cmis.marketing/fullchain.pem
# /etc/letsencrypt/live/cmis.marketing/privkey.pem

# Copy certificates to Docker
sudo mkdir -p /var/www/cmis/docker/nginx/ssl
sudo cp /etc/letsencrypt/live/cmis.marketing/fullchain.pem /var/www/cmis/docker/nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/cmis.marketing/privkey.pem /var/www/cmis/docker/nginx/ssl/key.pem
sudo chown -R cmis:cmis /var/www/cmis/docker/nginx/ssl

# Update Nginx configuration
# Add SSL configuration to docker/nginx/default.conf

# Restart services
docker-compose restart nginx
```

### Auto-renewal

```bash
# Add renewal to crontab
sudo crontab -e
# Add:
0 0 * * * certbot renew --quiet --deploy-hook "cp /etc/letsencrypt/live/cmis.marketing/*.pem /var/www/cmis/docker/nginx/ssl/ && docker-compose -f /var/www/cmis/docker-compose.yml restart nginx"
```

## Monitoring & Maintenance

### Health Checks

```bash
# Run comprehensive health check
./scripts/deployment/health-check.sh --verbose

# Quick check
curl https://cmis.marketing/health
```

### Monitoring Services

```bash
# View service status
docker-compose ps

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres

# Monitor resource usage
docker stats
```

### Routine Maintenance Tasks

**Daily:**
- Check health endpoint
- Review error logs

**Weekly:**
- Full backup
- Review disk space
- Check for security updates
- Review performance metrics

**Monthly:**
- Update dependencies
- Review and optimize database
- Test disaster recovery procedures

### Automated Monitoring

Set up monitoring with tools like:
- **Uptime monitoring:** UptimeRobot, Pingdom
- **Application monitoring:** New Relic, DataDog
- **Log aggregation:** ELK Stack, Papertrail
- **Error tracking:** Sentry, Bugsnag

## Troubleshooting

### Application Not Responding

```bash
# Check service status
docker-compose ps

# Check logs for errors
docker-compose logs --tail=100

# Restart services
docker-compose restart

# Full restart
docker-compose down && docker-compose up -d
```

### Database Connection Issues

```bash
# Check PostgreSQL is running
docker-compose exec postgres psql -U cmis -d cmis -c "SELECT 1"

# View PostgreSQL logs
docker-compose logs postgres

# Restart database
docker-compose restart postgres
```

### High Memory Usage

```bash
# Check container memory usage
docker stats

# Clear Redis cache
docker-compose exec redis redis-cli FLUSHALL

# Restart services
docker-compose restart
```

### Disk Space Full

```bash
# Check disk usage
df -h

# Clean Docker
docker system prune -af --volumes

# Clean old logs
find /var/log/cmis -name "*.log" -mtime +30 -delete

# Clean old backups
find /var/backups/cmis -name "cmis-*" -mtime +30 -delete
```

## Rollback Procedures

### Automatic Rollback

```bash
# Rollback to previous version
./scripts/deployment/rollback.sh HEAD~1

# Rollback to specific commit
./scripts/deployment/rollback.sh abc123
```

### Manual Rollback

```bash
# Stop services
docker-compose down

# Revert code
git reset --hard <previous_commit>

# Rebuild
docker-compose build

# Start services
docker-compose up -d

# Restore database (if needed)
gunzip -c /var/backups/cmis/cmis-database-TIMESTAMP.sql.gz | \
  docker-compose exec -T postgres psql -U cmis -d cmis
```

### Database Rollback

```bash
# List backups
ls -lh /var/backups/cmis/

# Restore specific backup
gunzip -c /var/backups/cmis/cmis-database-20251120-120000.sql.gz | \
  docker-compose exec -T postgres psql -U cmis -d cmis

# Rollback migrations
docker-compose exec app php artisan migrate:rollback --step=1
```

## CI/CD Integration

For automated deployments, see:
- [CI/CD Guide](../.github/CI-CD-GUIDE.md)
- [GitHub Actions Workflows](../.github/workflows/)
- [GitLab CI Configuration](../.gitlab-ci.yml)

## Additional Resources

- [Docker Documentation](../docker/README.md)
- [Makefile Commands](../Makefile)
- [CLAUDE.md Project Guidelines](../CLAUDE.md)
- [Multi-Tenancy Patterns](.claude/knowledge/MULTI_TENANCY_PATTERNS.md)

## Support

For deployment issues:
1. Check this guide and troubleshooting section
2. Review deployment script logs
3. Check Docker and application logs
4. Consult team documentation

## Deployment Checklist

### Pre-Deployment

- [ ] Server meets minimum requirements
- [ ] Domain DNS configured
- [ ] SSL certificate obtained
- [ ] Environment variables configured
- [ ] Secrets/credentials prepared
- [ ] Backup of current data (if applicable)
- [ ] Maintenance window scheduled

### During Deployment

- [ ] Run deployment script
- [ ] Monitor logs during deployment
- [ ] Verify services started successfully
- [ ] Run database migrations
- [ ] Verify health checks pass
- [ ] Test critical functionality

### Post-Deployment

- [ ] Smoke test application
- [ ] Verify integrations working
- [ ] Check monitoring dashboards
- [ ] Review error logs
- [ ] Test rollback procedure (staging)
- [ ] Update documentation
- [ ] Notify team of successful deployment

---

**Last Updated:** 2025-11-20
**Version:** 1.0
