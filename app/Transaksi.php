<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = [
        'tenant_id', 'penyewa_id', 'status', 'dibayar', 'sisa', 'tanggal', 'user_id', 'shift', 'detail', 'created_at', 'owner_id', 'lokasi_id'
    ];

    public function tenant(){
        return $this->belongsTo('App\Tenant');
    }

    public function penyewa(){
        return $this->belongsTo('App\Penyewa');
    }

    public function collector(){
        return $this->belongsTo('App\User');
    }

    public function history(){
        return $this->belongsTo('App\HistoryTransaksi');
    }
}