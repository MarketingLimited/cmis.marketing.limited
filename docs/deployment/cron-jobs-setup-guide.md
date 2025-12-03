# CMIS Platform - Cron Jobs Configuration Guide

**Last Updated:** 2025-11-30
**Platform:** CMIS - Cognitive Marketing Intelligence Suite
**Environment:** Production/Staging

---

## 🎯 Overview

CMIS requires cron jobs to run scheduled tasks like:
- Publishing scheduled social media posts
- Syncing platform metrics (Meta, Google, TikTok, etc.)
- Processing AI embeddings
- Refreshing OAuth tokens
- Database backups
- Sending reports

---

## 📋 Current Cron Configuration

### View Current Crontab

```bash
# View current user's cron jobs
crontab -l

# View with line numbers
crontab -l | cat -n
```

### Current CMIS Cron Job

```cron
* * * * * /home/cmis-test/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Breakdown:**
- `* * * * *` - Run every minute
- `/home/cmis-test/bin/php` - **Absolute path** to PHP binary (REQUIRED)
- `artisan schedule:run` - Laravel scheduler command
- `>> /dev/null 2>&1` - Silence output (optional)

**⚠️ CRITICAL:** Always use **absolute paths** in cron jobs!

---

## 🔧 How to Configure Cron Jobs

### Method 1: Edit Crontab Directly

```bash
# Open crontab editor
crontab -e

# This opens your default editor (usually vim or nano)
# Add your cron jobs, save and exit
```

### Method 2: Replace Entire Crontab

```bash
# Create a cron file
cat > /tmp/cmis-crontab << 'EOF'
# CMIS Platform - Laravel Scheduler (runs every minute)
* * * * * /home/cmis-test/bin/php /home/cmis-test/public_html/artisan schedule:run >> /dev/null 2>&1

# Optional: Laravel queue worker monitor (restart if down)
* * * * * /home/cmis-test/bin/php /home/cmis-test/public_html/artisan queue:restart --quiet >> /dev/null 2>&1
EOF

# Install the cron file
crontab /tmp/cmis-crontab

# Verify
crontab -l
```

### Method 3: Append to Existing Crontab

```bash
# Backup current crontab
crontab -l > /tmp/crontab-backup-$(date +%Y%m%d-%H%M%S)

# Append new job
(crontab -l 2>/dev/null; echo "* * * * * /home/cmis-test/bin/php artisan schedule:run >> /dev/null 2>&1") | crontab -
```

---

## 📅 Cron Schedule Syntax

```
┌───────────── minute (0 - 59)
│ ┌───────────── hour (0 - 23)
│ │ ┌───────────── day of month (1 - 31)
│ │ │ ┌───────────── month (1 - 12)
│ │ │ │ ┌───────────── day of week (0 - 6) (Sunday=0)
│ │ │ │ │
│ │ │ │ │
* * * * * command to execute
```

### Common Examples

| Schedule | Cron Expression | Description |
|----------|----------------|-------------|
| Every minute | `* * * * *` | Run every 60 seconds |
| Every 5 minutes | `*/5 * * * *` | Run at :00, :05, :10, etc. |
| Every hour | `0 * * * *` | Run at the start of every hour |
| Every day at 2 AM | `0 2 * * *` | Daily backup time |
| Every Monday at 9 AM | `0 9 * * 1` | Weekly reports |
| First day of month | `0 0 1 * *` | Monthly cleanup |

---

## 🚀 Laravel Scheduler Tasks

### What the Scheduler Runs

The Laravel scheduler (`schedule:run`) executes tasks defined in `app/Console/Kernel.php`:

```php
// Current CMIS scheduled tasks:

// 1. Publish scheduled social posts - EVERY MINUTE
$schedule->command('social:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// 2. Process scheduled posts queue - EVERY MINUTE
$schedule->job(new ProcessScheduledPostsJob())
    ->everyMinute();

// 3. Refresh expired OAuth tokens - HOURLY
$schedule->job(new RefreshExpiredTokensJob())
    ->hourly();

// 4. Sync platform metrics - HOURLY
$schedule->job(new DispatchPlatformSyncs('metrics'))
    ->hourly();

// 5. Sync campaigns - EVERY 4 HOURS
$schedule->job(new DispatchPlatformSyncs('campaigns'))
    ->everyFourHours();

// 6. Full platform sync - DAILY at 2 AM
$schedule->job(new DispatchPlatformSyncs('all'))
    ->dailyAt('02:00');

// 7. Database backup - DAILY at 2 AM
$schedule->command('db:backup --no-interaction')
    ->dailyAt('02:00');

// 8. Process embeddings - EVERY 15 MINUTES
$schedule->command('cmis:process-embeddings --batch=20')
    ->everyFifteenMinutes();

// 9. Platform analytics sync - EVERY 30 MINUTES
$schedule->job(new SyncPlatformAnalyticsJob())
    ->everyThirtyMinutes();

// 10. Token expiry check - DAILY at 9 AM
$schedule->command('integrations:check-expiring-tokens --days=7')
    ->dailyAt('09:00');
```

### View All Scheduled Tasks

```bash
# List all scheduled tasks with next run time
php artisan schedule:list

# Output example:
#   *    *   * * *  php artisan social:publish-scheduled  Next Due: 30 seconds
#   0    *   * * *  RefreshExpiredTokensJob              Next Due: 45 minutes
```

---

## 🛠️ Advanced Cron Configurations

### 1. With Logging

```cron
# Log scheduler output to file
* * * * * /home/cmis-test/bin/php artisan schedule:run >> /home/cmis-test/public_html/storage/logs/scheduler.log 2>&1

# Rotate logs (prevents huge files)
0 0 * * * find /home/cmis-test/public_html/storage/logs -name "scheduler.log" -mtime +7 -delete
```

### 2. Queue Worker Auto-Restart

```cron
# Restart queue worker every hour (prevents memory leaks)
0 * * * * /home/cmis-test/bin/php artisan queue:restart --quiet >> /dev/null 2>&1

# Monitor and start queue worker if not running
* * * * * pgrep -f "queue:work" > /dev/null || /home/cmis-test/bin/php artisan queue:work --daemon --queue=social-publishing,default --sleep=3 --tries=3 >> /dev/null 2>&1 &
```

### 3. Multi-Environment Setup

```cron
# Production
* * * * * cd /var/www/cmis-production && /usr/bin/php artisan schedule:run --env=production >> /dev/null 2>&1

# Staging
* * * * * cd /var/www/cmis-staging && /usr/bin/php artisan schedule:run --env=staging >> /dev/null 2>&1
```

### 4. Email Notifications on Failure

```cron
MAILTO=admin@cmis.test

# Scheduler with email on error
* * * * * /home/cmis-test/bin/php artisan schedule:run || echo "CMIS Scheduler Failed at $(date)" | mail -s "CMIS Alert" admin@cmis.test
```

---

## 🔍 Finding Absolute Paths

### PHP Binary Path

```bash
# Method 1: which command
which php
# Output: /home/cmis-test/bin/php

# Method 2: whereis command
whereis php
# Output: php: /home/cmis-test/bin/php /usr/bin/php8.3

# Method 3: Check PHP version and path
php --version
which php
```

### Project Path

```bash
# Get current directory
pwd
# Output: /home/cmis-test/public_html

# Get artisan path
realpath artisan
# Output: /home/cmis-test/public_html/artisan
```

---

## ✅ Verification & Testing

### 1. Check Cron is Running

```bash
# Check cron daemon status
systemctl status cron     # Debian/Ubuntu
systemctl status crond    # CentOS/RHEL

# Or check process
ps aux | grep cron | grep -v grep
```

### 2. Test Cron Command Manually

```bash
# Run the exact command from your crontab
/home/cmis-test/bin/php artisan schedule:run

# Check if it works
echo $?  # Should output: 0 (success)
```

### 3. Monitor Scheduler Execution

```bash
# Watch scheduler in real-time
watch -n 1 'php artisan schedule:list'

# Check recent scheduler logs
tail -f storage/logs/laravel.log | grep -i "schedule"

# Check specific task logs
tail -f storage/logs/social-publishing.log
```

### 4. Verify Cron Job Ran

```bash
# Check cron execution logs (system level)
grep CRON /var/log/syslog | tail -20

# Check Laravel logs
grep -i "scheduled\|schedule:run" storage/logs/laravel.log | tail -20
```

---

## 🚨 Common Issues & Solutions

### Issue 1: Cron Not Running

**Problem:** Tasks not executing automatically

**Solutions:**
```bash
# 1. Check cron daemon is running
sudo systemctl status cron
sudo systemctl start cron     # If stopped
sudo systemctl enable cron    # Enable on boot

# 2. Check crontab syntax
crontab -l | grep -v "^#" | grep -v "^$"

# 3. Test command manually
/home/cmis-test/bin/php artisan schedule:run -v
```

### Issue 2: Wrong PHP Path

**Problem:** `php: command not found` in cron logs

**Solution:**
```bash
# Find correct PHP path
which php

# Update crontab with absolute path
crontab -e
# Change: php artisan schedule:run
# To:     /home/cmis-test/bin/php artisan schedule:run
```

### Issue 3: Permission Denied

**Problem:** Cron can't execute script

**Solutions:**
```bash
# 1. Check file permissions
ls -la /home/cmis-test/public_html/artisan

# 2. Make artisan executable
chmod +x /home/cmis-test/public_html/artisan

# 3. Check ownership
chown cmis-test:cmis-test /home/cmis-test/public_html/artisan
```

### Issue 4: Working Directory Wrong

**Problem:** Artisan can't find files

**Solution:**
```cron
# Add 'cd' before command
* * * * * cd /home/cmis-test/public_html && /home/cmis-test/bin/php artisan schedule:run >> /dev/null 2>&1
```

### Issue 5: Environment Variables Missing

**Problem:** Database connection fails in cron

**Solutions:**
```bash
# Method 1: Source .env in crontab
* * * * * cd /home/cmis-test/public_html && . .env && /home/cmis-test/bin/php artisan schedule:run >> /dev/null 2>&1

# Method 2: Use --env flag
* * * * * /home/cmis-test/bin/php artisan schedule:run --env=production >> /dev/null 2>&1

# Method 3: Set PATH in crontab
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
* * * * * cd /home/cmis-test/public_html && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Monitoring & Logging

### 1. Create Scheduler Log

```bash
# Add to crontab
* * * * * /home/cmis-test/bin/php artisan schedule:run >> /home/cmis-test/public_html/storage/logs/scheduler.log 2>&1
```

### 2. Rotate Logs Automatically

```bash
# Install logrotate config
sudo cat > /etc/logrotate.d/cmis-scheduler << 'EOF'
/home/cmis-test/public_html/storage/logs/scheduler.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 644 cmis-test cmis-test
}
EOF

# Test logrotate
sudo logrotate -d /etc/logrotate.d/cmis-scheduler
```

### 3. Monitor with Laravel Telescope (Optional)

```bash
# Install Telescope
composer require laravel/telescope --dev

# Publish config
php artisan telescope:install
php artisan migrate

# Access at: https://cmis-test.kazaaz.com/telescope
```

---

## 🎯 Recommended Cron Setup for CMIS

### Complete Production Setup

```cron
# CMIS Platform Cron Jobs
# User: cmis-test
# Updated: 2025-11-30

# Set environment variables
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin:/home/cmis-test/bin
MAILTO=admin@cmis.test

# Laravel Scheduler (handles all scheduled tasks)
* * * * * cd /home/cmis-test/public_html && /home/cmis-test/bin/php artisan schedule:run >> /dev/null 2>&1

# Queue Worker Health Check (restart if needed)
*/5 * * * * pgrep -f "queue:work" > /dev/null || cd /home/cmis-test/public_html && /home/cmis-test/bin/php artisan queue:work --daemon --queue=social-publishing,default >> /dev/null 2>&1 &

# Cleanup old logs (weekly)
0 3 * * 0 find /home/cmis-test/public_html/storage/logs -name "*.log" -mtime +30 -delete

# Database optimization (monthly)
0 4 1 * * cd /home/cmis-test/public_html && /home/cmis-test/bin/php artisan db:optimize >> /dev/null 2>&1
```

### Install Recommended Setup

```bash
# Save to file
cat > /tmp/cmis-production-crontab << 'EOF'
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin:/home/cmis-test/bin
MAILTO=admin@cmis.test

* * * * * cd /home/cmis-test/public_html && /home/cmis-test/bin/php artisan schedule:run >> /dev/null 2>&1
*/5 * * * * pgrep -f "queue:work" > /dev/null || cd /home/cmis-test/public_html && /home/cmis-test/bin/php artisan queue:work --daemon --queue=social-publishing,default >> /dev/null 2>&1 &
0 3 * * 0 find /home/cmis-test/public_html/storage/logs -name "*.log" -mtime +30 -delete
0 4 1 * * cd /home/cmis-test/public_html && /home/cmis-test/bin/php artisan db:optimize >> /dev/null 2>&1
EOF

# Backup current crontab
crontab -l > /tmp/crontab-backup-$(date +%Y%m%d-%H%M%S)

# Install new crontab
crontab /tmp/cmis-production-crontab

# Verify
crontab -l
```

---

## 📚 Additional Resources

### Commands Reference

```bash
# View crontab
crontab -l

# Edit crontab
crontab -e

# Remove all cron jobs
crontab -r

# Install from file
crontab /path/to/cronfile

# View cron logs (system)
grep CRON /var/log/syslog | tail -50

# Laravel scheduler commands
php artisan schedule:list          # List all tasks
php artisan schedule:run           # Run scheduler once
php artisan schedule:work          # Run scheduler continuously
php artisan schedule:test          # Test scheduled tasks
```

### Useful Tools

- **crontab.guru** - Visual cron schedule editor: https://crontab.guru/
- **cronitor.io** - Cron job monitoring service
- **Laravel Horizon** - Queue monitoring dashboard

---

## ✅ Verification Checklist

After configuring cron jobs:

- [ ] Cron daemon is running
- [ ] Crontab uses absolute paths to PHP and artisan
- [ ] Command works when run manually
- [ ] Scheduler tasks are executing (check logs)
- [ ] Queue worker is running
- [ ] Scheduled posts are publishing automatically
- [ ] Platform syncs are running
- [ ] Backups are being created
- [ ] Logs are being written
- [ ] No errors in `/var/log/syslog`

---

## 🆘 Support

**Issues?**
1. Check logs: `storage/logs/laravel.log`
2. Check scheduler: `php artisan schedule:list`
3. Test manually: `php artisan schedule:run -v`
4. Check cron logs: `grep CRON /var/log/syslog | tail -50`

**Documentation:**
- Laravel Scheduler: https://laravel.com/docs/scheduling
- Cron: `man crontab` or `man 5 crontab`

---

**Last Verified:** 2025-11-30
**Platform Version:** CMIS 3.2
**Laravel Version:** 10.x
