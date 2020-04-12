<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Tagih as TenantCollection;
use App\User;

class TagihController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        $user = \Auth::user();
        return response()->json([
            "diagnostic" => [
                "code" => 200,
                "message" => "success"
            ],
            "data" => TenantCollection::collection($user->lokasi->tenant()->paginate(20))
        ]);
    }
}