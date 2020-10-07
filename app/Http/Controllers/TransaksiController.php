<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tenant;
use App\Transaksi;
use App\Lokasi;
use App\Http\Resources\Tunggakan;
use App\Http\Resources\Tunggak;
use App\Http\Resources\TunggakanCollection;
use App\Http\Resources\TunggakCollection;
use Illuminate\Database\Eloquent\Builder;
use App\User;
use App\Penyewa;

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
            'shift' => 'required', 
            'detail' => 'required|json', 
        ]);
        
        $tenant = Tenant::findOrFail($req->tenant_id);

        if ($tenant->lokasi_id == \Auth::user()->lokasi_id) {
            $tenant->status_tagih = $req->status;
            $tenant->save();

            $detail = json_decode($req->detail);

            $harga = (($detail->bop ?? 0) 
                                + ($detail->permeter ?? 0)
                                + ($detail->barang ?? 0)
                                + ($detail->listrik  ?? 0)
                                + ($detail->sampah ?? 0)
                                + ($detail->air ?? 0));
            $sisa = $harga - $req->dibayar;
            $user = \Auth::user();
            $data = User::findOrFail(User::findOrFail($user->user_id)->user_id);
            $req->merge([
                'sisa' => $sisa, 
                'user_id' => $user->id,
                'lokasi_id' => $user->lokasi_id,
                'owner_id' => $data->id,
            ]);

            return response()->json([
                "diagnostic" => [
                    'code' => 201,
                    "message" => "created transaksi"
                ],
                "response" => [
                    "data" => Transaksi::create($req->all())
                ]
            ]);
        } else {
            return response()->json([
                "diagnostic" => [
                    'code' => 500,
                    "message" => "Eror Transaksi"
                ],
                "response" => [
                    "data" => [
                        "message" => "error"
                    ]
                ]
            ]);
        }
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
        // $penyewa = Penyewa::where('nama','like', "%$req->kode%")->where('lokasi_id',\Auth::user()->lokasi_id)->first();

        // $data = Tunggakan::collection(Lokasi::findOrFail(\Auth::user()->lokasi_id)->tenant()->where('kode', "like", "%$req->kode%")->whereHas('transaksi', function (Builder $query) {
        //             $query->where('status', 'menunggak');
        //         })->paginate(20));

        // return new TunggakanCollection($penyewa ? Tunggakan::collection($penyewa->tenant()->whereHas('transaksi', function (Builder $query){
        //     $query->where('status', 'menunggak')->where('lokasi_id',\Auth::user()->lokasi_id);
        // })->paginate(20)) : $data);
        $tunggakan = Transaksi::leftJoin('tenants', 'transaksis.tenant_id', '=', 'tenants.id')
                    ->leftJoin('penyewas', 'tenants.penyewa_id', '=', 'penyewas.id')
                    ->where('penyewas.nama', 'like', "%$req->kode%")->orWhere('kode', 'like', "%$req->kode%")
                    ->where(['transaksis.lokasi_id' => \Auth::user()->lokasi_id, 'transaksis.status' => 'menunggak'])
                    ->groupBy('penyewas.nama')
                    ->paginate(20);
        return new TunggakCollection(Tunggak::collection($tunggakan));
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

        $data = Transaksi::where(['tenant_id' => $req->tenant_id, 'status' => 'menunggak', 'lokasi_id' => \Auth::user()->lokasi_id])->get();
        $sim = $req->dibayar;

        if($data == NULL) {
            return response()->json([
                "diagnostic" => [
                    'code' => 500,
                    "message" => "error bayar tunggakan"
                ],
                "response" => [
                    "data" => [
                        "dibayar" => "gagal bayar"
                    ]
                ]
            ]);
        }
        
        foreach ($data as $file) {
            if($sim >= 0){
                if($sim - $file->sisa >= 0){
                        $file->dibayar = $file->sisa;
                        $sisa = $sim - $file->sisa;
                        $file->status = "lunas";
                        $file->sisa= 0;
                        $file->save();
                        $sim = $sisa;
                        $tenant = Tenant::findOrFail($req->tenant_id);
                        $tenant->status_tagih = "lunas";
                        $tenant->save();
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

    public function sesi(Request $req)
    {
        $this->validate($req,[
            "id_collector" => "required",
            "jam_masuk" => "required",
            "jam_keluar" => "required",
        ]);

        $user = \Auth::user();

        $data = Transaksi::where('user_id', $req->id_collector)
            ->whereBetween('created_at', [$req->jam_masuk, $req->jam_keluar])
            ->orWhereBetween('updated_at', [$req->jam_masuk, $req->jam_keluar]);

        return response()->json([
            "diagnostic" => [
                'code' => 200,
                "message" => "akhir sesi"
            ],
            "response" => [
                "data" => [
                    "id_collector" => $req->id_collector,
                    "nama_collector" => $user->name,
                    "jam_masuk" => $req->jam_masuk,
                    "jam_keluar" => $req->jam_keluar,
                    "total_bayar" => $data->where('status', '<>', 'menunggak')->count(),
                    "total_tunggakan" => $data->where('status', 'menunggak')->count(),
                    "total_penagihan" =>  $data->where('status', '<>', 'menunggak')->sum("dibayar"),
                    "total_menunggak" =>  $data->where('status', 'menunggak')->sum("sisa"),
                ]
            ]
        ]);
    }

    public function bayar2(Request $req)
    {
        $this->validate($req, [
            'tenant_id' => 'required', 
            'dibayar' => 'required', 
        ]);

        $penyewa = Tenant::findOrFail($req->tenant_id)->penyewa;
        $sim = $req->dibayar;
        
        $tenants = $penyewa->tenant()->whereHas('transaksi', function (Builder $query){
            $query->where('status', 'menunggak');
        })->get();

        foreach ($tenants as $tenant) {
            $tunggakan = $tenant->transaksi()->where(['tenant_id' => $req->tenant_id, 'status' => 'menunggak', 'lokasi_id' => \Auth::user()->lokasi_id])->get();
            foreach ($tunggakan as $file) {
                if($sim >= 0){
                    if($sim - $file->sisa >= 0){
                            $file->dibayar = $file->sisa;
                            $sisa = $sim - $file->sisa;
                            $file->status = "lunas";
                            $file->sisa= 0;
                            $file->save();
                            $sim = $sisa;
                            $tenant = Tenant::findOrFail($req->tenant_id);
                            $tenant->status_tagih = "lunas";
                            $tenant->save();
                        } else{
                            $file->dibayar = $sim;
                            $sisa = $sim - $file->sisa;
                            $file->sisa= $file->sisa - $sim;
                            $file->save();
                            $sim = $sisa;
                        }
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

    public function store2(Request $req) {
        $this->validate($req, [
            'tenants' => 'required|json', 
            'total' => 'required', 
        ]);

        $tenants = json_decode($req->tenants);
        $total = $req->total;
        try {
            foreach ($tenants as $data) {
                $tenant = Tenant::findOrFail($data->tenant_id);
                $tenant->status_tagih = $data->status;
                $tenant->save();

                $detail = json_decode($data->detail);

                $harga = (($detail->bop ?? 0) 
                                    + ($detail->permeter ?? 0)
                                    + ($detail->barang ?? 0)
                                    + ($detail->listrik  ?? 0)
                                    + ($detail->sampah ?? 0)
                                    + ($detail->air ?? 0));
                if($total >= $harga){
                    $sisa = 0;
                    $data->status = 'lunas';
                    $total -= $harga;
                }else{
                    $sisa = $harga - $total;
                    $data->status = 'menunggak';
                    $total -= $harga;
                }
                
                $user = \Auth::user();
                $data = User::findOrFail(User::findOrFail($user->user_id)->user_id);
                $data->merge([
                    'sisa' => $sisa, 
                    'user_id' => $user->id,
                    'lokasi_id' => $user->lokasi_id,
                    'owner_id' => $data->id,
                ]);

                Transaksi::create($data->all());
            }
            return response()->json([
                "diagnostic" => [
                    'code' => 200,
                    "message" => "Sukses"
                ],
                "response" => [
                    "data" => [
                        "message" => "sukses"
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "diagnostic" => [
                    'code' => 500,
                    "message" => "Eror Transaksi"
                ],
                "response" => [
                    "data" => [
                        "message" => "error"
                    ]
                ]
            ]);
        }
    }
}