<?php

namespace App\Http\Controllers;

use App\AssetActivityLog;
use App\AssetPolicyViolation;
use App\Services\AssetPolicyScannerService;
use Auth;

class AssetPolicyController extends Controller
{
    /**
     * Display policy violation dashboard.
     *
     * @param  \App\Services\AssetPolicyScannerService  $scanner
     * @return \Illuminate\Contracts\View\View
     */
    public function index(AssetPolicyScannerService $scanner)
    {
        $user = Auth::user();
        $scopeUserId = $user->hasGlobalAssetVisibility() ? null : $user->id;

        $scanStats = null;
        if (request()->boolean('refresh') && ($user->canRunDiscovery() || $user->canApproveAssetChanges())) {
            $scanStats = $scanner->scan($scopeUserId);
        }

        $query = AssetPolicyViolation::with('asset');
        if ($scopeUserId !== null) {
            $query->where('user_id', $scopeUserId);
        }

        $summary = [
            'open_total' => (clone $query)->where('status', 'open')->count(),
            'critical' => (clone $query)->where('status', 'open')->where('severity', 'critical')->count(),
            'high' => (clone $query)->where('status', 'open')->where('severity', 'high')->count(),
            'medium' => (clone $query)->where('status', 'open')->where('severity', 'medium')->count(),
            'resolved_total' => (clone $query)->where('status', 'resolved')->count(),
        ];

        $violations = $query
            ->orderByRaw("FIELD(status, 'open', 'resolved')")
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderBy('detected_at', 'desc')
            ->paginate(20);

        return view('policies.index', compact('summary', 'violations', 'scanStats'));
    }

    /**
     * Mark policy violation as resolved.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resolve($id)
    {
        $violation = AssetPolicyViolation::with('asset')->findOrFail($id);

        if (!Auth::user()->hasGlobalAssetVisibility() && $violation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to resolve this violation.');
        }

        $violation->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        if ($violation->asset) {
            AssetActivityLog::create([
                'asset_id' => $violation->asset->id,
                'actor_user_id' => Auth::id(),
                'action' => 'policy_violation_resolved_manual',
                'message' => 'Policy violation resolved manually',
                'context' => json_encode([
                    'policy_code' => $violation->policy_code,
                    'violation_id' => $violation->id,
                ]),
            ]);
        }

        return redirect()->route('policies.index')->with('success', 'Violation ditandai resolved.');
    }
}
