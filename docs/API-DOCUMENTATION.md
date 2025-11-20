# CMIS API Documentation

API reference for CMIS (Cognitive Marketing Information System) RESTful API.

## Base URL

```
Production: https://cmis.marketing/api
Staging: https://staging.cmis.marketing/api
Local: http://localhost/api
```

## Authentication

All API endpoints require authentication using Bearer tokens obtained via OAuth 2.0.

```http
Authorization: Bearer {access_token}
```

### OAuth Endpoints

- `GET /oauth/{platform}` - Initiate OAuth flow
- `GET /oauth/{platform}/callback` - OAuth callback handler
- `POST /oauth/{platform}/disconnect` - Revoke integration

## Rate Limiting

API requests are rate-limited based on authentication tier:

| Tier | Limit | Window |
|------|-------|--------|
| Guest | 100 requests | 1 minute |
| Authenticated | 1,000 requests | 1 minute |
| API Key | 5,000 requests | 1 minute |

Rate limit headers are included in responses:
- `X-RateLimit-Limit` - Request limit
- `X-RateLimit-Remaining` - Remaining requests
- `X-RateLimit-Reset` - Reset timestamp

## Response Format

All responses follow a consistent JSON format:

### Success Response

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "2025-11-20T22:00:00Z",
    "request_id": "req_abc123"
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input data",
    "details": { ... }
  },
  "meta": {
    "timestamp": "2025-11-20T22:00:00Z",
    "request_id": "req_abc123"
  }
}
```

## API Endpoints

### Health Check

#### GET /health
Basic health check endpoint.

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-20T22:00:00Z"
}
```

#### GET /health/detailed
Detailed health check with dependency status.

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-20T22:00:00Z",
  "checks": {
    "database": { "healthy": true, "response_time_ms": 5.2 },
    "cache": { "healthy": true },
    "storage": { "healthy": true },
    "queue": { "healthy": true, "pending_jobs": 12 }
  }
}
```

### Campaigns

#### GET /api/campaigns
List all campaigns for the authenticated user's organization.

**Query Parameters:**
- `page` (integer) - Page number (default: 1)
- `per_page` (integer) - Items per page (default: 15, max: 100)
- `status` (string) - Filter by status (draft, active, paused, completed)
- `sort` (string) - Sort field (created_at, name, budget)
- `order` (string) - Sort order (asc, desc)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "campaign_id": "uuid",
      "name": "Summer Campaign 2025",
      "status": "active",
      "budget": 10000.00,
      "start_date": "2025-06-01",
      "end_date": "2025-08-31",
      "created_at": "2025-05-01T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  }
}
```

#### POST /api/campaigns
Create a new campaign.

**Request Body:**
```json
{
  "name": "Campaign Name",
  "description": "Campaign description",
  "budget": 10000.00,
  "start_date": "2025-06-01",
  "end_date": "2025-08-31",
  "status": "draft"
}
```

**Response:** Returns created campaign object.

#### GET /api/campaigns/{id}
Get campaign details.

#### PUT /api/campaigns/{id}
Update campaign.

#### DELETE /api/campaigns/{id}
Delete campaign (soft delete).

### Social Posts

#### GET /api/posts
List social posts.

**Query Parameters:**
- `platform` (string) - Filter by platform (facebook, instagram, twitter, etc.)
- `status` (string) - Filter by status (draft, scheduled, published, failed)
- `date_from` (date) - Filter posts from date
- `date_to` (date) - Filter posts to date

#### POST /api/posts
Create and publish/schedule a social media post.

**Request Body:**
```json
{
  "platform": "facebook",
  "account_id": "uuid",
  "content": "Post content",
  "media_urls": ["https://example.com/image.jpg"],
  "scheduled_for": "2025-06-15T14:00:00Z",
  "status": "scheduled"
}
```

#### POST /api/posts/bulk
Bulk create posts across multiple platforms.

**Request Body:**
```json
{
  "posts": [
    {
      "platform": "facebook",
      "content": "Content for Facebook",
      "scheduled_for": "2025-06-15T14:00:00Z"
    },
    {
      "platform": "instagram",
      "content": "Content for Instagram",
      "media_urls": ["https://example.com/image.jpg"],
      "scheduled_for": "2025-06-15T14:00:00Z"
    }
  ]
}
```

### Analytics

#### GET /api/analytics/campaigns/{id}
Get campaign analytics.

**Query Parameters:**
- `date_from` (date) - Start date
- `date_to` (date) - End date
- `metrics` (array) - Metrics to include

**Response:**
```json
{
  "success": true,
  "data": {
    "campaign_id": "uuid",
    "period": {
      "from": "2025-06-01",
      "to": "2025-06-30"
    },
    "metrics": {
      "impressions": 125000,
      "clicks": 5200,
      "conversions": 320,
      "spend": 8500.00,
      "ctr": 4.16,
      "cpc": 1.63,
      "cpa": 26.56
    }
  }
}
```

#### GET /api/analytics/posts/{id}
Get post performance metrics.

### Integrations

#### GET /api/integrations
List connected platform integrations.

#### GET /api/integrations/{id}
Get integration details.

#### DELETE /api/integrations/{id}
Disconnect integration.

### Webhooks

#### POST /webhooks/{platform}
Receive webhook events from platforms.

**Headers:**
- `X-Hub-Signature-256` - Webhook signature (Meta)
- `X-Snapchat-Signature` - Webhook signature (Snapchat)

**Note:** Webhook endpoints verify signatures before processing.

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| VALIDATION_ERROR | 422 | Invalid input data |
| UNAUTHORIZED | 401 | Authentication required |
| FORBIDDEN | 403 | Insufficient permissions |
| NOT_FOUND | 404 | Resource not found |
| RATE_LIMIT_EXCEEDED | 429 | Too many requests |
| INTERNAL_ERROR | 500 | Server error |
| SERVICE_UNAVAILABLE | 503 | Service temporarily unavailable |

## Pagination

List endpoints support cursor-based pagination:

**Request:**
```
GET /api/campaigns?page=2&per_page=20
```

**Response includes meta:**
```json
{
  "meta": {
    "current_page": 2,
    "per_page": 20,
    "total": 150,
    "last_page": 8,
    "from": 21,
    "to": 40
  }
}
```

## Filtering

Most list endpoints support filtering via query parameters:

```
GET /api/posts?status=published&platform=facebook&date_from=2025-06-01
```

## Sorting

Sort using `sort` and `order` parameters:

```
GET /api/campaigns?sort=budget&order=desc
```

## Webhooks Integration

### Meta (Facebook/Instagram)

**Endpoint:** `POST /webhooks/meta`

**Verification:** Hub challenge verification on subscription

**Events:**
- Page posts
- Comments
- Messages
- Feed updates

### Google Ads

**Endpoint:** `POST /webhooks/google`

**Events:**
- Campaign changes
- Budget alerts
- Performance notifications

### TikTok

**Endpoint:** `POST /webhooks/tiktok`

**Events:**
- Video status changes
- Analytics updates
- Comment notifications

## SDK & Client Libraries

Official SDKs coming soon:
- JavaScript/TypeScript
- Python
- PHP
- Ruby

## Support

- API Issues: https://github.com/MarketingLimited/cmis/issues
- Documentation: https://docs.cmis.marketing
- Email: support@cmis.marketing

## Changelog

### v1.0.0 (2025-11-20)
- Initial API release
- OAuth 2.0 authentication
- Campaign management endpoints
- Social media posting
- Analytics endpoints

---

**Version:** 1.0.0
**Last Updated:** 2025-11-20
