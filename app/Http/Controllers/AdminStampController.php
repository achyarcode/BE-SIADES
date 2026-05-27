<?php

namespace App\Http\Controllers;

use App\Http\Requests\StampStoreRequest;
use App\Models\AdminStamp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminStampController extends Controller
{
    public function index(Request $request)
    {
        $stamps = AdminStamp::where('admin_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($stamps);
    }

    public function store(StampStoreRequest $request)
    {
        $validated = $request->validated();
        $adminId = $request->user()->id;

        if (AdminStamp::where('admin_id', $adminId)->where('stamp_name', $validated['stamp_name'])->exists()) {
            return response()->json(['message' => 'Nama stempel sudah digunakan'], 409);
        }

        $filePath = $request->file('stamp_file')->store('stamps', 'public');
        if (! $filePath) {
            return response()->json(['message' => 'Gagal menyimpan stempel'], 500);
        }

        $stamp = AdminStamp::create([
            'admin_id' => $adminId,
            'stamp_name' => $validated['stamp_name'],
            'file_path' => $filePath,
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
}
