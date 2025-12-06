#!/bin/bash

# CMIS Multi-Tenancy Security Audit Script
# تاريخ الإنشاء: 2024-12-06
# الغرض: العثور على جميع ثغرات Multi-tenancy في CMIS

echo "======================================"
echo "🔍 CMIS Multi-Tenancy Security Audit"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
TOTAL_MIGRATIONS=0
PROTECTED_MIGRATIONS=0
TOTAL_MODELS=0
PROTECTED_MODELS=0

echo -e "${YELLOW}📊 فحص Migrations للـ RLS...${NC}"
echo "----------------------------------------"

# Count total migrations
TOTAL_MIGRATIONS=$(find database/migrations -name "*.php" -type f | wc -l)

# Find migrations with RLS
echo "Migrations مع RLS:"
for file in database/migrations/*.php; do
    if grep -q "enableRLS\|ENABLE ROW LEVEL SECURITY" "$file" 2>/dev/null; then
        ((PROTECTED_MIGRATIONS++))
        basename "$file" | sed 's/^/  ✅ /'
    fi
done

echo ""
echo "Migrations بدون RLS (خطر!):"
for file in database/migrations/*.php; do
    # Skip system migrations
    if [[ $(basename "$file") == *"create_extensions"* ]] || \
       [[ $(basename "$file") == *"create_views"* ]] || \
       [[ $(basename "$file") == *"create_sequences"* ]] || \
       [[ $(basename "$file") == *"create_functions"* ]] || \
       [[ $(basename "$file") == *"create_triggers"* ]] || \
       [[ $(basename "$file") == *"create_policies"* ]] || \
       [[ $(basename "$file") == *"create_indexes"* ]]; then
        continue
    fi

    if ! grep -q "enableRLS\|ENABLE ROW LEVEL SECURITY" "$file" 2>/dev/null; then
        echo -e "  ${RED}❌ $(basename "$file")${NC}"
    fi
done

echo ""
echo -e "${YELLOW}📊 فحص Models للـ HasOrganization trait...${NC}"
echo "----------------------------------------"

# Count total models
TOTAL_MODELS=$(find app/Models -name "*.php" -type f | wc -l)

# Find models with HasOrganization
echo "Models مع HasOrganization:"
for file in app/Models/**/*.php app/Models/*.php 2>/dev/null; do
    if [ -f "$file" ] && grep -q "use HasOrganization" "$file" 2>/dev/null; then
        ((PROTECTED_MODELS++))
        echo "$file" | sed 's|app/Models/||' | sed 's/^/  ✅ /'
    fi
done | head -20
echo "  ... و $((PROTECTED_MODELS - 20)) آخرين"

echo ""
echo "Models بدون HasOrganization (خطر!):"
UNPROTECTED_COUNT=0
for file in app/Models/**/*.php app/Models/*.php 2>/dev/null; do
    if [ -f "$file" ]; then
        # Skip concerns and base models
        if [[ "$file" == *"/Concerns/"* ]] || \
           [[ $(basename "$file") == "BaseModel.php" ]]; then
            continue
        fi

        if ! grep -q "use HasOrganization" "$file" 2>/dev/null; then
            if [ $UNPROTECTED_COUNT -lt 20 ]; then
                echo -e "  ${RED}❌ $(echo "$file" | sed 's|app/Models/||')${NC}"
            fi
            ((UNPROTECTED_COUNT++))
        fi
    fi
done
if [ $UNPROTECTED_COUNT -gt 20 ]; then
    echo "  ... و $((UNPROTECTED_COUNT - 20)) آخرين"
fi

echo ""
echo -e "${YELLOW}📊 فحص Middleware...${NC}"
echo "----------------------------------------"

MIDDLEWARE_COUNT=$(ls app/Http/Middleware/*Context*.php 2>/dev/null | wc -l)
echo "عدد Middleware للسياق: $MIDDLEWARE_COUNT"

if [ $MIDDLEWARE_COUNT -gt 1 ]; then
    echo -e "${RED}⚠️  تحذير: يوجد $MIDDLEWARE_COUNT middleware مختلفة - خطر race conditions!${NC}"
    ls app/Http/Middleware/*Context*.php 2>/dev/null | while read file; do
        if grep -q "@deprecated" "$file" 2>/dev/null; then
            echo -e "  ${YELLOW}⚠️  $(basename "$file") [DEPRECATED]${NC}"
        else
            echo "  📁 $(basename "$file")"
        fi
    done
fi

echo ""
echo -e "${YELLOW}📊 فحص الاختبارات...${NC}"
echo "----------------------------------------"

# Count multi-tenancy tests
MT_TESTS=$(grep -r "test.*multi.*tenancy\|test.*rls\|test.*organization.*isolation" tests/ 2>/dev/null | wc -l)
echo "اختبارات Multi-tenancy: $MT_TESTS"

# Check if InteractsWithRLS is used
RLS_TRAIT_USAGE=$(grep -r "use InteractsWithRLS" tests/ 2>/dev/null | wc -l)
echo "استخدام InteractsWithRLS trait: $RLS_TRAIT_USAGE"

if [ $RLS_TRAIT_USAGE -eq 0 ]; then
    echo -e "${RED}⚠️  تحذير: InteractsWithRLS trait غير مستخدم!${NC}"
fi

echo ""
echo "======================================"
echo -e "${YELLOW}📊 الملخص النهائي${NC}"
echo "======================================"

# Calculate percentages
RLS_PERCENTAGE=$((PROTECTED_MIGRATIONS * 100 / TOTAL_MIGRATIONS))
MODEL_PERCENTAGE=$((PROTECTED_MODELS * 100 / TOTAL_MODELS))

echo ""
echo "🛡️ RLS Protection:"
echo "  • Migrations: $PROTECTED_MIGRATIONS/$TOTAL_MIGRATIONS ($RLS_PERCENTAGE%)"
if [ $RLS_PERCENTAGE -lt 50 ]; then
    echo -e "    ${RED}⚠️  خطر: أقل من 50% محمي!${NC}"
fi

echo ""
echo "🏢 Model Protection:"
echo "  • Models: $PROTECTED_MODELS/$TOTAL_MODELS ($MODEL_PERCENTAGE%)"
if [ $MODEL_PERCENTAGE -lt 75 ]; then
    echo -e "    ${RED}⚠️  خطر: أقل من 75% محمي!${NC}"
fi

echo ""
echo "🔌 Middleware:"
echo "  • عدد Middleware: $MIDDLEWARE_COUNT"
if [ $MIDDLEWARE_COUNT -gt 1 ]; then
    echo -e "    ${RED}⚠️  خطر: أكثر من middleware واحد!${NC}"
fi

echo ""
echo "🧪 Testing:"
echo "  • اختبارات Multi-tenancy: $MT_TESTS"
if [ $MT_TESTS -lt 10 ]; then
    echo -e "    ${RED}⚠️  خطر: أقل من 10 اختبارات!${NC}"
fi

echo ""
# Overall score
SCORE=0
[ $RLS_PERCENTAGE -ge 90 ] && ((SCORE+=25)) || [ $RLS_PERCENTAGE -ge 50 ] && ((SCORE+=10))
[ $MODEL_PERCENTAGE -ge 90 ] && ((SCORE+=25)) || [ $MODEL_PERCENTAGE -ge 50 ] && ((SCORE+=10))
[ $MIDDLEWARE_COUNT -eq 1 ] && ((SCORE+=25))
[ $MT_TESTS -ge 50 ] && ((SCORE+=25)) || [ $MT_TESTS -ge 10 ] && ((SCORE+=10))

echo "======================================"
echo -e "${YELLOW}التقييم النهائي: $SCORE/100${NC}"

if [ $SCORE -lt 50 ]; then
    echo -e "${RED}⚠️  حرج: النظام غير آمن للإنتاج!${NC}"
elif [ $SCORE -lt 75 ]; then
    echo -e "${YELLOW}⚠️  تحذير: يحتاج تحسينات كبيرة${NC}"
else
    echo -e "${GREEN}✅ جيد: لكن يحتاج مراجعة${NC}"
fi

echo "======================================"
echo ""
echo "📝 للحصول على تقرير مفصل:"
echo "   انظر: docs/active/analysis/multi-tenancy-critical-assessment-2024-12-06.md"
echo ""