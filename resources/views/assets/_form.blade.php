@php
  $asset = $asset ?? null;

  $typeMap = [
    'server' => 'application_server',
    'database' => 'database_server',
    'network' => 'network_peripheral',
    'endpoint' => 'etc',
    'storage' => 'etc',
    'other' => 'etc',
  ];

  $selectedType = old('asset_type', $asset->asset_type ?? 'application_server');
  $selectedType = $typeMap[$selectedType] ?? $selectedType;

  $profile = old('profile', $asset->asset_profile ?? []);
  $profile = is_array($profile) ? $profile : [];

  $servicesValue = old('services');
  if ($servicesValue === null) {
    $servicesValue = $asset
      ? $asset->services->map(function ($service) {
          return [
            'name' => $service->service_name,
            'type' => $service->service_type,
            'technology_stack' => $service->technology_stack,
            'version' => $service->version,
            'status' => $service->status,
            'port' => $service->port,
            'notes' => $service->notes,
          ];
        })->values()->all()
      : [];
  }

  if (empty($servicesValue)) {
    $servicesValue = [[
      'name' => '',
      'type' => 'application',
      'technology_stack' => '',
      'version' => '',
      'status' => 'unknown',
      'port' => '',
      'notes' => '',
    ]];
  }
@endphp

<div class="row g-3">
  <div class="col-12 col-lg-6">
    <label class="form-label">Asset Name</label>
    <input
      type="text"
      name="name"
      class="form-control @error('name') is-invalid @enderror"
      value="{{ old('name', $asset->name ?? '') }}"
      placeholder="Asset Name"
      required>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Asset Type</label>
    <select name="asset_type" class="form-control @error('asset_type') is-invalid @enderror" data-asset-type required>
      @foreach($options['asset_types'] as $option)
        <option value="{{ $option }}" {{ $selectedType === $option ? 'selected' : '' }}>
          {{ ucwords(str_replace('_', ' ', $option)) }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Environment</label>
    <select name="environment" class="form-control @error('environment') is-invalid @enderror" required>
      @foreach($options['environments'] as $option)
        <option value="{{ $option }}" {{ old('environment', $asset->environment ?? 'production') === $option ? 'selected' : '' }}>
          {{ strtoupper($option) }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Asset Status</label>
    <select name="status" class="form-control @error('status') is-invalid @enderror" required>
      @foreach($options['statuses'] as $option)
        <option value="{{ $option }}" {{ old('status', $asset->status ?? 'active') === $option ? 'selected' : '' }}>
          {{ ucfirst($option) }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Lifecycle Stage</label>
    <select name="lifecycle_stage" class="form-control @error('lifecycle_stage') is-invalid @enderror" required>
      @foreach($options['lifecycle_stages'] as $option)
        <option value="{{ $option }}" {{ old('lifecycle_stage', $asset->lifecycle_stage ?? 'operational') === $option ? 'selected' : '' }}>
          {{ ucfirst($option) }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-3">
    <label class="form-label">Source</label>
    <select name="source" class="form-control @error('source') is-invalid @enderror">
      @foreach($options['sources'] as $option)
        <option value="{{ $option }}" {{ old('source', $asset->source ?? 'manual') === $option ? 'selected' : '' }}>
          {{ strtoupper($option) }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-12 col-lg-6">
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

  <div class="col-12 col-lg-6">
    <label class="form-label">Owner Name</label>
    <input
      type="text"
      name="owner_name"
      class="form-control @error('owner_name') is-invalid @enderror"
      value="{{ old('owner_name', $asset->owner_name ?? '') }}"
      placeholder="Application / Device Owner">
  </div>

  <div class="col-12 col-lg-4" data-asset-section="non_application">
    <label class="form-label">IP Address</label>
    <input
      type="text"
      name="ip_address"
      class="form-control @error('ip_address') is-invalid @enderror"
      value="{{ old('ip_address', $asset->ip_address ?? '') }}"
      placeholder="10.10.20.12">
  </div>

  <div class="col-12 col-lg-4" data-asset-section="non_application">
    <label class="form-label">Hostname</label>
    <input
      type="text"
      name="hostname"
      class="form-control @error('hostname') is-invalid @enderror"
      value="{{ old('hostname', $asset->hostname ?? '') }}"
      placeholder="hostname-01">
  </div>

  <div class="col-12 col-lg-4" data-asset-section="non_application">
    <label class="form-label">Port</label>
    <input
      type="text"
      name="port"
      class="form-control @error('port') is-invalid @enderror"
      value="{{ old('port', $asset->port ?? '') }}"
      placeholder="443">
  </div>

  <div class="col-12" data-asset-section="application">
    <div class="card card-body border border-primary-subtle">
      <h6 class="mb-3">Application Detail</h6>
      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <label class="form-label">Runtime / Basis App</label>
          <input
            type="text"
            name="profile[application_runtime]"
            class="form-control @error('profile.application_runtime') is-invalid @enderror"
            value="{{ old('profile.application_runtime', $profile['application_runtime'] ?? '') }}"
            placeholder="Java, Vue.js, .NET, PHP">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Jenis Aplikasi</label>
          <select name="profile[application_category]" class="form-control @error('profile.application_category') is-invalid @enderror">
            <option value="">Select Type</option>
            @foreach($options['application_categories'] as $option)
              <option value="{{ $option }}" {{ old('profile.application_category', $profile['application_category'] ?? '') === $option ? 'selected' : '' }}>
                {{ ucfirst($option) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Versi Aplikasi</label>
          <input
            type="text"
            name="profile[application_version]"
            class="form-control @error('profile.application_version') is-invalid @enderror"
            value="{{ old('profile.application_version', $profile['application_version'] ?? '') }}"
            placeholder="v2.4.1">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Tanggal Rilis Pertama</label>
          <input
            type="date"
            name="profile[first_release_date]"
            class="form-control @error('profile.first_release_date') is-invalid @enderror"
            value="{{ old('profile.first_release_date', $profile['first_release_date'] ?? '') }}">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Tanggal Rilis Terakhir</label>
          <input
            type="date"
            name="profile[last_release_date]"
            class="form-control @error('profile.last_release_date') is-invalid @enderror"
            value="{{ old('profile.last_release_date', $profile['last_release_date'] ?? '') }}">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Status Aplikasi</label>
          <select name="profile[application_status]" class="form-control @error('profile.application_status') is-invalid @enderror">
            <option value="">Select Status</option>
            @foreach($options['application_statuses'] as $option)
              <option value="{{ $option }}" {{ old('profile.application_status', $profile['application_status'] ?? '') === $option ? 'selected' : '' }}>
                {{ $option === 'used' ? 'Digunakan' : 'Tidak Digunakan' }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12" data-asset-section="server">
    <div class="card card-body border border-info-subtle">
      <h6 class="mb-3">Server Detail</h6>
      <input type="hidden" name="server_role" value="{{ old('server_role', $asset->server_role ?? '') }}" data-server-role-input>
      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <label class="form-label">Host Type</label>
          <select name="host_type" class="form-control @error('host_type') is-invalid @enderror">
            <option value="">Select Host Type</option>
            @foreach($options['host_types'] as $option)
              <option value="{{ $option }}" {{ old('host_type', $asset->host_type ?? '') === $option ? 'selected' : '' }}>
                {{ ucwords(str_replace('_', ' ', $option)) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Operating System</label>
          <input
            type="text"
            name="operating_system"
            class="form-control @error('operating_system') is-invalid @enderror"
            value="{{ old('operating_system', $asset->operating_system ?? '') }}"
            placeholder="Ubuntu Server / Windows Server / RHEL">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">OS Version</label>
          <input
            type="text"
            name="os_version"
            class="form-control @error('os_version') is-invalid @enderror"
            value="{{ old('os_version', $asset->os_version ?? '') }}"
            placeholder="22.04 LTS">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">OS EOL Date</label>
          <input
            type="date"
            name="os_eol_date"
            class="form-control @error('os_eol_date') is-invalid @enderror"
            value="{{ old('os_eol_date', isset($asset) && $asset->os_eol_date ? $asset->os_eol_date->format('Y-m-d') : '') }}">
        </div>
      </div>
    </div>
  </div>

  <div class="col-12" data-asset-section="database_server">
    <div class="card card-body border border-danger-subtle">
      <h6 class="mb-3">Database License Detail</h6>
      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <label class="form-label">License Type</label>
          <select name="profile[db_license_type]" class="form-control @error('profile.db_license_type') is-invalid @enderror">
            <option value="">Select License Type</option>
            @foreach($options['db_license_types'] as $option)
              <option value="{{ $option }}" {{ old('profile.db_license_type', $profile['db_license_type'] ?? '') === $option ? 'selected' : '' }}>
                {{ ucfirst($option) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">License EOL Date</label>
          <input
            type="date"
            name="profile[db_license_eol_date]"
            class="form-control @error('profile.db_license_eol_date') is-invalid @enderror"
            value="{{ old('profile.db_license_eol_date', $profile['db_license_eol_date'] ?? '') }}">
        </div>
      </div>
    </div>
  </div>

  <div class="col-12" data-asset-section="network_peripheral">
    <div class="card card-body border border-warning-subtle">
      <h6 class="mb-3">Network Peripheral Detail</h6>
      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <label class="form-label">Peripheral Type</label>
          <select name="profile[peripheral_type]" class="form-control @error('profile.peripheral_type') is-invalid @enderror">
            <option value="">Select Type</option>
            @foreach($options['peripheral_types'] as $option)
              <option value="{{ $option }}" {{ old('profile.peripheral_type', $profile['peripheral_type'] ?? '') === $option ? 'selected' : '' }}>
                {{ ucwords(str_replace('_', ' ', $option)) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Vendor</label>
          <input
            type="text"
            name="profile[vendor]"
            class="form-control @error('profile.vendor') is-invalid @enderror"
            value="{{ old('profile.vendor', $profile['vendor'] ?? '') }}"
            placeholder="Cisco / Juniper / Mikrotik">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Model</label>
          <input
            type="text"
            name="profile[model]"
            class="form-control @error('profile.model') is-invalid @enderror"
            value="{{ old('profile.model', $profile['model'] ?? '') }}"
            placeholder="Nexus 9300">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Serial Number</label>
          <input
            type="text"
            name="profile[serial_number]"
            class="form-control @error('profile.serial_number') is-invalid @enderror"
            value="{{ old('profile.serial_number', $profile['serial_number'] ?? '') }}"
            placeholder="SN-XXXX">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Firmware Version</label>
          <input
            type="text"
            name="profile[firmware_version]"
            class="form-control @error('profile.firmware_version') is-invalid @enderror"
            value="{{ old('profile.firmware_version', $profile['firmware_version'] ?? '') }}"
            placeholder="17.9.4">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Firmware EOL Date</label>
          <input
            type="date"
            name="profile[firmware_eol_date]"
            class="form-control @error('profile.firmware_eol_date') is-invalid @enderror"
            value="{{ old('profile.firmware_eol_date', $profile['firmware_eol_date'] ?? '') }}">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Support Contract End</label>
          <input
            type="date"
            name="profile[support_contract_end_date]"
            class="form-control @error('profile.support_contract_end_date') is-invalid @enderror"
            value="{{ old('profile.support_contract_end_date', $profile['support_contract_end_date'] ?? '') }}">
        </div>
        <div class="col-12 col-lg-8">
          <label class="form-label">Management Interface</label>
          <input
            type="text"
            name="profile[management_interface]"
            class="form-control @error('profile.management_interface') is-invalid @enderror"
            value="{{ old('profile.management_interface', $profile['management_interface'] ?? '') }}"
            placeholder="SSH / HTTPS / Console / API">
        </div>
      </div>
    </div>
  </div>

  <div class="col-12" data-asset-section="etc">
    <div class="card card-body border border-secondary-subtle">
      <h6 class="mb-3">Endpoint (ETC) Detail</h6>
      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <label class="form-label">Endpoint Type</label>
          <select name="profile[endpoint_type]" class="form-control @error('profile.endpoint_type') is-invalid @enderror">
            <option value="">Select Type</option>
            @foreach($options['endpoint_types'] as $option)
              <option value="{{ $option }}" {{ old('profile.endpoint_type', $profile['endpoint_type'] ?? '') === $option ? 'selected' : '' }}>
                {{ ucwords(str_replace('_', ' ', $option)) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Assigned User</label>
          <input
            type="text"
            name="profile[assigned_user]"
            class="form-control @error('profile.assigned_user') is-invalid @enderror"
            value="{{ old('profile.assigned_user', $profile['assigned_user'] ?? '') }}"
            placeholder="Nama pengguna device">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Department</label>
          <input
            type="text"
            name="profile[department]"
            class="form-control @error('profile.department') is-invalid @enderror"
            value="{{ old('profile.department', $profile['department'] ?? '') }}"
            placeholder="IT / Finance / Operations">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Device Serial Number</label>
          <input
            type="text"
            name="profile[device_serial_number]"
            class="form-control @error('profile.device_serial_number') is-invalid @enderror"
            value="{{ old('profile.device_serial_number', $profile['device_serial_number'] ?? '') }}"
            placeholder="SN-DEVICE">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Purchase Date</label>
          <input
            type="date"
            name="profile[purchase_date]"
            class="form-control @error('profile.purchase_date') is-invalid @enderror"
            value="{{ old('profile.purchase_date', $profile['purchase_date'] ?? '') }}">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Warranty End Date</label>
          <input
            type="date"
            name="profile[warranty_end_date]"
            class="form-control @error('profile.warranty_end_date') is-invalid @enderror"
            value="{{ old('profile.warranty_end_date', $profile['warranty_end_date'] ?? '') }}">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Device Condition</label>
          <select name="profile[device_condition]" class="form-control @error('profile.device_condition') is-invalid @enderror">
            <option value="">Select Condition</option>
            @foreach($options['device_conditions'] as $option)
              <option value="{{ $option }}" {{ old('profile.device_condition', $profile['device_condition'] ?? '') === $option ? 'selected' : '' }}>
                {{ ucfirst($option) }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12" data-asset-section="application_server">
    <div class="card card-body border border-primary-subtle">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <h6 class="mb-0">Hosted Services / Applications</h6>
          <small class="text-muted">Khusus Application Server.</small>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" data-add-service>
          <i class="fa fa-plus"></i> Add Service
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead>
            <tr>
              <th style="min-width: 160px;">Service Name</th>
              <th style="min-width: 140px;">Type</th>
              <th style="min-width: 170px;">Technology Stack</th>
              <th style="min-width: 120px;">Version</th>
              <th style="min-width: 120px;">Status</th>
              <th style="min-width: 100px;">Port</th>
              <th style="min-width: 200px;">Notes</th>
              <th style="width: 60px;">#</th>
            </tr>
          </thead>
          <tbody data-service-rows data-next-index="{{ count($servicesValue) }}">
            @foreach($servicesValue as $serviceIndex => $service)
              <tr data-service-row>
                <td>
                  <input type="text" name="services[{{ $serviceIndex }}][name]" class="form-control form-control-sm"
                    value="{{ $service['name'] ?? '' }}" placeholder="Payment API">
                </td>
                <td>
                  <select name="services[{{ $serviceIndex }}][type]" class="form-control form-control-sm">
                    @foreach($options['service_types'] as $option)
                      <option value="{{ $option }}" {{ ($service['type'] ?? 'application') === $option ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $option)) }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <input type="text" name="services[{{ $serviceIndex }}][technology_stack]" class="form-control form-control-sm"
                    value="{{ $service['technology_stack'] ?? '' }}" placeholder="Apache Tomcat / PostgreSQL / Vue.js">
                </td>
                <td>
                  <input type="text" name="services[{{ $serviceIndex }}][version]" class="form-control form-control-sm"
                    value="{{ $service['version'] ?? '' }}" placeholder="1.0.0">
                </td>
                <td>
                  <select name="services[{{ $serviceIndex }}][status]" class="form-control form-control-sm">
                    @foreach($options['service_statuses'] as $option)
                      <option value="{{ $option }}" {{ ($service['status'] ?? 'unknown') === $option ? 'selected' : '' }}>
                        {{ ucfirst($option) }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <input type="text" name="services[{{ $serviceIndex }}][port]" class="form-control form-control-sm"
                    value="{{ $service['port'] ?? '' }}" placeholder="8080">
                </td>
                <td>
                  <input type="text" name="services[{{ $serviceIndex }}][notes]" class="form-control form-control-sm"
                    value="{{ $service['notes'] ?? '' }}" placeholder="Optional note">
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-outline-danger btn-xs" data-remove-service>&times;</button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <label class="form-label">Last Seen At</label>
    <input
      type="datetime-local"
      name="last_seen_at"
      class="form-control @error('last_seen_at') is-invalid @enderror"
      value="{{ old('last_seen_at', isset($asset) && $asset->last_seen_at ? $asset->last_seen_at->format('Y-m-d\TH:i') : '') }}">
  </div>

  <div class="col-12 col-lg-8">
    <label class="form-label">Tags</label>
    <input
      type="text"
      name="tags"
      class="form-control @error('tags') is-invalid @enderror"
      value="{{ old('tags', $asset->tags ?? '') }}"
      placeholder="tier-1, core, compliance">
  </div>

  <div class="col-12">
    <label class="form-label">Notes</label>
    <textarea
      name="notes"
      class="form-control @error('notes') is-invalid @enderror"
      rows="3"
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
        placeholder="Jelaskan alasan perubahan">
    </div>
  @endif
</div>

<template id="service-row-template">
  <tr data-service-row>
    <td>
      <input type="text" name="services[__INDEX__][name]" class="form-control form-control-sm" placeholder="Payment API">
    </td>
    <td>
      <select name="services[__INDEX__][type]" class="form-control form-control-sm">
        @foreach($options['service_types'] as $option)
          <option value="{{ $option }}" {{ $option === 'application' ? 'selected' : '' }}>
            {{ ucwords(str_replace('_', ' ', $option)) }}
          </option>
        @endforeach
      </select>
    </td>
    <td>
      <input type="text" name="services[__INDEX__][technology_stack]" class="form-control form-control-sm" placeholder="Apache Tomcat / PostgreSQL / Vue.js">
    </td>
    <td>
      <input type="text" name="services[__INDEX__][version]" class="form-control form-control-sm" placeholder="1.0.0">
    </td>
    <td>
      <select name="services[__INDEX__][status]" class="form-control form-control-sm">
        @foreach($options['service_statuses'] as $option)
          <option value="{{ $option }}" {{ $option === 'unknown' ? 'selected' : '' }}>
            {{ ucfirst($option) }}
          </option>
        @endforeach
      </select>
    </td>
    <td>
      <input type="text" name="services[__INDEX__][port]" class="form-control form-control-sm" placeholder="8080">
    </td>
    <td>
      <input type="text" name="services[__INDEX__][notes]" class="form-control form-control-sm" placeholder="Optional note">
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-outline-danger btn-xs" data-remove-service>&times;</button>
    </td>
  </tr>
</template>

@once
  @push('scripts_body')
    <script>
      (function () {
        var typeSelect = document.querySelector('[data-asset-type]');
        var serverRoleInput = document.querySelector('[data-server-role-input]');
        var sectionElements = document.querySelectorAll('[data-asset-section]');
        var rowsContainer = document.querySelector('[data-service-rows]');
        var addButton = document.querySelector('[data-add-service]');
        var template = document.getElementById('service-row-template');

        if (!typeSelect) {
          return;
        }

        function normalizeType(value) {
          var map = {
            server: 'application_server',
            database: 'database_server',
            network: 'network_peripheral',
            endpoint: 'etc',
            storage: 'etc',
            other: 'etc'
          };
          return map[value] || value;
        }

        function isServerType(typeValue) {
          return typeValue === 'application_server' || typeValue === 'database_server';
        }

        function toggleSections() {
          var typeValue = normalizeType(typeSelect.value);

          sectionElements.forEach(function (element) {
            var sectionType = element.getAttribute('data-asset-section');
            var isVisible = false;

            if (sectionType === 'application') {
              isVisible = typeValue === 'application';
            } else if (sectionType === 'server') {
              isVisible = isServerType(typeValue);
            } else if (sectionType === 'application_server') {
              isVisible = typeValue === 'application_server';
            } else if (sectionType === 'database_server') {
              isVisible = typeValue === 'database_server';
            } else if (sectionType === 'network_peripheral') {
              isVisible = typeValue === 'network_peripheral';
            } else if (sectionType === 'etc') {
              isVisible = typeValue === 'etc';
            } else if (sectionType === 'non_application') {
              isVisible = typeValue !== 'application';
            }

            element.classList.toggle('d-none', !isVisible);
          });

          if (serverRoleInput) {
            if (typeValue === 'application_server') {
              serverRoleInput.value = 'application_server';
            } else if (typeValue === 'database_server') {
              serverRoleInput.value = 'database_server';
            } else {
              serverRoleInput.value = '';
            }
          }
        }

        if (rowsContainer && addButton && template) {
          function nextIndex() {
            var current = parseInt(rowsContainer.getAttribute('data-next-index') || '0', 10);
            rowsContainer.setAttribute('data-next-index', String(current + 1));
            return current;
          }

          addButton.addEventListener('click', function () {
            var index = nextIndex();
            rowsContainer.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(index)));
          });

          rowsContainer.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLElement) || !target.hasAttribute('data-remove-service')) {
              return;
            }

            var row = target.closest('[data-service-row]');
            if (!row) {
              return;
            }

            var rowCount = rowsContainer.querySelectorAll('[data-service-row]').length;
            if (rowCount <= 1) {
              row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
              row.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
              return;
            }
            row.remove();
          });
        }

        typeSelect.addEventListener('change', toggleSections);
        toggleSections();
      })();
    </script>
  @endpush
@endonce
