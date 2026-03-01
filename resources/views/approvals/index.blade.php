@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    CHANGE APPROVALS
    <small>Dual-control workflow for sensitive asset updates</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li class="active">Approvals</li>
  </ol>
</section>

<section class="content">
  @php
    $approvedCount = $recentRequests->where('status', 'approved')->count();
    $rejectedCount = $recentRequests->where('status', 'rejected')->count();
  @endphp

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Pending Queue</small>
          <h4 class="mb-0">{{ $pendingRequests->total() }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card border-start border-success border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Recently Approved</small>
          <h4 class="mb-0">{{ $approvedCount }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card border-start border-danger border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Recently Rejected</small>
          <h4 class="mb-0">{{ $rejectedCount }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Pending Requests</h5>
      <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm">Open Inventory</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Request</th>
            <th>Asset</th>
            <th>Type</th>
            <th>Requester</th>
            <th>Submitted</th>
            <th>Reason</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pendingRequests as $changeRequest)
            <tr>
              <td>#{{ $changeRequest->id }}</td>
              <td>
                @if($changeRequest->asset)
                  <a href="{{ route('assets.show', $changeRequest->asset->id) }}" class="fw-semibold">
                    {{ $changeRequest->asset->name }}
                  </a>
                  <small class="d-block text-muted">{{ $changeRequest->asset->asset_code }}</small>
                @else
                  <span class="text-muted">Asset deleted</span>
                @endif
              </td>
              <td class="text-capitalize">{{ str_replace('_', ' ', $changeRequest->change_type) }}</td>
              <td>
                {{ optional($changeRequest->requester)->name ?: '-' }}
                <small class="d-block text-muted">{{ optional($changeRequest->requester)->email ?: '' }}</small>
              </td>
              <td>{{ $changeRequest->created_at->format('Y-m-d H:i') }}</td>
              <td>{{ $changeRequest->reason ?: '-' }}</td>
              <td>
                <div class="d-grid gap-1" style="min-width: 200px;">
                  <form action="{{ route('approvals.approve', $changeRequest->id) }}" method="POST">
                    @csrf
                    <input type="text" class="form-control form-control-sm mb-1" name="reason" placeholder="Approval note (opsional)">
                    <button type="submit" class="btn btn-success btn-xs w-100">Approve</button>
                  </form>
                  <form action="{{ route('approvals.reject', $changeRequest->id) }}" method="POST">
                    @csrf
                    <input type="text" class="form-control form-control-sm mb-1" name="reason" placeholder="Reject reason">
                    <button type="submit" class="btn btn-danger btn-xs w-100">Reject</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Tidak ada request change yang menunggu approval.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($pendingRequests->hasPages())
      <div class="card-footer">
        {{ $pendingRequests->appends(request()->query())->links() }}
      </div>
    @endif
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Recent Decisions</h5>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>Request</th>
            <th>Asset</th>
            <th>Decision</th>
            <th>Approver</th>
            <th>Reviewed At</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentRequests as $changeRequest)
            @php
              $decisionClass = $changeRequest->status === 'approved' ? 'success' : 'danger';
            @endphp
            <tr>
              <td>#{{ $changeRequest->id }}</td>
              <td>{{ optional($changeRequest->asset)->name ?: '-' }}</td>
              <td><span class="badge bg-label-{{ $decisionClass }} text-capitalize">{{ $changeRequest->status }}</span></td>
              <td>{{ optional($changeRequest->approver)->name ?: '-' }}</td>
              <td>{{ optional($changeRequest->reviewed_at)->format('Y-m-d H:i') ?: '-' }}</td>
              <td>{{ $changeRequest->reason ?: '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-3">Belum ada history approval.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</section>
@endsection
