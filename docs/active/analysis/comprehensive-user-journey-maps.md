# CMIS User Journey Maps
## Comprehensive Interface Analysis
**Last Updated:** 2025-11-22
**Version:** 1.0
**Project:** Cognitive Marketing Information System (CMIS)

---

## Executive Summary

CMIS provides **four distinct interfaces** for different user types:

1. **Web Interface** - Visual dashboard for marketing teams
2. **REST API** - Programmatic access for developers and integrations
3. **CLI (Command-Line)** - System administration and automation
4. **GPT/ChatGPT Interface** - Conversational AI-powered assistant

This document maps the complete user journey across all interfaces, explaining what users experience from start to finish.

---

## Table of Contents

- [User Types & Their Goals](#user-types--their-goals)
- [Interface 1: Web Application Journey](#interface-1-web-application-journey)
- [Interface 2: REST API Journey](#interface-2-rest-api-journey)
- [Interface 3: CLI (Command-Line) Journey](#interface-3-cli-command-line-journey)
- [Interface 4: GPT/ChatGPT Journey](#interface-4-gptchatgpt-journey)
- [Interface 5: Webhook Integration (Passive)](#interface-5-webhook-integration-passive)
- [Master User Journey Summary](#master-user-journey-summary)
- [Common Pain Points & Solutions](#common-pain-points--solutions)
- [Open Questions](#open-questions)

---

## User Types & Their Goals

### 1. Marketing Manager (Primary User)
**Who they are:** Marketing professionals managing ad campaigns across platforms
**What they want:** Track campaigns, create content, view analytics, manage team
**How they access:** Web browser (Chrome, Firefox, Safari)
**Technical skill:** Low to medium - familiar with marketing tools

### 2. API Consumer (Developer/Integration)
**Who they are:** Developers integrating CMIS with other systems
**What they want:** Automate campaign creation, sync data, fetch analytics programmatically
**How they access:** REST API via code (Python, JavaScript, etc.)
**Technical skill:** High - understands APIs, authentication, JSON

### 3. System Administrator
**Who they are:** IT staff managing the CMIS infrastructure
**What they want:** Sync platform data, process AI embeddings, generate reports, maintain system
**How they access:** Command-line terminal (SSH)
**Technical skill:** High - comfortable with terminal commands

### 4. ChatGPT/AI Assistant User
**Who they are:** Marketing users who prefer conversational interfaces
**What they want:** Ask questions about campaigns, get insights, create content via chat
**How they access:** ChatGPT with CMIS integration
**Technical skill:** Low - natural language interaction

### 5. External Platform (Passive)
**Who they are:** Ad platforms (Meta, Google, TikTok, LinkedIn, etc.)
**What they want:** Send real-time updates about ad performance, comments, messages
**How they access:** Webhooks (automated POST requests)
**Technical skill:** N/A - automated system

---

## Interface 1: Web Application Journey

### Entry Point: Login & Authentication

**The user starts here:**
1. Opens browser and navigates to CMIS URL
2. Sees a clean login page in Arabic (RTL layout)
3. Enters email and password
4. Can optionally click "Remember me"
5. Clicks "Login" button

**What happens:**
- System validates credentials
- If successful: redirected to organization selection or dashboard
- If failed: error message appears in Arabic explaining the problem

**First-time users:**
- Click "Don't have an account? Register now"
- Fill in registration form (name, email, password)
- System creates account and sends verification email
- After verification, user can log in

### Onboarding Journey (New Users)

**After first login:**
1. User sees onboarding wizard
2. Step-by-step guide shows:
   - How to create their first organization
   - How to connect advertising platforms (Meta, Google, etc.)
   - How to create their first campaign
   - Where to find analytics
3. User can skip steps or complete the full tour
4. Progress is saved - can resume later
5. After completion, lands on main dashboard

### Organization Selection & Switching

**Multi-organization users:**
1. After login, sees list of organizations they belong to
2. Each organization shows:
   - Organization name
   - User's role (Admin, Manager, Member)
   - Number of active campaigns
3. User clicks on an organization to enter
4. Dashboard loads with that organization's data

**Switching organizations:**
1. User clicks organization switcher in top navigation
2. Dropdown shows all accessible organizations
3. Click different organization
4. Page refreshes with new organization's data
5. All subsequent actions apply to selected organization

### Main Dashboard Experience

**User lands on dashboard and sees:**

**Top Section - Quick Stats Cards:**
- Number of organizations
- Active campaigns count
- Creative assets available
- KPI performance indicators

**Middle Section - Visual Charts:**
- Pie chart: Campaign distribution by status (draft, active, paused, completed)
- Bar chart: Campaigns by organization

**Performance Metrics:**
- Weekly advertising spend with progress bar
- Impressions count and trend
- Click-through rates
- Conversion statistics

**Top Campaigns Widget:**
- List of best-performing campaigns
- Each shows campaign name, organization, and performance score

**Recent Activity Feed:**
- Timeline of recent actions:
  - "New campaign created"
  - "Platform integration added"
  - "Creative asset uploaded"
  - "Analytics report generated"
- Each with timestamp and icon

**Quick Actions Bar:**
- Large, colorful buttons for common tasks:
  - Create New Campaign
  - Add Organization
  - Upload Creative Content
  - View Analytics
  - Manage Integrations
  - AI Features

**Real-time Updates:**
- Dashboard auto-refreshes every 30 seconds
- New data appears without page reload
- Notifications pop up for important events

### Campaign Management Journey

**Creating a New Campaign:**

**Step 1: Campaign Wizard Start**
1. User clicks "Create New Campaign" button
2. Chooses between:
   - Quick campaign setup (guided wizard)
   - Advanced campaign creation (full form)

**Step 2: Basic Information**
1. User enters:
   - Campaign name (e.g., "Summer Sale 2025")
   - Description (optional)
   - Start date (calendar picker appears)
   - End date (calendar picker appears)
   - Budget amount
2. Clicks "Next"

**Step 3: Target Audience**
1. User defines who should see the ads:
   - Age range (slider: 18-65)
   - Gender (checkboxes: Male, Female, All)
   - Location (searchable country/city selector)
   - Interests (multi-select dropdown)
2. System shows estimated audience size
3. Clicks "Next"

**Step 4: Platform Selection**
1. User chooses advertising platforms:
   - Meta (Facebook & Instagram)
   - Google Ads
   - TikTok
   - LinkedIn
   - Twitter/X
   - Snapchat
2. For each platform, selects connected ad accounts
3. Clicks "Next"

**Step 5: Campaign Objectives**
1. User selects campaign goal:
   - Brand Awareness
   - Traffic to Website
   - Lead Generation
   - Conversions/Sales
   - App Installs
   - Video Views
2. System adapts subsequent options based on goal
3. Clicks "Next"

**Step 6: Review & Create**
1. User sees summary of all selections
2. Can go back to edit any step
3. Can save as draft (come back later)
4. Clicks "Create Campaign"
5. Success message appears
6. Redirected to campaign detail page

**Viewing Campaign List:**
1. User clicks "Campaigns" in navigation
2. Sees table of all campaigns:
   - Campaign name
   - Status (colored badge: green=active, gray=draft, etc.)
   - Start/end dates
   - Budget vs. spent
   - Performance metrics
3. Can filter by status or search by name
4. Can sort by any column

**Viewing Single Campaign:**
1. User clicks campaign name from list
2. Opens campaign detail page showing:
   - Campaign header with status and action buttons
   - Performance summary cards (impressions, clicks, conversions)
   - Chart showing performance over time
   - List of connected ad accounts and platforms
   - Content plans associated with campaign
   - Team members assigned
3. Action buttons available:
   - Edit Campaign
   - Pause/Resume Campaign
   - Duplicate Campaign
   - Archive Campaign
   - Export Report

**Campaign Performance Dashboard:**
1. User clicks "Performance Dashboard" tab
2. Sees comprehensive analytics:
   - Time-range selector (daily, weekly, monthly, yearly)
   - Real-time metrics updating
   - Comparison with previous period
   - Platform-by-platform breakdown
   - Geographic performance map
   - Audience demographics
3. Can download charts as images
4. Can export data as Excel/PDF

### Platform Integration Journey

**Connecting an Advertising Platform:**

**Step 1: Navigate to Integrations**
1. User clicks "Integrations" in settings menu
2. Sees grid of available platforms:
   - Meta (Facebook & Instagram) - blue card
   - Google Ads - red card
   - TikTok - black card
   - LinkedIn - blue card
   - Twitter/X - black card
   - Snapchat - yellow card
3. Each card shows:
   - Platform logo and name
   - "Connected" or "Not Connected" status
   - "Connect" or "Manage" button

**Step 2: Initiate Connection**
1. User clicks "Connect" on Meta platform
2. System explains what access is needed:
   - "CMIS needs permission to:"
   - Read your ad accounts
   - Create and manage campaigns
   - View analytics and insights
3. User clicks "Authorize"

**Step 3: OAuth Flow (External Platform)**
1. Browser redirects to Meta login page
2. User logs into Meta account (if not already)
3. Meta shows permission request
4. User clicks "Allow" to grant permissions
5. Meta redirects back to CMIS

**Step 4: Account Selection**
1. CMIS shows list of ad accounts from Meta
2. User selects which accounts to sync:
   - [✓] Ad Account 1234567890
   - [✓] Ad Account 0987654321
   - [ ] Ad Account (inactive)
3. Clicks "Complete Integration"

**Step 5: Confirmation**
1. Success message appears
2. Platform card now shows "Connected" status
3. Sync begins automatically in background
4. User can view sync status and history

**Managing Connected Platform:**
1. User clicks "Manage" on connected platform
2. Can:
   - View connected accounts
   - Add/remove accounts
   - Refresh access token
   - Test connection
   - Disconnect platform
   - View sync logs

### Creative Content Management

**Uploading Creative Assets:**

**Step 1: Access Creative Library**
1. User clicks "Creative" in navigation
2. Sees grid of existing assets:
   - Images (thumbnails)
   - Videos (play icons)
   - Documents (file icons)
3. Each shows file name, size, upload date

**Step 2: Upload New Asset**
1. User clicks "Upload" button
2. Drag-and-drop zone appears or file picker
3. User selects file(s) from computer
4. Upload progress bar appears
5. After upload:
   - Thumbnail generates automatically
   - User can add metadata:
     - Title
     - Description
     - Tags
     - Associated campaign
   - Clicks "Save"

**Step 3: Use in Campaign**
1. When creating campaign content:
   - Asset picker opens
   - User searches/filters library
   - Clicks asset to select
   - Asset preview appears
   - Confirms selection

**Creating Content Plans:**

**Step 1: New Content Plan**
1. User clicks "Create Content Plan"
2. Enters:
   - Plan name
   - Associated campaign (dropdown)
   - Content type (social post, blog, ad copy, email, video script)
   - Target platforms (multi-select)

**Step 2: Content Details**
1. User provides:
   - Tone (professional, casual, humorous, etc.)
   - Key messages (bullet points)
   - Brand guidelines reference
   - Target audience description

**Step 3: AI Generation (Optional)**
1. User clicks "Generate with AI"
2. AI creates content based on inputs
3. Generated content appears in editor
4. User can:
   - Accept as-is
   - Edit manually
   - Regenerate with different tone
   - Add variations

**Step 4: Approval Workflow**
1. User submits for approval
2. Assigned approver gets notification
3. Approver reviews and:
   - Approves (content moves to ready)
   - Rejects with comments (back to creator)
   - Requests changes

**Step 5: Publishing**
1. Approved content shows "Publish" button
2. User selects:
   - Platforms to publish on
   - Schedule (now or future date/time)
3. Clicks "Publish" or "Schedule"
4. System queues for publishing
5. User gets confirmation when published

### Social Media Management

**Social Inbox Journey:**

**Step 1: View Unified Inbox**
1. User clicks "Inbox" in navigation
2. Sees list of all messages/comments from connected platforms:
   - Facebook comments
   - Instagram comments and DMs
   - LinkedIn messages
   - Twitter mentions
3. Each message shows:
   - Platform icon
   - Sender name and profile picture
   - Message preview
   - Time received
   - Unread badge

**Step 2: Respond to Message**
1. User clicks on message
2. Full conversation opens in right panel
3. User types reply in text box
4. Can add:
   - Emoji reactions
   - Attachments
   - Saved reply templates
5. Clicks "Send Reply"
6. Reply posts to original platform
7. Message marked as read

**Step 3: Assign to Team Member**
1. User clicks "Assign" button
2. Selects team member from dropdown
3. Team member gets notification
4. Message appears in their queue

**Step 4: Add Internal Note**
1. User clicks "Add Note"
2. Writes note visible only to team
3. Note saved with timestamp and author
4. Helps team coordinate responses

**Social Scheduler Journey:**

**Step 1: Create Post**
1. User clicks "Social" → "Scheduler"
2. Clicks "New Post" button
3. Composer opens with:
   - Text editor
   - Image/video uploader
   - Emoji picker
   - Hashtag suggestions
   - Link preview

**Step 2: Platform Selection**
1. User checks platforms to post on:
   - [✓] Facebook Page
   - [✓] Instagram
   - [ ] LinkedIn
   - [✓] Twitter
2. Platform-specific fields appear:
   - Twitter: Character counter
   - Instagram: Alt text for accessibility
   - LinkedIn: Article mode option

**Step 3: Scheduling**
1. User chooses publish time:
   - Publish now (immediate)
   - Schedule for specific date/time
   - Queue (next available slot)
   - Best time suggestion (AI-powered)
2. Calendar and time picker appear
3. User selects date and time

**Step 4: Preview & Publish**
1. User clicks "Preview"
2. Sees how post will look on each platform
3. Can make final edits
4. Clicks "Schedule" or "Publish Now"
5. Post added to queue
6. Confirmation message appears

**Step 5: View Scheduled Posts**
1. User clicks "Scheduled" tab
2. Sees calendar view of upcoming posts
3. Each post shows:
   - Platforms
   - Scheduled time
   - Preview
   - Status (pending, publishing, published, failed)
4. Can reschedule or cancel posts

### Analytics & Reporting

**Viewing Analytics:**

**Step 1: Access Analytics**
1. User clicks "Analytics" in navigation
2. Lands on Enterprise Analytics Hub
3. Sees high-level overview:
   - Total impressions
   - Total clicks
   - Total conversions
   - Overall ROI

**Step 2: Filter & Customize**
1. User applies filters:
   - Date range selector (last 7 days, 30 days, custom)
   - Campaign selector (all or specific)
   - Platform selector (all or specific)
   - Metric selector (checkboxes for what to show)
2. Charts update in real-time

**Step 3: Drill Down**
1. User clicks on specific campaign
2. Detailed analytics page opens:
   - Performance trend chart
   - Funnel visualization (awareness → consideration → conversion)
   - Attribution breakdown (which touchpoints led to conversions)
   - Ad set performance comparison
   - Creative performance comparison
3. Can export any chart or data table

**Step 4: Compare Campaigns**
1. User selects multiple campaigns (checkboxes)
2. Clicks "Compare"
3. Side-by-side comparison appears:
   - Metrics in table format
   - Performance charts overlaid
   - Best/worst performers highlighted
4. Can export comparison as PDF report

**Real-Time Dashboard:**

1. User clicks "Real-Time Analytics"
2. Sees live updating metrics:
   - Current active users on landing pages
   - Live conversion events
   - Ad spend per minute
   - Geographic heat map of activity
3. Auto-refreshes every 10 seconds
4. Sound notification for conversion events (optional)

**Generating Reports:**

**Step 1: Report Builder**
1. User clicks "Reports" → "Create Report"
2. Selects report type:
   - Performance Report (campaign metrics)
   - AI Insights Report (AI-powered recommendations)
   - Organization Summary (overall performance)
   - Content Analysis (content effectiveness)

**Step 2: Configure Report**
1. User selects:
   - Date range
   - Campaigns to include
   - Metrics to display
   - Chart types (bar, line, pie)
   - Include/exclude sections

**Step 3: Generate & Download**
1. User clicks "Generate Report"
2. Progress bar shows generation status
3. When ready, report appears in browser
4. Can download as:
   - PDF (for presentations)
   - Excel (for data analysis)
   - Share link (for team viewing)

**Scheduled Reports:**

1. User clicks "Schedule Report"
2. Configures:
   - Report type and settings
   - Frequency (daily, weekly, monthly)
   - Email recipients (team members)
   - Delivery time
3. Saves scheduled report
4. Reports auto-generate and email at specified times
5. User can view scheduled reports and edit/cancel

### AI Features Journey

**AI Dashboard:**

**Step 1: Access AI Features**
1. User clicks "AI" in navigation
2. Sees AI dashboard with:
   - AI usage quota (e.g., "450/500 requests this month")
   - Progress bar showing quota usage
   - Daily usage trend chart
   - Cost summary

**Step 2: AI-Powered Insights**
1. User selects campaign
2. Clicks "Get AI Insights"
3. AI analyzes campaign data
4. Returns insights like:
   - "Your Instagram ads perform 35% better on weekends"
   - "Consider increasing budget for 25-34 age group"
   - "Ad creative #3 has highest engagement"
   - "Anomaly detected: Sudden drop in CTR on Tuesday"

**Step 3: Content Generation**
1. User clicks "Generate Ad Copy"
2. Provides inputs:
   - Product/service name
   - Key benefits (bullet points)
   - Target audience
   - Tone (professional, casual, playful)
   - Platform (Facebook, Instagram, Google)
3. Clicks "Generate"
4. AI produces multiple variations
5. User can:
   - Select favorite variation
   - Edit generated content
   - Regenerate with different parameters
   - Save to content library

**Step 4: Smart Recommendations**
1. User views campaign
2. AI recommendations panel shows:
   - Budget optimization suggestions
   - Audience expansion opportunities
   - Best posting times
   - Underperforming ad sets to pause
   - High-potential keywords to add
3. User can apply recommendations with one click

**Semantic Search:**

**Step 1: Search Knowledge Base**
1. User clicks search bar
2. Types natural language query:
   - "What are our brand colors?"
   - "Show me competitor analysis for Q4"
   - "Best-performing product descriptions"

**Step 2: AI-Powered Results**
1. System uses vector search (pgvector)
2. Returns relevant knowledge items:
   - Brand guidelines document (88% relevance)
   - Market research report (75% relevance)
   - Campaign brief (62% relevance)
3. Each result shows:
   - Title and preview
   - Relevance score
   - Document type
   - Last updated date

**Step 3: Add to Knowledge Base**
1. User clicks "Add Knowledge"
2. Uploads document or pastes text
3. Selects content type:
   - Brand guideline
   - Market research
   - Competitor analysis
   - Campaign brief
   - Product information
4. Adds title, summary, tags
5. Saves - AI automatically generates embeddings
6. Document becomes searchable immediately

### Team Management Journey

**Inviting Team Members:**

**Step 1: Access Team Settings**
1. User (must be Admin) clicks organization name
2. Selects "Team" tab
3. Sees list of current team members:
   - Name, email, role
   - Last active time
   - Assigned accounts

**Step 2: Send Invitation**
1. Clicks "Invite Member" button
2. Enters:
   - Email address
   - Role (Admin, Manager, Member, Viewer)
   - Message (optional)
3. Clicks "Send Invitation"
4. Invitee receives email with invitation link

**Step 3: Accept Invitation (Invitee Side)**
1. Invitee clicks link in email
2. If no account:
   - Fills registration form
   - Creates account
3. If existing account:
   - Logs in automatically
4. Sees invitation acceptance page
5. Clicks "Accept Invitation"
6. Gets access to organization

**Step 4: Manage Team Member**
1. Admin clicks on team member
2. Can:
   - Change role
   - Assign to specific ad accounts
   - View activity log
   - Suspend account
   - Remove from organization

### Settings & Preferences

**Profile Settings:**
1. User clicks profile icon → "Settings"
2. Can update:
   - Profile picture (upload new image)
   - Display name
   - Email address
   - Password (requires current password)
   - Language preference (English, Arabic)
   - Time zone
   - Notification preferences

**Notification Settings:**
1. User clicks "Notifications" tab
2. Toggles notifications for:
   - New inbox messages
   - Campaign performance alerts
   - Team member actions
   - Report generation completed
   - Budget threshold warnings
   - AI quota alerts
3. Chooses delivery method:
   - In-app notifications
   - Email notifications
   - Both

**Integration Settings:**
1. User clicks "Integrations" tab
2. Can:
   - View all connected platforms
   - Refresh access tokens
   - Test connections
   - View webhook logs
   - Generate API keys for external access

### Error & Edge Cases

**When things go wrong:**

**Login Fails:**
- Clear error message: "Invalid email or password"
- Option to reset password
- Option to contact support

**Platform Connection Fails:**
- Error explains issue: "Meta denied access" or "Token expired"
- Suggests solution: "Reconnect your account"
- Link to troubleshooting guide

**Campaign Creation Fails:**
- Validation errors highlighted in red
- Tooltip explains each error
- Can save as draft to fix later

**AI Quota Exceeded:**
- Warning when approaching limit (90%)
- Error when limit reached
- Option to upgrade plan
- Shows when quota resets

**Sync Errors:**
- Notification: "Sync failed for Google Ads"
- View error details
- Option to retry manually
- Contact support if persists

**Upload Failures:**
- Progress bar shows error
- Explains reason: "File too large" or "Invalid format"
- Suggests solution: "Resize to under 10MB" or "Use JPG/PNG"

### Logout Journey

**Step 1: Logout**
1. User clicks profile icon
2. Clicks "Logout"
3. Confirmation prompt: "Are you sure?"
4. Clicks "Yes, Logout"

**Step 2: Session End**
1. User logged out immediately
2. All session data cleared
3. Redirected to login page
4. Success message: "You have been logged out"

**Step 3: Return Visit**
1. If "Remember Me" was checked:
   - Email pre-filled on login page
2. If not:
   - Clean login form appears

---

## Interface 2: REST API Journey

### Entry Point: API Authentication

**The developer starts here:**

**Step 1: Obtain API Key**
1. Developer logs into CMIS web interface
2. Goes to Settings → Integrations → API Keys
3. Clicks "Generate New API Key"
4. Names the key (e.g., "Production Integration")
5. Copies generated key (shown once)
6. Stores securely in environment variables

**Alternative: OAuth Flow**
1. Developer registers application
2. Gets client_id and client_secret
3. Implements OAuth authorization code flow
4. User grants permission via web browser
5. Developer exchanges code for access token
6. Uses token for API requests

### API Discovery & Documentation

**Step 1: View API Documentation**
1. Developer visits `/api/documentation` endpoint
2. Sees interactive API documentation (Swagger/OpenAPI)
3. Can:
   - Browse all available endpoints
   - See request/response schemas
   - Test endpoints directly in browser
   - Download OpenAPI spec file

**Step 2: Explore Endpoints**
Developer discovers endpoint categories:
- Authentication (`/api/auth/*`)
- Organizations (`/api/orgs/*`)
- Campaigns (`/api/orgs/{org_id}/campaigns/*`)
- Creative Assets (`/api/orgs/{org_id}/creative/*`)
- Analytics (`/api/orgs/{org_id}/analytics/*`)
- AI Features (`/api/orgs/{org_id}/ai/*`)
- Integrations (`/api/orgs/{org_id}/integrations/*`)
- Social (`/api/orgs/{org_id}/social/*`)

### Authentication Flow

**Option A: API Key Authentication**

```
1. Developer makes request with API key in header:
   GET /api/user/orgs
   Headers:
     Authorization: Bearer {api_key}
     Accept: application/json

2. System validates API key
3. If valid: returns data
4. If invalid: returns 401 Unauthorized
```

**Option B: OAuth Authentication**

```
1. Developer initiates OAuth flow
2. User grants permission
3. Developer gets access_token
4. Makes API requests:
   GET /api/orgs/{org_id}/campaigns
   Headers:
     Authorization: Bearer {access_token}
     Accept: application/json
```

### Typical API Workflow: Campaign Automation

**Scenario:** Developer wants to automatically create campaigns from their system

**Step 1: Get User Organizations**
```
Request:
  GET /api/user/orgs
  Headers:
    Authorization: Bearer {api_key}

Response:
  {
    "success": true,
    "data": [
      {
        "id": "org-uuid-123",
        "name": "Acme Marketing",
        "role": "admin"
      }
    ]
  }
```

**Step 2: Get Current Context**
```
Request:
  GET /api/context/
  Headers:
    Authorization: Bearer {api_key}

Response:
  {
    "success": true,
    "data": {
      "org_id": "org-uuid-123",
      "user_id": "user-uuid-456",
      "permissions": ["campaigns.create", "campaigns.read"]
    }
  }
```

**Step 3: Create Campaign**
```
Request:
  POST /api/orgs/org-uuid-123/campaigns
  Headers:
    Authorization: Bearer {api_key}
    Content-Type: application/json
  Body:
    {
      "name": "Summer Sale 2025",
      "description": "Promotional campaign for summer products",
      "start_date": "2025-06-01",
      "end_date": "2025-08-31",
      "budget": 50000,
      "objectives": ["conversions", "brand_awareness"],
      "target_audience": {
        "age_min": 25,
        "age_max": 45,
        "locations": ["US", "CA"],
        "interests": ["fashion", "shopping"]
      }
    }

Response:
  {
    "success": true,
    "message": "Campaign created successfully",
    "data": {
      "campaign_id": "campaign-uuid-789",
      "name": "Summer Sale 2025",
      "status": "draft",
      "created_at": "2025-05-15T10:30:00Z"
    }
  }
```

**Step 4: Upload Creative Assets**
```
Request:
  POST /api/orgs/org-uuid-123/creative/assets
  Headers:
    Authorization: Bearer {api_key}
    Content-Type: multipart/form-data
  Body:
    file: [binary image data]
    title: "Summer Product Banner"
    campaign_id: "campaign-uuid-789"

Response:
  {
    "success": true,
    "data": {
      "asset_id": "asset-uuid-101",
      "url": "https://cdn.cmis.com/assets/...",
      "thumbnail_url": "https://cdn.cmis.com/thumbs/..."
    }
  }
```

**Step 5: Create Content Plan with AI**
```
Request:
  POST /api/orgs/org-uuid-123/creative/content-plans
  Headers:
    Authorization: Bearer {api_key}
  Body:
    {
      "campaign_id": "campaign-uuid-789",
      "name": "Summer Sale Ad Copy",
      "content_type": "ad_copy",
      "target_platforms": ["facebook", "instagram"],
      "tone": "enthusiastic",
      "key_messages": [
        "50% off summer collection",
        "Limited time offer",
        "Free shipping"
      ]
    }

Response:
  {
    "success": true,
    "data": {
      "plan_id": "plan-uuid-202",
      "status": "pending_generation"
    }
  }
```

**Step 6: Generate Content with AI**
```
Request:
  POST /api/orgs/org-uuid-123/creative/content-plans/plan-uuid-202/generate
  Headers:
    Authorization: Bearer {api_key}
  Body:
    {
      "options": {
        "variations": 3,
        "max_length": 100
      }
    }

Response:
  {
    "success": true,
    "message": "Content generation started",
    "data": {
      "job_id": "job-uuid-303",
      "status": "processing"
    }
  }
```

**Step 7: Check Job Status**
```
Request:
  GET /api/jobs/job-uuid-303/status
  Headers:
    Authorization: Bearer {api_key}

Response:
  {
    "success": true,
    "data": {
      "job_id": "job-uuid-303",
      "status": "completed",
      "progress": 100,
      "result": {
        "variations": [
          "🌞 Summer Sale is HERE! Get 50% off our entire summer collection...",
          "☀️ Hot deals for hot days! Limited time: 50% off + FREE shipping...",
          "Beat the heat with our Summer Sale! Half price on everything summer..."
        ]
      }
    }
  }
```

**Step 8: Get Campaign Analytics**
```
Request:
  GET /api/orgs/org-uuid-123/campaigns/campaign-uuid-789/metrics
  Headers:
    Authorization: Bearer {api_key}
  Query Parameters:
    start_date=2025-06-01
    end_date=2025-06-30

Response:
  {
    "success": true,
    "data": {
      "impressions": 1250000,
      "clicks": 45000,
      "conversions": 2150,
      "spend": 18500.00,
      "ctr": 3.6,
      "cpc": 0.41,
      "conversion_rate": 4.78,
      "roas": 3.24
    }
  }
```

### Platform Integration via API

**Scenario:** Developer connects Meta platform programmatically

**Step 1: Get OAuth URL**
```
Request:
  GET /api/orgs/org-uuid-123/integrations/meta/auth-url
  Headers:
    Authorization: Bearer {api_key}

Response:
  {
    "success": true,
    "data": {
      "auth_url": "https://www.facebook.com/v18.0/dialog/oauth?client_id=...&redirect_uri=...",
      "state": "random-state-token-for-security"
    }
  }
```

**Step 2: User Authorizes (Web Browser)**
1. Developer redirects user to auth_url
2. User logs into Meta and grants permissions
3. Meta redirects to callback URL with code

**Step 3: Exchange Code for Tokens**
```
Request:
  POST /api/orgs/org-uuid-123/integrations/meta/callback
  Headers:
    Authorization: Bearer {api_key}
  Body:
    {
      "code": "authorization-code-from-meta",
      "state": "random-state-token-for-security"
    }

Response:
  {
    "success": true,
    "message": "Platform connected successfully",
    "data": {
      "integration_id": "integration-uuid-404",
      "platform": "meta",
      "status": "active",
      "ad_accounts": [
        {
          "id": "act_123456789",
          "name": "Main Ad Account"
        }
      ]
    }
  }
```

**Step 4: Sync Platform Data**
```
Request:
  POST /api/orgs/org-uuid-123/sync/integration-uuid-404
  Headers:
    Authorization: Bearer {api_key}

Response:
  {
    "success": true,
    "message": "Sync initiated",
    "data": {
      "sync_id": "sync-uuid-505",
      "status": "running"
    }
  }
```

### Semantic Search via API

**Scenario:** Developer wants to search knowledge base with natural language

**Step 1: Perform Semantic Search**
```
Request:
  POST /api/orgs/org-uuid-123/search/semantic
  Headers:
    Authorization: Bearer {api_key}
  Body:
    {
      "query": "What are our brand guidelines for social media?",
      "limit": 5
    }

Response:
  {
    "success": true,
    "data": {
      "results": [
        {
          "id": "knowledge-uuid-606",
          "title": "Social Media Brand Guidelines",
          "content_type": "brand_guideline",
          "relevance_score": 0.94,
          "summary": "Comprehensive guidelines for social media posts...",
          "excerpt": "...brand colors are blue (#1E3A8A) and orange (#F97316)..."
        },
        {
          "id": "knowledge-uuid-607",
          "title": "Visual Identity Standards",
          "content_type": "brand_guideline",
          "relevance_score": 0.78,
          "summary": "Logo usage, typography, and color palette..."
        }
      ],
      "total": 2
    }
  }
```

### Bulk Operations via API

**Scenario:** Developer needs to update multiple campaigns at once

**Step 1: Bulk Update Campaign Status**
```
Request:
  PATCH /api/orgs/org-uuid-123/ad-campaigns/bulk/status
  Headers:
    Authorization: Bearer {api_key}
  Body:
    {
      "campaign_ids": [
        "campaign-uuid-1",
        "campaign-uuid-2",
        "campaign-uuid-3"
      ],
      "status": "paused"
    }

Response:
  {
    "success": true,
    "data": {
      "updated": 3,
      "failed": 0,
      "results": [
        {"id": "campaign-uuid-1", "status": "paused"},
        {"id": "campaign-uuid-2", "status": "paused"},
        {"id": "campaign-uuid-3", "status": "paused"}
      ]
    }
  }
```

### Webhook Management via API

**Scenario:** Developer wants to receive real-time notifications

**Step 1: Create Webhook**
```
Request:
  POST /api/orgs/org-uuid-123/webhooks
  Headers:
    Authorization: Bearer {api_key}
  Body:
    {
      "url": "https://myapp.com/webhooks/cmis",
      "events": [
        "campaign.created",
        "campaign.status_changed",
        "analytics.threshold_reached"
      ],
      "secret": "webhook-secret-for-verification"
    }

Response:
  {
    "success": true,
    "data": {
      "webhook_id": "webhook-uuid-707",
      "url": "https://myapp.com/webhooks/cmis",
      "status": "active"
    }
  }
```

**Step 2: Receive Webhook Events**
When events occur, CMIS sends POST request to webhook URL:
```
POST https://myapp.com/webhooks/cmis
Headers:
  X-CMIS-Signature: sha256-hash-for-verification
  Content-Type: application/json
Body:
  {
    "event": "campaign.status_changed",
    "timestamp": "2025-06-15T14:30:00Z",
    "data": {
      "campaign_id": "campaign-uuid-789",
      "old_status": "active",
      "new_status": "paused",
      "reason": "budget_exceeded"
    }
  }
```

### Rate Limiting & Error Handling

**Rate Limits:**
- Standard endpoints: 60 requests per minute
- AI endpoints: 10 requests per minute
- Webhook endpoints: Unlimited (verified)

**When rate limit exceeded:**
```
Response:
  Status: 429 Too Many Requests
  Body:
    {
      "success": false,
      "message": "Rate limit exceeded",
      "retry_after": 45,
      "limit": 10,
      "remaining": 0
    }
  Headers:
    X-RateLimit-Limit: 10
    X-RateLimit-Remaining: 0
    X-RateLimit-Reset: 1685967600
    Retry-After: 45
```

**Common Error Responses:**

**Validation Error (422):**
```
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["Campaign name is required"],
    "budget": ["Budget must be a positive number"]
  }
}
```

**Not Found (404):**
```
{
  "success": false,
  "message": "Campaign not found"
}
```

**Unauthorized (401):**
```
{
  "success": false,
  "message": "Invalid or expired API key"
}
```

**Server Error (500):**
```
{
  "success": false,
  "message": "Internal server error",
  "error_id": "error-uuid-808"
}
```

### API Best Practices for Developers

**Pagination:**
```
Request:
  GET /api/orgs/org-uuid-123/campaigns?page=2&limit=20

Response:
  {
    "success": true,
    "data": [...],
    "meta": {
      "current_page": 2,
      "total_pages": 10,
      "per_page": 20,
      "total": 195
    },
    "links": {
      "first": "/api/orgs/.../campaigns?page=1",
      "prev": "/api/orgs/.../campaigns?page=1",
      "next": "/api/orgs/.../campaigns?page=3",
      "last": "/api/orgs/.../campaigns?page=10"
    }
  }
```

**Filtering:**
```
GET /api/orgs/org-uuid-123/campaigns?status=active&platform=meta&sort=-created_at
```

**Field Selection:**
```
GET /api/orgs/org-uuid-123/campaigns?fields=id,name,status,budget
```

**Including Related Resources:**
```
GET /api/orgs/org-uuid-123/campaigns?include=content_plans,analytics
```

---

## Interface 3: CLI (Command-Line) Journey

### Entry Point: SSH Access

**System Administrator starts here:**

**Step 1: Connect to Server**
```bash
# Admin opens terminal
ssh admin@cmis-server.com

# Enters password or uses SSH key
# Logs into server successfully
```

**Step 2: Navigate to Project**
```bash
cd /var/www/cmis
# or wherever CMIS is installed

# Verify Laravel installation
php artisan --version
# Output: Laravel Framework 10.x.x
```

### Available CLI Commands

**View All Available Commands:**
```bash
php artisan list

# Output shows organized command groups:
# - cmis:*          (CMIS-specific commands)
# - sync:*          (Platform synchronization)
# - generate:*      (Report and embedding generation)
# - audit:*         (System auditing)
# - cleanup:*       (Maintenance tasks)
```

### Common Admin Workflows

**Workflow 1: Sync Platform Data**

**Scenario:** Admin needs to sync all Meta ads data for all organizations

**Step 1: View Sync Command Help**
```bash
php artisan sync:platform --help

# Output:
# Description:
#   Syncs data from a specified external platform
#
# Usage:
#   sync:platform <platform> [options]
#
# Arguments:
#   platform              Platform to sync (meta, google, tiktok, etc.)
#
# Options:
#   --org=ORG            One or more organization IDs (optional)
#   --type=TYPE          Type of data (all, campaigns, posts) [default: "all"]
```

**Step 2: Run Sync for All Meta Organizations**
```bash
php artisan sync:platform meta --type=all

# Output:
# 🚀 Starting sync for platform: [meta]
# Found 5 active integration(s) to process.
# Processing integration for Org ID: org-uuid-123
#    -> Syncing campaigns...
#    ✅ Synced 45 campaigns.
#    -> Syncing posts...
#    ✅ Synced 230 posts.
# Processing integration for Org ID: org-uuid-456
#    -> Syncing campaigns...
#    ✅ Synced 12 campaigns.
#    -> Syncing posts...
#    ✅ Synced 89 posts.
# ... (continues for all orgs)
# 🏁 Sync finished for platform: [meta]
```

**Step 3: Sync Specific Organization**
```bash
php artisan sync:platform google --org=org-uuid-123 --type=campaigns

# Output:
# 🚀 Starting sync for platform: [google]
# Targeting specific organizations: org-uuid-123
# Found 1 active integration(s) to process.
# Processing integration for Org ID: org-uuid-123
#    -> Syncing campaigns...
#    ✅ Synced 28 campaigns.
# 🏁 Sync finished for platform: [google]
```

**Workflow 2: Process AI Embeddings**

**Scenario:** Admin needs to generate AI embeddings for knowledge base items

**Step 1: View Pending Embeddings**
```bash
php artisan cmis:process-embeddings --help

# Output:
# Description:
#   Process pending CMIS knowledge embeddings
#
# Options:
#   --batch-size=SIZE    Number of items per batch [default: 100]
#   --continuous         Run continuously (daemon mode)
```

**Step 2: Process in Single Batch**
```bash
php artisan cmis:process-embeddings --batch-size=50

# Output:
# Starting CMIS Embedding Processing...
# Batch Size: 50
#
# Processing item: knowledge-uuid-101
# Processing item: knowledge-uuid-102
# Processing item: knowledge-uuid-103
# ...
#
# ┌────────────────────┬───────┐
# │ Metric             │ Value │
# ├────────────────────┼───────┤
# │ Total Processed    │ 50    │
# │ Successful         │ 48    │
# │ Failed             │ 2     │
# │ Success Rate       │ 96%   │
# └────────────────────┴───────┘
#
# Processing completed.
```

**Step 3: Run in Continuous Mode (Background)**
```bash
# Start continuous processing (runs as daemon)
nohup php artisan cmis:process-embeddings --continuous > /var/log/cmis-embeddings.log 2>&1 &

# Output:
# [1] 12345  (process ID)

# Check if running
ps aux | grep cmis:process-embeddings

# View live logs
tail -f /var/log/cmis-embeddings.log
```

**Workflow 3: Generate Reports**

**Scenario:** Admin needs to generate weekly reports for all organizations

**Step 1: Generate Performance Report**
```bash
php artisan generate:performance-report --help

# Output:
# Description:
#   Generate performance reports for campaigns
#
# Options:
#   --org=ORG           Organization ID (optional, default: all)
#   --period=PERIOD     Report period (daily, weekly, monthly) [default: "weekly"]
#   --email             Send report via email
```

**Step 2: Generate & Email Weekly Reports**
```bash
php artisan generate:performance-report --period=weekly --email

# Output:
# Generating weekly performance reports...
#
# Organization: Acme Marketing (org-uuid-123)
#   ✅ Report generated: reports/acme-marketing-2025-06-15.pdf
#   ✅ Email sent to: admin@acme.com
#
# Organization: Digital Agency (org-uuid-456)
#   ✅ Report generated: reports/digital-agency-2025-06-15.pdf
#   ✅ Email sent to: manager@digital.com
#
# Total reports generated: 2
# Total emails sent: 2
```

**Workflow 4: System Maintenance**

**Scenario:** Admin performs routine maintenance tasks

**Step 1: Clean Up Old Data**
```bash
php artisan cleanup:system-data --help

# Output:
# Description:
#   Clean up old logs, temporary files, and expired sessions
#
# Options:
#   --days=DAYS         Keep data from last N days [default: 30]
#   --dry-run           Show what would be deleted without deleting
```

**Step 2: Preview Cleanup (Dry Run)**
```bash
php artisan cleanup:system-data --days=30 --dry-run

# Output:
# Cleanup Analysis (Dry Run)
#
# Items to be cleaned:
#   - Logs older than 30 days: 15,234 records
#   - Expired sessions: 1,245 records
#   - Temporary files: 89 files (2.3 GB)
#   - Webhook logs older than 30 days: 45,123 records
#
# Total space to be freed: 2.5 GB
#
# Run without --dry-run to actually delete.
```

**Step 3: Execute Cleanup**
```bash
php artisan cleanup:system-data --days=30

# Output:
# Starting cleanup process...
#
# ✅ Deleted 15,234 old log records
# ✅ Deleted 1,245 expired sessions
# ✅ Deleted 89 temporary files (2.3 GB freed)
# ✅ Deleted 45,123 old webhook logs
#
# Cleanup completed successfully!
# Total space freed: 2.5 GB
```

**Workflow 5: Database Operations**

**Scenario:** Admin needs to execute SQL queries for debugging

**Step 1: Open Database Console**
```bash
# Connect to PostgreSQL database
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis

# Output:
# psql (14.5)
# Type "help" for help.
#
# cmis=#
```

**Step 2: Query Database**
```sql
-- Check total campaigns
SELECT COUNT(*) FROM cmis.campaigns;

-- Output:
--  count
-- -------
--    245

-- Check campaigns by status
SELECT status, COUNT(*)
FROM cmis.campaigns
GROUP BY status;

-- Output:
--   status   | count
-- -----------+-------
--  active    |   120
--  paused    |    45
--  draft     |    60
--  completed |    20
```

**Step 3: Execute Custom SQL File**
```bash
# Run predefined SQL script
php artisan db:execute-sql --file=/path/to/query.sql

# Or use psql directly
psql -h 127.0.0.1 -U begin -d cmis -f /path/to/query.sql
```

**Workflow 6: Audit & Monitoring**

**Scenario:** Admin checks system health and security

**Step 1: Generate Audit Report**
```bash
php artisan audit:status

# Output:
# System Audit Report
# Generated: 2025-06-15 14:30:00
#
# ┌────────────────────────────┬─────────┬─────────┐
# │ Check                      │ Status  │ Details │
# ├────────────────────────────┼─────────┼─────────┤
# │ Database Connection        │ ✅ OK   │ 12ms    │
# │ Redis Connection           │ ✅ OK   │ 3ms     │
# │ RLS Policies               │ ✅ OK   │ All OK  │
# │ API Rate Limiting          │ ✅ OK   │ Active  │
# │ AI Quota Usage             │ ⚠️ WARN │ 85%     │
# │ Disk Space                 │ ✅ OK   │ 45% used│
# │ Failed Jobs                │ ⚠️ WARN │ 3 jobs  │
# │ Platform Integrations      │ ✅ OK   │ All OK  │
# └────────────────────────────┴─────────┴─────────┘
#
# Warnings: 2
# Critical: 0
```

**Step 2: Check Specific Component**
```bash
# Check for alerts
php artisan audit:check-alerts

# Output:
# Active Alerts:
#   [WARN] AI quota at 85% for org-uuid-123
#   [WARN] 3 jobs failed in last hour
#   [INFO] Campaign budget threshold reached for campaign-uuid-789
```

**Workflow 7: Scheduled Tasks**

**Scenario:** Admin sets up automated jobs

**Step 1: View Scheduled Tasks**
```bash
php artisan schedule:list

# Output:
# ┌──────────────────────────────────┬─────────────┬────────────┐
# │ Command                          │ Interval    │ Next Run   │
# ├──────────────────────────────────┼─────────────┼────────────┤
# │ sync:platform meta --type=all    │ Every hour  │ 15:00:00   │
# │ sync:platform google --type=all  │ Every hour  │ 15:00:00   │
# │ cmis:process-embeddings          │ Every 5 min │ 14:35:00   │
# │ generate:analytics-report        │ Daily 6am   │ Tomorrow   │
# │ cleanup:expired-sessions         │ Daily 2am   │ Tomorrow   │
# │ backup:database                  │ Daily 3am   │ Tomorrow   │
# └──────────────────────────────────┴─────────────┴────────────┘
```

**Step 2: Run Scheduler Manually (Test)**
```bash
php artisan schedule:run

# Output:
# Running scheduled command: sync:platform meta --type=all
# Running scheduled command: cmis:process-embeddings
# Completed 2 scheduled tasks.
```

**Step 3: Set Up Cron Job (Production)**
```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /var/www/cmis && php artisan schedule:run >> /dev/null 2>&1

# Save and exit
# Now Laravel scheduler runs every minute and executes due tasks
```

**Workflow 8: Cache Management**

**Scenario:** Admin clears and warms up caches

**Step 1: Clear All Caches**
```bash
php artisan optimize:clear

# Output:
# Configuration cache cleared!
# Route cache cleared!
# View cache cleared!
# Application cache cleared!
# Compiled services and packages files removed!
```

**Step 2: Rebuild Caches**
```bash
php artisan optimize

# Output:
# Configuration cached successfully!
# Routes cached successfully!
# Files cached successfully!
```

**Step 3: Clear Specific Cache**
```bash
# Clear only application cache (Redis/File)
php artisan cache:clear

# Clear specific organization's cache
php artisan cache:clear --tags=org:org-uuid-123
```

---

## Interface 4: GPT/ChatGPT Journey

### Entry Point: ChatGPT Integration

**User starts conversation in ChatGPT:**

**Initial Connection:**
1. User opens ChatGPT (web or app)
2. Types: "I want to check my CMIS campaigns"
3. ChatGPT recognizes CMIS integration (via custom GPT or plugin)
4. Prompts for authentication if needed
5. User authorizes CMIS access (OAuth flow)
6. ChatGPT connects to CMIS API

### Conversational Workflows

**Workflow 1: Campaign Information**

**User Query:**
```
User: "Show me my active campaigns"
```

**Behind the Scenes:**
1. ChatGPT calls CMIS API:
   ```
   GET /api/gpt/campaigns?status=active
   Headers:
     Authorization: Bearer {user_token}
   ```

2. CMIS returns campaign data

3. ChatGPT formats response conversationally

**ChatGPT Response:**
```
You have 3 active campaigns:

1. **Summer Sale 2025**
   - Budget: $50,000 (spent $18,500 so far)
   - Running: June 1 - August 31
   - Performance: 45,000 clicks, 2,150 conversions
   - ROAS: 3.24x

2. **Product Launch - Widget Pro**
   - Budget: $25,000 (spent $8,200)
   - Running: May 15 - July 15
   - Performance: 22,300 clicks, 890 conversions
   - ROAS: 2.89x

3. **Brand Awareness Q2**
   - Budget: $15,000 (spent $12,100)
   - Running: April 1 - June 30
   - Performance: 1.2M impressions, 18,000 clicks
   - CTR: 1.5%

Would you like details on any specific campaign?
```

**Follow-up Conversation:**
```
User: "Tell me more about the Summer Sale campaign"

ChatGPT: [Calls GET /api/gpt/campaigns/{id}]

"Here are the details for **Summer Sale 2025**:

**Overview:**
- Status: Active
- Platforms: Meta (Facebook & Instagram), Google Ads
- Objective: Drive conversions and sales
- Start: June 1, 2025
- End: August 31, 2025

**Performance (June 1-15):**
- Impressions: 1.25M
- Clicks: 45,000 (CTR: 3.6%)
- Conversions: 2,150 (Rate: 4.78%)
- Spend: $18,500
- Revenue: $59,940
- ROAS: 3.24x (great!)

**Audience:**
- Age: 25-45
- Locations: US, Canada
- Interests: Fashion, Shopping, Summer Activities

**Top Performing Ad:**
"☀️ Hot deals for hot days! 50% off + FREE shipping"
- 12,500 clicks
- 780 conversions
- ROAS: 4.1x

Would you like to see analytics, adjust budget, or create similar campaigns?"
```

**Workflow 2: Creating Campaign via Conversation**

**User starts:**
```
User: "I want to create a new campaign for our fall collection"
```

**ChatGPT responds:**
```
Great! I'll help you create a fall collection campaign. Let me ask you a few questions:

1. What should we call this campaign?
```

**User replies:**
```
User: "Fall Fashion 2025"
```

**ChatGPT continues:**
```
Perfect! **Fall Fashion 2025** it is.

2. What's your budget for this campaign?
```

**User:**
```
User: "Let's start with $40,000"
```

**ChatGPT:**
```
Got it, $40,000 budget.

3. When should the campaign run?
```

**User:**
```
User: "September 1st through November 30th"
```

**ChatGPT:**
```
Excellent, September 1 - November 30, 2025.

4. Which platforms do you want to advertise on?
   - Meta (Facebook & Instagram)
   - Google Ads
   - TikTok
   - LinkedIn
   - All of the above
```

**User:**
```
User: "Meta and TikTok"
```

**ChatGPT:**
```
Perfect! Meta and TikTok.

5. What's your main objective?
   - Brand Awareness
   - Website Traffic
   - Lead Generation
   - Conversions/Sales
```

**User:**
```
User: "Conversions and sales"
```

**ChatGPT:**
```
Great choice!

Here's what I'm about to create:

**Campaign Summary:**
- Name: Fall Fashion 2025
- Budget: $40,000
- Dates: September 1 - November 30, 2025
- Platforms: Meta, TikTok
- Objective: Conversions/Sales
- Status: Will start as Draft (you can activate later)

Should I create this campaign?
```

**User:**
```
User: "Yes, create it"
```

**ChatGPT:**
```
[Calls POST /api/gpt/campaigns with data]

✅ Campaign created successfully!

Your **Fall Fashion 2025** campaign is ready as a draft.

**Next steps:**
1. Add creative assets (images, videos)
2. Define your target audience
3. Create ad copy
4. Review and activate

Would you like me to help with any of these?
```

**Workflow 3: Getting AI Insights**

**User query:**
```
User: "Give me insights on my Summer Sale campaign performance"
```

**ChatGPT:**
```
[Calls GET /api/gpt/campaigns/{id}/analytics and /api/gpt/ai-insights]

**AI Insights for Summer Sale 2025:**

📊 **Performance Analysis:**
Your campaign is performing well overall with a 3.24x ROAS.

🎯 **Key Findings:**
1. **Instagram ads outperform Facebook by 35%**
   - Instagram ROAS: 4.1x
   - Facebook ROAS: 2.7x
   - Recommendation: Shift 20% of budget from Facebook to Instagram

2. **Weekend performance is significantly better**
   - Weekend CTR: 4.8%
   - Weekday CTR: 2.9%
   - Recommendation: Increase bids on weekends

3. **Age group 25-34 has highest conversion rate**
   - 25-34: 6.2% conversion rate
   - 35-44: 3.8% conversion rate
   - Recommendation: Create dedicated audience for 25-34

4. **Ad creative #3 ("Hot deals for hot days") is your star performer**
   - 62% higher CTR than average
   - 48% higher conversion rate
   - Recommendation: Create more variations of this style

⚠️ **Anomalies Detected:**
- Unusual CTR drop on Tuesday, June 13 (-45%)
- Possible cause: Platform outage
- Impact: Minimal (1 day only)

💡 **Action Items:**
1. Reallocate $2,000 from Facebook to Instagram
2. Increase weekend bid adjustments by 15%
3. Create lookalike audience based on 25-34 converters
4. Test 2-3 variations of top-performing ad

Would you like me to implement any of these recommendations?
```

**Workflow 4: Content Generation**

**User request:**
```
User: "Generate ad copy for my Fall Fashion campaign targeting women 25-40"
```

**ChatGPT:**
```
[Calls POST /api/gpt/content-plans with AI generation request]

I'll create some ad copy variations for you. Give me just a moment...

✅ Here are 5 ad copy variations for **Fall Fashion 2025**:

**Variation 1 - Aspirational:**
"🍂 Fall in love with fall fashion. Discover our new collection designed for the modern woman who values style and comfort. Shop the latest trends before they're gone. Free shipping on orders over $50."

**Variation 2 - Urgent:**
"⏰ Limited Time! Our Fall Collection just dropped and items are selling fast. Don't miss your chance to refresh your wardrobe with cozy sweaters, chic boots, and must-have accessories. 30% off this week only!"

**Variation 3 - Benefit-Focused:**
"Look effortlessly stylish this fall. Our curated collection features versatile pieces that transition from office to evening. Premium fabrics, timeless designs, sizes 0-18. Your perfect fall wardrobe awaits."

**Variation 4 - Social Proof:**
"⭐ Rated 4.8/5 by 10,000+ customers. See why women everywhere are choosing our fall collection. Flattering fits, superior quality, sustainable materials. Join the style revolution. Shop now!"

**Variation 5 - Playful:**
"PSL season is here, and so is our fall collection! 🎃 From oversized cardigans perfect for pumpkin patches to sleek blazers for fall date nights - we've got your autumn style covered. Treat yourself!"

**Character counts:**
- Variation 1: 246 chars (great for Facebook)
- Variation 2: 243 chars (great for Instagram)
- Variation 3: 228 chars (great for Google)
- Variation 4: 213 chars (great for all platforms)
- Variation 5: 251 chars (great for TikTok/Instagram)

Which variation(s) would you like to use, or should I generate more options?
```

**Workflow 5: Search Knowledge Base**

**User query:**
```
User: "What are our brand color guidelines?"
```

**ChatGPT:**
```
[Calls POST /api/gpt/knowledge/search with semantic search]

Based on your brand guidelines:

**Primary Brand Colors:**
- **Deep Blue:** #1E3A8A (Primary)
  - Use for: Headers, CTAs, brand elements
  - Represents: Trust, professionalism

- **Vibrant Orange:** #F97316 (Accent)
  - Use for: Highlights, special offers, CTAs
  - Represents: Energy, creativity

**Secondary Colors:**
- **Light Gray:** #F3F4F6 (Background)
- **Dark Gray:** #1F2937 (Text)
- **White:** #FFFFFF (Negative space)

**Usage Guidelines:**
- Maintain 4.5:1 contrast ratio for accessibility
- Use orange sparingly (max 20% of design)
- Blue should dominate brand materials
- Never use blue and orange at 50/50 ratio

**Social Media Specific:**
- Profile backgrounds: Deep blue
- CTA buttons: Vibrant orange
- Text overlays: White with 50% opacity blue background

Need the full brand guidelines document or help applying these to a specific project?
```

**Workflow 6: Quick Actions**

**Simple commands:**

```
User: "Pause my Product Launch campaign"

ChatGPT: [Calls PATCH /api/gpt/campaigns/{id}/status]
✅ I've paused the "Product Launch - Widget Pro" campaign.
It will stop showing ads immediately.
Would you like to resume it later or make changes first?
```

```
User: "What's my AI usage this month?"

ChatGPT: [Calls GET /api/gpt/ai/quota]
📊 Your AI usage for June 2025:
- Requests: 387 / 500 (77%)
- Remaining: 113 requests
- Resets: July 1, 2025
- Estimated cost: $18.50

You're on track! Need to increase quota?
```

```
User: "Show me today's stats"

ChatGPT: [Calls GET /api/gpt/analytics/overview]
📈 Today's Overview (June 15, 2025):

- **Impressions:** 45,230
- **Clicks:** 1,823 (CTR: 4.0%)
- **Conversions:** 89 (Rate: 4.9%)
- **Spend:** $1,245.80
- **Revenue:** $4,180.50
- **ROAS:** 3.36x

Compared to yesterday: +12% conversions, +8% ROAS

Your best performer today: Summer Sale campaign
```

**Workflow 7: Conversational Context**

**ChatGPT remembers conversation context:**

```
User: "How's my Summer Sale campaign doing?"

ChatGPT: [Shows Summer Sale performance]

User: "Can you compare it with last year?"

ChatGPT: [Knows "it" = Summer Sale, fetches historical data]
**Summer Sale 2025 vs 2024:**

| Metric | 2025 (current) | 2024 (final) | Change |
|--------|----------------|--------------|--------|
| Spend | $18,500 | $45,000 | -59% (partial) |
| ROAS | 3.24x | 2.1x | +54% 🎉 |
| Conversions | 2,150 | 3,890 | -45% (partial) |
| Conv. Rate | 4.78% | 2.9% | +65% 🎉 |

You're on track to beat last year's performance!
If current pace continues, you'll end with:
- Final spend: ~$51,000 (+13%)
- Projected conversions: ~5,900 (+52%)
- Projected ROAS: ~3.24x (+54%)

User: "What made the difference?"

ChatGPT: [Analyzes differences]
Great question! The main improvements from 2024 to 2025:

1. **Better audience targeting** (+40% impact)
   - 2024: Broad 18-65 targeting
   - 2025: Focused 25-45 with interests
   - Result: Higher quality traffic

2. **Improved creative** (+30% impact)
   - 2024: Generic product photos
   - 2025: Lifestyle images + video
   - Result: Better engagement

3. **Platform optimization** (+20% impact)
   - 2024: Heavy Facebook focus
   - 2025: Balanced Meta + Instagram
   - Result: Better ROAS

4. **AI-powered bid management** (+10% impact)
   - 2024: Manual bidding
   - 2025: Automated with AI optimization
   - Result: Better cost efficiency

Should I save these learnings for future campaigns?
```

### Error Handling in GPT Interface

**When API fails:**
```
User: "Show my campaigns"

ChatGPT:
I apologize, but I'm having trouble connecting to CMIS right now.
The system might be temporarily unavailable.

Would you like me to:
1. Try again in a moment
2. Help you with something else
3. Show you how to access CMIS directly

(Error ID: err-uuid-909 - you can share this with support)
```

**When authentication expires:**
```
User: "Create a campaign"

ChatGPT:
Your CMIS session has expired. For security, please re-authenticate:

[Provides OAuth link]

After you re-authorize, I'll remember what you were doing and we can continue creating your campaign.
```

**When quota exceeded:**
```
User: "Generate 50 ad variations"

ChatGPT:
I can help with that, but I noticed you have 113 AI requests remaining this month.
Generating 50 variations would use approximately 50 requests.

Options:
1. Generate 10 variations now (10 requests)
2. Proceed with 50 (leaves 63 requests for the month)
3. Generate 50 next month when quota resets

What would you prefer?
```

---

## Interface 5: Webhook Integration (Passive)

### How External Platforms Communicate with CMIS

**This is a "passive" interface - CMIS receives data automatically from platforms**

### Webhook Flow Overview

**What happens:**
1. User connects Meta/Google/TikTok to CMIS (via web or API)
2. Platform sets up webhook to notify CMIS of events
3. When something happens on platform (new comment, ad performance update, etc.)
4. Platform sends POST request to CMIS webhook URL
5. CMIS receives, verifies, processes, and stores data
6. User sees updated data in CMIS without manual sync

### Platform-Specific Webhooks

**Meta (Facebook & Instagram) Webhooks:**

**Verification Request (Initial Setup):**
```
When CMIS registers webhook with Meta:

GET https://cmis.com/api/webhooks/meta
Query Parameters:
  hub.mode=subscribe
  hub.challenge=random-string
  hub.verify_token=secret-token

CMIS verifies token and responds:
  Status: 200 OK
  Body: random-string (echo back challenge)

Meta confirms webhook is active.
```

**Event Notifications:**
```
When user comments on Facebook ad:

POST https://cmis.com/api/webhooks/meta
Headers:
  X-Hub-Signature-256: sha256=hash-of-payload
  Content-Type: application/json
Body:
  {
    "object": "instagram",
    "entry": [
      {
        "id": "instagram-account-id",
        "time": 1686932400,
        "changes": [
          {
            "field": "comments",
            "value": {
              "id": "comment-id-123",
              "from": {
                "id": "user-id",
                "username": "john_doe"
              },
              "text": "Love this product!",
              "created_time": 1686932400
            }
          }
        ]
      }
    ]
  }

CMIS processing:
1. Verifies signature using shared secret
2. Extracts comment data
3. Stores in unified_inbox table
4. Sends notification to assigned team member
5. Responds: 200 OK (acknowledges receipt)
```

**Google Ads Webhooks:**

**Performance Update:**
```
When campaign metrics update:

POST https://cmis.com/api/webhooks/google
Headers:
  X-Goog-Channel-ID: channel-uuid
  X-Goog-Resource-State: update
Body:
  {
    "kind": "api#channel",
    "resourceId": "campaign-resource-id",
    "metrics": {
      "impressions": 12500,
      "clicks": 450,
      "conversions": 23,
      "cost_micros": 185000000
    },
    "timestamp": "2025-06-15T14:30:00Z"
  }

CMIS processing:
1. Verifies Google signature
2. Updates campaign metrics in database
3. Recalculates ROAS, CTR, etc.
4. Triggers alerts if thresholds crossed
5. Updates real-time dashboard
6. Responds: 200 OK
```

**TikTok Ads Webhooks:**

**Ad Approval Status:**
```
When ad is reviewed:

POST https://cmis.com/api/webhooks/tiktok
Headers:
  X-TikTok-Signature: signature-hash
Body:
  {
    "event": "ad_review_complete",
    "ad_id": "ad-id-456",
    "advertiser_id": "advertiser-id",
    "status": "approved",
    "timestamp": 1686932400
  }

CMIS processing:
1. Verifies TikTok signature
2. Updates ad status in database
3. Notifies campaign manager via email/in-app
4. If approved, marks ad as ready to run
5. Responds: 200 OK
```

**LinkedIn Webhooks:**

**Lead Form Submission:**
```
When someone submits lead form:

POST https://cmis.com/api/webhooks/linkedin
Headers:
  X-LinkedIn-Signature: signature
Body:
  {
    "event_type": "lead_form_response",
    "form_response_id": "response-uuid",
    "campaign_id": "campaign-id",
    "lead_data": {
      "first_name": "Jane",
      "last_name": "Smith",
      "email": "jane@example.com",
      "company": "Tech Corp",
      "title": "Marketing Director"
    },
    "timestamp": "2025-06-15T14:30:00Z"
  }

CMIS processing:
1. Verifies LinkedIn signature
2. Creates lead record in CRM
3. Increments campaign conversion count
4. Sends lead notification to sales team
5. Triggers automated follow-up email
6. Responds: 200 OK
```

**Twitter/X Webhooks:**

**Tweet Engagement:**
```
When user engages with promoted tweet:

POST https://cmis.com/api/webhooks/twitter
Headers:
  X-Twitter-Webhooks-Signature: sha256-signature
Body:
  {
    "event": "tweet_engagement",
    "tweet_id": "tweet-id-789",
    "engagement_type": "like",
    "user": {
      "id": "user-id",
      "screen_name": "user_handle"
    },
    "timestamp": 1686932400
  }

CMIS processing:
1. Verifies Twitter signature
2. Updates engagement metrics
3. Stores for analytics
4. Updates real-time dashboard
5. Responds: 200 OK
```

**Snapchat Webhooks:**

**Campaign Budget Alert:**
```
When campaign reaches budget threshold:

POST https://cmis.com/api/webhooks/snapchat
Headers:
  X-Snapchat-Signature: signature
Body:
  {
    "event_type": "budget_threshold_reached",
    "campaign_id": "campaign-uuid",
    "ad_account_id": "account-id",
    "budget_spent_percentage": 80,
    "remaining_budget": 2000,
    "timestamp": "2025-06-15T14:30:00Z"
  }

CMIS processing:
1. Verifies Snapchat signature
2. Creates alert in system
3. Notifies campaign manager
4. Updates budget status on dashboard
5. Suggests budget increase if ROAS is good
6. Responds: 200 OK
```

### Webhook Security

**How CMIS Verifies Webhooks:**

**Step 1: Signature Verification**
```php
// Pseudo-code showing verification process

function verifyWebhook($platform, $payload, $signature) {
    // Get platform's secret key
    $secret = getSecret($platform);

    // Calculate expected signature
    $expected = hash_hmac('sha256', $payload, $secret);

    // Compare signatures (timing-safe)
    if (hash_equals($expected, $signature)) {
        return true; // Valid webhook
    }

    return false; // Invalid - reject
}
```

**Step 2: Timestamp Validation**
```php
// Reject old webhooks (prevent replay attacks)
function isTimestampValid($timestamp) {
    $now = time();
    $age = $now - $timestamp;

    // Reject if older than 5 minutes
    return $age <= 300;
}
```

**Step 3: Idempotency Check**
```php
// Prevent processing duplicate webhooks
function isAlreadyProcessed($webhookId) {
    // Check database for webhook ID
    return DB::exists('webhook_logs', ['webhook_id' => $webhookId]);
}
```

### Webhook Failure Handling

**When webhook processing fails:**

**Automatic Retry (Platform Side):**
```
Platform sends webhook → CMIS returns 500 error
Platform waits 1 minute → retries
CMIS returns 500 error again
Platform waits 5 minutes → retries
CMIS returns 200 OK → success
```

**CMIS Logging:**
```
Every webhook logged in database:
- webhook_id (unique ID)
- platform (meta, google, etc.)
- event_type (comment, metric_update, etc.)
- payload (JSON)
- signature (for verification)
- status (success, failed, retrying)
- error_message (if failed)
- processed_at (timestamp)
- retries_count
```

**Admin Review Failed Webhooks:**
```bash
# CLI command to view failed webhooks
php artisan audit:check-alerts

# Output:
# Recent Webhook Failures:
#   [ERROR] Meta webhook failed: Invalid signature
#   [ERROR] Google webhook failed: Timeout processing
#   [WARN] TikTok webhook retried 3 times before success
```

---

## Master User Journey Summary

### The Complete CMIS Experience (10 Key Steps)

**For Marketing Managers using Web Interface:**

1. **Discover & Sign Up**
   - Find CMIS through search, referral, or marketing
   - Register for account with email/password
   - Verify email address
   - Complete onboarding wizard

2. **Set Up Organization**
   - Create first organization
   - Invite team members
   - Set preferences (currency, timezone, language)

3. **Connect Advertising Platforms**
   - Navigate to Integrations
   - Connect Meta, Google, TikTok, LinkedIn, etc. via OAuth
   - Select ad accounts to sync
   - Wait for initial data sync

4. **Explore Dashboard**
   - View performance overview
   - See active campaigns
   - Check recent activity
   - Understand quick actions

5. **Create First Campaign**
   - Use campaign wizard
   - Define objectives, budget, dates
   - Select platforms and audiences
   - Save as draft or activate

6. **Create & Manage Content**
   - Upload creative assets
   - Generate ad copy with AI
   - Create content plans
   - Submit for approval

7. **Monitor Performance**
   - View real-time analytics
   - Compare campaigns
   - Get AI-powered insights
   - Adjust budgets based on ROAS

8. **Engage with Audience**
   - Respond to comments in unified inbox
   - Schedule social posts
   - Manage team conversations
   - Track response times

9. **Generate Reports**
   - Create performance reports
   - Schedule automated reports
   - Export to PDF/Excel
   - Share with stakeholders

10. **Optimize & Scale**
    - Apply AI recommendations
    - Duplicate successful campaigns
    - Adjust targeting and budgets
    - Expand to new platforms

---

**For Developers using API:**

1. **Discover API**
   - Find API documentation
   - Review endpoints and capabilities
   - Understand authentication options

2. **Authenticate**
   - Generate API key or implement OAuth
   - Test authentication
   - Store credentials securely

3. **Explore API**
   - Read interactive documentation
   - Test endpoints in browser
   - Understand request/response formats

4. **Implement Integration**
   - Write code to call API
   - Handle authentication
   - Parse JSON responses
   - Implement error handling

5. **Automate Campaign Creation**
   - Build workflow to create campaigns
   - Upload assets programmatically
   - Generate content with AI API
   - Schedule publishing

6. **Fetch Analytics**
   - Poll analytics endpoints
   - Parse performance data
   - Store in own database
   - Build custom dashboards

7. **Set Up Webhooks**
   - Register webhook URL
   - Implement signature verification
   - Process real-time events
   - Update own system

8. **Handle Errors**
   - Implement retry logic
   - Handle rate limits
   - Log API errors
   - Monitor API health

9. **Optimize Performance**
   - Implement caching
   - Use field selection
   - Batch operations where possible
   - Monitor API usage

10. **Maintain Integration**
    - Keep up with API changes
    - Monitor for errors
    - Update when new features added
    - Provide support to users

---

**For System Admins using CLI:**

1. **Access Server**
   - SSH into server
   - Navigate to CMIS directory
   - Verify installation

2. **Understand Commands**
   - List available artisan commands
   - Read command help
   - Understand options

3. **Set Up Scheduled Tasks**
   - Configure cron jobs
   - Test scheduler
   - Monitor execution logs

4. **Sync Platform Data**
   - Run manual syncs when needed
   - Monitor sync status
   - Handle sync failures

5. **Process AI Embeddings**
   - Start embedding processor
   - Monitor progress
   - Handle failures

6. **Generate Reports**
   - Run report generation commands
   - Email reports to users
   - Archive old reports

7. **Maintain System**
   - Clean up old data
   - Clear caches
   - Optimize database
   - Monitor disk space

8. **Audit & Monitor**
   - Run health checks
   - Review security alerts
   - Check failed jobs
   - Monitor API usage

9. **Troubleshoot Issues**
   - View logs
   - Execute SQL queries
   - Test connections
   - Fix configuration

10. **Plan Capacity**
    - Monitor resource usage
    - Plan for growth
    - Optimize performance
    - Scale infrastructure

---

**For ChatGPT Users:**

1. **Initiate Conversation**
   - Open ChatGPT
   - Ask about CMIS
   - Authenticate when prompted

2. **Explore Capabilities**
   - Ask what ChatGPT can help with
   - Learn available commands
   - Understand limitations

3. **View Campaign Data**
   - Ask to see campaigns
   - Request performance metrics
   - Get comparisons

4. **Create Campaign via Conversation**
   - State intention to create campaign
   - Answer questions step by step
   - Review and confirm

5. **Generate Content**
   - Request ad copy generation
   - Specify parameters conversationally
   - Review variations
   - Select favorites

6. **Get AI Insights**
   - Ask for campaign analysis
   - Request recommendations
   - Understand anomalies

7. **Take Quick Actions**
   - Pause/resume campaigns
   - Check quotas
   - View daily stats

8. **Search Knowledge**
   - Ask natural language questions
   - Get relevant information
   - Access brand guidelines

9. **Learn & Iterate**
   - Ask follow-up questions
   - Dive deeper into topics
   - Build on previous answers

10. **Collaborate with AI**
    - Use ChatGPT as assistant
    - Combine with web interface for complex tasks
    - Leverage for routine queries

---

## Common Pain Points & Solutions

### Pain Point 1: "I don't understand why my campaign isn't performing"

**What users experience:**
- Campaign running but low ROAS
- Don't know what to adjust
- Overwhelmed by metrics

**CMIS Solutions:**
- AI Insights automatically identify issues
- Plain language explanations
- Specific actionable recommendations
- Comparison with successful campaigns
- ChatGPT can explain in conversation

**Example Journey:**
```
User views campaign → sees low ROAS → clicks "Get AI Insights"
→ AI explains: "Your Facebook ads underperform Instagram by 40%"
→ Recommends: "Shift budget to Instagram"
→ User clicks "Apply" → budget automatically adjusted
```

---

### Pain Point 2: "Managing multiple platforms is time-consuming"

**What users experience:**
- Logging into 5+ platforms daily
- Copying data between systems
- Missing notifications
- Inconsistent reporting

**CMIS Solutions:**
- Single dashboard for all platforms
- Unified inbox for all messages/comments
- Automated syncing
- Consolidated reporting
- Webhook integration for real-time updates

**Example Journey:**
```
User logs into CMIS once → sees all platforms
→ Unified inbox shows Facebook comment + Instagram DM
→ Replies to both from same screen
→ Analytics show Meta + Google + TikTok combined
```

---

### Pain Point 3: "Creating ad content takes too long"

**What users experience:**
- Writer's block
- A/B testing requires manual variations
- Design inconsistencies
- Brand guideline violations

**CMIS Solutions:**
- AI content generation
- Multiple variations instantly
- Brand guideline integration
- Templates and saved replies
- ChatGPT for conversational creation

**Example Journey:**
```
User needs ad copy → describes product to AI
→ AI generates 5 variations
→ User picks favorite → edits slightly
→ Saves to content library
→ Total time: 3 minutes instead of 30
```

---

### Pain Point 4: "I miss important alerts and updates"

**What users experience:**
- Campaign budget exhausted without notice
- Comments go unanswered
- Token expiration breaks sync
- Performance drops unnoticed

**CMIS Solutions:**
- Real-time webhook notifications
- In-app + email alerts
- Budget threshold warnings
- AI anomaly detection
- Team assignment & escalation

**Example Journey:**
```
Campaign reaches 80% budget → webhook triggers
→ CMIS sends alert to manager
→ Email + in-app notification
→ Manager increases budget from notification
→ Campaign continues without interruption
```

---

### Pain Point 5: "Team collaboration is chaotic"

**What users experience:**
- Don't know who's handling what
- Duplicate responses to customers
- No visibility into team activity
- Approval bottlenecks

**CMIS Solutions:**
- Team management with roles
- Inbox assignment system
- Activity feed
- Approval workflows
- Internal notes and comments

**Example Journey:**
```
Customer comments on ad → appears in unified inbox
→ Manager assigns to team member
→ Team member gets notification
→ Replies and marks resolved
→ Manager sees in activity feed
```

---

### Pain Point 6: "API integration is complex"

**What developers experience:**
- Unclear documentation
- Complex authentication
- Unknown endpoints
- Debugging difficulties

**CMIS Solutions:**
- Interactive API documentation
- Clear examples for every endpoint
- Test directly in browser
- Detailed error messages
- OpenAPI spec download

**Example Journey:**
```
Developer finds API docs → browses endpoints
→ Tests GET /campaigns in browser
→ Sees example request/response
→ Downloads OpenAPI spec
→ Generates client library
→ Implements in 1 hour instead of 1 day
```

---

### Pain Point 7: "System maintenance is manual and error-prone"

**What admins experience:**
- Remembering to sync platforms
- Manual cache clearing
- Forgot to generate reports
- Inconsistent backups

**CMIS Solutions:**
- Laravel scheduler (cron)
- Automated syncs every hour
- Scheduled reports
- Self-healing caches
- CLI commands for everything

**Example Journey:**
```
Admin sets up cron once
→ Scheduler runs every minute
→ Syncs platforms hourly
→ Generates reports daily
→ Cleans up weekly
→ Admin only intervenes for issues
```

---

### Pain Point 8: "I don't know if I'm getting good ROI from AI features"

**What users experience:**
- Paying for AI quota
- Not sure if AI suggestions help
- Quota runs out unexpectedly
- Hard to track AI impact

**CMIS Solutions:**
- AI usage dashboard
- Cost tracking
- Quota warnings
- A/B test AI vs manual
- ChatGPT for quota-aware suggestions

**Example Journey:**
```
User checks AI dashboard
→ Sees: 450/500 requests used
→ Tracks cost: $18.50
→ Views impact: Campaigns with AI have 25% higher ROAS
→ Decides to upgrade quota
→ ROI clearly positive
```

---

## Open Questions

### Questions for Product Team

1. **Multi-language Support:**
   - Current UI is Arabic-RTL, does it support English/LTR?
   - How does language switching work?
   - Are AI responses multilingual?

2. **User Roles & Permissions:**
   - What specific permissions exist (Admin, Manager, Member, Viewer)?
   - Can custom roles be created?
   - How granular is access control?

3. **Data Retention:**
   - How long is historical data kept?
   - Are there limits on analytics date ranges?
   - What happens to data when organization is deleted?

4. **Billing & Quotas:**
   - What are the pricing tiers?
   - What happens when AI quota is exceeded (hard stop or overage)?
   - How are additional team members billed?

5. **Platform Coverage:**
   - Are all 6 platforms (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat) fully supported?
   - Any platform limitations?
   - Plans for additional platforms (Pinterest, Reddit)?

6. **Mobile Experience:**
   - Is there a mobile app?
   - Is web interface mobile-responsive?
   - Can ChatGPT integration work on mobile?

7. **Data Export:**
   - Can users export all their data?
   - What formats are supported for bulk export?
   - Is there an import feature?

8. **Compliance & Privacy:**
   - GDPR compliance features?
   - Data residency options?
   - Privacy controls for team members?

9. **Onboarding Completion:**
   - Can onboarding be skipped entirely?
   - Can it be reset/restarted?
   - Are there different onboarding paths for different roles?

10. **API Versioning:**
    - Is API versioned (v1, v2)?
    - How are breaking changes communicated?
    - Deprecation timeline?

### Questions About User Behavior

1. **Typical Session Length:**
   - How long do users spend in CMIS daily?
   - Most common workflows?
   - Peak usage times?

2. **Feature Adoption:**
   - What percentage use AI features?
   - Is ChatGPT integration heavily used?
   - Are webhooks commonly set up?

3. **Team Size:**
   - Average team size per organization?
   - How many organizations per user?
   - Collaboration patterns?

4. **Content Creation:**
   - Do users prefer AI-generated or manual content?
   - How many variations do they typically generate?
   - Approval workflow usage?

5. **Platform Preferences:**
   - Which platforms are most connected?
   - Do users manage all platforms or selective?
   - Platform-specific workflows?

### Questions About Technical Implementation

1. **Real-time Updates:**
   - How is dashboard auto-refresh implemented (polling, WebSockets)?
   - Are there any latency issues?
   - How is real-time sync handled?

2. **Background Jobs:**
   - What queue system is used (Redis, database)?
   - How are failed jobs handled?
   - Job retry policies?

3. **AI Processing:**
   - Which AI provider (OpenAI, Gemini, both)?
   - How are embeddings stored and queried?
   - Vector search performance?

4. **Database Scaling:**
   - How is RLS performance at scale?
   - Partitioning strategy for metrics?
   - Index optimization?

5. **Error Recovery:**
   - What happens if webhook signature verification fails repeatedly?
   - How are platform API outages handled?
   - Automatic retry mechanisms?

---

## Appendix: Journey Map Quick Reference

### Web Interface Touchpoints
- Login page
- Dashboard (main hub)
- Campaign list & detail pages
- Content library & creator
- Unified inbox
- Social scheduler
- Analytics dashboards
- Team management
- Settings panels
- AI features dashboard

### API Endpoints (Key Categories)
- `/api/auth/*` - Authentication
- `/api/user/orgs` - User organizations
- `/api/orgs/{org_id}/campaigns/*` - Campaign management
- `/api/orgs/{org_id}/creative/*` - Content & assets
- `/api/orgs/{org_id}/analytics/*` - Performance data
- `/api/orgs/{org_id}/ai/*` - AI features
- `/api/orgs/{org_id}/integrations/*` - Platform connections
- `/api/orgs/{org_id}/inbox/*` - Unified inbox
- `/api/orgs/{org_id}/social/*` - Social posting
- `/api/gpt/*` - ChatGPT-specific endpoints

### CLI Commands (Key Categories)
- `sync:platform` - Sync platform data
- `cmis:process-embeddings` - AI embeddings
- `generate:*` - Report generation
- `audit:*` - Health checks & auditing
- `cleanup:*` - Maintenance
- `db:execute-sql` - Database operations

### Webhook Endpoints
- `/api/webhooks/meta` - Meta (Facebook, Instagram, WhatsApp)
- `/api/webhooks/google` - Google Ads
- `/api/webhooks/tiktok` - TikTok Ads
- `/api/webhooks/linkedin` - LinkedIn Ads
- `/api/webhooks/twitter` - Twitter/X Ads
- `/api/webhooks/snapchat` - Snapchat Ads

---

## Document Metadata

**Created:** 2025-11-22
**Author:** Claude Code Analysis
**Audience:** Product managers, designers, developers, stakeholders
**Purpose:** Comprehensive understanding of all user journeys across all interfaces
**Format:** Non-technical, narrative-focused
**Length:** ~25,000 words
**Last Updated:** 2025-11-22

---

**End of User Journey Maps Document**
