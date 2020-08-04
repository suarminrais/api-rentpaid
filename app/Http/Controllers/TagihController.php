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
        return TenantCollection::collection($user->lokasi->tenant()->where('penyewa_id', "<>", null)->orderBy('created_at', 'desc')->paginate(20));
    }
}