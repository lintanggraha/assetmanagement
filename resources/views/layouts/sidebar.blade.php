@php
  $isAssetOps = request()->path() === '/' || request()->is('dashboard');
  $isGuide = request()->is('user-guide');
  $isAssetInventory = request()->is('asset-inventory*');
  $isDiscovery = request()->is('discovery*');
  $isPolicies = request()->is('asset-policies*');
  $isApprovals = request()->is('approvals*');
  $isUsers = request()->is('users*');
  $user = Auth::user();
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo">
    <a href="{{ url('/dashboard') }}" class="app-brand-link">
      <span class="app-brand-logo" aria-hidden="true">
        <i class="bx bx-grid-alt text-primary fs-4"></i>
      </span>
      <span class="app-brand-text menu-text fw-bold ms-2">Asset Management</span>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @auth
      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link">
          <i class="menu-icon tf-icons bx bx-user-circle"></i>
          <div class="text-truncate">
            <span class="d-block">Welcome {{ Auth::user()->name }}</span>
            <small class="text-uppercase text-muted">{{ Auth::user()->role }}</small>
          </div>
        </a>
      </li>
    @endauth

    <li class="menu-header small text-uppercase">
      <span class="menu-header-text">Asset Operations</span>
    </li>

    <li class="menu-item {{ $isAssetOps ? 'active' : '' }}">
      <a href="{{ route('dashboard') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-command"></i>
        <div class="text-truncate">Asset Ops Center</div>
      </a>
    </li>

    <li class="menu-item {{ $isGuide ? 'active' : '' }}">
      <a href="{{ route('guide.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-book-open"></i>
        <div class="text-truncate">User Guide</div>
      </a>
    </li>

    <li class="menu-item {{ $isAssetInventory ? 'active' : '' }}">
      <a href="{{ route('assets.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-package"></i>
        <div class="text-truncate">Asset Inventory</div>
      </a>
    </li>

    @if($user && $user->canRunDiscovery())
      <li class="menu-item {{ $isDiscovery ? 'active' : '' }}">
        <a href="{{ route('discovery.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-radar"></i>
          <div class="text-truncate">Discovery Center</div>
        </a>
      </li>
    @endif

    <li class="menu-item {{ $isPolicies ? 'active' : '' }}">
      <a href="{{ route('policies.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
        <div class="text-truncate">Policy Violations</div>
      </a>
    </li>

    @if($user && $user->canApproveAssetChanges())
      <li class="menu-item {{ $isApprovals ? 'active' : '' }}">
        <a href="{{ route('approvals.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-check-shield"></i>
          <div class="text-truncate">Approvals</div>
        </a>
      </li>
    @endif

    @if($user && $user->canManageUsers())
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Administration</span>
      </li>
      <li class="menu-item {{ $isUsers ? 'active' : '' }}">
        <a href="{{ route('users.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-group"></i>
          <div class="text-truncate">User Management</div>
        </a>
      </li>
    @endif

    @auth
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Session</span>
      </li>
      <li class="menu-item">
        <a href="{{ route('logout') }}" class="menu-link" onclick="event.preventDefault();document.getElementById('logout-form-sidebar').submit();">
          <i class="menu-icon tf-icons bx bx-log-out"></i>
          <div class="text-truncate">Sign Out</div>
        </a>
        <form id="logout-form-sidebar" action="{{ route('logout') }}" method="POST" class="d-none">
          @csrf
        </form>
      </li>
    @else
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Account</span>
      </li>
      <li class="menu-item {{ request()->is('login') ? 'active' : '' }}">
        <a href="{{ route('login') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-log-in"></i>
          <div class="text-truncate">Login</div>
        </a>
      </li>
      <li class="menu-item {{ request()->is('register') ? 'active' : '' }}">
        <a href="{{ route('register') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-user-plus"></i>
          <div class="text-truncate">Register</div>
        </a>
      </li>
    @endauth
  </ul>
</aside>
