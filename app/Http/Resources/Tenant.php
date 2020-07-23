<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Tenant extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "kode_tenant" => $this->kode,
            "status" => $this->status_tagih,
            "harga" => ($this->kategori->tarif->bop 
                            + ($this->kategori->tarif->bop * 0.1)
                            + ($this->kategori->tarif->permeter * $this->luas)
                            + $this->kategori->tarif->barang 
                            + $this->kategori->tarif->listrik 
                            + $this->kategori->tarif->sampah 
                            + $this->kategori->tarif->air),
            "tarif" => $this->kategori->tarif->get(['permeter','bop', 'air', 'barang', 'listrik', 'sampah']),
            "penyewa" => ($this->penyewa) ? $this->penyewa->nama : '',
            "lokasi" => $this->lokasi->lokasi,
        ];
    }
}