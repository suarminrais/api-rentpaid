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
            $tenant = TenantCollection::collection(Tenant::findOrFail($id)->penyewa->tenant);
            
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

        $penyewa = Penyewa::where('nama','like', "%$req->search%")->first();

        $tenant = TenantCollection::collection( $penyewa ? $penyewa->tenant()->paginate(20) : Tenant::where('kode', 'like', "%$req->search%")->latest()->paginate(20));
        
        return $tenant;
    }
}