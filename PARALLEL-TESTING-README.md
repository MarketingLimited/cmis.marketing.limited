# Parallel Testing Quick Reference

**Status:** ✅ Configured and Ready
**Setup Date:** 2025-11-21
**Infrastructure:** 15 parallel test databases + ParaTest

---

## 🚀 Quick Start

### First Time Setup

```bash
# 1. Create parallel test databases (one-time setup)
./setup-parallel-databases.sh
```

### Running Tests

```bash
# Run unit tests in parallel (fastest)
./run-tests-parallel.sh --unit

# Run feature tests in parallel
./run-tests-parallel.sh --feature

# Run integration tests in parallel
./run-tests-parallel.sh --integration

# Run all tests
./run-tests-parallel.sh
```

---

## 📊 Performance

**Expected Speedup:** 4-6x faster than sequential execution

| Test Suite | Sequential | Parallel (7 cores) |
|------------|-----------|-------------------|
| Unit | ~15 min | ~3 min |
| Feature | ~10 min | ~2 min |
| Integration | ~8 min | ~2 min |

---

## 🛠️ Configuration

### Database Setup
- **Main Database:** `cmis_test`
- **Parallel Databases:** `cmis_test_1` through `cmis_test_15`
- **Total:** 16 databases

### Process Count
- **Auto-detected:** CPU cores - 1 (minimum 2)
- **Your System:** 7 parallel processes (detected automatically)

---

## 📁 Key Files

```
├── setup-parallel-databases.sh          # Database setup script
├── run-tests-parallel.sh                # Parallel test runner
├── tests/
│   ├── TestCase.php                     # Base test case (parallel support)
│   ├── ParallelTestCase.php             # Parallel testing trait
│   └── Feature/API/
│       └── MultiTenancyAPIIsolationTest.php  # New RLS tests
└── docs/guides/development/
    └── parallel-testing-guide.md        # Complete documentation
```

---

## 🔧 Maintenance

### Recreate Databases

```bash
# If you need to reset databases
./setup-parallel-databases.sh
```

### After Adding Migrations

```bash
# Databases are automatically updated by setup script
./setup-parallel-databases.sh
```

---

## 📚 Documentation

**Full Guide:** `docs/guides/development/parallel-testing-guide.md`

Includes:
- ✅ Detailed configuration
- ✅ Troubleshooting guide
- ✅ Best practices
- ✅ CI/CD integration
- ✅ Performance optimization

---

## ✅ What's Included

### Infrastructure
- ✅ ParaTest package installed
- ✅ 15 parallel test databases created
- ✅ Automatic database selection per worker
- ✅ Parallel test runner script
- ✅ Database setup script

### Code
- ✅ `ParallelTestCase` trait for automatic database selection
- ✅ `TestCase` updated with parallel support
- ✅ PHPUnit configured for parallel execution

### Tests
- ✅ 10 new multi-tenancy API isolation tests
- ✅ Comprehensive RLS endpoint coverage
- ✅ All existing tests compatible with parallel execution

### Documentation
- ✅ Complete parallel testing guide
- ✅ Quick reference (this file)
- ✅ Troubleshooting section
- ✅ Best practices guide

---

## 🎯 Usage Examples

### Basic Usage

```bash
# Fastest way to run all unit tests
./run-tests-parallel.sh --unit
```

### Advanced Usage

```bash
# Custom process count
vendor/bin/paratest --processes=10 --testsuite=Unit

# Specific test suite
vendor/bin/paratest --testsuite=Feature --no-coverage

# With filter
vendor/bin/paratest --testsuite=Unit --filter=CampaignTest
```

### CI/CD Integration

```yaml
# GitHub Actions example
- name: Setup Parallel Databases
  run: ./setup-parallel-databases.sh

- name: Run Tests
  run: ./run-tests-parallel.sh --unit
```

---

## 🐛 Common Issues

### Issue: Tests Failing

**Check:**
1. Databases exist: `psql -U begin -d postgres -c "SELECT datname FROM pg_database WHERE datname LIKE 'cmis_test%';"`
2. Migrations ran: `DB_DATABASE=cmis_test php artisan migrate:status`
3. RLS context initialized in tests

**Fix:**
```bash
./setup-parallel-databases.sh
```

### Issue: Slow Execution

**Possible Causes:**
- Too many processes for CPU cores
- Database I/O bottleneck
- Memory constraints

**Fix:**
```bash
# Reduce processes
vendor/bin/paratest --processes=4 --testsuite=Unit
```

---

## 💡 Tips

1. **Always use parallel for unit tests** - Fastest execution
2. **Run setup script after pulling migrations** - Keeps databases in sync
3. **Monitor system resources** - Adjust process count accordingly
4. **Use `--no-coverage`** - Much faster than with coverage
5. **Test independence** - Ensure tests don't depend on each other

---

## 📞 Need Help?

- **Full Documentation:** `docs/guides/development/parallel-testing-guide.md`
- **Testing Guide:** `docs/guides/development/testing-quick-start.md`
- **Multi-Tenancy:** `docs/knowledge/MULTI_TENANCY_PATTERNS.md`

---

**Ready to test? Start with:**

```bash
./run-tests-parallel.sh --unit
```

**Happy Testing! 🚀**
