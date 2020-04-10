<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = [
        'penyewa_id', 'tenant_id', 'status', 'dibayar', 'sisa', 'tanggal'
    ];

    public function penyewa(){
        return $this->belongsTo('App\Penyewa');
    }

    public function tenant(){
        return $this->belongsTo('App\Tenant');
    }

    public function collector(){
        return $this->belongsTo('App\User');
    }
}