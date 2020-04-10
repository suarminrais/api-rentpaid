<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $fillable = [
        'lokasi', 'luas', 'kode', 'kecamatan', 'desa', 'alamat', 'user_id', 'status',
    ];

    public function user(){
        return $this->belongsTo('App\User');
    }

    public function tenant(){
        return $this->hasMany('App\Tenant');
    }

    public function collector(){
        return $this->hasMany('App\User');
    }
}