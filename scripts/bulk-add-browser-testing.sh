#!/bin/bash

###############################################################################
# Bulk Browser Testing Integration Script
#
# Adds browser testing sections to all remaining Claude Code agents
# with domain-specific use cases
#
# Usage: bash scripts/bulk-add-browser-testing.sh
###############################################################################

AGENTS_DIR=".claude/agents"
AGENTS_LIST="/tmp/agents-to-update.txt"
LOG_FILE="/tmp/browser-testing-update-log.txt"
SUCCESS_COUNT=0
FAIL_COUNT=0
SKIP_COUNT=0

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 Bulk Browser Testing Integration"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Clear log file
> "$LOG_FILE"

# Function to determine agent category and use cases
get_agent_use_cases() {
    local agent_name="$1"
    local use_cases=""

    # Meta Platform
    if [[ "$agent_name" == *"meta-"* ]]; then
        use_cases="- Test Meta Ads Manager UI integration
- Verify Facebook/Instagram ad preview rendering
- Screenshot campaign setup wizards
- Validate Meta pixel implementation displays"

    # Google Platform
    elif [[ "$agent_name" == *"google-"* ]]; then
        use_cases="- Test Google Ads UI integration
- Verify ad preview rendering (Search, Display, Shopping)
- Screenshot campaign management interface
- Validate Google Tag implementation displays"

    # TikTok Platform
    elif [[ "$agent_name" == *"tiktok-"* ]]; then
        use_cases="- Test TikTok Ads Manager integration
- Verify video ad preview rendering
- Screenshot campaign creation flows
- Validate TikTok pixel implementation displays"

    # LinkedIn Platform
    elif [[ "$agent_name" == *"linkedin-"* ]]; then
        use_cases="- Test LinkedIn Campaign Manager integration
- Verify sponsored content preview rendering
- Screenshot B2B targeting UI
- Validate LinkedIn Insight Tag displays"

    # Twitter Platform
    elif [[ "$agent_name" == *"twitter-"* ]]; then
        use_cases="- Test Twitter Ads UI integration
- Verify promoted tweet preview rendering
- Screenshot campaign setup interface
- Validate Twitter pixel implementation displays"

    # Snapchat Platform
    elif [[ "$agent_name" == *"snapchat-"* ]]; then
        use_cases="- Test Snapchat Ads Manager integration
- Verify Snap ad preview rendering
- Screenshot AR lens campaign setup
- Validate Snapchat pixel implementation displays"

    # Campaign Management
    elif [[ "$agent_name" == *"campaign"* ]]; then
        use_cases="- Test campaign management workflows
- Verify campaign dashboard displays
- Screenshot campaign creation wizards
- Validate campaign metrics visualizations"

    # Budget & Forecasting
    elif [[ "$agent_name" == *"budget"* ]] || [[ "$agent_name" == *"forecast"* ]] || [[ "$agent_name" == *"pacing"* ]]; then
        use_cases="- Test budget allocation UI
- Verify budget pacing visualizations
- Screenshot forecasting dashboards
- Validate spend tracking displays"

    # Audiences
    elif [[ "$agent_name" == *"audience"* ]]; then
        use_cases="- Test audience builder UI flows
- Verify audience segmentation displays
- Screenshot audience insights dashboards
- Validate audience size estimations"

    # Analytics & Attribution
    elif [[ "$agent_name" == *"analytics"* ]] || [[ "$agent_name" == *"attribution"* ]] || [[ "$agent_name" == *"metric"* ]]; then
        use_cases="- Test analytics dashboard rendering
- Verify attribution model visualizations
- Screenshot performance reports
- Validate metric calculation displays"

    # Automation
    elif [[ "$agent_name" == *"auto"* ]] || [[ "$agent_name" == *"automation"* ]]; then
        use_cases="- Test automation rule configuration UI
- Verify automated action status displays
- Screenshot automation workflows
- Validate automation performance metrics"

    # Testing & Experimentation
    elif [[ "$agent_name" == *"test"* ]] || [[ "$agent_name" == *"experiment"* ]] || [[ "$agent_name" == *"ab-"* ]]; then
        use_cases="- Test experiment setup wizards
- Verify A/B test variant displays
- Screenshot test results dashboards
- Validate statistical significance displays"

    # Social Media
    elif [[ "$agent_name" == *"social"* ]]; then
        use_cases="- Test social media post previews
- Verify social calendar displays
- Screenshot engagement metrics
- Validate social media publishing UI"

    # Content & Creative
    elif [[ "$agent_name" == *"content"* ]] || [[ "$agent_name" == *"creative"* ]] || [[ "$agent_name" == *"template"* ]] || [[ "$agent_name" == *"asset"* ]]; then
        use_cases="- Test content library displays
- Verify creative preview rendering
- Screenshot asset management UI
- Validate creative performance metrics"

    # AI & Insights
    elif [[ "$agent_name" == *"ai-"* ]] || [[ "$agent_name" == *"insight"* ]] || [[ "$agent_name" == *"predictive"* ]] || [[ "$agent_name" == *"smart"* ]]; then
        use_cases="- Test AI-powered recommendation displays
- Verify insight visualization dashboards
- Screenshot predictive analytics UI
- Validate AI model performance metrics"

    # Reporting
    elif [[ "$agent_name" == *"report"* ]]; then
        use_cases="- Test report generation UI
- Verify report visualization rendering
- Screenshot custom report builders
- Validate report export functionality"

    # Compliance & Security
    elif [[ "$agent_name" == *"compliance"* ]] || [[ "$agent_name" == *"security"* ]] || [[ "$agent_name" == *"fraud"* ]]; then
        use_cases="- Test compliance dashboard displays
- Verify security audit UI
- Screenshot compliance report views
- Validate security status indicators"

    # OAuth & Webhooks
    elif [[ "$agent_name" == *"oauth"* ]] || [[ "$agent_name" == *"webhook"* ]]; then
        use_cases="- Test OAuth connection flows
- Verify webhook status displays
- Screenshot platform authorization UI
- Validate connection status indicators"

    # System & Integration
    elif [[ "$agent_name" == *"integration"* ]] || [[ "$agent_name" == *"sync"* ]] || [[ "$agent_name" == *"data"* ]]; then
        use_cases="- Test integration status displays
- Verify data sync dashboards
- Screenshot connection management UI
- Validate sync status indicators"

    # Generic/Other
    else
        use_cases="- Test feature-specific UI flows
- Verify component displays correctly
- Screenshot relevant dashboards
- Validate functionality in browser"
    fi

    echo "$use_cases"
}

# Function to add browser testing section to an agent
add_browser_testing_section() {
    local agent_file="$1"
    local agent_name=$(basename "$agent_file" .md)

    # Check if already has browser testing section
    if grep -q "Browser Testing" "$agent_file"; then
        echo -e "${YELLOW}⏭️  Skipping${NC} $agent_name (already has browser testing section)"
        ((SKIP_COUNT++))
        echo "[SKIP] $agent_name - already has browser testing" >> "$LOG_FILE"
        return
    fi

    # Get domain-specific use cases
    local use_cases=$(get_agent_use_cases "$agent_name")

    # Create the browser testing section
    local browser_section="

## 🌐 Browser Testing Integration

### Capabilities Available

This agent can utilize browser-based verification for visual validation of changes.

### Available Tools

| Tool | Command | Use Case |
|------|---------|----------|
| **Playwright** | \`npx playwright screenshot [url] output.png\` | Multi-browser screenshots |
| **Puppeteer** | \`node scripts/browser-tests/puppeteer-test.js [url]\` | Full page testing |
| **Responsive** | \`node scripts/browser-tests/responsive-test.js [url]\` | Mobile/tablet/desktop |
| **Lynx** | \`lynx -dump [url]\` | Quick content extraction |

### Test Environment

- **URL**: https://cmis-test.kazaaz.com/
- **Scripts**: \`/scripts/browser-tests/\`
- **Languages**: Arabic (RTL), English (LTR)

### When This Agent Should Use Browser Testing

$use_cases

**Documentation**: \`CLAUDE.md\` → Browser Testing Environment
**Test Scripts**: \`/scripts/browser-tests/README.md\`

---

**Updated**: 2025-11-28 - Browser Testing Integration
"

    # Append to the agent file
    echo "$browser_section" >> "$agent_file"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Updated${NC} $agent_name"
        ((SUCCESS_COUNT++))
        echo "[SUCCESS] $agent_name" >> "$LOG_FILE"
    else
        echo -e "${RED}❌ Failed${NC} $agent_name"
        ((FAIL_COUNT++))
        echo "[FAIL] $agent_name" >> "$LOG_FILE"
    fi
}

# Main processing loop
echo "📝 Processing agents from: $AGENTS_LIST"
echo ""

if [ ! -f "$AGENTS_LIST" ]; then
    echo -e "${RED}❌ Error: Agent list file not found: $AGENTS_LIST${NC}"
    exit 1
fi

TOTAL_AGENTS=$(wc -l < "$AGENTS_LIST")
CURRENT=0

while IFS= read -r agent_filename; do
    ((CURRENT++))
    agent_path="$AGENTS_DIR/$agent_filename"

    if [ ! -f "$agent_path" ]; then
        echo -e "${RED}❌ Not found${NC} $agent_filename"
        ((FAIL_COUNT++))
        echo "[FAIL] $agent_filename - file not found" >> "$LOG_FILE"
        continue
    fi

    echo -ne "${BLUE}[$CURRENT/$TOTAL_AGENTS]${NC} "
    add_browser_testing_section "$agent_path"

done < "$AGENTS_LIST"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Bulk Update Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${GREEN}✅ Successfully updated: $SUCCESS_COUNT${NC}"
echo -e "${YELLOW}⏭️  Skipped (already updated): $SKIP_COUNT${NC}"
echo -e "${RED}❌ Failed: $FAIL_COUNT${NC}"
echo ""
echo "Total processed: $TOTAL_AGENTS"
echo ""
echo "Log saved to: $LOG_FILE"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exit 0
