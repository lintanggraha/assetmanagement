@extends('layouts.auth')

@section('content')
  <div class="card px-sm-6 px-0">
    <div class="card-body">
      <div class="app-brand justify-content-center mb-6">
        <a href="{{ url('/dashboard') }}" class="app-brand-link gap-2">
          <span class="app-brand-logo demo">
            <span class="avatar avatar-sm">
              <span class="avatar-initial rounded bg-label-primary fw-bold">S</span>
            </span>
          </span>
          <span class="app-brand-text demo text-heading fw-bold">SSAS Project</span>
        </a>
      </div>

      <h4 class="mb-1">Welcome back</h4>
      <p class="mb-6">Sign in to start your session.</p>

      <form method="POST" action="{{ route('login') }}" class="mb-6">
        @csrf

        <div class="mb-6">
          <label for="email" class="form-label">Email</label>
          <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
            autofocus
            autocomplete="email"
            class="form-control @error('email') is-invalid @enderror"
            placeholder="name@example.com">
          @error('email')
            <span class="invalid-feedback" role="alert">
              <strong>{{ $message }}</strong>
            </span>
          @enderror
        </div>

        <div class="mb-6 form-password-toggle">
          <div class="d-flex justify-content-between">
            <label for="password" class="form-label">Password</label>
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}">
                <small>Forgot Password?</small>
              </a>
            @endif
          </div>
          <div class="input-group input-group-merge">
            <input
              id="password"
              type="password"
              name="password"
              required
              autocomplete="current-password"
              class="form-control @error('password') is-invalid @enderror"
              placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
            <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
          </div>
          @error('password')
            <span class="invalid-feedback d-block" role="alert">
              <strong>{{ $message }}</strong>
            </span>
          @enderror
        </div>

        <div class="mb-6">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Remember Me</label>
          </div>
        </div>

        <div class="mb-6">
          <button type="submit" class="btn btn-primary d-grid w-100">{{ __('Login') }}</button>
        </div>
      </form>

      @if (Route::has('register'))
        <p class="text-center">
          <span>Need an account?</span>
          <a href="{{ route('register') }}"><span>Register</span></a>
        </p>
      @endif
    </div>
  </div>
@endsection
