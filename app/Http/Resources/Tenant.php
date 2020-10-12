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
                            + ($this->kategori->tarif->permeter * $this->luas)
                            + $this->kategori->tarif->barang 
                            + $this->kategori->tarif->listrik 
                            + $this->kategori->tarif->sampah 
                            + $this->kategori->tarif->air),
            "tarif" => $this->kategori->tarif,
            "penyewa" => ($this->penyewa) ? $this->penyewa->nama : '',
            "penyewa_id" => ($this->penyewa) ? $this->penyewa->id : '',
            "lokasi" => $this->lokasi->lokasi,
            "tunggakan" => $this->transaksi()->where('status', 'menunggak')->sum('sisa')
        ];
    }
}