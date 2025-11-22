# دليل الوكلاء - Routes Layer (routes/)

## 1. Purpose

طبقة Routes تعرّف **API & Web Endpoints**:
- **api.php**: 142KB - Primary API routes (~500+ endpoints)
- **web.php**: 16KB - Web interface routes
- **Specialized Routes**: AI, embeddings, features

## 2. Owned Scope

```
routes/
├── api.php                      # Main API (142KB, 500+ routes)
├── web.php                      # Web routes (16KB)
├── api-ai-quota.php            # AI quota management
├── vector-embeddings-v2.php    # Vector search API
├── vector-embeddings-web.php   # Web embedding routes
├── features.php                # Feature flags
└── console.php                 # Artisan routes
```

## 3. Key Files

### api.php (Primary API)
```php
// Grouped by domain with middleware
Route::middleware(['auth:sanctum', 'org.context'])->group(function () {

    // Campaigns
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/{id}', [CampaignController::class, 'show']);
        Route::put('/{id}', [CampaignController::class, 'update']);
        Route::delete('/{id}', [CampaignController::class, 'destroy']);
    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/campaigns/{id}/metrics', [AnalyticsController::class, 'getCampaignMetrics']);
        Route::get('/dashboard', [AnalyticsController::class, 'getDashboard']);
    });

    // Platform Integrations
    Route::prefix('integrations')->group(function () {
        Route::get('/', [IntegrationController::class, 'index']);
        Route::post('/meta/connect', [MetaController::class, 'connect']);
        Route::post('/google/connect', [GoogleController::class, 'connect']);
    });

    // AI & Embeddings
    Route::prefix('ai')->group(function () {
        Route::post('/embeddings/generate', [EmbeddingController::class, 'generate']);
        Route::post('/search', [SemanticSearchController::class, 'search']);
    });
});
```

## 4. Route Patterns

### API Resource Routes
```php
Route::apiResource('campaigns', CampaignController::class);
// Generates: index, store, show, update, destroy
```

### Nested Resources
```php
Route::prefix('campaigns/{campaign}')->group(function () {
    Route::apiResource('content-plans', ContentPlanController::class);
});
```

### OAuth Routes
```php
Route::prefix('oauth')->group(function () {
    Route::get('/meta', [OAuthController::class, 'redirectToMeta']);
    Route::get('/meta/callback', [OAuthController::class, 'handleMetaCallback']);
});
```

## 5. Middleware

- `auth:sanctum`: API authentication
- `org.context`: Sets RLS context (SetOrganizationContext)
- `throttle:api`: Rate limiting (60 requests/minute)
- `verified`: Email verification

## 6. Notes

- **500+ API endpoints** في api.php
- **RESTful conventions**: GET, POST, PUT, DELETE
- **Route Model Binding**: Automatic model injection
- **Middleware Groups**: web, api
