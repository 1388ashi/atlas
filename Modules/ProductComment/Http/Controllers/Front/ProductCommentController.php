<?php

namespace Modules\ProductComment\Http\Controllers\Front;

use Illuminate\Http\JsonResponse;
use Modules\ProductComment\Entities\ProductComment;
use Modules\ProductComment\ApiResources\ProductCommentResource;
use Shetabit\Shopit\Modules\ProductComment\Http\Controllers\Front\ProductCommentController as BaseProductCommentController;

class ProductCommentController extends BaseProductCommentController
{
    public function show($productId): JsonResponse
    {
        $comment = ProductComment::query()
            ->where('product_id' , $productId)
            ->whereNull('parent_id')
            ->status(ProductComment::STATUS_APPROVED)
            ->filters()->paginateOrAll()->each(function ($item) {
                if($item->show_customer_name){
                    return $item->load('creator');
                }
            });

        $comment->load(['childs']);

        return response()->success('', [
            'comment' => ProductCommentResource::collection($comment)
        ]);
    }

}
