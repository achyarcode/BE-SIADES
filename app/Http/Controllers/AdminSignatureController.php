<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $request->validate([
            'signature_name' => 'required|string|max:255',
            'signature_data' => 'required|string', // Base64 string
        ]);

        $base64Image = $request->signature_data;
        
        // Strip the prefix if it exists
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]); // png, jpg, etc.
            if (!in_array($type, ['png', 'jpg', 'jpeg'])) {
                return response()->json(['message' => 'Invalid image type'], 400);
            }
        } else {
            return response()->json(['message' => 'Invalid base64 string'], 400);
        }

        $imageData = base64_decode($base64Image);
        $fileName = 'signatures/' . Str::random(40) . '.png';
        
        Storage::disk('public')->put($fileName, $imageData);

        $signature = AdminSignature::create([
            'admin_id' => $request->user()->id,
            'signature_name' => $request->signature_name,
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
}
