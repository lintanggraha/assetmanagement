<?php

namespace App\Http\Controllers;

use App\Asset;
use App\AssetActivityLog;
use App\AssetChangeRequest;
use App\AssetDiscoveryFinding;
use App\AssetPolicyViolation;
use App\AssetService;
use App\Bank;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    /**
     * Display asset inventory with filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $query = $this->assetQuery()->with('bank')->withCount('services');

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('asset_code', 'like', '%' . $keyword . '%')
                    ->orWhere('name', 'like', '%' . $keyword . '%')
                    ->orWhere('ip_address', 'like', '%' . $keyword . '%')
                    ->orWhere('hostname', 'like', '%' . $keyword . '%')
                    ->orWhere('operating_system', 'like', '%' . $keyword . '%')
                    ->orWhere('os_version', 'like', '%' . $keyword . '%')
                    ->orWhere('owner_name', 'like', '%' . $keyword . '%')
                    ->orWhere('tags', 'like', '%' . $keyword . '%');
            });
        }

        foreach (['asset_type', 'host_type', 'server_role', 'environment', 'criticality', 'status', 'source'] as $filter) {
            if ($request->filled($filter)) {
                $value = $request->{$filter};
                if ($filter === 'asset_type') {
                    $value = $this->normalizeAssetType($value);
                }
                $query->where($filter, $value);
            }
        }

        if ($request->filled('os_eol_state')) {
            if ($request->os_eol_state === 'expired') {
                $query->whereNotNull('os_eol_date')
                    ->whereDate('os_eol_date', '<', now()->toDateString());
            } elseif ($request->os_eol_state === 'next_90_days') {
                $query->whereNotNull('os_eol_date')
                    ->whereBetween('os_eol_date', [now()->toDateString(), now()->addDays(90)->toDateString()]);
            } elseif ($request->os_eol_state === 'next_180_days') {
                $query->whereNotNull('os_eol_date')
                    ->whereBetween('os_eol_date', [now()->toDateString(), now()->addDays(180)->toDateString()]);
            } elseif ($request->os_eol_state === 'missing') {
                $query->whereNull('os_eol_date');
            }
        }

        if ($request->filled('risk_min')) {
            $query->where('risk_score', '>=', (int) $request->risk_min);
        }

        if ($request->filled('risk_max')) {
            $query->where('risk_score', '<=', (int) $request->risk_max);
        }

        if ($request->filled('stale_days')) {
            $staleLimit = now()->subDays((int) $request->stale_days);
            $query->where(function ($staleQuery) use ($staleLimit) {
                $staleQuery->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $staleLimit);
            });
        }

        $assets = $query
            ->orderBy('risk_score', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(15)
            ->appends($request->query());

        $summaryBase = $this->assetQuery();
        $summary = [
            'total' => (clone $summaryBase)->count(),
            'active' => (clone $summaryBase)->where('status', 'active')->count(),
            'critical' => (clone $summaryBase)->whereIn('criticality', ['high', 'critical'])->count(),
            'high_risk' => (clone $summaryBase)->where('risk_score', '>=', 70)->count(),
            'open_violations' => $this->policyViolationQuery()->where('status', 'open')->count(),
            'pending_approvals' => $this->changeRequestScopeQuery()->where('status', 'pending')->count(),
        ];
        $eolNotification = $this->buildEolNotificationSummary(
            (clone $summaryBase)->select(['id', 'asset_code', 'name', 'asset_type', 'status', 'os_eol_date', 'asset_profile'])->get()
        );

        $filters = $request->only([
            'q',
            'asset_type',
            'host_type',
            'server_role',
            'environment',
            'criticality',
            'status',
            'source',
            'risk_min',
            'risk_max',
            'stale_days',
            'os_eol_state',
        ]);

        return view('assets.index', [
            'assets' => $assets,
            'summary' => $summary,
            'eolNotification' => $eolNotification,
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    /**
     * Show create asset form.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $this->ensureCanManageAssets();

        return view('assets.create', [
            'banks' => Bank::orderBy('nama')->get(),
            'options' => $this->options(),
        ]);
    }

    /**
     * Store a newly created asset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->ensureCanManageAssets();

        $payload = $this->validatedPayload($request);
        $servicesPayload = $this->normalizeServices($payload['services'] ?? []);
        unset($payload['services']);
        $payload = $this->normalizeAssetPayload($payload);
        if (!$this->isServerType($payload['asset_type'])) {
            $servicesPayload = [];
        }

        $payload['asset_code'] = $this->createAssetCode();
        $payload['user_id'] = Auth::id();
        $payload['source'] = $this->normalizeSource($payload['source'] ?? 'manual');
        $payload['discovery_confidence'] = $payload['discovery_confidence'] ?? ($payload['source'] === 'manual' ? 100 : 90);
        $payload['tags'] = $this->normalizeTags($payload['tags'] ?? null);
        $payload['last_seen_at'] = $this->normalizeDateTime($payload['last_seen_at'] ?? null);
        $payload['os_eol_date'] = $this->normalizeDate($payload['os_eol_date'] ?? null);
        $payload['criticality'] = $this->deriveCriticalityFromEol(
            $payload['asset_type'],
            $payload['os_eol_date'],
            $payload['asset_profile'] ?? [],
            'medium'
        );
        $payload['risk_score'] = $this->calculateRiskScore(
            $payload['criticality'],
            $payload['status'],
            $payload['discovery_confidence'],
            $payload['last_seen_at']
        );

        $asset = Asset::create($payload);
        if ($this->isServerType($asset->asset_type)) {
            $this->syncServices($asset, $servicesPayload);
        }

        $this->trackActivity($asset->id, 'created', 'Asset created manually', [
            'source' => $asset->source,
            'criticality' => $asset->criticality,
            'risk_score' => $asset->risk_score,
        ]);

        return redirect()->route('assets.show', $asset->id)
            ->with('success', 'Asset berhasil ditambahkan ke inventory.');
    }

    /**
     * Display a specific asset.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $asset = $this->scopedAsset($id);
        $activityLogs = AssetActivityLog::with('actor')
            ->where('asset_id', $asset->id)
            ->orderBy('created_at', 'desc')
            ->limit(25)
            ->get();

        $latestFindings = AssetDiscoveryFinding::with('run')
            ->where('asset_id', $asset->id)
            ->orderBy('created_at', 'desc')
            ->limit(25)
            ->get();

        $pendingRequests = AssetChangeRequest::with('requester')
            ->where('asset_id', $asset->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $openViolations = AssetPolicyViolation::where('asset_id', $asset->id)
            ->where('status', 'open')
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('assets.show', compact(
            'asset',
            'activityLogs',
            'latestFindings',
            'pendingRequests',
            'openViolations'
        ));
    }

    /**
     * Show edit form.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $this->ensureCanManageAssets();
        $asset = $this->scopedAsset($id);

        return view('assets.edit', [
            'asset' => $asset,
            'banks' => Bank::orderBy('nama')->get(),
            'options' => $this->options(),
        ]);
    }

    /**
     * Update the specified asset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $this->ensureCanManageAssets();
        $asset = $this->scopedAsset($id);

        $payload = $this->validatedPayload($request);
        $servicesPayload = $this->normalizeServices($payload['services'] ?? []);
        unset($payload['services']);
        $payload = $this->normalizeAssetPayload($payload);
        if (!$this->isServerType($payload['asset_type'])) {
            $servicesPayload = [];
        }

        $payload['tags'] = $this->normalizeTags($payload['tags'] ?? null);
        $payload['last_seen_at'] = $this->normalizeDateTime($payload['last_seen_at'] ?? null);
        $payload['os_eol_date'] = $this->normalizeDate($payload['os_eol_date'] ?? null);
        $payload['source'] = $this->normalizeSource($payload['source'] ?? $asset->source ?? 'manual');
        $payload['discovery_confidence'] = $payload['discovery_confidence'] ?? $asset->discovery_confidence;
        $payload['criticality'] = $this->deriveCriticalityFromEol(
            $payload['asset_type'],
            $payload['os_eol_date'],
            $payload['asset_profile'] ?? [],
            'medium'
        );
        $payload['risk_score'] = $this->calculateRiskScore(
            $payload['criticality'],
            $payload['status'],
            $payload['discovery_confidence'],
            $payload['last_seen_at']
        );

        $asset->fill($payload);
        $changedFields = array_values(array_filter(array_keys($asset->getDirty()), function ($field) {
            return $field !== 'updated_at';
        }));
        $servicesChanged = $this->servicesChanged($asset, $servicesPayload);

        if (empty($changedFields) && !$servicesChanged) {
            return redirect()->route('assets.show', $asset->id)
                ->with('success', 'Tidak ada perubahan data pada asset.');
        }

        $sensitiveChanged = array_intersect($changedFields, [
            'asset_type',
            'asset_profile',
            'criticality',
            'status',
            'lifecycle_stage',
            'owner_name',
            'owner_email',
            'bank_id',
            'host_type',
            'server_role',
            'operating_system',
            'os_version',
            'os_eol_date',
        ]);

        if (!Auth::user()->canApproveAssetChanges() && (!empty($sensitiveChanged) || $servicesChanged)) {
            $requestedPayload = array_merge($payload, [
                'services' => $servicesPayload,
            ]);

            $changeRequest = $this->createChangeRequest(
                $asset,
                'update_sensitive',
                $requestedPayload,
                $request->input('approval_reason'),
                [
                    'changed_fields' => array_values($sensitiveChanged),
                    'services_changed' => $servicesChanged,
                ]
            );

            return redirect()->route('assets.show', $asset->id)->with(
                'success',
                'Perubahan sensitif dikirim untuk approval. Request #' . $changeRequest->id . ' menunggu persetujuan.'
            );
        }

        $asset->save();
        if ($servicesChanged) {
            $this->syncServices($asset, $servicesPayload);
            $changedFields[] = 'services';
        }

        $this->trackActivity($asset->id, 'updated', 'Asset updated', [
            'changed_fields' => implode(', ', $changedFields),
            'risk_score' => $asset->risk_score,
        ]);

        return redirect()->route('assets.show', $asset->id)
            ->with('success', 'Asset berhasil diperbarui.');
    }

    /**
     * Retire asset from active inventory.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        $this->ensureCanManageAssets();
        $asset = $this->scopedAsset($id);

        if ($asset->status === 'retired') {
            return redirect()->route('assets.show', $asset->id)->with('success', 'Asset sudah berstatus retired.');
        }

        if (!Auth::user()->canApproveAssetChanges()) {
            $changeRequest = $this->createChangeRequest(
                $asset,
                'retire_asset',
                [
                    'status' => 'retired',
                    'lifecycle_stage' => 'retired',
                ],
                $request->input('approval_reason'),
                ['requested_by' => Auth::id()]
            );

            return redirect()->route('assets.show', $asset->id)->with(
                'success',
                'Permintaan retire dikirim untuk approval. Request #' . $changeRequest->id . ' menunggu persetujuan.'
            );
        }

        $asset->update([
            'status' => 'retired',
            'lifecycle_stage' => 'retired',
            'risk_score' => $this->calculateRiskScore(
                $asset->criticality,
                'retired',
                $asset->discovery_confidence,
                $asset->last_seen_at
            ),
        ]);

        $this->trackActivity($asset->id, 'retired', 'Asset marked as retired', [
            'status' => $asset->status,
            'lifecycle_stage' => $asset->lifecycle_stage,
        ]);

        return redirect()->route('assets.index')->with('success', 'Asset dipindahkan ke status retired.');
    }

    /**
     * Build options for select inputs.
     *
     * @return array<string, array<int, string>>
     */
    private function options()
    {
        return [
            'asset_types' => ['application', 'application_server', 'database_server', 'network_peripheral', 'etc'],
            'legacy_asset_types' => ['database', 'server', 'network', 'storage', 'endpoint', 'other'],
            'host_types' => ['physical', 'virtual_machine', 'container', 'cloud_instance', 'other'],
            'server_roles' => ['application_server', 'database_server'],
            'environments' => ['production', 'staging', 'development', 'uat', 'dr', 'other'],
            'criticalities' => ['low', 'medium', 'high', 'critical'],
            'statuses' => ['active', 'maintenance', 'inactive', 'unknown', 'retired'],
            'lifecycle_stages' => ['onboarding', 'operational', 'degraded', 'sunset', 'retired'],
            'sources' => ['manual', 'import'],
            'os_eol_states' => ['expired', 'next_90_days', 'next_180_days', 'missing'],
            'service_types' => ['application', 'database', 'proxy', 'queue', 'cache', 'api', 'frontend', 'other'],
            'service_statuses' => ['running', 'degraded', 'down', 'maintenance', 'unknown'],
            'application_categories' => ['desktop', 'web', 'mobile', 'other'],
            'application_statuses' => ['used', 'not_used'],
            'db_license_types' => ['enterprise', 'community'],
            'peripheral_types' => ['router', 'switch', 'firewall', 'load_balancer', 'wireless_ap', 'modem', 'other'],
            'endpoint_types' => ['desktop', 'laptop', 'thin_client', 'mini_pc', 'other'],
            'device_conditions' => ['good', 'fair', 'poor', 'retired'],
        ];
    }

    /**
     * Validate request payload for create/update.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request)
    {
        $options = $this->options();
        $validAssetTypes = array_unique(array_merge($options['asset_types'], $options['legacy_asset_types']));

        return $request->validate([
            'name' => 'required|string|max:150',
            'asset_type' => 'required|in:' . implode(',', $validAssetTypes),
            'host_type' => 'nullable|in:' . implode(',', $options['host_types']) . '|required_if:asset_type,application_server,database_server,server',
            'server_role' => 'nullable|in:' . implode(',', $options['server_roles']),
            'environment' => 'required|in:' . implode(',', $options['environments']),
            'criticality' => 'nullable|in:' . implode(',', $options['criticalities']),
            'status' => 'required|in:' . implode(',', $options['statuses']),
            'lifecycle_stage' => 'required|in:' . implode(',', $options['lifecycle_stages']),
            'owner_name' => 'nullable|string|max:120',
            'owner_email' => 'nullable|email|max:120',
            'bank_id' => 'nullable|exists:bank,id',
            'ip_address' => 'nullable|string|max:100',
            'hostname' => 'nullable|string|max:120',
            'operating_system' => 'nullable|string|max:120|required_if:asset_type,application_server,database_server,server',
            'os_version' => 'nullable|string|max:120',
            'os_eol_date' => 'nullable|date',
            'port' => 'nullable|string|max:50',
            'source' => 'nullable|in:' . implode(',', $options['sources']),
            'discovery_confidence' => 'nullable|integer|min:0|max:100',
            'tags' => 'nullable|string|max:255',
            'last_seen_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'services' => 'nullable|array|max:20',
            'services.*.name' => 'nullable|string|max:150',
            'services.*.type' => 'nullable|in:' . implode(',', $options['service_types']),
            'services.*.technology_stack' => 'nullable|string|max:150',
            'services.*.version' => 'nullable|string|max:80',
            'services.*.status' => 'nullable|in:' . implode(',', $options['service_statuses']),
            'services.*.port' => 'nullable|string|max:50',
            'services.*.notes' => 'nullable|string|max:500',
            'profile' => 'nullable|array',
            'profile.application_runtime' => 'nullable|string|max:120|required_if:asset_type,application',
            'profile.application_category' => 'nullable|in:' . implode(',', $options['application_categories']) . '|required_if:asset_type,application',
            'profile.application_version' => 'nullable|string|max:80',
            'profile.first_release_date' => 'nullable|date',
            'profile.last_release_date' => 'nullable|date',
            'profile.application_status' => 'nullable|in:' . implode(',', $options['application_statuses']) . '|required_if:asset_type,application',
            'profile.db_license_type' => 'nullable|in:' . implode(',', $options['db_license_types']) . '|required_if:asset_type,database_server,database',
            'profile.db_license_eol_date' => 'nullable|date',
            'profile.peripheral_type' => 'nullable|in:' . implode(',', $options['peripheral_types']) . '|required_if:asset_type,network_peripheral,network',
            'profile.vendor' => 'nullable|string|max:120',
            'profile.model' => 'nullable|string|max:120',
            'profile.serial_number' => 'nullable|string|max:120',
            'profile.firmware_version' => 'nullable|string|max:80',
            'profile.firmware_eol_date' => 'nullable|date',
            'profile.support_contract_end_date' => 'nullable|date',
            'profile.management_interface' => 'nullable|string|max:120',
            'profile.endpoint_type' => 'nullable|in:' . implode(',', $options['endpoint_types']) . '|required_if:asset_type,etc,endpoint,storage,other',
            'profile.assigned_user' => 'nullable|string|max:120',
            'profile.department' => 'nullable|string|max:120',
            'profile.device_serial_number' => 'nullable|string|max:120',
            'profile.purchase_date' => 'nullable|date',
            'profile.warranty_end_date' => 'nullable|date',
            'profile.device_condition' => 'nullable|in:' . implode(',', $options['device_conditions']),
            'approval_reason' => 'nullable|string|max:255',
        ]);
    }

    /**
     * Ensure current user can manage assets.
     *
     * @return void
     */
    private function ensureCanManageAssets()
    {
        if (!Auth::user()->canManageAssetRecords()) {
            abort(403, 'Current role cannot manage assets.');
        }
    }

    /**
     * Build scoped query based on role visibility.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function assetQuery()
    {
        $query = Asset::query();
        if (!Auth::user()->hasGlobalAssetVisibility()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    /**
     * Build scoped policy violation query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function policyViolationQuery()
    {
        $query = AssetPolicyViolation::query();
        if (!Auth::user()->hasGlobalAssetVisibility()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    /**
     * Build scoped change request query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function changeRequestScopeQuery()
    {
        $query = AssetChangeRequest::query();
        if (!Auth::user()->canApproveAssetChanges()) {
            $query->where('requester_user_id', Auth::id());
        }

        return $query;
    }

    /**
     * Find asset respecting visibility scope.
     *
     * @param  int  $id
     * @return \App\Asset
     */
    private function scopedAsset($id)
    {
        return $this->assetQuery()
            ->with(['bank', 'services'])
            ->findOrFail($id);
    }

    /**
     * Create an approval request.
     *
     * @param  \App\Asset  $asset
     * @param  string  $changeType
     * @param  array<string, mixed>  $requestedPayload
     * @param  string|null  $reason
     * @param  array<string, mixed>  $meta
     * @return \App\AssetChangeRequest
     */
    private function createChangeRequest(Asset $asset, $changeType, array $requestedPayload, $reason = null, array $meta = [])
    {
        $existingPending = AssetChangeRequest::where('asset_id', $asset->id)
            ->where('change_type', $changeType)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return $existingPending;
        }

        $snapshot = $asset->only([
            'name',
            'asset_type',
            'host_type',
            'server_role',
            'environment',
            'criticality',
            'status',
            'lifecycle_stage',
            'owner_name',
            'owner_email',
            'bank_id',
            'ip_address',
            'hostname',
            'operating_system',
            'os_version',
            'os_eol_date',
            'port',
            'risk_score',
            'discovery_confidence',
            'asset_profile',
        ]);
        $snapshot['services'] = $asset->services()
            ->orderBy('id')
            ->get(['service_name', 'service_type', 'technology_stack', 'version', 'status', 'port', 'is_primary', 'notes'])
            ->map(function ($service) {
                return [
                    'name' => $service->service_name,
                    'type' => $service->service_type,
                    'technology_stack' => $service->technology_stack,
                    'version' => $service->version,
                    'status' => $service->status,
                    'port' => $service->port,
                    'is_primary' => (bool) $service->is_primary,
                    'notes' => $service->notes,
                ];
            })
            ->values()
            ->all();

        $payload = array_merge($requestedPayload, [
            'meta' => $meta,
        ]);

        $changeRequest = AssetChangeRequest::create([
            'asset_id' => $asset->id,
            'requester_user_id' => Auth::id(),
            'change_type' => $changeType,
            'status' => 'pending',
            'reason' => $reason,
            'current_snapshot' => json_encode($snapshot),
            'requested_payload' => json_encode($payload),
        ]);

        $this->trackActivity($asset->id, 'change_requested', 'Asset change request submitted', [
            'request_id' => $changeRequest->id,
            'change_type' => $changeType,
            'reason' => $reason,
        ]);

        return $changeRequest;
    }

    /**
     * Normalize tags input.
     *
     * @param  string|null  $tags
     * @return string|null
     */
    private function normalizeTags($tags)
    {
        if (!$tags) {
            return null;
        }

        $chunks = collect(explode(',', $tags))
            ->map(function ($item) {
                return trim($item);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($chunks) ? null : implode(', ', $chunks);
    }

    /**
     * Normalize datetime input.
     *
     * @param  string|null  $value
     * @return string|null
     */
    private function normalizeDateTime($value)
    {
        if (!$value) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime($value));
    }

    /**
     * Normalize date input.
     *
     * @param  string|null  $value
     * @return string|null
     */
    private function normalizeDate($value)
    {
        if (!$value) {
            return null;
        }

        $parsed = strtotime($value);
        return $parsed ? date('Y-m-d', $parsed) : null;
    }

    /**
     * Normalize source value to supported set.
     *
     * @param  string|null  $source
     * @return string
     */
    private function normalizeSource($source)
    {
        $source = Str::lower(trim((string) $source));
        return $source === 'manual' ? 'manual' : 'import';
    }

    /**
     * Derive criticality strictly from nearest EOL date.
     *
     * @param  string  $assetType
     * @param  mixed  $osEolDate
     * @param  array<string, mixed>  $assetProfile
     * @param  string  $fallback
     * @return string
     */
    private function deriveCriticalityFromEol($assetType, $osEolDate, array $assetProfile, $fallback = 'medium')
    {
        $daysCandidates = [];

        $osDays = $this->daysUntilDate($osEolDate);
        if ($osDays !== null) {
            $daysCandidates[] = $osDays;
        }

        $licenseEolDate = $this->databaseLicenseEolDate($assetType, $assetProfile);
        $licenseDays = $this->daysUntilDate($licenseEolDate);
        if ($licenseDays !== null) {
            $daysCandidates[] = $licenseDays;
        }

        if (empty($daysCandidates)) {
            return $fallback;
        }

        $nearestDays = min($daysCandidates);
        if ($nearestDays < 0) {
            return 'critical';
        }
        if ($nearestDays <= 30) {
            return 'high';
        }
        if ($nearestDays <= 90) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Build EOL alert summary for inventory/dashboard notifications.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Asset>  $assets
     * @return array<string, mixed>
     */
    private function buildEolNotificationSummary($assets)
    {
        $summary = [
            'os_expired' => 0,
            'os_near' => 0,
            'license_expired' => 0,
            'license_near' => 0,
            'total_alerts' => 0,
            'items' => [],
        ];

        foreach ($assets as $asset) {
            if (($asset->status ?? null) === 'retired') {
                continue;
            }

            $profile = is_array($asset->asset_profile) ? $asset->asset_profile : [];

            $osDays = $this->daysUntilDate($asset->os_eol_date ?? null);
            if ($osDays !== null && $osDays <= 90) {
                $isExpired = $osDays < 0;
                $summary[$isExpired ? 'os_expired' : 'os_near']++;
                $summary['items'][] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'asset_code' => $asset->asset_code,
                    'target' => 'OS',
                    'state' => $isExpired ? 'expired' : 'near',
                    'days_left' => $osDays,
                    'eol_date' => $this->normalizeDate($asset->os_eol_date),
                ];
            }

            $licenseEol = $this->databaseLicenseEolDate($asset->asset_type ?? null, $profile);
            $licenseDays = $this->daysUntilDate($licenseEol);
            if ($licenseDays !== null && $licenseDays <= 90) {
                $isExpired = $licenseDays < 0;
                $summary[$isExpired ? 'license_expired' : 'license_near']++;
                $summary['items'][] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'asset_code' => $asset->asset_code,
                    'target' => 'License',
                    'state' => $isExpired ? 'expired' : 'near',
                    'days_left' => $licenseDays,
                    'eol_date' => $licenseEol,
                ];
            }
        }

        usort($summary['items'], function ($left, $right) {
            $leftRank = $left['state'] === 'expired' ? 0 : 1;
            $rightRank = $right['state'] === 'expired' ? 0 : 1;

            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }

            return $left['days_left'] <=> $right['days_left'];
        });

        $summary['total_alerts'] = count($summary['items']);
        $summary['items'] = array_slice($summary['items'], 0, 8);

        return $summary;
    }

    /**
     * Get database license EOL date if asset type is database server.
     *
     * @param  string|null  $assetType
     * @param  array<string, mixed>  $assetProfile
     * @return string|null
     */
    private function databaseLicenseEolDate($assetType, array $assetProfile)
    {
        $assetType = $this->normalizeAssetType((string) $assetType);
        if ($assetType !== 'database_server') {
            return null;
        }

        return $this->normalizeDate($assetProfile['db_license_eol_date'] ?? null);
    }

    /**
     * Calculate day delta to date (negative means expired).
     *
     * @param  mixed  $dateValue
     * @return int|null
     */
    private function daysUntilDate($dateValue)
    {
        if (!$dateValue) {
            return null;
        }

        if ($dateValue instanceof \DateTimeInterface) {
            $dateValue = $dateValue->format('Y-m-d');
        }

        $normalized = $this->normalizeDate($dateValue);
        if (!$normalized) {
            return null;
        }

        return now()->startOfDay()->diffInDays($normalized, false);
    }

    /**
     * Normalize asset payload by target type.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeAssetPayload(array $payload)
    {
        $assetType = $this->normalizeAssetType($payload['asset_type'] ?? 'etc');
        $profile = isset($payload['profile']) && is_array($payload['profile']) ? $payload['profile'] : [];

        $payload['asset_type'] = $assetType;
        $payload['asset_profile'] = $this->normalizeAssetProfile($assetType, $profile);
        unset($payload['profile']);

        if ($assetType === 'application_server') {
            $payload['server_role'] = 'application_server';
        } elseif ($assetType === 'database_server') {
            $payload['server_role'] = 'database_server';
        } else {
            $payload['host_type'] = null;
            $payload['server_role'] = null;
            $payload['operating_system'] = null;
            $payload['os_version'] = null;
            $payload['os_eol_date'] = null;
        }

        if ($assetType === 'application') {
            $payload['ip_address'] = null;
            $payload['hostname'] = null;
            $payload['port'] = null;
        }

        return $payload;
    }

    /**
     * Normalize custom profile based on type.
     *
     * @param  string  $assetType
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function normalizeAssetProfile($assetType, array $profile)
    {
        if ($assetType === 'application') {
            return [
                'application_runtime' => $this->nullableTrim($profile['application_runtime'] ?? null),
                'application_category' => $this->enumValue(
                    $profile['application_category'] ?? 'other',
                    ['desktop', 'web', 'mobile', 'other'],
                    'other'
                ),
                'application_version' => $this->nullableTrim($profile['application_version'] ?? null),
                'first_release_date' => $this->normalizeDate($profile['first_release_date'] ?? null),
                'last_release_date' => $this->normalizeDate($profile['last_release_date'] ?? null),
                'application_status' => $this->enumValue(
                    $profile['application_status'] ?? 'used',
                    ['used', 'not_used'],
                    'used'
                ),
            ];
        }

        if ($assetType === 'network_peripheral') {
            return [
                'peripheral_type' => $this->enumValue(
                    $profile['peripheral_type'] ?? 'other',
                    ['router', 'switch', 'firewall', 'load_balancer', 'wireless_ap', 'modem', 'other'],
                    'other'
                ),
                'vendor' => $this->nullableTrim($profile['vendor'] ?? null),
                'model' => $this->nullableTrim($profile['model'] ?? null),
                'serial_number' => $this->nullableTrim($profile['serial_number'] ?? null),
                'firmware_version' => $this->nullableTrim($profile['firmware_version'] ?? null),
                'firmware_eol_date' => $this->normalizeDate($profile['firmware_eol_date'] ?? null),
                'support_contract_end_date' => $this->normalizeDate($profile['support_contract_end_date'] ?? null),
                'management_interface' => $this->nullableTrim($profile['management_interface'] ?? null),
            ];
        }

        if ($assetType === 'database_server') {
            $licenseType = Str::lower((string) $this->nullableTrim($profile['db_license_type'] ?? null));
            if (!in_array($licenseType, ['enterprise', 'community'], true)) {
                $licenseType = null;
            }

            return [
                'db_license_type' => $licenseType,
                'db_license_eol_date' => $this->normalizeDate($profile['db_license_eol_date'] ?? null),
            ];
        }

        if ($assetType === 'application_server') {
            return [];
        }

        return [
            'endpoint_type' => $this->enumValue(
                $profile['endpoint_type'] ?? 'desktop',
                ['desktop', 'laptop', 'thin_client', 'mini_pc', 'other'],
                'desktop'
            ),
            'assigned_user' => $this->nullableTrim($profile['assigned_user'] ?? null),
            'department' => $this->nullableTrim($profile['department'] ?? null),
            'device_serial_number' => $this->nullableTrim($profile['device_serial_number'] ?? null),
            'purchase_date' => $this->normalizeDate($profile['purchase_date'] ?? null),
            'warranty_end_date' => $this->normalizeDate($profile['warranty_end_date'] ?? null),
            'device_condition' => $this->enumValue(
                $profile['device_condition'] ?? 'good',
                ['good', 'fair', 'poor', 'retired'],
                'good'
            ),
        ];
    }

    /**
     * Normalize service rows from request.
     *
     * @param  array<int, array<string, mixed>>  $services
     * @return array<int, array<string, mixed>>
     */
    private function normalizeServices(array $services)
    {
        $options = $this->options();
        $defaultType = $options['service_types'][0];
        $defaultStatus = $options['service_statuses'][count($options['service_statuses']) - 1];

        return collect($services)
            ->map(function ($service) use ($options, $defaultType, $defaultStatus) {
                $name = trim((string) ($service['name'] ?? ''));
                if ($name === '') {
                    return null;
                }

                $type = trim((string) ($service['type'] ?? $defaultType));
                if (!in_array($type, $options['service_types'], true)) {
                    $type = $defaultType;
                }

                $status = trim((string) ($service['status'] ?? $defaultStatus));
                if (!in_array($status, $options['service_statuses'], true)) {
                    $status = $defaultStatus;
                }

                return [
                    'service_name' => $name,
                    'service_type' => $type,
                    'technology_stack' => $this->nullableTrim($service['technology_stack'] ?? null),
                    'version' => $this->nullableTrim($service['version'] ?? null),
                    'status' => $status,
                    'port' => $this->nullableTrim($service['port'] ?? null),
                    'notes' => $this->nullableTrim($service['notes'] ?? null),
                    'is_primary' => false,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Compare incoming services with current persisted services.
     *
     * @param  \App\Asset  $asset
     * @param  array<int, array<string, mixed>>  $incomingServices
     * @return bool
     */
    private function servicesChanged(Asset $asset, array $incomingServices)
    {
        $currentServices = $asset->services()
            ->orderBy('id')
            ->get(['service_name', 'service_type', 'technology_stack', 'version', 'status', 'port', 'notes', 'is_primary'])
            ->map(function ($service) {
                return [
                    'service_name' => $service->service_name,
                    'service_type' => $service->service_type,
                    'technology_stack' => $service->technology_stack,
                    'version' => $service->version,
                    'status' => $service->status,
                    'port' => $service->port,
                    'notes' => $service->notes,
                    'is_primary' => (bool) $service->is_primary,
                ];
            })
            ->all();

        return $this->serviceSignature($currentServices) !== $this->serviceSignature($incomingServices);
    }

    /**
     * Build deterministic service signature.
     *
     * @param  array<int, array<string, mixed>>  $services
     * @return string
     */
    private function serviceSignature(array $services)
    {
        $normalized = collect($services)
            ->map(function ($service) {
                return [
                    'service_name' => (string) ($service['service_name'] ?? ''),
                    'service_type' => (string) ($service['service_type'] ?? ''),
                    'technology_stack' => (string) ($service['technology_stack'] ?? ''),
                    'version' => (string) ($service['version'] ?? ''),
                    'status' => (string) ($service['status'] ?? ''),
                    'port' => (string) ($service['port'] ?? ''),
                    'notes' => (string) ($service['notes'] ?? ''),
                    'is_primary' => (bool) ($service['is_primary'] ?? false),
                ];
            })
            ->sortBy(function ($row) {
                return implode('|', [
                    $row['service_name'],
                    $row['service_type'],
                    $row['technology_stack'],
                    $row['version'],
                    $row['status'],
                    $row['port'],
                    $row['notes'],
                    $row['is_primary'] ? '1' : '0',
                ]);
            })
            ->values()
            ->all();

        return sha1(json_encode($normalized));
    }

    /**
     * Replace asset services with current payload.
     *
     * @param  \App\Asset  $asset
     * @param  array<int, array<string, mixed>>  $services
     * @return void
     */
    private function syncServices(Asset $asset, array $services)
    {
        $asset->services()->delete();
        if (empty($services)) {
            return;
        }

        foreach ($services as $index => $service) {
            AssetService::create([
                'asset_id' => $asset->id,
                'service_name' => $service['service_name'],
                'service_type' => $service['service_type'],
                'technology_stack' => $service['technology_stack'],
                'version' => $service['version'],
                'status' => $service['status'],
                'port' => $service['port'],
                'notes' => $service['notes'],
                'is_primary' => $index === 0,
            ]);
        }
    }

    /**
     * Normalize legacy and current asset types.
     *
     * @param  string  $assetType
     * @return string
     */
    private function normalizeAssetType($assetType)
    {
        $assetType = Str::lower(trim((string) $assetType));

        $mapping = [
            'application' => 'application',
            'application_server' => 'application_server',
            'server' => 'application_server',
            'database_server' => 'database_server',
            'database' => 'database_server',
            'network_peripheral' => 'network_peripheral',
            'network' => 'network_peripheral',
            'etc' => 'etc',
            'endpoint' => 'etc',
            'storage' => 'etc',
            'other' => 'etc',
        ];

        return $mapping[$assetType] ?? 'etc';
    }

    /**
     * Determine whether target type can host service mappings.
     *
     * @param  string  $assetType
     * @return bool
     */
    private function isServerType($assetType)
    {
        return $assetType === 'application_server';
    }

    /**
     * Normalize enum values.
     *
     * @param  mixed  $value
     * @param  array<int, string>  $allowed
     * @param  string  $fallback
     * @return string
     */
    private function enumValue($value, array $allowed, $fallback)
    {
        $value = Str::lower(trim((string) $value));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    /**
     * Normalize nullable string.
     *
     * @param  mixed  $value
     * @return string|null
     */
    private function nullableTrim($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /**
     * Calculate dynamic risk score.
     *
     * @param  string  $criticality
     * @param  string  $status
     * @param  int  $confidence
     * @param  string|null  $lastSeenAt
     * @return int
     */
    private function calculateRiskScore($criticality, $status, $confidence, $lastSeenAt)
    {
        $base = [
            'low' => 25,
            'medium' => 45,
            'high' => 70,
            'critical' => 90,
        ];

        $statusModifier = [
            'active' => 0,
            'maintenance' => 6,
            'inactive' => 12,
            'unknown' => 15,
            'retired' => -30,
        ];

        $score = $base[$criticality] ?? 45;
        $score += $statusModifier[$status] ?? 0;

        if ((int) $confidence < 70) {
            $score += (int) round((70 - (int) $confidence) / 2);
        }

        if ($lastSeenAt) {
            $lastSeenDays = now()->diffInDays($lastSeenAt);
            if ($lastSeenDays > 90) {
                $score += 20;
            } elseif ($lastSeenDays > 30) {
                $score += 10;
            }
        } else {
            $score += 8;
        }

        if ($status === 'retired') {
            return max(0, min(30, (int) $score));
        }

        return max(1, min(100, (int) $score));
    }

    /**
     * Generate unique asset code.
     *
     * @return string
     */
    private function createAssetCode()
    {
        do {
            $code = 'AST-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (Asset::where('asset_code', $code)->exists());

        return $code;
    }

    /**
     * Write audit activity record.
     *
     * @param  int  $assetId
     * @param  string  $action
     * @param  string  $message
     * @param  array<string, mixed>  $context
     * @return void
     */
    private function trackActivity($assetId, $action, $message, array $context = [])
    {
        AssetActivityLog::create([
            'asset_id' => $assetId,
            'actor_user_id' => Auth::id(),
            'action' => $action,
            'message' => $message,
            'context' => empty($context) ? null : json_encode($context),
        ]);
    }
}

