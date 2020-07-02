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
                            + ($this->kategori->tarif->bop * 0.1)
                            + $this->kategori->tarif->permeter
                            + $this->kategori->tarif->barang 
                            + $this->kategori->tarif->listrik 
                            + $this->kategori->tarif->sampah 
                            + $this->kategori->tarif->air),
            "penyewa" => [
                "id" =>$this->penyewa->id,
                "nama" =>$this->penyewa->nama,
            ],
            "status" => $this->status_tagih,
        ];
    }
}