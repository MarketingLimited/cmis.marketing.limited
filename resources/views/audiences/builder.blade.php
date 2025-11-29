@extends('layouts.admin')

@section('title', __('audiences.builder_title'))

@section('content')
<div class="p-6" x-data="audienceBuilder()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('orgs.audiences.index', ['org' => $orgModel->org_id]) }}"
               class="p-2 hover:bg-slate-700 rounded-lg transition-colors text-slate-400 hover:text-white">
                <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">{{ __('audiences.builder_title') }}</h1>
                <p class="text-slate-400 mt-1">{{ __('audiences.builder_description') }}</p>
            </div>
        </div>
        <button @click="saveAudience()"
                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-blue-500/25">
            <i class="fas fa-save"></i>
            {{ __('common.save') }}
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Rules Panel -->
        <div class="lg:col-span-3 space-y-4">
            <!-- Platform & Name -->
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">{{ __('audiences.name') }}</label>
                        <input type="text" x-model="audienceName"
                               placeholder="{{ __('audiences.name_placeholder') }}"
                               class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">{{ __('audiences.select_platform') }}</label>
                        <select x-model="selectedPlatform"
                                class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                            <option value="">{{ __('audiences.select_platform') }}</option>
                            @foreach($platforms as $key => $platform)
                                @if($platform['connected'])
                                    <option value="{{ $key }}">{{ $platform['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Rule Groups -->
            <template x-for="(group, groupIndex) in ruleGroups" :key="groupIndex">
                <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                    <!-- Group Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium"
                                  :class="group.type === 'include' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'">
                                <span x-text="group.type === 'include' ? '{{ __('audiences.include') }}' : '{{ __('audiences.exclude') }}'"></span>
                            </span>
                            <select x-model="group.match"
                                    class="bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-1 text-sm text-white focus:outline-none focus:border-blue-500">
                                <option value="all">{{ __('audiences.match_all') }}</option>
                                <option value="any">{{ __('audiences.match_any') }}</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="toggleGroupType(groupIndex)"
                                    class="p-2 hover:bg-slate-700 rounded-lg transition-colors text-slate-400 hover:text-white">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <button @click="removeGroup(groupIndex)"
                                    class="p-2 hover:bg-red-500/20 rounded-lg transition-colors text-slate-400 hover:text-red-400"
                                    x-show="ruleGroups.length > 1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Rules -->
                    <div class="space-y-3">
                        <template x-for="(rule, ruleIndex) in group.rules" :key="ruleIndex">
                            <div class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-lg">
                                <select x-model="rule.type"
                                        class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                                    <option value="website">{{ __('audiences.website_visitors') }}</option>
                                    <option value="app">{{ __('audiences.app_activity') }}</option>
                                    <option value="customer">{{ __('audiences.customer_list') }}</option>
                                    <option value="engagement">{{ __('audiences.engagement') }}</option>
                                    <option value="purchase">{{ __('audiences.purchase_behavior') }}</option>
                                </select>

                                <select x-model="rule.operator"
                                        class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                                    <option value="visited">Visited</option>
                                    <option value="not_visited">Did not visit</option>
                                    <option value="contains">Contains</option>
                                    <option value="equals">Equals</option>
                                </select>

                                <input type="text" x-model="rule.value"
                                       placeholder="Value"
                                       class="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">

                                <select x-model="rule.period"
                                        class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                                    <option value="7">{{ __('audiences.last_7_days') }}</option>
                                    <option value="14">{{ __('audiences.last_14_days') }}</option>
                                    <option value="30">{{ __('audiences.last_30_days') }}</option>
                                    <option value="60">{{ __('audiences.last_60_days') }}</option>
                                    <option value="90">{{ __('audiences.last_90_days') }}</option>
                                    <option value="180">{{ __('audiences.last_180_days') }}</option>
                                </select>

                                <button @click="removeRule(groupIndex, ruleIndex)"
                                        class="p-2 hover:bg-red-500/20 rounded-lg transition-colors text-slate-400 hover:text-red-400"
                                        x-show="group.rules.length > 1">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Add Rule Button -->
                    <button @click="addRule(groupIndex)"
                            class="mt-3 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        {{ __('audiences.add_rule') }}
                    </button>
                </div>
            </template>

            <!-- Add Group Button -->
            <button @click="addGroup()"
                    class="w-full p-4 border-2 border-dashed border-slate-700 hover:border-slate-600 rounded-xl text-slate-400 hover:text-white transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-plus"></i>
                {{ __('audiences.add_rule_group') }}
            </button>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Audience Size Estimate -->
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">{{ __('audiences.estimated_reach') }}</h3>
                <div class="text-center">
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500/20 to-purple-500/20 flex items-center justify-center mx-auto mb-3 relative">
                        <div class="absolute inset-0 rounded-full border-4 border-blue-500/30"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-blue-500 border-t-transparent animate-spin" x-show="calculating"></div>
                        <i class="fas fa-users text-3xl text-blue-400" x-show="!calculating"></i>
                    </div>
                    <p class="text-3xl font-bold text-white" x-text="estimatedSize">--</p>
                    <p class="text-xs text-slate-400 mt-1">{{ __('audiences.audience_size') }}</p>
                </div>
            </div>

            <!-- Quick Add -->
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">{{ __('audiences.rule_type') }}</h3>
                <div class="space-y-2">
                    <button @click="addPresetRule('website')"
                            class="w-full p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors text-start flex items-center gap-3">
                        <i class="fas fa-globe text-blue-400 w-5"></i>
                        <span class="text-slate-300 text-sm">{{ __('audiences.website_visitors') }}</span>
                    </button>
                    <button @click="addPresetRule('app')"
                            class="w-full p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors text-start flex items-center gap-3">
                        <i class="fas fa-mobile-alt text-green-400 w-5"></i>
                        <span class="text-slate-300 text-sm">{{ __('audiences.app_activity') }}</span>
                    </button>
                    <button @click="addPresetRule('customer')"
                            class="w-full p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors text-start flex items-center gap-3">
                        <i class="fas fa-users text-purple-400 w-5"></i>
                        <span class="text-slate-300 text-sm">{{ __('audiences.customer_list') }}</span>
                    </button>
                    <button @click="addPresetRule('engagement')"
                            class="w-full p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors text-start flex items-center gap-3">
                        <i class="fas fa-heart text-red-400 w-5"></i>
                        <span class="text-slate-300 text-sm">{{ __('audiences.engagement') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function audienceBuilder() {
    return {
        audienceName: '',
        selectedPlatform: '',
        estimatedSize: '--',
        calculating: false,
        ruleGroups: [
            {
                type: 'include',
                match: 'all',
                rules: [
                    { type: 'website', operator: 'visited', value: '', period: '30' }
                ]
            }
        ],

        init() {
            this.$watch('ruleGroups', () => this.calculateSize(), { deep: true });
        },

        addGroup() {
            this.ruleGroups.push({
                type: 'include',
                match: 'all',
                rules: [
                    { type: 'website', operator: 'visited', value: '', period: '30' }
                ]
            });
        },

        removeGroup(index) {
            if (this.ruleGroups.length > 1) {
                this.ruleGroups.splice(index, 1);
            }
        },

        toggleGroupType(index) {
            this.ruleGroups[index].type = this.ruleGroups[index].type === 'include' ? 'exclude' : 'include';
        },

        addRule(groupIndex) {
            this.ruleGroups[groupIndex].rules.push({
                type: 'website',
                operator: 'visited',
                value: '',
                period: '30'
            });
        },

        removeRule(groupIndex, ruleIndex) {
            if (this.ruleGroups[groupIndex].rules.length > 1) {
                this.ruleGroups[groupIndex].rules.splice(ruleIndex, 1);
            }
        },

        addPresetRule(type) {
            this.ruleGroups[0].rules.push({
                type: type,
                operator: 'visited',
                value: '',
                period: '30'
            });
        },

        calculateSize() {
            this.calculating = true;
            // Simulate API call
            setTimeout(() => {
                const baseSize = Math.floor(Math.random() * 500000) + 100000;
                this.estimatedSize = this.formatNumber(baseSize);
                this.calculating = false;
            }, 500);
        },

        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(0) + 'K';
            return num.toString();
        },

        saveAudience() {
            // Submit form data
            const formData = {
                name: this.audienceName,
                platform: this.selectedPlatform,
                audience_type: 'custom',
                rules: this.ruleGroups
            };
            console.log('Saving:', formData);
            // Would POST to backend
        }
    }
}
</script>
@endpush
