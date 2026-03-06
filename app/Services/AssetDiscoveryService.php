<?php

namespace App\Services;

use App\Aplikasi;
use App\Asset;
use App\AssetActivityLog;
use App\AssetDiscoveryFinding;
use App\AssetDiscoveryRun;
use App\Bank;
use App\Database as DatabaseAsset;
use Illuminate\Support\Str;

class AssetDiscoveryService
{
    /**
     * Run discovery for a specific user.
     *
     * @param  int  $userId
     * @param  array<string, mixed>  $config
     * @return \App\AssetDiscoveryRun
     */
    public function runForUser($userId, array $config = [])
    {
        $sourceMode = $config['source_mode'] ?? 'catalog_sync';
        $scope = $config['scope'] ?? 'Scheduled Discovery';
        $manualPayload = trim((string) ($config['manual_payload'] ?? ''));
        $includeCatalog = isset($config['include_catalog']) ? (bool) $config['include_catalog'] : in_array($sourceMode, ['catalog_sync', 'hybrid']);
        $actorId = $config['actor_user_id'] ?? null;

        if (!in_array($sourceMode, ['catalog_sync', 'manual_seed', 'hybrid'])) {
            throw new \InvalidArgumentException('Unsupported source mode for discovery.');
        }

        if ($sourceMode === 'manual_seed' && $manualPayload === '') {
            throw new \InvalidArgumentException('Manual payload required for manual_seed mode.');
        }

        $run = AssetDiscoveryRun::create([
            'run_uuid' => (string) Str::uuid(),
            'scope' => $scope,
            'source_mode' => $sourceMode,
            'status' => 'running',
            'started_at' => now(),
            'input_payload' => $manualPayload !== '' ? $manualPayload : null,
            'user_id' => $userId,
        ]);

        try {
            $candidates = [];

            if ($includeCatalog) {
                $candidates = array_merge($candidates, $this->catalogCandidates($userId));
            }

            if (in_array($sourceMode, ['manual_seed', 'hybrid']) && $manualPayload !== '') {
                $candidates = array_merge($candidates, $this->manualCandidates($manualPayload));
            }

            if (empty($candidates)) {
                $run->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'summary' => 'No candidate assets found in current scope.',
                ]);

                return $run->fresh();
            }

            $uniqueCandidates = [];
            foreach ($candidates as $candidate) {
                $uniqueCandidates[$candidate['fingerprint']] = $candidate;
            }
            $candidates = array_values($uniqueCandidates);

            $stats = [
                'found' => count($candidates),
                'new' => 0,
                'updated' => 0,
                'matched' => 0,
            ];

            foreach ($candidates as $candidate) {
                $status = $this->ingestCandidate($run, $candidate, $userId, $actorId);
                if (isset($stats[$status])) {
                    $stats[$status]++;
                }
            }

            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_found' => $stats['found'],
                'total_new' => $stats['new'],
                'total_updated' => $stats['updated'],
                'total_matched' => $stats['matched'],
                'summary' => sprintf(
                    'Discovery completed. Found %d candidates (%d new, %d updated, %d matched).',
                    $stats['found'],
                    $stats['new'],
                    $stats['updated'],
                    $stats['matched']
                ),
            ]);

            return $run->fresh();
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'summary' => 'Discovery failed: ' . substr($exception->getMessage(), 0, 250),
            ]);

            throw $exception;
        }
    }

    /**
     * Build candidate set from legacy catalogs.
     *
     * @param  int  $userId
     * @return array<int, array<string, mixed>>
     */
    private function catalogCandidates($userId)
    {
        $candidates = [];

        $apps = Aplikasi::where('user_id', $userId)->get();
        foreach ($apps as $app) {
            $candidate = $this->buildCandidate([
                'name' => $app->nama,
                'asset_type' => 'application',
                'host_type' => null,
                'server_role' => null,
                'environment' => 'production',
                'criticality' => 'high',
                'status' => 'active',
                'lifecycle_stage' => 'operational',
                'ip_address' => $app->ip,
                'hostname' => null,
                'operating_system' => null,
                'os_version' => null,
                'os_eol_date' => null,
                'port' => $app->port,
                'bank_id' => $app->bank_id,
                'owner_name' => null,
                'owner_email' => null,
                'source' => 'import',
                'confidence' => 92,
                'notes' => 'Auto-synced from legacy aplikasi catalog',
            ]);

            if ($candidate) {
                $candidates[] = $candidate;
            }
        }

        $databases = DatabaseAsset::where('user_id', $userId)->get();
        foreach ($databases as $database) {
            $candidate = $this->buildCandidate([
                'name' => $database->nama,
                'asset_type' => 'database_server',
                'host_type' => null,
                'server_role' => 'database_server',
                'environment' => 'production',
                'criticality' => 'high',
                'status' => 'active',
                'lifecycle_stage' => 'operational',
                'ip_address' => $database->ip,
                'hostname' => null,
                'operating_system' => null,
                'os_version' => null,
                'os_eol_date' => null,
                'port' => $database->port,
                'bank_id' => $database->bank_id,
                'owner_name' => null,
                'owner_email' => null,
                'source' => 'import',
                'confidence' => 95,
                'notes' => $database->deskripsi ?: 'Auto-synced from legacy database catalog',
            ]);

            if ($candidate) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /**
     * Parse manual candidates from payload.
     *
     * @param  string  $manualPayload
     * @return array<int, array<string, mixed>>
     */
    private function manualCandidates($manualPayload)
    {
        $candidates = [];
        $lines = preg_split('/\r\n|\r|\n/', $manualPayload);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || Str::startsWith($line, '#')) {
                continue;
            }

            $parts = str_getcsv($line);

            $candidate = $this->buildCandidate([
                'name' => $parts[0] ?? null,
                'asset_type' => $parts[1] ?? 'etc',
                'ip_address' => $parts[2] ?? null,
                'port' => $parts[3] ?? null,
                'hostname' => $parts[4] ?? null,
                'environment' => $parts[5] ?? 'production',
                'owner_email' => $parts[7] ?? null,
                'owner_name' => $parts[8] ?? null,
                'bank_id' => $parts[9] ?? null,
                'host_type' => $parts[10] ?? null,
                'server_role' => $parts[11] ?? null,
                'operating_system' => $parts[12] ?? null,
                'os_version' => $parts[13] ?? null,
                'os_eol_date' => $parts[14] ?? null,
                'status' => 'active',
                'lifecycle_stage' => 'operational',
                'source' => 'import',
                'confidence' => 78,
                'notes' => 'Ingested from manual discovery payload',
            ]);

            if ($candidate) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /**
     * Ingest candidate into inventory.
     *
     * @param  \App\AssetDiscoveryRun  $run
     * @param  array<string, mixed>  $candidate
     * @param  int  $userId
     * @param  int|null  $actorId
     * @return string
     */
    private function ingestCandidate(AssetDiscoveryRun $run, array $candidate, $userId, $actorId = null)
    {
        $asset = $this->matchExistingAsset($candidate, $userId);
        $findingStatus = 'matched';
        $changedFields = [];

        if (!$asset) {
            $asset = Asset::create([
                'asset_code' => $this->createAssetCode(),
                'name' => $candidate['name'],
                'asset_type' => $candidate['asset_type'],
                'host_type' => $candidate['host_type'],
                'server_role' => $candidate['server_role'],
                'environment' => $candidate['environment'],
                'criticality' => $candidate['criticality'],
                'status' => $candidate['status'],
                'lifecycle_stage' => $candidate['lifecycle_stage'],
                'owner_name' => $candidate['owner_name'],
                'owner_email' => $candidate['owner_email'],
                'bank_id' => $candidate['bank_id'],
                'ip_address' => $candidate['ip_address'],
                'hostname' => $candidate['hostname'],
                'operating_system' => $candidate['operating_system'],
                'os_version' => $candidate['os_version'],
                'os_eol_date' => $candidate['os_eol_date'],
                'port' => $candidate['port'],
                'source' => $candidate['source'],
                'discovery_confidence' => $candidate['confidence'],
                'risk_score' => $this->calculateRiskScore(
                    $candidate['criticality'],
                    $candidate['status'],
                    $candidate['confidence'],
                    now()->toDateTimeString()
                ),
                'tags' => null,
                'last_seen_at' => now()->toDateTimeString(),
                'notes' => $candidate['notes'],
                'user_id' => $userId,
            ]);

            $findingStatus = 'new';

            $this->trackActivity($asset->id, $actorId, 'discovered_new', 'Asset discovered and created', [
                'run_uuid' => $run->run_uuid,
                'source' => $candidate['source'],
                'fingerprint' => $candidate['fingerprint'],
            ]);
        } else {
            $updatableFields = [
                'host_type',
                'server_role',
                'environment',
                'status',
                'lifecycle_stage',
                'bank_id',
                'ip_address',
                'hostname',
                'operating_system',
                'os_version',
                'os_eol_date',
                'port',
            ];

            foreach ($updatableFields as $field) {
                if ($candidate[$field] !== null && $candidate[$field] !== '' && $asset->{$field} != $candidate[$field]) {
                    $asset->{$field} = $candidate[$field];
                    $changedFields[] = $field;
                }
            }

            if (!empty($candidate['owner_name']) && $asset->owner_name !== $candidate['owner_name']) {
                $asset->owner_name = $candidate['owner_name'];
                $changedFields[] = 'owner_name';
            }

            if (!empty($candidate['owner_email']) && $asset->owner_email !== $candidate['owner_email']) {
                $asset->owner_email = $candidate['owner_email'];
                $changedFields[] = 'owner_email';
            }

            if ($asset->status !== 'retired' && $asset->status !== 'active') {
                $asset->status = 'active';
                $changedFields[] = 'status';
            }

            if ((int) $candidate['confidence'] > (int) $asset->discovery_confidence) {
                $asset->discovery_confidence = (int) $candidate['confidence'];
                $changedFields[] = 'discovery_confidence';
            }

            if ($asset->source !== 'manual') {
                $asset->source = $candidate['source'];
            }

            $asset->last_seen_at = now()->toDateTimeString();
            $asset->criticality = $this->deriveCriticalityFromEol(
                $asset->asset_type,
                $asset->os_eol_date,
                is_array($asset->asset_profile) ? $asset->asset_profile : [],
                'medium'
            );
            $asset->risk_score = $this->calculateRiskScore(
                $asset->criticality,
                $asset->status,
                $asset->discovery_confidence,
                $asset->last_seen_at
            );

            if (!empty($changedFields)) {
                $asset->save();
                $findingStatus = 'updated';

                $this->trackActivity($asset->id, $actorId, 'discovered_update', 'Asset updated via discovery', [
                    'run_uuid' => $run->run_uuid,
                    'changed_fields' => implode(', ', array_unique($changedFields)),
                ]);
            } else {
                $asset->save();
                $findingStatus = 'matched';
            }
        }

        AssetDiscoveryFinding::create([
            'run_id' => $run->id,
            'asset_id' => $asset->id,
            'fingerprint' => $candidate['fingerprint'],
            'asset_name' => $candidate['name'],
            'asset_type' => $candidate['asset_type'],
            'ip_address' => $candidate['ip_address'],
            'hostname' => $candidate['hostname'],
            'port' => $candidate['port'],
            'environment' => $candidate['environment'],
            'finding_status' => $findingStatus,
            'confidence' => (int) $candidate['confidence'],
            'payload' => json_encode([
                'candidate' => $candidate,
                'changed_fields' => array_values(array_unique($changedFields)),
            ]),
        ]);

        return $findingStatus;
    }

    /**
     * Match existing asset by key properties.
     *
     * @param  array<string, mixed>  $candidate
     * @param  int  $userId
     * @return \App\Asset|null
     */
    private function matchExistingAsset(array $candidate, $userId)
    {
        $query = Asset::where('user_id', $userId)
            ->where('asset_type', $candidate['asset_type'])
            ->whereRaw('LOWER(name) = ?', [Str::lower($candidate['name'])]);

        if (!empty($candidate['ip_address'])) {
            $query->where('ip_address', $candidate['ip_address']);
        }

        if (!empty($candidate['port'])) {
            $query->where('port', $candidate['port']);
        }

        $asset = $query->first();
        if ($asset) {
            return $asset;
        }

        if (!empty($candidate['hostname'])) {
            $asset = Asset::where('user_id', $userId)
                ->where('asset_type', $candidate['asset_type'])
                ->whereRaw('LOWER(name) = ?', [Str::lower($candidate['name'])])
                ->where('hostname', $candidate['hostname'])
                ->first();
        }

        if ($asset) {
            return $asset;
        }

        if (!empty($candidate['ip_address'])) {
            $asset = Asset::where('user_id', $userId)
                ->where('asset_type', $candidate['asset_type'])
                ->where('ip_address', $candidate['ip_address'])
                ->where('port', $candidate['port'])
                ->first();
        }

        return $asset;
    }

    /**
     * Build normalized candidate record.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    private function buildCandidate(array $raw)
    {
        $name = trim((string) ($raw['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $assetType = $this->normalizeAssetType($raw['asset_type'] ?? 'etc');

        $hostType = $this->nullableEnumValue(
            $raw['host_type'] ?? null,
            ['physical', 'virtual_machine', 'container', 'cloud_instance', 'other'],
            'other'
        );

        $serverRole = $this->nullableEnumValue(
            $raw['server_role'] ?? null,
            ['application_server', 'database_server'],
            $assetType === 'database_server' ? 'database_server' : 'application_server'
        );
        if ($assetType === 'database_server' && !$serverRole) {
            $serverRole = 'database_server';
        } elseif ($assetType === 'application_server' && !$serverRole) {
            $serverRole = 'application_server';
        } elseif (!in_array($assetType, ['application_server', 'database_server'], true)) {
            $serverRole = null;
        }

        $environment = $this->enumValue(
            $raw['environment'] ?? 'production',
            ['production', 'staging', 'development', 'uat', 'dr', 'other'],
            'production'
        );

        $status = $this->enumValue(
            $raw['status'] ?? 'active',
            ['active', 'maintenance', 'inactive', 'unknown', 'retired'],
            'active'
        );

        $lifecycle = $this->enumValue(
            $raw['lifecycle_stage'] ?? 'operational',
            ['onboarding', 'operational', 'degraded', 'sunset', 'retired'],
            'operational'
        );

        $ip = $this->nullableTrim($raw['ip_address'] ?? null);
        $hostname = $this->nullableTrim($raw['hostname'] ?? null);
        $port = $this->nullableTrim($raw['port'] ?? null);
        $operatingSystem = $this->nullableTrim($raw['operating_system'] ?? null);
        $osVersion = $this->nullableTrim($raw['os_version'] ?? null);
        $osEolDate = $this->normalizeDate($raw['os_eol_date'] ?? null);
        $criticality = $this->deriveCriticalityFromEol($assetType, $osEolDate, [], 'medium');
        $bankId = $this->nullableInt($raw['bank_id'] ?? null);

        if ($bankId && !Bank::where('id', $bankId)->exists()) {
            $bankId = null;
        }

        return [
            'name' => $name,
            'asset_type' => $assetType,
            'host_type' => $hostType,
            'server_role' => $serverRole,
            'environment' => $environment,
            'criticality' => $criticality,
            'status' => $status,
            'lifecycle_stage' => $lifecycle,
            'owner_name' => $this->nullableTrim($raw['owner_name'] ?? null),
            'owner_email' => $this->nullableTrim($raw['owner_email'] ?? null),
            'bank_id' => $bankId,
            'ip_address' => $ip,
            'hostname' => $hostname,
            'operating_system' => $operatingSystem,
            'os_version' => $osVersion,
            'os_eol_date' => $osEolDate,
            'port' => $port,
            'source' => $this->enumValue(
                $raw['source'] ?? 'import',
                ['manual', 'import'],
                'import'
            ),
            'confidence' => max(0, min(100, (int) ($raw['confidence'] ?? 75))),
            'notes' => $this->nullableTrim($raw['notes'] ?? null),
            'fingerprint' => $this->fingerprint($assetType, $name, $ip, $port, $hostname),
        ];
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
        return in_array($value, $allowed) ? $value : $fallback;
    }

    /**
     * Normalize legacy and current asset types.
     *
     * @param  mixed  $assetType
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
     * Normalize nullable enum values.
     *
     * @param  mixed  $value
     * @param  array<int, string>  $allowed
     * @param  string  $fallback
     * @return string|null
     */
    private function nullableEnumValue($value, array $allowed, $fallback)
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->enumValue($value, $allowed, $fallback);
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
     * Normalize nullable integer.
     *
     * @param  mixed  $value
     * @return int|null
     */
    private function nullableInt($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Normalize date to Y-m-d.
     *
     * @param  mixed  $value
     * @return string|null
     */
    private function normalizeDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = strtotime((string) $value);
        return $parsed ? date('Y-m-d', $parsed) : null;
    }

    /**
     * Derive criticality from nearest EOL date.
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
     * Extract database license EOL date when applicable.
     *
     * @param  string  $assetType
     * @param  array<string, mixed>  $assetProfile
     * @return string|null
     */
    private function databaseLicenseEolDate($assetType, array $assetProfile)
    {
        if (!in_array($assetType, ['database_server', 'database'], true)) {
            return null;
        }

        return $this->normalizeDate($assetProfile['db_license_eol_date'] ?? null);
    }

    /**
     * Calculate days until date (negative means expired).
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
     * Build deterministic fingerprint.
     *
     * @param  string  $assetType
     * @param  string  $name
     * @param  string|null  $ip
     * @param  string|null  $port
     * @param  string|null  $hostname
     * @return string
     */
    private function fingerprint($assetType, $name, $ip, $port, $hostname)
    {
        $seed = implode('|', [
            Str::lower($assetType),
            Str::lower($name),
            Str::lower((string) $ip),
            Str::lower((string) $port),
            Str::lower((string) $hostname),
        ]);

        return sha1($seed);
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
     * Track activity log.
     *
     * @param  int  $assetId
     * @param  int|null  $actorId
     * @param  string  $action
     * @param  string  $message
     * @param  array<string, mixed>  $context
     * @return void
     */
    private function trackActivity($assetId, $actorId, $action, $message, array $context = [])
    {
        AssetActivityLog::create([
            'asset_id' => $assetId,
            'actor_user_id' => $actorId,
            'action' => $action,
            'message' => $message,
            'context' => empty($context) ? null : json_encode($context),
        ]);
    }
}
