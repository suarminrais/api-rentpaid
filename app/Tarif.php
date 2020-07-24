<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    protected $fillable = [
        'nama', 'bop', 'barang', 'listrik', 'sampah', 'air', 'permeter', 'created_at'
    ];

    public function kategori(){
        return $this->hasMany('App\Kategori');
    }
}
