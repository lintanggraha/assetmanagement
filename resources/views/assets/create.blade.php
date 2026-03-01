@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    ADD ASSET
    <small>Register new asset into managed inventory</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li><a href="{{ route('assets.index') }}">Asset Inventory</a></li>
    <li class="active">Add Asset</li>
  </ol>
</section>

<section class="content">
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Asset Registration Form</h5>
    </div>
    <div class="card-body">
      <form action="{{ route('assets.store') }}" method="POST">
        @csrf
        @include('assets._form', ['asset' => null, 'banks' => $banks, 'options' => $options])
        <hr>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">Save Asset</button>
          <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</section>
@endsection
