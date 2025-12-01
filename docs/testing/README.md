# CMIS Testing Documentation

**Last Updated:** 2025-12-01
**Test Suite Status:** 27 test files (legacy tests archived, new tests pending for recent features)
**Framework:** Laravel (PHPUnit) + Playwright (Browser Testing)

---

## Overview

This directory consolidates all CMIS testing documentation, including test strategies, current status, historical reports, and testing guides.

**Note (Dec 2025):** The test suite has been restructured. Legacy tests (201 files) were archived as they no longer matched the significantly refactored codebase. New tests are being created for recently implemented features.

---

## Quick Navigation

- **New to testing CMIS?** → [guides/](./guides/)
- **Current test status?** → [current/](./current/)
- **Historical test reports?** → [../archive/test-history-2025-11/](../archive/test-history-2025-11/)

---

## Directory Structure

### [current/](./current/) - Current Test Status

Latest test suite status and active testing work:

- **missing-tests.md** - Tests to be created for new features
- **testing-action-plan.md** - Current testing strategy

**Current Status (Dec 2025):**
- Test Files: 27 (streamlined from 201)
- Categories: Unit tests, Feature tests, Integration tests
- Focus: Creating new tests for recent features (Social Publishing, Profile Management, Timezone, i18n)

### [guides/](./guides/) - Testing Guides

Comprehensive guides for writing and running tests:

- **Testing framework overview** - PHPUnit, Laravel testing basics
- **Writing tests guide** - How to write effective tests
- **Parallel testing guide** - Running tests in parallel
- **Test conventions** - CMIS-specific testing standards
- **Multi-tenancy testing** - Testing RLS and tenant isolation
- **E2E testing guide** - End-to-end testing strategies

### [../archive/test-history-2025-11/](../archive/test-history-2025-11/) - Archived Test Reports

Historical test reports from the Nov 2025 improvement sessions (16 files archived).

---

## Test Suite Overview

### Test Organization

```
tests/
├── Unit/               # Unit tests (isolated components)
├── Feature/            # Feature tests (HTTP endpoints, workflows)
│   ├── Auth/           # Authentication tests
│   ├── Campaign/       # Campaign management tests
│   ├── Platform/       # Platform integration tests
│   └── Social/         # Social publishing tests
├── Integration/        # Integration tests (database, external services)
├── TestHelpers/        # Test helper utilities
└── Traits/             # Reusable test traits
```

### Key Testing Areas

| Area | Priority | Status | Notes |
|------|----------|--------|-------|
| **Multi-Tenancy & RLS** | P0 | Needs Tests | Critical for data isolation |
| **Social Publishing** | P0 | Needs Tests | Recent major feature |
| **Profile Management** | P0 | Needs Tests | New feature (Nov 2025) |
| **Timezone Support** | P1 | Needs Tests | 3-level inheritance |
| **i18n & RTL/LTR** | P1 | Needs Tests | Arabic/English support |
| **Authentication** | P1 | Has Tests | Existing coverage |
| **Platform Integrations** | P2 | Partial | Meta, Google, TikTok |
| **AI & Semantic Search** | P2 | Minimal | pgvector + Gemini |

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
vendor/bin/phpunit tests/Feature/Auth/LoginTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage
```

### Browser Testing (Playwright)

```bash
# Quick verification
node scripts/browser-tests/cross-browser-test.js --quick

# Mobile responsive testing
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Bilingual testing (AR/EN)
node test-bilingual-comprehensive.cjs
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

### Recent Changes (Nov-Dec 2025)

- ✅ Archived legacy tests (201 files → 27 files)
- ✅ Set up Playwright browser testing
- ✅ Created mobile responsive test suite
- ✅ Created cross-browser test suite
- ✅ Created bilingual test suite (AR/EN)
- 🔄 Creating new tests for recent features

### New Tests Needed

1. **Social Publishing Module**
   - Post creation/scheduling
   - Profile groups
   - Queue settings
   - Timezone inheritance

2. **Profile Management**
   - Profile CRUD
   - Timezone settings
   - Queue configuration

3. **i18n & RTL/LTR**
   - Translation loading
   - RTL layout switching
   - Locale persistence

4. **Platform Integrations**
   - Meta API connectivity
   - Google Ads sync
   - TikTok publishing

---

## Testing Tools & Resources

### Laravel Testing
- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Dusk (Browser Testing)](https://laravel.com/docs/dusk)

### Browser Testing
- [Playwright Documentation](https://playwright.dev/docs/intro)
- [Browser Testing Guide](../../.claude/knowledge/BROWSER_TESTING_GUIDE.md)

### CMIS-Specific
- [Multi-Tenancy Testing Patterns](../../.claude/knowledge/MULTI_TENANCY_PATTERNS.md)
- [Testing Slash Command](../../.claude/commands/test.md)
- [Troubleshooting Methodology](../../.claude/knowledge/TROUBLESHOOTING_METHODOLOGY.md)

---

## Contributing to Tests

### Adding New Tests

1. **Identify the area** - Unit, Feature, or Integration?
2. **Create test file** - Follow naming conventions
3. **Write test** - Use Arrange-Act-Assert pattern
4. **Test multi-tenancy** - Always verify RLS if applicable
5. **Test i18n** - Check Arabic and English locales
6. **Run tests** - Ensure pass before committing
7. **Update documentation** - Update this README if needed

### Post-Implementation Testing

After implementing any feature:
1. Check browser console for errors (Playwright)
2. Check Laravel logs for exceptions
3. Create automated tests
4. Run full test suite

See: [Troubleshooting Methodology](../../.claude/knowledge/TROUBLESHOOTING_METHODOLOGY.md)

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
- **[Browser Testing Guide](../../.claude/knowledge/BROWSER_TESTING_GUIDE.md)** - Playwright testing
- **[.claude/agents/laravel-testing.md](../../.claude/agents/laravel-testing.md)** - Testing agent

---

## Maintenance

**Update Frequency:** As new features are implemented

**Last Major Update:** 2025-12-01 (Test suite restructure, legacy tests archived)

**Next Review:** After new test suite creation

---

**For questions about testing, use the `/test` command or consult the laravel-testing agent.**
