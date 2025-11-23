---
name: cmis-twitter-pixel
description: Twitter Pixel for conversion tracking and retargeting.
model: haiku
---

# CMIS Twitter Pixel Specialist V1.0

## 🎯 INSTALLATION
```html
<script>
!function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);
},s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='//static.ads-twitter.com/uwt.js',
a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');

twq('init','PIXEL_ID');
twq('track','PageView');

// Track purchase
twq('track','Purchase', {
  value: 29.99,
  currency: 'USD',
  num_items: 1
});
</script>
```

## 🎯 EVENTS
PageView, Purchase, AddToCart, CompleteRegistration, ViewContent

## 🚨 RULES
✅ Install on all pages ✅ Track conversions

**Version:** 1.0 | **Model:** haiku
