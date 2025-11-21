# Redis Setup Guide for CMIS
**Date:** November 21, 2025
**Priority:** HIGH - Performance Optimization
**Impact:** 10-50x faster cache operations

---

## Overview

Switching from database cache to Redis cache will dramatically improve performance:
- **Current:** Database cache (10-50x slower)
- **Target:** Redis cache (in-memory, highly optimized)
- **Expected Impact:**
  - Dashboard load time: 1000-1500ms → 100-200ms
  - Analytics queries: 500-800ms → 1-5ms
  - Session management: Much faster

---

## Prerequisites

### 1. Install Redis Server

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install redis-server -y

# macOS
brew install redis

# Verify installation
redis-server --version
```

### 2. Start Redis Service

```bash
# Ubuntu/Debian (systemd)
sudo systemctl start redis-server
sudo systemctl enable redis-server
sudo systemctl status redis-server

# macOS
brew services start redis

# Manual start (any OS)
redis-server

# Verify Redis is running
redis-cli ping
# Should output: PONG
```

### 3. Configure Redis (Optional but Recommended)

Edit Redis configuration file:
- Ubuntu: `/etc/redis/redis.conf`
- macOS: `/usr/local/etc/redis.conf`

**Recommended settings for CMIS:**

```conf
# Security
bind 127.0.0.1 ::1
protected-mode yes
requirepass YourStrongPasswordHere  # CHANGE THIS!

# Memory management
maxmemory 256mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000
appendonly yes

# Performance
tcp-keepalive 300
timeout 0
```

After editing, restart Redis:
```bash
sudo systemctl restart redis-server
```

---

## Laravel Configuration Changes

### Step 1: Update Environment Variables

Edit `.env` file:

```bash
# Cache Configuration
CACHE_STORE=redis
CACHE_PREFIX=cmis_cache_

# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue Configuration (recommended)
QUEUE_CONNECTION=redis

# Redis Connection
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null  # Set if you configured requirepass
REDIS_PORT=6379
REDIS_DB=0           # Default database
REDIS_CACHE_DB=1     # Separate database for cache
```

### Step 2: Verify Redis Configuration

Check `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),  // or 'predis'

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],

    'queue' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_QUEUE_DB', '2'),
    ],
],
```

### Step 3: Install PHP Redis Extension (If Needed)

```bash
# Check if phpredis is installed
php -m | grep redis

# If not installed:
# Ubuntu/Debian
sudo apt-get install php-redis
sudo systemctl restart php8.2-fpm  # Adjust PHP version

# macOS
pecl install redis
```

Alternatively, use Predis (pure PHP client):

```bash
composer require predis/predis
```

Then update `.env`:
```bash
REDIS_CLIENT=predis
```

### Step 4: Clear Old Cache and Apply Changes

```bash
# Clear database cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Rebuild config with Redis
php artisan config:cache

# Verify cache is using Redis
php artisan tinker
>>> Cache::getStore()->getRedis()->ping()
# Should return: true or "+PONG"
```

---

## Testing Redis Integration

### Test 1: Cache Operations

```bash
php artisan tinker
```

```php
// Test cache write
Cache::put('test_key', 'redis_works', 60);

// Test cache read
Cache::get('test_key');
// Should output: "redis_works"

// Test cache increment
Cache::put('counter', 0);
Cache::increment('counter');
Cache::get('counter');
// Should output: 1

// Test cache tags (Redis only feature)
Cache::tags(['users', 'posts'])->put('key', 'value', 60);
Cache::tags(['users'])->flush();

// Verify cache driver
echo config('cache.default');
// Should output: redis

// Get cache statistics
Cache::getStore()->getRedis()->info('stats');
```

### Test 2: Redis CLI

```bash
# Connect to Redis
redis-cli

# Check keys (should see Laravel cache keys)
KEYS *

# Check specific cache key
GET laravel_cache_:test_key

# Check cache database (should be DB 1)
SELECT 1
KEYS *

# Monitor Redis in real-time
MONITOR
# (In another terminal, trigger some cache operations)
```

### Test 3: Performance Comparison

```bash
# Before (Database cache) - Run this first with CACHE_STORE=database
php artisan tinker
```

```php
// Benchmark database cache
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    Cache::put("key_$i", "value_$i", 60);
}
$end = microtime(true);
echo "Database cache: " . ($end - $start) . " seconds\n";

// Clear cache
Cache::flush();
```

```bash
# After (Redis cache) - Change CACHE_STORE=redis and php artisan config:cache
php artisan tinker
```

```php
// Benchmark Redis cache
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    Cache::put("key_$i", "value_$i", 60);
}
$end = microtime(true);
echo "Redis cache: " . ($end - $start) . " seconds\n";

// You should see 10-50x improvement
```

---

## Production Deployment

### Redis Persistence Configuration

For production, ensure Redis persistence is properly configured:

```conf
# RDB Snapshots
save 900 1      # After 900 sec if at least 1 key changed
save 300 10     # After 300 sec if at least 10 keys changed
save 60 10000   # After 60 sec if at least 10000 keys changed

# AOF (Append Only File) - More durable
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec  # Balance between performance and durability
```

### Redis Security

```conf
# Bind to localhost only (if Redis is on same server as Laravel)
bind 127.0.0.1 ::1

# Require password
requirepass YourVeryStrongPassword123!

# Disable dangerous commands
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command KEYS ""
rename-command CONFIG "CONFIG_abc123xyz"  # Obfuscate instead of disable
```

Update `.env`:
```bash
REDIS_PASSWORD=YourVeryStrongPassword123!
```

### Redis Monitoring

Set up monitoring to track Redis performance:

```bash
# Install Redis monitoring tools
pip install redis-cli-monitor

# Monitor in real-time
redis-cli --stat

# Check memory usage
redis-cli INFO memory

# Check connected clients
redis-cli CLIENT LIST
```

### Laravel Horizon (Optional but Recommended)

If using Redis for queues, install Laravel Horizon for monitoring:

```bash
composer require laravel/horizon

php artisan horizon:install
php artisan migrate

# Start Horizon
php artisan horizon
```

Access dashboard at: `http://your-domain/horizon`

---

## Troubleshooting

### Issue 1: Connection Refused

```bash
# Check if Redis is running
sudo systemctl status redis-server

# Check Redis logs
tail -f /var/log/redis/redis-server.log

# Test connection
redis-cli ping
```

### Issue 2: Authentication Failed

```bash
# Check if password is set
redis-cli CONFIG GET requirepass

# Connect with password
redis-cli -a YourPassword

# Update .env with correct password
REDIS_PASSWORD=YourPassword
```

### Issue 3: Out of Memory

```bash
# Check current memory usage
redis-cli INFO memory

# Increase maxmemory in redis.conf
maxmemory 512mb

# Restart Redis
sudo systemctl restart redis-server
```

### Issue 4: Slow Cache Operations

```bash
# Check Redis slowlog
redis-cli SLOWLOG GET 10

# Check for blocking operations
redis-cli INFO stats

# Consider increasing Redis resources
```

### Issue 5: Laravel Can't Connect

```php
// Test in tinker
php artisan tinker

try {
    Cache::getStore()->getRedis()->ping();
    echo "✓ Connected!\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Check configuration
echo "Cache driver: " . config('cache.default') . "\n";
echo "Redis host: " . config('database.redis.default.host') . "\n";
echo "Redis port: " . config('database.redis.default.port') . "\n";
```

---

## Rollback Plan

If issues occur after switching to Redis:

### Quick Rollback

```bash
# 1. Switch back to database cache
# Edit .env
CACHE_STORE=database

# 2. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan config:cache

# 3. Verify
php artisan tinker
>>> echo config('cache.default');
# Should output: database
```

### Complete Rollback

```bash
# If Redis is causing issues, stop it
sudo systemctl stop redis-server

# Switch back to database cache
CACHE_STORE=database
php artisan config:cache

# Application should work normally with database cache
```

---

## Performance Benchmarks

Expected improvements after switching to Redis:

| Operation | Database Cache | Redis Cache | Improvement |
|-----------|----------------|-------------|-------------|
| **Cache Put** | 5-10ms | 0.1-0.5ms | 10-100x faster |
| **Cache Get** | 3-5ms | 0.05-0.2ms | 15-100x faster |
| **Session Read** | 10-20ms | 0.5-1ms | 20-40x faster |
| **Analytics Query** | 500-800ms | 1-5ms | 100-800x faster |
| **Dashboard Load** | 1000-1500ms | 100-200ms | 5-15x faster |

---

## Next Steps

1. **Install Redis** on development and staging servers
2. **Configure Redis** with recommended settings
3. **Update `.env`** files with Redis configuration
4. **Test thoroughly** using the test scripts above
5. **Monitor performance** before and after
6. **Deploy to production** after successful staging tests
7. **Set up monitoring** for Redis performance
8. **Document any custom configurations** for your team

---

## Additional Resources

- **Redis Documentation:** https://redis.io/documentation
- **Laravel Redis Documentation:** https://laravel.com/docs/redis
- **Laravel Caching:** https://laravel.com/docs/cache
- **Laravel Horizon:** https://laravel.com/docs/horizon
- **Redis Best Practices:** https://redis.io/topics/best-practices

---

**Status:** ⚠️ PENDING REDIS INSTALLATION

Once Redis is installed and running, update `.env` and run:
```bash
php artisan config:cache
php artisan cache:clear
```

Then verify with:
```bash
php artisan tinker
>>> Cache::put('test', 'works', 60);
>>> Cache::get('test');
```

**Expected Result:** 10-50x performance improvement in cache operations!
