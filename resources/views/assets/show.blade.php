@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    ASSET DETAIL
    <small>Single source of truth for operational and governance context</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li><a href="{{ route('assets.index') }}">Asset Inventory</a></li>
    <li class="active">{{ $asset->asset_code }}</li>
  </ol>
</section>

<section class="content">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1">{{ $asset->name }}</h4>
      <small class="text-muted">{{ $asset->asset_code }}</small>
    </div>
    <div class="d-flex gap-2">
      @if(Auth::user()->canManageAssetRecords())
        <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        @if($asset->status !== 'retired')
          <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('Retire asset ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">Retire</button>
          </form>
        @endif
      @endif
      @if(Auth::user()->canApproveAssetChanges())
        <a href="{{ route('approvals.index') }}" class="btn btn-outline-warning btn-sm">Approvals</a>
      @endif
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Core Metadata</h5>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Type</small>
              <span class="text-capitalize">{{ $asset->asset_type }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Environment</small>
              <span class="text-uppercase">{{ $asset->environment }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Status</small>
              <span class="badge bg-label-secondary text-capitalize">{{ $asset->status }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Criticality</small>
              <span class="badge bg-label-primary text-capitalize">{{ $asset->criticality }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Lifecycle</small>
              <span class="text-capitalize">{{ $asset->lifecycle_stage }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Source</small>
              <span class="text-uppercase">{{ $asset->source }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">IP / Host / Port</small>
              <span>{{ $asset->ip_address ?: '-' }} / {{ $asset->hostname ?: '-' }} / {{ $asset->port ?: '-' }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Owner</small>
              <span>{{ $asset->owner_name ?: 'Unassigned' }}</span>
              <small class="d-block text-muted">{{ $asset->owner_email ?: '-' }}</small>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Bank / BU</small>
              <span>{{ optional($asset->bank)->nama ?: '-' }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Last Seen</small>
              <span>{{ $asset->last_seen_at ? $asset->last_seen_at->format('Y-m-d H:i:s') : 'Never' }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Updated At</small>
              <span>{{ $asset->updated_at->format('Y-m-d H:i:s') }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Confidence</small>
              <span>{{ $asset->discovery_confidence }}%</span>
            </div>
            <div class="col-12">
              <small class="text-muted d-block">Tags</small>
              <span>{{ $asset->tags ?: '-' }}</span>
            </div>
            <div class="col-12">
              <small class="text-muted d-block">Notes</small>
              <div>{{ $asset->notes ?: '-' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">Risk Posture</h5>
        </div>
        <div class="card-body text-center">
          @php
            $riskClass = $asset->risk_score >= 70 ? 'danger' : ($asset->risk_score >= 40 ? 'warning' : 'success');
          @endphp
          <h1 class="mb-1 text-{{ $riskClass }}">{{ $asset->risk_score }}</h1>
          <small class="text-muted">Calculated dynamic risk score</small>
          <hr>
          <div class="d-flex justify-content-between">
            <span>Criticality</span>
            <span class="text-capitalize fw-semibold">{{ $asset->criticality }}</span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Status</span>
            <span class="text-capitalize fw-semibold">{{ $asset->status }}</span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Discovery Confidence</span>
            <span class="fw-semibold">{{ $asset->discovery_confidence }}%</span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Quick Navigation</h5>
        </div>
        <div class="card-body d-grid gap-2">
          <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm">Back to Inventory</a>
          <a href="{{ route('discovery.index') }}" class="btn btn-outline-primary btn-sm">Open Discovery Center</a>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Open Policy Violations</h5>
          <a href="{{ route('policies.index') }}" class="btn btn-outline-secondary btn-xs">Open Policies</a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Code</th>
                <th>Severity</th>
                <th>Message</th>
              </tr>
            </thead>
            <tbody>
              @forelse($openViolations as $violation)
                @php
                  $sevClass = $violation->severity === 'critical' ? 'danger' : ($violation->severity === 'high' ? 'warning' : 'secondary');
                @endphp
                <tr>
                  <td><code>{{ $violation->policy_code }}</code></td>
                  <td><span class="badge bg-label-{{ $sevClass }} text-capitalize">{{ $violation->severity }}</span></td>
                  <td>{{ $violation->message }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted py-3">No open violations.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Pending Change Requests</h5>
          @if(Auth::user()->canApproveAssetChanges())
            <a href="{{ route('approvals.index') }}" class="btn btn-outline-warning btn-xs">Review Queue</a>
          @endif
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Request</th>
                <th>Type</th>
                <th>Requester</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              @forelse($pendingRequests as $changeRequest)
                <tr>
                  <td>#{{ $changeRequest->id }}</td>
                  <td class="text-capitalize">{{ str_replace('_', ' ', $changeRequest->change_type) }}</td>
                  <td>{{ optional($changeRequest->requester)->name ?: '-' }}</td>
                  <td>{{ $changeRequest->created_at->format('Y-m-d H:i') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-3">No pending requests for this asset.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Activity Log</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Action</th>
                <th>Message</th>
              </tr>
            </thead>
            <tbody>
              @forelse($activityLogs as $log)
                <tr>
                  <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                  <td><span class="badge bg-label-secondary">{{ $log->action }}</span></td>
                  <td>
                    {{ $log->message }}
                    @if($log->context)
                      @php
                        $context = json_decode($log->context, true);
                      @endphp
                      @if(is_array($context) && count($context) > 0)
                        <small class="d-block text-muted">{{ implode(' | ', array_map(function($k, $v) { return $k . ': ' . (is_scalar($v) ? $v : json_encode($v)); }, array_keys($context), $context)) }}</small>
                      @endif
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted py-3">No activity log yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Latest Discovery Findings</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Run</th>
                <th>Status</th>
                <th>Confidence</th>
                <th>Captured At</th>
              </tr>
            </thead>
            <tbody>
              @forelse($latestFindings as $finding)
                @php
                  $findingClass = $finding->finding_status === 'new' ? 'success' : ($finding->finding_status === 'updated' ? 'warning' : 'secondary');
                @endphp
                <tr>
                  <td>
                    <a href="{{ route('discovery.show', $finding->run_id) }}">{{ substr(optional($finding->run)->run_uuid, 0, 8) }}</a>
                  </td>
                  <td><span class="badge bg-label-{{ $findingClass }}">{{ $finding->finding_status }}</span></td>
                  <td>{{ $finding->confidence }}%</td>
                  <td>{{ $finding->created_at->format('Y-m-d H:i') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-3">No discovery findings yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
