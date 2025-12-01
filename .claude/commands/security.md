---
description: Run security audit - check for vulnerabilities, exposed secrets, and security best practices
---

# Security Audit Command

Run a comprehensive security audit on the CMIS codebase.

## Checks to Perform

### 1. Exposed Secrets Check
```bash
# Check for hardcoded credentials
grep -rn "password\s*=\s*['\"]" --include="*.php" --include="*.env*" --include="*.js" app/ config/ | grep -v ".env.example" | head -20

# Check for API keys in code
grep -rn "api_key\|apikey\|secret_key\|API_SECRET" --include="*.php" --include="*.js" app/ config/ | grep -v "env(" | head -20

# Check for exposed .env files
ls -la .env* 2>/dev/null
```

### 2. SQL Injection Vulnerabilities
```bash
# Check for raw queries without bindings
grep -rn "DB::raw\|->whereRaw\|->selectRaw" --include="*.php" app/ | head -20

# Check for string concatenation in queries
grep -rn "\$.*\..*->where\|\".*\$" --include="*.php" app/Repositories/ app/Services/ | head -20
```

### 3. XSS Vulnerabilities
```bash
# Check for unescaped output in Blade
grep -rn "{!!\s*\$\|@php\s*echo" --include="*.blade.php" resources/views/ | head -20

# Check for missing CSRF tokens in forms
grep -rn "<form" --include="*.blade.php" resources/views/ | grep -v "@csrf" | head -10
```

### 4. Authentication & Authorization
```bash
# Check middleware usage on routes
grep -rn "Route::" routes/ | grep -v "middleware" | head -20

# Check for policies on controllers
grep -rn "authorize\|can\|Gate::" --include="*.php" app/Http/Controllers/ | head -20
```

### 5. RLS Policy Audit
```bash
# Run the dedicated RLS audit
# Check if RLS is enabled on all tables with org_id
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" -c "
SELECT
    schemaname || '.' || tablename as table_name,
    rowsecurity as rls_enabled
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
  AND tablename IN (
    SELECT table_name
    FROM information_schema.columns
    WHERE column_name = 'org_id'
  )
ORDER BY rls_enabled, table_name;
"
```

### 6. Dependency Vulnerabilities
```bash
# Check PHP dependencies
composer audit 2>/dev/null || echo "Run: composer audit"

# Check npm dependencies
npm audit --production 2>/dev/null || echo "Run: npm audit"
```

### 7. File Permission Check
```bash
# Check storage permissions
ls -la storage/
ls -la bootstrap/cache/

# Check for world-writable files
find . -type f -perm -002 2>/dev/null | grep -v node_modules | grep -v vendor | head -20
```

### 8. HTTPS & Security Headers
```bash
# Check for HTTP redirects
grep -rn "redirect.*http://" --include="*.php" app/ | head -10

# Check security middleware
grep -rn "X-Frame-Options\|X-XSS-Protection\|Content-Security-Policy" app/Http/Middleware/ config/
```

## Output Report

Generate a security report with:
- Critical vulnerabilities (exposed secrets, SQL injection)
- High priority (missing auth, XSS risks)
- Medium priority (missing headers, permissions)
- Low priority (best practice improvements)

Save to: `docs/active/analysis/security-audit-{date}.md`
