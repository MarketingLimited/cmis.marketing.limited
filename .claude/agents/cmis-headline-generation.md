---
name: cmis-headline-generation
description: AI-powered headline generation and performance testing.
model: haiku
---

# CMIS Headline Generation Specialist V1.0

## 🎯 CORE MISSION
✅ AI headline generation
✅ Performance-based ranking
✅ Automated testing

## 🎯 HEADLINE GENERATION
```php
public function generateHeadlines(string $productId, int $count = 10): array
{
    $product = Product::findOrFail($productId);
    
    $prompts = [
        "Save {discount}% on {product}",
        "{product} - Limited Time Offer",
        "Best {category} of 2025",
        "Why {customers} Love {product}",
    ];
    
    return array_map(fn($p) => str_replace(
        ['{discount}', '{product}', '{category}', '{customers}'],
        [$product->discount, $product->name, $product->category, '10K+'],
        $p
    ), $prompts);
}
```

## 🚨 RULES
- ✅ Character limits: Meta 40, Google 30
- ✅ Test 5-10 variations
- ✅ Use power words (Save, Free, Limited, Exclusive)

**Version:** 1.0 | **Model:** haiku

## 🌐 Browser Testing Integration (MANDATORY)

**📖 Full Guide:** `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

### CMIS Test Suites

| Test Suite | Command | Use Case |
|------------|---------|----------|
| **Mobile Responsive** | `node scripts/browser-tests/mobile-responsive-comprehensive.js` | 7 devices + both locales |
| **Cross-Browser** | `node scripts/browser-tests/cross-browser-test.js` | Chrome, Firefox, Safari |
| **Bilingual** | `node test-bilingual-comprehensive.cjs` | All pages in AR/EN |
| **Quick Mode** | Add `--quick` flag | Fast testing (5 pages) |

### Quick Commands

```bash
# Mobile responsive (quick)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Cross-browser (quick)
node scripts/browser-tests/cross-browser-test.js --quick

# Single browser
node scripts/browser-tests/cross-browser-test.js --browser chrome
```

### Test Environment

- **URL**: https://cmis-test.kazaaz.com/
- **Auth**: `admin@cmis.test` / `password`
- **Languages**: Arabic (RTL), English (LTR)

### Issues Checked Automatically

**Mobile:** Horizontal overflow, touch targets, font sizes, viewport meta, RTL/LTR
**Browser:** CSS support, broken images, SVG rendering, JS errors, layout metrics
### When This Agent Should Use Browser Testing

- Test feature-specific UI flows
- Verify component displays correctly
- Screenshot relevant dashboards
- Validate functionality in browser

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
