# Shared Infrastructure Pre-Flight Checks
**Version:** 1.0
**Last Updated:** 2025-01-19

This is a shared module for all agents that interact with database or testing infrastructure.

## 🚀 CRITICAL: Pre-Flight Infrastructure Validation

**⚠️ ALL agents working with database or tests MUST run these checks FIRST:**

### Quick Pre-Flight Command

```bash
# Run automated pre-flight checks
./scripts/test-preflight.sh

# If script doesn't exist, run manual checks below
```

### Manual Pre-Flight Checks

#### 1. PostgreSQL Server Validation
```bash
# Check if PostgreSQL is running
service postgresql status 2>&1 | grep -qi "active\|running\|online" && echo "✅ Running" || echo "❌ Not running"

# Start if not running
if ! service postgresql status 2>&1 | grep -qi "active\|running\|online"; then
    echo "Starting PostgreSQL..."
    service postgresql start
    sleep 2
fi

# Verify connection
psql -h 127.0.0.1 -U postgres -d postgres -c "SELECT 1;" >/dev/null 2>&1 && echo "✅ Can connect" || echo "❌ Cannot connect"
```

#### 2. Composer Dependencies Validation
```bash
# Check if vendor directory exists
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Verify installation
test -f vendor/autoload.php && echo "✅ Dependencies installed" || echo "❌ Dependencies missing"
```

#### 3. Database Role Validation
```bash
# Check for required 'begin' role
psql -h 127.0.0.1 -U postgres -d postgres -c "\du" 2>&1 | grep -q "begin" || {
    echo "Creating 'begin' database role..."
    psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE ROLE begin WITH LOGIN SUPERUSER PASSWORD '123@Marketing@321';"
}
```

#### 4. Environment Variables Check
```bash
# Ensure not using remote database for local work
if printenv | grep -q "^DB_HOST=2\.59\.156\.237"; then
    echo "⚠️  Remote DB detected. For local work, unset:"
    echo "unset DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD"
fi
```

## 🔧 Common PostgreSQL Issues & Solutions

### Issue: PostgreSQL Not Running

**Symptoms:**
- "connection to server failed"
- "could not connect to server"

**Solutions:**
```bash
# 1. Start PostgreSQL
service postgresql start

# 2. If SSL certificate errors:
sed -i 's/^ssl = on/ssl = off/' /etc/postgresql/*/main/postgresql.conf
service postgresql restart

# 3. If permission errors:
chmod 640 /etc/ssl/private/ssl-cert-snakeoil.key
chown root:ssl-cert /etc/ssl/private/ssl-cert-snakeoil.key
```

### Issue: Authentication Failed

**Symptoms:**
- "Peer authentication failed"
- "FATAL: password authentication failed"

**Solutions:**
```bash
# Switch to trust authentication for local development
sed -i 's/peer/trust/g' /etc/postgresql/*/main/pg_hba.conf
sed -i 's/scram-sha-256/trust/g' /etc/postgresql/*/main/pg_hba.conf
service postgresql reload
```

### Issue: Role Does Not Exist

**Symptoms:**
- "role 'begin' does not exist"
- "role 'postgres' does not exist"

**Solutions:**
```bash
# Create missing role
psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE ROLE begin WITH LOGIN SUPERUSER PASSWORD '123@Marketing@321';"

# Verify roles
psql -h 127.0.0.1 -U postgres -d postgres -c "\du"
```

### Issue: Extension Not Available

**Symptoms:**
- "extension 'vector' is not available"
- "extension 'uuid-ossp' is not available"

**Solutions:**
```bash
# Install pgvector
apt-get update && apt-get install -y postgresql-*-pgvector
service postgresql restart

# Install uuid-ossp (usually included)
psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";"
```

### Issue: Database Does Not Exist

**Symptoms:**
- "database 'cmis_test' does not exist"
- "database 'cmis_test_1' does not exist"

**Solutions:**
```bash
# Create test database
psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE DATABASE cmis_test;"

# Create parallel test databases (for testing)
for i in {1..15}; do
    psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE DATABASE cmis_test_$i;"
done
```

## 📋 Pre-Flight Checklist

Before starting ANY database or testing work:

- [ ] PostgreSQL server is running
- [ ] Can connect to PostgreSQL (psql test successful)
- [ ] Composer dependencies are installed (vendor/ exists)
- [ ] Required database roles exist (begin, postgres)
- [ ] Required extensions are available (pgvector, uuid-ossp)
- [ ] Test databases exist (if running tests)
- [ ] Environment variables are correct (not pointing to remote DB)

## 🎯 Quick Validation Script

```bash
#!/bin/bash
echo "=== Infrastructure Pre-Flight Check ==="

# PostgreSQL
service postgresql status >/dev/null 2>&1 && echo "✅ PostgreSQL running" || echo "❌ PostgreSQL not running"

# Composer
test -f vendor/autoload.php && echo "✅ Composer dependencies" || echo "❌ Composer dependencies missing"

# Connection
psql -h 127.0.0.1 -U postgres -d postgres -c "SELECT 1;" >/dev/null 2>&1 && echo "✅ Database connection" || echo "❌ Cannot connect to database"

# Role
psql -h 127.0.0.1 -U postgres -d postgres -c "\du" 2>&1 | grep -q "begin" && echo "✅ Database roles" || echo "❌ Missing database roles"

echo "=== Check Complete ==="
```

## 🚨 When to Run Pre-Flight Checks

Run pre-flight checks when:
1. Starting a new task involving database
2. Before running migrations
3. Before executing tests
4. After system restart
5. When encountering connection errors
6. Before database schema changes

## 📚 Related Resources

- **Full Pre-Flight Script:** `scripts/test-preflight.sh`
- **Testing Agent:** `.claude/agents/laravel-testing.md`
- **Database Agent:** `.claude/agents/laravel-db-architect.md`
- **DevOps Agent:** `.claude/agents/laravel-devops.md`

---

**Remember:** Infrastructure validation prevents 90% of common errors. Always validate before executing tasks!
