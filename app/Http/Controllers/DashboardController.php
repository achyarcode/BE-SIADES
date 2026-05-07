<?php
namespace App\Http\Controllers;

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
            'usahaAktif' => 0, // Placeholder for now
            'totalKas' => 0, // Placeholder for now
        ]);
    }
}
