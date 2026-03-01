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

      <h4 class="mb-1">Create account</h4>
      <p class="mb-6">Register to access SSAS Project dashboard.</p>

      <form method="POST" action="{{ route('register') }}" class="mb-6">
        @csrf

        <div class="mb-6">
          <label for="name" class="form-label">Full Name</label>
          <input
            id="name"
            type="text"
            name="name"
            value="{{ old('name') }}"
            required
            autocomplete="name"
            autofocus
            class="form-control @error('name') is-invalid @enderror"
            placeholder="Your full name">
          @error('name')
            <span class="invalid-feedback" role="alert">
              <strong>{{ $message }}</strong>
            </span>
          @enderror
        </div>

        <div class="mb-6">
          <label for="email" class="form-label">Email</label>
          <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
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
          <label for="password" class="form-label">Password</label>
          <div class="input-group input-group-merge">
            <input
              id="password"
              type="password"
              name="password"
              required
              autocomplete="new-password"
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

        <div class="mb-6 form-password-toggle">
          <label for="password-confirm" class="form-label">Confirm Password</label>
          <div class="input-group input-group-merge">
            <input
              id="password-confirm"
              type="password"
              name="password_confirmation"
              required
              autocomplete="new-password"
              class="form-control"
              placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
            <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
          </div>
        </div>

        <button type="submit" class="btn btn-primary d-grid w-100">{{ __('Register') }}</button>
      </form>

      <p class="text-center">
        <span>Already have an account?</span>
        <a href="{{ route('login') }}"><span>Sign in</span></a>
      </p>
    </div>
  </div>
@endsection
