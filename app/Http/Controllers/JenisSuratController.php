<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $existing = JenisSurat::query()
            ->where('nama', $validated['nama'])
            ->first();

        if ($existing?->is_active) {
            throw ValidationException::withMessages([
                'nama' => ['Nama jenis surat sudah digunakan.'],
            ]);
        }

        if ($existing) {
            $existing->update([
                'deskripsi' => $validated['deskripsi'] ?? $existing->deskripsi,
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Jenis surat berhasil ditambahkan',
                'data' => $existing->fresh(),
            ], 201);
        }

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
        $jenisSurat->update(['is_active' => false]);

        return response()->json([
            'message' => 'Jenis surat berhasil dihapus',
        ]);
    }
}
