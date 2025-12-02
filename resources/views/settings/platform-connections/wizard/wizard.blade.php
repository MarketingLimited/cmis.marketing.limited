@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
    $config = $platformConfig ?? [];
    $displayName = isset($config['display_name']) ? __($config['display_name']) : '';
@endphp

@section('title')
    @if($step === 1)
        {{ __('wizard.mode.title', ['platform' => $displayName]) }} - {{ __('settings.settings') }}
    @elseif($step === 2)
        {{ __('wizard.assets.title') }} - {{ $displayName }}
    @elseif($step === 3)
        {{ __('wizard.success.title', ['platform' => $displayName]) }}
    @endif
@endsection

@section('content')
    @if($step === 1)
        @include('settings.platform-connections.wizard.partials.step-1-content', [
            'currentOrg' => $currentOrg,
            'platform' => $platform,
            'config' => $config,
            'existingConnection' => $existingConnection ?? null,
            'isRtl' => $isRtl,
        ])
    @elseif($step === 2)
        @include('settings.platform-connections.wizard.partials.step-2-content', [
            'currentOrg' => $currentOrg,
            'platform' => $platform,
            'config' => $config,
            'connection' => $connection,
            'assets' => $assets ?? [],
            'smartDefaults' => $smartDefaults ?? [],
            'isRtl' => $isRtl,
        ])
    @elseif($step === 3)
        @include('settings.platform-connections.wizard.partials.step-3-content', [
            'currentOrg' => $currentOrg,
            'platform' => $platform,
            'config' => $config,
            'connection' => $connection,
            'syncedAssets' => $syncedAssets ?? [],
            'isRtl' => $isRtl,
        ])
    @endif
@endsection

@push('scripts')
<script>
function wizardMode() {
    return {
        connecting: false,
        showAdvanced: false,
        submittingManual: false
    }
}

function wizardAssets(assets, smartDefaults) {
    return {
        assets: assets || {},
        smartDefaults: smartDefaults || {},
        selected: {},
        submitting: false,
        showValidationError: false,

        init() {
            this.resetDefaults();
        },

        resetDefaults() {
            this.selected = {};
            for (const [assetType, defaultIds] of Object.entries(this.smartDefaults)) {
                this.selected[assetType] = [...(defaultIds || [])];
            }
        },

        isSelected(assetType, assetId) {
            return this.selected[assetType]?.includes(assetId) || false;
        },

        selectAll(assetType) {
            const assetList = this.assets[assetType] || [];
            this.selected[assetType] = assetList.map(a => a.id || a.platform_id || '');
        },

        deselectAll(assetType) {
            this.selected[assetType] = [];
        },

        getSelectionCount() {
            let count = 0;
            for (const ids of Object.values(this.selected)) {
                count += ids.length;
            }
            return count;
        },

        validateAndSubmit(event) {
            if (this.getSelectionCount() === 0) {
                this.showValidationError = true;
                return;
            }
            this.showValidationError = false;
            this.submitting = true;
            event.target.closest('form').submit();
        }
    }
}
</script>
@endpush

@push('styles')
<style>
@keyframes ping {
    75%, 100% {
        transform: scale(1.5);
        opacity: 0;
    }
}
.animate-ping {
    animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}
</style>
@endpush
