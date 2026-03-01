<?php

namespace App\Http\Controllers;

use App\Asset;
use App\AssetActivityLog;
use App\AssetChangeRequest;
use Auth;
use Illuminate\Http\Request;

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
                'source',
                'discovery_confidence',
                'tags',
                'last_seen_at',
                'notes',
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

            $updateData['risk_score'] = $this->calculateRiskScore(
                $updateData['criticality'] ?? $asset->criticality,
                $updateData['status'] ?? $asset->status,
                $updateData['discovery_confidence'] ?? $asset->discovery_confidence,
                $updateData['last_seen_at'] ?? $asset->last_seen_at
            );

            $asset->update($updateData);
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
}
