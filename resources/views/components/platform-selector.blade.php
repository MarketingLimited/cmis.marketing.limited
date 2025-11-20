{{--
Platform Selector Component

Usage:
<x-platform-selector
    feature-category="paid_campaigns"
    :selected="$selectedPlatform"
    @platform-selected="handlePlatformSelected"
/>
--}}

@props([
    'featureCategory' => 'paid_campaigns',
    'selected' => null,
    'multiple' => false,
    'showComingSoon' => true,
])

<div
    x-data="platformSelector(@js($featureCategory), @js($selected), @js($multiple))"
    x-init="init()"
    {{ $attributes->merge(['class' => 'platform-selector']) }}
>
    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center p-8">
        <div class="loading-spinner"></div>
        <span class="mr-3 text-gray-600">Loading platforms...</span>
    </div>

    <!-- Error State -->
    <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
        <p class="text-red-800" x-text="error"></p>
        <button @click="loadPlatforms()" class="mt-2 text-red-600 hover:text-red-800 underline">
            Retry
        </button>
    </div>

    <!-- Platform Grid -->
    <div x-show="!loading && !error" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="platform in platforms" :key="platform.key">
            <div
                class="platform-card"
                :class="{
                    'available': platform.enabled && isFeatureEnabled(platform),
                    'disabled': !platform.enabled || !isFeatureEnabled(platform),
                    'selected': isSelected(platform.key)
                }"
                @click="selectPlatform(platform)"
            >
                <!-- Platform Icon/Logo -->
                <div class="platform-icon" :class="platform.key">
                    <span x-text="getPlatformEmoji(platform.key)" class="text-3xl"></span>
                </div>

                <!-- Platform Name -->
                <h3 class="text-lg font-semibold mt-3" x-text="platform.display_name"></h3>

                <!-- Feature Badges -->
                <div class="feature-badges mt-2" x-show="platform.enabled && isFeatureEnabled(platform)">
                    <template x-for="[key, feature] in Object.entries(platform.features)" :key="key">
                        <span
                            x-show="feature.enabled"
                            class="badge badge-success text-xs"
                            x-text="feature.label"
                        ></span>
                    </template>
                </div>

                <!-- Coming Soon Badge -->
                <div x-show="!platform.enabled || !isFeatureEnabled(platform)" class="coming-soon-overlay">
                    <span class="coming-soon">قريباً / Coming Soon</span>
                </div>

                <!-- Selected Indicator -->
                <div x-show="isSelected(platform.key)" class="absolute top-2 left-2">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </template>
    </div>

    <!-- No Platforms Available Message -->
    <div x-show="!loading && !error && platforms.length === 0" class="text-center p-8 bg-gray-50 rounded-lg">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No platforms available</h3>
        <p class="mt-1 text-sm text-gray-500">Contact your administrator to enable platforms.</p>
    </div>
</div>

<script>
function platformSelector(featureCategory, initialSelected, multiple) {
    return {
        featureCategory: featureCategory,
        platforms: [],
        selected: multiple ? (Array.isArray(initialSelected) ? initialSelected : []) : initialSelected,
        multiple: multiple,
        loading: true,
        error: null,

        async init() {
            await this.loadPlatforms();
        },

        async loadPlatforms() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/api/features/available-platforms');

                if (!response.ok) {
                    throw new Error('Failed to load platforms');
                }

                const data = await response.json();
                this.platforms = data.platforms;

            } catch (err) {
                this.error = 'Failed to load platforms. Please try again.';
                console.error('Platform loading error:', err);
            } finally {
                this.loading = false;
            }
        },

        isFeatureEnabled(platform) {
            const feature = platform.features[this.featureCategory];
            return feature && feature.enabled;
        },

        isSelected(platformKey) {
            if (this.multiple) {
                return this.selected.includes(platformKey);
            }
            return this.selected === platformKey;
        },

        selectPlatform(platform) {
            // Only allow selection if platform and feature are enabled
            if (!platform.enabled || !this.isFeatureEnabled(platform)) {
                return;
            }

            if (this.multiple) {
                // Toggle selection for multiple mode
                const index = this.selected.indexOf(platform.key);
                if (index > -1) {
                    this.selected.splice(index, 1);
                } else {
                    this.selected.push(platform.key);
                }
            } else {
                // Single selection mode
                this.selected = platform.key;
            }

            // Emit event
            this.$dispatch('platform-selected', {
                platform: platform.key,
                selected: this.selected
            });
        },

        getPlatformEmoji(platform) {
            const emojis = {
                'meta': '📘',
                'google': '🔍',
                'tiktok': '🎵',
                'linkedin': '💼',
                'twitter': '🐦',
                'snapchat': '👻'
            };
            return emojis[platform] || '🌐';
        }
    }
}
</script>

<style scoped>
.platform-card {
    position: relative;
    padding: 20px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.3s ease;
    background-color: #ffffff;
    text-align: center;
    cursor: pointer;
}

.platform-card.available {
    border-color: #10b981;
}

.platform-card.available:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.platform-card.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.platform-card.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.platform-icon {
    margin: 0 auto;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background-color: #f3f4f6;
}

.coming-soon-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-center;
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 8px;
}

.coming-soon {
    background-color: #fbbf24;
    color: #78350f;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 12px;
}

.feature-badges {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 4px;
}
</style>
