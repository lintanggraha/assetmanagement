@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    USER MANAGEMENT
    <small>Role-based access and account activation controls</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li class="active">User Management</li>
  </ol>
</section>

<section class="content">
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="card border-start border-primary border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Total Users</small>
          <h4 class="mb-0">{{ $summary['total'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-start border-success border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Active</small>
          <h4 class="mb-0">{{ $summary['active'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-start border-danger border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Inactive</small>
          <h4 class="mb-0">{{ $summary['inactive'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-start border-warning border-3 h-100">
        <div class="card-body">
          <small class="text-muted d-block">Admin Roles</small>
          <h4 class="mb-0">{{ $summary['admins'] }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="alert alert-info">
    Role matrix: <strong>superadmin/admin</strong> manage users, <strong>auditor</strong> approval and policy oversight,
    <strong>operator</strong> manage inventory/discovery, <strong>viewer</strong> read-only.
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Accounts</h5>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last Update</th>
            <th>Manage</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $userRow)
            @php
              $actor = Auth::user();
              $isSelf = $actor->id === $userRow->id;
              $isTargetSuperadmin = $userRow->role === 'superadmin';
              $isActorAdmin = $actor->role === 'admin';
              $canEditRow = $actor->isSuperAdmin() || !$isTargetSuperadmin;
            @endphp
            <tr>
              <td>
                <span class="fw-semibold">{{ $userRow->name }}</span>
                @if($isSelf)
                  <span class="badge bg-label-primary">You</span>
                @endif
              </td>
              <td>{{ $userRow->email }}</td>
              <td><span class="badge bg-label-secondary text-uppercase">{{ $userRow->role }}</span></td>
              <td>
                @if($userRow->is_active)
                  <span class="badge bg-label-success">Active</span>
                @else
                  <span class="badge bg-label-danger">Inactive</span>
                @endif
              </td>
              <td>{{ $userRow->updated_at->format('Y-m-d H:i') }}</td>
              <td>
                @if($canEditRow)
                  <form action="{{ route('users.update', $userRow->id) }}" method="POST" class="d-flex flex-wrap gap-1 align-items-center">
                    @csrf
                    <select name="role" class="form-control form-control-sm" style="min-width: 150px;">
                      @foreach($roles as $roleOption)
                        @if(!($isActorAdmin && $roleOption === 'superadmin'))
                          <option value="{{ $roleOption }}" {{ $userRow->role === $roleOption ? 'selected' : '' }}>
                            {{ strtoupper($roleOption) }}
                          </option>
                        @endif
                      @endforeach
                    </select>
                    <select name="is_active" class="form-control form-control-sm" style="min-width: 110px;">
                      <option value="1" {{ $userRow->is_active ? 'selected' : '' }}>Active</option>
                      <option value="0" {{ !$userRow->is_active ? 'selected' : '' }} {{ $isSelf ? 'disabled' : '' }}>Inactive</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-xs">Save</button>
                  </form>
                @else
                  <span class="text-muted">Superadmin-only control</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Belum ada user terdaftar.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($users->hasPages())
      <div class="card-footer">
        {{ $users->links() }}
      </div>
    @endif
  </div>
</section>
@endsection
