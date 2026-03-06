<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class NormalizeAssetSourceAndCriticalityFromEol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('assets')
            ->select(['id', 'asset_type', 'status', 'source', 'os_eol_date', 'asset_profile', 'discovery_confidence', 'last_seen_at'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $profile = json_decode((string) $row->asset_profile, true);
                    $profile = is_array($profile) ? $profile : [];

                    $normalizedSource = $this->normalizeSource($row->source ?? null);
                    $derivedCriticality = $this->deriveCriticalityFromEol(
                        (string) ($row->asset_type ?? ''),
                        $row->os_eol_date ?? null,
                        $profile,
                        'medium'
                    );

                    $riskScore = $this->calculateRiskScore(
                        $derivedCriticality,
                        (string) ($row->status ?? 'active'),
                        (int) ($row->discovery_confidence ?? 100),
                        $row->last_seen_at ?? null
                    );

                    DB::table('assets')
                        ->where('id', $row->id)
                        ->update([
                            'source' => $normalizedSource,
                            'criticality' => $derivedCriticality,
                            'risk_score' => $riskScore,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Non-reversible normalization; keep current values.
    }

    /**
     * Normalize source value to supported set.
     *
     * @param  mixed  $source
     * @return string
     */
    private function normalizeSource($source)
    {
        $source = strtolower(trim((string) $source));
        return $source === 'manual' ? 'manual' : 'import';
    }

    /**
     * Derive criticality from nearest EOL date (OS/license).
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
     * Get db license EOL if asset is database server.
     *
     * @param  string  $assetType
     * @param  array<string, mixed>  $assetProfile
     * @return string|null
     */
    private function databaseLicenseEolDate($assetType, array $assetProfile)
    {
        $assetType = strtolower(trim($assetType));
        if (!in_array($assetType, ['database_server', 'database'], true)) {
            return null;
        }

        return $this->normalizeDate($assetProfile['db_license_eol_date'] ?? null);
    }

    /**
     * Normalize date value into Y-m-d.
     *
     * @param  mixed  $value
     * @return string|null
     */
    private function normalizeDate($value)
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        $parsed = strtotime((string) $value);
        return $parsed ? date('Y-m-d', $parsed) : null;
    }

    /**
     * Calculate days left to date (negative means expired).
     *
     * @param  mixed  $dateValue
     * @return int|null
     */
    private function daysUntilDate($dateValue)
    {
        $normalized = $this->normalizeDate($dateValue);
        if (!$normalized) {
            return null;
        }

        $today = strtotime(date('Y-m-d'));
        $target = strtotime($normalized);
        if (!$today || !$target) {
            return null;
        }

        return (int) floor(($target - $today) / 86400);
    }

    /**
     * Calculate risk score using existing scoring model.
     *
     * @param  string  $criticality
     * @param  string  $status
     * @param  int  $confidence
     * @param  mixed  $lastSeenAt
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
            $lastSeenTs = strtotime((string) $lastSeenAt);
            if ($lastSeenTs) {
                $days = (int) floor((time() - $lastSeenTs) / 86400);
                if ($days > 90) {
                    $score += 20;
                } elseif ($days > 30) {
                    $score += 10;
                }
            } else {
                $score += 8;
            }
        } else {
            $score += 8;
        }

        if ($status === 'retired') {
            return max(0, min(30, (int) $score));
        }

        return max(1, min(100, (int) $score));
    }
}

