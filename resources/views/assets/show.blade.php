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
  @php
    $assetType = $asset->asset_type;
    if ($assetType === 'server') {
      $assetType = 'application_server';
    } elseif ($assetType === 'database') {
      $assetType = 'database_server';
    } elseif ($assetType === 'network') {
      $assetType = 'network_peripheral';
    } elseif (in_array($assetType, ['endpoint', 'storage', 'other'])) {
      $assetType = 'etc';
    }

    $profile = is_array($asset->asset_profile) ? $asset->asset_profile : [];
    $isServerType = in_array($assetType, ['application_server', 'database_server']);
    $isServiceHostType = $assetType === 'application_server';
    $eolNotifications = [];
    $hasExpiredEol = false;

    if ($asset->status !== 'retired') {
      if ($asset->os_eol_date) {
        $osDaysLeft = now()->startOfDay()->diffInDays($asset->os_eol_date->format('Y-m-d'), false);
        if ($osDaysLeft <= 90) {
          $osExpired = $osDaysLeft < 0;
          $hasExpiredEol = $hasExpiredEol || $osExpired;
          $eolNotifications[] = [
            'target' => 'OS',
            'state' => $osExpired ? 'expired' : 'near',
            'days_left' => $osDaysLeft,
            'eol_date' => $asset->os_eol_date->format('Y-m-d'),
          ];
        }
      }

      if ($assetType === 'database_server' && !empty($profile['db_license_eol_date'])) {
        $licenseTs = strtotime((string) $profile['db_license_eol_date']);
        if ($licenseTs) {
          $licenseDate = date('Y-m-d', $licenseTs);
          $licenseDaysLeft = now()->startOfDay()->diffInDays($licenseDate, false);
          if ($licenseDaysLeft <= 90) {
            $licenseExpired = $licenseDaysLeft < 0;
            $hasExpiredEol = $hasExpiredEol || $licenseExpired;
            $eolNotifications[] = [
              'target' => 'License',
              'state' => $licenseExpired ? 'expired' : 'near',
              'days_left' => $licenseDaysLeft,
              'eol_date' => $licenseDate,
            ];
          }
        }
      }
    }
  @endphp

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

  @if(!empty($eolNotifications))
    <div class="alert alert-{{ $hasExpiredEol ? 'danger' : 'warning' }} mb-3">
      <strong>Notifikasi EOL Asset:</strong>
      <ul class="mb-0 mt-2 ps-3">
        @foreach($eolNotifications as $notif)
          <li>
            {{ $notif['target'] }} {{ strtoupper($notif['state']) }}
            @if($notif['days_left'] < 0)
              {{ abs($notif['days_left']) }} hari lalu
            @else
              dalam {{ $notif['days_left'] }} hari
            @endif
            ({{ $notif['eol_date'] }})
          </li>
        @endforeach
      </ul>
    </div>
  @endif

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
              <span class="text-capitalize">{{ ucwords(str_replace('_', ' ', $assetType)) }}</span>
            </div>
            @if($isServerType)
              <div class="col-12 col-md-4">
                <small class="text-muted d-block">Host Type</small>
                <span class="text-capitalize">{{ $asset->host_type ? str_replace('_', ' ', $asset->host_type) : '-' }}</span>
              </div>
              <div class="col-12 col-md-4">
                <small class="text-muted d-block">Server Role</small>
                <span class="text-capitalize">{{ $asset->server_role ? str_replace('_', ' ', $asset->server_role) : '-' }}</span>
              </div>
            @endif
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Environment</small>
              <span class="text-uppercase">{{ $asset->environment }}</span>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-muted d-block">Server Status</small>
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
            @if($isServerType)
              <div class="col-12 col-md-4">
                <small class="text-muted d-block">Operating System</small>
                <span>{{ $asset->operating_system ?: '-' }}</span>
              </div>
              <div class="col-12 col-md-4">
                <small class="text-muted d-block">OS Version</small>
                <span>{{ $asset->os_version ?: '-' }}</span>
              </div>
              <div class="col-12 col-md-4">
                <small class="text-muted d-block">OS EOL</small>
                @if($asset->os_eol_date)
                  @php
                    $isExpired = $asset->os_eol_date->lt(now()->startOfDay());
                    $isNear = !$isExpired && $asset->os_eol_date->lte(now()->addDays(90)->startOfDay());
                    $eolClass = $isExpired ? 'danger' : ($isNear ? 'warning' : 'success');
                  @endphp
                  <span class="badge bg-label-{{ $eolClass }}">{{ $asset->os_eol_date->format('Y-m-d') }}</span>
                @else
                  <span>-</span>
                @endif
              </div>
            @endif
            @if($assetType !== 'application')
              <div class="col-12 col-md-4">
                <small class="text-muted d-block">IP / Host / Port</small>
                <span>{{ $asset->ip_address ?: '-' }} / {{ $asset->hostname ?: '-' }} / {{ $asset->port ?: '-' }}</span>
              </div>
            @endif
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
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0">Type Specific Detail</h5>
    </div>
    <div class="card-body">
      @if($assetType === 'application')
        <div class="row g-2">
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Runtime / Basis</small>
            <span>{{ $profile['application_runtime'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Jenis Aplikasi</small>
            <span class="text-capitalize">{{ isset($profile['application_category']) ? str_replace('_', ' ', $profile['application_category']) : '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Versi Aplikasi</small>
            <span>{{ $profile['application_version'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Rilis Pertama</small>
            <span>{{ $profile['first_release_date'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Rilis Terakhir</small>
            <span>{{ $profile['last_release_date'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Status Aplikasi</small>
            <span>{{ ($profile['application_status'] ?? '') === 'not_used' ? 'Tidak Digunakan' : (($profile['application_status'] ?? '') === 'used' ? 'Digunakan' : '-') }}</span>
          </div>
        </div>
      @elseif($assetType === 'network_peripheral')
        <div class="row g-2">
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Peripheral Type</small>
            <span class="text-capitalize">{{ isset($profile['peripheral_type']) ? str_replace('_', ' ', $profile['peripheral_type']) : '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Vendor</small>
            <span>{{ $profile['vendor'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Model</small>
            <span>{{ $profile['model'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Serial Number</small>
            <span>{{ $profile['serial_number'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Firmware Version</small>
            <span>{{ $profile['firmware_version'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Firmware EOL</small>
            <span>{{ $profile['firmware_eol_date'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Support Contract End</small>
            <span>{{ $profile['support_contract_end_date'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-8">
            <small class="text-muted d-block">Management Interface</small>
            <span>{{ $profile['management_interface'] ?? '-' }}</span>
          </div>
        </div>
      @elseif($assetType === 'database_server')
        <div class="row g-2">
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">DB License Type</small>
            <span class="text-capitalize">{{ $profile['db_license_type'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">DB License EOL</small>
            @if(!empty($profile['db_license_eol_date']))
              @php
                $licenseTs = strtotime((string) $profile['db_license_eol_date']);
                $licenseBadgeClass = 'success';
                if ($licenseTs) {
                  $licenseDate = date('Y-m-d', $licenseTs);
                  $licenseDaysLeft = now()->startOfDay()->diffInDays($licenseDate, false);
                  if ($licenseDaysLeft < 0) {
                    $licenseBadgeClass = 'danger';
                  } elseif ($licenseDaysLeft <= 90) {
                    $licenseBadgeClass = 'warning';
                  }
                }
              @endphp
              <span class="badge bg-label-{{ $licenseBadgeClass }}">{{ $profile['db_license_eol_date'] }}</span>
            @else
              <span>-</span>
            @endif
          </div>
        </div>
      @elseif($assetType === 'etc')
        <div class="row g-2">
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Endpoint Type</small>
            <span class="text-capitalize">{{ isset($profile['endpoint_type']) ? str_replace('_', ' ', $profile['endpoint_type']) : '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Assigned User</small>
            <span>{{ $profile['assigned_user'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Department</small>
            <span>{{ $profile['department'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Device Serial</small>
            <span>{{ $profile['device_serial_number'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Purchase Date</small>
            <span>{{ $profile['purchase_date'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Warranty End</small>
            <span>{{ $profile['warranty_end_date'] ?? '-' }}</span>
          </div>
          <div class="col-12 col-md-4">
            <small class="text-muted d-block">Device Condition</small>
            <span class="text-capitalize">{{ isset($profile['device_condition']) ? str_replace('_', ' ', $profile['device_condition']) : '-' }}</span>
          </div>
        </div>
      @else
        <p class="text-muted mb-0">No additional type-specific details.</p>
      @endif
    </div>
  </div>

  <div class="row g-3">
    @if($isServiceHostType)
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Hosted Services / Applications</h5>
            <span class="badge bg-label-primary">{{ $asset->services->count() }} Service(s)</span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Service</th>
                  <th>Type</th>
                  <th>Tech Stack</th>
                  <th>Version</th>
                  <th>Status</th>
                  <th>Port</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                @forelse($asset->services as $service)
                  @php
                    $statusClass = $service->status === 'running'
                      ? 'success'
                      : ($service->status === 'degraded' ? 'warning' : ($service->status === 'down' ? 'danger' : 'secondary'));
                  @endphp
                  <tr>
                    <td>
                      {{ $service->service_name }}
                      @if($service->is_primary)
                        <span class="badge bg-label-primary">Primary</span>
                      @endif
                    </td>
                    <td class="text-capitalize">{{ str_replace('_', ' ', $service->service_type) }}</td>
                    <td>{{ $service->technology_stack ?: '-' }}</td>
                    <td>{{ $service->version ?: '-' }}</td>
                    <td><span class="badge bg-label-{{ $statusClass }} text-capitalize">{{ $service->status }}</span></td>
                    <td>{{ $service->port ?: '-' }}</td>
                    <td>{{ $service->notes ?: '-' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted py-3">No service/application mapped to this asset yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif

    <div class="col-12">
      <div class="card">
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

    <div class="col-12">
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

  </div>
</section>
@endsection
