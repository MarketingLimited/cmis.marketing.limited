# CMIS Comprehensive Agent Architecture Plan
## Feature-Level Specialized Agents for Maximum Precision

**Created:** 2025-11-23
**Version:** 1.0 - Complete Feature Granularity
**Total Planned Agents:** 180+ specialized agents
**Current Agents:** 47 agents
**New Agents to Create:** 133+ agents

---

## 🎯 Vision & Philosophy

### Current State (47 Agents)
Currently, CMIS has **domain-level agents** (e.g., `cmis-campaign-expert`, `cmis-meta-ads-specialist`). These handle entire domains but lack precision for specific features.

### Target State (180+ Agents)
Transform to **feature-level agents** where each agent is a specialist in ONE specific feature or sub-feature. Examples:
- Instead of `cmis-meta-ads-specialist` → Break into 20+ agents
- `cmis-meta-custom-audiences` - Custom Audience creation expert
- `cmis-meta-lookalike-audiences` - Lookalike Audience specialist
- `cmis-meta-advantage-plus-creative` - Advantage+ Creative expert
- `cmis-meta-placements-manual` - Manual placement configuration
- `cmis-meta-placements-advantage-plus` - Advantage+ placement optimization
- And so on...

### Benefits of Feature-Level Granularity
✅ **Deep Expertise:** Each agent knows EVERYTHING about ONE feature
✅ **Easy Maintenance:** Update one agent when API changes for that feature
✅ **Clear Responsibility:** No confusion about which agent handles what
✅ **Faster Development:** Developers know exactly which agent to ask
✅ **Better Testing:** Can test each feature's agent independently
✅ **Scalability:** Add new features = add new agents

---

## 📊 Agent Architecture Overview

### Agent Organization Structure

```
.claude/agents/
├── README.md (Updated master catalog)
│
├── Core Platform Agents/ (Keep existing 47, update them)
│   ├── cmis-orchestrator.md (Master coordinator)
│   ├── cmis-context-awareness.md
│   └── ... (existing agents)
│
├── Meta Advertising Agents/ (20+ new agents)
│   ├── Audience Management/
│   │   ├── cmis-meta-custom-audiences.md
│   │   ├── cmis-meta-lookalike-audiences.md
│   │   ├── cmis-meta-saved-audiences.md
│   │   ├── cmis-meta-advantage-plus-audiences.md
│   │   └── cmis-meta-audience-insights.md
│   ├── Campaign Structure/
│   │   ├── cmis-meta-campaign-objectives.md
│   │   ├── cmis-meta-campaign-budget-optimization.md
│   │   └── cmis-meta-campaign-bidding-strategies.md
│   ├── Ad Set Features/
│   │   ├── cmis-meta-placements-manual.md
│   │   ├── cmis-meta-placements-advantage-plus.md
│   │   ├── cmis-meta-optimization-events.md
│   │   └── cmis-meta-delivery-optimization.md
│   ├── Creative Features/
│   │   ├── cmis-meta-dynamic-creative.md
│   │   ├── cmis-meta-advantage-plus-creative.md
│   │   ├── cmis-meta-video-ads.md
│   │   └── cmis-meta-carousel-ads.md
│   └── Tracking & Attribution/
│       ├── cmis-meta-pixel-setup.md
│       ├── cmis-meta-conversion-api.md
│       └── cmis-meta-attribution-settings.md
│
├── Google Ads Agents/ (25+ new agents)
│   ├── Campaign Types/
│   │   ├── cmis-google-search-campaigns.md
│   │   ├── cmis-google-display-campaigns.md
│   │   ├── cmis-google-video-campaigns.md
│   │   ├── cmis-google-shopping-campaigns.md
│   │   ├── cmis-google-performance-max.md
│   │   └── cmis-google-discovery-campaigns.md
│   ├── Bidding & Budget/
│   │   ├── cmis-google-smart-bidding-tcpa.md
│   │   ├── cmis-google-smart-bidding-troas.md
│   │   ├── cmis-google-smart-bidding-maximize-conversions.md
│   │   └── cmis-google-budget-pacing.md
│   ├── Targeting/
│   │   ├── cmis-google-keyword-targeting.md
│   │   ├── cmis-google-audience-targeting.md
│   │   ├── cmis-google-demographic-targeting.md
│   │   └── cmis-google-rlsa.md
│   └── Quality & Optimization/
│       ├── cmis-google-quality-score.md
│       ├── cmis-google-ad-extensions.md
│       └── cmis-google-responsive-search-ads.md
│
├── TikTok Ads Agents/ (15+ new agents)
│   ├── Campaign Features/
│   │   ├── cmis-tiktok-campaign-objectives.md
│   │   ├── cmis-tiktok-spark-ads.md
│   │   └── cmis-tiktok-shopping-ads.md
│   ├── Creative Formats/
│   │   ├── cmis-tiktok-video-ads.md
│   │   ├── cmis-tiktok-in-feed-ads.md
│   │   └── cmis-tiktok-top-view-ads.md
│   └── Targeting/
│       ├── cmis-tiktok-interest-targeting.md
│       ├── cmis-tiktok-behavioral-targeting.md
│       └── cmis-tiktok-custom-audiences.md
│
├── LinkedIn Ads Agents/ (12+ new agents)
│   ├── Campaign Features/
│   │   ├── cmis-linkedin-sponsored-content.md
│   │   ├── cmis-linkedin-sponsored-messaging.md
│   │   └── cmis-linkedin-dynamic-ads.md
│   ├── B2B Targeting/
│   │   ├── cmis-linkedin-job-title-targeting.md
│   │   ├── cmis-linkedin-company-targeting.md
│   │   ├── cmis-linkedin-industry-targeting.md
│   │   └── cmis-linkedin-seniority-targeting.md
│   └── Lead Generation/
│       ├── cmis-linkedin-lead-gen-forms.md
│       └── cmis-linkedin-matched-audiences.md
│
├── Twitter Ads Agents/ (10+ new agents)
│   ├── Campaign Features/
│   │   ├── cmis-twitter-promoted-tweets.md
│   │   ├── cmis-twitter-promoted-accounts.md
│   │   └── cmis-twitter-promoted-trends.md
│   ├── Targeting/
│   │   ├── cmis-twitter-keyword-targeting.md
│   │   ├── cmis-twitter-follower-targeting.md
│   │   └── cmis-twitter-conversation-targeting.md
│   └── Creative/
│       ├── cmis-twitter-video-ads.md
│       └── cmis-twitter-cards.md
│
├── Snapchat Ads Agents/ (12+ new agents)
│   ├── Campaign Features/
│   │   ├── cmis-snapchat-snap-ads.md
│   │   ├── cmis-snapchat-story-ads.md
│   │   ├── cmis-snapchat-ar-lenses.md
│   │   └── cmis-snapchat-filters.md
│   ├── Targeting/
│   │   ├── cmis-snapchat-lifestyle-categories.md
│   │   └── cmis-snapchat-lookalike-audiences.md
│   └── Lead Generation/
│       └── cmis-snapchat-instant-forms.md
│
├── Campaign Domain Agents/ (15+ new agents)
│   ├── Campaign Lifecycle/
│   │   ├── cmis-campaign-planning.md
│   │   ├── cmis-campaign-execution.md
│   │   ├── cmis-campaign-monitoring.md
│   │   └── cmis-campaign-optimization.md
│   ├── Budget Management/
│   │   ├── cmis-budget-allocation.md
│   │   ├── cmis-budget-pacing.md
│   │   └── cmis-budget-forecasting.md
│   └── Campaign Context/
│       ├── cmis-field-definitions.md
│       ├── cmis-field-values.md
│       └── cmis-context-linking.md
│
├── Audience Domain Agents/ (10+ new agents)
│   ├── cmis-audience-segmentation.md
│   ├── cmis-audience-builder.md
│   ├── cmis-audience-sync.md
│   └── cmis-audience-insights.md
│
├── Creative Domain Agents/ (12+ new agents)
│   ├── Asset Management/
│   │   ├── cmis-asset-library.md
│   │   ├── cmis-asset-organization.md
│   │   └── cmis-asset-versioning.md
│   ├── Content Creation/
│   │   ├── cmis-content-plans.md
│   │   ├── cmis-content-items.md
│   │   ├── cmis-creative-briefs.md
│   │   └── cmis-variation-policies.md
│   └── Templates/
│       ├── cmis-video-templates.md
│       └── cmis-audio-templates.md
│
├── Analytics Domain Agents/ (18+ new agents)
│   ├── Metrics & Reporting/
│   │   ├── cmis-metric-definitions.md
│   │   ├── cmis-campaign-metrics.md
│   │   ├── cmis-report-templates.md
│   │   └── cmis-scheduled-reports.md
│   ├── Forecasting & Prediction/
│   │   ├── cmis-forecasting-arima.md
│   │   ├── cmis-forecasting-prophet.md
│   │   ├── cmis-forecasting-statistical.md
│   │   └── cmis-trend-analysis.md
│   ├── A/B Testing/
│   │   ├── cmis-experiment-design.md
│   │   ├── cmis-variant-management.md
│   │   ├── cmis-statistical-significance.md
│   │   └── cmis-winner-selection.md
│   ├── Alerts & Monitoring/
│   │   ├── cmis-alert-rules.md
│   │   ├── cmis-alert-evaluation.md
│   │   ├── cmis-alert-notifications.md
│   │   └── cmis-anomaly-detection.md
│   └── Attribution/
│       ├── cmis-attribution-last-click.md
│       ├── cmis-attribution-first-click.md
│       ├── cmis-attribution-linear.md
│       ├── cmis-attribution-time-decay.md
│       └── cmis-attribution-data-driven.md
│
├── Social Domain Agents/ (8+ new agents)
│   ├── Publishing/
│   │   ├── cmis-social-scheduling.md
│   │   ├── cmis-social-publishing.md
│   │   └── cmis-best-time-optimization.md
│   ├── Content Management/
│   │   ├── cmis-content-library.md
│   │   └── cmis-post-history.md
│   └── Engagement/
│       └── cmis-social-engagement-tracking.md
│
└── Integration Domain Agents/ (10+ new agents)
    ├── OAuth & Authentication/
    │   ├── cmis-oauth-meta.md
    │   ├── cmis-oauth-google.md
    │   ├── cmis-oauth-tiktok.md
    │   └── cmis-oauth-linkedin.md
    ├── Webhooks/
    │   ├── cmis-webhook-meta.md
    │   ├── cmis-webhook-google.md
    │   └── cmis-webhook-verification.md
    └── Data Sync/
        └── cmis-platform-sync.md
```

---

## 📝 Agent Naming Convention

### Pattern
```
cmis-<platform>-<feature-category>-<specific-feature>.md
```

### Examples
```
✅ cmis-meta-audiences-custom.md
✅ cmis-meta-audiences-lookalike.md
✅ cmis-google-bidding-tcpa.md
✅ cmis-tiktok-creative-video.md
✅ cmis-campaign-budget-allocation.md
```

### Rules
- All lowercase
- Hyphen-separated
- Platform prefix for platform-specific agents
- Clear feature hierarchy
- No abbreviations unless standard (TCPA, ROAS)

---

## 🎯 Detailed Agent Breakdown by Domain

### 1. Meta (Facebook/Instagram) Advertising - 20+ Agents

#### 1.1 Audience Management (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-meta-audiences-custom.md` | Custom Audience creation from customer lists, website traffic, app activity | CSV uploads, matching, privacy compliance |
| `cmis-meta-audiences-lookalike.md` | Lookalike Audience generation based on source audiences | Similarity scoring, expansion, size selection |
| `cmis-meta-audiences-saved.md` | Saved Audience targeting with demographics, interests, behaviors | Detailed targeting, AND/OR logic, exclusions |
| `cmis-meta-audiences-advantage-plus.md` | Advantage+ Audience (formerly Detailed Targeting Expansion) | Automatic expansion, performance optimization |
| `cmis-meta-audiences-insights.md` | Audience Insights analysis and recommendations | Demographics, interests, purchase behavior |

#### 1.2 Campaign Structure (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-meta-campaigns-objectives.md` | Campaign objective selection (AWARENESS, TRAFFIC, ENGAGEMENT, LEADS, APP_PROMOTION, SALES) | Objective mapping, optimization goals |
| `cmis-meta-campaigns-budget-optimization.md` | Campaign Budget Optimization (CBO) vs Ad Set budgets | Budget distribution, learning phase |
| `cmis-meta-campaigns-bidding.md` | Bidding strategies (LOWEST_COST, COST_CAP, BID_CAP, LOWEST_COST_WITH_MIN_ROAS) | Bid optimization, ROAS targets |

#### 1.3 Ad Set Features (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-meta-adsets-placements-manual.md` | Manual placement selection (Feed, Stories, Reels, In-Stream, etc.) | Platform-specific placements, optimization |
| `cmis-meta-adsets-placements-advantage-plus.md` | Advantage+ Placements (automatic placement optimization) | Cross-platform delivery, performance maximization |
| `cmis-meta-adsets-optimization.md` | Optimization event selection (LINK_CLICKS, LANDING_PAGE_VIEWS, CONVERSIONS, etc.) | Event mapping, conversion optimization |
| `cmis-meta-adsets-delivery.md` | Delivery optimization and scheduling | Dayparting, pacing, accelerated delivery |

#### 1.4 Creative Features (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-meta-creatives-single-image.md` | Single image ad creation and optimization | Image specs, text limits, call-to-action |
| `cmis-meta-creatives-video.md` | Video ad creation (Feed, Stories, Reels formats) | Video specs, aspect ratios, sound guidelines |
| `cmis-meta-creatives-carousel.md` | Carousel ad creation with multiple images/videos | Card ordering, dynamic creative |
| `cmis-meta-creatives-dynamic.md` | Dynamic Creative Testing (DCT) | Asset combinations, performance testing |
| `cmis-meta-creatives-advantage-plus.md` | Advantage+ Creative (formerly Dynamic Creative) | Automatic creative optimization, asset grouping |

#### 1.5 Tracking & Attribution (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-meta-pixel-setup.md` | Meta Pixel installation and event tracking | PageView, AddToCart, Purchase events |
| `cmis-meta-conversion-api.md` | Conversion API (CAPI) server-side tracking | Event deduplication, data enrichment |
| `cmis-meta-attribution.md` | Attribution settings and reporting | Attribution windows, click vs view attribution |

---

### 2. Google Ads - 25+ Agents

#### 2.1 Campaign Types (6 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-google-campaigns-search.md` | Search campaign creation and keyword management | Keyword research, match types, negative keywords |
| `cmis-google-campaigns-display.md` | Display campaign setup and targeting | GDN placements, contextual targeting |
| `cmis-google-campaigns-video.md` | YouTube video campaigns | TrueView, bumper ads, video discovery |
| `cmis-google-campaigns-shopping.md` | Google Shopping campaigns | Product feeds, Merchant Center integration |
| `cmis-google-campaigns-pmax.md` | Performance Max campaigns | Asset groups, audience signals |
| `cmis-google-campaigns-discovery.md` | Discovery campaigns | Multi-surface discovery ads |

#### 2.2 Bidding & Budget (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-google-bidding-tcpa.md` | Target CPA (Cost Per Acquisition) Smart Bidding | CPA targets, conversion optimization |
| `cmis-google-bidding-troas.md` | Target ROAS (Return on Ad Spend) Smart Bidding | ROAS targets, value optimization |
| `cmis-google-bidding-maximize.md` | Maximize Conversions/Conversion Value bidding | Budget-based optimization |
| `cmis-google-budget-pacing.md` | Budget pacing and scheduling | Shared budgets, dayparting |

#### 2.3 Targeting (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-google-targeting-keywords.md` | Keyword targeting and match types | Broad, phrase, exact match |
| `cmis-google-targeting-audiences.md` | Audience targeting (in-market, affinity, custom) | Audience layering, observation |
| `cmis-google-targeting-demographics.md` | Demographic targeting | Age, gender, household income |
| `cmis-google-targeting-rlsa.md` | Remarketing Lists for Search Ads (RLSA) | Search + audience combination |
| `cmis-google-targeting-locations.md` | Location targeting and bid adjustments | Geo-targeting, radius targeting |

#### 2.4 Quality & Optimization (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-google-quality-score.md` | Quality Score optimization | Ad relevance, expected CTR, landing page experience |
| `cmis-google-extensions.md` | Ad extensions (sitelinks, callouts, structured snippets) | Extension types, best practices |
| `cmis-google-rsa.md` | Responsive Search Ads creation | Headlines, descriptions, asset strength |
| `cmis-google-optimization-score.md` | Optimization Score recommendations | Actionable recommendations |
| `cmis-google-experiments.md` | Campaign experiments and drafts | A/B testing, experiment design |

#### 2.5 Shopping & Feeds (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-google-shopping-feeds.md` | Product feed creation and optimization | Feed specifications, feed rules |
| `cmis-google-shopping-pla.md` | Product Listing Ads optimization | Product groups, bidding |
| `cmis-google-shopping-merchant.md` | Google Merchant Center integration | Feed submission, diagnostics |

#### 2.6 Tracking & Analytics (2 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-google-conversion-tracking.md` | Conversion tracking setup | Google Tag, conversion actions |
| `cmis-google-analytics-integration.md` | Google Analytics 4 integration | Enhanced conversions, audience import |

---

### 3. TikTok Ads - 15+ Agents

#### 3.1 Campaign Features (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-tiktok-campaigns-objectives.md` | Campaign objective selection | REACH, TRAFFIC, CONVERSIONS, APP_INSTALLS |
| `cmis-tiktok-campaigns-spark.md` | Spark Ads (boosting organic content) | Creator collaboration, authentic content |
| `cmis-tiktok-campaigns-shopping.md` | TikTok Shopping Ads | Product catalog, shopping tags |
| `cmis-tiktok-campaigns-lead-gen.md` | Instant Form lead generation | Custom form fields, lead capture |

#### 3.2 Creative Formats (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-tiktok-creatives-video.md` | Video ad specifications and best practices | 9:16 vertical, 3-60s duration |
| `cmis-tiktok-creatives-in-feed.md` | In-Feed Ads creation | Native feed integration |
| `cmis-tiktok-creatives-top-view.md` | TopView Ads (premium placement) | Full-screen takeover |
| `cmis-tiktok-creatives-branded-effects.md` | Branded Effects (AR filters, stickers) | Custom effects, engagement |
| `cmis-tiktok-creatives-dynamic-showcase.md` | Dynamic Showcase Ads | Product catalog integration |

#### 3.3 Targeting (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-tiktok-targeting-interest.md` | Interest-based targeting | Content categories, user interests |
| `cmis-tiktok-targeting-behavioral.md` | Behavioral targeting | Video interactions, hashtag interactions |
| `cmis-tiktok-targeting-custom-audiences.md` | Custom Audiences (website, app, engagement) | Retargeting, engagement audiences |
| `cmis-tiktok-targeting-lookalike.md` | Lookalike Audience creation | Source audience expansion |

#### 3.4 Tracking & Attribution (2 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-tiktok-pixel.md` | TikTok Pixel installation and events | Web events, event parameters |
| `cmis-tiktok-attribution.md` | Attribution settings and reporting | Click vs view attribution |

---

### 4. LinkedIn Ads - 12+ Agents

#### 4.1 Campaign Features (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-linkedin-campaigns-sponsored-content.md` | Sponsored Content campaigns | Single image, video, carousel, document ads |
| `cmis-linkedin-campaigns-sponsored-messaging.md` | Sponsored Messaging (InMail) campaigns | Message ads, conversation ads |
| `cmis-linkedin-campaigns-text-ads.md` | Text Ads campaigns | Sidebar text ads |
| `cmis-linkedin-campaigns-dynamic.md` | Dynamic Ads campaigns | Follower ads, spotlight ads |

#### 4.2 B2B Targeting (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-linkedin-targeting-job-titles.md` | Job title targeting | Specific titles, functions |
| `cmis-linkedin-targeting-company.md` | Company targeting | Company name, size, industry |
| `cmis-linkedin-targeting-industry.md` | Industry targeting | Industry categories |
| `cmis-linkedin-targeting-seniority.md` | Seniority targeting | Entry-level to C-level |
| `cmis-linkedin-targeting-skills.md` | Skills and education targeting | Member skills, degrees |

#### 4.3 Lead Generation (2 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-linkedin-lead-gen-forms.md` | Lead Gen Forms setup | Custom questions, pre-fill, webhook integration |
| `cmis-linkedin-matched-audiences.md` | Matched Audiences (retargeting) | Website retargeting, contact targeting |

#### 4.4 Tracking & Attribution (1 agent)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-linkedin-insight-tag.md` | Insight Tag installation and conversion tracking | Website conversions, retargeting |

---

### 5. Twitter Ads - 10+ Agents

#### 5.1 Campaign Features (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-twitter-campaigns-promoted-tweets.md` | Promoted Tweets campaigns | Tweet engagement, website clicks |
| `cmis-twitter-campaigns-promoted-accounts.md` | Promoted Accounts campaigns | Follower growth |
| `cmis-twitter-campaigns-promoted-trends.md` | Promoted Trends campaigns | Trending topics, brand awareness |

#### 5.2 Targeting (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-twitter-targeting-keywords.md` | Keyword targeting | Timeline keywords, search keywords |
| `cmis-twitter-targeting-followers.md` | Follower targeting | Similar audiences |
| `cmis-twitter-targeting-conversation.md` | Conversation targeting | Topic-based targeting |
| `cmis-twitter-targeting-tailored.md` | Tailored Audiences | Website visitors, lists, CRM |

#### 5.3 Creative (2 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-twitter-creatives-video.md` | Video ads creation | Video specs, in-stream pre-roll |
| `cmis-twitter-creatives-cards.md` | Twitter Cards (Summary, Player, App) | Rich media cards |

#### 5.4 Tracking (1 agent)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-twitter-pixel.md` | Twitter Pixel and conversion tracking | Website tag, conversion events |

---

### 6. Snapchat Ads - 12+ Agents

#### 6.1 Campaign Features (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-snapchat-campaigns-snap-ads.md` | Snap Ads campaigns | Single video ads with swipe-up |
| `cmis-snapchat-campaigns-story-ads.md` | Story Ads campaigns | Branded tile in Discover |
| `cmis-snapchat-campaigns-collection-ads.md` | Collection Ads campaigns | Product catalogs |
| `cmis-snapchat-campaigns-ar-lenses.md` | AR Lenses campaigns | Sponsored Lenses, face filters |
| `cmis-snapchat-campaigns-filters.md` | Filters campaigns | Geofilters, sponsored filters |

#### 6.2 Targeting (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-snapchat-targeting-lifestyle.md` | Snap Lifestyle Categories targeting | 150+ predefined categories |
| `cmis-snapchat-targeting-custom-audiences.md` | Custom Audiences | Pixel-based, customer lists |
| `cmis-snapchat-targeting-lookalike.md` | Lookalike Audiences | Source audience expansion |
| `cmis-snapchat-targeting-predefined.md` | Predefined Audiences | Snapchat Audience Network |

#### 6.3 Creative (2 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-snapchat-creatives-video.md` | Video ad specifications | 9:16 vertical, 3-180s duration |
| `cmis-snapchat-creatives-instant-forms.md` | Instant Forms for lead generation | Form builder, lead delivery |

#### 6.4 Tracking (1 agent)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-snapchat-pixel.md` | Snap Pixel installation and events | Purchase, SignUp, ViewContent events |

---

### 7. Campaign Domain - 15+ Agents

#### 7.1 Campaign Lifecycle (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-campaigns-planning.md` | Campaign planning and strategy | Goal setting, audience definition |
| `cmis-campaigns-execution.md` | Campaign execution and launch | Cross-platform deployment |
| `cmis-campaigns-monitoring.md` | Real-time campaign monitoring | Performance tracking, alerts |
| `cmis-campaigns-optimization.md` | Campaign optimization strategies | Budget reallocation, creative refresh |
| `cmis-campaigns-reporting.md` | Campaign reporting and analysis | Performance summaries, insights |

#### 7.2 Budget Management (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-budgets-allocation.md` | Budget allocation across platforms | Distribution strategies, constraints |
| `cmis-budgets-pacing.md` | Budget pacing and spend monitoring | Daily/lifetime budgets, pacing curves |
| `cmis-budgets-forecasting.md` | Budget forecasting and projection | Spend predictions, ROI forecasting |
| `cmis-budgets-optimization.md` | Budget optimization algorithms | Performance-based reallocation |

#### 7.3 Campaign Context System (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-context-field-definitions.md` | Field Definition creation and management | EAV schema, field types |
| `cmis-context-field-values.md` | Field Value storage and retrieval | Value storage, validation |
| `cmis-context-linking.md` | Campaign-Context linking | Context associations |

#### 7.4 Campaign Templates (2 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-templates-campaign.md` | Campaign templates | Reusable campaign structures |
| `cmis-templates-workflow.md` | Workflow templates | Automation templates |

#### 7.5 Campaign Orchestration (1 agent)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-orchestration-multi-platform.md` | Multi-platform campaign orchestration | Synchronized deployment |

---

### 8. Audience Domain - 10+ Agents

#### 8.1 Audience Management (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-audiences-segmentation.md` | Audience segmentation strategies | Demographic, behavioral, psychographic |
| `cmis-audiences-builder.md` | Audience builder interface | Visual audience creation |
| `cmis-audiences-sync.md` | Cross-platform audience sync | Unified audience management |
| `cmis-audiences-insights.md` | Audience insights and analytics | Audience performance, overlap |

#### 8.2 Audience Types (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-audiences-custom.md` | Custom audience creation | Upload lists, pixel-based |
| `cmis-audiences-lookalike.md` | Lookalike audience generation | Similarity algorithms |
| `cmis-audiences-saved.md` | Saved audience management | Predefined criteria |

#### 8.3 Audience Enrichment (2 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-audiences-enrichment-data.md` | Third-party data enrichment | Data provider integration |
| `cmis-audiences-enrichment-ai.md` | AI-powered audience enrichment | Predictive audience traits |

#### 8.4 Audience Performance (1 agent)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-audiences-performance.md` | Audience performance tracking | CTR, conversion rates by audience |

---

### 9. Creative Domain - 12+ Agents

#### 9.1 Asset Management (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-assets-library.md` | Asset library organization | Folder structure, tagging |
| `cmis-assets-versioning.md` | Asset version control | Version history, rollback |
| `cmis-assets-approval.md` | Asset approval workflows | Multi-step approval |
| `cmis-assets-metadata.md` | Asset metadata management | Custom metadata fields |

#### 9.2 Content Creation (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-content-plans.md` | Content plan creation | Editorial calendars |
| `cmis-content-items.md` | Content item management | Individual content pieces |
| `cmis-content-briefs.md` | Creative brief templates | Brief creation, requirements |
| `cmis-content-variations.md` | Content variation generation | A/B testing variations |

#### 9.3 Templates (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-templates-video.md` | Video template creation | Reusable video structures |
| `cmis-templates-audio.md` | Audio template creation | Voice scripts, music beds |
| `cmis-templates-copy.md` | Copy templates | Messaging frameworks |

#### 9.4 Creative Optimization (1 agent)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-creative-optimization.md` | Creative performance optimization | Creative scoring, recommendations |

---

### 10. Analytics Domain - 18+ Agents

#### 10.1 Metrics & Reporting (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-metrics-definitions.md` | Metric definition management | Custom metrics, calculations |
| `cmis-metrics-campaign.md` | Campaign-level metrics | Aggregated performance |
| `cmis-reports-templates.md` | Report template creation | Reusable report structures |
| `cmis-reports-scheduled.md` | Scheduled report automation | Email delivery, frequency |

#### 10.2 Forecasting (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-forecasting-arima.md` | ARIMA forecasting models | Time series analysis |
| `cmis-forecasting-prophet.md` | Facebook Prophet forecasting | Seasonality detection |
| `cmis-forecasting-statistical.md` | Statistical forecasting methods | Linear regression, moving average |
| `cmis-forecasting-lstm.md` | LSTM neural network forecasting | Deep learning predictions |

#### 10.3 A/B Testing (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-experiments-design.md` | Experiment design and setup | Hypothesis, variants, sample size |
| `cmis-experiments-variants.md` | Variant management | Traffic allocation, variant tracking |
| `cmis-experiments-significance.md` | Statistical significance testing | Chi-square, t-tests, p-values |
| `cmis-experiments-winners.md` | Winner selection and rollout | Automated winner selection |

#### 10.4 Alerts & Monitoring (4 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-alerts-rules.md` | Alert rule creation | Threshold-based, anomaly-based |
| `cmis-alerts-evaluation.md` | Alert evaluation engine | Real-time evaluation |
| `cmis-alerts-notifications.md` | Multi-channel notifications | Email, Slack, webhook |
| `cmis-alerts-escalation.md` | Alert escalation policies | Multi-level escalation |

#### 10.5 Attribution (5 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-attribution-last-click.md` | Last-click attribution model | 100% credit to last touchpoint |
| `cmis-attribution-first-click.md` | First-click attribution model | 100% credit to first touchpoint |
| `cmis-attribution-linear.md` | Linear attribution model | Equal credit distribution |
| `cmis-attribution-time-decay.md` | Time-decay attribution model | Recency-weighted credit |
| `cmis-attribution-data-driven.md` | Data-driven attribution model | ML-based credit assignment |

---

### 11. Social Domain - 8+ Agents

#### 11.1 Publishing (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-social-scheduling.md` | Social post scheduling | Calendar integration, optimal timing |
| `cmis-social-publishing.md` | Multi-platform publishing | Unified publishing interface |
| `cmis-social-approval.md` | Social post approval workflow | Multi-step approval |

#### 11.2 Content Management (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-social-library.md` | Social content library | Post templates, hashtags |
| `cmis-social-calendar.md` | Social media calendar | Visual planning |
| `cmis-social-history.md` | Post history and analytics | Performance tracking |

#### 11.3 Engagement (2 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-social-engagement.md` | Engagement tracking and response | Comments, messages, mentions |
| `cmis-social-listening.md` | Social listening and monitoring | Brand mentions, sentiment |

---

### 12. Integration Domain - 10+ Agents

#### 12.1 OAuth & Authentication (6 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-oauth-meta.md` | Meta OAuth flow | App permissions, token refresh |
| `cmis-oauth-google.md` | Google OAuth flow | Scopes, consent screen |
| `cmis-oauth-tiktok.md` | TikTok OAuth flow | Authorization code flow |
| `cmis-oauth-linkedin.md` | LinkedIn OAuth flow | OAuth 2.0 implementation |
| `cmis-oauth-twitter.md` | Twitter OAuth flow | OAuth 1.0a, 2.0 |
| `cmis-oauth-snapchat.md` | Snapchat OAuth flow | App approval, permissions |

#### 12.2 Webhooks (3 agents)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-webhooks-meta.md` | Meta webhook integration | Signature verification, event handling |
| `cmis-webhooks-google.md` | Google webhook integration | Pub/Sub notifications |
| `cmis-webhooks-verification.md` | Webhook signature verification | Security validation |

#### 12.3 Data Sync (1 agent)
| Agent File | Description | Key Focus |
|-----------|-------------|-----------|
| `cmis-sync-platform.md` | Platform data synchronization | Incremental sync, conflict resolution |

---

## 🚀 Implementation Strategy

### Phase 1: Platform-Specific Agents (Weeks 1-4)
**Priority:** Meta & Google (80% of usage)

**Week 1: Meta Audience & Campaign Agents**
- `cmis-meta-audiences-custom.md`
- `cmis-meta-audiences-lookalike.md`
- `cmis-meta-campaigns-objectives.md`
- `cmis-meta-campaigns-budget-optimization.md`

**Week 2: Meta Creative & Placement Agents**
- `cmis-meta-adsets-placements-manual.md`
- `cmis-meta-adsets-placements-advantage-plus.md`
- `cmis-meta-creatives-video.md`
- `cmis-meta-creatives-advantage-plus.md`

**Week 3: Google Search & Shopping Agents**
- `cmis-google-campaigns-search.md`
- `cmis-google-campaigns-shopping.md`
- `cmis-google-bidding-tcpa.md`
- `cmis-google-targeting-keywords.md`

**Week 4: Google Display & Performance Max Agents**
- `cmis-google-campaigns-display.md`
- `cmis-google-campaigns-pmax.md`
- `cmis-google-quality-score.md`
- `cmis-google-rsa.md`

### Phase 2: Secondary Platforms (Weeks 5-6)
**Priority:** TikTok, LinkedIn, Twitter, Snapchat

**Week 5: TikTok & LinkedIn Agents**
- 8 TikTok agents (campaigns, creatives, targeting)
- 6 LinkedIn agents (campaigns, B2B targeting)

**Week 6: Twitter & Snapchat Agents**
- 5 Twitter agents (campaigns, targeting, creative)
- 6 Snapchat agents (campaigns, AR, targeting)

### Phase 3: Core Domain Agents (Weeks 7-9)

**Week 7: Campaign & Audience Agents**
- 15 Campaign domain agents
- 10 Audience domain agents

**Week 8: Creative & Analytics Agents**
- 12 Creative domain agents
- 10 Analytics agents (metrics, reports, forecasting)

**Week 9: Advanced Analytics Agents**
- 8 remaining Analytics agents (A/B testing, alerts, attribution)
- 8 Social domain agents

### Phase 4: Integration & OAuth Agents (Week 10)
- 10 Integration agents (OAuth, webhooks, sync)

### Phase 5: Testing & Documentation (Week 11-12)
- Update `agents/README.md` with all 180+ agents
- Test agent routing and coordination
- Create agent usage examples
- Performance optimization

---

## 📋 Agent Template Structure

Each agent will follow this structure:

```markdown
---
name: cmis-[platform]-[feature]
description: |
  Expert in [specific feature] for [platform].
  Handles [key capabilities].
model: haiku  # or sonnet for complex agents
---

# CMIS [Platform] [Feature] Specialist V1.0
## [Tagline describing expertise]

**Last Updated:** 2025-11-23
**Platform:** [Platform Name]
**Feature Category:** [Category]
**API Documentation:** [Link to official docs]

---

## 🚨 CRITICAL: LIVE API DISCOVERY

**BEFORE answering ANY question:**

### 1. Check Latest API Version
```bash
# WebSearch for latest API version
WebSearch("[ Platform] Ads API latest version 2025")
```

### 2. Fetch Official Documentation
```bash
# WebFetch official API docs
WebFetch("https://developers.[platform].com/docs/[api]",
         "What is the current API version and latest changes?")
```

### 3. Discover Current Implementation
```bash
# Check CMIS codebase for existing implementation
Glob("**/app/Services/AdPlatforms/[Platform]Connector.php")
Read("[discovered file path]")
```

---

## 🎯 CORE MISSION

Expert in **[Specific Feature]** for [Platform]:

✅ **Discover:** Current API version and feature capabilities
✅ **Guide:** Implementation using latest [Platform] API
✅ **Optimize:** [Feature] performance and best practices
✅ **Troubleshoot:** Common [feature] issues
✅ **Test:** [Feature] implementation patterns

**Your Superpower:** Deep expertise in [specific feature] with LIVE API discovery

---

## 🔍 DISCOVERY PROTOCOLS

### Protocol 1: Discover API Version & Capabilities

```bash
# Step 1: Search for latest API docs
WebSearch("[Platform] [Feature] API latest version 2025")

# Step 2: Fetch official documentation
WebFetch("https://developers.[platform].com/docs/[feature]",
         "What are the current capabilities and parameters?")

# Step 3: Check CMIS implementation
Grep("[feature keyword]", pattern: "[Platform]", path: "app/Services")
```

### Protocol 2: Discover Current Implementation

```bash
# Find existing implementation
Glob("**/app/Services/AdPlatforms/[Platform]*.php")
Glob("**/app/Models/AdPlatform/*.php")

# Read implementation files
Read("[connector file]")
```

### Protocol 3: Discover Related Models & Services

```bash
# Find related models
Grep("class.*[Feature]", path: "app/Models")

# Find related services
Grep("[feature]", path: "app/Services", output_mode: "files_with_matches")
```

---

## 📋 AGENT ROUTING REFERENCE

**Keywords:** [comma-separated keywords for this feature]
**Agent:** cmis-[platform]-[feature]
**When:** [When to use this agent vs. others]

**Example Requests:**
- "How do I create [feature] in [Platform]?"
- "What are the parameters for [feature]?"
- "How do I optimize [feature] performance?"

**Coordinates with:**
- `cmis-[platform]-[related-feature]` - [Reason]
- `cmis-platform-integration` - OAuth/authentication
- `cmis-multi-tenancy` - Multi-tenant isolation

---

## 🎯 KEY PATTERNS

### Pattern 1: [Primary Use Case]

**Implementation:**
```php
// Discover latest implementation
// NEVER hard-code API versions

// Example: Creating [feature]
$response = $platformConnector->[methodName]([
    'parameter1' => 'value1',
    'parameter2' => 'value2',
    // Discovered parameters from API docs
]);
```

**RLS Compliance:**
```php
// ALWAYS respect multi-tenancy
DB::statement("SELECT init_transaction_context(?)", [$orgId]);
```

**Testing Pattern:**
```php
// Mock platform API responses
// Test multi-tenant isolation
```

### Pattern 2: [Secondary Use Case]

[Similar structure]

---

## 💡 DECISION TREE

```
User asks about [feature]
    ↓
1. WebSearch for latest API docs
    ↓
2. WebFetch official documentation
    ↓
3. Discover CMIS implementation
    ↓
4. Provide guidance based on LATEST API
    ↓
5. Include RLS compliance
    ↓
6. Suggest testing approach
```

---

## 🎯 QUALITY ASSURANCE

After providing solution, verify:

- [ ] Used LATEST API version (discovered via WebSearch/WebFetch)
- [ ] Multi-tenancy respected (RLS compliance)
- [ ] Platform-specific best practices followed
- [ ] Error handling included
- [ ] Testing strategy provided
- [ ] Links to official documentation included

---

## 🚨 CRITICAL RULES

**ALWAYS:**
- ✅ Discover latest API version via WebSearch before answering
- ✅ WebFetch official docs for accurate parameters
- ✅ Check current CMIS implementation
- ✅ Respect multi-tenancy (RLS)
- ✅ Provide platform-specific examples
- ✅ Include testing patterns
- ✅ Link to official documentation

**NEVER:**
- ❌ Assume outdated API versions
- ❌ Give generic advice (always platform-specific)
- ❌ Bypass RLS with manual org filtering
- ❌ Skip discovery protocols
- ❌ Ignore error handling

---

## 📝 EXAMPLES

### Example 1: [Common Use Case]

**User:** "[Example question]"

**Agent Process:**
1. WebSearch("[Platform] [feature] API 2025")
2. WebFetch official docs
3. Discover CMIS implementation
4. Provide step-by-step guidance

**Output:**
```php
// Implementation code with latest API
// RLS compliance
// Error handling
// Testing approach
```

### Example 2: [Another Use Case]

[Similar structure]

---

## 📚 OFFICIAL DOCUMENTATION LINKS

**Primary:**
- [Platform] [Feature] API: [URL]
- [Platform] Developer Docs: [URL]

**Secondary:**
- [Platform] Best Practices: [URL]
- [Platform] Changelog: [URL]

---

**Version:** 1.0
**Last Updated:** 2025-11-23
**Status:** ACTIVE
**Model:** haiku (or sonnet for complex features)
**Tools:** WebSearch, WebFetch, Glob, Grep, Read, Bash
```

---

## 📊 Success Metrics

### Agent Quality Metrics
- **Accuracy:** % of correct answers based on latest API
- **Precision:** % of answers specific to the exact feature (not generic)
- **Discovery Rate:** % of answers that use WebSearch/WebFetch
- **RLS Compliance:** % of answers that respect multi-tenancy
- **Test Coverage:** % of answers that include testing patterns

### Usage Metrics
- **Agent Invocations:** How often each agent is used
- **User Satisfaction:** Feedback on answer quality
- **Resolution Time:** Time to answer user questions
- **Coordination Success:** % of multi-agent workflows that succeed

---

## 🔄 Maintenance Plan

### Weekly
- Update agents with platform API changes
- Review agent usage metrics
- Collect user feedback

### Monthly
- Audit agent quality metrics
- Update agent routing in orchestrator
- Add new agents for new features

### Quarterly
- Major agent architecture review
- Platform API version updates
- Agent consolidation/splitting as needed

---

## 📞 Next Steps

1. **Review this plan** - Confirm the architecture and agent breakdown
2. **Start Phase 1** - Begin with Meta and Google platform agents
3. **Create agent files** - Write each agent manually (no scripts)
4. **Test routing** - Ensure orchestrator routes correctly
5. **Iterate** - Refine based on actual usage

---

**Total New Agents to Create:** 133+ agents
**Estimated Timeline:** 12 weeks (3 months)
**Implementation Approach:** Manual creation (no scripts)
**Quality Standard:** LIVE API discovery, RLS compliance, platform-specific examples

---

**Ready to proceed?** Let's start writing the first batch of specialized agents! 🚀
