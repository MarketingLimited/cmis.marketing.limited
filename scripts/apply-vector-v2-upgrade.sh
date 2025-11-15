#!/bin/bash
# ========================================
# CMIS Vector Embeddings v2.0 Upgrade Script
# تطبيق التحديثات على قاعدة البيانات
# ========================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Database connection details
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-cmis}"
DB_USER="${DB_USER:-begin}"
DB_PASSWORD="${DB_PASSWORD:-123@Marketing@321}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}CMIS Vector Embeddings v2.0 Upgrade${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to execute SQL
execute_sql() {
    local sql_file=$1
    echo -e "${YELLOW}Executing: $sql_file${NC}"
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$sql_file"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Successfully executed: $sql_file${NC}"
    else
        echo -e "${RED}✗ Failed to execute: $sql_file${NC}"
        exit 1
    fi
    echo ""
}

# Check database connection
echo -e "${YELLOW}Checking database connection...${NC}"
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "SELECT version();" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Cannot connect to database${NC}"
    echo -e "${RED}Please check your database connection settings${NC}"
    exit 1
fi
echo ""

# Apply migration
echo -e "${YELLOW}Applying Vector Embeddings v2.0 migration...${NC}"
execute_sql "database/migrations/2025_11_15_000001_add_missing_vector_functions.sql"

# Verify installation
echo -e "${YELLOW}Verifying installation...${NC}"
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" <<EOF
SELECT cmis_knowledge.verify_installation();
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Installation verified successfully${NC}"
else
    echo -e "${RED}✗ Installation verification failed${NC}"
fi
echo ""

# Display summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Installation Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✓ process_embedding_queue() - معالجة قائمة الانتظار${NC}"
echo -e "${GREEN}✓ hybrid_search() - البحث الهجين${NC}"
echo -e "${GREEN}✓ smart_context_loader_v2() - تحميل السياق الذكي v2${NC}"
echo -e "${GREEN}✓ register_knowledge_with_vectors() - تسجيل معرفة مع vectors${NC}"
echo -e "${GREEN}✓ v_embedding_status - عرض حالة Embeddings${NC}"
echo -e "${GREEN}✓ v_intent_analysis - تحليل النوايا${NC}"
echo ""

# Display next steps
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Next Steps / الخطوات التالية${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}1. Test semantic search:${NC}"
echo -e "   SELECT * FROM cmis_knowledge.semantic_search_advanced('استراتيجيات التسويق', 'increase_sales', NULL, NULL, NULL, 5, 0.7);"
echo ""
echo -e "${YELLOW}2. Process embedding queue:${NC}"
echo -e "   SELECT cmis_knowledge.process_embedding_queue(10);"
echo ""
echo -e "${YELLOW}3. Try hybrid search:${NC}"
echo -e "   SELECT * FROM cmis_knowledge.hybrid_search('marketing campaigns', NULL, 0.3, 0.7, 10);"
echo ""
echo -e "${YELLOW}4. Check embedding status:${NC}"
echo -e "   SELECT * FROM cmis_knowledge.v_embedding_status;"
echo ""
echo -e "${YELLOW}5. Analyze intents:${NC}"
echo -e "   SELECT * FROM cmis_knowledge.v_intent_analysis;"
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Upgrade completed successfully! ✨${NC}"
echo -e "${GREEN}========================================${NC}"
