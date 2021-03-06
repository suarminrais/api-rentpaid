<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Tunggakan extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "kode_tenant" => $this->kode,
            "penyewa" => $this->penyewa->nama,
            "penyewa_id" => $this->penyewa->id,
            "total_tunggakan" => $this->transaksi()->where('status', 'menunggak')->sum('sisa'),
            "tunggakan" => $this->transaksi()->where('status', 'menunggak')->get(['id', 'sisa as harga', 'tanggal'])
        ];
    }
}