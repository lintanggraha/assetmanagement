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

        $discoveredAssets = (clone $assetsBase)
            ->whereIn('source', ['discovery', 'catalog_sync', 'sync', 'manual_seed'])
            ->count();

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
            'discovery' => $totalAssets > 0 ? round(($discoveredAssets / $totalAssets) * 100) : 0,
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
            'assetsByType',
            'assetsByStatus',
            'recentAssets',
            'topRiskAssets',
            'recentRuns'
        ));
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
