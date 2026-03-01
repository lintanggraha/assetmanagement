<!doctype html>
<html
  lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  class="layout-menu-fixed layout-compact"
  data-assets-path="{{ asset('sneat/assets/') }}/"
  data-template="vertical-menu-template-free">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'SSAS Project') }}</title>

  <link rel="icon" type="image/x-icon" href="{{ asset('sneat/assets/img/favicon/favicon.ico') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/fonts/iconify-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/core.css') }}">
  <link rel="stylesheet" href="{{ asset('sneat/assets/css/demo.css') }}">
  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte-v2/bower_components/font-awesome/css/font-awesome.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte-v2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('sneat/assets/css/ssas-legacy.css') }}?v={{ @filemtime(public_path('sneat/assets/css/ssas-legacy.css')) }}">

  <script src="{{ asset('sneat/assets/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset('sneat/assets/js/config.js') }}"></script>
  @stack('scripts_head')
</head>
<body>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      @include('layouts.sidebar')

      <div class="layout-page">
        @include('layouts.header')

        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            @if (session('success'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            @if (session('error'))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            @if ($errors->any())
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Validation error:</strong>
                <ul class="mb-0 mt-2">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            @yield('content')
          </div>

          <footer class="content-footer footer bg-footer-theme">
            <div class="container-xxl">
              <div class="footer-container d-flex align-items-center justify-content-end py-4 flex-md-row flex-column">
                <div class="d-none d-md-inline-block">
                  <span class="footer-link">Version 1.0.1</span>
                </div>
              </div>
            </div>
          </footer>

          <div class="content-backdrop fade"></div>
        </div>
      </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
  </div>

  <script src="{{ asset('sneat/assets/vendor/libs/jquery/jquery.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/libs/popper/popper.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/js/bootstrap.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/js/menu.js') }}"></script>
  <script src="{{ asset('adminlte-v2/bower_components/datatables.net/js/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('adminlte-v2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
  <script src="{{ asset('sneat/assets/js/main.js') }}"></script>
  <script src="{{ asset('sneat/assets/js/ssas-legacy.js') }}?v={{ @filemtime(public_path('sneat/assets/js/ssas-legacy.js')) }}"></script>
  @stack('scripts_body')
  @stack('script_ckeditor')
</body>
</html>
