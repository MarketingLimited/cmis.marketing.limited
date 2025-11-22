# CMIS Testing Documentation

**Last Updated:** 2025-11-22
**Test Suite Status:** 201 tests, 33.4% pass rate (improving continuously)
**Framework:** Laravel (PHPUnit)

---

## Overview

This directory consolidates all CMIS testing documentation, including test strategies, current status, historical reports, and testing guides.

---

## Quick Navigation

- **New to testing CMIS?** → [guides/](./guides/)
- **Current test status?** → [current/](./current/)
- **Historical test reports?** → [history/](./history/)

---

## Directory Structure

### [current/](./current/) - Current Test Status

Latest test suite status, assessments, and active improvements:

- **Test suite summaries** - Overall pass rates, coverage metrics
- **Active test fixes** - Ongoing test improvement work
- **Test failure analysis** - Current known failures and fixes
- **Coverage reports** - Code coverage metrics

**Latest Status (Nov 2025):**
- Total Tests: 201
- Pass Rate: 33.4% (improving from 20%)
- Test Files: Unit tests, Feature tests, Integration tests
- Coverage: Targeting 40-45% in current phase

### [guides/](./guides/) - Testing Guides

Comprehensive guides for writing and running tests:

- **Testing framework overview** - PHPUnit, Laravel testing basics
- **Writing tests guide** - How to write effective tests
- **Parallel testing guide** - Running tests in parallel
- **Test conventions** - CMIS-specific testing standards
- **Multi-tenancy testing** - Testing RLS and tenant isolation
- **E2E testing guide** - End-to-end testing strategies

### [history/](./history/) - Historical Test Reports

Archived test reports and improvement sessions:

- **Session summaries** - Test improvement sessions (Nov 2025)
- **Test fix reports** - Historical test fixes
- **Progress tracking** - Test improvement over time
- **Milestone reports** - Key testing milestones achieved

---

## Test Suite Overview

### Test Organization

```
tests/
├── Unit/               # Unit tests (isolated components)
├── Feature/            # Feature tests (HTTP endpoints, workflows)
├── Integration/        # Integration tests (database, external services)
└── Browser/            # Browser tests (Dusk - if implemented)
```

### Key Testing Areas

| Area | Test Count | Coverage | Priority |
|------|-----------|----------|----------|
| **Multi-Tenancy & RLS** | High | Critical | P0 |
| **Authentication & Authorization** | Medium | High | P1 |
| **Campaign Management** | High | Medium | P1 |
| **Platform Integrations** | Medium | Low | P2 |
| **AI & Semantic Search** | Low | Low | P2 |
| **Social Publishing** | Medium | Medium | P1 |
| **API Endpoints** | High | Medium | P1 |
| **Database Operations** | High | High | P0 |

---

## Running Tests

### Quick Start

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature

# Run specific test file
vendor/bin/phpunit tests/Unit/Models/CampaignTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage
```

### Parallel Testing

```bash
# Run tests in parallel (faster)
php artisan test --parallel

# See: guides/parallel-testing.md for setup
```

### Using Slash Command

```bash
# Use CMIS custom test command
/test
```

---

## Testing Standards

### Multi-Tenancy Testing

**CRITICAL:** Always test with multiple organizations for data isolation:

```php
public function test_campaign_respects_rls()
{
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    $campaign1 = Campaign::factory()->for($org1)->create();
    $campaign2 = Campaign::factory()->for($org2)->create();

    // Set org context
    init_transaction_context($org1->id);

    // Should only see org1's campaign
    $this->assertCount(1, Campaign::all());
    $this->assertTrue(Campaign::all()->contains($campaign1));
    $this->assertFalse(Campaign::all()->contains($campaign2));
}
```

### Test Naming Conventions

```php
// ✅ Good
test_user_can_create_campaign_when_authenticated()
test_campaign_creation_fails_without_required_fields()
test_rls_policy_prevents_cross_org_data_access()

// ❌ Bad
testCampaign()
test1()
testStuff()
```

### Test Structure

Follow **Arrange-Act-Assert** pattern:

```php
public function test_user_can_update_campaign()
{
    // Arrange
    $user = User::factory()->create();
    $campaign = Campaign::factory()->for($user->org)->create();

    // Act
    $response = $this->actingAs($user)
        ->putJson("/api/campaigns/{$campaign->id}", [
            'name' => 'Updated Name'
        ]);

    // Assert
    $response->assertOk();
    $this->assertEquals('Updated Name', $campaign->fresh()->name);
}
```

---

## Test Improvement Roadmap

### Current Phase (Nov 2025)
- ✅ Reached 33.4% pass rate (from 20%)
- ✅ Fixed agent testing improvements
- ✅ Established parallel testing infrastructure
- 🔄 Targeting 40-45% pass rate

### Next Milestones
1. **40% Pass Rate** (Target: Dec 2025)
   - Fix remaining P0 critical test failures
   - Improve multi-tenancy test coverage

2. **60% Pass Rate** (Target: Q1 2026)
   - Add platform integration tests
   - Improve API endpoint coverage

3. **85% Pass Rate** (Target: Q2 2026)
   - Comprehensive E2E tests
   - Full CI/CD integration

---

## Key Test Reports

### Current Status
- [current/test-suite-status.md](./current/test-suite-status.md) - Latest overall status
- [current/test-coverage-report.md](./current/test-coverage-report.md) - Coverage metrics

### Recent Improvements (Nov 2025)
- [history/test-improvement-session-2025-11-21.md](./history/test-improvement-session-2025-11-21.md)
- [history/test-fixes-progress-2025-11-20.md](./history/test-fixes-progress-2025-11-20.md)
- [history/agent-testing-improvements-2025-11-19.md](./history/agent-testing-improvements-2025-11-19.md)

---

## Testing Tools & Resources

### Laravel Testing
- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Dusk (Browser Testing)](https://laravel.com/docs/dusk)

### CMIS-Specific
- [Multi-Tenancy Testing Patterns](../../.claude/knowledge/MULTI_TENANCY_PATTERNS.md)
- [Testing Slash Command](./.claude/commands/test.md)
- [Testing Infrastructure Summary](./guides/testing-infrastructure-summary.md)

### External Tools
- **Code Coverage:** PHPUnit built-in coverage
- **CI/CD:** GitHub Actions (see `.github/workflows/`)
- **Parallel Testing:** Laravel Parallel Testing package

---

## Contributing to Tests

### Adding New Tests

1. **Identify the area** - Unit, Feature, or Integration?
2. **Create test file** - Follow naming conventions
3. **Write test** - Use Arrange-Act-Assert pattern
4. **Test multi-tenancy** - Always verify RLS if applicable
5. **Run tests** - Ensure pass before committing
6. **Update documentation** - Update this README if needed

### Fixing Failing Tests

1. **Check current/missing-tests.md** for known failures
2. **Reproduce locally** - Run specific test
3. **Fix root cause** - Don't just make test pass
4. **Verify multi-tenancy** - Check RLS still works
5. **Run full suite** - Ensure no regressions
6. **Document fix** - Add to current/test-fixes.md

---

## CI/CD Integration

### GitHub Actions Workflow

Tests run automatically on:
- Push to any branch
- Pull request creation
- Merge to main branch

See: [.github/workflows/](./.github/workflows/) for CI/CD configuration

---

## Troubleshooting

### Common Test Issues

**Issue:** Tests fail with "app.current_org_id not set"
**Fix:** Call `init_transaction_context($orgId)` before database operations

**Issue:** RLS policy prevents test data access
**Fix:** Ensure test sets organization context correctly

**Issue:** Tests pass locally but fail in CI
**Fix:** Check database seeding, environment variables

**Issue:** Parallel tests fail
**Fix:** See `guides/parallel-testing.md` for configuration

---

## Related Documentation

- **[CLAUDE.md](../../CLAUDE.md)** - Project guidelines including testing standards
- **[docs/development/](../development/)** - Development guides
- **[.claude/agents/laravel-testing.md](../../.claude/agents/laravel-testing.md)** - Testing agent

---

## Maintenance

**Update Frequency:** Weekly during active test improvement phases

**Last Major Update:** 2025-11-22 (Documentation restructure)

**Next Review:** When 40% pass rate achieved

---

**For questions about testing, use the `/test` command or consult the laravel-testing agent.**
