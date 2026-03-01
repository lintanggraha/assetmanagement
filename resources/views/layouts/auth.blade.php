<!doctype html>
<html
  lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  class="layout-wide customizer-hide"
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
  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/pages/page-auth.css') }}">

  <script src="{{ asset('sneat/assets/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset('sneat/assets/js/config.js') }}"></script>
  @stack('scripts_head')
</head>
<body>
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        @yield('content')
      </div>
    </div>
  </div>

  <script src="{{ asset('sneat/assets/vendor/libs/jquery/jquery.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/libs/popper/popper.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/js/bootstrap.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/js/menu.js') }}"></script>
  <script src="{{ asset('sneat/assets/js/main.js') }}"></script>
  <script src="{{ asset('sneat/assets/js/ssas-legacy.js') }}"></script>
  @stack('scripts_body')
</body>
</html>
