# CMIS Docker Configuration

This directory contains Docker configuration files for running CMIS in containerized environments.

## Architecture

The CMIS Docker setup consists of the following services:

- **app** - Laravel application (PHP-FPM 8.2)
- **nginx** - Web server (Nginx Alpine)
- **postgres** - PostgreSQL 16 with pgvector extension
- **redis** - Redis 7 for caching and queues
- **queue** - Laravel queue worker
- **scheduler** - Laravel task scheduler

## Quick Start

### Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- At least 4GB available RAM
- At least 10GB available disk space

### Initial Setup

1. **Copy environment file**
   ```bash
   cp .env.docker.example .env
   ```

2. **Generate application key**
   ```bash
   docker-compose run --rm app php artisan key:generate
   ```

3. **Update environment variables**
   Edit `.env` and set:
   - `DB_PASSWORD` - Strong database password
   - `APP_KEY` - Generated in step 2
   - Platform API credentials (if available)

4. **Build and start services**
   ```bash
   docker-compose up -d --build
   ```

5. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate --seed
   ```

6. **Access the application**
   - Open http://localhost in your browser
   - Default credentials are set in the seeder

## Directory Structure

```
docker/
├── nginx/
│   ├── default.conf      # Nginx server configuration
│   └── ssl/              # SSL certificates (optional)
├── php/
│   ├── php.ini           # PHP configuration
│   └── www.conf          # PHP-FPM pool configuration
├── postgres/
│   └── init/
│       └── 01-init.sql   # Database initialization script
├── redis/
│   └── redis.conf        # Redis configuration
├── supervisor/
│   └── supervisord.conf  # Supervisor configuration
└── README.md             # This file
```

## Service Details

### Application Service (app)

- **Base Image:** `php:8.2-fpm-alpine`
- **Port:** 9000 (internal)
- **Extensions:** pdo_pgsql, mbstring, bcmath, intl, zip, opcache, redis
- **Health Check:** `/health` endpoint

### Nginx Service

- **Base Image:** `nginx:alpine`
- **Ports:** 80 (HTTP), 443 (HTTPS)
- **Configuration:** `/etc/nginx/conf.d/default.conf`
- **Logs:** `/var/log/nginx/`

### PostgreSQL Service

- **Base Image:** `pgvector/pgvector:pg16`
- **Port:** 5432
- **Extensions:** vector, uuid-ossp, pg_trgm, btree_gin
- **Data:** Persistent volume `postgres-data`
- **Schemas:** 12 schemas for multi-tenancy

### Redis Service

- **Base Image:** `redis:7-alpine`
- **Port:** 6379
- **Persistence:** AOF + RDB
- **Max Memory:** 256MB (configurable)
- **Data:** Persistent volume `redis-data`

### Queue Worker Service

- **Command:** `php artisan queue:work`
- **Restart:** Always (handles failures gracefully)
- **Max Time:** 3600 seconds per job
- **Tries:** 3 attempts per job

### Scheduler Service

- **Command:** Runs `php artisan schedule:run` every 60 seconds
- **Restart:** Always
- **Purpose:** Background task automation

## Common Commands

### Start Services
```bash
docker-compose up -d
```

### Stop Services
```bash
docker-compose down
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres
```

### Execute Commands in Container
```bash
# Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Composer
docker-compose exec app composer install
docker-compose exec app composer update

# Database access
docker-compose exec postgres psql -U cmis -d cmis
```

### Rebuild Services
```bash
# Rebuild all services
docker-compose up -d --build

# Rebuild specific service
docker-compose up -d --build app
```

### Clear Everything and Restart
```bash
docker-compose down -v  # Remove volumes (WARNING: deletes data)
docker-compose up -d --build
docker-compose exec app php artisan migrate:fresh --seed
```

## Performance Tuning

### PHP-FPM Scaling

Edit `docker/php/www.conf`:
```ini
pm.max_children = 50        # Maximum workers
pm.start_servers = 10       # Initial workers
pm.min_spare_servers = 5    # Minimum idle workers
pm.max_spare_servers = 20   # Maximum idle workers
```

### OPcache Configuration

Edit `docker/php/php.ini`:
```ini
opcache.memory_consumption = 256
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0  # Disable in production
```

### PostgreSQL Tuning

For production, customize PostgreSQL settings:
```yaml
# docker-compose.yml
postgres:
  command: postgres -c shared_buffers=256MB -c max_connections=200
```

### Redis Memory

Edit `docker/redis/redis.conf`:
```
maxmemory 512mb  # Increase for larger cache
maxmemory-policy allkeys-lru
```

## Security

### Production Checklist

- [ ] Set strong `DB_PASSWORD`
- [ ] Set `APP_DEBUG=false`
- [ ] Enable HTTPS with valid SSL certificates
- [ ] Configure firewall rules (only expose ports 80, 443)
- [ ] Use secrets management for API keys
- [ ] Enable Redis authentication
- [ ] Implement rate limiting in Nginx
- [ ] Regular security updates (`docker-compose pull`)

### SSL/HTTPS Setup

1. Place SSL certificates in `docker/nginx/ssl/`:
   - `cert.pem` - SSL certificate
   - `key.pem` - Private key

2. Update `docker/nginx/default.conf` to include:
   ```nginx
   server {
       listen 443 ssl http2;
       ssl_certificate /etc/nginx/ssl/cert.pem;
       ssl_certificate_key /etc/nginx/ssl/key.pem;
       # ... rest of configuration
   }
   ```

3. Rebuild nginx:
   ```bash
   docker-compose up -d --build nginx
   ```

## Monitoring

### Health Checks

All services have health checks configured:

```bash
# Check service health
docker-compose ps

# View specific health status
docker inspect cmis-app --format='{{.State.Health.Status}}'
```

### Resource Usage

```bash
# Container stats
docker stats

# Service-specific stats
docker stats cmis-app cmis-postgres cmis-redis
```

### Database Monitoring

```bash
# Active connections
docker-compose exec postgres psql -U cmis -d cmis -c \
  "SELECT count(*) FROM pg_stat_activity;"

# Database size
docker-compose exec postgres psql -U cmis -d cmis -c \
  "SELECT pg_size_pretty(pg_database_size('cmis'));"
```

## Backup & Restore

### Database Backup

```bash
# Create backup
docker-compose exec postgres pg_dump -U cmis -d cmis > backup-$(date +%Y%m%d).sql

# Restore backup
docker-compose exec -T postgres psql -U cmis -d cmis < backup-20251120.sql
```

### Volume Backup

```bash
# Backup postgres data
docker run --rm -v cmis_postgres-data:/data -v $(pwd):/backup \
  alpine tar czf /backup/postgres-backup.tar.gz /data

# Restore postgres data
docker run --rm -v cmis_postgres-data:/data -v $(pwd):/backup \
  alpine tar xzf /backup/postgres-backup.tar.gz -C /
```

## Troubleshooting

### App Container Won't Start

Check logs:
```bash
docker-compose logs -f app
```

Common issues:
- Missing `.env` file
- Invalid `APP_KEY`
- Database connection issues

### Database Connection Errors

1. Verify postgres is running:
   ```bash
   docker-compose ps postgres
   ```

2. Check database credentials:
   ```bash
   docker-compose exec postgres psql -U cmis -d cmis
   ```

3. Verify network connectivity:
   ```bash
   docker-compose exec app ping postgres
   ```

### Permission Issues

Reset permissions:
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 755 storage bootstrap/cache
```

### Queue Worker Not Processing Jobs

Check queue worker logs:
```bash
docker-compose logs -f queue
```

Restart queue worker:
```bash
docker-compose restart queue
```

### Out of Memory

Increase Docker memory limit:
- Docker Desktop: Settings > Resources > Memory (increase to 4GB+)
- Docker Engine: Edit `/etc/docker/daemon.json`

## Development vs Production

### Development Mode

```bash
# Use development environment
cp .env.example .env
sed -i 's/APP_ENV=production/APP_ENV=local/' .env
sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env

# Enable code hot-reloading
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up
```

### Production Mode

```bash
# Use production environment
cp .env.docker.example .env
# Edit .env with production values

# Build with optimizations
docker-compose up -d --build

# Optimize Laravel
docker-compose exec app php artisan optimize
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## CI/CD Integration

### GitHub Actions Example

```yaml
- name: Build Docker Image
  run: |
    docker-compose build --build-arg BUILD_DATE=$(date -u +'%Y-%m-%dT%H:%M:%SZ') \
      --build-arg VCS_REF=${{ github.sha }}

- name: Run Tests
  run: docker-compose run --rm app php artisan test

- name: Push to Registry
  run: |
    docker tag cmis-app:latest registry.example.com/cmis-app:${{ github.sha }}
    docker push registry.example.com/cmis-app:${{ github.sha }}
```

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Deployment Guide](https://laravel.com/docs/deployment)
- [PostgreSQL Performance Tuning](https://wiki.postgresql.org/wiki/Performance_Optimization)

## Support

For issues related to:
- **Docker setup:** Check this README and troubleshooting section
- **Application code:** See main project documentation
- **Platform integrations:** See `.claude/knowledge/` documentation
