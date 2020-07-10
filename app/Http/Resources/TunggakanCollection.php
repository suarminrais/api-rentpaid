<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Transaksi;

class TunggakanCollection extends ResourceCollection
{
    private $pagination;

    public function __construct($resource)
    {
        $this->pagination = [
            'current_page' => $resource->currentPage(),
            // "from" => $resource->from(),
            "last_page" => $resource->lastPage(),
            "path" => $resource->path(),
            'per_page' => $resource->perPage(),
            // "to" => $resource->to(),
        ];

        $resource = $resource->getCollection();

        parent::__construct($resource);
    }

    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'pagination' => $this->pagination
        ];
    }

    public function with($request)
    {
        return [
            'diagnostic' => [
                'code' => 200,
                'message' => 'success'
            ],
            "total_tunggakan" => Transaksi::where('status', "menunggak")->sum('sisa')
        ];
    }
}