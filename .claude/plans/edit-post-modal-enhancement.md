# Edit Post Modal Enhancement Plan

**Date:** 2025-12-01
**Status:** Planning
**Scope:** Full Feature Set - All Enhancement Categories
**Estimated Complexity:** Comprehensive (1-2 weeks)

---

## Executive Summary

This plan enhances the Edit Post Modal at `/orgs/{org_id}/social` by bringing existing publish modal capabilities to the edit experience, adding AI-powered content assistance, platform-specific validation, and improved media management. The approach prioritizes **reusing existing components** to minimize risk and ensure consistency.

---

## Current State Analysis

### Existing Edit Post Modal Features
- Content editing (textarea with character counter)
- Schedule date/time editing (draft/scheduled posts only)
- Timezone display with inheritance hierarchy
- Media preview (read-only - cannot modify)
- Platform icon & account info display
- Status-aware restrictions
- Save/Cancel with loading states

### Key Files
| Component | File Path |
|-----------|-----------|
| Modal UI | `resources/views/social/components/modals/edit-post-modal.blade.php` |
| Alpine.js Logic | `resources/views/social/scripts/social-manager.blade.php` |
| Backend Controller | `app/Http/Controllers/Social/SocialPostController.php` |
| Platform Config | `config/social-platforms.php` |

### Existing Components Available for Reuse
| Component | Source | Status |
|-----------|--------|--------|
| Emoji Picker | `publish-modal/composer/global-content.blade.php` | Ready |
| Mention Picker | `publish-modal/overlays/mention-picker.blade.php` | Ready |
| Hashtag Manager | `publish-modal/overlays/hashtag-manager.blade.php` | Ready |
| Media Upload/Reorder | `publish-modal/composer/global-content.blade.php` | Ready |
| AI Content APIs | `app/Http/Controllers/API/AIAssistantController.php` | Ready |
| Platform Validation | `config/social-platforms.php` + Services | Ready |
| Link Shortening | `platformFeatures.js` | Ready |

---

## Enhancement Categories

### Category 1: Media Management
**Priority:** High
**Complexity:** Medium

#### 1.1 Media Editing Capabilities
- [ ] Add/remove media files (images/videos)
- [ ] Drag-and-drop media reordering
- [ ] Upload new media with progress indicators
- [ ] Media library integration
- [ ] Platform-specific media validation

#### 1.2 Implementation Details

**Files to Modify:**
- `resources/views/social/components/modals/edit-post-modal.blade.php`
- `resources/views/social/scripts/social-manager.blade.php`
- `app/Http/Controllers/Social/SocialPostController.php`

**New State Variables:**
```javascript
// Add to socialManager in social-manager.blade.php
editMediaDraggedIndex: null,
editMediaDragOverIndex: null,
editMediaUploading: false,
editMediaUploadProgress: 0,
showEditMediaLibrary: false,
```

**New Methods:**
```javascript
// Media management for edit modal
async uploadEditMedia(event) { /* Upload handler */ }
handleEditMediaDrop(event) { /* Drag-drop upload */ }
removeEditMedia(index) { /* Remove media item */ }
reorderEditMedia(oldIdx, newIdx) { /* Reorder via drag */ }
selectFromMediaLibrary(media) { /* Add from library */ }
```

**Backend Updates:**
```php
// SocialPostController::update() - Add media handling
$request->validate([
    'media' => 'sometimes|array|max:10',
    'media.*.url' => 'required|string',
    'media.*.type' => 'required|in:image,video',
]);
```

**UI Changes:**
```blade
<!-- Replace read-only media preview with editable grid -->
<div class="grid grid-cols-4 gap-2"
     @dragend="editMediaDraggedIndex = null">
    <template x-for="(media, index) in editingPost.media" :key="index">
        <div draggable="true"
             @dragstart="editMediaDraggedIndex = index"
             @dragover.prevent="editMediaDragOverIndex = index"
             @drop.prevent="reorderEditMedia(editMediaDraggedIndex, index)"
             class="relative group">
            <!-- Media thumbnail -->
            <!-- Order badge -->
            <!-- Remove button -->
            <!-- Drag handle -->
        </div>
    </template>
    <!-- Add media button -->
</div>
```

---

### Category 2: Platform Validation
**Priority:** High
**Complexity:** Low-Medium

#### 2.1 Real-time Character Limits
- [ ] Show platform-specific character limits
- [ ] Color-coded progress bar (green → yellow → red)
- [ ] Warning when approaching/exceeding limit
- [ ] Display remaining characters

#### 2.2 Platform Character Limits (from config)
| Platform | Text Limit | Special Notes |
|----------|-----------|---------------|
| Twitter | 280 | Premium: 4,000 |
| LinkedIn | 3,000 | |
| TikTok | 2,200 | Caption |
| Instagram | 2,200 | Caption |
| Threads | 500 | |
| YouTube | 100 title / 5,000 desc | |
| Reddit | 300 title / 40,000 body | |
| Pinterest | 100 title / 500 desc | |
| Google Business | 1,500 | |

#### 2.3 Implementation Details

**New State Variables:**
```javascript
editPlatformLimits: {
    twitter: 280,
    linkedin: 3000,
    tiktok: 2200,
    instagram: 2200,
    facebook: 63206,
    threads: 500,
    // ... loaded from config
},
editCharacterWarningThreshold: 0.9, // 90%
```

**New Computed Properties:**
```javascript
get editCharacterCount() {
    return this.editingPost.content?.length || 0;
},
get editCharacterLimit() {
    return this.editPlatformLimits[this.editingPost.platform] || 5000;
},
get editCharacterPercentage() {
    return Math.min((this.editCharacterCount / this.editCharacterLimit) * 100, 100);
},
get editCharacterStatus() {
    const pct = this.editCharacterPercentage;
    if (pct >= 100) return 'exceeded';
    if (pct >= 90) return 'warning';
    if (pct >= 75) return 'caution';
    return 'ok';
}
```

**UI Component:**
```blade
<!-- Character limit indicator -->
<div class="mt-2">
    <div class="flex justify-between text-xs mb-1">
        <span x-text="editCharacterCount + '/' + editCharacterLimit"></span>
        <span x-show="editCharacterStatus === 'exceeded'" class="text-red-600">
            {{ __('social.character_limit_exceeded') }}
        </span>
    </div>
    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full transition-all duration-300"
             :class="{
                 'bg-green-500': editCharacterStatus === 'ok',
                 'bg-yellow-500': editCharacterStatus === 'caution',
                 'bg-orange-500': editCharacterStatus === 'warning',
                 'bg-red-500': editCharacterStatus === 'exceeded'
             }"
             :style="'width: ' + editCharacterPercentage + '%'">
        </div>
    </div>
</div>
```

**Backend Endpoint:**
```php
// Add new route: GET /orgs/{org}/social/platform-limits
Route::get('platform-limits', [SocialPostController::class, 'getPlatformLimits']);

// Controller method
public function getPlatformLimits()
{
    return $this->success([
        'limits' => config('social-platforms'),
    ]);
}
```

---

### Category 3: AI Content Assistance
**Priority:** High
**Complexity:** Medium

#### 3.1 AI Features
- [ ] Hashtag suggestions (platform-specific)
- [ ] Content improvement suggestions
- [ ] Content transformation (shorter/longer/formal/casual)
- [ ] Add emojis automatically
- [ ] Brand fit analysis (if profile group linked)

#### 3.2 Available API Endpoints
| Feature | Endpoint | Method |
|---------|----------|--------|
| Generate Hashtags | `/api/ai/generate-hashtags` | POST |
| Improve Content | `/api/ai/suggest-improvements` | POST |
| Transform Content | `/api/ai/transform-social-content` | POST |
| Generate Variations | `/api/ai/generate-variations` | POST |
| Brand Suggestions | `/api/social/kb-content/suggestions` | GET |
| Brand Fit Score | `/api/social/kb-content/analyze-fit` | POST |

#### 3.3 Implementation Details

**New State Variables:**
```javascript
// AI assistance state
showEditAIPanel: false,
editAILoading: false,
editAISuggestions: {
    hashtags: [],
    improvements: [],
    variations: [],
    brandFit: null
},
editAIError: null,
```

**New Methods:**
```javascript
async generateEditHashtags() {
    this.editAILoading = true;
    try {
        const response = await fetch('/api/ai/generate-hashtags', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                caption: this.editingPost.content,
                platform: this.editingPost.platform
            })
        });
        const result = await response.json();
        this.editAISuggestions.hashtags = result.data?.hashtags || [];
    } finally {
        this.editAILoading = false;
    }
}

async improveEditContent() {
    // Call /api/ai/suggest-improvements
}

async transformEditContent(type) {
    // Call /api/ai/transform-social-content
    // Types: shorter, longer, formal, casual, hashtags, emojis
}

async analyzeEditBrandFit() {
    // Call /api/social/kb-content/analyze-fit
}

insertEditHashtag(hashtag) {
    const tag = hashtag.startsWith('#') ? hashtag : '#' + hashtag;
    this.editingPost.content += ' ' + tag;
}

applyEditSuggestion(suggestion) {
    this.editingPost.content = suggestion;
}
```

**UI Component - AI Panel:**
```blade
<!-- AI Assistance Panel (collapsible) -->
<div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
    <button @click="showEditAIPanel = !showEditAIPanel"
            class="flex items-center justify-between w-full text-sm font-medium">
        <span class="flex items-center gap-2">
            <i class="fas fa-magic text-purple-500"></i>
            {{ __('social.ai_assistant') }}
        </span>
        <i class="fas" :class="showEditAIPanel ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
    </button>

    <div x-show="showEditAIPanel" x-collapse class="mt-3 space-y-3">
        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-2">
            <button @click="generateEditHashtags()" :disabled="editAILoading"
                    class="px-3 py-1.5 text-xs bg-purple-100 text-purple-700 rounded-full">
                <i class="fas fa-hashtag me-1"></i>
                {{ __('social.suggest_hashtags') }}
            </button>
            <button @click="transformEditContent('emojis')" :disabled="editAILoading"
                    class="px-3 py-1.5 text-xs bg-yellow-100 text-yellow-700 rounded-full">
                <i class="fas fa-smile me-1"></i>
                {{ __('social.add_emojis') }}
            </button>
            <button @click="improveEditContent()" :disabled="editAILoading"
                    class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 rounded-full">
                <i class="fas fa-lightbulb me-1"></i>
                {{ __('social.improve') }}
            </button>
        </div>

        <!-- Transform Options -->
        <div class="flex gap-2">
            <button @click="transformEditContent('shorter')" class="...">
                {{ __('social.make_shorter') }}
            </button>
            <button @click="transformEditContent('longer')" class="...">
                {{ __('social.make_longer') }}
            </button>
            <button @click="transformEditContent('formal')" class="...">
                {{ __('social.more_formal') }}
            </button>
            <button @click="transformEditContent('casual')" class="...">
                {{ __('social.more_casual') }}
            </button>
        </div>

        <!-- Suggestions Display -->
        <template x-if="editAISuggestions.hashtags.length > 0">
            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <p class="text-xs font-medium mb-2">{{ __('social.suggested_hashtags') }}</p>
                <div class="flex flex-wrap gap-1">
                    <template x-for="tag in editAISuggestions.hashtags">
                        <button @click="insertEditHashtag(tag)"
                                class="px-2 py-0.5 text-xs bg-white rounded border hover:bg-purple-100"
                                x-text="tag"></button>
                    </template>
                </div>
            </div>
        </template>

        <!-- Brand Fit Score (if available) -->
        <template x-if="editAISuggestions.brandFit">
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-medium">{{ __('social.brand_fit') }}</span>
                    <span class="text-lg font-bold"
                          :class="editAISuggestions.brandFit.score >= 70 ? 'text-green-600' : 'text-yellow-600'"
                          x-text="editAISuggestions.brandFit.score + '%'"></span>
                </div>
            </div>
        </template>
    </div>
</div>
```

---

### Category 4: Rich Editing Experience
**Priority:** Medium
**Complexity:** Medium

#### 4.1 Features
- [ ] Emoji picker (reuse from publish modal)
- [ ] Mention picker (reuse from publish modal)
- [ ] Hashtag quick-insert
- [ ] Link shortening
- [ ] Rich text formatting toolbar (bold, italic, etc.)

#### 4.2 Implementation Details

**Reuse Existing Components:**
```blade
<!-- Include emoji picker from publish modal pattern -->
@include('components.publish-modal.partials.emoji-picker', [
    'targetModel' => 'editingPost.content',
    'showVar' => 'showEditEmojiPicker'
])

<!-- Include mention picker -->
@include('components.publish-modal.partials.mention-picker', [
    'targetModel' => 'editingPost.content',
    'showVar' => 'showEditMentionPicker'
])
```

**New State Variables:**
```javascript
// Rich editing state
showEditEmojiPicker: false,
showEditMentionPicker: false,
showEditHashtagManager: false,
editLinkShorteningInProgress: false,

// Emoji collection (copy from publish modal)
editCommonEmojis: [
    '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃',
    // ... 160+ emojis
],
```

**New Methods:**
```javascript
insertEditEmoji(emoji) {
    // Insert at cursor position
    const textarea = this.$refs.editContentTextarea;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = this.editingPost.content;
    this.editingPost.content = text.slice(0, start) + emoji + text.slice(end);

    // Reset cursor
    this.$nextTick(() => {
        textarea.focus();
        textarea.setSelectionRange(start + emoji.length, start + emoji.length);
    });

    this.showEditEmojiPicker = false;
}

async shortenEditLink() {
    // Extract URL from content and shorten
}

formatEditText(type) {
    // Apply markdown formatting: bold, italic, etc.
}
```

**UI - Toolbar:**
```blade
<!-- Editing Toolbar -->
<div class="flex items-center gap-1 p-2 bg-gray-50 dark:bg-gray-700 rounded-t-lg border-b">
    <!-- Emoji -->
    <div class="relative">
        <button @click="showEditEmojiPicker = !showEditEmojiPicker"
                class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">
            <i class="fas fa-smile text-gray-500"></i>
        </button>
        <!-- Emoji dropdown -->
        <div x-show="showEditEmojiPicker" @click.away="showEditEmojiPicker = false"
             class="absolute top-full start-0 mt-1 p-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl z-50 w-64 max-h-48 overflow-y-auto">
            <div class="grid grid-cols-8 gap-1">
                <template x-for="emoji in editCommonEmojis.slice(0, 64)">
                    <button @click="insertEditEmoji(emoji)"
                            class="p-1 hover:bg-gray-100 rounded text-lg"
                            x-text="emoji"></button>
                </template>
            </div>
        </div>
    </div>

    <!-- Hashtag -->
    <button @click="showEditHashtagManager = true"
            class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">
        <i class="fas fa-hashtag text-gray-500"></i>
    </button>

    <!-- Mention -->
    <button @click="showEditMentionPicker = true"
            class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">
        <i class="fas fa-at text-gray-500"></i>
    </button>

    <!-- Divider -->
    <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

    <!-- Formatting -->
    <button @click="formatEditText('bold')" class="p-2 hover:bg-gray-200 rounded font-bold">
        B
    </button>
    <button @click="formatEditText('italic')" class="p-2 hover:bg-gray-200 rounded italic">
        I
    </button>

    <!-- Link Shortener -->
    <button @click="shortenEditLink()" :disabled="editLinkShorteningInProgress"
            class="p-2 hover:bg-gray-200 rounded">
        <i class="fas fa-link text-gray-500"></i>
    </button>
</div>
```

---

## Implementation Phases

### Phase 1: Platform Validation (Days 1-2)
**Low risk, high impact**

1. Add platform limits to edit modal state
2. Create character counter component with progress bar
3. Add validation feedback UI
4. Backend endpoint for platform limits
5. Test with all platforms

**Files Modified:**
- `edit-post-modal.blade.php`
- `social-manager.blade.php`
- `SocialPostController.php`

### Phase 2: Rich Editing Toolbar (Days 3-4)
**Medium risk, high UX impact**

1. Extract emoji picker as reusable partial
2. Add editing toolbar to modal
3. Implement cursor-aware text insertion
4. Add keyboard shortcuts
5. Test RTL/LTR support

**Files Modified:**
- `edit-post-modal.blade.php`
- `social-manager.blade.php`
- Create: `resources/views/components/edit-post/toolbar.blade.php`

### Phase 3: AI Content Assistance (Days 5-7)
**Medium risk, high value**

1. Add AI panel UI (collapsible)
2. Integrate hashtag generation API
3. Integrate content transformation APIs
4. Add brand fit analysis (if profile group exists)
5. Handle loading states and errors
6. Test with rate limits

**Files Modified:**
- `edit-post-modal.blade.php`
- `social-manager.blade.php`
- Create: `resources/views/components/edit-post/ai-panel.blade.php`

### Phase 4: Media Management (Days 8-10)
**Higher risk, essential feature**

1. Convert read-only media to editable grid
2. Add drag-and-drop reordering
3. Add media upload capability
4. Integrate media library
5. Add platform-specific media validation
6. Update backend to handle media changes
7. Comprehensive testing

**Files Modified:**
- `edit-post-modal.blade.php`
- `social-manager.blade.php`
- `SocialPostController.php`
- Create: `resources/views/components/edit-post/media-grid.blade.php`

---

## Translation Keys Required

```php
// resources/lang/ar/social.php & resources/lang/en/social.php
'ai_assistant' => 'AI Assistant / مساعد الذكاء الاصطناعي',
'suggest_hashtags' => 'Suggest Hashtags / اقتراح وسوم',
'add_emojis' => 'Add Emojis / إضافة رموز تعبيرية',
'improve' => 'Improve / تحسين',
'make_shorter' => 'Shorter / أقصر',
'make_longer' => 'Longer / أطول',
'more_formal' => 'Formal / رسمي',
'more_casual' => 'Casual / عادي',
'suggested_hashtags' => 'Suggested Hashtags / الوسوم المقترحة',
'brand_fit' => 'Brand Fit / توافق العلامة التجارية',
'character_limit_exceeded' => 'Character limit exceeded / تم تجاوز الحد الأقصى للأحرف',
'characters_remaining' => ':count characters remaining / :count حرف متبقي',
'add_media' => 'Add Media / إضافة وسائط',
'reorder_media' => 'Drag to reorder / اسحب لإعادة الترتيب',
'remove_media' => 'Remove / إزالة',
'uploading_media' => 'Uploading... / جاري الرفع...',
'media_upload_failed' => 'Upload failed / فشل الرفع',
```

---

## Risk Mitigation

### Preserving Current Functionality
1. **Feature flags:** Add `$enableEnhancedEditModal` config option
2. **Gradual rollout:** Test each phase independently
3. **Fallback:** Keep original modal code as backup
4. **No breaking changes:** Existing API contracts maintained

### Testing Strategy
1. **Unit tests:** New methods in isolation
2. **Feature tests:** API endpoints
3. **Browser tests:** Full modal flow (both languages)
4. **Platform tests:** Each platform's validation rules

### Performance Considerations
1. **Lazy load AI panel:** Only fetch suggestions when opened
2. **Debounce character counting:** Avoid excessive re-renders
3. **Optimize media uploads:** Use chunked upload for large files
4. **Cache platform limits:** Load once, store in Alpine state

---

## Success Criteria

### Must Have
- [ ] Character limit validation with visual feedback
- [ ] Emoji picker functional
- [ ] AI hashtag suggestions working
- [ ] Media add/remove capability
- [ ] RTL/LTR support maintained
- [ ] No regressions in existing functionality

### Should Have
- [ ] Media drag-and-drop reordering
- [ ] Content transformation (shorter/longer)
- [ ] Brand fit analysis
- [ ] Mention picker

### Nice to Have
- [ ] Link preview
- [ ] Keyboard shortcuts
- [ ] Undo/redo support
- [ ] Draft autosave

---

## Dependencies

### External
- AI API endpoints (already implemented)
- Media upload endpoint (already implemented)
- Platform config (already exists)

### Internal
- Publish modal components (for reuse patterns)
- Translation files (need new keys)
- socialManager Alpine component (modification)

---

## Post-Implementation

### Verification Checklist
- [ ] Browser console clean (no errors)
- [ ] Laravel logs clean (no exceptions)
- [ ] Arabic (RTL) language tested
- [ ] English (LTR) language tested
- [ ] All platforms validated
- [ ] Mobile responsive tested
- [ ] Automated tests created
- [ ] Git commit with verification status

### Documentation Updates
- [ ] Update CLAUDE.md if needed
- [ ] Update browser testing guide
- [ ] Add to knowledge base

---

## Approval Required

Please review this plan and confirm:

1. **Scope:** Is the full feature set appropriate, or should we reduce scope?
2. **Phases:** Is the phased approach acceptable?
3. **Priorities:** Should any category be reprioritized?
4. **Timeline:** Any deadline constraints?
5. **Testing:** Any specific test scenarios to include?

Once approved, implementation will begin with Phase 1 (Platform Validation).
