<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class PerangkatDesaController extends Controller
{
    /**
     * List all admins and super-admins
     */
    public function index(Request $request)
    {
        $query = User::with('roles')->whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'super-admin']);
        });

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->paginate($request->limit ?? 10)->through(function ($user) {
                return [
                    'id' => $user->id,
                    'namaLengkap' => $user->name,
                    'username' => $user->username,
                    'nik' => $user->nik,
                    'role' => $user->roles->pluck('name')->first(),
                ];
            })
        );
    }

    /**
     * Search users by NIK or Username (for assigning new admin)
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json([]);
        }

        $users = User::with('roles')
            ->where('username', 'like', "%{$query}%")
            ->orWhere('nik', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'username', 'nik']);

        // Attach their current role
        $users->each(function($user) {
            $user->current_role = $user->roles->pluck('name')->first() ?? 'warga';
        });

        return response()->json($users);
    }

    /**
     * Assign a role to a user
     */
    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,super-admin'
        ]);

        $user = User::findOrFail($request->user_id);
        
        // Synchronize to the new role (removes other roles)
        $user->syncRoles([$request->role]);

        return response()->json(['message' => "Role {$request->role} assigned successfully."]);
    }

    /**
     * Revoke admin/super-admin role and return to warga
     */
    public function revokeRole(Request $request, User $user)
    {
        // Prevent demoting self if it's the current user
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Anda tidak bisa menurunkan jabatan diri sendiri.'], 403);
        }

        // Prevent demoting the master account
        if ($user->username === 'kepaladesa') {
            return response()->json(['message' => 'Akun utama Kepala Desa tidak bisa diubah.'], 403);
        }

        // Sync to default role
        $user->syncRoles(['warga']);

        return response()->json(['message' => 'Role berhasil dicabut. Akun kembali menjadi warga.']);
    }
}
