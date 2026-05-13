<?php

namespace App\Http\Controllers;

use App\Models\Katalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KatalogController extends Controller
{
    // ==================================================
    // 1. TAMPILKAN SEMUA PRODUK (Bisa diakses tanpa login)
    // ==================================================
    public function publicIndex()
    {
        $katalogs = Katalog::with('user:id,name,no_telp')->latest()->get();

        return response()->json([
            'message' => 'Berhasil mengambil data E-Katalog',
            'data' => $katalogs
        ], 200);
    }

    // ==================================================
    // 2. TAMPILKAN SEMUA PRODUK (Admin - untuk persetujuan)
    // ==================================================
    public function index(Request $request)
    {
        $query = Katalog::with('user:id,name,username');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(10));
    }

    // ==================================================
    // 3. TAMBAH PRODUK BARU (Wajib Login)
    // ==================================================
    public function store(Request $request)
    {
        $request->validate([
            'nama_produk' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'nullable|numeric',
            'kontak_wa' => 'nullable|string|max:15',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('gambar')) {
            $imagePath = $request->file('gambar')->store('katalog_images', 'public');
        }

        $katalog = Katalog::create([
            'user_id' => Auth::id(),
            'nama_produk' => $request->nama_produk,
            'deskripsi' => $request->deskripsi,
            'harga' => $request->harga,
            'kontak_wa' => $request->kontak_wa,
            'gambar' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke E-Katalog',
            'data' => $katalog
        ], 201);
    }

    // ==================================================
    // 4. UPDATE PRODUK (Wajib Login & Pemilik Produk)
    // ==================================================
    public function update(Request $request, $id)
    {
        $katalog = Katalog::find($id);

        if (!$katalog) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        if ($katalog->user_id !== Auth::id() && !Auth::user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Akses ditolak! Anda bukan pemilik produk ini.'], 403);
        }

        $request->validate([
            'nama_produk' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'nullable|numeric',
            'kontak_wa' => 'nullable|string|max:15',
        ]);

        $katalog->update([
            'nama_produk' => $request->nama_produk,
            'deskripsi' => $request->deskripsi,
            'harga' => $request->harga,
            'kontak_wa' => $request->kontak_wa,
        ]);

        return response()->json([
            'message' => 'Produk berhasil diperbarui',
            'data' => $katalog
        ], 200);
    }

    // ==================================================
    // 5. HAPUS PRODUK (Wajib Login & Pemilik Produk)
    // ==================================================
    public function destroy($id)
    {
        $katalog = Katalog::find($id);

        if (!$katalog) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        if ($katalog->user_id !== Auth::id() && !Auth::user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Akses ditolak! Anda bukan pemilik produk ini.'], 403);
        }

        if ($katalog->gambar) {
            Storage::disk('public')->delete($katalog->gambar);
        }

        $katalog->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus'
        ], 200);
    }

    // ==================================================
    // 6. UPDATE STATUS PRODUK (Admin only)
    // ==================================================
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Aktif,Nonaktif,Menunggu'
        ]);

        $katalog = Katalog::findOrFail($id);
        $katalog->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status katalog berhasil diperbarui',
            'data' => $katalog
        ]);
    }
}
