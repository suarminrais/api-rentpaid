<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Tenant as TenantCollection;
use App\Http\Resources\User;
use App\Tenant;
use App\Penyewa;

class TenantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function user()
    {
        $user = \Auth::user();

        return response()->json([
            "diagnostic" => [
                "code" => 200,
                "message" => "success"
            ],
            "response" => [
                "data" => new User($user)
            ]
        ]);
    }

    public function index(){
        $tenant = TenantCollection::collection(Tenant::orderBy('updated_at', 'desc')->paginate(20));
        
        return $tenant;
    }

    public function show($id){
        try{
            $tenant = new TenantCollection(Tenant::findOrFail($id));
            
            return response()->json([
                'diagnostic' => [
                    'code' => 200,
                    'message' => 'success'
                ],
                "response" => [
                    'data' => $tenant
                ]
            ]);
        } catch (\Exception $e){
            return response()->json([
                'diagnostic' => [
                    'code' => 404,
                    'message' => 'Data tenant not found'
                ],
            ]);
        }
    }

    public function showRev($id){
        try{
            $tenant = TenantCollection::collection(Tenant::where('id',$id)->orWhere('kode','like',"%$id%")
                ->where('lokasi_id', \Auth::user()->lokasi_id)->first()->penyewa->tenant);
            
            return response()->json([
                'diagnostic' => [
                    'code' => 200,
                    'message' => 'success'
                ],
                "response" => [
                    'data' => $tenant
                ]
            ]);
        } catch (\Exception $e){
            return response()->json([
                'diagnostic' => [
                    'code' => 404,
                    'message' => 'Data tenant not found'
                ],
            ]);
        }
    }

    public function find($tenant){
        try{
            $tenant = TenantCollection::collection(Tenant::where('kode',$tenant)->first()->penyewa->tenant);
            
            return response()->json([
                'diagnostic' => [
                    'code' => 200,
                    'message' => 'success'
                ],
                "response" => [
                    'data' => $tenant
                ]
            ]);
        } catch (\Exception $e){
            return response()->json([
                'diagnostic' => [
                    'code' => 404,
                    'message' => 'Data tenant not found'
                ],
            ]);
        }
    }

    public function search(Request $req){
        $this->validate($req, [
            'search' => 'required|string',
        ]);

        $data = Tenant::where('tenants.lokasi_id', \Auth::user()->lokasi_id)
                    ->leftJoin('penyewas', 'tenants.penyewa_id', '=', 'penyewas.id')
                    ->where('penyewas.nama', 'like', "%$req->search%")->orWhere('tenants.kode', 'like', "%$req->search%")
                    ->select('tenants.*', 'penyewas.nama');
        
        return TenantCollection::collection($data->paginate(20));
    }
}