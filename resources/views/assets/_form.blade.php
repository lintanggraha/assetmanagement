@php
  $asset = $asset ?? null;
@endphp

<div class="row g-3">
  <div class="col-12 col-lg-6">
    <label class="form-label">Asset Name</label>
    <input
      type="text"
      name="name"
      class="form-control @error('name') is-invalid @enderror"
      value="{{ old('name', $asset->name ?? '') }}"
      placeholder="Core Banking API Gateway"
      required>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Asset Type</label>
    <select name="asset_type" class="form-control @error('asset_type') is-invalid @enderror" required>
      @foreach($options['asset_types'] as $option)
        <option value="{{ $option }}" {{ old('asset_type', $asset->asset_type ?? 'application') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Environment</label>
    <select name="environment" class="form-control @error('environment') is-invalid @enderror" required>
      @foreach($options['environments'] as $option)
        <option value="{{ $option }}" {{ old('environment', $asset->environment ?? 'production') === $option ? 'selected' : '' }}>{{ strtoupper($option) }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Criticality</label>
    <select name="criticality" class="form-control @error('criticality') is-invalid @enderror" required>
      @foreach($options['criticalities'] as $option)
        <option value="{{ $option }}" {{ old('criticality', $asset->criticality ?? 'medium') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-control @error('status') is-invalid @enderror" required>
      @foreach($options['statuses'] as $option)
        <option value="{{ $option }}" {{ old('status', $asset->status ?? 'active') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Lifecycle Stage</label>
    <select name="lifecycle_stage" class="form-control @error('lifecycle_stage') is-invalid @enderror" required>
      @foreach($options['lifecycle_stages'] as $option)
        <option value="{{ $option }}" {{ old('lifecycle_stage', $asset->lifecycle_stage ?? 'operational') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Source</label>
    <select name="source" class="form-control @error('source') is-invalid @enderror">
      @foreach($options['sources'] as $option)
        <option value="{{ $option }}" {{ old('source', $asset->source ?? 'manual') === $option ? 'selected' : '' }}>{{ strtoupper($option) }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Discovery Confidence</label>
    <input
      type="number"
      min="0"
      max="100"
      name="discovery_confidence"
      class="form-control @error('discovery_confidence') is-invalid @enderror"
      value="{{ old('discovery_confidence', $asset->discovery_confidence ?? 100) }}"
      placeholder="0-100">
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">IP Address</label>
    <input
      type="text"
      name="ip_address"
      class="form-control @error('ip_address') is-invalid @enderror"
      value="{{ old('ip_address', $asset->ip_address ?? '') }}"
      placeholder="10.10.20.12">
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Hostname</label>
    <input
      type="text"
      name="hostname"
      class="form-control @error('hostname') is-invalid @enderror"
      value="{{ old('hostname', $asset->hostname ?? '') }}"
      placeholder="core-gateway-prod-01">
  </div>

  <div class="col-12 col-lg-2">
    <label class="form-label">Port</label>
    <input
      type="text"
      name="port"
      class="form-control @error('port') is-invalid @enderror"
      value="{{ old('port', $asset->port ?? '') }}"
      placeholder="443">
  </div>

  <div class="col-12 col-lg-4">
    <label class="form-label">Bank / Business Unit</label>
    <select name="bank_id" class="form-control @error('bank_id') is-invalid @enderror">
      <option value="">Not Assigned</option>
      @foreach($banks as $bank)
        <option value="{{ $bank->id }}" {{ (string) old('bank_id', $asset->bank_id ?? '') === (string) $bank->id ? 'selected' : '' }}>
          {{ $bank->nama }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Owner Name</label>
    <input
      type="text"
      name="owner_name"
      class="form-control @error('owner_name') is-invalid @enderror"
      value="{{ old('owner_name', $asset->owner_name ?? '') }}"
      placeholder="Application Owner">
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Owner Email</label>
    <input
      type="email"
      name="owner_email"
      class="form-control @error('owner_email') is-invalid @enderror"
      value="{{ old('owner_email', $asset->owner_email ?? '') }}"
      placeholder="owner@company.com">
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Last Seen At</label>
    <input
      type="datetime-local"
      name="last_seen_at"
      class="form-control @error('last_seen_at') is-invalid @enderror"
      value="{{ old('last_seen_at', isset($asset) && $asset->last_seen_at ? $asset->last_seen_at->format('Y-m-d\TH:i') : '') }}">
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Tags</label>
    <input
      type="text"
      name="tags"
      class="form-control @error('tags') is-invalid @enderror"
      value="{{ old('tags', $asset->tags ?? '') }}"
      placeholder="core-banking, payment, tier-1">
  </div>

  <div class="col-12">
    <label class="form-label">Notes</label>
    <textarea
      name="notes"
      class="form-control @error('notes') is-invalid @enderror"
      rows="4"
      placeholder="Operational note, dependencies, compliance references...">{{ old('notes', $asset->notes ?? '') }}</textarea>
  </div>

  @if($asset && Auth::user()->canManageAssetRecords() && !Auth::user()->canApproveAssetChanges())
    <div class="col-12">
      <label class="form-label">Approval Reason (for sensitive changes)</label>
      <input
        type="text"
        name="approval_reason"
        class="form-control @error('approval_reason') is-invalid @enderror"
        value="{{ old('approval_reason') }}"
        placeholder="Jelaskan alasan perubahan (wajib secara proses bila mengubah status/criticality/owner/bank)">
    </div>
  @endif
</div>
