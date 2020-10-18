<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoryTransaksi extends Model
{
    protected $fillable = [
        'transaksi_id', 'user_id', 'tanggal', 'sisa', 'dibayar', 'menu'
    ];

    public function transaksi() {
        return $this->hasMany('App\Transaksi');
    }

    public function user() {
        return $this->hasMany('App\User');
    }
}