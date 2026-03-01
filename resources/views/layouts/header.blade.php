@php
  $displayName = Auth::check() ? Auth::user()->name : 'Guest';
  $displayInitial = strtoupper(substr($displayName, 0, 1));
  $displayRole = Auth::check() ? Auth::user()->role : 'guest';
@endphp

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)" data-sidebar-toggle="true" aria-label="Toggle sidebar" title="Toggle sidebar">
      <i class="icon-base bx bx-menu icon-md"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <div class="navbar-nav align-items-center d-none d-sm-flex">
      <div class="nav-item fw-semibold text-heading">Asset Management System</div>
    </div>

    <ul class="navbar-nav flex-row align-items-center ms-auto">
      @guest
        <li class="nav-item me-2">
          <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">Login</a>
        </li>
        <li class="nav-item">
          <a href="{{ route('register') }}" class="btn btn-sm btn-primary">Register</a>
        </li>
      @else
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
          <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
            <div class="avatar avatar-online">
              <span class="avatar-initial rounded-circle bg-label-primary">{{ $displayInitial }}</span>
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item mt-0" href="javascript:void(0);">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0 me-2">
                    <div class="avatar avatar-online">
                      <span class="avatar-initial rounded-circle bg-label-primary">{{ $displayInitial }}</span>
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-0">{{ $displayName }}</h6>
                    <small class="text-body-secondary text-uppercase">{{ $displayRole }}</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <div class="dropdown-divider"></div>
            </li>
            <li>
              <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form-navbar').submit();">
                <i class="icon-base bx bx-power-off icon-md me-3"></i>
                <span>Sign Out</span>
              </a>
              <form id="logout-form-navbar" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
              </form>
            </li>
          </ul>
        </li>
      @endguest
    </ul>
  </div>
</nav>
