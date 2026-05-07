<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /admin/users
     * List all warga (citizens) with search and pagination
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Only show users with 'warga' role, or no roles (unassigned citizens)
        $query->where(function ($q) {
            $q->whereHas('roles', function ($rq) {
                $rq->where('name', 'warga');
            })->orWhereDoesntHave('roles');
        });

        // Search filter
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('username', 'like', '%' . $search . '%')
                  ->orWhere('nik', 'like', '%' . $search . '%');
            });
        }

        // RT filter
        if ($request->rt) {
            $query->where('rt', $request->rt);
        }

        // RW filter
        if ($request->rw) {
            $query->where('rw', $request->rw);
        }

        return response()->json(
            $query->paginate($request->limit ?? 10)->through(function ($user) {
                return [
                    'id' => $user->id,
                    'namaLengkap' => $user->name,
                    'nomorWA' => $user->no_telp,
                    'jenisKelamin' => $user->jenis_kelamin === 'Laki-laki' ? 'L' : 'P',
                    'alamat' => $user->alamat,
                    'rt' => $user->rt,
                    'rw' => $user->rw,
                    'nik' => $user->nik,
                    'tempatLahir' => $user->tempat_lahir,
                    'tanggalLahir' => $user->tanggal_lahir,
                    'email' => $user->email,
                ];
            })
        );
    }

    /**
     * POST /admin/users
     * Create a new warga (citizen) user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'namaLengkap' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'password' => 'nullable|string|min:6', // Optional, defaults to 'password123' if empty
            'nik' => 'required|string|size:16|unique:users,nik',
            'no_kk' => 'nullable|string|size:16',
            'nomorWA' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:users,email',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'alamat' => 'nullable|string',
            'jenisKelamin' => 'nullable|in:L,P',
            'tempatLahir' => 'nullable|string|max:255',
            'tanggalLahir' => 'nullable|date',
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['namaLengkap'],
            'username' => $validated['username'],
            'password' => $validated['password'] ?? 'password123', 
            'nik' => $validated['nik'],
            'no_kk' => $validated['no_kk'] ?? null,
            'no_telp' => $validated['nomorWA'] ?? null,
            'email' => $validated['email'] ?? null,
            'rt' => $validated['rt'] ?? null,
            'rw' => $validated['rw'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'jenis_kelamin' => ($validated['jenisKelamin'] ?? 'L') === 'L' ? 'Laki-laki' : 'Perempuan',
            'tempat_lahir' => $validated['tempatLahir'] ?? null,
            'tanggal_lahir' => $validated['tanggalLahir'] ?? null,
        ]);

        // Assign 'warga' role (consistency with AuthController)
        $user->assignRole('warga');

        return response()->json([
            'message' => 'Warga berhasil ditambahkan',
            'user' => [
                'id' => $user->id,
                'namaLengkap' => $user->name,
                'nomorWA' => $user->no_telp,
            ]
        ], 201);
    }

    /**
     * PUT /admin/users/{id}
     * Update an existing warga (citizen) user
     */
    public function update(Request $request, User $user)
    {

        $validated = $request->validate([
            'namaLengkap' => 'sometimes|string|max:255',
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
            'nomorWA' => 'nullable|string|max:20',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'alamat' => 'nullable|string',
            'jenisKelamin' => 'nullable|in:L,P',
            'tempatLahir' => 'nullable|string|max:255',
            'tanggalLahir' => 'nullable|date',
        ]);

        // Map fields to database columns
        $data = [];
        if (isset($validated['namaLengkap'])) $data['name'] = $validated['namaLengkap'];
        if (isset($validated['username'])) $data['username'] = $validated['username'];
        if (isset($validated['password'])) $data['password'] = $validated['password'];
        if (isset($validated['nik'])) $data['nik'] = $validated['nik'];
        if (isset($validated['no_kk'])) $data['no_kk'] = $validated['no_kk'];
        if (isset($validated['nomorWA'])) $data['no_telp'] = $validated['nomorWA'];
        if (isset($validated['email'])) $data['email'] = $validated['email'];
        if (isset($validated['rt'])) $data['rt'] = $validated['rt'];
        if (isset($validated['rw'])) $data['rw'] = $validated['rw'];
        if (isset($validated['alamat'])) $data['alamat'] = $validated['alamat'];
        if (isset($validated['jenisKelamin'])) $data['jenis_kelamin'] = $validated['jenisKelamin'] === 'L' ? 'Laki-laki' : 'Perempuan';
        if (isset($validated['tempatLahir'])) $data['tempat_lahir'] = $validated['tempatLahir'];
        if (isset($validated['tanggalLahir'])) $data['tanggal_lahir'] = $validated['tanggalLahir'];

        // Update user
        $user->update($data);

        return response()->json([
            'message' => 'Warga berhasil diperbarui',
            'user' => [
                'id' => $user->id,
                'namaLengkap' => $user->name,
                'nomorWA' => $user->no_telp,
            ]
        ], 200);
    }

    /**
     * DELETE /admin/users/{id}
     * Delete a warga (citizen) user
     */
    public function destroy(User $user)
    {

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
