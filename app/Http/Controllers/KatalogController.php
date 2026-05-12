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
    public function index()
    {
        // Ambil semua produk, beserta nama & no telp pemiliknya (relasi user)
        // latest() agar produk terbaru muncul paling atas
        $katalogs = Katalog::with('user:id,name,no_telp')->latest()->get();

        return response()->json([
            'message' => 'Berhasil mengambil data E-Katalog',
            'data' => $katalogs
        ], 200);
    }

    // ==================================================
    // 2. TAMBAH PRODUK BARU (Wajib Login)
    // ==================================================
    public function store(Request $request)
    {
        // a. Validasi Input
        $request->validate([
            'nama_produk' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'nullable|numeric',
            'kontak_wa' => 'nullable|string|max:15',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // File foto maks 2MB
        ]);

        // b. Proses Upload Gambar (Jika ada)
        $imagePath = null;
        if ($request->hasFile('gambar')) {
            // Simpan gambar ke folder storage/app/public/katalog_images
            $imagePath = $request->file('gambar')->store('katalog_images', 'public');
        }

        // c. Simpan ke Database
        $katalog = Katalog::create([
            'user_id' => Auth::id(), // Otomatis mengisi ID dari user yang sedang login
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
    // 3. UPDATE PRODUK (Wajib Login & Pemilik Produk)
    // ==================================================
    public function update(Request $request, $id)
    {
        $katalog = Katalog::find($id);

        if (!$katalog) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        // CEK KEPEMILIKAN: Yang boleh ngedit cuma yang punya akun atau Super Admin
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
    // 4. HAPUS PRODUK (Wajib Login & Pemilik Produk)
    // ==================================================
    public function destroy($id)
    {
        $katalog = Katalog::find($id);

        if (!$katalog) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        // CEK KEPEMILIKAN
        if ($katalog->user_id !== Auth::id() && !Auth::user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Akses ditolak! Anda bukan pemilik produk ini.'], 403);
        }

        // Hapus gambar dari server (jika produk tersebut punya gambar)
        if ($katalog->gambar) {
            Storage::disk('public')->delete($katalog->gambar);
        }

        $katalog->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus'
        ], 200);
    }
}