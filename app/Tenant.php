<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'kategori_id', 'luas', 'disewa', 'status', 'lokasi_id', 'penyewa_id', 'kode', 'barat', 'timur', 'utara', 'selatan'
    ];

    public function penyewa(){
        return $this->belongsTo('App\Penyewa');
    }

    public function kategori(){
        return $this->belongsTo('App\Kategori');
    }

    public function lokasi(){
        return $this->belongsTo('App\Lokasi');
    }

    public function transaksi() {
        return $this->hasMany('App\Transaksi');
    }
}