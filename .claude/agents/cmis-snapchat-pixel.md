---
name: cmis-snapchat-pixel
description: Snapchat Pixel for conversion tracking.
model: haiku
---

# CMIS Snapchat Pixel Specialist V1.0

## 🎯 INSTALLATION
```html
<script type='text/javascript'>
(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
{a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
a.queue=[];var s='script';r=t.createElement(s);r.async=!0;
r.src=n;var u=t.getElementsByTagName(s)[0];
u.parentNode.insertBefore(r,u);})(window,document,
'https://sc-static.net/scevent.min.js');

snaptr('init', 'PIXEL_ID', {
  'user_email': '__INSERT_USER_EMAIL__'
});
snaptr('track', 'PAGE_VIEW');

// Track purchase
snaptr('track', 'PURCHASE', {
  'price': 29.99,
  'currency': 'USD',
  'transaction_id': 'order-123'
});
</script>
```

## 🎯 EVENTS
PAGE_VIEW, PURCHASE, SIGN_UP, ADD_CART, VIEW_CONTENT

## 🚨 RULES
✅ Install on all pages ✅ Hash user email (SHA-256)

**Version:** 1.0 | **Model:** haiku
