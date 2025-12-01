---
name: cmis-headline-generation
description: AI-powered headline generation and performance testing.
model: sonnet
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

## 🌐 Browser Testing

**📖 See:** `.claude/agents/_shared/browser-testing-integration.md`

### When This Agent Should Use Browser Testing

- Test feature-specific UI flows
- Verify component displays correctly
- Screenshot relevant dashboards
- Validate functionality in browser

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
