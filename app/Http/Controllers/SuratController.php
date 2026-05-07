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
        $request->validate([
            'signature_position' => 'nullable|array',
            'signed_pdf' => 'nullable|file|mimes:pdf|max:4096',
        ]);

        $surat = Surat::findOrFail($id);
        
        // 1. Save signature position if provided
        if ($request->has('signature_position')) {
            $surat->signature_position = $request->signature_position;
        }

        // 2. Save signed PDF if provided (Phase 2 will handle server-side signing, 
        // for now we support manual upload of signed PDF if needed)
        if ($request->hasFile('signed_pdf')) {
            if ($surat->file_path) {
                Storage::disk('public')->delete($surat->file_path);
            }
            
            $filePath = $request->file('signed_pdf')->store('surats/signed', 'public');
            $surat->file_path = $filePath;
        }

        // 3. Update status
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

    public function download($id)
    {
        $surat = Surat::findOrFail($id);
        $user = auth()->user();

        // Security checks:
        // 1. Must be DISETUJUI
        if ($surat->status !== 'DISETUJUI') {
            return response()->json(['message' => 'Dokumen belum disetujui'], 403);
        }

        // 2. Must be owner OR admin
        $isOwner = $surat->user_id === $user->id;
        $isAdmin = $user->hasRole('admin') || $user->hasRole('super-admin'); // Adjust role names as needed

        if (!$isOwner && !$isAdmin) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        if (!$surat->file_path || !Storage::disk('public')->exists($surat->file_path)) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        return Storage::disk('public')->download($surat->file_path);
    }
}
