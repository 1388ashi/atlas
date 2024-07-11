<?php

namespace Modules\Product\ApiResources;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductTorobPaginationResource extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'max_pages' => $this->lastPage(),
            'count' => $this->total(),
            'products' => ProductTorobResource::collection($this->collection)
        ];
    }
}
