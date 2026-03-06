@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    ASSET OPS CENTER
    <small>Asset Management Control Plane</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li class="active">Asset Ops Center</li>
  </ol>
</section>

<section class="content">
  @if(($eolNotification['total'] ?? 0) > 0)
    <div class="alert alert-warning mb-3">
      <strong>Notifikasi EOL:</strong>
      OS expired: {{ $eolNotification['os_expired'] }},
      OS <= 90 hari: {{ $eolNotification['os_near'] }},
      License expired: {{ $eolNotification['license_expired'] }},
      License <= 90 hari: {{ $eolNotification['license_near'] }}.
    </div>
  @endif

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h5 class="mb-1">Operational Snapshot</h5>
      <small class="text-muted">Last refresh {{ now()->format('Y-m-d H:i:s') }}</small>
    </div>
    <div class="d-flex gap-2">
      @if(Auth::user()->canManageAssetRecords())
        <a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm">
          <i class="fa fa-plus"></i> Add Asset
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
      @if(Auth::user()->canManageUsers())
        <a href="{{ route('users.index') }}" class="btn btn-outline-dark btn-sm">
          <i class="fa fa-users"></i> Users
        </a>
      @endif
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-2">
      <div class="card border-start border-primary border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Total Assets</small>
          <h3 class="mb-0">{{ $metrics['total'] }}</h3>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-2">
      <div class="card border-start border-success border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Active</small>
          <h3 class="mb-0">{{ $metrics['active'] }}</h3>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-2">
      <div class="card border-start border-danger border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Critical Assets</small>
          <h3 class="mb-0">{{ $metrics['critical'] }}</h3>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-2">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Stale &gt; 30 Days</small>
          <h3 class="mb-0">{{ $metrics['stale'] }}</h3>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-2">
      <div class="card border-start border-secondary border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">No Owner</small>
          <h3 class="mb-0">{{ $metrics['unmanaged'] }}</h3>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-2">
      <div class="card border-start border-dark border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">High Risk (&ge; 70)</small>
          <h3 class="mb-0">{{ $metrics['high_risk'] }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-6">
      <div class="card border-start border-danger border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Open Policy Violations</small>
          <h3 class="mb-0">{{ $metrics['open_violations'] }}</h3>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Pending Change Approvals</small>
          <h3 class="mb-0">{{ $metrics['pending_approvals'] }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Asset Composition by Type</h5>
          <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-primary">Open Inventory</a>
        </div>
        <div class="card-body">
          @if($assetsByType->count() > 0)
            @foreach($assetsByType as $typeRow)
              @php
                $percent = $metrics['total'] > 0 ? round(($typeRow->total / $metrics['total']) * 100) : 0;
              @endphp
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-capitalize">{{ str_replace('_', ' ', $typeRow->asset_type) }}</span>
                  <span class="fw-semibold">{{ $typeRow->total }} ({{ $percent }}%)</span>
                </div>
                <div class="progress" style="height: 8px;">
                  <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%;"></div>
                </div>
              </div>
            @endforeach
          @else
            <p class="text-muted mb-0">Belum ada asset. Tambahkan asset manual dari Asset Inventory.</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Inventory Data Hygiene</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <div class="d-flex justify-content-between">
              <span>Ownership Coverage</span>
              <strong>{{ $coverage['owner'] }}%</strong>
            </div>
            <div class="progress mt-1" style="height: 8px;">
              <div class="progress-bar bg-success" style="width: {{ $coverage['owner'] }}%;"></div>
            </div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between">
              <span>Visibility (last_seen populated)</span>
              <strong>{{ $coverage['visibility'] }}%</strong>
            </div>
            <div class="progress mt-1" style="height: 8px;">
              <div class="progress-bar bg-info" style="width: {{ $coverage['visibility'] }}%;"></div>
            </div>
          </div>
          <div class="mb-2">
            <div class="d-flex justify-content-between">
              <span>Import Coverage</span>
              <strong>{{ $coverage['import'] }}%</strong>
            </div>
            <div class="progress mt-1" style="height: 8px;">
              <div class="progress-bar bg-primary" style="width: {{ $coverage['import'] }}%;"></div>
            </div>
          </div>
          <hr>
          <div class="row text-center">
            @foreach($assetsByStatus as $statusRow)
              <div class="col-6 col-md-4 mb-2">
                <small class="d-block text-muted text-capitalize">{{ $statusRow->status }}</small>
                <strong>{{ $statusRow->total }}</strong>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Top Risk Assets</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Asset</th>
                <th>Type</th>
                <th>Status</th>
                <th>Risk</th>
                <th>Last Seen</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topRiskAssets as $asset)
                <tr>
                  <td>
                    <a href="{{ route('assets.show', $asset->id) }}" class="fw-semibold">{{ $asset->name }}</a>
                    <small class="d-block text-muted">{{ $asset->asset_code }}</small>
                  </td>
                  <td class="text-capitalize">{{ $asset->asset_type }}</td>
                  <td><span class="badge bg-label-secondary text-capitalize">{{ $asset->status }}</span></td>
                  <td><span class="badge bg-label-danger">{{ $asset->risk_score }}</span></td>
                  <td>{{ $asset->last_seen_at ? $asset->last_seen_at->diffForHumans() : 'Never' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">Belum ada data asset untuk dianalisis.</td>
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
