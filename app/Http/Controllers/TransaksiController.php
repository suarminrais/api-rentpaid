<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tenant;
use App\Transaksi;
use App\Lokasi;
use App\Http\Resources\Tunggakan;
use App\Http\Resources\TunggakanCollection;
use Illuminate\Database\Eloquent\Builder;

class TransaksiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $req) {
        $this->validate($req, [
            'tenant_id' => 'required', 
            'status' => 'required', 
            'dibayar' => 'required', 
            'tanggal' => 'required', 
        ]);
        
        $tenant = Tenant::findOrFail($req->tenant_id);
        $tenant->status_tagih = $req->status;
        $tenant->save();

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
            "response" => [
                "data" => Transaksi::create($req->all())
            ]
        ]);
    }

    public function tunggakan()
    {
        return new TunggakanCollection(Tunggakan::collection(Lokasi::findOrFail(\Auth::user()->lokasi_id)->tenant()->whereHas('transaksi', function (Builder $query) {
    $query->where('status', 'menunggak');
})->paginate(20)));
    }

    public function search(Request $req)
    {
        $this->validate($req,[
            "kode" => "required"
        ]);
        return new TunggakanCollection(Tunggakan::collection(Lokasi::findOrFail(\Auth::user()->lokasi_id)->tenant()->where('kode', "like", "%$req->kode%")->whereHas('transaksi', function (Builder $query) {
    $query->where('status', 'menunggak');
})->paginate(20)));
    }

    public function tunggakanSingle($id)
    {
        $data = new Tunggakan(Lokasi::findOrFail(\Auth::user()->lokasi_id)->tenant()->findOrFail($id));
        return response()->json([
            "diagnostic" => [
                'code' => 200,
                "message" => "tunggakan data"
            ],
            "response" => [
                "data" => $data
            ]
        ]);
    }

    public function bayar(Request $req)
    {
        $this->validate($req, [
            'tenant_id' => 'required', 
            'dibayar' => 'required', 
        ]);

        $data = Transaksi::where(['tenant_id' => $req->tenant_id, 'status' => 'menunggak'])->get();
        $sim = $req->dibayar;
        
        foreach ($data as $file) {
            if($sim >= 0){
                if($sim - $file->sisa >= 0){
                        $file->dibayar = $file->sisa;
                        $sisa = $sim - $file->sisa;
                        $file->status = "lunas";
                        $file->sisa= 0;
                        $file->save();
                        $sim = $sisa;
                    } else{
                        $file->dibayar = $sim;
                        $sisa = $sim - $file->sisa;
                        $file->sisa= $file->sisa - $sim;
                        $file->save();
                        $sim = $sisa;
                    }
                }
        }

        return response()->json([
            "diagnostic" => [
                'code' => 200,
                "message" => "bayar tunggakan"
            ],
            "response" => [
                "data" => [
                    "dibayar" => "oke"
                ]
            ]
        ]);
    }
}