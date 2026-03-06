<?php

namespace App\Http\Controllers;

use App\Asset;
use App\AssetChangeRequest;
use App\AssetDiscoveryRun;
use App\AssetPolicyViolation;
use Auth;
use Illuminate\Support\Facades\DB;

class AssetOpsController extends Controller
{
    /**
     * Display operational asset dashboard.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $staleThreshold = now()->subDays(30);
        $assetsBase = $this->assetScopeQuery();

        $totalAssets = (clone $assetsBase)->count();
        $activeAssets = (clone $assetsBase)->where('status', 'active')->count();
        $criticalAssets = (clone $assetsBase)->whereIn('criticality', ['high', 'critical'])->count();
        $staleAssets = (clone $assetsBase)
            ->where(function ($query) use ($staleThreshold) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $staleThreshold);
            })
            ->count();

        $unmanagedAssets = (clone $assetsBase)
            ->where(function ($query) {
                $query->whereNull('owner_name')
                    ->orWhere('owner_name', '');
            })
            ->count();

        $highRiskAssets = (clone $assetsBase)->where('risk_score', '>=', 70)->count();

        $assetsByType = (clone $assetsBase)
            ->select('asset_type', DB::raw('COUNT(*) as total'))
            ->groupBy('asset_type')
            ->orderBy('total', 'desc')
            ->get();

        $assetsByStatus = (clone $assetsBase)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('total', 'desc')
            ->get();

        $recentAssets = (clone $assetsBase)
            ->with('bank')
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get();

        $topRiskAssets = (clone $assetsBase)
            ->with('bank')
            ->orderBy('risk_score', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get();

        $recentRunsQuery = AssetDiscoveryRun::query();
        if (!$user->hasGlobalAssetVisibility()) {
            $recentRunsQuery->where('user_id', $user->id);
        }

        $recentRuns = $recentRunsQuery
            ->orderBy('started_at', 'desc')
            ->limit(6)
            ->get();

        $withOwner = (clone $assetsBase)
            ->whereNotNull('owner_name')
            ->where('owner_name', '!=', '')
            ->count();

        $withLastSeen = (clone $assetsBase)
            ->whereNotNull('last_seen_at')
            ->count();

        $importedAssets = (clone $assetsBase)
            ->where('source', 'import')
            ->count();

        $eolNotification = $this->buildEolNotificationSummary(
            (clone $assetsBase)->select(['id', 'asset_code', 'name', 'asset_type', 'status', 'os_eol_date', 'asset_profile'])->get()
        );

        $violationsQuery = AssetPolicyViolation::where('status', 'open');
        if (!$user->hasGlobalAssetVisibility()) {
            $violationsQuery->where('user_id', $user->id);
        }

        $approvalQuery = AssetChangeRequest::where('status', 'pending');
        if (!$user->canApproveAssetChanges()) {
            $approvalQuery->where('requester_user_id', $user->id);
        }

        $coverage = [
            'owner' => $totalAssets > 0 ? round(($withOwner / $totalAssets) * 100) : 0,
            'visibility' => $totalAssets > 0 ? round(($withLastSeen / $totalAssets) * 100) : 0,
            'import' => $totalAssets > 0 ? round(($importedAssets / $totalAssets) * 100) : 0,
        ];

        $metrics = [
            'total' => $totalAssets,
            'active' => $activeAssets,
            'critical' => $criticalAssets,
            'stale' => $staleAssets,
            'unmanaged' => $unmanagedAssets,
            'high_risk' => $highRiskAssets,
            'open_violations' => $violationsQuery->count(),
            'pending_approvals' => $approvalQuery->count(),
        ];

        return view('assets.dashboard', compact(
            'metrics',
            'coverage',
            'eolNotification',
            'assetsByType',
            'assetsByStatus',
            'recentAssets',
            'topRiskAssets',
            'recentRuns'
        ));
    }

    /**
     * Build compact EOL notification summary for dashboard.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Asset>  $assets
     * @return array<string, int>
     */
    private function buildEolNotificationSummary($assets)
    {
        $summary = [
            'os_expired' => 0,
            'os_near' => 0,
            'license_expired' => 0,
            'license_near' => 0,
            'total' => 0,
        ];

        foreach ($assets as $asset) {
            if (($asset->status ?? null) === 'retired') {
                continue;
            }

            $osDays = $this->daysUntilDate($asset->os_eol_date ?? null);
            if ($osDays !== null && $osDays <= 90) {
                $summary[$osDays < 0 ? 'os_expired' : 'os_near']++;
                $summary['total']++;
            }

            $profile = is_array($asset->asset_profile) ? $asset->asset_profile : [];
            $licenseEol = $this->databaseLicenseEolDate($asset->asset_type ?? null, $profile);
            $licenseDays = $this->daysUntilDate($licenseEol);
            if ($licenseDays !== null && $licenseDays <= 90) {
                $summary[$licenseDays < 0 ? 'license_expired' : 'license_near']++;
                $summary['total']++;
            }
        }

        return $summary;
    }

    /**
     * Parse database license EOL date from asset profile.
     *
     * @param  string|null  $assetType
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
     * Normalize date input to Y-m-d.
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
     * Calculate days until given date (negative means expired).
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

        return now()->startOfDay()->diffInDays($normalized, false);
    }

    /**
     * Build asset scope query by user role.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function assetScopeQuery()
    {
        $query = Asset::query();
        if (!Auth::user()->hasGlobalAssetVisibility()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }
}
