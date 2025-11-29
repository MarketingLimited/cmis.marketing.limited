# Modal Files Inventory

**Date:** 2025-11-30
**Purpose:** Complete documentation of all modal files in the CMIS codebase
**Status:** ✅ Complete

---

## 📂 Modal Files Overview

### Generic/Reusable Modals

| File | Status | Lines | Usage | Purpose |
|------|--------|-------|-------|---------|
| `components/ui/modal.blade.php` | ✅ **ACTIVE** | 82 | 6+ files | Primary generic modal component |
| `components/modal.blade.php.deprecated` | ⚠️ **DEPRECATED** | 101 | 0 files | Old modal (renamed, to be deleted) |
| `components/delete-confirmation-modal.blade.php` | ✅ **ACTIVE** | 198 | Global | Specialized delete confirmation modal |

---

### Publish/Content Creation Modals

| File | Status | Lines | Usage | Purpose |
|------|--------|-------|-------|---------|
| `components/publish-modal.blade.php` | ✅ **ACTIVE** | 183 | Global | Universal post publishing interface |
| `social/posts/components/create-modal.blade.php` | ✅ **ACTIVE** | 250 | social/index.blade.php | Simple social post creation |
| `social/components/modals/edit-post-modal.blade.php` | ✅ **ACTIVE** | 137 | social/index.blade.php | Edit existing social posts |
| `social/components/modals/queue-settings-modal.blade.php` | ✅ **ACTIVE** | 23K | social/index.blade.php | Queue settings configuration |

---

### Social History Modals

| File | Status | Lines | Usage | Purpose |
|------|--------|-------|-------|---------|
| `social/history/components/import-modal.blade.php` | ⚠️ **REVIEW** | ~260 | social/history/* | Import social posts |
| `social/history/components/post-detail-modal.blade.php` | ⚠️ **REVIEW** | ~95 | social/history/* | View post details |
| `social/history/components/kb-modal.blade.php` | ⚠️ **REVIEW** | ~57 | social/history/* | Knowledge base modal |
| `social/history/components/campaign-modal.blade.php` | ⚠️ **REVIEW** | ~9 | social/history/* | Campaign modal |

---

### Publish Modal Sub-Components (24 files)

#### Overlays (6 files)
| File | Status | Lines | Purpose |
|------|--------|-------|---------|
| `overlays/hashtag-manager.blade.php` | ✅ **ACTIVE** | 141 | Hashtag management (trending, saved sets) |
| `overlays/mention-picker.blade.php` | ✅ **ACTIVE** | 59 | Mention connected accounts |
| `overlays/calendar.blade.php` | ✅ **ACTIVE** | 94 | Scheduled posts calendar view |
| `overlays/best-times.blade.php` | ✅ **ACTIVE** | 50 | Analytics-driven posting time suggestions |
| `overlays/media-source-picker.blade.php` | ✅ **ACTIVE** | 97 | Media sources (URL, Drive, Dropbox, OneDrive) |
| `overlays/media-library.blade.php` | ✅ **ACTIVE** | 71 | Previously uploaded media |

#### Main Components (7 files)
| File | Status | Lines | Purpose |
|------|--------|-------|---------|
| `header.blade.php` | ✅ **ACTIVE** | 33 | Modal header with close button |
| `warnings-banner.blade.php` | ✅ **ACTIVE** | 53 | Platform warnings/alerts banner |
| `profile-selector.blade.php` | ✅ **ACTIVE** | 171 | Multi-platform profile selection |
| `preview-panel.blade.php` | ✅ **ACTIVE** | 341 | Real-time platform previews |
| `footer.blade.php` | ✅ **ACTIVE** | 11 | Modal footer with actions |
| `content-composer.blade.php` | ⚠️ **UNUSED?** | ? | (Check if replaced by composer/main.blade.php) |

#### Composer Components (11 files)
| File | Status | Lines | Purpose |
|------|--------|-------|---------|
| `composer/main.blade.php` | ✅ **ACTIVE** | ? | Main composer container |
| `composer/tabs.blade.php` | ✅ **ACTIVE** | 22 | Global vs Platform tabs |
| `composer/global-content.blade.php` | ✅ **ACTIVE** | 218 | Global content input (all platforms) |
| `composer/platform-content.blade.php` | ✅ **ACTIVE** | 503 | Platform-specific content |
| `composer/scheduling.blade.php` | ✅ **ACTIVE** | 101 | Scheduling options |
| `composer/platform-options/instagram.blade.php` | ✅ **ACTIVE** | ? | Instagram-specific options |
| `composer/platform-options/twitter.blade.php` | ✅ **ACTIVE** | ? | Twitter-specific options |
| `composer/platform-options/facebook.blade.php` | ✅ **ACTIVE** | ? | Facebook-specific options |
| `composer/platform-options/tiktok.blade.php` | ✅ **ACTIVE** | ? | TikTok-specific options |
| `composer/platform-options/youtube.blade.php` | ✅ **ACTIVE** | ? | YouTube-specific options |
| `composer/platform-options/google-business.blade.php` | ✅ **ACTIVE** | ? | Google Business-specific options |
| `composer/platform-options/location.blade.php` | ✅ **ACTIVE** | ? | Location tagging options |

---

## 📊 Usage Statistics

### Active Modals by Type

| Category | Active | Deprecated | Review Needed | Total |
|----------|--------|------------|---------------|-------|
| **Generic Modals** | 2 | 1 | 0 | 3 |
| **Publish Modals** | 28 | 0 | 1 | 29 |
| **Social Modals** | 4 | 0 | 0 | 4 |
| **Social History** | 0 | 0 | 4 | 4 |
| **TOTAL** | 34 | 1 | 5 | 40 |

---

## 🔍 Detailed Analysis

### Active Modals (34 files)

All modals marked as **ACTIVE** are currently in use and should be maintained.

---

### Deprecated Modals (1 file)

**`components/modal.blade.php.deprecated`**
- **Reason:** Duplicate of `components/ui/modal.blade.php`
- **Action:** Delete in next cleanup
- **Safety:** No usages found in codebase
- **Date Deprecated:** 2025-11-30

---

### Modals Needing Review (5 files)

1. **`components/publish-modal/content-composer.blade.php`**
   - **Issue:** May be replaced by `composer/main.blade.php`
   - **Action:** Verify if still included anywhere
   - **Next Step:** Check for `@include('...content-composer')`

2. **Social History Modals (4 files)**
   - **Issue:** Unclear if social history feature is actively used
   - **Action:** Verify with product team or check usage analytics
   - **Files:**
     - `social/history/components/import-modal.blade.php`
     - `social/history/components/post-detail-modal.blade.php`
     - `social/history/components/kb-modal.blade.php`
     - `social/history/components/campaign-modal.blade.php`

---

## ✅ Cleanup Actions

### Immediate Actions

- [x] Document all modal files
- [x] Mark deprecated files
- [ ] Delete `modal.blade.php.deprecated` (after 1 sprint safety period)
- [ ] Verify `content-composer.blade.php` usage
- [ ] Verify social history modal usage

### Future Actions

- [ ] Consider extracting shared components:
  - Media upload component
  - Content textarea component
  - Publishing options component
- [ ] Standardize all modal z-index values
- [ ] Standardize all modal positioning (fixed vs absolute)

---

## 📝 Usage Patterns

### How to Use Generic Modals

**Preferred:** `<x-ui.modal>`
```blade
<x-ui.modal name="my-modal" title="Modal Title" max-width="lg">
    <!-- Modal content -->

    <x-slot name="footer">
        <button @click="$dispatch('close-modal', 'my-modal')">Close</button>
    </x-slot>
</x-ui.modal>

<!-- Open from JavaScript -->
<button @click="$dispatch('open-modal', 'my-modal')">Open Modal</button>
```

**Delete Confirmation:** `@include('components.delete-confirmation-modal')`
```blade
@include('components.delete-confirmation-modal')

<!-- Trigger from JavaScript -->
<button @click="$dispatch('open-delete-modal', {
    url: '/api/resource/123',
    name: 'Resource Name',
    cascade: '<li>Related Item 1</li><li>Related Item 2</li>'
})">
    Delete
</button>
```

### How to Use Publish Modal

**Global Publish Button:**
```blade
<!-- In layouts/admin.blade.php -->
<button @click="$dispatch('open-publish-modal')">
    <i class="fas fa-plus"></i> Create Post
</button>
```

**The publish-modal is automatically included in the admin layout.**

### How to Use Social Modals

**In social/index.blade.php:**
```blade
<!-- Create Post -->
<button @click="showCreateModal = true">Create Post</button>

<!-- Edit Post -->
<button @click="openEditModal(post)">Edit</button>
```

---

## 🎯 Best Practices

1. **Use `<x-ui.modal>` for all new generic modals**
   - Don't create new modal components
   - Extend/customize via slots

2. **Don't duplicate modal logic**
   - Use the existing publish-modal for publishing
   - Use the existing create-modal for simple social posts
   - Use the existing ui.modal for everything else

3. **Standardize z-index**
   - Modals: `z-50`
   - Overlays within modals: `z-[200]`
   - Never use inline `style="display: none;"`

4. **Use `fixed` positioning for top-level modals**
   - Better viewport coverage
   - More predictable behavior
   - Easier responsive handling

---

**Maintained by:** Development Team
**Last Updated:** 2025-11-30
