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
use App\HistoryTransaksi as HsR;

class TransaksiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $req) {
        $this->validate($req, [
            'tenant_id' => 'required', 
            'penyewa_id' => 'required', 
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

            $transaksi = Transaksi::create($req->all());
            $transaksi->history()->create([
                'transaksi_id' => $transaksi->id,
                'user_id' => \Auth::user()->id,
                'tanggal' => $transaksi->created_at,
                'menu' => 'penagihan',
                'dibayar' => $req->dibayar,
                'sisa' => $req->sisa
            ]);

            return response()->json([
                "diagnostic" => [
                    'code' => 201,
                    "message" => "created transaksi"
                ],
                "response" => [
                    "data" => $transaksi
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
    
        $tunggakan = Transaksi::where(['transaksis.lokasi_id' => \Auth::user()->lokasi_id, 'transaksis.status' => 'menunggak'])->leftJoin('tenants', 'transaksis.tenant_id', '=', 'tenants.id')
                    ->leftJoin('penyewas', 'tenants.penyewa_id', '=', 'penyewas.id')
                    ->where('penyewas.nama', 'like', "%$req->kode%")->orWhere('tenants.kode', 'like', "%$req->kode%")
                    ->groupBy('tenants.id')
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
            'created_at' => 'required', 
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
                        $detail = json_decode($file->detail);
                        $harga = (($detail->bop ?? 0) 
                                + ($detail->permeter ?? 0)
                                + ($detail->barang ?? 0)
                                + ($detail->listrik  ?? 0)
                                + ($detail->sampah ?? 0)
                                + ($detail->air ?? 0));
                        $file->dibayar = $harga;
                        $sisa = $sim - $file->sisa;
                        $file->history()->create([
                            'transaksi_id' => $file->id,
                            'user_id' => \Auth::user()->id,
                            'dibayar' => $file->sisa,
                            'sisa' => 0,
                            'tanggal' => $req->created_at,
                            'menu' => 'daftar_tunggakan'
                        ]);
                        $file->status = "lunas";
                        $file->sisa= 0;
                        $file->save();
                        $sim = $sisa;
                        $tenant = Tenant::findOrFail($req->tenant_id);
                        $tenant->status_tagih = "lunas";
                        $tenant->save();
                    } else{
                        $detail = json_decode($file->detail);
                        $harga = (($detail->bop ?? 0) 
                                + ($detail->permeter ?? 0)
                                + ($detail->barang ?? 0)
                                + ($detail->listrik  ?? 0)
                                + ($detail->sampah ?? 0)
                                + ($detail->air ?? 0));
                        $file->dibayar = $sim;
                        $sisa = $harga - $sim;
                        $file->sisa= $sisa;
                        $file->save();
                        $file->history()->create([
                            'transaksi_id' => $file->id,
                            'user_id' => \Auth::user()->id,
                            'dibayar' => $sim,
                            'sisa' => $sisa,
                            'tanggal' => $req->created_at,
                            'menu' => 'daftar_tunggakan'
                        ]);
                        $sim = 0;
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
            "jam_masuk" => "required",
            "jam_keluar" => "required",
        ]);

        $user = \Auth::user();

        $tagihan = HsR::where('user_id', $user->id)
            ->whereBetween('tanggal', [$req->jam_masuk, $req->jam_keluar])->where('menu', 'penagihan')->count();
        $total_tagihan = HsR::where('user_id', $user->id)
            ->whereBetween('tanggal', [$req->jam_masuk, $req->jam_keluar])->where('menu', 'penagihan')->sum("dibayar");
        $tunggakan = HsR::where('user_id', $user->id)
            ->whereBetween('tanggal', [$req->jam_masuk, $req->jam_keluar])->where('menu', '<>','penagihan')->count();
        $total_tunggakan = HsR::where('user_id', $user->id)
            ->whereBetween('tanggal', [$req->jam_masuk, $req->jam_keluar])->where('menu', 'penagihan')->sum("sisa");
        $bayar_tunggkan = HsR::where('user_id', $user->id)
            ->whereBetween('tanggal', [$req->jam_masuk, $req->jam_keluar])->where('menu', 'daftar_tunggakan')->sum("dibayar");
        $total_bayar = HsR::where('user_id', $user->id)
            ->whereBetween('tanggal', [$req->jam_masuk, $req->jam_keluar])->sum("dibayar");

        return response()->json([
            "diagnostic" => [
                'code' => 200,
                "message" => "akhir sesi"
            ],
            "response" => [
                "data" => [
                    "id_collector" => $user->id,
                    "nama_collector" => $user->name,
                    "jam_masuk" => $req->jam_masuk,
                    "jam_keluar" => $req->jam_keluar,
                    "tagihan" => $tagihan,
                    "tunggakan" => $tunggakan,
                    "total_tagihan" =>  $total_tagihan,
                    "total_tunggakan" =>  $total_tunggakan,
                    "bayar_tunggakan" =>  $bayar_tunggkan,
                    "kas_diterima" =>  $total_bayar,
                ]
            ]
        ]);
    }

    public function bayar2(Request $req)
    {
        $this->validate($req, [
            'tenant_id' => 'required', 
            'dibayar' => 'required', 
            'penyewa_id' => 'required',
            'created_at' => 'required'
        ]);

        // $penyewa = Tenant::findOrFail($req->tenant_id)->penyewa;
        $sim = $req->dibayar;
        
        // $tenants = $penyewa->tenant()->whereHas('transaksi', function (Builder $query){
        //     $query->where('status', 'menunggak');
        // })->get();

        // foreach ($tenants as $tenant) {
            $tunggakan = Transaksi::where(['penyewa_id' => $req->penyewa_id, 'status' => 'menunggak', 'lokasi_id' => \Auth::user()->lokasi_id])->get();
            foreach ($tunggakan as $file) {
                if($sim >= 0){
                    if($sim - $file->sisa >= 0){
                            $detail = json_decode($file->detail);
                            $harga = (($detail->bop ?? 0) 
                                    + ($detail->permeter ?? 0)
                                    + ($detail->barang ?? 0)
                                    + ($detail->listrik  ?? 0)
                                    + ($detail->sampah ?? 0)
                                    + ($detail->air ?? 0));
                            $file->dibayar = $harga;
                            $sisa = $sim - $file->sisa;
                            $file->history()->create([
                                'transaksi_id' => $file->id,
                                'user_id' => \Auth::user()->id,
                                'dibayar' => $file->sisa,
                                'sisa' => 0,
                                'tanggal' => $req->created_at,
                                'menu' => 'daftar_tunggakan'
                            ]);
                            $file->status = "lunas";
                            $file->sisa= 0;
                            $file->save();
                            $sim = $sisa;
                            $tenant = Tenant::findOrFail($req->tenant_id);
                            $tenant->status_tagih = "lunas";
                            $tenant->save();
                        } else{
                            $detail = json_decode($file->detail);
                            $harga = (($detail->bop ?? 0) 
                                    + ($detail->permeter ?? 0)
                                    + ($detail->barang ?? 0)
                                    + ($detail->listrik  ?? 0)
                                    + ($detail->sampah ?? 0)
                                    + ($detail->air ?? 0));
                            $file->dibayar = $sim;
                            $sisa = $harga - $sim;
                            $file->sisa= $sisa;
                            $file->save();
                            $file->history()->create([
                                'transaksi_id' => $file->id,
                                'user_id' => \Auth::user()->id,
                                'dibayar' => $sim,
                                'sisa' => $sisa,
                                'tanggal' => $req->created_at,
                                'menu' => 'daftar_tunggakan'
                            ]);
                            $sim = 0;
                        }
                    }
            }
        // }

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
            'tenants' => 'required|array', 
            'penyewa_id' => 'required', 
            'total' => 'required', 
        ]);

        $tenants = $req->tenants;
        $total = $req->total;
        try {
            foreach ($tenants as $data) {
                $harga = (($data['detail']['bop'] ?? 0) 
                                    + ($data['detail']['permeter'] ?? 0)
                                    + ($data['detail']['barang'] ?? 0)
                                    + ($data['detail']['listrik']  ?? 0)
                                    + ($data['detail']['sampah'] ?? 0)
                                    + ($data['detail']['air'] ?? 0));
                
                $sisa = 0;
                
                if($req->total == 0){
                    $sisa = $harga;
                    $data['status'] = 'menunggak';
                    $data['dibayar'] = 0;
                    $tenant = Tenant::findOrFail($data['tenant_id']);
                    $tenant->status_tagih = $data['status'];
                    $tenant->save();
                }else{
                    if($total > 0){
                        if($total<$harga && $total>0){
                            $data['status'] = 'menunggak';
                            $total = 0;
                            $data['dibayar'] = $total;
                            $sisa = $harga-$total;
                        }else{
                            $data['status'] = 'lunas';
                            $total -= $harga;
                            $data['dibayar'] = $harga;
                            $sisa = 0;
                        }
                        $tenant = Tenant::findOrFail($data['tenant_id']);
                        $tenant->status_tagih = $data['status'];
                        $tenant->save();

                    }else{
                        $data['status'] = 'menunggak';
                        $sisa = $harga;
                        $data['dibayar'] = 0;
                        $tenant = Tenant::findOrFail($data['tenant_id']);
                        $tenant->status_tagih = $data['status'];
                        $tenant->save();
                    }
                }
                
                $user = \Auth::user();
                $data2 = User::findOrFail(User::findOrFail($user->user_id)->user_id);

                $data['sisa'] = $sisa;
                $data['user_id'] = $user->id;
                $data['lokasi_id'] = $user->lokasi_id;
                $data['owner_id'] = $data2->id;

                $d = Transaksi::create([
                    'tenant_id' => $data['tenant_id'], 
                    'status' => $data['status'],
                    'dibayar' => $data['dibayar'], 
                    'sisa' => $data['sisa'], 
                    'tanggal' => $data['tanggal'], 
                    'user_id' => $data['user_id'], 
                    'shift' => $data['shift'], 
                    'detail' => json_encode($data['detail']), 
                    'created_at' => $data['created_at'], 
                    'owner_id' => $data['owner_id'], 
                    'lokasi_id' => $data['lokasi_id'],
                    'penyewa_id' => $req->penyewa_id
                ]);
                $d->history()->create([
                    'transaksi_id' => $d->id,
                    'user_id' => \Auth::user()->id,
                    'tanggal' => $data['created_at'],
                    'menu' => 'penagihan',
                    'dibayar' => $data['dibayar'],
                    'sisa' => $data['sisa']
                ]);
            }
            return response()->json([
                "diagnostic" => [
                    'code' => 200,
                    "message" => "bayar tagihan"
                ],
                "response" => [
                    "data" => [
                        "dibayar" => "oke"
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