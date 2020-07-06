<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "email" => $this->email,
            "nohp" => $this->nohp ? $this->nohp :'',
            "ktp" => $this->ktp ? $this->ktp : '',
            "photo" => $this->photo ? $this->photo : '',
            "lokasi" => [
                "id" => $this->lokasi->id,
                "lokasi" => $this->lokasi->lokasi,
                "alamat" => $this->lokasi->alamat,
            ]
        ];
    }
}