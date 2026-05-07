<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /admin/warga
     * List all warga (citizens) with search and pagination
     */
    public function index(Request $request)
    {
        $query = User::role('user')->orWhere(function ($q) {
            $q->whereDoesntHave('roles');
        });

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('username', 'like', '%' . $request->search . '%')
                  ->orWhere('nik', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->paginate($request->limit ?? 10));
    }

    /**
     * POST /admin/warga
     * Create a new warga (citizen) user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users|max:255',
            'password' => 'required|string|min:6',
            'nik' => 'required|string|size:16|unique:users',
            'no_kk' => 'nullable|string|size:16',
            'no_telp' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:users',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'alamat' => 'nullable|string',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'], // Auto-hashed via User model cast
            'nik' => $validated['nik'],
            'no_kk' => $validated['no_kk'] ?? null,
            'no_telp' => $validated['no_telp'] ?? null,
            'email' => $validated['email'] ?? null,
            'rt' => $validated['rt'] ?? null,
            'rw' => $validated['rw'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
            'tempat_lahir' => $validated['tempat_lahir'] ?? null,
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
        ]);

        // Assign 'user' role
        $user->assignRole('user');

        return response()->json([
            'message' => 'Warga berhasil ditambahkan',
            'user' => $user->load('roles')
        ], 201);
    }

    /**
     * PUT /admin/warga/{id}
     * Update an existing warga (citizen) user
     */
    public function update(Request $request, User $id)
    {
        $user = $id;

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|string|min:6',
            'nik' => [
                'sometimes',
                'string',
                'size:16',
                Rule::unique('users')->ignore($user->id),
            ],
            'no_kk' => 'nullable|string|size:16',
            'no_telp' => 'nullable|string|max:20',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'alamat' => 'nullable|string',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
        ]);

        // Update user (password will be auto-hashed if present)
        $user->update($validated);

        return response()->json([
            'message' => 'Warga berhasil diperbarui',
            'user' => $user->load('roles')
        ], 200);
    }

    /**
     * DELETE /admin/warga/{id}
     * Delete a warga (citizen) user
     */
    public function destroy(User $id)
    {
        $user = $id;

        // Prevent deleting super-admin users
        if ($user->hasRole('super-admin')) {
            return response()->json([
                'message' => 'Tidak dapat menghapus pengguna dengan role super-admin'
            ], 403);
        }

        // Delete all Sanctum tokens for this user first
        $user->tokens()->delete();

        // Delete the user
        $user->delete();

        return response()->json([
            'message' => 'Warga berhasil dihapus'
        ], 200);
    }
}

