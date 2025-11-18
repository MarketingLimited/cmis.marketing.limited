# CMIS API Documentation

This directory contains comprehensive API documentation for the CMIS platform, including REST APIs, platform integrations, and AI services.

---

## Quick Navigation

- **[API Overview](overview.md)** - API architecture and design
- **[OpenAPI Specification](openapi.yaml)** - Complete REST API specification
- **[Integration Guide](integration-guide.md)** - How to integrate with CMIS API
- **[Authentication](authentication.md)** - API authentication guide
- **[Vector Embeddings API](vector-embeddings-v2.md)** - AI-powered semantic search
- **[GPT Actions](gpt-actions.yaml)** - GPT integration actions
- **[Instagram API Guide](instagram-api.json)** - Instagram integration

---

## Overview

CMIS provides a comprehensive REST API for:

- Campaign management
- Content creation and publishing
- Social media integration
- AI-powered content generation
- Analytics and reporting
- Platform integrations

### Base URL
```
Production: https://cmis.kazaaz.com/api/v1
Staging: https://staging.cmis.kazaaz.com/api/v1
```

### API Versions
- **v1** - Current stable version
- **v2** - In development (vector embeddings, enhanced AI)

---

## Authentication

### API Token Authentication

```bash
# Get API token
curl -X POST https://cmis.kazaaz.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'

# Use token in requests
curl -X GET https://cmis.kazaaz.com/api/v1/campaigns \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### OAuth 2.0 (Platform Integrations)

For platform integrations (Facebook, Instagram, LinkedIn):

```bash
# Initiate OAuth flow
GET /api/v1/platforms/{platform}/authorize

# Handle callback
GET /api/v1/platforms/{platform}/callback?code=AUTH_CODE
```

See [Authentication Guide](authentication.md) for details.

---

## Core API Endpoints

### Campaigns

```
GET    /api/v1/campaigns                 # List campaigns
POST   /api/v1/campaigns                 # Create campaign
GET    /api/v1/campaigns/{id}            # Get campaign
PUT    /api/v1/campaigns/{id}            # Update campaign
DELETE /api/v1/campaigns/{id}            # Delete campaign
POST   /api/v1/campaigns/{id}/publish    # Publish campaign
```

### Content

```
GET    /api/v1/content-plans              # List content plans
POST   /api/v1/content-plans              # Create content plan
GET    /api/v1/content-items              # List content items
POST   /api/v1/content-items              # Create content item
```

### Social Publishing

```
POST   /api/v1/social/publish             # Publish to social platforms
GET    /api/v1/social/posts               # List social posts
POST   /api/v1/social/schedule            # Schedule post
GET    /api/v1/social/analytics           # Get social analytics
```

### AI & Semantic Search

```
POST   /api/v1/ai/generate                # Generate content with AI
POST   /api/v1/ai/embeddings              # Create embeddings
POST   /api/v1/ai/search                  # Semantic search
GET    /api/v1/ai/suggestions             # Get AI suggestions
```

### Analytics

```
GET    /api/v1/analytics/campaigns/{id}   # Campaign analytics
GET    /api/v1/analytics/overview         # Overview metrics
POST   /api/v1/analytics/report           # Generate custom report
GET    /api/v1/analytics/export           # Export analytics data
```

See [OpenAPI Specification](openapi.yaml) for complete endpoint documentation.

---

## AI-Powered APIs

### Vector Embeddings V2

Semantic search and content similarity using vector embeddings.

**Endpoint:** `/api/v2/embeddings`

**Features:**
- Generate embeddings for text
- Semantic similarity search
- Content recommendations
- Duplicate detection

**Example:**
```json
POST /api/v2/embeddings/search
{
  "query": "social media campaign for fashion brand",
  "limit": 10,
  "threshold": 0.7
}
```

See [Vector Embeddings API Documentation](vector-embeddings-v2.md) for details.

### Content Generation

AI-powered content generation for campaigns.

**Endpoint:** `/api/v1/ai/generate`

**Example:**
```json
POST /api/v1/ai/generate
{
  "type": "social_post",
  "platform": "instagram",
  "topic": "summer fashion collection",
  "tone": "casual",
  "length": "medium"
}
```

---

## Platform Integration APIs

### Meta (Facebook/Instagram)

```
POST   /api/v1/platforms/facebook/authorize
POST   /api/v1/platforms/instagram/publish
GET    /api/v1/platforms/facebook/pages
GET    /api/v1/platforms/instagram/insights
```

### LinkedIn

```
POST   /api/v1/platforms/linkedin/authorize
POST   /api/v1/platforms/linkedin/publish
GET    /api/v1/platforms/linkedin/organizations
```

### TikTok

```
POST   /api/v1/platforms/tiktok/authorize
POST   /api/v1/platforms/tiktok/upload
GET    /api/v1/platforms/tiktok/videos
```

See [Integration Guide](integration-guide.md) and platform-specific documentation.

---

## Request/Response Format

### Request Format

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_API_TOKEN
Accept: application/json
X-Org-ID: {organization_id}  (for multi-tenant operations)
```

**Body (JSON):**
```json
{
  "name": "Summer Campaign 2024",
  "status": "draft",
  "platforms": ["facebook", "instagram"],
  "scheduled_at": "2024-12-01T10:00:00Z"
}
```

### Response Format

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": "uuid-here",
    "name": "Summer Campaign 2024",
    "status": "draft",
    "created_at": "2024-11-18T12:00:00Z"
  },
  "meta": {
    "timestamp": "2024-11-18T12:00:00Z"
  }
}
```

**Error Response (400/422):**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "errors": {
      "name": ["The name field is required."]
    }
  },
  "meta": {
    "timestamp": "2024-11-18T12:00:00Z"
  }
}
```

---

## Rate Limiting

### Limits

- **Public API:** 60 requests/minute per IP
- **Authenticated API:** 1000 requests/minute per user
- **Platform APIs:** Subject to platform-specific limits

### Rate Limit Headers

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1638360000
```

### Handling Rate Limits

```bash
# Response when rate limit exceeded (429)
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests. Please try again later.",
    "retry_after": 60
  }
}
```

---

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `UNAUTHORIZED` | 401 | Missing or invalid authentication |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `VALIDATION_ERROR` | 422 | Invalid input data |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `SERVER_ERROR` | 500 | Internal server error |
| `SERVICE_UNAVAILABLE` | 503 | Service temporarily unavailable |

---

## Pagination

List endpoints support pagination:

**Request:**
```
GET /api/v1/campaigns?page=2&per_page=20
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 2,
    "from": 21,
    "last_page": 5,
    "per_page": 20,
    "to": 40,
    "total": 100
  },
  "links": {
    "first": "/api/v1/campaigns?page=1",
    "last": "/api/v1/campaigns?page=5",
    "prev": "/api/v1/campaigns?page=1",
    "next": "/api/v1/campaigns?page=3"
  }
}
```

---

## Filtering and Sorting

### Filtering

```
GET /api/v1/campaigns?status=active&platform=facebook
```

### Sorting

```
GET /api/v1/campaigns?sort=-created_at,name
```

Prefix with `-` for descending order.

### Field Selection

```
GET /api/v1/campaigns?fields=id,name,status
```

---

## Webhooks

Subscribe to events via webhooks:

### Supported Events

- `campaign.created`
- `campaign.updated`
- `campaign.published`
- `post.published`
- `post.failed`
- `analytics.updated`

### Webhook Configuration

```json
POST /api/v1/webhooks
{
  "url": "https://your-app.com/webhook",
  "events": ["campaign.published", "post.failed"],
  "secret": "webhook_secret_key"
}
```

### Webhook Payload

```json
{
  "event": "campaign.published",
  "data": {
    "id": "uuid",
    "name": "Summer Campaign",
    "published_at": "2024-12-01T10:00:00Z"
  },
  "timestamp": "2024-12-01T10:00:00Z",
  "signature": "hmac-sha256-signature"
}
```

---

## SDKs and Client Libraries

### Official SDKs

- **PHP SDK** - `composer require cmis/php-sdk`
- **JavaScript SDK** - `npm install @cmis/js-sdk`
- **Python SDK** - `pip install cmis-sdk`

### Example Usage (PHP)

```php
use CMIS\Client;

$client = new Client('YOUR_API_TOKEN');

// Create campaign
$campaign = $client->campaigns->create([
    'name' => 'Summer Campaign 2024',
    'platforms' => ['facebook', 'instagram']
]);

// Publish campaign
$client->campaigns->publish($campaign->id);
```

---

## API Changelog

### v2.0 (In Development)
- Enhanced vector embeddings API
- Improved semantic search
- GraphQL support
- Real-time subscriptions

### v1.1 (Current)
- Added TikTok integration
- Enhanced analytics endpoints
- Improved rate limiting
- Webhook support

### v1.0 (Initial Release)
- Core campaign management
- Social publishing
- Basic analytics
- Platform integrations

---

## Best Practices

### Security
- Use HTTPS for all API calls
- Store API tokens securely
- Rotate tokens regularly
- Validate webhook signatures
- Use least-privilege permissions

### Performance
- Use pagination for large datasets
- Implement proper caching
- Use field selection to reduce payload size
- Batch requests when possible
- Handle rate limits gracefully

### Error Handling
- Implement retry logic with exponential backoff
- Log errors for debugging
- Handle all error codes appropriately
- Provide meaningful error messages to users

---

## Testing

### Sandbox Environment

```
Sandbox URL: https://sandbox.cmis.kazaaz.com/api/v1
```

Use sandbox for testing without affecting production data.

### API Testing Tools

- **Postman Collection** - Import from `/api/postman/collection.json`
- **OpenAPI Spec** - Use with Swagger UI or Redoc
- **cURL Examples** - See examples in [Integration Guide](integration-guide.md)

---

## Support

- **API Issues** → Check [OpenAPI Specification](openapi.yaml)
- **Integration Help** → See [Integration Guide](integration-guide.md)
- **Authentication Problems** → See [Authentication Guide](authentication.md)
- **Platform-Specific** → See [Platform Integrations](../integrations/)

---

## Related Documentation

- **[Platform Integrations](../integrations/)** - Platform-specific integration guides
- **[AI Features](../features/ai-semantic/)** - AI and semantic features
- **[Database Schema](../features/database/)** - Data models and relationships
- **[Architecture](../architecture/)** - System architecture

---

**API Version:** v1.1
**Last Updated:** 2025-11-18
**Maintained by:** CMIS API Team
