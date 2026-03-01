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
        @if(Auth::user()->canRunDiscovery())
          <a href="{{ route('discovery.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-crosshairs"></i> Discovery Center
          </a>
        @endif
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
            <input type="text" class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by code, name, IP, owner, tags">
          </div>
          <div class="col-6 col-lg-2">
            <select class="form-control" name="asset_type">
              <option value="">All Types</option>
              @foreach($options['asset_types'] as $option)
                <option value="{{ $option }}" {{ ($filters['asset_type'] ?? '') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
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
              </td>
              <td class="text-capitalize">{{ $asset->asset_type }}</td>
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
              <td colspan="9" class="text-center text-muted py-4">Belum ada asset. Mulai dari Add Asset atau jalankan discovery.</td>
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
