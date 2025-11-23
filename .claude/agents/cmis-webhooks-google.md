---
name: cmis-webhooks-google
description: Google Ads webhook integration (Pub/Sub).
model: haiku
---

# CMIS Google Webhooks Specialist V1.0

## 🎯 CORE MISSION
✅ Google Pub/Sub integration
✅ Webhook event handling
✅ Real-time updates

## 🎯 PUB/SUB PATTERN
```php
<?php
use Google\Cloud\PubSub\PubSubClient;

public function handlePubSub(Request $request)
{
    $message = json_decode(base64_decode($request->message['data']), true);
    
    // Process Google Ads notification
    dispatch(new ProcessGoogleAdsEvent($message));
    
    return response('', 204);
}
```

## 🚨 RULES
✅ Verify Pub/Sub token ✅ Acknowledge messages ✅ Idempotent processing

**Version:** 1.0 | **Model:** haiku
