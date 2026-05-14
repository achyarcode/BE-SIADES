<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisSuratController extends Controller
{
    public function index()
    {
        return response()->json(
            JenisSurat::query()
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'deskripsi', 'is_active'])
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('jenis_surats', 'nama')],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $jenisSurat = JenisSurat::create([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Jenis surat berhasil ditambahkan',
            'data' => $jenisSurat,
        ], 201);
    }

    public function destroy($id)
    {
        $jenisSurat = JenisSurat::findOrFail($id);
        $jenisSurat->delete();

        return response()->json([
            'message' => 'Jenis surat berhasil dihapus',
        ]);
    }
}
