<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratController extends Controller
{
    public function index(Request $request)
    {
        $query = Surat::with('user')->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->limit ?? 10));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_surat' => 'required|string',
            'keperluan' => 'required|string',
            'file' => 'required|file|mimes:pdf|max:2048',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('surats', 'public');
        }

        $surat = Surat::create([
            'user_id' => $request->user()->id,
            'nama_pemohon' => $request->user()->name,
            'jenis_surat' => $request->jenis_surat,
            'keperluan' => $request->keperluan,
            'file_path' => $filePath,
            'status' => 'PENDING',
        ]);

        return response()->json($surat, 201);
    }

    public function approve(Request $request, $id)
    {
        $surat = Surat::findOrFail($id);
        
        // 1. Simpan PDF yang sudah ditandatangani jika ada
        if ($request->hasFile('signed_pdf')) {
            // Hapus file lama jika ingin menghemat ruang, 
            // atau simpan sebagai versi baru. Di sini kita timpa path-nya.
            if ($surat->file_path) {
                Storage::disk('public')->delete($surat->file_path);
            }
            
            $filePath = $request->file('signed_pdf')->store('surats/signed', 'public');
            $surat->file_path = $filePath;
        }

        // 2. Update status
        $surat->status = 'DISETUJUI';
        $surat->save();

        return response()->json([
            'message' => 'Surat berhasil disetujui',
            'data' => $surat
        ]);
    }

    public function reject(Request $request, $id)
    {
        $surat = Surat::findOrFail($id);
        $surat->update(['status' => 'DITOLAK']);

        return response()->json($surat);
    }
}
