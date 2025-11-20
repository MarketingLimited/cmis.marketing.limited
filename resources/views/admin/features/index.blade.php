<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إدارة الميزات والمنصات - CMIS</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Toggle Switch Styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #10b981;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-gray-50" x-data="featureManager()">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">إدارة الميزات والمنصات</h1>
                        <p class="text-sm text-gray-600 mt-1">تحكم في الميزات المتاحة لكل منصة إعلانية</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span x-show="hasChanges" class="text-amber-600 text-sm font-medium">
                            لديك تغييرات غير محفوظة
                        </span>
                        <button
                            x-show="hasChanges"
                            @click="saveChanges"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            حفظ التغييرات
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Presets Section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">إعدادات سريعة (Presets)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                    <button
                        @click="applyPreset('launch')"
                        class="px-4 py-3 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 transition text-sm font-medium">
                        🚀 إعداد الإطلاق الأولي
                    </button>
                    <button
                        @click="applyPreset('all-scheduling')"
                        class="px-4 py-3 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg hover:bg-purple-100 transition text-sm font-medium">
                        📅 جميع الجدولة
                    </button>
                    <button
                        @click="applyPreset('all-paid')"
                        class="px-4 py-3 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition text-sm font-medium">
                        💰 جميع الحملات الممولة
                    </button>
                    <button
                        @click="applyPreset('full-launch')"
                        class="px-4 py-3 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 transition text-sm font-medium">
                        ✅ تفعيل كل شيء
                    </button>
                    <button
                        @click="applyPreset('disable-all')"
                        class="px-4 py-3 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition text-sm font-medium">
                        ❌ إيقاف كل شيء
                    </button>
                </div>
            </div>

            <!-- Feature Matrix Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider sticky right-0 bg-gray-50">
                                    الميزة
                                </th>
                                @foreach($platforms as $platform)
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex flex-col items-center gap-1">
                                        <span>{{ ucfirst($platform) }}</span>
                                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-lg">
                                            @switch($platform)
                                                @case('meta')
                                                    📘
                                                    @break
                                                @case('google')
                                                    🔍
                                                    @break
                                                @case('tiktok')
                                                    🎵
                                                    @break
                                                @case('linkedin')
                                                    💼
                                                    @break
                                                @case('twitter')
                                                    🐦
                                                    @break
                                                @case('snapchat')
                                                    👻
                                                    @break
                                            @endswitch
                                        </div>
                                    </div>
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($features as $feature)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky right-0 bg-white">
                                    <div class="flex items-center gap-2">
                                        @switch($feature)
                                            @case('scheduling')
                                                <span class="text-2xl">📅</span>
                                                <div>
                                                    <div>جدولة المنشورات</div>
                                                    <div class="text-xs text-gray-500 font-normal">Scheduling</div>
                                                </div>
                                                @break
                                            @case('paid_campaigns')
                                                <span class="text-2xl">💰</span>
                                                <div>
                                                    <div>الحملات الممولة</div>
                                                    <div class="text-xs text-gray-500 font-normal">Paid Campaigns</div>
                                                </div>
                                                @break
                                            @case('analytics')
                                                <span class="text-2xl">📊</span>
                                                <div>
                                                    <div>التحليلات</div>
                                                    <div class="text-xs text-gray-500 font-normal">Analytics</div>
                                                </div>
                                                @break
                                            @case('organic_posts')
                                                <span class="text-2xl">📱</span>
                                                <div>
                                                    <div>المنشورات العضوية</div>
                                                    <div class="text-xs text-gray-500 font-normal">Organic Posts</div>
                                                </div>
                                                @break
                                        @endswitch
                                    </div>
                                </td>
                                @foreach($platforms as $platform)
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <label class="toggle-switch inline-block">
                                        <input
                                            type="checkbox"
                                            :checked="isEnabled('{{ $feature }}', '{{ $platform }}')"
                                            @change="toggleFeature('{{ $feature }}.{{ $platform }}.enabled', $event.target.checked)">
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">المنصات المفعلة</h3>
                    <div class="text-3xl font-bold text-blue-600" x-text="enabledPlatformsCount"></div>
                    <p class="text-xs text-gray-500 mt-1">من أصل 6 منصات</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">الميزات المفعلة</h3>
                    <div class="text-3xl font-bold text-green-600" x-text="enabledFeaturesCount"></div>
                    <p class="text-xs text-gray-500 mt-1">من أصل 24 ميزة</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">التغييرات المعلقة</h3>
                    <div class="text-3xl font-bold text-amber-600" x-text="changes.length"></div>
                    <p class="text-xs text-gray-500 mt-1">في انتظار الحفظ</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function featureManager() {
            return {
                matrix: @json($matrix),
                changes: [],
                hasChanges: false,
                enabledPlatformsCount: 0,
                enabledFeaturesCount: 0,

                init() {
                    this.updateStats();
                },

                isEnabled(feature, platform) {
                    // Check if there's a pending change first
                    const changeKey = `${feature}.${platform}.enabled`;
                    const change = this.changes.find(c => c.key === changeKey);
                    if (change !== undefined) {
                        return change.enabled;
                    }

                    // Otherwise return current value
                    return this.matrix[feature]?.[platform] || false;
                },

                toggleFeature(key, enabled) {
                    const existingIndex = this.changes.findIndex(c => c.key === key);
                    if (existingIndex >= 0) {
                        this.changes[existingIndex].enabled = enabled;
                    } else {
                        this.changes.push({ key, enabled });
                    }
                    this.hasChanges = this.changes.length > 0;
                    this.updateStats();
                },

                async saveChanges() {
                    if (this.changes.length === 0) return;

                    try {
                        const response = await fetch('/admin/features/bulk-toggle', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                features: this.changes
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('✅ تم حفظ التغييرات بنجاح');
                            this.changes = [];
                            this.hasChanges = false;
                            location.reload();
                        } else {
                            alert('❌ فشل حفظ التغييرات: ' + data.message);
                        }
                    } catch (error) {
                        alert('❌ فشل حفظ التغييرات: ' + error.message);
                    }
                },

                async applyPreset(presetName) {
                    const presetNames = {
                        'launch': 'إعداد الإطلاق الأولي',
                        'all-scheduling': 'تفعيل جميع الجدولة',
                        'all-paid': 'تفعيل جميع الحملات الممولة',
                        'full-launch': 'تفعيل كل شيء',
                        'disable-all': 'إيقاف كل شيء'
                    };

                    if (!confirm(`هل تريد تطبيق إعداد "${presetNames[presetName]}"؟`)) {
                        return;
                    }

                    try {
                        const response = await fetch('/admin/features/apply-preset', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ preset: presetName })
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('✅ تم تطبيق الإعداد بنجاح');
                            location.reload();
                        } else {
                            alert('❌ فشل تطبيق الإعداد: ' + data.message);
                        }
                    } catch (error) {
                        alert('❌ فشل تطبيق الإعداد: ' + error.message);
                    }
                },

                updateStats() {
                    // Count enabled platforms (at least one feature enabled)
                    const platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
                    const features = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];

                    this.enabledPlatformsCount = platforms.filter(platform => {
                        return features.some(feature => this.isEnabled(feature, platform));
                    }).length;

                    // Count total enabled features
                    this.enabledFeaturesCount = 0;
                    features.forEach(feature => {
                        platforms.forEach(platform => {
                            if (this.isEnabled(feature, platform)) {
                                this.enabledFeaturesCount++;
                            }
                        });
                    });
                }
            }
        }
    </script>
</body>
</html>
