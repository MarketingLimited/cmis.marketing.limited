@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<h2>{{ __('integrations.integration_details') }}</h2>
<p>{{ __('integrations.integration_details_description') }}</p>

<!-- {{ __('integrations.basic_section') }} -->
<div style="margin:20px 0; padding:20px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px;">
  <h3 style="color:#1e293b;">{{ __('integrations.integration_info') }}</h3>
  <table style="width:100%; border-collapse:collapse; margin-top:10px;">
    <tr><td style="padding:8px; font-weight:bold;">{{ __('integrations.name') }}:</td><td id="intName">Meta Business</td></tr>
    <tr><td style="padding:8px; font-weight:bold;">{{ __('integrations.type') }}:</td><td id="intType">{{ __('integrations.ad_platform') }}</td></tr>
    <tr><td style="padding:8px; font-weight:bold;">{{ __('integrations.status') }}:</td><td id="intStatus">{{ __('integrations.active') }} ✅</td></tr>
    <tr><td style="padding:8px; font-weight:bold;">{{ __('integrations.created_date') }}:</td><td id="intCreated">2025-09-20</td></tr>
    <tr><td style="padding:8px; font-weight:bold;">{{ __('integrations.last_updated') }}:</td><td id="intUpdated">2025-10-25</td></tr>
  </table>
</div>

<!-- {{ __('integrations.connection_keys') }} -->
<div style="margin:20px 0; padding:20px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:10px;">
  <h3 style="color:#1e293b;">{{ __('integrations.connection_keys') }}</h3>
  <p>{{ __('integrations.connection_keys_description') }}</p>
  <div style="margin-top:10px;">
    <div style="margin-bottom:8px;">
      <strong>{{ __('integrations.app_id') }}:</strong> <span id="appId">•••••••••••••••</span>
    </div>
    <div style="margin-bottom:8px;">
      <strong>{{ __('integrations.app_secret') }}:</strong> <span id="appSecret">•••••••••••••••</span>
    </div>
    <div style="margin-bottom:8px;">
      <strong>{{ __('integrations.access_token') }}:</strong> <span id="accessToken">•••••••••••••••</span>
    </div>
    <button id="toggleKeys" style="margin-top:10px; background:#1e293b; color:white; padding:8px 14px; border:none; border-radius:6px; cursor:pointer;">{{ __('integrations.show_keys') }}</button>
  </div>
</div>

<!-- {{ __('integrations.activity_log') }} -->
<div style="margin:20px 0; padding:20px; background:#ffffff; border:1px solid #cbd5e1; border-radius:10px;">
  <h3 style="color:#1e293b;">{{ __('integrations.activity_log') }}</h3>
  <table style="width:100%; border-collapse:collapse; margin-top:10px;">
    <thead>
      <tr style="background:#e2e8f0;">
        <th style="padding:8px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">{{ __('integrations.date') }}</th>
        <th style="padding:8px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">{{ __('integrations.operation') }}</th>
        <th style="padding:8px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">{{ __('integrations.result') }}</th>
      </tr>
    </thead>
    <tbody id="activityLog">
      <tr><td style="padding:8px;">2025-10-25</td><td>{{ __('integrations.update_campaign') }}</td><td>{{ __('integrations.success') }} ✅</td></tr>
      <tr><td style="padding:8px;">2025-10-24</td><td>{{ __('integrations.refresh_token') }}</td><td>{{ __('integrations.success') }} ✅</td></tr>
      <tr><td style="padding:8px;">2025-10-23</td><td>{{ __('integrations.import_data') }}</td><td>{{ __('integrations.warning_delay') }} ⚠️</td></tr>
    </tbody>
  </table>
</div>

<!-- {{ __('integrations.control_buttons') }} -->
<div style="display:flex; gap:10px; margin-top:20px;">
  <a href="{{ route('orgs.settings.integrations', ['org' => $currentOrg]) }}" style="background:#475569; color:white; padding:10px 15px; border-radius:6px; text-decoration:none;">{{ __('integrations.back') }}</a>
  <button style="background:#10b981; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer;">{{ __('integrations.update_connection') }}</button>
  <button style="background:#ef4444; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer;">{{ __('integrations.delete_integration') }}</button>
</div>

<script>
  const toggleBtn = document.getElementById('toggleKeys');
  const appId = document.getElementById('appId');
  const appSecret = document.getElementById('appSecret');
  const accessToken = document.getElementById('accessToken');
  let visible = false;

  toggleBtn.addEventListener('click', () => {
    visible = !visible;
    if (visible) {
      appId.textContent = '1234567890';
      appSecret.textContent = 'ABCD-EFGH-IJKL-MNOP';
      accessToken.textContent = 'EAAG8gZ1234TOKEN';
      toggleBtn.textContent = '{{ __('integrations.hide_keys') }}';
    } else {
      appId.textContent = appSecret.textContent = accessToken.textContent = '•••••••••••••••';
      toggleBtn.textContent = '{{ __('integrations.show_keys') }}';
    }
  });
</script>
@endsection
