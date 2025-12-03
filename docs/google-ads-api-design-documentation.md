# CMIS - Google Ads API Integration Design Documentation

**Document Version:** 1.0
**Date:** December 2025
**Company:** Marketing Dot Limited Digital Solutions
**Application:** CMIS (Cognitive Marketing Intelligence Suite)
**Contact:** Mohamed Jasim Ahmed

---

## 1. Executive Summary

CMIS is a comprehensive multi-platform marketing management system that integrates with Google Ads API to provide full campaign management capabilities, enabling marketing agencies and businesses to create, manage, and optimize their Google Ads campaigns alongside other advertising platforms.

**API Access Level Requested:** Standard Access
**Primary Use Cases:** Campaign management, performance reporting, cross-platform optimization

---

## 2. Application Overview

### 2.1 What is CMIS?
CMIS (Cognitive Marketing Intelligence Suite) is a B2B SaaS platform that consolidates advertising management across multiple platforms (Google Ads, Meta, TikTok, LinkedIn, Snapchat, Twitter) into a unified dashboard. The platform enables marketers to:

- **Create campaigns** across multiple platforms from one interface
- **Manage and optimize** existing campaigns with AI-powered recommendations
- **Monitor performance** with unified cross-platform reporting
- **Automate workflows** for campaign scaling and budget management
- **Collaborate** with team members on campaign strategies

### 2.2 Target Users
- Marketing agencies managing multiple client accounts
- In-house marketing teams at mid-to-large enterprises
- Digital advertising professionals
- E-commerce businesses running multi-platform campaigns

### 2.3 Business Model
CMIS operates on a SaaS subscription model where marketing agencies and businesses pay monthly/annual fees based on:
- Number of connected ad accounts
- Monthly ad spend managed through the platform
- Number of team members

---

## 3. Google Ads API Integration Scope

### 3.1 Features Using Google Ads API

| Feature | API Operations | Access Type | Purpose |
|---------|---------------|-------------|---------|
| Account Discovery | `customers:listAccessibleCustomers` | Read | List user's accessible Google Ads accounts |
| Account Details | `CustomerService` | Read | Get account name, currency, status |
| **Campaign Creation** | `CampaignService.mutate` | **Write** | Create new Search, Display, Video, Shopping campaigns |
| **Campaign Management** | `CampaignService.mutate` | **Write** | Update campaign settings, status, budgets |
| **Campaign Deletion** | `CampaignService.mutate` | **Write** | Remove campaigns |
| **Ad Group Management** | `AdGroupService.mutate` | **Write** | Create, update, delete ad groups |
| **Ad Creation** | `AdGroupAdService.mutate` | **Write** | Create responsive search ads, display ads |
| **Keyword Management** | `AdGroupCriterionService.mutate` | **Write** | Add, update, remove keywords |
| **Budget Management** | `CampaignBudgetService.mutate` | **Write** | Create and modify campaign budgets |
| **Bidding Strategies** | `BiddingStrategyService` | **Write** | Configure Target CPA, Target ROAS, Maximize Conversions |
| Performance Reporting | `GoogleAdsService.searchStream` | Read | Retrieve metrics for dashboards |
| Conversion Tracking | `ConversionActionService` | Read | List conversion actions |

### 3.2 Campaign Types Supported
- Search Campaigns (Text ads, RSA)
- Display Campaigns (GDN)
- Video Campaigns (YouTube)
- Shopping Campaigns (Product feeds)
- Performance Max Campaigns
- App Campaigns

### 3.3 Data Accessed
- Customer/Account information
- Campaign structures (campaigns, ad groups, ads, keywords)
- Performance metrics (impressions, clicks, cost, conversions, ROAS)
- Bidding and budget configurations
- Audience targeting settings
- Geographic and demographic targeting

---

## 4. User Flow Diagrams

### 4.1 Account Connection Flow
```
┌──────────┐    ┌──────────────┐    ┌─────────────────┐
│  User    │───▶│ CMIS Login   │───▶│ Platform        │
│          │    │              │    │ Connections     │
└──────────┘    └──────────────┘    └────────┬────────┘
                                             │
                                             ▼
                                   ┌─────────────────┐
                                   │ Connect Google  │
                                   │ (OAuth 2.0)     │
                                   └────────┬────────┘
                                             │
                    ┌────────────────────────┼────────────────────────┐
                    │                        │                        │
                    ▼                        ▼                        ▼
          ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
          │ Google OAuth    │    │ User Grants     │    │ CMIS Receives   │
          │ Consent Screen  │───▶│ Permissions     │───▶│ Access Token    │
          └─────────────────┘    └─────────────────┘    └────────┬────────┘
                                                                  │
                                                                  ▼
                                                        ┌─────────────────┐
                                                        │ Select Google   │
                                                        │ Ads Accounts    │
                                                        │ to Manage       │
                                                        └─────────────────┘
```

### 4.2 Campaign Creation Flow
```
┌─────────────────────────────────────────────────────────────────────────┐
│                     CAMPAIGN CREATION FLOW                               │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│ User Opens   │────▶│ Select       │────▶│ Configure    │
│ Campaign     │     │ Campaign     │     │ Campaign     │
│ Creator      │     │ Type         │     │ Settings     │
└──────────────┘     └──────────────┘     └──────┬───────┘
                                                  │
         ┌────────────────────────────────────────┤
         │                                        │
         ▼                                        ▼
┌──────────────┐                        ┌──────────────┐
│ Set Budget   │                        │ Define       │
│ & Bidding    │                        │ Targeting    │
│ Strategy     │                        │ (Geo, Demo)  │
└──────┬───────┘                        └──────┬───────┘
       │                                        │
       └────────────────┬───────────────────────┘
                        │
                        ▼
              ┌──────────────────┐
              │ Create Ad Groups │
              │ & Keywords       │
              └────────┬─────────┘
                       │
                       ▼
              ┌──────────────────┐
              │ Create Ads       │
              │ (RSA, Display)   │
              └────────┬─────────┘
                       │
                       ▼
              ┌──────────────────┐
              │ Review &         │
              │ Launch Campaign  │
              └────────┬─────────┘
                       │
                       ▼
              ┌──────────────────┐
              │ Google Ads API   │
              │ CampaignService  │
              │ .mutate()        │
              └──────────────────┘
```

### 4.3 Campaign Optimization Flow
```
┌─────────────────────────────────────────────────────────────────────────┐
│                    CAMPAIGN OPTIMIZATION FLOW                            │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│ Fetch        │────▶│ AI Analyzes  │────▶│ Generate     │
│ Performance  │     │ Performance  │     │ Optimization │
│ Metrics      │     │ Data         │     │ Suggestions  │
└──────────────┘     └──────────────┘     └──────┬───────┘
                                                  │
                                                  ▼
                                        ┌──────────────────┐
                                        │ User Reviews     │
                                        │ Recommendations  │
                                        └────────┬─────────┘
                                                 │
                        ┌────────────────────────┼────────────────────────┐
                        │                        │                        │
                        ▼                        ▼                        ▼
              ┌──────────────┐        ┌──────────────┐        ┌──────────────┐
              │ Adjust       │        │ Pause Low    │        │ Scale High   │
              │ Bids/Budget  │        │ Performers   │        │ Performers   │
              └──────┬───────┘        └──────┬───────┘        └──────┬───────┘
                     │                        │                       │
                     └────────────────────────┼───────────────────────┘
                                              │
                                              ▼
                                    ┌──────────────────┐
                                    │ Apply Changes    │
                                    │ via Google Ads   │
                                    │ API              │
                                    └──────────────────┘
```

---

## 5. Technical Architecture

### 5.1 System Architecture
```
┌─────────────────────────────────────────────────────────────────────────┐
│                           CMIS PLATFORM                                  │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │ Campaign    │  │ Analytics   │  │ Audience    │  │ Creative    │    │
│  │ Manager     │  │ Dashboard   │  │ Manager     │  │ Studio      │    │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘    │
│         │                │                │                │            │
│         └────────────────┴────────────────┴────────────────┘            │
│                                   │                                      │
│                          ┌────────┴────────┐                            │
│                          │ Platform        │                            │
│                          │ Integration     │                            │
│                          │ Layer           │                            │
│                          └────────┬────────┘                            │
└───────────────────────────────────┼─────────────────────────────────────┘
                                    │
        ┌───────────────────────────┼───────────────────────────┐
        │                           │                           │
        ▼                           ▼                           ▼
┌───────────────┐         ┌───────────────┐         ┌───────────────┐
│ Google Ads    │         │ Meta Ads      │         │ Other         │
│ API           │         │ API           │         │ Platforms     │
└───────────────┘         └───────────────┘         └───────────────┘
```

### 5.2 Authentication Flow
```
┌─────────┐         ┌─────────┐         ┌──────────────┐
│  CMIS   │         │ Google  │         │ Google Ads   │
│  App    │         │ OAuth   │         │ API          │
└────┬────┘         └────┬────┘         └──────┬───────┘
     │                   │                      │
     │ 1. Redirect to    │                      │
     │    OAuth URL      │                      │
     │──────────────────▶│                      │
     │                   │                      │
     │ 2. User consents  │                      │
     │    to adwords     │                      │
     │    scope          │                      │
     │◀──────────────────│                      │
     │                   │                      │
     │ 3. Exchange code  │                      │
     │    for tokens     │                      │
     │──────────────────▶│                      │
     │                   │                      │
     │ 4. Access + Refresh│                     │
     │    tokens         │                      │
     │◀──────────────────│                      │
     │                   │                      │
     │ 5. API Request                           │
     │    - Authorization: Bearer {token}       │
     │    - developer-token: {dev_token}        │
     │─────────────────────────────────────────▶│
     │                   │                      │
     │ 6. Response                              │
     │◀─────────────────────────────────────────│
```

### 5.3 API Request Structure
```
Headers:
  Authorization: Bearer {access_token}
  developer-token: {developer_token}
  login-customer-id: {manager_account_id}  // if using MCC
  Content-Type: application/json
```

### 5.4 Sample API Operations

**Create Campaign:**
```json
POST /v18/customers/{customer_id}/campaigns:mutate

{
  "operations": [{
    "create": {
      "name": "CMIS Campaign - Winter Sale 2025",
      "advertisingChannelType": "SEARCH",
      "status": "PAUSED",
      "campaignBudget": "customers/{customer_id}/campaignBudgets/{budget_id}",
      "biddingStrategyType": "TARGET_CPA",
      "targetCpa": {
        "targetCpaMicros": 5000000
      },
      "networkSettings": {
        "targetGoogleSearch": true,
        "targetSearchNetwork": true
      },
      "startDate": "2025-01-01",
      "endDate": "2025-01-31"
    }
  }]
}
```

**Update Campaign Status:**
```json
POST /v18/customers/{customer_id}/campaigns:mutate

{
  "operations": [{
    "updateMask": "status",
    "update": {
      "resourceName": "customers/{customer_id}/campaigns/{campaign_id}",
      "status": "ENABLED"
    }
  }]
}
```

**Delete Campaign:**
```json
POST /v18/customers/{customer_id}/campaigns:mutate

{
  "operations": [{
    "remove": "customers/{customer_id}/campaigns/{campaign_id}"
  }]
}
```

**Create Ad Group with Keywords:**
```json
POST /v18/customers/{customer_id}/adGroups:mutate

{
  "operations": [{
    "create": {
      "name": "Product Keywords",
      "campaign": "customers/{customer_id}/campaigns/{campaign_id}",
      "status": "ENABLED",
      "type": "SEARCH_STANDARD",
      "cpcBidMicros": 1500000
    }
  }]
}
```

---

## 6. Security & Compliance

### 6.1 Data Security Measures
| Security Layer | Implementation |
|---------------|----------------|
| Encryption at Rest | AES-256 encryption for all tokens and credentials |
| Encryption in Transit | TLS 1.3 for all API communications |
| Access Control | Role-based permissions with organization isolation |
| Database Security | PostgreSQL Row-Level Security (RLS) policies |
| Audit Logging | All campaign changes logged with user attribution |

### 6.2 Token Management
- Access tokens stored encrypted using Laravel's encryption
- Automatic token refresh before expiration
- Refresh tokens stored in secure, isolated database
- Token revocation on user disconnect
- No tokens logged or exposed in error messages

### 6.3 Multi-Tenancy Security
```
┌─────────────────────────────────────────────────────────────┐
│                    MULTI-TENANT ISOLATION                    │
├─────────────────────────────────────────────────────────────┤
│  Organization A          │  Organization B                  │
│  ┌─────────────────┐     │  ┌─────────────────┐            │
│  │ Google Ads      │     │  │ Google Ads      │            │
│  │ Account 1       │     │  │ Account 3       │            │
│  │ Account 2       │     │  │ Account 4       │            │
│  └─────────────────┘     │  └─────────────────┘            │
│          ▲               │          ▲                       │
│          │               │          │                       │
│    RLS Policy            │    RLS Policy                    │
│    org_id = A            │    org_id = B                    │
└─────────────────────────────────────────────────────────────┘
```

### 6.4 User Consent & Control
- Users explicitly authorize Google Ads access via OAuth
- Clear scope explanation during consent
- Users can disconnect at any time
- All associated data deleted on disconnect
- Activity logs available to account owners

### 6.5 Compliance
- GDPR compliant data handling
- Data Processing Agreement available
- Regular security audits
- SOC 2 compliance (in progress)

---

## 7. Rate Limiting & Best Practices

### 7.1 Rate Limit Compliance
| Limit Type | Our Approach |
|------------|--------------|
| Daily Operations | Track usage, implement quotas per org |
| Requests/Second | Queue system with rate limiting |
| Mutate Operations | Batch operations where possible |

### 7.2 Error Handling
```
┌─────────────────────────────────────────────────────────────┐
│                    ERROR HANDLING FLOW                       │
└─────────────────────────────────────────────────────────────┘

API Error Received
       │
       ▼
┌──────────────┐
│ Is Transient?│───Yes──▶ Retry with exponential backoff
│ (429, 503)   │          (max 3 retries)
└──────┬───────┘
       │ No
       ▼
┌──────────────┐
│ Is Auth      │───Yes──▶ Refresh token & retry
│ Error? (401) │
└──────┬───────┘
       │ No
       ▼
┌──────────────┐
│ Log Error    │───▶ Display user-friendly message
│ & Alert      │     Suggest corrective action
└──────────────┘
```

### 7.3 Operational Best Practices
- Batch multiple operations in single requests
- Use partial failures to continue on recoverable errors
- Cache read-only data (account info, campaign structures)
- Implement circuit breakers for API failures
- Monitor API usage and costs

---

## 8. Use Case Examples

### 8.1 Agency Managing Multiple Clients
```
Agency "Digital Marketing Pro" uses CMIS to:
1. Connect 50+ client Google Ads accounts
2. Create campaign templates for common industries
3. Deploy campaigns across multiple accounts
4. Monitor performance in unified dashboard
5. Generate white-label reports for clients
6. Optimize bids based on AI recommendations
```

### 8.2 E-commerce Business
```
"Fashion Store XYZ" uses CMIS to:
1. Create Shopping campaigns synced with product feed
2. Set up remarketing campaigns for cart abandoners
3. A/B test ad copy across platforms
4. Automatically pause underperforming products
5. Scale budget on high-ROAS campaigns
```

### 8.3 Lead Generation Company
```
"Legal Leads Inc" uses CMIS to:
1. Create Search campaigns for legal keywords
2. Set Target CPA bidding for lead generation
3. Track conversions from form submissions
4. Optimize ad scheduling based on conversion times
5. Generate cost-per-lead reports across platforms
```

---

## 9. Screenshots

*Note: The following screenshots demonstrate CMIS platform capabilities. Screenshots are available in the `docs/screenshots/` folder.*

### 9.1 Main Dashboard
**File:** `screenshots/1-dashboard.png`

The CMIS main dashboard provides:
- Organization overview and quick metrics
- Language switching (Arabic/English with RTL/LTR support)
- Navigation to all platform modules

### 9.2 Google Ads Account Selection
**File:** `screenshots/2-google-ads-selection.png`

The Google Assets configuration page showing:
- Connected Google account with OAuth status
- YouTube Channel selection with Brand Accounts support
- Google Ads Account configuration section
- Instructions for Google Ads API setup requirements

### 9.3 Platform Connections
**File:** `screenshots/3-platform-connections.png`

Platform Connections management showing:
- Meta (Facebook/Instagram/Threads) integration with connected Ad Accounts
- Google Services integration (Analytics, Ads, YouTube, Search Console)
- OAuth connection management and token status
- Multi-account support for agencies

### 9.4 Apps Marketplace
**File:** `screenshots/4-marketplace-features.png`

The CMIS Marketplace showing all available modules:
- **Core:** Dashboard, Social Media, Profile Groups, Settings, Historical Content, Platform Connections, Profile Management
- **Marketing:** Campaigns, Audiences, Influencer Marketing, Campaign Orchestration
- Each module can be enabled/disabled per organization

### 9.5 Profile Management (Social Profiles)
**File:** `screenshots/5-youtube-profiles.png`

Profile Management page showing:
- Connected social profiles (Threads, YouTube)
- Profile status tracking (Active/Inactive)
- Connection dates and profile groups
- Multi-platform profile organization

---

## 10. Development & Testing

### 10.1 Development Process
- Test account used for development
- Staging environment with sandbox accounts
- All changes reviewed before production
- Automated tests for API integrations

### 10.2 Quality Assurance
- Unit tests for all API operations
- Integration tests with Google Ads test accounts
- User acceptance testing for new features
- Performance testing for bulk operations

---

## 11. Support & Contact

**Company:** Marketing Dot Limited Digital Solutions
**Website:** https://marketing.limited
**Email:** info@marketing.limited
**Mobile:** +973 37000454

**Technical Contact:** Mohamed Jasim Ahmed
**API Integration Lead:** Mohamed Jasim Ahmed
**Data Protection Officer:** Mohamed Jasim Ahmed

---

## 12. Appendix

### 12.1 OAuth Scopes Required
```
https://www.googleapis.com/auth/adwords
```

### 12.2 Technology Stack
| Component | Technology |
|-----------|------------|
| Backend | Laravel 11 (PHP 8.2) |
| Database | PostgreSQL 16 with RLS |
| Cache | Redis |
| Queue | Laravel Horizon |
| Frontend | Alpine.js, Tailwind CSS |
| Hosting | AWS / Google Cloud |

### 12.3 API Versions
- Google Ads API: v18 (latest)
- OAuth 2.0 for authentication

### 12.4 Compliance Certifications
- [ ] SOC 2 Type II (in progress)
- [x] GDPR Compliant
- [x] SSL/TLS Encryption
- [x] Regular Penetration Testing

### 12.5 CMIS Platform Modules

**Core Modules:**
| Module | Description |
|--------|-------------|
| Dashboard | Central hub for monitoring all activities and metrics |
| Social Media | Compose, schedule, and manage social media posts |
| Profile Groups | Organize social profiles into manageable groups |
| Platform Connections | Connect and manage integrations with ad platforms |
| Profile Management | Manage social media profiles, connections, and settings |
| Historical Content | View and analyze past social media posts and performance |

**Marketing Modules:**
| Module | Description |
|--------|-------------|
| **Campaigns** | Create and manage advertising campaigns across platforms |
| **Audiences** | Build and manage target audiences for campaigns |
| **Keywords Manager** | Manage Google Ads keywords, negative keywords, and keyword research |
| **Ad Accounts** | Connect and manage advertising accounts across platforms |
| Product Catalogs | Multi-platform product feed management and synchronization |
| Lead Management | Track and manage leads from marketing campaigns |
| Influencer Marketing | Discover and manage influencer partnerships |
| Campaign Orchestration | Advanced campaign coordination across channels |

**Analytics Modules:**
| Module | Description |
|--------|-------------|
| Analytics | Track performance metrics and generate insights |
| Predictive Analytics | AI-powered forecasting and trend predictions |
| A/B Testing | Test and optimize campaigns with experiments |
| Optimization Engine | Automated campaign optimization and recommendations |
| Reports Builder | Create custom reports and schedule automated delivery |

**AI & Intelligence Modules:**
| Module | Description |
|--------|-------------|
| AI Assistant | Intelligent assistant for content creation and insights |
| Knowledge Base | AI-powered knowledge management and search |
| Social Listening | Monitor brand mentions and sentiment across social media |

**Automation Modules:**
| Module | Description |
|--------|-------------|
| Automation | Workflow automation and alerts |
| Workflows | Custom workflow creation and management |
| Alerts | Real-time notifications and monitoring |
| Approval Workflows | Content and campaign approval processes |

---

*Document prepared for Google Ads API Standard Access Application*
*CMIS - Cognitive Marketing Intelligence Suite*
*Marketing Dot Limited Digital Solutions*
*December 2025*
