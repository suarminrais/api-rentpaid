<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Tenant as TenantCollection;
use App\Http\Resources\User;
use App\Tenant;

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
            "data" => new User($user)
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
                'data' => $tenant
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
}