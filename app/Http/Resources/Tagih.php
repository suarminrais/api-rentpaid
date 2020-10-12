<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Tagih extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "kode_tenant" => $this->kode,
            "harga" => ($this->kategori->tarif->bop 
                            + ($this->kategori->tarif->permeter * $this->luas)
                            + $this->kategori->tarif->barang 
                            + $this->kategori->tarif->listrik 
                            + $this->kategori->tarif->sampah 
                            + $this->kategori->tarif->air),
            "penyewa" => $this->penyewa->nama,
            "penyewa_id" => $this->penyewa->id,
            "status" => $this->status_tagih,
        ];
    }
}