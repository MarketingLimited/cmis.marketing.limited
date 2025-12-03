# Social Media Publishing API Research & Optimization Plan (2025)

**Date:** November 26, 2025
**Project:** CMIS - Cognitive Marketing Intelligence Suite
**Purpose:** Comprehensive research and optimization plan for multi-platform social media publishing

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Platform API Research](#platform-api-research)
3. [Current Implementation Analysis](#current-implementation-analysis)
4. [Gap Analysis](#gap-analysis)
5. [Optimization Plan](#optimization-plan)
6. [Implementation Priorities](#implementation-priorities)
7. [API Reference & Sources](#api-reference--sources)

---

## Executive Summary

### Research Scope

Comprehensive API research covering 11 major social media platforms:
- ✅ Instagram
- ✅ Facebook
- ✅ TikTok
- ✅ Snapchat
- ✅ YouTube
- ✅ LinkedIn
- ✅ X (Twitter)
- ✅ Pinterest
- ✅ Threads (Meta)
- ✅ Google Business Profile
- ✅ Tumblr
- ✅ Reddit

### Key Findings

**Currently Implemented:**
- ✅ Meta (Facebook/Instagram) - System User tokens
- ✅ Post scheduling and publishing to feed
- ✅ Basic media upload support
- ✅ Multi-platform post type selection (Feed, Reels, Stories)

**Major Gaps Identified:**
- ❌ TikTok organic posting
- ❌ YouTube video upload
- ❌ LinkedIn posts and articles
- ❌ X (Twitter) tweets
- ❌ Pinterest pins
- ❌ Threads posts
- ❌ Google Business Profile posts
- ❌ Tumblr posts
- ❌ Reddit submissions
- ❌ Snapchat Creative Kit integration

---

## Platform API Research

### 1. Instagram (Graph API)

**API:** Instagram Graph API via Facebook Graph API
**Latest Version:** v21.0
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Feed Posts | ✅ Yes | ✅ Yes | Active |
| Reels | ✅ Yes | ✅ Yes | Active (since mid-2022) |
| Stories | ✅ Yes | ✅ Yes | Active (since 2023) |
| Carousels | ✅ Yes | ✅ Yes | Active |

**Key Features:**
- ✅ Up to 25 posts per day via API
- ✅ Two-step publishing process (create container → publish)
- ✅ Support for images, videos, and carousels
- ✅ Content scheduling via `publish_time` parameter
- ✅ Requires Instagram Business Account
- ✅ Access via connected Facebook Page

**Authentication:** OAuth 2.0 via Facebook
**Permissions Required:**
- `instagram_content_publish`
- `instagram_basic`
- `pages_read_engagement`

**Rate Limits:**
- 200 calls per hour per user
- 25 posts per day

**Limitations:**
- Cannot schedule Stories in real-time (workaround via third-party scheduling)
- Requires Facebook Page connection

**Sources:**
- [Top Instagram Graph API Use Cases for Brands in 2025](https://www.getphyllo.com/post/instagram-graph-api-use-cases-in-2025-iv)
- [Instagram API : A Complete Guide For Businesses In 2025](https://tagembed.com/blog/instagram-api/)
- [Schedule & Publish All Instagram Content Types with Facebook Graph API](https://n8n.io/workflows/4498-schedule-and-publish-all-instagram-content-types-with-facebook-graph-api/)
- [A Complete Guide To the Instagram Reels API](https://www.getphyllo.com/post/a-complete-guide-to-the-instagram-reels-api)

---

### 2. Facebook (Graph API)

**API:** Facebook Graph API
**Latest Version:** v21.0
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Feed Posts | ✅ Yes | ✅ Yes | Active |
| Reels | ✅ Yes | ✅ Yes | Active (since mid-2022) |
| Stories | ✅ Yes | ✅ Yes | Active (Nov 2024) |
| Videos | ✅ Yes | ✅ Yes | Active |
| Photos | ✅ Yes | ✅ Yes | Active |

**Key Features:**
- ✅ Two-step publishing (create container → publish)
- ✅ Cross-posting between Facebook and Instagram
- ✅ Video reels up to 90 seconds
- ✅ Story posts with 24-hour lifecycle
- ✅ Pages-only publishing (not personal profiles)

**Authentication:** OAuth 2.0
**Permissions Required:**
- `pages_show_list`
- `pages_read_engagement`
- `pages_manage_posts`

**Rate Limits:**
- 200 calls per hour per user
- Platform-specific throttling

**Process:**
1. Create media container with content
2. Wait for processing (async)
3. Publish container with `POST /{page-id}/feed`

**Sources:**
- [Facebook Reels API and Specifications](https://vistasocial.com/insights/facebook-reels-api-and-specifications/)
- [How to Publish Stories with the Facebook Stories API](https://www.ayrshare.com/how-to-publish-stories-with-the-facebook-stories-api/)
- [Facebook Reels API: How to Post Facebook Reels](https://www.ayrshare.com/facebook-reels-api-how-to-post-fb-reels-using-a-social-media-api/)

---

### 3. TikTok (Content Posting API)

**API:** TikTok Content Posting API
**Latest Version:** v2
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Videos | ✅ Yes | ⚠️ Limited | Active |
| Photos | ✅ Yes | ⚠️ Limited | Active (2025) |
| Drafts | ✅ Yes | N/A | Active |

**Key Features:**
- ✅ Video upload via FILE_UPLOAD or PULL_FROM_URL
- ✅ Photo carousels support (new in 2025)
- ✅ Draft saving capability
- ⚠️ **Important:** Unaudited clients restricted to private posts only
- ⚠️ Must pass TikTok audit for public posting

**Authentication:** OAuth 2.0
**Permissions Required:**
- `video.publish` scope (requires approval)

**Rate Limits:**
- 6 requests per minute per user access token

**Upload Methods:**
1. **FILE_UPLOAD:** Direct file upload from local storage
2. **PULL_FROM_URL:** Provide URL for TikTok to download

**Important Restrictions:**
- 🔴 **All content from unaudited apps will be PRIVATE only**
- 🔴 **Audit required for public posting**
- 🔴 **Compliance with TikTok ToS mandatory**

**Audit Process:**
1. Register app on TikTok for Developers
2. Add Content Posting API product
3. Apply for `video.publish` scope
4. Pass compliance audit
5. Get approval for public posting

**Sources:**
- [Guide to Using the Content Posting API for TikTok](https://developers.tiktok.com/doc/content-posting-api-get-started)
- [TikTok Content Posting API Overview](https://developers.tiktok.com/doc/content-posting-api-reference-direct-post?enter_method=left_navigation)
- [Content Posting API | TikTok for Developers](https://developers.tiktok.com/products/content-posting-api/)

---

### 4. YouTube (Data API v3)

**API:** YouTube Data API v3
**Latest Documentation Update:** August 28, 2025
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Videos | ✅ Yes | ✅ Yes | Active |
| Shorts | ✅ Yes | ✅ Yes | Active |
| Thumbnails | ✅ Yes | N/A | Active |
| Captions | ✅ Yes | N/A | Active |

**Key Features:**
- ✅ Full video upload with metadata
- ✅ Privacy settings: public, private, unlisted
- ✅ Category and keyword support
- ✅ Thumbnail upload
- ✅ Caption/subtitle upload
- ✅ Playlist management

**Authentication:** OAuth 2.0 (no service accounts)
**Permissions Required:**
- `https://www.googleapis.com/auth/youtube.upload`
- `https://www.googleapis.com/auth/youtube`

**Setup Process:**
1. Create Google Cloud project
2. Enable YouTube Data API v3
3. Configure OAuth 2.0 credentials (Desktop app)
4. Get refresh token (one-time setup)
5. Use refresh token for ongoing uploads

**API Endpoint:**
```
POST https://www.googleapis.com/upload/youtube/v3/videos
```

**Upload Process:**
1. Call `videos.insert` with video file + metadata
2. (Optional) Call `thumbnails.set` for custom thumbnail
3. (Optional) Call `captions.insert` for subtitles

**Rate Limits:**
- Quota-based system (10,000 units/day default)
- Upload = 1,600 units per video

**Sources:**
- [Upload a Video | YouTube Data API](https://developers.google.com/youtube/v3/guides/uploading_a_video)
- [YouTube Data API | Google for Developers](https://developers.google.com/youtube/v3)
- [From Zero to First Upload: YouTube API Guide (2025)](https://medium.com/@dorangao/from-zero-to-first-upload-a-from-scratch-guide-to-publishing-videos-to-youtube-via-api-2025-73251a9324bd)

---

### 5. LinkedIn (Posts API)

**API:** LinkedIn Posts API (2025-11 version)
**Latest Version:** November 2025
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Text Posts | ✅ Yes | ⚠️ Limited | Active |
| Image Posts | ✅ Yes | ⚠️ Limited | Active |
| Video Posts | ✅ Yes | ⚠️ Limited | Active |
| Article Posts | ✅ Yes | ⚠️ Limited | Active |
| Multi-Image | ✅ Yes | ⚠️ Limited | Active |
| Polls | ✅ Yes | ⚠️ Limited | Active |

**Key Features:**
- ✅ Personal and Company Page posting
- ✅ Rich media support (images, videos)
- ✅ Video reusability across ad campaigns
- ✅ Article sharing with metadata
- ✅ Multi-image carousel posts
- ✅ Poll creation

**Authentication:** OAuth 2.0 with OpenID Connect
**Permissions Required:**
- `w_member_social` (create/modify/delete posts)
- `openid` (Sign In with LinkedIn)
- `email` (get author ID)

**API Endpoint:**
```
POST https://api.linkedin.com/rest/posts
```

**Publishing Process:**
1. **Text posts:** Direct POST with content
2. **Image posts:**
   - Upload image via Images API → get `urn:li:image:{id}`
   - Create post with image URN
3. **Video posts:**
   - Upload video via Videos API → get `urn:li:video:{id}`
   - Create post with video URN

**Video Reusability:**
- Videos uploaded for organic posts can be reused in video ads
- No need for separate upload

**Rate Limits:**
- Standard OAuth rate limits apply
- No specific documented limits

**Sources:**
- [Posts API - LinkedIn | Microsoft Learn](https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/posts-api?view=li-lms-2025-11)
- [Share on LinkedIn - LinkedIn | Microsoft Learn](https://learn.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/share-on-linkedin)
- [Videos API - LinkedIn](https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/videos-api?view=li-lms-2025-11)

---

### 6. X (Twitter) API v2

**API:** X API v2
**Latest Pricing Update:** 2025
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Tweets (Posts) | ✅ Yes | ✅ Via 3rd party | Active |
| Threads | ✅ Yes | ✅ Via 3rd party | Active |
| Media Tweets | ✅ Yes | ✅ Via 3rd party | Active |
| Polls | ✅ Yes | ⚠️ Limited | Active |

**Key Features:**
- ✅ Text tweets up to 280 characters (4,000 for Premium)
- ✅ Media upload (images, videos, GIFs)
- ✅ Thread creation
- ✅ Reply controls
- ⚠️ **Media upload moved to v2 API** (v1.1 deprecated June 2025)

**Authentication:** OAuth 1.0a with HMAC-SHA1
**Permissions Required:**
- App permissions set to "Read and Write"

**API Endpoint:**
```
POST https://api.x.com/2/tweets
```

**Content-Type:** `application/json` (required for v2)

**Pricing Tiers (2025):**

| Tier | Price | Posts/Month | Reads/Month |
|------|-------|------------|-------------|
| Free | $0 | 500 | 100 |
| Basic | $200 | 10,000 | 10,000 |
| Pro | $5,000 | 1,000,000 | 1M |
| Enterprise | $42,000+ | 50,000,000+ | Custom |

**Recent Changes (2025):**
- June 9, 2025: v1.1 media upload deprecated
- Must use v2 API for all operations
- ProMediaAPI removed

**Rate Limits:**
- Based on pricing tier
- Free tier: 500 posts/month

**Sources:**
- [How to Get an X (Twitter) API Key and Post with the Free Tier (October 2025)](https://medium.com/@modernrobinhood1998/how-to-get-an-x-twitter-api-key-and-post-with-the-free-tier-october-2025-b428b23e3fa8)
- [POST /2/tweets | Docs | Twitter Developer Platform](https://developer.x.com/en/docs/x-api/tweets/manage-tweets/api-reference/post-tweets)
- [X API v2 support | Twitter Developer Platform](https://developer.x.com/en/support/x-api/v2)

---

### 7. Pinterest (API v5)

**API:** Pinterest API v5
**Latest Version:** v5
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Pins (Images) | ✅ Yes | ✅ Yes | Active |
| Video Pins | ✅ Yes | ✅ Yes | Active |
| Idea Pins | ✅ Yes | ✅ Yes | Active |
| Boards | ✅ Yes | N/A | Active |

**Key Features:**
- ✅ Pin creation with images/videos
- ✅ Board creation and management
- ✅ Rich metadata (title, description, link)
- ✅ Board assignment
- ✅ Scheduling support via third-party tools

**Platform Scale (2025):**
- 570 million monthly active users (Q1 2025)
- 1.5 billion Pins saved per week
- 340 million ad reach globally

**Authentication:** OAuth 2.0
**Permissions Required:**
- `pins:read`
- `pins:write`
- `boards:read`
- `boards:write`

**API Endpoint:**
```
POST https://api.pinterest.com/v5/pins
```

**Pin Creation Process:**
1. Get access token via OAuth
2. POST request with:
   - Media (image/video)
   - Title and description
   - Board ID
   - Destination link (optional)
3. Receive Pin ID and URL

**Board Management:**
- Create boards programmatically
- Update board details
- List all user boards
- Pin organization

**Rate Limits:**
- Standard OAuth limits
- No specific published limits

**Sources:**
- [Creating boards & Pins](https://developers.pinterest.com/docs/api-features/creating-boards-and-pins/)
- [Pinterest Developers](https://developers.pinterest.com/)
- [Step-by-Step Guide to Creating Pins with Pinterest API](https://www.tutscoder.com/post/pinterest-api-integration)
- [A Developer's Guide to the Pinterest API](https://getlate.dev/blog/pinterest-api)

---

### 8. Threads (Meta)

**API:** Threads API (Meta)
**Latest Major Update:** July 25, 2025
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Text Posts | ✅ Yes | ✅ Yes (via auto_publish) | Active |
| Image Posts | ✅ Yes | ✅ Yes | Active |
| Video Posts | ✅ Yes | ✅ Yes | Active |
| GIF Posts | ✅ Yes (2025) | ✅ Yes | Active |
| Polls | ✅ Yes (July 2025) | ✅ Yes | Active |
| Location Tags | ✅ Yes (July 2025) | ✅ Yes | Active |
| Topic Tags | ✅ Yes (July 2025) | ✅ Yes | Active |

**Key Features (2025 Updates):**
- ✅ **Simplified publishing:** `auto_publish_text` parameter for single API call
- ✅ **Poll creation and retrieval:** Interactive polls in posts
- ✅ **Location tagging:** Geographic targeting
- ✅ **Real-time webhooks:** Mentions notifications
- ✅ **Public profile access:** Read public Threads profiles
- ✅ **Topic tags:** Categorize posts
- ✅ **Click metrics:** Track engagement
- ✅ **Enhanced search:** Improved content discovery
- ✅ **GIF support:** Animated GIFs in posts
- ✅ **Reply restrictions:** Followers-only replies option

**Authentication:** OAuth 2.0 (Meta)
**Permissions Required:**
- `threads_basic`
- `threads_content_publish`
- `threads_manage_insights` (for analytics)

**API History:**
- June 18, 2024: Initial API launch (Cannes Lions)
- July 25, 2025: Major feature expansion

**Publishing Process:**
1. **Simple text post:**
   ```
   POST /threads/publish?auto_publish_text=true
   ```
2. **Media posts:** Traditional container → publish flow
3. **Polls:** Create with poll options array
4. **Location:** Add location search + tag

**Webhooks:**
- Real-time mention notifications
- Faster response times
- Community management support

**Rate Limits:**
- Standard Meta Graph API limits
- No specific Threads limits published

**Sources:**
- [Meta expands Threads API with advanced features for developers](https://ppc.land/meta-expands-threads-api-with-advanced-features-for-developers/)
- [Meta Expands Threads API with Advanced Features](https://martech360.com/social-media-technology/social-media-platforms/meta-expands-threads-api-with-advanced-features-to-empower-developers-and-boost-engagement/)
- [Threads API Changelog](https://www.threads.com/@threadsapi.changelog)
- [Meta Adds More Functionality to the Threads API](https://www.socialmediatoday.com/news/meta-adds-more-functionality-threads-api/735139/)

---

### 9. Google Business Profile

**API:** Google Business Profile APIs (formerly Google My Business API)
**Latest Update:** November 25, 2025 (Multi-location scheduling)
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Posts | ✅ Yes | ✅ Yes (NEW Nov 2025) | Active |
| Events | ✅ Yes | ✅ Yes | Active |
| Offers | ✅ Yes | ✅ Yes | Active |
| Call-to-Action | ✅ Yes | ✅ Yes | Active |
| Q&A | ✅ Yes | N/A | Active |
| Reviews | ✅ Response only | N/A | Active |

**Key Features:**
- ✅ **NEW (Nov 25, 2025):** Scheduling support
- ✅ **NEW (Nov 25, 2025):** Multi-location publishing (one post → multiple locations)
- ✅ Post types: Event, CTA, Offer, What's New
- ✅ Review management and responses
- ✅ Q&A management
- ⚠️ Single image only (API limitation)
- ❌ Product posts not supported via API
- ❌ Video posts not supported via API (yet)

**Authentication:** OAuth 2.0 (Google Cloud)
**Permissions Required:**
- `https://www.googleapis.com/auth/business.manage`

**API Endpoint:**
```
POST /v4/accounts/{accountId}/locations/{locationId}/localPosts
```

**Post Types:**
1. **What's New:** General updates
2. **Event:** Time-bound events
3. **Offer:** Promotional offers
4. **Call to Action:** Action buttons (Learn More, Sign Up, etc.)

**Recent Updates (Nov 2025):**
- Scheduling: Set future publish times
- Multi-location: Apply one post to multiple locations instantly
- Improved UI in Business Profile Manager

**Limitations:**
- Only 1 image per post via API (GBP UI supports up to 10)
- No video support via API (GBP UI supports video)
- No product posts via API

**Third-Party Integration:**
- Social Champ, Hootsuite, Sendible, Sprout Social, Buffer all support GBP
- Unified APIs available for cross-platform management

**Sources:**
- [Google Business Profiles adds scheduling and multi-location publishing (Nov 25, 2025)](https://searchengineland.com/google-business-profiles-adds-scheduling-and-multi-location-publishing-to-google-posts-465177)
- [Create Posts on Google | Google Business Profile APIs](https://developers.google.com/my-business/content/posts-data)
- [Google Business Profile API - Ayrshare API Documentation](https://www.ayrshare.com/docs/apis/post/social-networks/google)

---

### 10. Tumblr

**API:** Tumblr API v2 with Neue Post Format (NPF)
**Latest Version:** v2
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Text Posts | ✅ Yes | ✅ Yes | Active |
| Photo Posts | ✅ Yes | ✅ Yes | Active |
| Video Posts | ✅ Yes | ✅ Yes | Active |
| Audio Posts | ✅ Yes | ✅ Yes | Active |
| Link Posts | ✅ Yes | ✅ Yes | Active |
| Quote Posts | ✅ Yes | ✅ Yes | Active |
| Chat Posts | ✅ Yes | ✅ Yes | Active |

**Key Features:**
- ✅ **Neue Post Format (NPF):** Modern JSON specification
- ✅ Post states: published, queue, draft, private
- ✅ Scheduling via `publish_on` parameter
- ✅ Queue management
- ✅ Rich content blocks
- ✅ Reblog support

**Authentication:** OAuth 1.0a
**Permissions Required:**
- OAuth token and secret

**API Endpoint:**
```
POST https://api.tumblr.com/v2/blog/{blog-identifier}/post
```

**Post States:**
- **published:** Publicly published immediately (default)
- **queue:** Added to blog's post queue
- **draft:** Saved as draft
- **private:** Privately published immediately

**Neue Post Format (NPF):**
- Modern JSON-based content specification
- Easier than HTML manipulation
- Future-proof (all posts will eventually be NPF)
- Use `npf=true` query parameter to force NPF

**Scheduling:**
```json
{
  "state": "published",
  "publish_on": "2025-12-01T14:30:00Z"
}
```

**Recent Issues (2025):**
- API key acquisition process has changed
- May present challenges for new developers

**Official Resources:**
- [GitHub: Tumblr API Documentation](https://github.com/tumblr/docs)
- [Tumblr NPF Specification](https://www.tumblr.com/docs/npf)

**Sources:**
- [API | Tumblr](https://www.tumblr.com/docs/en/api/v2)
- [Tumblr Engineering — New Public API and Neue Post Format Documentation](https://engineering.tumblr.com/post/179080448939/new-public-api-and-neue-post-format-documentation)
- [GitHub - tumblr/pytumblr: A Python Tumblr API v2 Client](https://github.com/tumblr/pytumblr)

---

### 11. Reddit

**API:** Reddit Data API
**Latest Documentation Update:** November 11, 2025
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Text Posts | ✅ Yes | ✅ Via 3rd party | Active |
| Link Posts | ✅ Yes | ✅ Via 3rd party | Active |
| Image Posts | ✅ Yes | ✅ Via 3rd party | Active |
| Video Posts | ✅ Yes | ✅ Via 3rd party | Active |
| Comments | ✅ Yes | N/A | Active |
| Replies | ✅ Yes | N/A | Active |

**Key Features:**
- ✅ Submit posts to subreddits
- ✅ Text, link, and media submissions
- ✅ Comment and reply management
- ✅ Subreddit-specific rules compliance
- ⚠️ Aggressive rate limiting on native API
- ✅ Third-party scheduling support

**Authentication:** OAuth 2.0
**Permissions Required:**
- Registered OAuth app
- `submit` scope

**API Endpoint:**
```
POST https://www.reddit.com/api/submit
```

**Submission Parameters:**
- `sr` (subreddit)
- `kind` (link, self, image, video)
- `title` (required)
- `text` or `url` (depending on kind)

**Scheduling (Third-Party):**
```json
{
  "scheduled_at": "2025-12-01T14:30:00Z",
  "subreddit": "r/example",
  "title": "Post title",
  "content": "Post content"
}
```

**Rate Limiting:**
- Native API: Very aggressive limits
- Smart queuing required to stay within limits
- Third-party APIs use automatic spacing

**Best Practices:**
- Respect subreddit rules
- Avoid spam
- Engage authentically
- Use appropriate flair
- Check posting guidelines per subreddit

**Recent Updates (2025):**
- Data API Wiki updated November 11, 2025
- Enhanced OAuth documentation
- Better rate limit guidance

**Sources:**
- [Reddit Data API Wiki – Reddit Help (Updated Nov 11, 2025)](https://support.reddithelp.com/hc/en-us/articles/16160319875092-Reddit-Data-API-Wiki)
- [Post to Reddit using its API](https://dev.to/codybontecou/post-to-reddit-using-its-api-15g7)
- [API: submit · reddit-archive/reddit Wiki](https://github.com/reddit-archive/reddit/wiki/API:-submit)
- [Automate Reddit with a reddit posting api](https://getlate.dev/blog/reddit-posting-api)

---

### 12. Snapchat (Note: Ads-focused, not organic)

**API:** Snapchat Marketing API
**Status:** Open to all developers
**Publishing Capabilities:**

| Content Type | Supported | Scheduling | API Status |
|-------------|-----------|------------|------------|
| Ads | ✅ Yes | ✅ Yes | Active |
| Organic Snaps | ❌ No (use Creative Kit) | N/A | Not via Marketing API |
| Stories | ❌ No (use Creative Kit) | N/A | Not via Marketing API |

**Important Note:**
- ⚠️ **Marketing API is for ADVERTISING only, not organic content**
- ✅ For organic content sharing, use **Snap Kit → Creative Kit**

**Marketing API Features:**
- Campaign management
- Ad creation and scheduling
- Targeting and optimization
- Performance analytics

**Creative Kit (for organic sharing):**
- Enables sharing from third-party apps to Snapchat
- User shares content directly to their Story or friends
- Not a programmatic posting API

**2025 Updates:**
- Old API docs redirect to https://developers.snap.com/api/
- New objective_v2_properties required for campaigns
- CHAT_FEED placement enforcement (Nov 27, 2025)

**Recommendation:**
- For ad campaigns: Use Marketing API
- For organic content: Implement Creative Kit for user-initiated sharing
- No direct organic posting API available

**Sources:**
- [Snapchat Marketing API](https://marketingapi.snapchat.com/)
- [Snapchat API: Developer Guide](https://www.getphyllo.com/post/snapchat-api-guide)
- [Snapchat's API](https://businesshelp.snapchat.com/s/topic/0TO0y000000cYW0GAM/snapchats-api?language=en_US)

---

## Current Implementation Analysis

### What's Already Built

#### ✅ Fully Implemented

1. **Meta (Facebook/Instagram) Integration**
   - System User token management
   - Facebook Pages connection
   - Instagram Business Account connection
   - Asset selection (Pages, Instagram accounts)
   - Access token validation and refresh
   - Multi-account support

2. **Social Post Management**
   - Post creation interface
   - Multi-platform selection
   - Post type selection (Feed, Reels, Stories, Carousel)
   - Media upload support
   - Content editor with AI assistant
   - Draft, scheduled, and published states
   - Post scheduling

3. **AI Content Enhancement**
   - Gemini 2.5 Flash integration
   - Content transformation (shorter, longer, formal, casual)
   - Hashtag generation
   - Emoji suggestion
   - Arabic language support

4. **Database Architecture**
   - `cmis.social_posts` table with RLS
   - `cmis_platform.platform_connections` table
   - Post type support: feed, reel, story, carousel, tweet, thread
   - Scheduling columns
   - Media URL storage

### ⚠️ Partially Implemented

1. **Publishing Service**
   - Basic structure exists
   - `publishToPlatform()` method stub
   - Needs platform-specific implementations

2. **Platform Services**
   - `MetaPostsService` exists
   - Other platform services missing

### ❌ Not Implemented

1. **Platform Integrations**
   - TikTok organic posting
   - YouTube video upload
   - LinkedIn posts
   - X (Twitter) tweets
   - Pinterest pins
   - Threads posts
   - Google Business Profile posts
   - Tumblr posts
   - Reddit submissions

2. **Advanced Features**
   - Cross-platform analytics
   - Optimal posting time analysis
   - Content performance tracking
   - Auto-scheduling based on engagement
   - Platform-specific format optimization

---

## Gap Analysis

### Critical Gaps (High Priority)

| Gap | Impact | Effort | Priority |
|-----|--------|--------|----------|
| TikTok Integration | High - Major platform | High - Audit required | P0 |
| YouTube Upload | High - Video platform | Medium | P0 |
| LinkedIn Publishing | High - B2B essential | Medium | P0 |
| X (Twitter) Integration | High - Real-time platform | Medium | P0 |
| Threads Publishing | Medium - Growing platform | Low - Uses Meta API | P1 |

### Medium Gaps (Medium Priority)

| Gap | Impact | Effort | Priority |
|-----|--------|--------|----------|
| Pinterest Integration | Medium - Visual platform | Medium | P1 |
| Google Business Profile | Medium - Local businesses | Medium | P1 |
| Publishing Service Implementation | High - Core functionality | Medium | P1 |
| Advanced Scheduling | Medium - UX improvement | Low | P2 |

### Low Gaps (Low Priority)

| Gap | Impact | Effort | Priority |
|-----|--------|--------|----------|
| Tumblr Integration | Low - Niche platform | Low | P3 |
| Reddit Integration | Low - Manual engagement better | Medium | P3 |
| Snapchat Creative Kit | Low - User-initiated only | Medium | P3 |

---

## Optimization Plan

### Phase 1: Complete Core Publishing (Priority P0-P1)

#### 1.1 Implement Platform Services

**Create unified platform service architecture:**

```php
// app/Services/Social/AbstractSocialPlatform.php
abstract class AbstractSocialPlatform
{
    abstract public function publish(array $content): array;
    abstract public function schedule(array $content, DateTime $scheduledTime): array;
    abstract public function validateContent(array $content): bool;
    abstract public function getPostTypes(): array;
    abstract public function getMediaRequirements(): array;
}
```

**Implement platform-specific services:**
- `TikTokSocialService.php`
- `YouTubeSocialService.php`
- `LinkedInSocialService.php`
- `TwitterSocialService.php`
- `ThreadsSocialService.php`
- `PinterestSocialService.php`
- `GoogleBusinessService.php`

#### 1.2 Update Publishing Service

```php
// app/Services/PublishingService.php
class PublishingService
{
    protected function publishToPlatform(Post $post): ?array
    {
        $platform = $this->getPlatformService($post->platform);

        return $platform->publish([
            'content' => $post->post_text,
            'media' => $post->media_urls,
            'post_type' => $post->post_type,
            'metadata' => $post->metadata,
        ]);
    }

    protected function getPlatformService(string $platform): AbstractSocialPlatform
    {
        return match($platform) {
            'facebook' => new FacebookSocialService(),
            'instagram' => new InstagramSocialService(),
            'tiktok' => new TikTokSocialService(),
            'youtube' => new YouTubeSocialService(),
            'linkedin' => new LinkedInSocialService(),
            'twitter' => new TwitterSocialService(),
            'threads' => new ThreadsSocialService(),
            'pinterest' => new PinterestSocialService(),
            'google_business' => new GoogleBusinessService(),
            'tumblr' => new TumblrSocialService(),
            'reddit' => new RedditSocialService(),
            default => throw new \Exception("Unsupported platform: {$platform}"),
        };
    }
}
```

#### 1.3 Expand Platform Connections UI

**Add connection cards for:**
- TikTok (OAuth + Audit status)
- YouTube (Google OAuth)
- LinkedIn (OAuth)
- X/Twitter (OAuth 1.0a)
- Pinterest (OAuth)
- Threads (Meta OAuth - reuse existing)
- Google Business Profile (Google OAuth)
- Tumblr (OAuth 1.0a)
- Reddit (OAuth 2.0)

**Update:**
`resources/views/settings/platform-connections/index.blade.php`

#### 1.4 Update Social Posts UI

**Platform selection enhancement:**
- Add all 11 platforms to dropdown
- Show platform-specific post types
- Display character limits per platform
- Show media requirements per platform

**Post type mapping per platform:**

```javascript
allPostTypes: {
    'facebook': [
        {value: 'feed', label: 'منشور عادي', icon: 'fa-newspaper'},
        {value: 'reel', label: 'ريل', icon: 'fa-video'},
        {value: 'story', label: 'قصة', icon: 'fa-circle'}
    ],
    'instagram': [
        {value: 'feed', label: 'منشور عادي', icon: 'fa-image'},
        {value: 'reel', label: 'ريل', icon: 'fa-video'},
        {value: 'story', label: 'قصة', icon: 'fa-circle'},
        {value: 'carousel', label: 'كاروسيل', icon: 'fa-images'}
    ],
    'tiktok': [
        {value: 'video', label: 'فيديو', icon: 'fa-video'},
        {value: 'photo', label: 'صورة', icon: 'fa-image'}
    ],
    'youtube': [
        {value: 'video', label: 'فيديو', icon: 'fa-video'},
        {value: 'short', label: 'شورت', icon: 'fa-film'}
    ],
    'linkedin': [
        {value: 'post', label: 'منشور', icon: 'fa-newspaper'},
        {value: 'article', label: 'مقال', icon: 'fa-file-alt'},
        {value: 'video', label: 'فيديو', icon: 'fa-video'}
    ],
    'twitter': [
        {value: 'tweet', label: 'تغريدة', icon: 'fa-comment'},
        {value: 'thread', label: 'سلسلة', icon: 'fa-list'}
    ],
    'threads': [
        {value: 'post', label: 'منشور', icon: 'fa-at'},
        {value: 'poll', label: 'استطلاع', icon: 'fa-poll'}
    ],
    'pinterest': [
        {value: 'pin', label: 'بن', icon: 'fa-thumbtack'},
        {value: 'video', label: 'فيديو', icon: 'fa-video'}
    ],
    'google_business': [
        {value: 'update', label: 'تحديث', icon: 'fa-newspaper'},
        {value: 'event', label: 'حدث', icon: 'fa-calendar'},
        {value: 'offer', label: 'عرض', icon: 'fa-tag'}
    ],
    'tumblr': [
        {value: 'text', label: 'نص', icon: 'fa-align-left'},
        {value: 'photo', label: 'صورة', icon: 'fa-image'},
        {value: 'video', label: 'فيديو', icon: 'fa-video'}
    ],
    'reddit': [
        {value: 'text', label: 'نص', icon: 'fa-align-left'},
        {value: 'link', label: 'رابط', icon: 'fa-link'},
        {value: 'image', label: 'صورة', icon: 'fa-image'}
    ]
}
```

### Phase 2: Advanced Scheduling & Optimization (Priority P2)

#### 2.1 Best Time to Post Analysis

```php
// app/Services/BestTimeAnalyzerService.php
class BestTimeAnalyzerService
{
    public function analyzeBestTimes(string $orgId, string $platform): array
    {
        // Analyze historical post performance
        // Return optimal posting times by day/hour
        // Factor in audience timezone
        // Consider platform-specific engagement patterns
    }

    public function suggestSchedule(array $posts, string $platform): array
    {
        // Suggest optimal posting schedule
        // Avoid oversaturation
        // Maximize reach and engagement
    }
}
```

#### 2.2 Queue Management

- Buffer-style queue system
- Auto-schedule to optimal times
- Time slot management
- Platform-specific queues

#### 2.3 Content Calendar

- Visual calendar view
- Drag-and-drop rescheduling
- Multi-platform view
- Campaign grouping

### Phase 3: Analytics & Insights (Priority P2-P3)

#### 3.1 Cross-Platform Analytics

- Unified metrics dashboard
- Platform comparison
- Best performing content
- Engagement trends
- Audience insights

#### 3.2 Performance Tracking

- Real-time post performance
- Reach and impressions
- Engagement rate
- Click-through rate
- Conversion tracking

#### 3.3 AI-Powered Insights

- Content recommendations
- Optimal hashtag suggestions
- Engagement predictions
- Trend detection

### Phase 4: Bulk Operations & Automation (Priority P3)

#### 4.1 Bulk Publishing

- CSV import
- Multi-post creation
- Template-based posting
- Campaign duplication

#### 4.2 Automation Rules

- Auto-publish on conditions
- Cross-posting rules
- Content recycling
- RSS feed integration

#### 4.3 Approval Workflows

- Multi-level approvals
- Role-based permissions
- Draft → Review → Approve → Publish
- Rejection with feedback

---

## Implementation Priorities

### Sprint 1 (2 weeks): TikTok & YouTube

**Why these first:**
- High user demand
- Video-first platforms
- Different from Meta platforms

**Deliverables:**
1. TikTok OAuth integration
2. TikTok audit application process
3. TikTok video upload service
4. YouTube OAuth integration
5. YouTube video upload service
6. Platform connection UI updates

**Success Criteria:**
- ✅ Users can connect TikTok account
- ✅ Users can connect YouTube channel
- ✅ Can upload and publish videos to both platforms
- ✅ Scheduling works for both platforms

### Sprint 2 (2 weeks): LinkedIn & X (Twitter)

**Why these:**
- Professional and real-time platforms
- Text-focused, easier than video
- High business value

**Deliverables:**
1. LinkedIn OAuth integration
2. LinkedIn Posts API implementation
3. LinkedIn image/video upload
4. X (Twitter) OAuth 1.0a integration
5. X API v2 tweet posting
6. X media upload

**Success Criteria:**
- ✅ Users can connect LinkedIn accounts
- ✅ Users can connect X (Twitter) accounts
- ✅ Can publish text, image, and video posts
- ✅ Thread creation for X works

### Sprint 3 (1-2 weeks): Threads, Pinterest, Google Business

**Why these:**
- Medium priority platforms
- Growing user bases
- Good coverage completion

**Deliverables:**
1. Threads API integration (reuse Meta OAuth)
2. Pinterest OAuth and pin creation
3. Google Business Profile OAuth
4. GBP post publishing

**Success Criteria:**
- ✅ Users can publish to Threads
- ✅ Users can create Pinterest pins
- ✅ Local businesses can post to GBP

### Sprint 4 (1 week): Tumblr & Reddit

**Why last:**
- Lower priority platforms
- Niche audiences
- Manual engagement often better

**Deliverables:**
1. Tumblr OAuth and NPF posts
2. Reddit OAuth and submissions
3. Subreddit selector

**Success Criteria:**
- ✅ Users can publish to Tumblr
- ✅ Users can submit to Reddit
- ✅ Subreddit rules respected

### Sprint 5+ (Ongoing): Advanced Features

**Phase 2-4 features:**
- Best time analysis
- Queue management
- Analytics dashboard
- Bulk operations
- Automation rules
- Approval workflows

---

## Technical Architecture Recommendations

### 1. Service Layer Pattern

```
app/Services/Social/
├── AbstractSocialPlatform.php (base class)
├── Facebook/
│   ├── FacebookSocialService.php
│   └── FacebookAuthService.php
├── Instagram/
│   ├── InstagramSocialService.php
│   └── InstagramAuthService.php
├── TikTok/
│   ├── TikTokSocialService.php
│   ├── TikTokAuthService.php
│   └── TikTokAuditService.php
├── YouTube/
│   ├── YouTubeSocialService.php
│   └── YouTubeAuthService.php
├── LinkedIn/
│   ├── LinkedInSocialService.php
│   └── LinkedInAuthService.php
├── Twitter/
│   ├── TwitterSocialService.php
│   └── TwitterAuthService.php
├── Threads/
│   ├── ThreadsSocialService.php
│   └── ThreadsAuthService.php
├── Pinterest/
│   ├── PinterestSocialService.php
│   └── PinterestAuthService.php
├── GoogleBusiness/
│   ├── GoogleBusinessService.php
│   └── GoogleBusinessAuthService.php
├── Tumblr/
│   ├── TumblrSocialService.php
│   └── TumblrAuthService.php
└── Reddit/
    ├── RedditSocialService.php
    └── RedditAuthService.php
```

### 2. Queue System

```php
// app/Jobs/PublishSocialPostJob.php
class PublishSocialPostJob implements ShouldQueue
{
    public function handle(PublishingService $service)
    {
        $service->publishContent($this->post);
    }
}

// app/Jobs/ScheduledPostPublisherJob.php
class ScheduledPostPublisherJob implements ShouldQueue
{
    public function handle()
    {
        // Find posts scheduled for now
        // Dispatch PublishSocialPostJob for each
    }
}
```

### 3. Event System

```php
// app/Events/PostPublished.php
class PostPublished
{
    public function __construct(public Post $post) {}
}

// app/Listeners/TrackPostAnalytics.php
class TrackPostAnalytics
{
    public function handle(PostPublished $event)
    {
        // Track initial publish
        // Schedule analytics sync job
    }
}
```

### 4. Configuration Management

```php
// config/social-platforms.php
return [
    'facebook' => [
        'enabled' => env('FACEBOOK_ENABLED', true),
        'api_version' => 'v21.0',
        'max_media' => 10,
        'max_length' => 63206,
        'supported_types' => ['feed', 'reel', 'story'],
    ],
    'tiktok' => [
        'enabled' => env('TIKTOK_ENABLED', false),
        'api_version' => 'v2',
        'max_video_size' => 4096, // MB
        'max_video_duration' => 600, // seconds
        'requires_audit' => true,
        'supported_types' => ['video', 'photo'],
    ],
    // ... other platforms
];
```

---

## Database Schema Updates

### New Tables Needed

```sql
-- Platform-specific metadata
CREATE TABLE cmis_platform.oauth_tokens (
    token_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    platform TEXT NOT NULL, -- tiktok, youtube, linkedin, etc.
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    token_type TEXT DEFAULT 'Bearer',
    expires_at TIMESTAMPTZ,
    scope TEXT[],
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Publishing queue
CREATE TABLE cmis.publishing_queue (
    queue_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    post_id UUID NOT NULL REFERENCES cmis.social_posts(post_id),
    platform TEXT NOT NULL,
    scheduled_for TIMESTAMPTZ NOT NULL,
    status TEXT DEFAULT 'pending', -- pending, processing, published, failed
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    last_attempt_at TIMESTAMPTZ,
    error_message TEXT,
    published_at TIMESTAMPTZ,
    external_post_id TEXT,
    external_url TEXT,
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Analytics tracking
CREATE TABLE cmis.post_analytics (
    analytics_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    post_id UUID NOT NULL REFERENCES cmis.social_posts(post_id),
    platform TEXT NOT NULL,
    external_post_id TEXT NOT NULL,
    synced_at TIMESTAMPTZ NOT NULL,
    metrics JSONB NOT NULL, -- platform-specific metrics
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(post_id, platform, synced_at)
);
```

---

## Environment Variables Needed

```bash
# TikTok
TIKTOK_CLIENT_KEY=
TIKTOK_CLIENT_SECRET=
TIKTOK_REDIRECT_URI=

# YouTube (Google Cloud)
YOUTUBE_CLIENT_ID=
YOUTUBE_CLIENT_SECRET=
YOUTUBE_REDIRECT_URI=

# LinkedIn
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
LINKEDIN_REDIRECT_URI=

# X (Twitter)
TWITTER_API_KEY=
TWITTER_API_SECRET=
TWITTER_ACCESS_TOKEN=
TWITTER_ACCESS_SECRET=
TWITTER_BEARER_TOKEN=

# Pinterest
PINTEREST_APP_ID=
PINTEREST_APP_SECRET=
PINTEREST_REDIRECT_URI=

# Google Business Profile (use YouTube/Google Cloud)
GOOGLE_BUSINESS_PROFILE_ENABLED=true

# Tumblr
TUMBLR_CONSUMER_KEY=
TUMBLR_CONSUMER_SECRET=

# Reddit
REDDIT_CLIENT_ID=
REDDIT_CLIENT_SECRET=
REDDIT_REDIRECT_URI=
```

---

## Testing Strategy

### Unit Tests

```php
// tests/Unit/Services/TikTokSocialServiceTest.php
class TikTokSocialServiceTest extends TestCase
{
    public function test_validates_video_format()
    {
        // Test video format validation
    }

    public function test_uploads_video_successfully()
    {
        // Mock TikTok API
        // Test upload
    }
}
```

### Integration Tests

```php
// tests/Integration/SocialPublishingWorkflowTest.php
class SocialPublishingWorkflowTest extends TestCase
{
    public function test_complete_publishing_workflow()
    {
        // Create post
        // Schedule
        // Publish to multiple platforms
        // Verify external IDs
        // Check analytics sync
    }
}
```

### Feature Tests

```php
// tests/Feature/API/SocialPostAPITest.php
class SocialPostAPITest extends TestCase
{
    public function test_creates_multi_platform_post()
    {
        // API call to create post
        // Select multiple platforms
        // Verify queue entries
    }
}
```

---

## Documentation Required

1. **Platform Setup Guides**
   - How to get TikTok API access
   - YouTube API setup walkthrough
   - LinkedIn app creation
   - X Developer account setup
   - Pinterest app registration
   - Google Cloud project setup
   - Tumblr API key
   - Reddit app creation

2. **User Guides**
   - Connecting each platform
   - Publishing to each platform
   - Platform-specific best practices
   - Character limits and media requirements

3. **Developer Guides**
   - Adding new platform integrations
   - Custom post type handlers
   - Analytics sync patterns
   - Webhook handling

---

## Cost Estimates

### API Costs (Monthly)

| Platform | Cost | Notes |
|----------|------|-------|
| Facebook/Instagram | Free | Graph API free tier |
| TikTok | Free | Content Posting API free |
| YouTube | Free | Data API free (quota-based) |
| LinkedIn | Free | Posts API free |
| X (Twitter) | $0-$200+ | Free: 500 posts/mo, Basic: $200 |
| Pinterest | Free | API free tier |
| Threads | Free | Meta platform |
| Google Business | Free | API free |
| Tumblr | Free | API free |
| Reddit | Free | API free |

**Estimated Total:** $0-$200/month (depending on X tier)

### Development Costs (Estimates)

| Sprint | Duration | Focus | Effort |
|--------|----------|-------|--------|
| Sprint 1 | 2 weeks | TikTok + YouTube | High |
| Sprint 2 | 2 weeks | LinkedIn + X | Medium |
| Sprint 3 | 1-2 weeks | Threads + Pinterest + GBP | Medium |
| Sprint 4 | 1 week | Tumblr + Reddit | Low |
| Sprint 5+ | Ongoing | Advanced features | Variable |

**Total Initial Implementation:** 6-7 weeks

---

## Risk Assessment

### High Risk

| Risk | Impact | Mitigation |
|------|--------|------------|
| TikTok audit rejection | Cannot publish publicly | Apply early, ensure ToS compliance |
| X API pricing changes | Budget impact | Monitor pricing, consider alternatives |
| Platform API deprecation | Feature loss | Monitor changelogs, maintain flexibility |
| Rate limit violations | Service interruption | Implement queueing, respect limits |

### Medium Risk

| Risk | Impact | Mitigation |
|------|--------|------------|
| OAuth token expiration | Publishing failures | Auto-refresh tokens, user notifications |
| Media format incompatibility | Upload failures | Format validation, conversion |
| Platform downtime | Temporary outage | Retry logic, error handling |

### Low Risk

| Risk | Impact | Mitigation |
|------|--------|------------|
| Character limit changes | Content truncation | Dynamic limit checking |
| New post types | Feature gap | Monitor updates, add support |

---

## Success Metrics

### Phase 1 (Core Publishing)

- ✅ 10 platforms connected and operational
- ✅ 95%+ successful publish rate
- ✅ < 30 second average publish time
- ✅ Zero data loss incidents

### Phase 2 (Optimization)

- ✅ 30%+ engagement improvement (optimal timing)
- ✅ 50%+ time saved (queue automation)
- ✅ 20%+ reach increase (best time posting)

### Phase 3 (Analytics)

- ✅ 100% post performance tracked
- ✅ Cross-platform comparison available
- ✅ AI insights accuracy >80%

### Phase 4 (Automation)

- ✅ 80%+ posts automated
- ✅ 90%+ approval workflow adoption
- ✅ 5x bulk operation efficiency

---

## Conclusion

This research provides a comprehensive roadmap for implementing multi-platform social media publishing across 11 major platforms. The phased approach prioritizes high-impact platforms first while maintaining flexibility for future enhancements.

**Next Steps:**
1. ✅ Review and approve this research document
2. ⏳ Begin Sprint 1: TikTok & YouTube integration
3. ⏳ Set up developer accounts for all platforms
4. ⏳ Implement base AbstractSocialPlatform architecture
5. ⏳ Create platform-specific services one by one

**Estimated Time to Full Implementation:** 6-7 weeks for core features, ongoing for advanced features.

---

## Appendix: API Reference & Sources

All sources are embedded within each platform section above. For quick reference:

- **Instagram:** [Instagram Graph API Use Cases](https://www.getphyllo.com/post/instagram-graph-api-use-cases-in-2025-iv)
- **Facebook:** [Facebook Reels API](https://vistasocial.com/insights/facebook-reels-api-and-specifications/)
- **TikTok:** [Content Posting API Guide](https://developers.tiktok.com/doc/content-posting-api-get-started)
- **YouTube:** [Upload Video Guide](https://developers.google.com/youtube/v3/guides/uploading_a_video)
- **LinkedIn:** [Posts API (Nov 2025)](https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/posts-api?view=li-lms-2025-11)
- **X (Twitter):** [POST /2/tweets](https://developer.x.com/en/docs/x-api/tweets/manage-tweets/api-reference/post-tweets)
- **Pinterest:** [Creating Pins Guide](https://developers.pinterest.com/docs/api-features/creating-boards-and-pins/)
- **Threads:** [Meta Expands Threads API (July 2025)](https://ppc.land/meta-expands-threads-api-with-advanced-features-for-developers/)
- **Google Business:** [Multi-location Publishing (Nov 25, 2025)](https://searchengineland.com/google-business-profiles-adds-scheduling-and-multi-location-publishing-to-google-posts-465177)
- **Tumblr:** [Tumblr API v2](https://www.tumblr.com/docs/en/api/v2)
- **Reddit:** [Reddit Data API Wiki (Nov 11, 2025)](https://support.reddithelp.com/hc/en-us/articles/16160319875092-Reddit-Data-API-Wiki)

---

**Document Version:** 1.0
**Last Updated:** November 26, 2025
**Author:** Claude Code (AI Research Assistant)
**Status:** ✅ Complete - Ready for Review
