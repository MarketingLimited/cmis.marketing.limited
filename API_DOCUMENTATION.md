# CMIS Marketing - Complete API Documentation

**Version:** 1.0.0
**Base URL:** `https://your-domain.com/api`
**Authentication:** Bearer Token (Laravel Sanctum)

---

## 📋 Table of Contents

1. [Authentication](#authentication)
2. [Platform Integrations](#platform-integrations)
3. [Data Synchronization](#data-synchronization)
4. [Content Publishing](#content-publishing)
5. [Ad Campaign Management](#ad-campaign-management)
6. [Unified Inbox](#unified-inbox)
7. [Unified Comments](#unified-comments)
8. [Analytics & Reporting](#analytics-reporting)
9. [Webhooks](#webhooks)
10. [Response Formats](#response-formats)

---

## 🔐 Authentication

### Register
```http
POST /api/auth/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secure_password",
  "password_confirmation": "secure_password"
}
```

**Response:**
```json
{
  "success": true,
  "user": { "user_id": "...", "name": "John Doe", "email": "john@example.com" },
  "token": "1|abc123..."
}
```

### Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "secure_password"
}
```

### Get Current User
```http
GET /api/auth/me
Authorization: Bearer {token}
```

---

## 🔌 Platform Integrations

### Get Available Platforms
```http
GET /api/orgs/{org_id}/integrations/platforms
```

**Response:**
```json
{
  "success": true,
  "platforms": [
    {
      "id": "meta",
      "name": "Meta (Facebook & Instagram)",
      "supports": ["posts", "comments", "messages", "ads", "oauth"]
    },
    {
      "id": "tiktok",
      "name": "TikTok",
      "supports": ["posts", "comments", "ads", "oauth"]
    }
  ]
}
```

### Get Connected Platforms
```http
GET /api/orgs/{org_id}/integrations
```

**Response:**
```json
{
  "success": true,
  "integrations": [
    {
      "integration_id": "uuid",
      "platform": "meta",
      "external_account_name": "My Facebook Page",
      "is_active": true,
      "last_sync_at": "2025-01-15T10:30:00Z"
    }
  ],
  "total": 1
}
```

### Connect Platform (OAuth)

**Step 1: Get Auth URL**
```http
GET /api/orgs/{org_id}/integrations/{platform}/auth-url
```

**Response:**
```json
{
  "success": true,
  "auth_url": "https://platform.com/oauth?client_id=...",
  "platform": "meta"
}
```

**Step 2: Handle Callback**
```http
POST /api/orgs/{org_id}/integrations/{platform}/callback
```

**Request Body:**
```json
{
  "code": "oauth_authorization_code",
  "state": "state_token"
}
```

### Connect Platform (API Key)

For platforms like WooCommerce, WordPress:

```http
POST /api/orgs/{org_id}/integrations/{platform}/connect
```

**Request Body (WooCommerce):**
```json
{
  "store_url": "https://mystore.com",
  "consumer_key": "ck_...",
  "consumer_secret": "cs_..."
}
```

**Request Body (WordPress):**
```json
{
  "site_url": "https://mysite.com",
  "username": "admin",
  "application_password": "xxxx xxxx xxxx xxxx"
}
```

### Disconnect Platform
```http
DELETE /api/orgs/{org_id}/integrations/{integration_id}
```

### Test Connection
```http
POST /api/orgs/{org_id}/integrations/{integration_id}/test
```

---

## 🔄 Data Synchronization

### Sync All Platforms
```http
POST /api/orgs/{org_id}/sync/all
```

**Response:**
```json
{
  "success": true,
  "message": "Sync jobs dispatched for all platforms",
  "integrations_count": 5,
  "jobs_dispatched": 20
}
```

### Sync Specific Integration
```http
POST /api/orgs/{org_id}/sync/{integration_id}
```

**Request Body:**
```json
{
  "sync_types": ["posts", "comments", "messages", "campaigns"]
}
```

### Sync Posts Only
```http
POST /api/orgs/{org_id}/sync/{integration_id}/posts
```

### Sync Comments Only
```http
POST /api/orgs/{org_id}/sync/{integration_id}/comments
```

### Sync Messages Only
```http
POST /api/orgs/{org_id}/sync/{integration_id}/messages
```

### Sync Campaigns Only
```http
POST /api/orgs/{org_id}/sync/{integration_id}/campaigns
```

### Get Sync Status
```http
GET /api/orgs/{org_id}/sync/{integration_id}/status
```

**Response:**
```json
{
  "success": true,
  "integration": {
    "integration_id": "uuid",
    "platform": "meta",
    "is_active": true,
    "last_sync_at": "2025-01-15T10:30:00Z"
  },
  "recent_syncs": [...]
}
```

### Get Sync History
```http
GET /api/orgs/{org_id}/sync/history?limit=50
```

---

## 📝 Content Publishing

### Publish Content Immediately
```http
POST /api/orgs/{org_id}/publishing/publish-now
```

**Request Body:**
```json
{
  "content": "Check out our new product! 🚀",
  "title": "Product Launch",
  "integration_ids": ["integration-uuid-1", "integration-uuid-2"],
  "media_urls": ["https://example.com/image.jpg"]
}
```

**Response:**
```json
{
  "success": true,
  "content_item_id": "uuid",
  "results": [
    {
      "integration_id": "uuid",
      "platform": "meta",
      "success": true,
      "platform_post_id": "123456789"
    },
    {
      "integration_id": "uuid",
      "platform": "twitter",
      "success": true,
      "platform_post_id": "987654321"
    }
  ]
}
```

### Schedule Content
```http
POST /api/orgs/{org_id}/publishing/schedule
```

**Request Body:**
```json
{
  "content": "Coming soon! Stay tuned 👀",
  "title": "Teaser",
  "integration_ids": ["integration-uuid"],
  "scheduled_at": "2025-01-20T15:00:00Z",
  "media_urls": []
}
```

**Response:**
```json
{
  "success": true,
  "content_item_id": "uuid",
  "scheduled_posts": [
    {
      "schedule_id": "uuid",
      "integration_id": "uuid",
      "platform": "meta",
      "scheduled_at": "2025-01-20T15:00:00Z"
    }
  ],
  "message": "Content scheduled successfully"
}
```

### Get Scheduled Posts
```http
GET /api/orgs/{org_id}/publishing/scheduled?status=pending
```

**Response:**
```json
{
  "success": true,
  "scheduled_posts": [...],
  "total": 5
}
```

### Update Scheduled Post
```http
PUT /api/orgs/{org_id}/publishing/scheduled/{schedule_id}
```

**Request Body:**
```json
{
  "scheduled_at": "2025-01-21T10:00:00Z",
  "content": "Updated content"
}
```

### Cancel Scheduled Post
```http
DELETE /api/orgs/{org_id}/publishing/scheduled/{schedule_id}
```

### Get Publishing History
```http
GET /api/orgs/{org_id}/publishing/history?limit=50
```

---

## 📢 Ad Campaign Management

### Get All Campaigns
```http
GET /api/orgs/{org_id}/campaigns?platform=meta&status=ACTIVE
```

**Response:**
```json
{
  "success": true,
  "campaigns": [
    {
      "campaign_id": "uuid",
      "platform": "meta",
      "campaign_name": "Summer Sale 2025",
      "objective": "OUTCOME_SALES",
      "status": "ACTIVE",
      "daily_budget": 5000,
      "created_at": "2025-01-10T00:00:00Z"
    }
  ],
  "total": 1
}
```

### Create Campaign
```http
POST /api/orgs/{org_id}/campaigns
```

**Request Body:**
```json
{
  "integration_id": "uuid",
  "campaign_name": "Black Friday Campaign",
  "objective": "OUTCOME_SALES",
  "status": "PAUSED",
  "daily_budget": 10000,
  "start_date": "2025-01-20",
  "end_date": "2025-01-30",
  "targeting": {
    "age_min": 25,
    "age_max": 45,
    "locations": ["US", "CA"],
    "interests": ["technology", "gadgets"]
  }
}
```

**Response:**
```json
{
  "success": true,
  "campaign_id": "uuid",
  "platform_campaign_id": "123456789",
  "message": "Campaign created successfully"
}
```

### Update Campaign
```http
PUT /api/orgs/{org_id}/campaigns/{campaign_id}
```

**Request Body:**
```json
{
  "campaign_name": "Updated Campaign Name",
  "daily_budget": 15000,
  "status": "ACTIVE"
}
```

### Get Campaign Details
```http
GET /api/orgs/{org_id}/campaigns/{campaign_id}
```

### Pause Campaign
```http
POST /api/orgs/{org_id}/campaigns/{campaign_id}/pause
```

### Activate Campaign
```http
POST /api/orgs/{org_id}/campaigns/{campaign_id}/activate
```

### Delete Campaign
```http
DELETE /api/orgs/{org_id}/campaigns/{campaign_id}
```

### Get Campaign Metrics
```http
GET /api/orgs/{org_id}/campaigns/{campaign_id}/metrics
```

**Response:**
```json
{
  "success": true,
  "campaign_id": "uuid",
  "platform": "meta",
  "live_metrics": {
    "impressions": 125000,
    "clicks": 3500,
    "conversions": 142,
    "spend": 48500,
    "ctr": 2.8,
    "cpc": 13.86
  },
  "stored_metrics": [...]
}
```

### Get Platform Objectives
```http
GET /api/orgs/{org_id}/campaigns/objectives/{platform}
```

**Response:**
```json
{
  "success": true,
  "platform": "meta",
  "objectives": {
    "OUTCOME_AWARENESS": "Brand Awareness",
    "OUTCOME_ENGAGEMENT": "Engagement",
    "OUTCOME_LEADS": "Lead Generation",
    "OUTCOME_SALES": "Sales & Conversions"
  }
}
```

---

## 💬 Unified Inbox

### Get All Messages
```http
GET /api/orgs/{org_id}/inbox?status=unread&platform=meta&limit=50
```

**Response:**
```json
{
  "success": true,
  "messages": [...],
  "total": 25,
  "unread_count": 10
}
```

### Get Conversation Thread
```http
GET /api/orgs/{org_id}/inbox/conversation/{conversation_id}
```

### Reply to Message
```http
POST /api/orgs/{org_id}/inbox/{message_id}/reply
```

**Request Body:**
```json
{
  "reply_text": "Thank you for reaching out! How can we help?"
}
```

### Mark as Read
```http
POST /api/orgs/{org_id}/inbox/mark-as-read
```

**Request Body:**
```json
{
  "message_ids": ["uuid1", "uuid2"]
}
```

### Assign Message
```http
POST /api/orgs/{org_id}/inbox/{message_id}/assign
```

**Request Body:**
```json
{
  "assigned_to": "user_uuid"
}
```

### Get Inbox Statistics
```http
GET /api/orgs/{org_id}/inbox/statistics
```

---

## 💭 Unified Comments

### Get All Comments
```http
GET /api/orgs/{org_id}/comments?platform=meta&status=unread
```

**Response:**
```json
{
  "success": true,
  "comments": [...],
  "total": 50
}
```

### Reply to Comment
```http
POST /api/orgs/{org_id}/comments/{comment_id}/reply
```

**Request Body:**
```json
{
  "reply_text": "Thanks for your feedback!"
}
```

### Hide Comment
```http
POST /api/orgs/{org_id}/comments/{comment_id}/hide
```

**Request Body:**
```json
{
  "hide": true
}
```

### Delete Comment
```http
DELETE /api/orgs/{org_id}/comments/{comment_id}
```

### Like Comment
```http
POST /api/orgs/{org_id}/comments/{comment_id}/like
```

### Bulk Actions
```http
POST /api/orgs/{org_id}/comments/bulk-action
```

**Request Body:**
```json
{
  "comment_ids": ["uuid1", "uuid2", "uuid3"],
  "action": "hide"
}
```

### Get Comments Statistics
```http
GET /api/orgs/{org_id}/comments/statistics
```

---

## 📊 Analytics & Reporting

### Get Overview Analytics
```http
GET /api/orgs/{org_id}/analytics/overview?period=30
```

**Response:**
```json
{
  "success": true,
  "period_days": 30,
  "overview": {
    "total_posts": 150,
    "total_comments": 487,
    "total_messages": 234,
    "active_campaigns": 8,
    "connected_platforms": 5
  },
  "posts_by_platform": [...],
  "daily_posts_trend": [...]
}
```

### Get Platform Analytics
```http
GET /api/orgs/{org_id}/analytics/platform/{integration_id}
```

### Get Post Performance
```http
GET /api/orgs/{org_id}/analytics/posts?platform=meta&limit=20
```

### Get Campaign Performance
```http
GET /api/orgs/{org_id}/analytics/campaigns?period=30
```

**Response:**
```json
{
  "success": true,
  "period_days": 30,
  "campaigns": [
    {
      "campaign_id": "uuid",
      "campaign_name": "Summer Sale",
      "platform": "meta",
      "status": "ACTIVE",
      "metrics": {
        "total_impressions": 250000,
        "total_clicks": 7500,
        "total_spend": 98500,
        "total_conversions": 342
      }
    }
  ],
  "total_campaigns": 8
}
```

### Get Engagement Analytics
```http
GET /api/orgs/{org_id}/analytics/engagement?period=30
```

**Response:**
```json
{
  "success": true,
  "period_days": 30,
  "comments_by_platform": [...],
  "messages_by_platform": [...],
  "daily_comments_trend": [...],
  "daily_messages_trend": [...]
}
```

### Export Report
```http
POST /api/orgs/{org_id}/analytics/export
```

**Request Body:**
```json
{
  "period": 30,
  "format": "json"
}
```

---

## 🔔 Webhooks

Webhooks are **publicly accessible** (no authentication required) to receive real-time updates from platforms.

### Meta (Facebook & Instagram) Webhook
```http
GET/POST /api/webhooks/meta
```

**Verification (GET):**
- Query params: `hub.mode`, `hub.verify_token`, `hub.challenge`

**Events (POST):**
- New messages
- New comments
- Post updates

### WhatsApp Webhook
```http
GET/POST /api/webhooks/whatsapp
```

**Events:**
- Incoming messages
- Message status updates

### TikTok Webhook
```http
POST /api/webhooks/tiktok
```

**Headers:**
- `X-TikTok-Signature`: HMAC signature

### Twitter/X Webhook
```http
POST /api/webhooks/twitter
```

**Events:**
- New tweets
- Direct messages

---

## 📄 Response Formats

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message description",
  "code": "ERROR_CODE"
}
```

### Pagination
```json
{
  "success": true,
  "data": [...],
  "total": 100,
  "current_page": 1,
  "per_page": 20,
  "last_page": 5
}
```

---

## 🌐 Supported Platforms

| Platform | OAuth | Posts | Comments | Messages | Ads |
|----------|-------|-------|----------|----------|-----|
| Meta (Facebook & Instagram) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Google Ads | ✅ | ❌ | ❌ | ❌ | ✅ |
| TikTok | ✅ | ✅ | ✅ | ❌ | ✅ |
| Twitter/X | ✅ | ✅ | ✅ | ✅ | ✅ |
| LinkedIn | ✅ | ✅ | ✅ | ✅ | ✅ |
| Snapchat | ✅ | ❌ | ❌ | ❌ | ✅ |
| YouTube | ✅ | ✅ | ✅ | ❌ | ❌ |
| WooCommerce | API Key | ❌ | ❌ | ❌ | ❌ |
| WordPress | App Password | ✅ | ✅ | ❌ | ❌ |
| WhatsApp Business | API Key | ❌ | ❌ | ✅ | ❌ |

---

## 🔑 Rate Limits

- **Default:** 200 requests per hour per integration
- **Webhooks:** No rate limit
- **OAuth callbacks:** No rate limit

Rate limit headers:
```
X-RateLimit-Limit: 200
X-RateLimit-Remaining: 195
X-RateLimit-Reset: 1642521600
```

---

## 📞 Support

For API support or questions:
- **Email:** api-support@cmis.marketing
- **Documentation:** https://docs.cmis.marketing
- **Status Page:** https://status.cmis.marketing

---

**Last Updated:** January 2025
**API Version:** 1.0.0
