<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Tunggak extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->tenant->id,
            "kode_tenant" => $this->kode,
            "penyewa" => $this->nama,
            "total_tunggakan" => $this->tenant->transaksi()->where('status', 'menunggak')->sum('sisa'),
            "tunggakan" => $this->tenant->transaksi()->where('status', 'menunggak')->get(['id', 'sisa as harga', 'tanggal'])
        ];
    }
}