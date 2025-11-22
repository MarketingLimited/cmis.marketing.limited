# Code Duplication Refactoring Plan

**Date:** 2025-11-21
**Status:** Ready for Implementation
**Full Analysis:** See `docs/active/analysis/code-duplication-analysis-2025-11-21.md`

---

## Quick Summary

**Duplicated Code Found:** ~11,271 lines (7.1% of 157,883 LOC)
**Potential Savings:** 9,271 lines (82% reduction to <2,000 lines)
**Risk Level:** Low to Medium (with proper testing)
**Estimated Effort:** 12 weeks (phased approach)

---

## Top 5 Critical Issues

### 1. BaseModel Abandonment (CRITICAL)
- **Impact:** 283 models NOT using existing BaseModel
- **Lines Duplicated:** ~1,174 lines
- **Fix:** Convert all models to extend BaseModel
- **Effort:** Medium | **Risk:** Low | **Priority:** HIGH

### 2. Platform Service Duplication (CRITICAL)
- **Impact:** 6 platform services with 13+ duplicated methods each
- **Lines Duplicated:** ~4,000 lines
- **Fix:** Expand AbstractAdPlatform with template methods
- **Effort:** High | **Risk:** Medium | **Priority:** HIGH

### 3. Controller Response Duplication (HIGH)
- **Impact:** 1,910 JSON response patterns across 148 controllers
- **Lines Duplicated:** ~1,900 lines
- **Fix:** Create ApiResponse trait
- **Effort:** Low | **Risk:** Very Low | **Priority:** HIGH

### 4. org() Relationship Duplication (HIGH)
- **Impact:** 99 models with identical org() method
- **Lines Duplicated:** ~297 lines
- **Fix:** Create HasOrganization trait
- **Effort:** Low | **Risk:** Very Low | **Priority:** HIGH

### 5. RLS Policy Duplication (MEDIUM)
- **Impact:** 126 policies with inconsistent patterns
- **Lines Duplicated:** ~2,000 lines (future migrations)
- **Fix:** Create RLS helper trait
- **Effort:** Low | **Risk:** Low | **Priority:** MEDIUM

---

## Implementation Phases

### Phase 1: Quick Wins (Week 1-2) ⚡
**Effort:** Low | **Impact:** High | **Risk:** Very Low

#### Tasks:
1. Create `app/Http/Controllers/Concerns/ApiResponse.php`
   - Methods: successResponse(), errorResponse(), validationErrorResponse()
   - Update 5 controllers as proof of concept
   - Roll out to all 148 controllers

2. Create `app/Models/Concerns/HasOrganization.php`
   - Method: org() relationship
   - Include in BaseModel
   - Remove from individual models

3. Create `database/migrations/Concerns/HasRLSPolicies.php`
   - Methods: enableRLS(), addOrgIsolationPolicy(), createTableWithRLS()
   - Use in next migration

4. Create custom exception classes
   - PlatformConnectionException
   - PlatformAuthenticationException
   - CampaignOperationException

#### Success Metrics:
- [ ] ApiResponse trait created and used in 148 controllers
- [ ] HasOrganization trait created and used in BaseModel
- [ ] RLS helper trait created and documented
- [ ] 4 custom exception classes created
- [ ] ~2,200 lines of duplication eliminated

---

### Phase 2: Model Refactoring (Week 3-4) 🔨
**Effort:** Medium | **Impact:** High | **Risk:** Low

#### Tasks:
1. Test BaseModel thoroughly
   - Verify RLS policies work
   - Verify UUID generation
   - Verify org() relationship
   - Run full test suite

2. Create model conversion script
   - Identify all models extending Model
   - Generate conversion diffs
   - Review manually

3. Convert models in batches
   - Batch 1: 50 models (test heavily)
   - Batch 2: 50 models
   - Batch 3: 50 models
   - Continue until all 283 converted

4. Update documentation
   - Update CMIS_PROJECT_KNOWLEDGE.md
   - Update model templates
   - Update developer onboarding docs

#### Success Metrics:
- [ ] BaseModel tested with 100% passing tests
- [ ] 283 models converted to extend BaseModel
- [ ] Zero failing tests after conversion
- [ ] Documentation updated
- [ ] ~1,174 lines of duplication eliminated

---

### Phase 3: Service Abstraction (Week 5-8) 🏗️
**Effort:** High | **Impact:** Very High | **Risk:** Medium

#### Tasks:
1. Design template method pattern
   - executeCampaignOperation()
   - transformPlatformResponse()
   - handlePlatformError()
   - validatePlatformCredentials()

2. Update AbstractAdPlatform
   - Add template methods
   - Add common transformations
   - Add error handling patterns

3. Refactor Meta platform (proof of concept)
   - Implement abstract methods
   - Remove duplicated code
   - Test all operations
   - Verify metrics sync

4. Roll out to remaining platforms
   - Google Ads
   - TikTok
   - LinkedIn
   - Twitter
   - Snapchat

5. Create shared utilities
   - MetricsTransformer
   - StatusMapper
   - ResponseNormalizer

#### Success Metrics:
- [ ] AbstractAdPlatform expanded with template methods
- [ ] Meta platform refactored and tested
- [ ] 5 remaining platforms refactored
- [ ] All platform tests passing
- [ ] ~4,000 lines of duplication eliminated

---

### Phase 4: Controller Enhancement (Week 9-10) 📋
**Effort:** Medium | **Impact:** High | **Risk:** Low

#### Tasks:
1. Create BaseController
   - Use ApiResponse trait
   - Add common exception handling
   - Add authorization helpers

2. Generate Form Request classes
   - Identify 93 missing Form Requests
   - Generate using artisan make:request
   - Move validation rules from controllers
   - Update controllers to use Form Requests

3. Update all controllers
   - Extend BaseController
   - Use ApiResponse methods
   - Remove inline validation
   - Use Form Requests

4. Standardize exception handling
   - Use custom exceptions
   - Remove generic Exception throws
   - Add proper logging

#### Success Metrics:
- [ ] BaseController created and tested
- [ ] 93 Form Request classes generated
- [ ] 148 controllers updated
- [ ] Zero inline validation remaining
- [ ] ~2,500 lines of duplication eliminated

---

### Phase 5: Migration Standardization (Week 11-12) 📊
**Effort:** Low | **Impact:** Medium | **Risk:** Low

#### Tasks:
1. Document RLS standard
   - Choose: current_setting('app.current_org_id')::uuid
   - Document in MULTI_TENANCY_PATTERNS.md
   - Create migration template

2. Audit existing migrations
   - List inconsistencies
   - Document for reference
   - No changes to existing (too risky)

3. Update migration templates
   - Include HasRLSPolicies trait
   - Show usage examples
   - Update Laravel Idea templates

4. Create migration generator
   - Artisan command: make:migration-with-rls
   - Auto-includes RLS setup
   - Reduces boilerplate

#### Success Metrics:
- [ ] RLS standard documented
- [ ] Migration template updated
- [ ] Artisan command created
- [ ] Developer onboarding updated
- [ ] Future migrations will save ~50 lines each

---

## Quick Action Items (Start Today)

### Immediate (No Dependencies)
1. ✅ Create `app/Http/Controllers/Concerns/ApiResponse.php`
2. ✅ Create `app/Models/Concerns/HasOrganization.php`
3. ✅ Create custom exception classes
4. ✅ Create `database/migrations/Concerns/HasRLSPolicies.php`

### This Week
5. Test ApiResponse trait in 5 controllers
6. Add HasOrganization to BaseModel
7. Write tests for RLS helper trait
8. Document new patterns in knowledge base

### Next Week
9. Begin model conversion (Batch 1: 50 models)
10. Update controller templates
11. Generate first batch of Form Requests
12. Design platform service abstraction

---

## Files to Create (Phase 1)

### 1. ApiResponse Trait
**Path:** `/home/user/cmis.marketing.limited/app/Http/Controllers/Concerns/ApiResponse.php`
**Lines:** ~60
**Usage:** Use in all controllers

### 2. HasOrganization Trait
**Path:** `/home/user/cmis.marketing.limited/app/Models/Concerns/HasOrganization.php`
**Lines:** ~20
**Usage:** Include in BaseModel

### 3. HasRLSPolicies Trait
**Path:** `/home/user/cmis.marketing.limited/database/migrations/Concerns/HasRLSPolicies.php`
**Lines:** ~80
**Usage:** Use in new migrations

### 4. Custom Exceptions
**Path:** `/home/user/cmis.marketing.limited/app/Exceptions/Platform/`
**Files:** 4 exception classes
**Lines:** ~40 total

### 5. BaseController
**Path:** `/home/user/cmis.marketing.limited/app/Http/Controllers/BaseController.php`
**Lines:** ~40
**Usage:** Extend in all controllers

---

## Testing Strategy

### Phase 1 Testing
- Unit tests for ApiResponse trait methods
- Feature tests for 5 controllers using ApiResponse
- Unit tests for HasOrganization trait
- Integration tests for RLS helper trait

### Phase 2 Testing
- Multi-tenancy isolation tests for BaseModel
- UUID generation tests
- Full model test suite (must pass 100%)
- Cross-organization data leak tests

### Phase 3 Testing
- Platform integration tests (all CRUD operations)
- Metrics sync tests
- Error handling tests
- Rate limiting tests
- OAuth flow tests

### Phase 4 Testing
- Form Request validation tests
- Controller authorization tests
- Exception handling tests
- API response format tests

### Phase 5 Testing
- RLS policy verification
- Migration rollback tests
- Policy isolation tests

---

## Rollback Plan

### If Issues Arise:
1. **Phase 1:** Simple - just stop using traits (no data changes)
2. **Phase 2:** Git revert model changes, restore from backup
3. **Phase 3:** Platform-specific - revert one platform at a time
4. **Phase 4:** Controller changes are safe (no data changes)
5. **Phase 5:** Migrations are one-way (future only)

### Safety Measures:
- Database backups before each phase
- Git branch for each phase
- Feature flags for new patterns
- Gradual rollout (not big bang)
- Keep old code commented for 1 sprint

---

## Success Metrics Summary

| Phase | Lines Saved | Risk | Effort | Duration |
|-------|-------------|------|--------|----------|
| Phase 1 | ~2,200 | Very Low | Low | 2 weeks |
| Phase 2 | ~1,174 | Low | Medium | 2 weeks |
| Phase 3 | ~4,000 | Medium | High | 4 weeks |
| Phase 4 | ~2,500 | Low | Medium | 2 weeks |
| Phase 5 | ~2,000 (future) | Low | Low | 2 weeks |
| **Total** | **~11,874** | **Low-Med** | **Medium** | **12 weeks** |

---

## Next Steps

1. Review this plan with team
2. Get approval for Phase 1
3. Create GitHub issues for Phase 1 tasks
4. Assign developers
5. Begin implementation
6. Schedule daily standups for coordination
7. Plan demo for end of Phase 1

---

**Created:** 2025-11-21
**Owner:** Development Team
**Reviewed By:** (Pending)
**Status:** Awaiting Approval
