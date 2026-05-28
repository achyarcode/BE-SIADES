<?php

namespace App\Http\Controllers;

use App\Http\Requests\StampStoreRequest;
use App\Models\AdminStamp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminStampController extends Controller
{
    public function index(Request $request)
    {
        $stamps = AdminStamp::where('admin_id', $request->user()->id)->get();
        return response()->json($stamps);
    }

    public function store(StampStoreRequest $request)
    {
        $validated = $request->validated();

        $adminId = $request->user()->id;

        if (AdminStamp::where('admin_id', $adminId)->where('stamp_name', $validated['stamp_name'])->exists()) {
            return response()->json(['message' => 'Nama stempel sudah digunakan'], 409);
        }

        $file = $validated['stamp_file'];
        $fileName = 'stamps/' . Str::random(40) . '.png';

        if (! Storage::disk('public')->put($fileName, file_get_contents($file))) {
            return response()->json(['message' => 'Gagal menyimpan stempel'], 500);
        }

        $stamp = AdminStamp::create([
            'admin_id' => $adminId,
            'stamp_name' => $validated['stamp_name'],
            'file_path' => $fileName,
        ]);

        return response()->json($stamp, 201);
    }

    public function destroy($id)
    {
        $stamp = AdminStamp::findOrFail($id);

        if ($stamp->admin_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($stamp->file_path);
        $stamp->delete();

        return response()->json(['message' => 'Stamp deleted successfully']);
    }

    public function showImage($id)
    {
        $stamp = AdminStamp::findOrFail($id);

        if ($stamp->admin_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $path = $stamp->file_path;
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $fileData = Storage::disk('public')->get($path);
        $base64 = base64_encode($fileData);
        $mime = 'image/png';

        return response()->json([
            'stamp_data' => 'data:' . $mime . ';base64,' . $base64
        ]);
    }
}
