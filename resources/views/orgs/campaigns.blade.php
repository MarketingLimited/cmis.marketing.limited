@extends('layouts.admin')

@section('content')
<h2>📊 {{ __('organizations.organization_campaigns') }}</h2>

<form id="compareForm" action="{{ url('orgs/' . $id . '/campaigns/compare') }}" method="get">
<table>
    <thead>
        <tr>
            <th>{{ __('organizations.select') }}</th>
            <th>{{ __('organizations.campaign_name') }}</th>
            <th>{{ __('organizations.objective') }}</th>
            <th>{{ __('organizations.status') }}</th>
            <th>{{ __('organizations.start_date') }}</th>
            <th>{{ __('organizations.end_date') }}</th>
            <th>{{ __('organizations.budget') }}</th>
            <th>{{ __('organizations.currency') }}</th>
            <th>{{ __('organizations.view_details') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($campaigns as $c)
        <tr>
            <td><input type="checkbox" name="campaign_ids[]" value="{{ $c->campaign_id }}"></td>
            <td>{{ $c->name }}</td>
            <td>{{ $c->objective ?? __('organizations.not_specified') }}</td>
            <td>{{ $c->status ?? __('organizations.unknown') }}</td>
            <td>{{ $c->start_date ?? '-' }}</td>
            <td>{{ $c->end_date ?? '-' }}</td>
            <td>{{ $c->budget ? number_format($c->budget, 2) : '-' }}</td>
            <td>{{ $c->currency ?? '-' }}</td>
            <td>
                <a href="/campaigns/{{ $c->campaign_id }}" class="button">{{ __('organizations.view') }}</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="9">{{ __('organizations.no_campaigns_message') }}</td></tr>
        @endforelse
    </tbody>
</table>

<button type="submit">{{ __('organizations.compare_selected_campaigns') }}</button>
</form>

<script>
  document.getElementById('compareForm').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('input[name="campaign_ids[]"]:checked');
    if (checked.length < 2) {
      e.preventDefault();
      alert('{{ __('organizations.select_two_campaigns_compare') }}');
    }
  });
</script>
@endsection