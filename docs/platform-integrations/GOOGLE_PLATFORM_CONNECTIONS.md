# Google Platform Connections - Technical Documentation

**Last Updated:** 2025-11-26
**Status:** Production Ready
**Author:** CMIS Development Team

---

## Table of Contents

1. [Overview](#overview)
2. [Supported Google Services](#supported-google-services)
3. [Authentication Methods](#authentication-methods)
4. [Brand Accounts & YouTube Channels](#brand-accounts--youtube-channels)
5. [OAuth Implementation](#oauth-implementation)
6. [API Integration Details](#api-integration-details)
7. [Configuration](#configuration)
8. [Troubleshooting](#troubleshooting)

---

## Overview

CMIS supports comprehensive Google platform integration, allowing users to connect their Google accounts and access multiple Google services through a unified connection. The integration uses OAuth 2.0 for authentication and supports both personal Google accounts and Google Brand Accounts.

### Key Features

- **Unified OAuth Connection**: Single sign-in grants access to all enabled Google services
- **Multi-Service Support**: YouTube, Analytics, Ads, Business Profile, and more
- **Brand Account Support**: Connect YouTube brand channels separately
- **Token Auto-Refresh**: Automatic access token renewal using refresh tokens
- **Multi-Select Drive**: Select My Drive + multiple Shared Drives

---

## Supported Google Services

| Service | API | Scope Required | Auto-Fetch |
|---------|-----|----------------|------------|
| YouTube | YouTube Data API v3 | `youtube.readonly`, `youtube.upload`, `youtube` | Yes |
| Google Ads | Google Ads API | `adwords` | No* |
| Google Analytics | Analytics Admin API | `analytics.readonly`, `analytics.edit` | Yes |
| Business Profile | My Business API | `business.manage` | Yes |
| Tag Manager | Tag Manager API v2 | `tagmanager.readonly`, `tagmanager.edit.containers` | Yes |
| Merchant Center | Content API for Shopping | (implicit via OAuth) | Yes |
| Search Console | Search Console API | `webmasters.readonly` | Yes |
| Google Calendar | Calendar API v3 | `calendar.readonly` | Yes |
| Google Drive | Drive API v3 | `drive.readonly`, `drive.metadata.readonly` | Yes |
| Google Trends | N/A | N/A | Auto-enabled |
| Keyword Planner | Via Google Ads | `adwords` | Via Ads |

*Google Ads requires a separate Developer Token and is recommended to be added manually.

---

## Authentication Methods

### 1. OAuth 2.0 (Recommended)

Direct authentication using Google's OAuth 2.0 flow with credentials from `.env`:

```
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-secret
GOOGLE_REDIRECT_URI=https://your-domain.com/integrations/google/callback
```

**Advantages:**
- Automatic token refresh
- User-friendly consent flow
- Supports brand account selection

### 2. Service Account (Advanced)

For server-to-server authentication without user interaction:

```json
{
  "type": "service_account",
  "project_id": "your-project",
  "private_key_id": "key-id",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...",
  "client_email": "service@project.iam.gserviceaccount.com",
  "client_id": "123456789",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token"
}
```

**Use Cases:**
- Automated background tasks
- Server-to-server API calls
- When user interaction is not possible

---

## Brand Accounts & YouTube Channels

### Understanding Brand Accounts

Google Brand Accounts are separate Google identities that can be managed by multiple users. YouTube channels are often associated with Brand Accounts rather than personal Google accounts.

### Key Limitations

| Limitation | Description | Workaround |
|------------|-------------|------------|
| No List API | Google provides no API to list all Brand Accounts | Manual entry or multiple connections |
| One Auth at a Time | OAuth authenticates ONE account per connection | Create separate connections per brand |
| Manager Restrictions | Channel managers cannot use YouTube APIs | Only channel owners can authenticate |
| Partner API Only | `managedByMe` requires YouTube Partner access | Not available for regular users |

### How to Connect Brand Account Channels

#### Method 1: OAuth Account Selector (Recommended)

1. Click "Connect with Google" in CMIS
2. Google shows "Choose an account or a brand account"
3. Select the Brand Account (not your personal account)
4. Complete authorization
5. The connection is created for that Brand Account

**Note:** The account selector only shows Brand Accounts you **own**, not those you manage.

#### Method 2: Manual Channel ID Entry

1. Go to YouTube Studio for the Brand Account channel
2. Navigate to **Settings → Channel → Advanced settings**
3. Copy the **Channel ID** (starts with `UC...`)
4. In CMIS, click "Add manually" in the YouTube section
5. Paste the Channel ID

#### Method 3: Multiple Connections

Create separate Google connections for each Brand Account:

1. Connect your personal Google account
2. Click "Connect with Google" again
3. Click "Use another account" or select a different Brand Account
4. Repeat for each Brand Account channel

### OAuth Parameters for Brand Account Selection

CMIS uses these OAuth parameters to enable Brand Account selection:

```php
$params = [
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['redirect_uri'],
    'response_type' => 'code',
    'scope' => implode(' ', $config['scopes']),
    'state' => $state,
    'access_type' => 'offline',           // Get refresh token
    'prompt' => 'consent',                 // Force consent + account selection
    'include_granted_scopes' => 'true',    // Incremental authorization
];
```

**Prompt Parameter Values:**
- `none` - No UI, fails if interaction needed
- `consent` - Always show consent screen (required for refresh token)
- `select_account` - Force account selector display
- `consent select_account` - Both (use space or `%20` to combine)

---

## OAuth Implementation

### Authorization Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    User     │     │    CMIS     │     │   Google    │
└──────┬──────┘     └──────┬──────┘     └──────┬──────┘
       │                   │                   │
       │ Click Connect     │                   │
       │──────────────────>│                   │
       │                   │                   │
       │                   │ Redirect to OAuth │
       │                   │──────────────────>│
       │                   │                   │
       │     Account Selection Screen          │
       │<──────────────────────────────────────│
       │                   │                   │
       │  Select Account   │                   │
       │──────────────────────────────────────>│
       │                   │                   │
       │                   │ Callback + Code   │
       │                   │<──────────────────│
       │                   │                   │
       │                   │ Exchange for Token│
       │                   │──────────────────>│
       │                   │                   │
       │                   │ Access + Refresh  │
       │                   │<──────────────────│
       │                   │                   │
       │                   │ Get User Info     │
       │                   │──────────────────>│
       │                   │                   │
       │                   │ User Profile      │
       │                   │<──────────────────│
       │                   │                   │
       │   Redirect to     │                   │
       │   Asset Selection │                   │
       │<──────────────────│                   │
```

### Token Refresh Logic

```php
private function getValidGoogleAccessToken(PlatformConnection $connection): ?string
{
    $accessToken = $connection->access_token;

    // Check if token is expired
    if ($connection->token_expires_at && $connection->token_expires_at->isPast()) {
        if ($connection->refresh_token) {
            // Exchange refresh token for new access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('social-platforms.google.client_id'),
                'client_secret' => config('social-platforms.google.client_secret'),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $connection->update([
                    'access_token' => $response->json('access_token'),
                    'token_expires_at' => now()->addSeconds($response->json('expires_in', 3600)),
                ]);
                return $response->json('access_token');
            }
        }
    }

    return $accessToken;
}
```

---

## API Integration Details

### YouTube Data API v3

**Endpoint:** `https://www.googleapis.com/youtube/v3/channels`

**Parameters:**
```php
[
    'part' => 'snippet,statistics,contentDetails,brandingSettings',
    'mine' => 'true',  // Get authenticated user's channel
]
```

**Response Processing:**
```php
$channels = [];
foreach ($response->json('items', []) as $channel) {
    $channels[] = [
        'id' => $channel['id'],
        'title' => $channel['snippet']['title'],
        'thumbnail' => $channel['snippet']['thumbnails']['default']['url'],
        'subscriber_count' => $channel['statistics']['subscriberCount'],
        'custom_url' => $channel['snippet']['customUrl'],
    ];
}
```

### Google Analytics Admin API

**Endpoint:** `https://analyticsadmin.googleapis.com/v1beta/accountSummaries`

**Response Structure:**
```json
{
  "accountSummaries": [
    {
      "name": "accountSummaries/123456",
      "account": "accounts/123456",
      "displayName": "My Account",
      "propertySummaries": [
        {
          "property": "properties/789012",
          "displayName": "My Website",
          "propertyType": "PROPERTY_TYPE_ORDINARY"
        }
      ]
    }
  ]
}
```

### Google Drive API v3

**Shared Drives Endpoint:** `https://www.googleapis.com/drive/v3/drives`

**My Drive Folders:** `https://www.googleapis.com/drive/v3/files`
```php
[
    'q' => "mimeType='application/vnd.google-apps.folder' and 'root' in parents",
    'pageSize' => 20,
    'fields' => 'files(id,name,mimeType)',
]
```

### Search Console API

**Endpoint:** `https://www.googleapis.com/webmasters/v3/sites`

**Response:**
```json
{
  "siteEntry": [
    {
      "siteUrl": "https://example.com/",
      "permissionLevel": "siteOwner"
    }
  ]
}
```

---

## Configuration

### Required Environment Variables

```bash
# Google OAuth Credentials
GOOGLE_CLIENT_ID=247393241942-xxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxx
GOOGLE_REDIRECT_URI="${APP_URL}/integrations/google/callback"
```

### Google Cloud Console Setup

1. **Create Project:** https://console.cloud.google.com/
2. **Enable APIs:**
   - YouTube Data API v3
   - Google Analytics Admin API
   - Google Search Console API
   - Google Calendar API
   - Google Drive API
   - Tag Manager API
   - My Business Business Information API
   - Content API for Shopping

3. **Create OAuth Credentials:**
   - Go to APIs & Services → Credentials
   - Create OAuth client ID → Web application
   - Add authorized redirect URI: `https://your-domain.com/integrations/google/callback`

4. **Configure Consent Screen:**
   - Set application name and logo
   - Add required scopes
   - Add test users (for development)

### Required OAuth Scopes

```php
'scopes' => [
    // Core profile
    'openid',
    'email',
    'profile',
    // YouTube
    'https://www.googleapis.com/auth/youtube.readonly',
    'https://www.googleapis.com/auth/youtube.upload',
    'https://www.googleapis.com/auth/youtube',
    // Google Ads
    'https://www.googleapis.com/auth/adwords',
    // Analytics
    'https://www.googleapis.com/auth/analytics.readonly',
    'https://www.googleapis.com/auth/analytics.edit',
    // Business Profile
    'https://www.googleapis.com/auth/business.manage',
    // Tag Manager
    'https://www.googleapis.com/auth/tagmanager.readonly',
    'https://www.googleapis.com/auth/tagmanager.edit.containers',
    // Search Console
    'https://www.googleapis.com/auth/webmasters.readonly',
    // Calendar
    'https://www.googleapis.com/auth/calendar.readonly',
    // Drive
    'https://www.googleapis.com/auth/drive.readonly',
    'https://www.googleapis.com/auth/drive.metadata.readonly',
],
```

---

## Troubleshooting

### Common Errors

#### `redirect_uri_mismatch`

**Cause:** Redirect URI not registered in Google Cloud Console

**Solution:**
1. Go to Google Cloud Console → APIs & Services → Credentials
2. Edit your OAuth client
3. Add exact redirect URI: `https://your-domain.com/integrations/google/callback`
4. Wait 5 minutes for propagation

#### `access_denied`

**Cause:** User denied consent or insufficient permissions

**Solution:**
- Check if user has access to the requested service
- Verify OAuth scopes are approved in consent screen
- For Brand Accounts, ensure user is the owner

#### `invalid_grant`

**Cause:** Refresh token expired or revoked

**Solution:**
- Delete connection and reconnect
- Check if user revoked access at https://myaccount.google.com/permissions

#### No YouTube Channels Found

**Cause:** User authenticated with wrong account

**Solution:**
1. Disconnect the Google connection
2. Reconnect and select the correct account/brand account
3. Or manually add Channel ID

#### Brand Account Not Showing

**Cause:** User is manager, not owner of Brand Account

**Solution:**
- Only Brand Account owners see the account in OAuth selector
- Use manual Channel ID entry
- Contact Brand Account owner to authenticate

### Debug Logging

Enable detailed logging in `config/logging.php`:

```php
'channels' => [
    'google' => [
        'driver' => 'daily',
        'path' => storage_path('logs/google.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

### API Rate Limits

| API | Quota | Notes |
|-----|-------|-------|
| YouTube Data API | 10,000 units/day | channels.list = 1 unit |
| Analytics Admin | 600 requests/min | Per project |
| Drive API | 1,000 requests/100s | Per user |
| Calendar API | 1,000,000 requests/day | Per project |
| Search Console | 200 requests/minute | Per project |

---

## Database Schema

### Platform Connection Storage

```sql
-- Stored in cmis_platform.platform_connections
{
    "connection_id": "uuid",
    "org_id": "uuid",
    "platform": "google",
    "account_id": "google_user_id",
    "account_name": "User Name",
    "status": "active",
    "access_token": "encrypted",
    "refresh_token": "encrypted",
    "token_expires_at": "timestamp",
    "scopes": ["array", "of", "scopes"],
    "account_metadata": {
        "credential_type": "oauth",
        "email": "user@gmail.com",
        "picture": "https://...",
        "selected_assets": {
            "youtube_channel": "UC...",
            "analytics": "properties/123",
            "include_my_drive": true,
            "shared_drives": ["drive1", "drive2"],
            "manual_drives": []
        }
    }
}
```

---

## References

### Official Documentation

- [YouTube Data API v3](https://developers.google.com/youtube/v3/docs)
- [Google Analytics Admin API](https://developers.google.com/analytics/devguides/config/admin/v1)
- [Google Drive API v3](https://developers.google.com/drive/api/v3/reference)
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)

### Stack Overflow Discussions

- [List all channels I manage](https://stackoverflow.com/questions/22539755/youtube-api-list-all-channels-that-i-manage)
- [Brand Account OAuth selection](https://stackoverflow.com/questions/45350308/not-being-able-to-choose-the-right-channel-when-using-oauth-2-0-for-web-server-a)
- [Force account chooser](https://stackoverflow.com/questions/14384354/force-google-account-chooser)
- [Get all Brand Accounts](https://stackoverflow.com/questions/49678845/get-all-brand-accounts-via-google-api)

---

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2025-11-26 | 1.0.0 | Initial documentation |
| 2025-11-26 | 1.0.0 | Added Brand Account research findings |
| 2025-11-26 | 1.0.0 | Added multi-select Drive support |
