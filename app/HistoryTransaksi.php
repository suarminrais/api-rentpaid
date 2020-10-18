<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoryTransaksi extends Model
{
    protected $fillable = [
        'transaksi_id', 'tanggal', 'sisa', 'dibayar', 'menu'
    ];

    public function transaksi() {
        return $this->hasMany('App\Transaksi');
    }
}