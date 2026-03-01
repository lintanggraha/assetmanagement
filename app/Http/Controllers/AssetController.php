<?php

namespace App\Http\Controllers;

use App\Asset;
use App\AssetActivityLog;
use App\AssetChangeRequest;
use App\AssetDiscoveryFinding;
use App\AssetPolicyViolation;
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
        $query = $this->assetQuery()->with('bank');

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('asset_code', 'like', '%' . $keyword . '%')
                    ->orWhere('name', 'like', '%' . $keyword . '%')
                    ->orWhere('ip_address', 'like', '%' . $keyword . '%')
                    ->orWhere('hostname', 'like', '%' . $keyword . '%')
                    ->orWhere('owner_name', 'like', '%' . $keyword . '%')
                    ->orWhere('tags', 'like', '%' . $keyword . '%');
            });
        }

        foreach (['asset_type', 'environment', 'criticality', 'status', 'source'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->{$filter});
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

        $filters = $request->only([
            'q',
            'asset_type',
            'environment',
            'criticality',
            'status',
            'source',
            'risk_min',
            'risk_max',
            'stale_days',
        ]);

        return view('assets.index', [
            'assets' => $assets,
            'summary' => $summary,
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
        $payload['asset_code'] = $this->createAssetCode();
        $payload['user_id'] = Auth::id();
        $payload['source'] = $payload['source'] ?? 'manual';
        $payload['discovery_confidence'] = $payload['discovery_confidence'] ?? ($payload['source'] === 'manual' ? 100 : 80);
        $payload['tags'] = $this->normalizeTags($payload['tags'] ?? null);
        $payload['last_seen_at'] = $this->normalizeDateTime($payload['last_seen_at'] ?? null);
        $payload['risk_score'] = $this->calculateRiskScore(
            $payload['criticality'],
            $payload['status'],
            $payload['discovery_confidence'],
            $payload['last_seen_at']
        );

        $asset = Asset::create($payload);

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
        $payload['tags'] = $this->normalizeTags($payload['tags'] ?? null);
        $payload['last_seen_at'] = $this->normalizeDateTime($payload['last_seen_at'] ?? null);
        $payload['discovery_confidence'] = $payload['discovery_confidence'] ?? $asset->discovery_confidence;
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

        if (empty($changedFields)) {
            return redirect()->route('assets.show', $asset->id)
                ->with('success', 'Tidak ada perubahan data pada asset.');
        }

        $sensitiveChanged = array_intersect($changedFields, [
            'criticality',
            'status',
            'lifecycle_stage',
            'owner_name',
            'owner_email',
            'bank_id',
        ]);

        if (!Auth::user()->canApproveAssetChanges() && !empty($sensitiveChanged)) {
            $changeRequest = $this->createChangeRequest(
                $asset,
                'update_sensitive',
                $payload,
                $request->input('approval_reason'),
                ['changed_fields' => array_values($sensitiveChanged)]
            );

            return redirect()->route('assets.show', $asset->id)->with(
                'success',
                'Perubahan sensitif dikirim untuk approval. Request #' . $changeRequest->id . ' menunggu persetujuan.'
            );
        }

        $asset->save();

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
            'asset_types' => ['application', 'database', 'server', 'network', 'storage', 'endpoint', 'other'],
            'environments' => ['production', 'staging', 'development', 'uat', 'dr', 'other'],
            'criticalities' => ['low', 'medium', 'high', 'critical'],
            'statuses' => ['active', 'maintenance', 'inactive', 'unknown', 'retired'],
            'lifecycle_stages' => ['onboarding', 'operational', 'degraded', 'sunset', 'retired'],
            'sources' => ['manual', 'discovery', 'catalog_sync', 'manual_seed', 'import', 'sync'],
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

        return $request->validate([
            'name' => 'required|string|max:150',
            'asset_type' => 'required|in:' . implode(',', $options['asset_types']),
            'environment' => 'required|in:' . implode(',', $options['environments']),
            'criticality' => 'required|in:' . implode(',', $options['criticalities']),
            'status' => 'required|in:' . implode(',', $options['statuses']),
            'lifecycle_stage' => 'required|in:' . implode(',', $options['lifecycle_stages']),
            'owner_name' => 'nullable|string|max:120',
            'owner_email' => 'nullable|email|max:120',
            'bank_id' => 'nullable|exists:bank,id',
            'ip_address' => 'nullable|string|max:100',
            'hostname' => 'nullable|string|max:120',
            'port' => 'nullable|string|max:50',
            'source' => 'nullable|in:' . implode(',', $options['sources']),
            'discovery_confidence' => 'nullable|integer|min:0|max:100',
            'tags' => 'nullable|string|max:255',
            'last_seen_at' => 'nullable|date',
            'notes' => 'nullable|string',
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
            ->with('bank')
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
            'environment',
            'criticality',
            'status',
            'lifecycle_stage',
            'owner_name',
            'owner_email',
            'bank_id',
            'ip_address',
            'hostname',
            'port',
            'risk_score',
            'discovery_confidence',
        ]);

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

