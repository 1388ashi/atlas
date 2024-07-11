<?php

namespace Modules\ProductComment\ApiResources;

use Illuminate\Http\Resources\Json\JsonResource;
use Shetabit\Shopit\Modules\Customer\ApiResources\SafeCustomerResource;
use Shetabit\Shopit\Modules\ProductComment\ApiResources\ProductCommentResource as BaseProductCommentResource;
// اطلاعات حساس تو فرانت ندیم
class ProductCommentResource extends BaseProductCommentResource
{

    public function toArray($request)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'rate' => $this->rate,
            'product_id' => $this->product_id,
            'created_at' => $this->created_at,
            'creator' => new SafeCustomerResource($this->whenLoaded('creator'), $this->show_customer_name),
            'childs' => $this->childs
        ];
    }
}
