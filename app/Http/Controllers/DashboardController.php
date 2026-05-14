<?php

namespace App\Http\Controllers;

use App\Models\Katalog;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'totalWarga' => User::role('warga')->count(),
            'suratMenunggu' => Surat::where('status', 'PENDING')->count(),
            'usahaAktif' => Katalog::where('status', Katalog::STATUS_AKTIF)->count(),
        ]);
    }

    public function wargaStats(Request $request)
    {
        $userId = $request->user()->id;

        $totalPengajuan = Surat::where('user_id', $userId)->count();
        $suratSelesai = Surat::where('user_id', $userId)->where('status', 'DISETUJUI')->count();
        $recentSurat = Surat::with('jenisSurat:id,nama')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'jenis_surat_id', 'created_at', 'status'])
            ->map(function (Surat $surat) {
                return [
                    'id' => $surat->id,
                    'jenis_surat' => optional($surat->jenisSurat)->nama ?? 'Lainnya',
                    'created_at' => $surat->created_at,
                    'status' => $surat->status,
                ];
            })
            ->values();

        return response()->json([
            'totalPengajuan' => $totalPengajuan,
            'suratSelesai' => $suratSelesai,
            'recentSurat' => $recentSurat,
        ]);
    }
}
