<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tenant;
use App\Transaksi;

class TransaksiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $req) {
        $this->validate($req, [
            'penyewa_id' => 'required', 
            'tenant_id' => 'required', 
            'status' => 'required', 
            'dibayar' => 'required', 
            'tanggal' => 'required', 
        ]);
        
        $tenant = Tenant::findOrFail($req->tenant_id);
        $harga = ($tenant->kategori->tarif->bop 
                            + ($tenant->kategori->tarif->bop * 0.1)
                            + $tenant->kategori->tarif->permeter
                            + $tenant->kategori->tarif->barang 
                            + $tenant->kategori->tarif->listrik 
                            + $tenant->kategori->tarif->sampah 
                            + $tenant->kategori->tarif->air);
        $sisa = $harga - $req->dibayar;
        $user = \Auth::user();
        $req->merge(['sisa' => $sisa, 'user_id' => $user->id]);

        return response()->json([
            "diagnostic" => [
                'code' => 201,
                "message" => "created transaksi"
            ],
            "data" => Transaksi::create($req->all())
        ]);
    }
}