<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignatureStoreRequest;
use App\Models\AdminSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminSignatureController extends Controller
{
    public function index(Request $request)
    {
        $signatures = AdminSignature::where('admin_id', $request->user()->id)->get();

        return response()->json($signatures);
    }

    public function store(SignatureStoreRequest $request)
    {
        $validated = $request->validated();
        $adminId = $request->user()->id;

        if (AdminSignature::where('admin_id', $adminId)->where('signature_name', $validated['signature_name'])->exists()) {
            return response()->json(['message' => 'Nama tanda tangan sudah digunakan'], 409);
        }

        $base64Image = $validated['signature_data'];

        // Strip the prefix if it exists
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]); // png, jpg, etc.
            if (! in_array($type, ['png', 'jpg', 'jpeg'])) {
                return response()->json(['message' => 'Invalid image type'], 400);
            }
        } else {
            return response()->json(['message' => 'Invalid base64 string'], 400);
        }

        $imageData = base64_decode($base64Image, true);
        if ($imageData === false) {
            return response()->json(['message' => 'Invalid base64 payload'], 400);
        }

        // Limit decoded payload to 2 MB to prevent oversized uploads.
        if (strlen($imageData) > 2 * 1024 * 1024) {
            return response()->json(['message' => 'Signature image terlalu besar'], 422);
        }

        $fileName = 'signatures/'.Str::random(40).'.png';

        if (! Storage::disk('public')->put($fileName, $imageData)) {
            return response()->json(['message' => 'Gagal menyimpan tanda tangan'], 500);
        }

        $signature = AdminSignature::create([
            'admin_id' => $adminId,
            'signature_name' => $validated['signature_name'],
            'file_path' => $fileName,
        ]);

        return response()->json($signature, 201);
    }

    public function destroy($id)
    {
        $signature = AdminSignature::findOrFail($id);

        // Ensure user owns the signature
        if ($signature->admin_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete file from storage
        Storage::disk('public')->delete($signature->file_path);

        // Delete from DB
        $signature->delete();

        return response()->json(['message' => 'Signature deleted successfully']);
    }

    public function showImage($id)
    {
        $signature = AdminSignature::findOrFail($id);

        if ($signature->admin_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $path = $signature->file_path;
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $fileData = Storage::disk('public')->get($path);
        $base64 = base64_encode($fileData);
        $mime = 'image/png';

        return response()->json([
            'signature_data' => 'data:' . $mime . ';base64,' . $base64
        ]);
    }
}

