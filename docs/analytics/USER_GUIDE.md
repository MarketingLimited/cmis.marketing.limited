# CMIS Analytics Dashboards - User Guide

**Welcome to CMIS Analytics!** This guide will help you navigate and make the most of your analytics dashboards.

---

## 📍 Quick Navigation

- **Enterprise Hub**: Your central analytics command center
- **Real-Time Dashboard**: Live campaign performance metrics
- **Campaign Analytics**: Deep-dive into individual campaign ROI
- **KPI Dashboard**: Track your key performance indicators
- **Alerts**: Stay informed with real-time notifications

---

## 🏠 Enterprise Analytics Hub

**Access**: `/analytics/enterprise` or click "Enterprise Dashboard" in the navigation

The Enterprise Hub is your one-stop destination for all analytics, combining multiple dashboards in a tabbed interface.

### What You'll See:

#### 🎯 Quick Stats (Top Cards)
- **Active Campaigns**: Number of currently running campaigns
- **Real-Time Performance**: Live metrics indicator
- **KPI Monitoring**: Health score summary
- **Active Alerts**: Unread notifications count

#### 📊 Three Main Tabs:

**1. Real-Time Dashboard Tab**
   - View current performance across all campaigns
   - Key metrics: Impressions, Clicks, CTR, Spend
   - Updated automatically every 30 seconds
   - Click "View Full Real-Time Dashboard" for detailed view

**2. KPI Dashboard Tab**
   - Organization health score (0-100)
   - Status distribution: Exceeded, On Track, At Risk, Off Track
   - Quick overview of all KPIs
   - Click "View Full KPI Dashboard" for detailed view

**3. Campaign Overview Tab**
   - Grid of all active campaigns
   - Quick links to analytics and campaign details
   - Create new campaign button

#### 🔔 Notification Bell (Bottom Right)
- Red badge shows unread alert count
- Click to open alert panel
- Filter by severity: All, Critical, High
- Acknowledge or resolve alerts directly

---

## ⚡ Real-Time Dashboard

**Access**: `/analytics/realtime`

Monitor your campaign performance in real-time with auto-refreshing metrics.

### Key Features:

#### Time Window Selector
Choose your view:
- **1 Minute**: Ultra-real-time (very recent activity)
- **5 Minutes**: Short-term trends
- **15 Minutes**: Recent performance
- **1 Hour**: Hourly overview (default)

#### Auto-Refresh Control
- **ON**: Dashboard updates every 30 seconds automatically
- **OFF**: Manual refresh only (click "Refresh" button)

### Understanding Your Metrics:

#### Organization Totals (Top Cards)
- **Total Impressions**: How many times your ads were shown
- **Total Clicks**: Number of clicks received
- **Total Conversions**: Actions completed (purchases, sign-ups, etc.)
- **Total Spend**: Money invested in campaigns

#### Derived Metrics (Second Row)
- **CTR (Click-Through Rate)**: (Clicks ÷ Impressions) × 100
  - Good CTR: 2-5% depending on industry
  - Higher is generally better
- **CPC (Cost Per Click)**: Spend ÷ Clicks
  - Lower is better
  - Compare against industry benchmarks
- **Conversion Rate**: (Conversions ÷ Clicks) × 100
  - Indicates how effective your landing pages are
  - Higher percentages mean better performance

#### Campaign Performance Chart
- Visual comparison of all active campaigns
- Bar chart showing key metrics side-by-side
- Quickly identify top and underperforming campaigns

#### Active Campaigns Table
- Detailed breakdown for each campaign
- Click "View Details" to access full campaign analytics

---

## 📈 Campaign Analytics

**Access**: `/analytics/campaign/{campaign-id}` or click "Analytics" from any campaign

Deep-dive into individual campaign performance with comprehensive insights.

### Navigation:

#### Date Range Selector
- Choose custom start and end dates
- Analyze specific time periods
- Compare different date ranges

#### Attribution Model Selector
Choose how to credit conversions:
- **Last-Click**: Credit to final touchpoint (default for most platforms)
- **First-Click**: Credit to initial touchpoint (awareness campaigns)
- **Linear**: Equal credit across all touchpoints (holistic view)
- **Time-Decay**: More credit to recent touchpoints (long sales cycles)
- **Position-Based**: Credit to first and last touchpoints (40-20-40 model)
- **Data-Driven**: Machine learning-based attribution (most accurate)

### Five Analytics Tabs:

#### 1️⃣ Overview Tab
**Quick Summary Cards:**
- Total Spend: Budget invested
- Total Revenue: Income generated
- Profit: Revenue minus spend
- ROI: Return on investment percentage

**Profitability Status:**
- **HIGHLY PROFITABLE**: ROI > 100%
- **PROFITABLE**: ROI > 0%
- **BREAK EVEN**: ROI ≈ 0%
- **UNPROFITABLE**: ROI < 0%
- **HIGHLY UNPROFITABLE**: ROI < -50%

**Break-Even Point**: Shows how much revenue needed to cover costs

#### 2️⃣ ROI Analysis Tab
**ROI Breakdown Chart:**
- Visual representation of spend vs. profit
- Doughnut chart for easy comparison

**Financial Metrics:**
- **Gross Profit Margin**: (Revenue - Cost) ÷ Revenue × 100
- **Net Profit Margin**: Final profit as percentage of revenue
- **ROAS (Return on Ad Spend)**: Revenue ÷ Spend
  - ROAS of 2.0 = $2 earned for every $1 spent
  - ROAS < 1.0 = Losing money
  - ROAS > 3.0 = Excellent performance

#### 3️⃣ Attribution Tab
**Channel Insights:**
- See which marketing channels drive the most conversions
- Understand customer journey touchpoints
- Optimize budget allocation

**How to Use:**
1. Select attribution model from dropdown
2. Review pie chart showing channel distribution
3. Check contribution percentage for each channel
4. Compare touchpoints and attributed conversions

**Example Insight:**
"Google Ads contributes 40% with 500 touchpoints and 20 conversions"
→ Google Ads is a strong performer, consider increasing budget

#### 4️⃣ Lifetime Value (LTV) Tab
**Key Metrics:**
- **Average LTV**: Expected revenue per customer over their lifetime
- **Total Customer Value**: LTV × number of customers
- **Customer Acquisition Cost (CAC)**: Cost to acquire one customer
- **LTV/CAC Ratio**: Customer value vs. acquisition cost
  - **3:1 or higher**: Healthy and sustainable
  - **1:1**: Breaking even (not profitable)
  - **< 1:1**: Losing money on each customer
- **Payback Period**: Days to recover acquisition cost

**Optimization Tips:**
- Increase LTV through upselling, cross-selling, retention
- Reduce CAC through targeting and conversion optimization
- Aim for LTV/CAC ratio of 3:1 or higher

#### 5️⃣ Projections Tab
**30-Day Forecast:**
- Predicted spend for next 30 days
- Projected revenue based on current trends
- Estimated ROI

**Confidence Levels:**
- **High (>80%)**: Reliable forecast based on strong data
- **Medium (50-80%)**: Moderate confidence, some uncertainty
- **Low (<50%)**: Significant uncertainty, limited data

**Chart Comparison:**
- Bar chart comparing current vs. projected metrics
- Helps with budget planning and goal setting

---

## 📊 KPI Dashboard

**Access**: `/analytics/kpis`

Monitor your Key Performance Indicators with real-time tracking and health scores.

### Organization Health Score
**What It Is:**
- Overall performance score from 0-100
- Based on all active KPIs
- Higher is better

**Health Labels:**
- **Excellent (80-100)**: 💚 Everything performing great
- **Good (60-79)**: 💙 On track, minor improvements possible
- **Fair (40-59)**: 💛 Needs attention in some areas
- **Poor (20-39)**: 🧡 Significant issues, action required
- **Critical (0-19)**: ❤️ Urgent intervention needed

### Status Distribution
**Four Status Categories:**

1. **Exceeded** 🎯 (Green)
   - Performance surpassing targets
   - Continue current strategies

2. **On Track** ✅ (Blue)
   - Meeting targets successfully
   - Maintain current approach

3. **At Risk** ⚠️ (Yellow)
   - Below warning threshold
   - Review and adjust strategies

4. **Off Track** ❌ (Red)
   - Significantly below target
   - Immediate action required

### Reading KPI Cards

Each KPI card shows:
- **KPI Name**: What's being measured (e.g., "Conversion Rate")
- **Status Badge**: Visual indicator with icon and color
- **Current Value**: Your actual performance
- **Target Value**: Your goal
- **Progress Bar**: Visual progress percentage
- **Gap to Target**: How far from goal (positive or negative)
- **Period**: Time frame for measurement

**Example:**
```
Conversion Rate                    Status: At Risk ⚠️
Current: 8.5%          Target: 10.0%
Progress: [████████░░] 85%
Gap to Target: +1.5%
Period: Last 30 days
```

### Taking Action

**If KPI is At Risk or Off Track:**
1. Click through to campaign details
2. Review recent changes that may have impacted performance
3. Check attribution data for channel performance
4. Adjust targeting, creative, or bidding strategies
5. Monitor daily for improvement

---

## 🔔 Alerts & Notifications

**Access**: Click the bell icon (top right or bottom right)

Stay informed about important events and anomalies.

### Alert Types:

1. **Budget Exceeded** 💰
   - Campaign spent more than allocated budget
   - Action: Review campaign settings, pause if necessary

2. **Performance Drop** 📉
   - Significant decrease in key metrics
   - Action: Investigate recent changes, review creative

3. **Anomaly Detected** 🔍
   - Unusual pattern in data detected by AI
   - Action: Review anomaly details, verify data accuracy

4. **Spend Spike** 💸
   - Sudden increase in spending
   - Action: Check for bid changes or increased competition

5. **Zero Conversions** 🚫
   - Campaign running but not generating conversions
   - Action: Review tracking setup, check landing pages

6. **Impression Drop** 👁️
   - Significant decrease in ad impressions
   - Action: Check bid strategy, review ad approval status

### Severity Levels:

- **Critical** 🔴: Immediate attention required
- **High** 🟠: Address within hours
- **Medium** 🟡: Review within 24 hours
- **Low** 🔵: Informational, no urgency

### Managing Alerts:

**Acknowledge**:
- Mark as "seen" without taking action yet
- Useful when you need time to investigate
- Alert stays in system for tracking

**Resolve**:
- Mark issue as fixed
- Removes from active alert list
- Keeps record in history

**Filtering**:
- Click severity buttons (All, Critical, High) to filter view
- Focus on most urgent items first

### Alert Best Practices:

1. **Check daily**: Review alerts every morning
2. **Prioritize by severity**: Address critical alerts first
3. **Document actions**: Add notes when resolving
4. **Set up notifications**: Enable browser notifications for critical alerts
5. **Don't ignore patterns**: Multiple similar alerts may indicate systemic issue

---

## 💡 Tips for Success

### Monitoring Strategy

**Daily Tasks:**
- [ ] Check Real-Time Dashboard for current performance
- [ ] Review active alerts and take action on critical items
- [ ] Monitor top 3-5 campaigns for any unusual activity

**Weekly Tasks:**
- [ ] Review KPI Dashboard health score
- [ ] Analyze campaign ROI in detail
- [ ] Compare attribution across different models
- [ ] Check LTV metrics and customer value

**Monthly Tasks:**
- [ ] Deep-dive into all campaign analytics
- [ ] Review projections and update targets
- [ ] Analyze trends across full month
- [ ] Adjust strategy based on insights

### Optimization Workflow

1. **Identify**: Use Real-Time Dashboard to spot performance issues
2. **Analyze**: Use Campaign Analytics to understand root causes
3. **Plan**: Review projections to set realistic goals
4. **Execute**: Make changes based on data insights
5. **Monitor**: Track KPIs to verify improvements
6. **Repeat**: Continuous optimization cycle

### Common Scenarios

**Scenario: Campaign ROI is negative**
1. Go to Campaign Analytics → ROI tab
2. Check if spend is unusually high or revenue too low
3. Review Attribution tab to see which channels underperform
4. Check LTV tab - maybe customers are valuable long-term
5. Review Projections - will it improve?
6. Decision: Optimize or pause campaign

**Scenario: KPI health score dropped**
1. Go to KPI Dashboard
2. Identify which KPIs are at risk or off track
3. Click through to related campaigns
4. Review recent changes in those campaigns
5. Check Real-Time Dashboard for immediate trends
6. Take corrective action

**Scenario: Multiple alerts for one campaign**
1. Open Notification Center
2. Review all alerts for patterns
3. Click through to Campaign Analytics
4. Investigate in Overview tab first
5. Deep-dive into specific tabs based on alert types
6. Make informed decision (pause, optimize, or continue monitoring)

---

## ❓ FAQ

**Q: How often does data update?**
A: Real-time data refreshes every 30-60 seconds. Historical data may have a few minutes delay.

**Q: Why don't I see my campaign?**
A: Campaigns must have activity in the selected time window to appear. Try expanding your time range.

**Q: What's a good ROI percentage?**
A: It varies by industry, but generally: ROI > 100% is excellent, 50-100% is good, 0-50% needs improvement, negative ROI is unprofitable.

**Q: Which attribution model should I use?**
A: Start with Linear for a balanced view. Use Data-Driven if you have significant data. First-Click for awareness campaigns, Last-Click for direct response.

**Q: How is health score calculated?**
A: It's a weighted average of all KPIs based on their importance and current performance against targets.

**Q: Can I export dashboard data?**
A: Export functionality coming soon. Currently, you can take screenshots or copy visible data.

**Q: Why is my LTV/CAC ratio low?**
A: Either customer lifetime value is too low (increase retention, upselling) or acquisition cost is too high (optimize targeting, improve conversion rates).

**Q: What if I see an anomaly alert?**
A: Review the specific metric and time frame. Verify data accuracy, check for external factors (holidays, events), and investigate campaign changes.

---

## 🆘 Getting Help

**Need assistance?**
1. Check this User Guide first
2. Review tooltips (hover over ⓘ icons in the interface)
3. Contact your account manager
4. Email support: support@cmis.com
5. Check system status: status.cmis.com

**Reporting Issues:**
- Take a screenshot of the issue
- Note the exact time it occurred
- Include campaign ID or URL if relevant
- Describe what you expected vs. what happened

---

## 🎓 Learning Resources

**Recommended Reading:**
- Understanding Marketing Attribution Models
- ROI vs. ROAS: What's the Difference?
- Customer Lifetime Value Calculations
- KPI Setting Best Practices

**Video Tutorials:**
- Getting Started with CMIS Analytics (10 min)
- Advanced ROI Analysis (15 min)
- Attribution Modeling Explained (12 min)
- KPI Dashboard Deep Dive (8 min)

---

**Last Updated**: 2025-11-21
**Version**: 1.0

Happy analyzing! 📊🚀
