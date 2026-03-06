@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    ASSET INVENTORY
    <small>Professional asset register with filtering and lifecycle control</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li class="active">Asset Inventory</li>
  </ol>
</section>

<section class="content">
  @if(($eolNotification['total_alerts'] ?? 0) > 0)
    <div class="alert alert-warning mb-3">
      <strong>Notifikasi EOL:</strong>
      OS expired: {{ $eolNotification['os_expired'] }},
      OS <= 90 hari: {{ $eolNotification['os_near'] }},
      License expired: {{ $eolNotification['license_expired'] }},
      License <= 90 hari: {{ $eolNotification['license_near'] }}.
      @if(!empty($eolNotification['items']))
        <ul class="mb-0 mt-2 ps-3">
          @foreach($eolNotification['items'] as $alertItem)
            <li>
              <a href="{{ route('assets.show', $alertItem['asset_id']) }}">{{ $alertItem['asset_name'] }}</a>
              ({{ $alertItem['asset_code'] }}) - {{ $alertItem['target'] }} {{ strtoupper($alertItem['state']) }}
              @if($alertItem['days_left'] < 0)
                {{ abs($alertItem['days_left']) }} hari lalu
              @else
                dalam {{ $alertItem['days_left'] }} hari
              @endif
              ({{ $alertItem['eol_date'] }})
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  @endif

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4 col-xl-2">
      <div class="card border-start border-primary border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Total Assets</small>
          <h4 class="mb-0">{{ $summary['total'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
      <div class="card border-start border-success border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Active Assets</small>
          <h4 class="mb-0">{{ $summary['active'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
      <div class="card border-start border-danger border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Critical</small>
          <h4 class="mb-0">{{ $summary['critical'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
      <div class="card border-start border-dark border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">High Risk (&ge; 70)</small>
          <h4 class="mb-0">{{ $summary['high_risk'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Pending Approvals</small>
          <h4 class="mb-0">{{ $summary['pending_approvals'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
      <div class="card border-start border-info border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Open Violations</small>
          <h4 class="mb-0">{{ $summary['open_violations'] }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Filter Inventory</h5>
      <div class="d-flex gap-2">
        <a href="{{ route('policies.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-shield"></i> Policies
        </a>
        @if(Auth::user()->canApproveAssetChanges())
          <a href="{{ route('approvals.index') }}" class="btn btn-outline-warning btn-sm">
            <i class="fa fa-check-circle"></i> Approvals
          </a>
        @endif
        @if(Auth::user()->canManageAssetRecords())
          <a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> Add Asset
          </a>
        @endif
      </div>
    </div>
    <div class="card-body">
      <form method="GET" action="{{ route('assets.index') }}">
        <div class="row g-2">
          <div class="col-12 col-lg-3">
            <input type="text" class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search code, name, IP, owner, OS, tags">
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="asset_type">
              <option value="">All Types</option>
              @foreach($options['asset_types'] as $option)
                <option value="{{ $option }}" {{ ($filters['asset_type'] ?? '') === $option ? 'selected' : '' }}>
                  {{ ucwords(str_replace('_', ' ', $option)) }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="host_type">
              <option value="">All Host Types</option>
              @foreach($options['host_types'] as $option)
                <option value="{{ $option }}" {{ ($filters['host_type'] ?? '') === $option ? 'selected' : '' }}>
                  {{ ucwords(str_replace('_', ' ', $option)) }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="server_role">
              <option value="">All Roles</option>
              @foreach($options['server_roles'] as $option)
                <option value="{{ $option }}" {{ ($filters['server_role'] ?? '') === $option ? 'selected' : '' }}>
                  {{ ucwords(str_replace('_', ' ', $option)) }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="status">
              <option value="">All Status</option>
              @foreach($options['statuses'] as $option)
                <option value="{{ $option }}" {{ ($filters['status'] ?? '') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="criticality">
              <option value="">All Criticality</option>
              @foreach($options['criticalities'] as $option)
                <option value="{{ $option }}" {{ ($filters['criticality'] ?? '') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="environment">
              <option value="">All Environments</option>
              @foreach($options['environments'] as $option)
                <option value="{{ $option }}" {{ ($filters['environment'] ?? '') === $option ? 'selected' : '' }}>{{ strtoupper($option) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-lg-1">
            <input type="number" class="form-control" min="0" max="100" name="risk_min" value="{{ $filters['risk_min'] ?? '' }}" placeholder="Risk >=">
          </div>
          <div class="col-6 col-lg-1">
            <input type="number" class="form-control" min="0" max="100" name="risk_max" value="{{ $filters['risk_max'] ?? '' }}" placeholder="Risk <=">
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="source">
              <option value="">All Sources</option>
              @foreach($options['sources'] as $option)
                <option value="{{ $option }}" {{ ($filters['source'] ?? '') === $option ? 'selected' : '' }}>{{ strtoupper($option) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="os_eol_state">
              <option value="">All OS EOL</option>
              <option value="expired" {{ ($filters['os_eol_state'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
              <option value="next_90_days" {{ ($filters['os_eol_state'] ?? '') === 'next_90_days' ? 'selected' : '' }}>Next 90 Days</option>
              <option value="next_180_days" {{ ($filters['os_eol_state'] ?? '') === 'next_180_days' ? 'selected' : '' }}>Next 180 Days</option>
              <option value="missing" {{ ($filters['os_eol_state'] ?? '') === 'missing' ? 'selected' : '' }}>EOL Missing</option>
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <input type="number" class="form-control" min="1" max="365" name="stale_days" value="{{ $filters['stale_days'] ?? '' }}" placeholder="Stale days">
          </div>
          <div class="col-12 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">Apply</button>
            <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm flex-fill">Reset</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Asset</th>
            <th>Type</th>
            <th>Env</th>
            <th>Status</th>
            <th>Criticality</th>
            <th>Risk</th>
            <th>Owner</th>
            <th>Last Seen</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($assets as $asset)
            @php
              $riskClass = $asset->risk_score >= 70 ? 'danger' : ($asset->risk_score >= 40 ? 'warning' : 'success');
            @endphp
            <tr>
              <td>
                <a href="{{ route('assets.show', $asset->id) }}" class="fw-semibold">{{ $asset->name }}</a>
                <small class="d-block text-muted">{{ $asset->asset_code }}</small>
                @if($asset->ip_address || $asset->hostname)
                  <small class="d-block text-muted">{{ $asset->ip_address ?: $asset->hostname }}{{ $asset->port ? ':' . $asset->port : '' }}</small>
                @endif
                @if($asset->host_type || $asset->server_role)
                  <small class="d-block text-muted">
                    {{ $asset->host_type ? ucwords(str_replace('_', ' ', $asset->host_type)) : '-' }}
                    /
                    {{ $asset->server_role ? ucwords(str_replace('_', ' ', $asset->server_role)) : '-' }}
                  </small>
                @endif
                @if($asset->operating_system || $asset->os_version || $asset->os_eol_date)
                  <small class="d-block text-muted">
                    OS: {{ $asset->operating_system ?: '-' }} {{ $asset->os_version ?: '' }}
                    @if($asset->os_eol_date)
                      | EOL {{ $asset->os_eol_date->format('Y-m-d') }}
                    @endif
                  </small>
                @endif
                @if($asset->asset_type === 'database_server' && is_array($asset->asset_profile) && !empty($asset->asset_profile['db_license_eol_date']))
                  <small class="d-block text-muted">
                    License EOL {{ $asset->asset_profile['db_license_eol_date'] }}
                  </small>
                @endif
                <small class="d-block text-muted">Services: {{ $asset->services_count ?? 0 }}</small>
              </td>
              <td class="text-capitalize">{{ ucwords(str_replace('_', ' ', $asset->asset_type)) }}</td>
              <td class="text-uppercase">{{ $asset->environment }}</td>
              <td><span class="badge bg-label-secondary text-capitalize">{{ $asset->status }}</span></td>
              <td><span class="badge bg-label-primary text-capitalize">{{ $asset->criticality }}</span></td>
              <td><span class="badge bg-label-{{ $riskClass }}">{{ $asset->risk_score }}</span></td>
              <td>
                @if($asset->owner_name)
                  {{ $asset->owner_name }}
                  @if($asset->owner_email)
                    <small class="d-block text-muted">{{ $asset->owner_email }}</small>
                  @endif
                @else
                  <span class="text-muted">Unassigned</span>
                @endif
              </td>
              <td>{{ $asset->last_seen_at ? $asset->last_seen_at->format('Y-m-d H:i') : 'Never' }}</td>
              <td>
                <div class="d-flex flex-wrap gap-1">
                  <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-outline-primary btn-xs">View</a>
                  @if(Auth::user()->canManageAssetRecords())
                    <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-outline-secondary btn-xs">Edit</a>
                    @if($asset->status !== 'retired')
                      <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('Retire asset ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-xs">Retire</button>
                      </form>
                    @endif
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-4">Belum ada asset. Mulai dari Add Asset atau import manual.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($assets->hasPages())
      <div class="card-footer">
        {{ $assets->links() }}
      </div>
    @endif
  </div>
</section>
@endsection
