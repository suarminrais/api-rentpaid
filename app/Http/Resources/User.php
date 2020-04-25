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
            "type" => $this->type,
            "nohp" => $this->nohp ? $this->nohp :'',
            "ktp" => $this->ktp ? $this->ktp : '',
            "status" => $this->status,
            "photo" => $this->photo ? $this->photo : '',
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "lokasi" => $this->lokasi
        ];
    }
}