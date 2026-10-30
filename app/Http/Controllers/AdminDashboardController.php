<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Pin;
use App\Models\Transaction;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.beranda', [
            'jumlahAnggota' => Member::count(),
            'jumlahPinBelumDipakai' => Pin::where('status', 'issued')->count(),
            'jumlahTransaksi' => Transaction::count(),
            'transaksiTerakhir' => Transaction::with('member')->latest('id')->limit(8)->get(),
        ]);
    }
}
