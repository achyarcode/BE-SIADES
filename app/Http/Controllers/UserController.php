<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\WargaProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'namaLengkap' => $user->name,
            'nik' => $user->nik,
            'nomorkk' => $user->no_kk,
            'username' => $user->username,
            'alamat' => $user->alamat,
            'mustUpdateCredentials' => (bool) $user->must_update_credentials,
        ]);
    }

    public function updateProfile(WargaProfileUpdateRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->update([
            'name' => $validated['namaLengkap'],
            'username' => $validated['username'],
            'no_kk' => $validated['nomorkk'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'id' => $user->id,
                'namaLengkap' => $user->name,
                'nik' => $user->nik,
                'nomorkk' => $user->no_kk,
                'username' => $user->username,
                'alamat' => $user->alamat,
            ],
        ]);
    }

    /**
     * GET /admin/users
     * List all warga (citizens) with search and pagination
     */
    public function index(UserIndexRequest $request)
    {
        $validated = $request->validated();
        $query = User::query();

        // Only show users with 'warga' role, or no roles (unassigned citizens)
        $query->where(function ($q) {
            $q->whereHas('roles', function ($rq) {
                $rq->where('name', 'warga');
            })->orWhereDoesntHave('roles');
        });

        // Search filter
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%')
                    ->orWhere('nik', 'like', '%'.$search.'%');
            });
        }

        // RT filter
        if (! empty($validated['rt'])) {
            $query->where('rt', $validated['rt']);
        }

        // RW filter
        if (! empty($validated['rw'])) {
            $query->where('rw', $validated['rw']);
        }

        return response()->json(
            $query->paginate($validated['limit'] ?? 10)->through(function ($user) {
                $jenisKelamin = match ($user->jenis_kelamin) {
                    'Laki-laki' => 'L',
                    'Perempuan' => 'P',
                    default => '-',
                };

                return [
                    'id' => $user->id,
                    'namaLengkap' => $user->name,
                    'nomorWA' => $user->no_telp,
                    'jenisKelamin' => $jenisKelamin,
                    'alamat' => $user->alamat,
                    'rt' => $user->rt,
                    'rw' => $user->rw,
                    'nik' => $user->nik,
                    'tempatLahir' => $user->tempat_lahir,
                    'tanggalLahir' => $user->tanggal_lahir,
                    'email' => $user->email,
                    'mustUpdateCredentials' => (bool) $user->must_update_credentials,
                ];
            })
        );
    }

    /**
     * POST /admin/users
     * Create a new warga (citizen) user
     */
    public function store(UserStoreRequest $request)
    {
        $validated = $request->validated();
        $usernameBase = $validated['username'] ?? $validated['nik'];
        $username = $usernameBase;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $usernameBase.'_'.$suffix;
            $suffix++;
        }

        // Create user
        $user = User::create([
            'name' => $validated['namaLengkap'],
            'username' => $username,
            'password' => $validated['password'] ?? 'password123',
            'nik' => $validated['nik'],
            'no_kk' => $validated['no_kk'] ?? null,
            'no_telp' => $validated['nomorWA'] ?? null,
            'email' => $validated['email'] ?? null,
            'rt' => $validated['rt'] ?? null,
            'rw' => $validated['rw'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'jenis_kelamin' => ($validated['jenisKelamin'] ?? null) === 'L'
                ? 'Laki-laki'
                : (($validated['jenisKelamin'] ?? null) === 'P' ? 'Perempuan' : null),
            'tempat_lahir' => $validated['tempatLahir'] ?? null,
            'tanggal_lahir' => $validated['tanggalLahir'] ?? null,
            'must_update_credentials' => array_key_exists('mustUpdateCredentials', $validated)
                ? (bool) $validated['mustUpdateCredentials']
                : true,
        ]);

        // Assign 'warga' role (consistency with AuthController)
        $user->assignRole('warga');

        return response()->json([
            'message' => 'Warga berhasil ditambahkan',
            'user' => [
                'id' => $user->id,
                'namaLengkap' => $user->name,
                'nomorWA' => $user->no_telp,
            ],
        ], 201);
    }

    /**
     * PUT /admin/users/{id}
     * Update an existing warga (citizen) user
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $validated = $request->validated();

        // Map fields to database columns
        $data = [];
        if (isset($validated['namaLengkap'])) {
            $data['name'] = $validated['namaLengkap'];
        }
        if (isset($validated['username'])) {
            $data['username'] = $validated['username'];
        }
        if (isset($validated['password'])) {
            $data['password'] = $validated['password'];
        }
        if (isset($validated['nik'])) {
            $data['nik'] = $validated['nik'];
        }
        if (isset($validated['no_kk'])) {
            $data['no_kk'] = $validated['no_kk'];
        }
        if (isset($validated['nomorWA'])) {
            $data['no_telp'] = $validated['nomorWA'];
        }
        if (isset($validated['email'])) {
            $data['email'] = $validated['email'];
        }
        if (isset($validated['rt'])) {
            $data['rt'] = $validated['rt'];
        }
        if (isset($validated['rw'])) {
            $data['rw'] = $validated['rw'];
        }
        if (isset($validated['alamat'])) {
            $data['alamat'] = $validated['alamat'];
        }
        if (isset($validated['jenisKelamin'])) {
            $data['jenis_kelamin'] = $validated['jenisKelamin'] === 'L' ? 'Laki-laki' : 'Perempuan';
        }
        if (isset($validated['tempatLahir'])) {
            $data['tempat_lahir'] = $validated['tempatLahir'];
        }
        if (isset($validated['tanggalLahir'])) {
            $data['tanggal_lahir'] = $validated['tanggalLahir'];
        }
        if (array_key_exists('mustUpdateCredentials', $validated)) {
            $data['must_update_credentials'] = (bool) $validated['mustUpdateCredentials'];
        }

        // Update user
        $user->update($data);

        return response()->json([
            'message' => 'Warga berhasil diperbarui',
            'user' => [
                'id' => $user->id,
                'namaLengkap' => $user->name,
                'nomorWA' => $user->no_telp,
            ],
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
                'message' => 'Tidak dapat menghapus pengguna dengan role super-admin',
            ], 403);
        }

        // Delete all Sanctum tokens for this user first
        $user->tokens()->delete();

        // Delete the user
        $user->delete();

        return response()->json([
            'message' => 'Warga berhasil dihapus',
        ], 200);
    }
}
