<?php

namespace App\Http\Controllers;

use App\Http\Requests\KatalogIndexRequest;
use App\Http\Requests\KatalogStatusUpdateRequest;
use App\Models\Katalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KatalogController extends Controller
{
    // ==================================================
    // 1. TAMPILKAN SEMUA PRODUK (Bisa diakses tanpa login)
    // ==================================================
    public function publicIndex()
    {
        $katalogs = Katalog::with('user:id,name,no_telp')
            ->where('status', Katalog::STATUS_AKTIF)
            ->where('warga_status', Katalog::STATUS_AKTIF)
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Berhasil mengambil data E-Katalog',
            'data' => $katalogs,
        ], 200);
    }

    // ==================================================
    // 2. TAMPILKAN SEMUA PRODUK (Admin - untuk persetujuan)
    // ==================================================
    public function index(KatalogIndexRequest $request)
    {
        $validated = $request->validated();

        $query = Katalog::with('user:id,name,username')->latest();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($validated['limit'] ?? 10)->through(function (Katalog $katalog) {
            $effectiveStatus = $katalog->status === Katalog::STATUS_AKTIF
                ? ($katalog->warga_status ?? Katalog::STATUS_AKTIF)
                : $katalog->status;

            return [
                'id' => $katalog->id,
                'user_id' => $katalog->user_id,
                'kategori_katalog_id' => $katalog->kategori_katalog_id,
                'nama_produk' => $katalog->nama_produk,
                'deskripsi' => $katalog->deskripsi,
                'harga' => $katalog->harga,
                'gambar' => $katalog->gambar,
                'kontak_wa' => $katalog->kontak_wa,
                'status' => $katalog->status,
                'warga_status' => $katalog->warga_status ?? Katalog::STATUS_AKTIF,
                'effective_status' => $effectiveStatus,
                'created_at' => $katalog->created_at,
                'updated_at' => $katalog->updated_at,
                'user' => $katalog->user,
            ];
        });

        return response()->json($paginated);
    }

    // ==================================================
    // 1b. TAMPILKAN KATALOG MILIK WARGA LOGIN
    // ==================================================
    public function myKatalog(Request $request)
    {
        $katalogs = Katalog::with('user:id,name,no_telp')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (Katalog $katalog) {
                $effectiveStatus = $katalog->status === Katalog::STATUS_AKTIF
                    ? ($katalog->warga_status ?? Katalog::STATUS_AKTIF)
                    : $katalog->status;

                return [
                    'id' => $katalog->id,
                    'nama_produk' => $katalog->nama_produk,
                    'deskripsi' => $katalog->deskripsi,
                    'harga' => $katalog->harga,
                    'kontak_wa' => $katalog->kontak_wa,
                    'gambar' => $katalog->gambar,
                    'status' => $katalog->status,
                    'warga_status' => $katalog->warga_status ?? Katalog::STATUS_AKTIF,
                    'effective_status' => $effectiveStatus,
                    'user' => $katalog->user,
                    'created_at' => $katalog->created_at,
                    'updated_at' => $katalog->updated_at,
                ];
            });

        return response()->json([
            'message' => 'Berhasil mengambil katalog milik Anda',
            'data' => $katalogs,
        ], 200);
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
            $file = $request->file('gambar');
            $extension = strtolower($file->getClientOriginalExtension());

            // Compress JPEG uploads to reduce storage use.
            if (in_array($extension, ['jpg', 'jpeg'], true)) {
                $binary = @file_get_contents($file->getRealPath());
                if ($binary !== false && function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
                    $image = @imagecreatefromstring($binary);
                    if ($image !== false) {
                        $maxWidth = 1600;
                        $width = imagesx($image);
                        $height = imagesy($image);

                        if ($width > $maxWidth) {
                            $newWidth = $maxWidth;
                            $newHeight = (int) round(($height * $newWidth) / $width);
                            $resized = imagecreatetruecolor($newWidth, $newHeight);
                            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            imagedestroy($image);
                            $image = $resized;
                        }

                        ob_start();
                        imagejpeg($image, null, 72);
                        $compressed = ob_get_clean();
                        imagedestroy($image);

                        if ($compressed !== false) {
                            $fileName = Str::uuid()->toString().'.jpg';
                            $imagePath = 'katalog_images/'.$fileName;
                            Storage::disk('public')->put($imagePath, $compressed);
                        }
                    }
                }
            }

            if ($imagePath === null) {
                $imagePath = $file->store('katalog_images', 'public');
            }
        }

        $katalog = Katalog::create([
            'user_id' => Auth::id(),
            'nama_produk' => $request->nama_produk,
            'deskripsi' => $request->deskripsi,
            'harga' => $request->harga,
            'kontak_wa' => $request->kontak_wa,
            'gambar' => $imagePath,
            'status' => Katalog::STATUS_MENUNGGU,
            'warga_status' => Katalog::STATUS_AKTIF,
        ]);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke E-Katalog',
            'data' => $katalog,
        ], 201);
    }

    // ==================================================
    // 4. UPDATE PRODUK (Wajib Login & Pemilik Produk)
    // ==================================================
    public function update(Request $request, $id)
    {
        $katalog = Katalog::find($id);

        if (! $katalog) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        if ($katalog->user_id !== Auth::id() && ! Auth::user()->hasRole('super-admin')) {
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
            'data' => $katalog,
        ], 200);
    }

    // ==================================================
    // 5. HAPUS PRODUK (Wajib Login & Pemilik Produk)
    // ==================================================
    public function destroy($id)
    {
        $katalog = Katalog::find($id);

        if (! $katalog) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        if ($katalog->user_id !== Auth::id() && ! Auth::user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Akses ditolak! Anda bukan pemilik produk ini.'], 403);
        }

        if ($katalog->gambar) {
            Storage::disk('public')->delete($katalog->gambar);
        }

        $katalog->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus',
        ], 200);
    }

    // ==================================================
    // 6. UPDATE STATUS PRODUK (Admin only)
    // ==================================================
    public function updateStatus(KatalogStatusUpdateRequest $request, $id)
    {
        $validated = $request->validated();

        $katalog = Katalog::findOrFail($id);
        $katalog->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status katalog berhasil diperbarui',
            'data' => $katalog,
        ]);
    }

    // ==================================================
    // 7. UPDATE STATUS KATALOG DARI WARGA (owner only)
    // ==================================================
    public function updateWargaStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'warga_status' => 'required|in:'.implode(',', Katalog::wargaStatuses()),
        ]);

        $katalog = Katalog::findOrFail($id);

        if ($katalog->user_id !== Auth::id()) {
            return response()->json(['message' => 'Akses ditolak! Anda bukan pemilik produk ini.'], 403);
        }

        if (($katalog->warga_status ?? Katalog::STATUS_AKTIF) === $validated['warga_status']) {
            return response()->json([
                'message' => 'Status warga sudah sama, tidak ada perubahan',
                'data' => $katalog,
            ]);
        }

        $katalog->update(['warga_status' => $validated['warga_status']]);

        return response()->json([
            'message' => 'Status katalog warga berhasil diperbarui',
            'data' => $katalog,
        ]);
    }
}
