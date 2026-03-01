<?php

namespace App\Http\Controllers;

use App\User;
use Auth;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * Display users with role and status control.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $users = User::orderBy('id')->paginate(20);
        $summary = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'admins' => User::whereIn('role', ['superadmin', 'admin'])->count(),
        ];

        $roles = ['superadmin', 'admin', 'auditor', 'operator', 'viewer'];

        return view('users.index', compact('users', 'summary', 'roles'));
    }

    /**
     * Update user role and active status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:superadmin,admin,auditor,operator,viewer',
            'is_active' => 'required|in:0,1',
        ]);

        $target = User::findOrFail($id);
        $actor = Auth::user();
        $newRole = $request->input('role');
        $newActive = (bool) $request->input('is_active');

        if ($actor->role === 'admin' && in_array($newRole, ['superadmin'])) {
            return redirect()->route('users.index')->with('error', 'Admin tidak boleh assign role superadmin.');
        }

        if ($actor->role === 'admin' && $target->role === 'superadmin') {
            return redirect()->route('users.index')->with('error', 'Admin tidak boleh mengubah akun superadmin.');
        }

        if ($target->id === $actor->id && !$newActive) {
            return redirect()->route('users.index')->with('error', 'Tidak dapat menonaktifkan akun sendiri.');
        }

        if ($actor->role !== 'superadmin' && $target->role === 'superadmin') {
            return redirect()->route('users.index')->with('error', 'Hanya superadmin yang bisa mengubah akun superadmin.');
        }

        $target->update([
            'role' => $newRole,
            'is_active' => $newActive,
        ]);

        return redirect()->route('users.index')->with('success', 'Role dan status user berhasil diperbarui.');
    }
}

