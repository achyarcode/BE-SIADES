<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Katalog;

class KatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = Katalog::with('user:id,name,username');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_usaha', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(10));
    }

    public function publicIndex(Request $request)
    {
        $query = Katalog::with('user:id,name')->where('status', 'Aktif');

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        return response()->json($query->paginate(10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_usaha' => 'required|string|max:255',
            'kategori' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'nullable|numeric',
            'satuan' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'Menunggu';

        $katalog = Katalog::create($validated);

        return response()->json([
            'message' => 'Katalog berhasil ditambahkan dan menunggu persetujuan',
            'data' => $katalog
        ], 201);
    }

    public function destroy($id)
    {
        $katalog = Katalog::findOrFail($id);
        $katalog->delete();

        return response()->json(['message' => 'Katalog berhasil dihapus']);
    }
}
