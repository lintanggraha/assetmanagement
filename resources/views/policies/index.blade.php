@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    POLICY VIOLATIONS
    <small>Compliance and hygiene monitoring for managed assets</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li class="active">Policy Violations</li>
  </ol>
</section>

<section class="content">
  <div class="d-flex justify-content-end mb-3 gap-2">
    @if(Auth::user()->canRunDiscovery() || Auth::user()->canApproveAssetChanges())
      <a href="{{ route('policies.index', ['refresh' => 1]) }}" class="btn btn-primary btn-sm">
        <i class="fa fa-refresh"></i> Refresh Policy Scan
      </a>
    @endif
    <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm">Open Inventory</a>
  </div>

  @if($scanStats)
    <div class="alert alert-info">
      Scan result:
      scanned={{ $scanStats['assets_scanned'] }},
      new_open={{ $scanStats['new_open'] }},
      still_open={{ $scanStats['still_open'] }},
      resolved={{ $scanStats['resolved'] }},
      total_open={{ $scanStats['total_open'] }}.
    </div>
  @endif

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-2">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Open Total</small>
          <h4 class="mb-0">{{ $summary['open_total'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <div class="card border-start border-danger border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Critical</small>
          <h4 class="mb-0">{{ $summary['critical'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <div class="card border-start border-danger border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">High</small>
          <h4 class="mb-0">{{ $summary['high'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Medium</small>
          <h4 class="mb-0">{{ $summary['medium'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <div class="card border-start border-success border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Resolved</small>
          <h4 class="mb-0">{{ $summary['resolved_total'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <div class="card border-start border-primary border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Resolution Rate</small>
          @php
            $totalTracked = $summary['open_total'] + $summary['resolved_total'];
            $resolutionRate = $totalTracked > 0 ? round(($summary['resolved_total'] / $totalTracked) * 100) : 0;
          @endphp
          <h4 class="mb-0">{{ $resolutionRate }}%</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Violation Register</h5>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Asset</th>
            <th>Policy</th>
            <th>Severity</th>
            <th>Status</th>
            <th>Message</th>
            <th>Detected</th>
            <th>Resolved</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($violations as $violation)
            @php
              $severityClass = $violation->severity === 'critical' ? 'danger' : ($violation->severity === 'high' ? 'warning' : 'secondary');
              $statusClass = $violation->status === 'open' ? 'warning' : 'success';
            @endphp
            <tr>
              <td>
                @if($violation->asset)
                  <a href="{{ route('assets.show', $violation->asset->id) }}" class="fw-semibold">{{ $violation->asset->name }}</a>
                  <small class="d-block text-muted">{{ $violation->asset->asset_code }}</small>
                @else
                  <span class="text-muted">Asset deleted</span>
                @endif
              </td>
              <td><code>{{ $violation->policy_code }}</code></td>
              <td><span class="badge bg-label-{{ $severityClass }} text-capitalize">{{ $violation->severity }}</span></td>
              <td><span class="badge bg-label-{{ $statusClass }} text-capitalize">{{ $violation->status }}</span></td>
              <td>{{ $violation->message }}</td>
              <td>{{ optional($violation->detected_at)->format('Y-m-d H:i') ?: '-' }}</td>
              <td>{{ optional($violation->resolved_at)->format('Y-m-d H:i') ?: '-' }}</td>
              <td>
                @if($violation->status === 'open' && Auth::user()->canApproveAssetChanges())
                  <form action="{{ route('policies.resolve', $violation->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-xs">Mark Resolved</button>
                  </form>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">Tidak ada policy violation untuk scope user saat ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($violations->hasPages())
      <div class="card-footer">
        {{ $violations->appends(request()->query())->links() }}
      </div>
    @endif
  </div>
</section>
@endsection
