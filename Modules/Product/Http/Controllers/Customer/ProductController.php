<?php

namespace Modules\Product\Http\Controllers\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Customer\Entities\Customer;
use Shetabit\Shopit\Modules\Product\Http\Controllers\Customer\ProductController as BaseProductController;

class ProductController extends BaseProductController
{
    protected ?Customer $user;

    public function show($id): JsonResponse
    {
        $comment = $this->user->productComments()->whereNull('parent_id')->with('childs')->withCommonRelations()->findOrFail($id);

        return response()->success('', compact('comment'));
    }

}
