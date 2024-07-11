<?php

namespace Modules\Product\ApiResources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Entities\Product;

class ProductTorobResource extends JsonResource
{
    public function toArray($request)
    {
        $majorFinalPrice = $this->major_final_price;
        $currentPrice = empty($majorFinalPrice) ? 0 : $majorFinalPrice['amount'];
        $oldPrice = empty($majorFinalPrice) ? 0 : $majorFinalPrice['amount'] + $majorFinalPrice['discount_price'];

        return [
            'title' => $this->title,
            'page_unique' => $this->id,
            'old_price' => $oldPrice,
            'current_price' => $currentPrice,
            'availability' => $this->status === Product::STATUS_AVAILABLE ? 'instock' : 'outofstock',
            'category_name' => $this->categories->first()->title,
            'image_link' => optional($this->major_image)->getFullUrl(),
            'page_url' => config('app.front_url') . "/product/{$this->id}/{$this->slug}",
            'short_desc' => mb_substr($this->short_description, 0, 60),
        ];
    }
}
