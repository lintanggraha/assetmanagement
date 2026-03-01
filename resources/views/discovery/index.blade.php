@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    DISCOVERY CENTER
    <small>Continuously identify, reconcile, and enrich asset inventory</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li class="active">Discovery Center</li>
  </ol>
</section>

<section class="content">
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="card border-start border-primary border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Total Runs</small>
          <h4 class="mb-0">{{ $metrics['total_runs'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-start border-success border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Completed Runs</small>
          <h4 class="mb-0">{{ $metrics['completed_runs'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-start border-info border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">New Assets Discovered</small>
          <h4 class="mb-0">{{ $metrics['new_assets_discovered'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Assets Updated by Discovery</small>
          <h4 class="mb-0">{{ $metrics['updated_assets'] }}</h4>
        </div>
      </div>
    </div>
  </div>

  @if(Auth::user()->canRunDiscovery())
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Run New Discovery</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('discovery.run') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-12 col-lg-4">
              <label class="form-label">Scope</label>
              <input type="text" class="form-control" name="scope" placeholder="Production East Region, Payment Domain, etc." value="{{ old('scope') }}">
            </div>
            <div class="col-12 col-lg-4">
              <label class="form-label">Source Mode</label>
              <select class="form-control" name="source_mode" required>
                <option value="catalog_sync" {{ old('source_mode') === 'catalog_sync' ? 'selected' : '' }}>Catalog Sync</option>
                <option value="manual_seed" {{ old('source_mode') === 'manual_seed' ? 'selected' : '' }}>Manual Seed</option>
                <option value="hybrid" {{ old('source_mode') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
              </select>
            </div>
            <div class="col-12 col-lg-4 d-flex align-items-end">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" value="1" id="include_catalog" name="include_catalog" {{ old('include_catalog', 1) ? 'checked' : '' }}>
                <label class="form-check-label" for="include_catalog">
                  Include legacy catalog (Aplikasi + Database)
                </label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Manual Payload (CSV per line, optional)</label>
              <textarea
                name="manual_payload"
                class="form-control"
                rows="6"
                placeholder="name,type,ip,port,hostname,environment,criticality,owner_email,owner_name,bank_id&#10;Core Gateway,application,10.10.20.12,443,core-gw-01,production,critical,owner@company.com,Gateway Team,1">{{ old('manual_payload') }}</textarea>
              <small class="text-muted">
                Format: <code>name,type,ip,port,hostname,environment,criticality,owner_email,owner_name,bank_id</code>.
                Gunakan mode <strong>Hybrid</strong> untuk gabungkan payload manual + sync katalog.
              </small>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Start Discovery Run</button>
              <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Open Inventory</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  @endif

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Discovery Run History</h5>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Run UUID</th>
            <th>Scope</th>
            <th>Source Mode</th>
            <th>Status</th>
            <th>Found</th>
            <th>New</th>
            <th>Updated</th>
            <th>Started</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($runs as $run)
            @php
              $statusClass = $run->status === 'completed' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning');
            @endphp
            <tr>
              <td><code>{{ $run->run_uuid }}</code></td>
              <td>{{ $run->scope ?: '-' }}</td>
              <td class="text-uppercase">{{ $run->source_mode }}</td>
              <td><span class="badge bg-label-{{ $statusClass }} text-capitalize">{{ $run->status }}</span></td>
              <td>{{ $run->total_found }}</td>
              <td>{{ $run->total_new }}</td>
              <td>{{ $run->total_updated }}</td>
              <td>{{ optional($run->started_at)->format('Y-m-d H:i') }}</td>
              <td>
                <a href="{{ route('discovery.show', $run->id) }}" class="btn btn-outline-primary btn-xs">Detail</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-4">Belum ada discovery run.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($runs->hasPages())
      <div class="card-footer">
        {{ $runs->links() }}
      </div>
    @endif
  </div>
</section>
@endsection
