<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StrukturDesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StrukturDesaController extends Controller
{
    // 1. TAMPILKAN SEMUA DATA (Bisa diakses Publik/Warga)
    public function index()
    {
        $struktur = StrukturDesa::all();

        return response()->json([
            'success' => true,
            'data' => $struktur,
        ], 200);
    }

    // 2. TAMBAH DATA BARU (Hanya Admin)
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'rw' => 'nullable|string|max:3',
            'rt' => 'nullable|string|max:3',
            'alamat' => 'nullable|string|max:500',
            'no_wa' => ['nullable', 'regex:/^08\d{8,11}$/'],
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|mimetypes:image/jpeg,image/png|max:2048',
        ]);

        // Logika upload foto jika ada berkas yang dikirim
        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('struktur-desa', 'public');
        }

        $struktur = StrukturDesa::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Data struktur desa berhasil ditambahkan',
            'data' => $struktur,
        ], 201);
    }

    // 3. TAMPILKAN SATU DATA SPESIFIK
    public function show($id)
    {
        $struktur = StrukturDesa::find($id);
        if (! $struktur) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $struktur], 200);
    }

    // 4. EDIT/UPDATE DATA (Hanya Admin)
    public function update(Request $request, $id)
    {
        $struktur = StrukturDesa::find($id);
        if (! $struktur) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'jabatan' => 'sometimes|required|string|max:255',
            'rw' => 'nullable|string|max:3',
            'rt' => 'nullable|string|max:3',
            'alamat' => 'nullable|string|max:500',
            'no_wa' => ['nullable', 'regex:/^08\d{8,11}$/'],
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|mimetypes:image/jpeg,image/png|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($struktur->foto) {
                Storage::disk('public')->delete($struktur->foto);
            }
            $data['foto'] = $request->file('foto')->store('struktur-desa', 'public');
        }

        $struktur->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data struktur desa berhasil diperbarui',
            'data' => $struktur,
        ], 200);
    }

    // 5. HAPUS DATA (Hanya Admin)
    public function destroy($id)
    {
        $struktur = StrukturDesa::find($id);
        if (! $struktur) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        if ($struktur->foto) {
            Storage::disk('public')->delete($struktur->foto);
        }

        $struktur->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data struktur desa berhasil dihapus',
        ], 200);
    }
}
