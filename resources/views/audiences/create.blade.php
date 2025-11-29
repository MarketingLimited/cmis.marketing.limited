@extends('layouts.admin')

@section('title', __('audiences.create_new_audience'))

@section('content')
<div class="p-6" x-data="audienceForm()">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('orgs.audiences.index', ['org' => $orgModel->org_id]) }}"
           class="p-2 hover:bg-slate-700 rounded-lg transition-colors text-slate-400 hover:text-white">
            <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('audiences.create_new_audience') }}</h1>
            <p class="text-slate-400 mt-1">{{ __('audiences.manage_audiences') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('orgs.audiences.store', ['org' => $orgModel->org_id]) }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">{{ __('audiences.basic_info') }}</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">{{ __('audiences.name') }}</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   placeholder="{{ __('audiences.name_placeholder') }}"
                                   class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                            @error('name')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">{{ __('audiences.description') }}</label>
                            <textarea name="description" rows="3"
                                      placeholder="{{ __('audiences.description_placeholder') }}"
                                      class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Platform Selection -->
                <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">{{ __('audiences.select_platform') }}</h2>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($platforms as $key => $platform)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="platform" value="{{ $key }}" x-model="selectedPlatform"
                                   class="peer sr-only" {{ !$platform['connected'] ? 'disabled' : '' }}>
                            <div class="p-4 rounded-xl border-2 transition-all
                                        {{ $platform['connected'] ? 'border-slate-700 peer-checked:border-blue-500 peer-checked:bg-blue-500/10' : 'border-slate-800 opacity-50' }}">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-{{ $platform['color'] }}-500/20 flex items-center justify-center">
                                        <i class="fab {{ $platform['icon'] }} text-{{ $platform['color'] }}-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium text-sm">{{ $platform['name'] }}</p>
                                        @if($platform['connected'])
                                            <p class="text-green-400 text-xs">{{ __('common.connected') }}</p>
                                        @else
                                            <p class="text-slate-500 text-xs">{{ __('common.not_connected') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    @if(!collect($platforms)->contains('connected', true))
                        <div class="mt-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                <div>
                                    <p class="text-yellow-400 font-medium">{{ __('audiences.connect_platform_first') }}</p>
                                    <a href="{{ route('orgs.settings.platform-connections', ['org' => $orgModel->org_id]) }}"
                                       class="text-sm text-yellow-400/80 hover:text-yellow-300 underline">
                                        {{ __('audiences.go_to_connections') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Audience Type -->
                <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">{{ __('audiences.select_type') }}</h2>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="audience_type" value="custom" x-model="audienceType" class="peer sr-only" checked>
                            <div class="p-4 rounded-xl border-2 border-slate-700 peer-checked:border-blue-500 peer-checked:bg-blue-500/10 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                        <i class="fas fa-users-cog text-blue-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium">{{ __('audiences.custom') }}</p>
                                        <p class="text-slate-400 text-xs">{{ __('audiences.website_visitors') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="audience_type" value="lookalike" x-model="audienceType" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-slate-700 peer-checked:border-purple-500 peer-checked:bg-purple-500/10 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                        <i class="fas fa-user-friends text-purple-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium">{{ __('audiences.lookalike') }}</p>
                                        <p class="text-slate-400 text-xs">{{ __('audiences.similarity') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="audience_type" value="saved" x-model="audienceType" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-slate-700 peer-checked:border-green-500 peer-checked:bg-green-500/10 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                                        <i class="fas fa-bookmark text-green-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium">{{ __('audiences.saved') }}</p>
                                        <p class="text-slate-400 text-xs">{{ __('audiences.demographic') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="audience_type" value="remarketing" x-model="audienceType" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-slate-700 peer-checked:border-orange-500 peer-checked:bg-orange-500/10 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center">
                                        <i class="fas fa-redo text-orange-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium">{{ __('audiences.remarketing') }}</p>
                                        <p class="text-slate-400 text-xs">{{ __('audiences.engagement') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                    <button type="submit"
                            class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-lg transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-500/25">
                        <i class="fas fa-save"></i>
                        {{ __('audiences.create_new_audience') }}
                    </button>

                    <a href="{{ route('orgs.audiences.builder', ['org' => $orgModel->org_id]) }}"
                       class="w-full mt-3 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        {{ __('audiences.builder_title') }}
                    </a>
                </div>

                <!-- Estimated Size -->
                <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-6">
                    <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">{{ __('audiences.estimated_reach') }}</h3>
                    <div class="text-center">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500/20 to-purple-500/20 flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-users text-2xl text-blue-400"></i>
                        </div>
                        <p class="text-2xl font-bold text-white" x-text="estimatedSize">--</p>
                        <p class="text-xs text-slate-400">{{ __('audiences.calculating') }}</p>
                    </div>
                </div>

                <!-- Tips -->
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                    <h3 class="text-blue-400 font-medium mb-2 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i>
                        {{ __('common.tips') }}
                    </h3>
                    <ul class="text-sm text-blue-300/80 space-y-1">
                        <li>{{ __('audiences.higher_similarity') }}</li>
                        <li>{{ __('audiences.lower_similarity') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function audienceForm() {
    return {
        selectedPlatform: '',
        audienceType: 'custom',
        estimatedSize: '--',

        init() {
            this.$watch('selectedPlatform', () => this.updateEstimate());
            this.$watch('audienceType', () => this.updateEstimate());
        },

        updateEstimate() {
            if (this.selectedPlatform) {
                // Simulated estimate - would call API in production
                const estimates = {
                    meta: '2.5M - 5M',
                    google: '1.8M - 3.5M',
                    tiktok: '800K - 1.5M',
                    snapchat: '500K - 1M',
                    twitter: '1M - 2M',
                    linkedin: '300K - 600K'
                };
                this.estimatedSize = estimates[this.selectedPlatform] || '--';
            }
        }
    }
}
</script>
@endpush
