<?php

namespace Modules\Product\ApiResources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Entities\Product;

class ProductEmallsResource extends JsonResource
{
    public function toArray($request)
    {
        $majorFinalPrice = $this->major_final_price;
        $currentPrice = empty($majorFinalPrice) ? 0 : $majorFinalPrice['amount'];
        $oldPrice = empty($majorFinalPrice) ? 0 : $majorFinalPrice['amount'] + $majorFinalPrice['discount_price'];

        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $currentPrice,
            'old_price' => $oldPrice,
            'is_available' => $this->status === Product::STATUS_AVAILABLE ? true : false,
            'image' => $this->major_image->getFullUrl(),
            'url' => config('app.front_url') . "/product/{$this->id}/{$this->slug}",
        ];
    }
}
