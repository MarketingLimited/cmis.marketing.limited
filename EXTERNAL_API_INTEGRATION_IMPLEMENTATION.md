# External API Integration Implementation

**Date:** 2025-11-29
**Status:** ✅ **FRONTEND COMPLETE** | ⏳ **BACKEND PENDING**
**Feature:** Hashtags & Locations from External Platform APIs

---

## 📊 Executive Summary

Successfully implemented **frontend integration** for fetching hashtags and locations from external platform APIs (Instagram, Twitter/X, TikTok). The modal now supports:

1. **Trending Hashtags from Platform APIs** - Platform selector with live fetching
2. **Location Autocomplete from Platform APIs** - Debounced search with platform-specific locations
3. **Platform Connections** - Fetches OAuth tokens from platform connections page

### Implementation Stats
- **Files Modified:** 3
- **Lines Added:** ~200 lines of code
- **Translation Keys Added:** 4 keys (EN/AR)
- **New Data Properties:** 3 properties
- **New JavaScript Methods:** 2 methods
- **Backend API Endpoints Needed:** 2 (see below)

---

## ✅ Frontend Implementation Complete

### 1. Hashtag Manager with Platform Selector
**Location:** `publish-modal.blade.php` lines 1237-1274

**Features:**
- **Platform Selector Dropdown:**
  - Instagram (default)
  - Twitter / X
  - TikTok

- **Tabs:**
  - My Sets (internal hashtag sets)
  - Recent (recently used hashtags)
  - Trending (fetched from selected platform API) **← NEW**

- **Loading States:**
  - Spinner animation while fetching
  - Empty state with helpful message
  - Error handling with toast notifications

- **UI Enhancements:**
  - Gradient buttons for trending hashtags (orange-to-red)
  - Platform-specific branding
  - Automatic fetch on tab switch

**User Flow:**
1. User opens hashtag manager
2. Selects platform from dropdown (Instagram/Twitter/TikTok)
3. Clicks "Trending" tab
4. System fetches trending hashtags from platform API
5. User clicks hashtag to insert into post

---

### 2. Location Autocomplete (Already Implemented)
**Location:** `publish-modal.blade.php` lines 777-811

**Features:**
- **Autocomplete Search:**
  - Minimum 3 characters to trigger search
  - 300ms debounce to prevent excessive API calls
  - Dropdown with location results

- **Location Display:**
  - Location name
  - Address (subtitle)
  - Map marker icon
  - Remove location button

**User Flow:**
1. User types location name (minimum 3 chars)
2. System debounces and calls API after 300ms
3. Dropdown shows matching locations
4. User selects location
5. Location displayed with option to remove

---

### 3. Platform Connections Integration
**Location:** `publish-modal.blade.php` lines 2593-2606

**Features:**
- Fetches connected platforms from `/orgs/{orgId}/settings/platform-connections`
- Stores OAuth tokens for API calls
- Called on modal initialization

**Data Structure:**
```javascript
platformConnections: [
  {
    id: 'uuid',
    platform: 'instagram',
    account_name: '@example',
    access_token: 'encrypted_token',
    // ... other platform-specific fields
  }
]
```

---

## 🔧 Technical Implementation

### New Data Properties
```javascript
// Hashtag Manager
loadingTrendingHashtags: false,
selectedHashtagPlatform: 'instagram',
platformConnections: [],

// Location Search (already existed)
locationResults: {},
locationSearchTimeout: null,
```

### New JavaScript Methods

#### 1. `loadPlatformConnections()`
**Location:** Lines 2593-2606

```javascript
async loadPlatformConnections() {
    const response = await fetch(
        `/orgs/${window.currentOrgId}/settings/platform-connections`
    );
    this.platformConnections = result.data || [];
}
```

**Purpose:** Fetches connected platform accounts with OAuth tokens

---

#### 2. `loadTrendingHashtags(platform)`
**Location:** Lines 2608-2632

```javascript
async loadTrendingHashtags(platform) {
    this.loadingTrendingHashtags = true;

    const response = await fetch(
        `/api/orgs/${orgId}/social/trending-hashtags/${platform}`
    );

    this.trendingHashtags = result.data || [];
    this.loadingTrendingHashtags = false;
}
```

**Purpose:** Fetches trending hashtags from selected platform API

**Platforms Supported:**
- `instagram` - Instagram trending hashtags
- `twitter` - Twitter/X trending topics
- `tiktok` - TikTok trending hashtags

**Error Handling:**
- Shows toast notification on error
- Clears loading state
- Logs error to console

---

#### 3. `searchLocation(query, platform)` (Already Implemented)
**Location:** Lines 2723-2749

```javascript
async searchLocation(query, platform) {
    // Debounce 300ms
    const response = await fetch(
        `/api/orgs/${orgId}/social/locations/search?query=${query}`
    );
    this.locationResults[platform] = data.results || [];
}
```

**Purpose:** Searches for locations with autocomplete

---

## 📁 Files Modified

### 1. `/resources/views/components/publish-modal.blade.php`
**Changes:** ~200 lines

**Sections Modified:**
- Hashtag Manager UI (platform selector, loading states, trending tab)
- Data properties (platformConnections, loadingTrendingHashtags)
- JavaScript methods (loadPlatformConnections, loadTrendingHashtags)
- init() method (added loadPlatformConnections call)

### 2. `/resources/lang/en/publish.php`
**Changes:** +4 translation keys

```php
'fetch_from_platform' => 'Fetch from platform',
'loading_trending_hashtags' => 'Loading trending hashtags...',
'select_platform_above' => 'Select a platform above to fetch trending hashtags',
'hashtags_from' => 'Hashtags from :platform',
```

### 3. `/resources/lang/ar/publish.php`
**Changes:** +4 translation keys (Arabic translations)

```php
'fetch_from_platform' => 'الحصول من المنصة',
'loading_trending_hashtags' => 'جاري تحميل الهاشتاقات الرائجة...',
'select_platform_above' => 'اختر منصة من الأعلى لجلب الهاشتاقات الرائجة',
'hashtags_from' => 'هاشتاقات من :platform',
```

---

## ⚠️ Backend API Endpoints Required

### 1. Platform Connections Endpoint (Might Already Exist)
**Endpoint:** `GET /orgs/{orgId}/settings/platform-connections`

**Purpose:** Fetch connected platform accounts with OAuth tokens

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "platform": "instagram",
      "account_name": "@example",
      "account_id": "platform_account_id",
      "access_token": "encrypted_access_token",
      "refresh_token": "encrypted_refresh_token",
      "expires_at": "2025-12-01T00:00:00Z",
      "scopes": ["user_profile", "user_media"],
      "is_active": true
    }
  ]
}
```

**Implementation Notes:**
- Check if this endpoint already exists at the URL provided by user
- If exists, verify response format matches
- If not, create new endpoint

---

### 2. Trending Hashtags Endpoint (NEW - MUST CREATE)
**Endpoint:** `GET /api/orgs/{orgId}/social/trending-hashtags/{platform}`

**Purpose:** Fetch trending hashtags from platform APIs

**Platforms:** `instagram`, `twitter`, `tiktok`

**Response:**
```json
{
  "success": true,
  "platform": "instagram",
  "data": [
    "photography",
    "instagood",
    "photooftheday",
    "fashion",
    "beautiful"
  ],
  "cached_at": "2025-11-29T10:00:00Z",
  "expires_at": "2025-11-29T11:00:00Z"
}
```

**Implementation Requirements:**

#### Instagram Trending Hashtags
**API:** Instagram Graph API
**Endpoint:** `GET /{ig-user-id}/available_catalogs` (or use search API)
**Authentication:** OAuth access token from platformConnections

```php
// Example implementation
public function instagramTrending($orgId) {
    $connection = PlatformConnection::where('org_id', $orgId)
        ->where('platform', 'instagram')
        ->where('is_active', true)
        ->first();

    if (!$connection) {
        return response()->json(['error' => 'No Instagram account connected'], 404);
    }

    // Check cache first (cache for 1 hour)
    $cacheKey = "trending_hashtags_instagram_{$orgId}";
    if (Cache::has($cacheKey)) {
        return Cache::get($cacheKey);
    }

    // Call Instagram API
    $response = Http::withToken($connection->access_token)
        ->get('https://graph.instagram.com/v18.0/ig_hashtag_search', [
            'user_id' => $connection->account_id,
            'q' => 'trending' // Or use popular hashtags
        ]);

    $hashtags = $response->json()['data'] ?? [];

    // Cache for 1 hour
    Cache::put($cacheKey, $hashtags, 3600);

    return response()->json([
        'success' => true,
        'platform' => 'instagram',
        'data' => $hashtags
    ]);
}
```

#### Twitter/X Trending Topics
**API:** Twitter API v2
**Endpoint:** `GET /2/trends/by/woeid/{woeid}`
**Authentication:** OAuth 2.0 Bearer Token

```php
public function twitterTrending($orgId) {
    $connection = PlatformConnection::where('org_id', $orgId)
        ->where('platform', 'twitter')
        ->first();

    $cacheKey = "trending_hashtags_twitter_{$orgId}";
    if (Cache::has($cacheKey)) {
        return Cache::get($cacheKey);
    }

    // Call Twitter API
    $response = Http::withToken($connection->access_token)
        ->get('https://api.twitter.com/2/trends/by/woeid/1', [ // 1 = Worldwide
            'max_results' => 20
        ]);

    $trends = collect($response->json()['data'] ?? [])
        ->pluck('name')
        ->map(fn($tag) => str_replace('#', '', $tag))
        ->toArray();

    Cache::put($cacheKey, $trends, 3600);

    return response()->json([
        'success' => true,
        'platform' => 'twitter',
        'data' => $trends
    ]);
}
```

#### TikTok Trending Hashtags
**API:** TikTok Research API or TikTok for Business API
**Endpoint:** Custom search or trending endpoint
**Authentication:** OAuth access token

```php
public function tiktokTrending($orgId) {
    $connection = PlatformConnection::where('org_id', $orgId)
        ->where('platform', 'tiktok')
        ->first();

    // Similar implementation to Instagram/Twitter
    // TikTok API documentation: https://developers.tiktok.com/
}
```

---

### 3. Location Search Endpoint (Already Called by Frontend)
**Endpoint:** `GET /api/orgs/{orgId}/social/locations/search?query={query}`

**Purpose:** Search for locations with autocomplete

**Response:**
```json
{
  "success": true,
  "results": [
    {
      "place_id": "ChIJN1t_tDeuEmsRUsoyG83frY4",
      "name": "Dubai Mall",
      "address": "Financial Center Road, Downtown Dubai, UAE",
      "latitude": 25.1981,
      "longitude": 55.2791,
      "platform_id": "instagram_location_id"
    }
  ]
}
```

**Implementation Requirements:**

#### Use Platform-Specific Location APIs

**Instagram Locations:**
```php
public function searchLocations($orgId, Request $request) {
    $query = $request->get('query');
    $connection = PlatformConnection::where('org_id', $orgId)
        ->where('platform', 'instagram')
        ->first();

    // Instagram Graph API
    $response = Http::withToken($connection->access_token)
        ->get('https://graph.instagram.com/v18.0/ig_location_search', [
            'query' => $query,
            'fields' => 'id,name,address,latitude,longitude'
        ]);

    return response()->json([
        'success' => true,
        'results' => $response->json()['data'] ?? []
    ]);
}
```

**Facebook/Meta Locations:**
```php
// Use Facebook Places API
$response = Http::withToken($connection->access_token)
    ->get('https://graph.facebook.com/v18.0/search', [
        'type' => 'place',
        'q' => $query,
        'fields' => 'id,name,location'
    ]);
```

**Google Places API (Fallback):**
```php
// If platform-specific API not available
$response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
    'input' => $query,
    'key' => config('services.google.places_api_key')
]);
```

---

## 🔒 Security & Rate Limiting

### 1. API Rate Limiting
**Implementation Required:**

```php
use Illuminate\Support\Facades\RateLimiter;

public function trendingHashtags($orgId, $platform) {
    // Rate limit: 30 requests per minute per org
    $key = "trending_hashtags_{$orgId}_{$platform}";

    if (RateLimiter::tooManyAttempts($key, 30)) {
        return response()->json([
            'error' => 'Too many requests. Please wait.'
        ], 429);
    }

    RateLimiter::hit($key, 60); // 60 seconds

    // ... fetch hashtags
}
```

### 2. Caching Strategy

**Cache Duration:**
- **Trending Hashtags:** 1 hour (3600 seconds)
- **Location Results:** 24 hours (86400 seconds)
- **Platform Connections:** 15 minutes (900 seconds)

**Cache Keys:**
```php
"trending_hashtags_{platform}_{orgId}"
"location_search_{query}_{platform}_{orgId}"
"platform_connections_{orgId}"
```

### 3. Token Management

**Refresh Tokens:**
- Check `expires_at` before each API call
- Automatically refresh if expired
- Update `platformConnections` table with new tokens

```php
if (Carbon::parse($connection->expires_at)->isPast()) {
    $connection->refreshAccessToken(); // Implement this method
}
```

---

## 📊 API Call Flow Diagrams

### Trending Hashtags Flow
```
User Clicks Trending Tab
    ↓
Frontend: loadTrendingHashtags('instagram')
    ↓
Backend: GET /api/orgs/{orgId}/social/trending-hashtags/instagram
    ↓
Check Cache (1 hour TTL)
    ↓ (cache miss)
Fetch from platformConnections table
    ↓
Call Instagram Graph API with OAuth token
    ↓
Parse response & extract hashtags
    ↓
Cache for 1 hour
    ↓
Return JSON: {data: ['hashtag1', 'hashtag2', ...]}
    ↓
Frontend: Display hashtags in modal
```

### Location Autocomplete Flow
```
User Types Location (3+ chars)
    ↓
Debounce 300ms
    ↓
Frontend: searchLocation(query, platform)
    ↓
Backend: GET /api/orgs/{orgId}/social/locations/search?query=...
    ↓
Fetch from platform connection
    ↓
Call Instagram/Facebook Location API
    ↓
Parse response & format locations
    ↓
Return JSON: {results: [{name, address, ...}]}
    ↓
Frontend: Display dropdown with results
```

---

## ✅ Testing Checklist

### Frontend Testing
- [ ] Platform selector switches correctly (Instagram/Twitter/TikTok)
- [ ] Trending tab shows loading spinner while fetching
- [ ] Trending hashtags display correctly
- [ ] Clicking hashtag inserts into post content
- [ ] Error toast shows on API failure
- [ ] Location autocomplete triggers after 3 characters
- [ ] Location autocomplete debounces correctly (300ms)
- [ ] Location dropdown displays results
- [ ] Selected location shows with remove button

### Backend Testing (To Be Created)
- [ ] `/settings/platform-connections` returns connected accounts
- [ ] `/trending-hashtags/instagram` returns Instagram hashtags
- [ ] `/trending-hashtags/twitter` returns Twitter trends
- [ ] `/trending-hashtags/tiktok` returns TikTok hashtags
- [ ] `/locations/search` returns location results
- [ ] Rate limiting works (30 req/min)
- [ ] Caching works (1 hour for hashtags)
- [ ] Token refresh works when expired
- [ ] Error handling for disconnected accounts

### Integration Testing
- [ ] Full flow: Select platform → Fetch hashtags → Insert
- [ ] Full flow: Type location → Select → Display
- [ ] OAuth token expiration handled gracefully
- [ ] API failures don't break modal
- [ ] Cache invalidation works correctly

---

## 📚 Platform API Documentation

### Instagram Graph API
- **Documentation:** https://developers.facebook.com/docs/instagram-api/
- **Hashtag Search:** https://developers.facebook.com/docs/instagram-api/guides/hashtag-search/
- **Location Search:** https://developers.facebook.com/docs/instagram-api/reference/location/

### Twitter API v2
- **Documentation:** https://developer.twitter.com/en/docs/twitter-api
- **Trends:** https://developer.twitter.com/en/docs/twitter-api/v2/reference/get-trends-by-woeid

### TikTok for Business API
- **Documentation:** https://developers.tiktok.com/
- **Research API:** https://developers.tiktok.com/doc/research-api-specs-query-videos/

---

## 🚀 Next Steps

### Immediate (Backend Implementation)
1. ✅ Create `/trending-hashtags/{platform}` endpoint
2. ✅ Implement Instagram hashtag fetching
3. ✅ Implement Twitter trends fetching
4. ✅ Implement TikTok hashtags fetching
5. ✅ Update `/locations/search` to use platform APIs
6. ✅ Add rate limiting middleware
7. ✅ Add caching layer
8. ✅ Implement token refresh logic

### Future Enhancements
- Add more platforms (LinkedIn, Pinterest)
- Implement hashtag analytics (popularity, engagement)
- Add location suggestions based on user's timezone
- Cache trending hashtags per-platform-account (more granular)
- Add hashtag performance metrics

---

## 🎯 Success Metrics

| Metric | Target | How to Measure |
|--------|--------|----------------|
| **API Response Time** | < 500ms | Monitor API logs |
| **Cache Hit Rate** | > 80% | Redis/Cache analytics |
| **User Adoption** | 50% of posts use trending hashtags | Track hashtag insertions |
| **Location Accuracy** | 95% correct locations | User feedback |
| **API Errors** | < 1% | Error tracking |

---

**Frontend Status:** ✅ **COMPLETE**
**Backend Status:** ⏳ **PENDING IMPLEMENTATION**

**Last Updated:** 2025-11-29
**Implemented By:** Claude Code (AI Assistant)
