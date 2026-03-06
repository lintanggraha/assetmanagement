<?php

namespace App\Services;

use App\Asset;
use App\AssetActivityLog;
use App\AssetPolicyViolation;

class AssetPolicyScannerService
{
    /**
     * Scan asset policies and synchronize violation records.
     *
     * @param  int|null  $userId
     * @return array<string, int>
     */
    public function scan($userId = null)
    {
        $query = Asset::query();
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $assets = $query->get();

        $stats = [
            'assets_scanned' => $assets->count(),
            'new_open' => 0,
            'still_open' => 0,
            'resolved' => 0,
            'total_open' => 0,
        ];

        foreach ($assets as $asset) {
            $rules = $this->evaluateAsset($asset);
            $activePolicyCodes = [];

            foreach ($rules as $rule) {
                $activePolicyCodes[] = $rule['policy_code'];

                $violation = AssetPolicyViolation::firstOrNew([
                    'asset_id' => $asset->id,
                    'policy_code' => $rule['policy_code'],
                ]);

                $isNew = !$violation->exists;
                $wasResolved = $violation->exists && $violation->status === 'resolved';

                $violation->fill([
                    'user_id' => $asset->user_id,
                    'severity' => $rule['severity'],
                    'status' => 'open',
                    'message' => $rule['message'],
                    'detected_at' => $violation->detected_at ?: now(),
                    'resolved_at' => null,
                    'metadata' => json_encode($rule['metadata']),
                ]);
                $violation->save();

                if ($isNew || $wasResolved) {
                    $stats['new_open']++;
                    $this->trackActivity($asset->id, 'policy_violation_open', $rule['message'], [
                        'policy_code' => $rule['policy_code'],
                        'severity' => $rule['severity'],
                    ]);
                } else {
                    $stats['still_open']++;
                }
            }

            $toResolve = AssetPolicyViolation::where('asset_id', $asset->id)
                ->where('status', 'open')
                ->when(!empty($activePolicyCodes), function ($resolveQuery) use ($activePolicyCodes) {
                    $resolveQuery->whereNotIn('policy_code', $activePolicyCodes);
                })
                ->when(empty($activePolicyCodes), function ($resolveQuery) {
                    $resolveQuery->whereNotNull('id');
                })
                ->get();

            foreach ($toResolve as $resolvedViolation) {
                $resolvedViolation->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);

                $stats['resolved']++;
                $this->trackActivity(
                    $asset->id,
                    'policy_violation_resolved',
                    'Policy violation automatically resolved',
                    ['policy_code' => $resolvedViolation->policy_code]
                );
            }
        }

        $openQuery = AssetPolicyViolation::where('status', 'open');
        if ($userId !== null) {
            $openQuery->where('user_id', $userId);
        }
        $stats['total_open'] = $openQuery->count();

        return $stats;
    }

    /**
     * Evaluate policy rules for a single asset.
     *
     * @param  \App\Asset  $asset
     * @return array<int, array<string, mixed>>
     */
    private function evaluateAsset(Asset $asset)
    {
        $violations = [];
        $tags = strtolower((string) $asset->tags);
        $profile = is_array($asset->asset_profile) ? $asset->asset_profile : [];
        $isStale = !$asset->last_seen_at || $asset->last_seen_at->lt(now()->subDays(30));
        $isCritical = in_array($asset->criticality, ['high', 'critical']);
        $isServerClass = in_array($asset->asset_type, ['application_server', 'database_server', 'server'], true)
            || !empty($asset->host_type)
            || !empty($asset->server_role);

        if (!$asset->owner_name || trim($asset->owner_name) === '') {
            $violations[] = [
                'policy_code' => 'OWNER_MISSING',
                'severity' => 'high',
                'message' => 'Asset owner is missing.',
                'metadata' => ['owner_name' => $asset->owner_name],
            ];
        }

        if ($asset->risk_score >= 70 && $isStale) {
            $violations[] = [
                'policy_code' => 'HIGH_RISK_STALE',
                'severity' => 'critical',
                'message' => 'High-risk asset has stale visibility (last_seen > 30 days).',
                'metadata' => [
                    'risk_score' => $asset->risk_score,
                    'last_seen_at' => optional($asset->last_seen_at)->toDateTimeString(),
                ],
            ];
        }

        if ($isCritical && strpos($tags, 'dr') === false && strpos($tags, 'redundant') === false) {
            $violations[] = [
                'policy_code' => 'CRITICAL_NO_DR_TAG',
                'severity' => 'high',
                'message' => 'Critical asset has no DR/redundancy tag evidence.',
                'metadata' => ['tags' => $asset->tags],
            ];
        }

        if ((int) $asset->discovery_confidence < 60) {
            $violations[] = [
                'policy_code' => 'DISCOVERY_CONFIDENCE_LOW',
                'severity' => 'medium',
                'message' => 'Asset discovery confidence is below 60%.',
                'metadata' => ['discovery_confidence' => $asset->discovery_confidence],
            ];
        }

        if (!$asset->ip_address && !$asset->hostname) {
            $violations[] = [
                'policy_code' => 'ENDPOINT_IDENTITY_MISSING',
                'severity' => 'medium',
                'message' => 'Asset has no IP and no hostname identifier.',
                'metadata' => [
                    'ip_address' => $asset->ip_address,
                    'hostname' => $asset->hostname,
                ],
            ];
        }

        if ($isServerClass && $asset->status !== 'retired' && (!$asset->operating_system || trim($asset->operating_system) === '')) {
            $violations[] = [
                'policy_code' => 'OS_INFO_MISSING',
                'severity' => 'high',
                'message' => 'Server-class asset has no operating system information.',
                'metadata' => [
                    'asset_type' => $asset->asset_type,
                    'host_type' => $asset->host_type,
                    'server_role' => $asset->server_role,
                ],
            ];
        }

        if ($asset->status !== 'retired' && $asset->os_eol_date) {
            if ($asset->os_eol_date->lt(now()->startOfDay())) {
                $violations[] = [
                    'policy_code' => 'OS_EOL_EXPIRED',
                    'severity' => 'critical',
                    'message' => 'Operating system is past EOL and must be remediated immediately.',
                    'metadata' => [
                        'operating_system' => $asset->operating_system,
                        'os_version' => $asset->os_version,
                        'os_eol_date' => $asset->os_eol_date->toDateString(),
                    ],
                ];
            } elseif ($asset->os_eol_date->lte(now()->addDays(90)->startOfDay())) {
                $violations[] = [
                    'policy_code' => 'OS_EOL_NEAR',
                    'severity' => 'high',
                    'message' => 'Operating system EOL is within the next 90 days.',
                    'metadata' => [
                        'operating_system' => $asset->operating_system,
                        'os_version' => $asset->os_version,
                        'os_eol_date' => $asset->os_eol_date->toDateString(),
                        'days_left' => now()->diffInDays($asset->os_eol_date, false),
                    ],
                ];
            }
        }

        if ($asset->status !== 'retired' && in_array($asset->asset_type, ['database_server', 'database'], true)) {
            $licenseEolDate = null;
            if (!empty($profile['db_license_eol_date'])) {
                $parsed = strtotime((string) $profile['db_license_eol_date']);
                if ($parsed) {
                    $licenseEolDate = date('Y-m-d', $parsed);
                }
            }

            if ($licenseEolDate) {
                $daysLeft = now()->startOfDay()->diffInDays($licenseEolDate, false);

                if ($daysLeft < 0) {
                    $violations[] = [
                        'policy_code' => 'DB_LICENSE_EOL_EXPIRED',
                        'severity' => 'critical',
                        'message' => 'Database license is expired and must be renewed or migrated immediately.',
                        'metadata' => [
                            'db_license_type' => $profile['db_license_type'] ?? null,
                            'db_license_eol_date' => $licenseEolDate,
                        ],
                    ];
                } elseif ($daysLeft <= 90) {
                    $violations[] = [
                        'policy_code' => 'DB_LICENSE_EOL_NEAR',
                        'severity' => 'high',
                        'message' => 'Database license EOL is within the next 90 days.',
                        'metadata' => [
                            'db_license_type' => $profile['db_license_type'] ?? null,
                            'db_license_eol_date' => $licenseEolDate,
                            'days_left' => $daysLeft,
                        ],
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Track policy activities.
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
            'actor_user_id' => null,
            'action' => $action,
            'message' => $message,
            'context' => empty($context) ? null : json_encode($context),
        ]);
    }
}
