<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Penyewa extends Model
{
    protected $fillable = [
        'nama', 'hp', 'ktp', 'alamat', 'status'
    ];

    public function tenant() {
        return $this->hasMany('App\Tenant');
    }

    public function transaksi() {
        return $this->hasMany('App\Transaksi');
    }
}