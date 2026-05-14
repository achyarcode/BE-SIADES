<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratApproveRequest;
use App\Http\Requests\SuratIndexRequest;
use App\Http\Requests\SuratRejectRequest;
use App\Http\Requests\SuratStoreRequest;
use App\Models\JenisSurat;
use App\Models\Surat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratController extends Controller
{
    private const ALLOWED_STATUSES = ['PENDING', 'DISETUJUI', 'DITOLAK'];

    public function index(SuratIndexRequest $request)
    {
        $validated = $request->validated();

        $query = Surat::with(['user', 'jenisSurat', 'approver'])->orderBy('created_at', 'desc');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $paginated = $query->paginate($validated['limit'] ?? 10)->through(function (Surat $surat) {
            return [
                'id' => $surat->id,
                'user_id' => $surat->user_id,
                'nama_pemohon' => $surat->nama_pemohon,
                // Keep plain-text field for FE compatibility, sourced from lookup relation.
                'jenis_surat' => optional($surat->jenisSurat)->nama,
                'jenis_surat_id' => $surat->jenis_surat_id,
                'keperluan' => $surat->keperluan,
                'keterangan' => $surat->keterangan,
                'file_path' => $surat->file_path,
                'signature_position' => $surat->signature_position,
                'status' => $surat->status,
                'approved_by' => $surat->approved_by,
                'alasan_penolakan' => $surat->alasan_penolakan,
                'created_at' => $surat->created_at,
                'updated_at' => $surat->updated_at,
                'user' => $surat->user,
                'approver' => $surat->approver,
                // Expose relation under non-colliding key.
                'jenis_surat_detail' => $surat->jenisSurat,
            ];
        });

        return response()->json($paginated);
    }

    public function store(SuratStoreRequest $request)
    {
        $validated = $request->validated();

        $filePath = null;
        $originalFilename = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();

            // Extra server-side guard: block non-.pdf extension even if client bypasses UI.
            if (strtolower($file->getClientOriginalExtension()) !== 'pdf') {
                return response()->json(['message' => 'File harus berformat PDF (.pdf)'], 422);
            }

            $safeOriginalName = Str::of(pathinfo($originalFilename, PATHINFO_FILENAME))
                ->replaceMatches('/[^A-Za-z0-9\-_ ]/', '')
                ->trim()
                ->limit(120, '')
                ->toString();
            $safeOriginalName = $safeOriginalName !== '' ? $safeOriginalName : 'surat';
            $fileName = $safeOriginalName.'-'.time().'.pdf';

            $filePath = $file->storeAs('surats', $fileName, 'public');
        }

        $surat = Surat::create([
            'user_id' => $request->user()->id,
            'nama_pemohon' => $request->user()->name,
            'jenis_surat_id' => $this->resolveJenisSuratId($validated),
            'keperluan' => $validated['keperluan'] ?? '-',
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'status' => 'PENDING',
        ]);

        return response()->json($surat, 201);
    }

    public function approve(SuratApproveRequest $request, $id)
    {
        // Hanya super-admin yang boleh menyetujui surat
        if (! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Hanya Kepala Desa yang dapat menyetujui surat'], 403);
        }

        $validated = $request->validated();

        $surat = Surat::findOrFail($id);
        if ($surat->status !== 'PENDING') {
            return response()->json(['message' => 'Surat hanya bisa disetujui saat status masih PENDING'], 409);
        }

        // 1. Save signature position if provided
        if (array_key_exists('signature_position', $validated)) {
            $surat->signature_position = $validated['signature_position'];
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

        // 3. Update status and audit trail
        $surat->status = 'DISETUJUI';
        $surat->approved_by = $request->user()->id;
        $surat->save();

        return response()->json([
            'message' => 'Surat berhasil disetujui',
            'data' => $surat,
        ]);
    }

    public function reject(SuratRejectRequest $request, $id)
    {
        // Hanya super-admin yang boleh menolak surat
        if (! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Hanya Kepala Desa yang dapat menolak surat'], 403);
        }

        $validated = $request->validated();

        $surat = Surat::findOrFail($id);
        if ($surat->status !== 'PENDING') {
            return response()->json(['message' => 'Surat hanya bisa ditolak saat status masih PENDING'], 409);
        }
        $surat->update([
            'status' => 'DITOLAK',
            'approved_by' => $request->user()->id,
            'alasan_penolakan' => $validated['alasan_penolakan'] ?? null,
        ]);

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

        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        if (! $surat->file_path || ! Storage::disk('public')->exists($surat->file_path)) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        $downloadName = $surat->original_filename;
        if (! $downloadName || trim($downloadName) === '') {
            $downloadName = 'surat-'.$surat->id.'.pdf';
        }

        $baseName = pathinfo($downloadName, PATHINFO_FILENAME);
        $ext = pathinfo($downloadName, PATHINFO_EXTENSION);
        $ext = $ext !== '' ? strtolower($ext) : 'pdf';

        // Add explicit suffix for approved/signed output.
        $suffix = '_TandaTangan';
        if (! Str::endsWith(Str::lower($baseName), strtolower($suffix))) {
            $baseName .= $suffix;
        }

        $downloadName = $baseName.'.'.$ext;

        return response()->download(Storage::disk('public')->path($surat->file_path), $downloadName);
    }

    private function resolveJenisSuratId(array $validated): int
    {
        if (! empty($validated['jenis_surat_id'])) {
            return (int) $validated['jenis_surat_id'];
        }

        $name = trim((string) ($validated['jenis_surat'] ?? ''));
        if ($name === '') {
            $name = 'Lainnya';
        }

        return (int) JenisSurat::query()->firstOrCreate(
            ['nama' => $name],
            ['deskripsi' => null, 'is_active' => true]
        )->id;
    }
}
