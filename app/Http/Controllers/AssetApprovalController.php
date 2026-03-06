<?php

namespace App\Http\Controllers;

use App\Asset;
use App\AssetActivityLog;
use App\AssetChangeRequest;
use App\AssetService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetApprovalController extends Controller
{
    /**
     * Display pending and recent approvals.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $pendingRequests = AssetChangeRequest::with(['asset', 'requester'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'pending_page');

        $recentRequests = AssetChangeRequest::with(['asset', 'requester', 'approver'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('reviewed_at', 'desc')
            ->limit(20)
            ->get();

        return view('approvals.index', compact('pendingRequests', 'recentRequests'));
    }

    /**
     * Approve a pending request and apply the change.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request, $id)
    {
        $changeRequest = AssetChangeRequest::with('asset')
            ->where('status', 'pending')
            ->findOrFail($id);
        $actor = Auth::user();

        if ($changeRequest->requester_user_id === $actor->id && !$actor->isSuperAdmin()) {
            return redirect()->route('approvals.index')
                ->with('error', 'Request milik sendiri tidak dapat di-approve oleh requester yang sama.');
        }

        $asset = $changeRequest->asset;
        if (!$asset) {
            return redirect()->route('approvals.index')->with('error', 'Asset target tidak ditemukan.');
        }

        $payload = json_decode((string) $changeRequest->requested_payload, true);
        $payload = is_array($payload) ? $payload : [];

        if ($changeRequest->change_type === 'retire_asset') {
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
        } elseif ($changeRequest->change_type === 'update_sensitive') {
            $allowed = [
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
                'source',
                'discovery_confidence',
                'tags',
                'last_seen_at',
                'notes',
                'asset_profile',
            ];

            $updateData = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $payload)) {
                    $updateData[$field] = $payload[$field];
                }
            }

            if (array_key_exists('last_seen_at', $updateData) && $updateData['last_seen_at']) {
                $parsed = strtotime($updateData['last_seen_at']);
                $updateData['last_seen_at'] = $parsed ? date('Y-m-d H:i:s', $parsed) : $asset->last_seen_at;
            }

            if (array_key_exists('os_eol_date', $updateData) && $updateData['os_eol_date']) {
                $parsed = strtotime($updateData['os_eol_date']);
                $updateData['os_eol_date'] = $parsed ? date('Y-m-d', $parsed) : $asset->os_eol_date;
            }

            if (array_key_exists('asset_type', $updateData)) {
                $updateData['asset_type'] = $this->normalizeAssetType($updateData['asset_type']);
            } else {
                $updateData['asset_type'] = $asset->asset_type;
            }

            if (array_key_exists('asset_profile', $updateData) && is_array($updateData['asset_profile'])) {
                $updateData['asset_profile'] = $this->normalizeAssetProfile(
                    $updateData['asset_type'],
                    $updateData['asset_profile']
                );
            } else {
                $updateData['asset_profile'] = $asset->asset_profile;
            }

            if ($updateData['asset_type'] === 'application_server') {
                $updateData['server_role'] = 'application_server';
            } elseif ($updateData['asset_type'] === 'database_server') {
                $updateData['server_role'] = 'database_server';
            } else {
                $updateData['host_type'] = null;
                $updateData['server_role'] = null;
                $updateData['operating_system'] = null;
                $updateData['os_version'] = null;
                $updateData['os_eol_date'] = null;
            }

            if ($updateData['asset_type'] === 'application') {
                $updateData['ip_address'] = null;
                $updateData['hostname'] = null;
                $updateData['port'] = null;
            }

            $updateData['source'] = $this->normalizeSource($updateData['source'] ?? $asset->source ?? 'manual');
            $updateData['criticality'] = $this->deriveCriticalityFromEol(
                $updateData['asset_type'],
                $updateData['os_eol_date'] ?? $asset->os_eol_date,
                is_array($updateData['asset_profile']) ? $updateData['asset_profile'] : (is_array($asset->asset_profile) ? $asset->asset_profile : []),
                'medium'
            );

            $updateData['risk_score'] = $this->calculateRiskScore(
                $updateData['criticality'],
                $updateData['status'] ?? $asset->status,
                $updateData['discovery_confidence'] ?? $asset->discovery_confidence,
                $updateData['last_seen_at'] ?? $asset->last_seen_at
            );

            $asset->update($updateData);

            if (isset($payload['services']) && is_array($payload['services']) && $this->isServerType($updateData['asset_type'])) {
                $this->syncServices($asset, $this->normalizeServices($payload['services']));
            } elseif (!$this->isServerType($updateData['asset_type'])) {
                $this->syncServices($asset, []);
            }
        } else {
            return redirect()->route('approvals.index')->with('error', 'Jenis request change tidak dikenali.');
        }

        $changeRequest->update([
            'status' => 'approved',
            'approver_user_id' => $actor->id,
            'reviewed_at' => now(),
            'reason' => $request->input('reason') ?: $changeRequest->reason,
        ]);

        AssetActivityLog::create([
            'asset_id' => $asset->id,
            'actor_user_id' => $actor->id,
            'action' => 'change_approved',
            'message' => 'Asset change request approved',
            'context' => json_encode([
                'request_id' => $changeRequest->id,
                'change_type' => $changeRequest->change_type,
            ]),
        ]);

        return redirect()->route('approvals.index')->with('success', 'Request change berhasil di-approve.');
    }

    /**
     * Reject pending request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, $id)
    {
        $changeRequest = AssetChangeRequest::with('asset')
            ->where('status', 'pending')
            ->findOrFail($id);
        $actor = Auth::user();

        if ($changeRequest->requester_user_id === $actor->id && !$actor->isSuperAdmin()) {
            return redirect()->route('approvals.index')
                ->with('error', 'Request milik sendiri tidak dapat ditolak oleh requester yang sama.');
        }

        $changeRequest->update([
            'status' => 'rejected',
            'approver_user_id' => $actor->id,
            'reviewed_at' => now(),
            'reason' => $request->input('reason') ?: $changeRequest->reason,
        ]);

        if ($changeRequest->asset) {
            AssetActivityLog::create([
                'asset_id' => $changeRequest->asset->id,
                'actor_user_id' => $actor->id,
                'action' => 'change_rejected',
                'message' => 'Asset change request rejected',
                'context' => json_encode([
                    'request_id' => $changeRequest->id,
                    'change_type' => $changeRequest->change_type,
                ]),
            ]);
        }

        return redirect()->route('approvals.index')->with('success', 'Request change ditolak.');
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

        $parsed = strtotime((string) $value);
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
     * Derive criticality from nearest OS/license EOL date.
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
     * Get database license EOL date when applicable.
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
     * Calculate days until a date (negative means expired).
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
     * Normalize service rows from request payload.
     *
     * @param  array<int, array<string, mixed>>  $services
     * @return array<int, array<string, mixed>>
     */
    private function normalizeServices(array $services)
    {
        $serviceTypes = ['application', 'database', 'proxy', 'queue', 'cache', 'api', 'frontend', 'other'];
        $serviceStatuses = ['running', 'degraded', 'down', 'maintenance', 'unknown'];

        return collect($services)
            ->map(function ($service) use ($serviceTypes, $serviceStatuses) {
                $name = trim((string) ($service['name'] ?? $service['service_name'] ?? ''));
                if ($name === '') {
                    return null;
                }

                $type = trim((string) ($service['type'] ?? $service['service_type'] ?? 'application'));
                if (!in_array($type, $serviceTypes, true)) {
                    $type = 'application';
                }

                $status = trim((string) ($service['status'] ?? 'unknown'));
                if (!in_array($status, $serviceStatuses, true)) {
                    $status = 'unknown';
                }

                return [
                    'service_name' => $name,
                    'service_type' => $type,
                    'technology_stack' => $this->nullableTrim($service['technology_stack'] ?? null),
                    'version' => $this->nullableTrim($service['version'] ?? null),
                    'status' => $status,
                    'port' => $this->nullableTrim($service['port'] ?? null),
                    'notes' => $this->nullableTrim($service['notes'] ?? null),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Replace asset services with approved payload.
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
}
