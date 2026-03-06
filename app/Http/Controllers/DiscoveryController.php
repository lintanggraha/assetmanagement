<?php

namespace App\Http\Controllers;

use App\AssetDiscoveryFinding;
use App\AssetDiscoveryRun;
use App\Services\AssetDiscoveryService;
use Auth;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!filter_var(env('ASSET_DISCOVERY_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
                return redirect()->route('dashboard')
                    ->with('error', 'Discovery Center sementara di-hide. Gunakan input manual dari Asset Inventory.');
            }

            return $next($request);
        });
    }

    /**
     * Discovery center page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $runsBase = $this->runQuery();
        $runs = (clone $runsBase)
            ->orderBy('started_at', 'desc')
            ->paginate(12);

        $metrics = [
            'total_runs' => (clone $runsBase)->count(),
            'completed_runs' => (clone $runsBase)->where('status', 'completed')->count(),
            'new_assets_discovered' => (clone $runsBase)->sum('total_new'),
            'updated_assets' => (clone $runsBase)->sum('total_updated'),
        ];

        return view('discovery.index', compact('runs', 'metrics'));
    }

    /**
     * Display detail of discovery run.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $run = $this->runQuery()->findOrFail($id);
        $findings = AssetDiscoveryFinding::with('asset')
            ->where('run_id', $run->id)
            ->orderBy('created_at', 'desc')
            ->paginate(40);

        $statusCounts = AssetDiscoveryFinding::selectRaw('finding_status, COUNT(*) as total')
            ->where('run_id', $run->id)
            ->groupBy('finding_status')
            ->pluck('total', 'finding_status');

        return view('discovery.show', compact('run', 'findings', 'statusCounts'));
    }

    /**
     * Execute discovery run.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Services\AssetDiscoveryService  $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function run(Request $request, AssetDiscoveryService $service)
    {
        if (!Auth::user()->canRunDiscovery()) {
            abort(403, 'Current role is not allowed to run discovery.');
        }

        $validated = $request->validate([
            'scope' => 'nullable|string|max:150',
            'source_mode' => 'required|in:catalog_sync,manual_seed,hybrid',
            'manual_payload' => 'nullable|string',
            'include_catalog' => 'nullable|boolean',
        ]);

        try {
            $run = $service->runForUser(Auth::id(), [
                'scope' => $validated['scope'] ?? null,
                'source_mode' => $validated['source_mode'],
                'manual_payload' => $validated['manual_payload'] ?? null,
                'include_catalog' => $request->boolean('include_catalog'),
                'actor_user_id' => Auth::id(),
            ]);

            return redirect()->route('discovery.show', $run->id)
                ->with('success', 'Discovery run selesai dan inventory berhasil diperbarui.');
        } catch (\InvalidArgumentException $exception) {
            return redirect()->route('discovery.index')
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            return redirect()->route('discovery.index')
                ->with('error', 'Discovery run gagal. Periksa format input dan coba lagi.');
        }
    }

    /**
     * Build discovery run query by role scope.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function runQuery()
    {
        $query = AssetDiscoveryRun::query();
        if (!Auth::user()->hasGlobalAssetVisibility()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }
}
