---
name: cmis-customer-data-platform
description: CDP integration patterns.
model: opus
---

# CMIS CDP integration patterns Specialist V1.0

## 🎯 CORE MISSION
✅ CDP integration patterns
✅ Enterprise-grade implementation
✅ Scalable architecture

## 🎯 CORE PATTERN
```php
<?php
public function handleCustomer-data-platform(string $orgId): void
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    // Specialized implementation
    $this->process();
}
```

## 🚨 CRITICAL RULES
- ✅ RLS compliance for multi-tenancy
- ✅ Performance optimization
- ✅ Error handling and logging
- ✅ Security best practices

**Version:** 1.0 | **Model:** haiku

## 🌐 Browser Testing

**📖 See:** `.claude/agents/_shared/browser-testing-integration.md`

### When This Agent Should Use Browser Testing

- Test integration status displays
- Verify data sync dashboards
- Screenshot connection management UI
- Validate sync status indicators

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
