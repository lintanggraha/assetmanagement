@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    EDIT ASSET
    <small>Maintain metadata, ownership, and lifecycle</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li><a href="{{ route('assets.index') }}">Asset Inventory</a></li>
    <li class="active">Edit Asset</li>
  </ol>
</section>

<section class="content">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ $asset->name }}</h5>
      <span class="badge bg-label-primary">{{ $asset->asset_code }}</span>
    </div>
    <div class="card-body">
      <form action="{{ route('assets.update', $asset->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('assets._form', ['asset' => $asset, 'banks' => $banks, 'options' => $options])
        <hr>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">Update Asset</button>
          <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-outline-secondary">Back</a>
        </div>
      </form>
    </div>
  </div>
</section>
@endsection
