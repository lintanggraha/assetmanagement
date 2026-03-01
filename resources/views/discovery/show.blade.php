@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    DISCOVERY RUN DETAIL
    <small>Traceability and reconciliation result</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li><a href="{{ route('discovery.index') }}">Discovery Center</a></li>
    <li class="active">{{ substr($run->run_uuid, 0, 8) }}</li>
  </ol>
</section>

<section class="content">
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Run Context</h5>
      @php
        $statusClass = $run->status === 'completed' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning');
      @endphp
      <span class="badge bg-label-{{ $statusClass }} text-capitalize">{{ $run->status }}</span>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <small class="text-muted d-block">Run UUID</small>
          <code>{{ $run->run_uuid }}</code>
        </div>
        <div class="col-12 col-md-4">
          <small class="text-muted d-block">Scope</small>
          <span>{{ $run->scope ?: '-' }}</span>
        </div>
        <div class="col-12 col-md-4">
          <small class="text-muted d-block">Source Mode</small>
          <span class="text-uppercase">{{ $run->source_mode }}</span>
        </div>
        <div class="col-12 col-md-3">
          <small class="text-muted d-block">Started At</small>
          <span>{{ optional($run->started_at)->format('Y-m-d H:i:s') ?: '-' }}</span>
        </div>
        <div class="col-12 col-md-3">
          <small class="text-muted d-block">Completed At</small>
          <span>{{ optional($run->completed_at)->format('Y-m-d H:i:s') ?: '-' }}</span>
        </div>
        <div class="col-12 col-md-2">
          <small class="text-muted d-block">Found</small>
          <strong>{{ $run->total_found }}</strong>
        </div>
        <div class="col-12 col-md-2">
          <small class="text-muted d-block">New</small>
          <strong>{{ $run->total_new }}</strong>
        </div>
        <div class="col-12 col-md-2">
          <small class="text-muted d-block">Updated</small>
          <strong>{{ $run->total_updated }}</strong>
        </div>
        <div class="col-12">
          <small class="text-muted d-block">Summary</small>
          <span>{{ $run->summary ?: '-' }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <div class="card border-start border-success border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">New Findings</small>
          <h4 class="mb-0">{{ $statusCounts['new'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Updated Findings</small>
          <h4 class="mb-0">{{ $statusCounts['updated'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card border-start border-secondary border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Matched Findings</small>
          <h4 class="mb-0">{{ $statusCounts['matched'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Findings</h5>
      <a href="{{ route('discovery.index') }}" class="btn btn-outline-secondary btn-sm">Back to Discovery Center</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Finding Status</th>
            <th>Asset Name</th>
            <th>Type</th>
            <th>Endpoint</th>
            <th>Confidence</th>
            <th>Inventory Link</th>
            <th>Captured At</th>
          </tr>
        </thead>
        <tbody>
          @forelse($findings as $finding)
            @php
              $findingClass = $finding->finding_status === 'new' ? 'success' : ($finding->finding_status === 'updated' ? 'warning' : 'secondary');
            @endphp
            <tr>
              <td><span class="badge bg-label-{{ $findingClass }}">{{ $finding->finding_status }}</span></td>
              <td>{{ $finding->asset_name }}</td>
              <td class="text-capitalize">{{ $finding->asset_type }}</td>
              <td>{{ $finding->ip_address ?: $finding->hostname ?: '-' }}{{ $finding->port ? ':' . $finding->port : '' }}</td>
              <td>{{ $finding->confidence }}%</td>
              <td>
                @if($finding->asset_id)
                  <a href="{{ route('assets.show', $finding->asset_id) }}">Open Asset</a>
                @else
                  <span class="text-muted">N/A</span>
                @endif
              </td>
              <td>{{ $finding->created_at->format('Y-m-d H:i') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Tidak ada findings pada run ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($findings->hasPages())
      <div class="card-footer">
        {{ $findings->links() }}
      </div>
    @endif
  </div>
</section>
@endsection
