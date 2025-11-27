{{-- Publishing Modal Component - Arabic RTL --}}
{{-- 3-Column Layout: Profiles | Composer | Preview --}}
<div x-data="publishModal()" x-show="open" x-cloak dir="rtl"
     class="fixed inset-0 z-50 overflow-hidden" @keydown.escape.window="closeModal()">
    {{-- Backdrop --}}
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900 bg-opacity-75" @click="closeModal()"></div>

    {{-- Modal Panel --}}
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div x-show="open" x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             class="relative w-full max-w-7xl max-h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">

            {{-- Modal Header --}}
            <div class="flex-shrink-0 px-6 py-4 border-b border-gray-200 bg-gradient-to-l from-indigo-600 to-purple-600">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <h2 class="text-lg font-bold text-white">
                            <i class="fas fa-paper-plane text-white/80 ml-2"></i>
                            <span x-text="editMode ? 'تعديل المنشور' : 'إنشاء منشور جديد'"></span>
                        </h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="saveDraft()" class="px-3 py-1.5 text-sm text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition">
                            <i class="fas fa-save ml-1"></i>حفظ كمسودة
                        </button>
                        <button @click="closeModal()" class="p-2 text-white/80 hover:text-white rounded-lg hover:bg-white/10">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Main Content - 3 Columns --}}
            <div class="flex-1 flex flex-row-reverse overflow-hidden">
                {{-- Column 1: Profile Groups & Profiles Selection (Right side in RTL) --}}
                <div class="w-80 flex-shrink-0 border-l border-gray-200 bg-gray-50 flex flex-col">

                    {{-- STEP 1: Profile Groups Selection --}}
                    <div class="p-3 border-b border-gray-200 bg-white">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-bold text-gray-700">
                                <i class="fas fa-folder-open text-indigo-500 ml-1"></i>
                                مجموعات الحسابات
                            </h4>
                            <span class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full" x-text="selectedGroupIds.length + ' / ' + profileGroups.length"></span>
                        </div>

                        {{-- Groups Multi-Select --}}
                        <div class="space-y-1 max-h-32 overflow-y-auto">
                            <template x-for="group in profileGroups" :key="group.group_id">
                                <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition"
                                       :class="selectedGroupIds.includes(group.group_id) ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50'">
                                    <input type="checkbox" :value="group.group_id"
                                           :checked="selectedGroupIds.includes(group.group_id)"
                                           @change="toggleGroupId(group.group_id)"
                                           class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-medium text-gray-700" x-text="group.name"></span>
                                    </div>
                                    <span class="text-xs text-gray-400" x-text="'(' + (group.profiles?.length || 0) + ')'"></span>
                                </label>
                            </template>
                        </div>

                        {{-- Quick Actions --}}
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
                            <button @click="selectAllGroups()" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                                <i class="fas fa-check-double ml-1"></i>تحديد الكل
                            </button>
                            <button @click="clearSelectedGroups()" class="text-xs text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times ml-1"></i>مسح
                            </button>
                        </div>
                    </div>

                    {{-- STEP 2: Profiles from Selected Groups --}}
                    <div class="flex-shrink-0 p-3 border-b border-gray-200 bg-gradient-to-l from-purple-50 to-indigo-50">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-gray-700">
                                <i class="fas fa-users text-purple-500 ml-1"></i>
                                الحسابات
                            </h4>
                            <span class="text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded-full" x-text="selectedProfiles.length + ' محدد'"></span>
                        </div>
                    </div>

                    {{-- Search Profiles --}}
                    <div class="p-3 border-b border-gray-200">
                        <div class="relative">
                            <input type="text" x-model="profileSearch" placeholder="ابحث في الحسابات..."
                                   class="w-full pr-9 pl-3 py-2 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        </div>
                        {{-- Platform Filters --}}
                        <div class="flex flex-wrap gap-1 mt-3">
                            <button @click="platformFilter = null"
                                    :class="platformFilter === null ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                                    class="px-2 py-1 text-xs rounded-full border border-gray-200 transition">الكل</button>
                            <template x-for="platform in availablePlatforms" :key="platform">
                                <button @click="platformFilter = platform"
                                        :class="platformFilter === platform ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                                        class="px-2 py-1 text-xs rounded-full border border-gray-200 transition">
                                    <i :class="getPlatformIcon(platform)"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Profile List --}}
                    <div class="flex-1 overflow-y-auto p-2">
                        {{-- Empty State: No Groups Selected --}}
                        <div x-show="selectedGroupIds.length === 0" class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-folder-open text-2xl text-gray-400"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-600">اختر مجموعات الحسابات أولاً</p>
                            <p class="text-xs text-gray-400 mt-1">حدد مجموعة أو أكثر من الأعلى</p>
                        </div>

                        {{-- Profiles from Selected Groups --}}
                        <div x-show="selectedGroupIds.length > 0">
                            {{-- Select All/Clear --}}
                            <div class="flex items-center justify-between px-2 py-2 mb-2">
                                <button @click="selectAllProfiles()" class="text-xs text-blue-600 hover:text-blue-700">
                                    <i class="fas fa-check-double ml-1"></i>تحديد كل الحسابات
                                </button>
                                <button @click="clearSelectedProfiles()" class="text-xs text-gray-500 hover:text-gray-700">مسح</button>
                            </div>

                            {{-- Grouped Profiles --}}
                            <template x-for="group in filteredProfileGroups" :key="group.group_id">
                            <div class="mb-4">
                                {{-- Group Header with Select All --}}
                                <div class="flex items-center justify-between px-2 py-2 bg-gradient-to-l from-indigo-50 to-purple-50 rounded-lg mb-2 cursor-pointer hover:from-indigo-100 hover:to-purple-100 transition"
                                     @click="toggleGroupSelection(group)">
                                    <div class="flex items-center gap-2">
                                        {{-- Group Checkbox --}}
                                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition"
                                             :class="isGroupFullySelected(group) ? 'bg-indigo-600 border-indigo-600' : (isGroupPartiallySelected(group) ? 'bg-indigo-300 border-indigo-400' : 'border-gray-300 bg-white')">
                                            <i class="fas fa-check text-white text-xs" x-show="isGroupFullySelected(group)"></i>
                                            <i class="fas fa-minus text-white text-xs" x-show="isGroupPartiallySelected(group) && !isGroupFullySelected(group)"></i>
                                        </div>
                                        <i class="fas fa-layer-group text-indigo-500"></i>
                                        <span class="text-sm font-bold text-gray-700" x-text="group.name"></span>
                                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full" x-text="group.profiles?.length || 0"></span>
                                    </div>
                                    <span class="text-xs text-indigo-600 font-medium">
                                        <span x-show="!isGroupFullySelected(group)">تحديد الكل</span>
                                        <span x-show="isGroupFullySelected(group)">إلغاء الكل</span>
                                    </span>
                                </div>
                                {{-- Accounts in Group --}}
                                <template x-for="profile in group.profiles" :key="profile.integration_id">
                                    <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-white transition mr-2"
                                           :class="{ 'bg-blue-50 ring-1 ring-blue-200': isProfileSelected(profile.integration_id) }">
                                        <input type="checkbox" :value="profile.integration_id"
                                               :checked="isProfileSelected(profile.integration_id)"
                                               @change="toggleProfile(profile)"
                                               class="sr-only">
                                        <div class="relative">
                                            <img :src="profile.avatar_url || getDefaultAvatar(profile)"
                                                 :alt="profile.account_name"
                                                 class="w-10 h-10 rounded-full ring-2"
                                                 :class="isProfileSelected(profile.integration_id) ? 'ring-blue-500' : 'ring-gray-200'">
                                            <div class="absolute -bottom-1 -left-1 w-5 h-5 rounded-full flex items-center justify-center text-white text-xs"
                                                 :class="getPlatformBgClass(profile.platform)">
                                                <i :class="getPlatformIcon(profile.platform)"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="profile.account_name"></p>
                                            <p class="text-xs text-gray-500 truncate" x-text="profile.platform_handle || profile.platform"></p>
                                        </div>
                                        <template x-if="profile.status === 'error'">
                                            <i class="fas fa-exclamation-circle text-red-500" title="خطأ في الاتصال"></i>
                                        </template>
                                        <div x-show="isProfileSelected(profile.integration_id)" class="text-green-500">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </template>
                        </div>
                    </div>

                    {{-- Selected Profiles Bar --}}
                    <div class="flex-shrink-0 p-3 bg-white border-t border-gray-200">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">
                                <span x-text="selectedProfiles.length" class="font-semibold text-blue-600"></span> محدد
                            </span>
                            <div class="flex -space-x-reverse -space-x-2 flex-1 overflow-hidden">
                                <template x-for="profile in selectedProfiles.slice(0, 5)" :key="profile.integration_id">
                                    <img :src="profile.avatar_url || getDefaultAvatar(profile)"
                                         class="w-7 h-7 rounded-full ring-2 ring-white" :alt="profile.account_name">
                                </template>
                                <template x-if="selectedProfiles.length > 5">
                                    <div class="w-7 h-7 rounded-full bg-gray-300 ring-2 ring-white flex items-center justify-center text-xs font-medium text-gray-600"
                                         x-text="'+' + (selectedProfiles.length - 5)"></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Column 2: Content Composer --}}
                <div class="flex-1 flex flex-col overflow-hidden">
                    {{-- Composer Header/Tabs --}}
                    <div class="flex-shrink-0 px-6 py-3 border-b border-gray-200 bg-white">
                        <div class="flex items-center gap-4">
                            <button @click="composerTab = 'global'"
                                    :class="composerTab === 'global' ? 'text-blue-600 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
                                    class="px-3 py-2 text-sm font-medium border-b-2 transition">
                                <i class="fas fa-globe ml-1"></i>المحتوى العام
                            </button>
                            <template x-for="platform in getSelectedPlatforms()" :key="platform">
                                <button @click="composerTab = platform"
                                        :class="composerTab === platform ? 'text-blue-600 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
                                        class="px-3 py-2 text-sm font-medium border-b-2 transition">
                                    <i :class="getPlatformIcon(platform) + ' mr-1'"></i>
                                    <span x-text="platform.charAt(0).toUpperCase() + platform.slice(1)"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Content Area --}}
                    <div class="flex-1 overflow-y-auto p-6">
                        {{-- Global Content Tab --}}
                        <div x-show="composerTab === 'global'">
                            {{-- Text Editor --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">محتوى المنشور</label>
                                <div class="relative">
                                    <textarea x-model="content.global.text" rows="6"
                                              @input="updateCharacterCounts()"
                                              class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                              placeholder="ماذا تريد أن تشارك؟"></textarea>
                                    {{-- Toolbar --}}
                                    <div class="absolute bottom-2 left-2 right-2 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <button @click="showEmojiPicker = !showEmojiPicker" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="إيموجي">
                                                <i class="far fa-smile"></i>
                                            </button>
                                            <button @click="showHashtagManager = true" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="هاشتاقات">
                                                <i class="fas fa-hashtag"></i>
                                            </button>
                                            <button @click="showMentionPicker = true" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="إشارة">
                                                <i class="fas fa-at"></i>
                                            </button>
                                            <button @click="showAIAssistant = true" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded" title="مساعد الذكاء الاصطناعي">
                                                <i class="fas fa-magic"></i>
                                            </button>
                                        </div>
                                        {{-- Character Counts --}}
                                        <div class="flex items-center gap-3 text-xs">
                                            <template x-for="platform in getSelectedPlatforms()" :key="platform">
                                                <span :class="getCharacterCountClass(platform)">
                                                    <i :class="getPlatformIcon(platform)" class="ml-1"></i>
                                                    <span x-text="getCharacterCount(platform)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Media Upload --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">الوسائط</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition cursor-pointer"
                                     @click="$refs.mediaInput.click()"
                                     @dragover.prevent="isDragging = true"
                                     @dragleave.prevent="isDragging = false"
                                     @drop.prevent="handleMediaDrop($event)"
                                     :class="{ 'border-blue-400 bg-blue-50': isDragging }">
                                    <input type="file" x-ref="mediaInput" @change="handleMediaUpload($event)" multiple accept="image/*,video/*" class="hidden">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-600">اسحب الملفات هنا أو انقر للرفع</p>
                                    <p class="text-xs text-gray-400 mt-1">صور: JPG, PNG, GIF | فيديو: MP4, MOV (بحد أقصى 100MB)</p>
                                </div>

                                {{-- Media Preview --}}
                                <div x-show="content.global.media.length > 0" class="mt-4 grid grid-cols-4 gap-3">
                                    <template x-for="(media, index) in content.global.media" :key="index">
                                        <div class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 group">
                                            <template x-if="media.type === 'image'">
                                                <img :src="media.preview_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="media.type === 'video'">
                                                <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                                    <i class="fas fa-play-circle text-white text-3xl"></i>
                                                </div>
                                            </template>
                                            <button @click="removeMedia(index)"
                                                    class="absolute top-2 right-2 w-6 h-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Link Input --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">الرابط (اختياري)</label>
                                <div class="flex gap-2">
                                    <input type="url" x-model="content.global.link" placeholder="https://..."
                                           class="flex-1 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <button @click="shortenLink()" class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                                            :disabled="!content.global.link" :class="{ 'opacity-50 cursor-not-allowed': !content.global.link }">
                                        <i class="fas fa-compress-alt ml-1"></i>اختصار
                                    </button>
                                </div>
                            </div>

                            {{-- Labels/Tags --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">التصنيفات</label>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="label in content.global.labels" :key="label">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                                            <span x-text="label"></span>
                                            <button @click="removeLabel(label)" class="hover:text-blue-900"><i class="fas fa-times"></i></button>
                                        </span>
                                    </template>
                                    <input type="text" x-model="newLabel" @keydown.enter.prevent="addLabel()"
                                           placeholder="إضافة تصنيف..."
                                           class="px-2 py-1 text-xs border-0 bg-transparent focus:ring-0 w-28">
                                </div>
                            </div>
                        </div>

                        {{-- Per-Platform Content Tabs --}}
                        <template x-for="platform in getSelectedPlatforms()" :key="platform">
                            <div x-show="composerTab === platform">
                                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm text-yellow-800">
                                        <i class="fas fa-info-circle ml-1"></i>
                                        تخصيص المحتوى لـ <span x-text="platform" class="font-semibold"></span>.
                                        اتركه فارغاً لاستخدام المحتوى العام.
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i :class="getPlatformIcon(platform) + ' ml-1'"></i>
                                        محتوى <span x-text="platform.charAt(0).toUpperCase() + platform.slice(1)"></span>
                                    </label>
                                    <textarea x-model="content.platforms[platform].text" rows="5"
                                              :placeholder="'محتوى مخصص لـ ' + platform + '...'"
                                              class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                                </div>

                                {{-- Platform-Specific Options --}}
                                <div x-show="platform === 'instagram'" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع المنشور</label>
                                        <div class="flex gap-3">
                                            <label class="flex items-center">
                                                <input type="radio" x-model="content.platforms.instagram.post_type" value="feed" class="ml-2"> منشور
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" x-model="content.platforms.instagram.post_type" value="reel" class="ml-2"> ريل
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" x-model="content.platforms.instagram.post_type" value="story" class="ml-2"> قصة
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">التعليق الأول</label>
                                        <textarea x-model="content.platforms.instagram.first_comment" rows="2"
                                                  placeholder="أضف الهاشتاقات هنا كتعليق أول..."
                                                  class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"></textarea>
                                    </div>
                                </div>

                                <div x-show="platform === 'twitter'" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">إعدادات الرد</label>
                                        <select x-model="content.platforms.twitter.reply_settings" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="everyone">الجميع يمكنهم الرد</option>
                                            <option value="following">من تتابعهم فقط</option>
                                            <option value="mentioned">المذكورون فقط</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Scheduling Section --}}
                    <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="scheduleEnabled" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                                <span class="text-sm font-medium text-gray-700">جدولة</span>
                            </div>

                            <template x-if="scheduleEnabled">
                                <div class="flex items-center gap-3">
                                    <input type="date" x-model="schedule.date"
                                           class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <input type="time" x-model="schedule.time"
                                           class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <select x-model="schedule.timezone" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="UTC">UTC</option>
                                        <option value="Asia/Riyadh">توقيت الرياض</option>
                                        <option value="Asia/Dubai">توقيت دبي</option>
                                        <option value="Europe/London">لندن</option>
                                        <option value="America/New_York">نيويورك</option>
                                    </select>
                                    <button @click="showBestTimes = true" class="px-3 py-1.5 text-sm text-blue-600 hover:text-blue-700">
                                        <i class="fas fa-clock ml-1"></i>أفضل الأوقات
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Column 3: Preview (Left side in RTL) --}}
                <div class="w-80 flex-shrink-0 border-r border-gray-200 bg-gray-50 flex flex-col">
                    <div class="flex-shrink-0 px-4 py-3 border-b border-gray-200 bg-white">
                        <h3 class="text-sm font-medium text-gray-900">المعاينة</h3>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4">
                        {{-- Platform Preview Selector --}}
                        <div class="flex gap-2 mb-4">
                            <template x-for="platform in getSelectedPlatforms()" :key="platform">
                                <button @click="previewPlatform = platform"
                                        :class="previewPlatform === platform ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                                        class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 transition">
                                    <i :class="getPlatformIcon(platform)"></i>
                                </button>
                            </template>
                        </div>

                        {{-- Preview Card --}}
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            {{-- Profile Header --}}
                            <div class="p-3 flex items-center gap-3">
                                <img :src="getPreviewProfile()?.avatar_url || '/img/default-avatar.png'"
                                     class="w-10 h-10 rounded-full">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900" x-text="getPreviewProfile()?.account_name || 'Account Name'"></p>
                                    <p class="text-xs text-gray-500" x-text="getPreviewTime()"></p>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="px-3 pb-3">
                                <p class="text-sm text-gray-800 whitespace-pre-wrap" x-text="getPreviewContent()"></p>
                            </div>

                            {{-- Media Preview --}}
                            <template x-if="content.global.media.length > 0">
                                <div class="aspect-square bg-gray-100">
                                    <img :src="content.global.media[0]?.preview_url" class="w-full h-full object-cover">
                                </div>
                            </template>

                            {{-- Engagement Mockup --}}
                            <div class="p-3 border-t border-gray-100 flex items-center gap-4 text-gray-500">
                                <span class="flex items-center gap-1 text-sm"><i class="far fa-heart"></i> 0</span>
                                <span class="flex items-center gap-1 text-sm"><i class="far fa-comment"></i> 0</span>
                                <span class="flex items-center gap-1 text-sm"><i class="far fa-share-square"></i> 0</span>
                            </div>
                        </div>

                        {{-- Brand Safety Check --}}
                        <div class="mt-4 p-3 rounded-lg" :class="brandSafetyStatus === 'pass' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                            <div class="flex items-center gap-2">
                                <i :class="brandSafetyStatus === 'pass' ? 'fas fa-check-circle text-green-500' : 'fas fa-exclamation-triangle text-red-500'"></i>
                                <span class="text-sm font-medium" :class="brandSafetyStatus === 'pass' ? 'text-green-700' : 'text-red-700'"
                                      x-text="brandSafetyStatus === 'pass' ? 'المحتوى يتوافق مع معايير العلامة التجارية' : 'تم اكتشاف مشاكل في المحتوى'"></span>
                            </div>
                            <template x-if="brandSafetyIssues.length > 0">
                                <ul class="mt-2 text-xs text-red-600 space-y-1">
                                    <template x-for="issue in brandSafetyIssues" :key="issue">
                                        <li class="flex items-start gap-1">
                                            <i class="fas fa-times mt-0.5"></i>
                                            <span x-text="issue"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 bg-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <template x-if="requiresApproval">
                            <span class="text-sm text-yellow-600">
                                <i class="fas fa-user-clock ml-1"></i>يتطلب الموافقة قبل النشر
                            </span>
                        </template>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            إلغاء
                        </button>
                        <button @click="submitForApproval()" x-show="requiresApproval"
                                :disabled="!canSubmit" :class="{ 'opacity-50 cursor-not-allowed': !canSubmit }"
                                class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 transition">
                            <i class="fas fa-paper-plane ml-1"></i>إرسال للموافقة
                        </button>
                        <button @click="publishNow()" x-show="!requiresApproval && !scheduleEnabled"
                                :disabled="!canSubmit" :class="{ 'opacity-50 cursor-not-allowed': !canSubmit }"
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-l from-indigo-600 to-purple-600 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition">
                            <i class="fas fa-paper-plane ml-1"></i>نشر الآن
                        </button>
                        <button @click="schedulePost()" x-show="!requiresApproval && scheduleEnabled"
                                :disabled="!canSubmit" :class="{ 'opacity-50 cursor-not-allowed': !canSubmit }"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                            <i class="far fa-clock ml-1"></i>جدولة المنشور
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Assistant Slide-over --}}
    <div x-show="showAIAssistant" x-transition:enter="ease-out duration-300"
         class="fixed inset-y-0 left-0 w-96 bg-white shadow-2xl z-60 flex flex-col" dir="rtl">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gradient-to-l from-blue-600 to-purple-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-magic ml-2"></i>مساعد الذكاء الاصطناعي</h3>
            <button @click="showAIAssistant = false" class="text-white/80 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            {{-- Brand Voice Selection --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">صوت العلامة التجارية</label>
                <select x-model="aiSettings.brandVoice" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">الافتراضي</option>
                    <template x-for="voice in brandVoices" :key="voice.voice_id">
                        <option :value="voice.voice_id" x-text="voice.name"></option>
                    </template>
                </select>
            </div>

            {{-- Tone --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">النبرة</label>
                <div class="grid grid-cols-2 gap-2">
                    <button @click="aiSettings.tone = 'professional'"
                            :class="aiSettings.tone === 'professional' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="px-3 py-2 text-sm rounded-lg border transition">احترافي</button>
                    <button @click="aiSettings.tone = 'friendly'"
                            :class="aiSettings.tone === 'friendly' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="px-3 py-2 text-sm rounded-lg border transition">ودود</button>
                    <button @click="aiSettings.tone = 'casual'"
                            :class="aiSettings.tone === 'casual' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="px-3 py-2 text-sm rounded-lg border transition">عفوي</button>
                    <button @click="aiSettings.tone = 'formal'"
                            :class="aiSettings.tone === 'formal' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="px-3 py-2 text-sm rounded-lg border transition">رسمي</button>
                    <button @click="aiSettings.tone = 'humorous'"
                            :class="aiSettings.tone === 'humorous' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="px-3 py-2 text-sm rounded-lg border transition">فكاهي</button>
                    <button @click="aiSettings.tone = 'inspirational'"
                            :class="aiSettings.tone === 'inspirational' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="px-3 py-2 text-sm rounded-lg border transition">ملهم</button>
                </div>
            </div>

            {{-- Length --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">الطول</label>
                <div class="flex gap-2">
                    <button @click="aiSettings.length = 'shorter'"
                            :class="aiSettings.length === 'shorter' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="flex-1 px-3 py-2 text-sm rounded-lg border transition">أقصر</button>
                    <button @click="aiSettings.length = 'same'"
                            :class="aiSettings.length === 'same' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="flex-1 px-3 py-2 text-sm rounded-lg border transition">نفس الطول</button>
                    <button @click="aiSettings.length = 'longer'"
                            :class="aiSettings.length === 'longer' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="flex-1 px-3 py-2 text-sm rounded-lg border transition">أطول</button>
                </div>
            </div>

            {{-- AI Prompt --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تعليمات مخصصة</label>
                <textarea x-model="aiSettings.prompt" rows="3" placeholder="أضف أي تعليمات محددة..."
                          class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"></textarea>
            </div>

            <button @click="generateWithAI()" :disabled="isGenerating"
                    class="w-full px-4 py-2 bg-gradient-to-l from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition font-medium">
                <span x-show="!isGenerating"><i class="fas fa-magic ml-2"></i>إنشاء المحتوى</span>
                <span x-show="isGenerating"><i class="fas fa-spinner fa-spin ml-2"></i>جاري الإنشاء...</span>
            </button>

            {{-- AI Suggestions --}}
            <template x-if="aiSuggestions.length > 0">
                <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-700">الاقتراحات</h4>
                    <template x-for="(suggestion, index) in aiSuggestions" :key="index">
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-300 transition cursor-pointer"
                             @click="useSuggestion(suggestion)">
                            <p class="text-sm text-gray-700" x-text="suggestion"></p>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function publishModal() {
    return {
        open: false,
        editMode: false,
        editPostId: null,

        // Profile Groups Selection (Step 1)
        profileGroups: [],
        selectedGroupIds: [], // Multi-select groups

        // Profile Selection (Step 2)
        selectedProfiles: [],
        profileSearch: '',
        platformFilter: null,
        availablePlatforms: ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok'],

        // Content
        composerTab: 'global',
        content: {
            global: {
                text: '',
                media: [],
                link: '',
                labels: [],
            },
            platforms: {
                instagram: { text: '', post_type: 'feed', first_comment: '' },
                facebook: { text: '' },
                twitter: { text: '', reply_settings: 'everyone' },
                linkedin: { text: '' },
                tiktok: { text: '' },
            }
        },
        newLabel: '',
        isDragging: false,

        // Scheduling
        scheduleEnabled: false,
        schedule: {
            date: '',
            time: '',
            timezone: 'UTC'
        },

        // Preview
        previewPlatform: 'instagram',

        // AI Assistant
        showAIAssistant: false,
        brandVoices: [],
        aiSettings: {
            brandVoice: '',
            tone: 'professional',
            length: 'same',
            prompt: ''
        },
        isGenerating: false,
        aiSuggestions: [],

        // Brand Safety
        brandSafetyStatus: 'pass',
        brandSafetyIssues: [],

        // Approval
        requiresApproval: false,

        // Character limits
        characterLimits: {
            twitter: 280,
            instagram: 2200,
            facebook: 63206,
            linkedin: 3000,
            tiktok: 2200
        },

        init() {
            this.loadProfileGroups();
            this.loadBrandVoices();

            // Listen for open modal event
            window.addEventListener('open-publish-modal', (event) => {
                this.open = true;
                if (event.detail?.postId) {
                    this.editMode = true;
                    this.editPostId = event.detail.postId;
                    this.loadPost(event.detail.postId);
                } else if (event.detail?.content) {
                    // Pre-fill content for duplicate post
                    this.content.global.text = event.detail.content;
                }
            });
        },

        async loadProfileGroups() {
            try {
                console.log('Loading profile groups for org:', window.currentOrgId);
                // Use web route for session auth (not API route)
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/profile-groups`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                console.log('Profile groups response status:', response.status);
                if (response.ok) {
                    const result = await response.json();
                    console.log('Profile groups loaded:', result);
                    this.profileGroups = result.data || result;
                } else {
                    const error = await response.text();
                    console.error('Profile groups error:', error);
                }
            } catch (e) {
                console.error('Failed to load profile groups', e);
            }
        },

        async loadBrandVoices() {
            try {
                // Use web route for session auth
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/brand-voices`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    const result = await response.json();
                    this.brandVoices = result.data || result;
                }
            } catch (e) {
                console.error('Failed to load brand voices', e);
            }
        },

        // Filter profile groups based on selected group IDs
        get filteredProfileGroups() {
            // If no groups selected, show nothing (must select groups first)
            if (this.selectedGroupIds.length === 0) {
                return [];
            }

            return this.profileGroups
                .filter(group => this.selectedGroupIds.includes(group.group_id))
                .map(group => ({
                    ...group,
                    profiles: group.profiles?.filter(p =>
                        (!this.platformFilter || p.platform === this.platformFilter) &&
                        (!this.profileSearch || p.account_name.toLowerCase().includes(this.profileSearch.toLowerCase()))
                    ) || []
                })).filter(g => g.profiles.length > 0);
        },

        get canSubmit() {
            return this.selectedProfiles.length > 0 &&
                   (this.content.global.text.trim() || this.content.global.media.length > 0);
        },

        isProfileSelected(id) {
            return this.selectedProfiles.some(p => p.integration_id === id);
        },

        toggleProfile(profile) {
            const index = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
            if (index >= 0) {
                this.selectedProfiles.splice(index, 1);
            } else {
                this.selectedProfiles.push(profile);
            }
        },

        // ============================================
        // STEP 1: Profile Group Selection Functions
        // ============================================

        // Toggle a group ID in the selection
        toggleGroupId(groupId) {
            const index = this.selectedGroupIds.indexOf(groupId);
            if (index >= 0) {
                this.selectedGroupIds.splice(index, 1);
                // Also remove profiles from this group
                const group = this.profileGroups.find(g => g.group_id === groupId);
                if (group?.profiles) {
                    group.profiles.forEach(profile => {
                        const profileIndex = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
                        if (profileIndex >= 0) {
                            this.selectedProfiles.splice(profileIndex, 1);
                        }
                    });
                }
            } else {
                this.selectedGroupIds.push(groupId);
            }
        },

        // Select all groups
        selectAllGroups() {
            this.selectedGroupIds = this.profileGroups.map(g => g.group_id);
        },

        // Clear all selected groups and profiles
        clearSelectedGroups() {
            this.selectedGroupIds = [];
            this.selectedProfiles = [];
        },

        // ============================================
        // STEP 2: Profile Selection Functions
        // ============================================

        selectAllProfiles() {
            this.filteredProfileGroups.forEach(group => {
                group.profiles.forEach(profile => {
                    if (!this.isProfileSelected(profile.integration_id)) {
                        this.selectedProfiles.push(profile);
                    }
                });
            });
        },

        clearSelectedProfiles() {
            this.selectedProfiles = [];
        },

        // Check if all profiles in a group are selected
        isGroupFullySelected(group) {
            if (!group.profiles || group.profiles.length === 0) return false;
            return group.profiles.every(p => this.isProfileSelected(p.integration_id));
        },

        // Check if any profile in a group is selected
        isGroupPartiallySelected(group) {
            if (!group.profiles || group.profiles.length === 0) return false;
            const selectedCount = group.profiles.filter(p => this.isProfileSelected(p.integration_id)).length;
            return selectedCount > 0 && selectedCount < group.profiles.length;
        },

        // Toggle all profiles in a group
        toggleGroupSelection(group) {
            if (!group.profiles) return;

            if (this.isGroupFullySelected(group)) {
                // Deselect all profiles in this group
                group.profiles.forEach(profile => {
                    const index = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
                    if (index >= 0) {
                        this.selectedProfiles.splice(index, 1);
                    }
                });
            } else {
                // Select all profiles in this group
                group.profiles.forEach(profile => {
                    if (!this.isProfileSelected(profile.integration_id)) {
                        this.selectedProfiles.push(profile);
                    }
                });
            }
        },

        getSelectedPlatforms() {
            return [...new Set(this.selectedProfiles.map(p => p.platform))];
        },

        getPlatformIcon(platform) {
            const icons = {
                facebook: 'fab fa-facebook',
                instagram: 'fab fa-instagram',
                twitter: 'fab fa-twitter',
                linkedin: 'fab fa-linkedin',
                tiktok: 'fab fa-tiktok'
            };
            return icons[platform] || 'fas fa-globe';
        },

        getPlatformBgClass(platform) {
            const classes = {
                facebook: 'bg-blue-600',
                instagram: 'bg-pink-500',
                twitter: 'bg-sky-500',
                linkedin: 'bg-blue-700',
                tiktok: 'bg-gray-900'
            };
            return classes[platform] || 'bg-gray-500';
        },

        getDefaultAvatar(profile) {
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(profile.account_name || 'U')}&background=6366f1&color=fff`;
        },

        updateCharacterCounts() {
            this.checkBrandSafety();
        },

        getCharacterCount(platform) {
            const text = this.content.platforms[platform]?.text || this.content.global.text;
            const limit = this.characterLimits[platform] || 2200;
            return `${text.length}/${limit}`;
        },

        getCharacterCountClass(platform) {
            const text = this.content.platforms[platform]?.text || this.content.global.text;
            const limit = this.characterLimits[platform] || 2200;
            if (text.length > limit) return 'text-red-600';
            if (text.length > limit * 0.9) return 'text-yellow-600';
            return 'text-gray-500';
        },

        handleMediaUpload(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.content.global.media.push({
                        file: file,
                        type: file.type.startsWith('video') ? 'video' : 'image',
                        preview_url: e.target.result
                    });
                };
                reader.readAsDataURL(file);
            });
        },

        handleMediaDrop(event) {
            this.isDragging = false;
            const files = Array.from(event.dataTransfer.files);
            files.forEach(file => {
                if (file.type.startsWith('image') || file.type.startsWith('video')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.content.global.media.push({
                            file: file,
                            type: file.type.startsWith('video') ? 'video' : 'image',
                            preview_url: e.target.result
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        removeMedia(index) {
            this.content.global.media.splice(index, 1);
        },

        addLabel() {
            if (this.newLabel.trim() && !this.content.global.labels.includes(this.newLabel.trim())) {
                this.content.global.labels.push(this.newLabel.trim());
                this.newLabel = '';
            }
        },

        removeLabel(label) {
            this.content.global.labels = this.content.global.labels.filter(l => l !== label);
        },

        getPreviewProfile() {
            return this.selectedProfiles.find(p => p.platform === this.previewPlatform) || this.selectedProfiles[0];
        },

        getPreviewContent() {
            return this.content.platforms[this.previewPlatform]?.text || this.content.global.text || 'Your post content will appear here...';
        },

        getPreviewTime() {
            if (this.scheduleEnabled && this.schedule.date && this.schedule.time) {
                return `Scheduled: ${this.schedule.date} ${this.schedule.time}`;
            }
            return 'Just now';
        },

        checkBrandSafety() {
            // Simulated brand safety check
            this.brandSafetyIssues = [];
            const text = this.content.global.text.toLowerCase();

            // Add real brand safety checks based on selected profile group's policy
            this.brandSafetyStatus = this.brandSafetyIssues.length === 0 ? 'pass' : 'fail';
        },

        async generateWithAI() {
            this.isGenerating = true;
            try {
                // Call AI generation API
                const response = await fetch('/api/ai/generate-content', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        original_text: this.content.global.text,
                        brand_voice_id: this.aiSettings.brandVoice,
                        tone: this.aiSettings.tone,
                        length: this.aiSettings.length,
                        instructions: this.aiSettings.prompt
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    this.aiSuggestions = data.suggestions || [];
                }
            } catch (e) {
                console.error('AI generation failed', e);
            } finally {
                this.isGenerating = false;
            }
        },

        useSuggestion(suggestion) {
            this.content.global.text = suggestion;
            this.showAIAssistant = false;
        },

        async saveDraft() {
            try {
                const response = await fetch(`/api/orgs/${window.currentOrgId}/publish-modal/save-draft`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.scheduleEnabled ? this.schedule : null,
                        is_draft: true
                    })
                });
                if (response.ok) {
                    window.notify('Draft saved successfully', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to save draft', 'error');
                }
            } catch (e) {
                console.error('Failed to save draft', e);
                window.notify('Failed to save draft', 'error');
            }
        },

        async publishNow() {
            if (!this.canSubmit) return;
            try {
                const response = await fetch(`/api/orgs/${window.currentOrgId}/publish-modal/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        is_draft: false
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    window.notify(data.message || 'Post created successfully', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to create post', 'error');
                }
            } catch (e) {
                console.error('Failed to publish', e);
                window.notify('Failed to publish post', 'error');
            }
        },

        async schedulePost() {
            if (!this.canSubmit) return;
            try {
                const response = await fetch(`/api/orgs/${window.currentOrgId}/publish-modal/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.schedule,
                        is_draft: false
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    window.notify(data.message || 'Post scheduled successfully', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to schedule post', 'error');
                }
            } catch (e) {
                console.error('Failed to schedule', e);
                window.notify('Failed to schedule post', 'error');
            }
        },

        async submitForApproval() {
            if (!this.canSubmit) return;
            try {
                // For now, treat as draft with pending approval status
                const response = await fetch(`/api/orgs/${window.currentOrgId}/publish-modal/save-draft`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.scheduleEnabled ? this.schedule : null,
                        is_draft: true,
                        requires_approval: true
                    })
                });
                if (response.ok) {
                    window.notify('Post submitted for approval', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to submit for approval', 'error');
                }
            } catch (e) {
                console.error('Failed to submit for approval', e);
                window.notify('Failed to submit for approval', 'error');
            }
        },

        closeModal() {
            this.open = false;
            this.resetForm();
        },

        resetForm() {
            this.editMode = false;
            this.editPostId = null;
            this.selectedProfiles = [];
            this.content = {
                global: { text: '', media: [], link: '', labels: [] },
                platforms: {
                    instagram: { text: '', post_type: 'feed', first_comment: '' },
                    facebook: { text: '' },
                    twitter: { text: '', reply_settings: 'everyone' },
                    linkedin: { text: '' },
                    tiktok: { text: '' }
                }
            };
            this.scheduleEnabled = false;
            this.schedule = { date: '', time: '', timezone: 'UTC' };
            this.composerTab = 'global';
            this.aiSuggestions = [];
        }
    };
}
</script>
