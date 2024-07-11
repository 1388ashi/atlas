<?php

namespace Modules\Product\ApiResources;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductEmallsPaginationResource extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'success' => true,
            'total' => $this->total(),
            'products' => ProductEmallsResource::collection($this->collection)
        ];
    }
}
