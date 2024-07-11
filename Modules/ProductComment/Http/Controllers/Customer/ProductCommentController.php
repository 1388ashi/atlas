<?php

namespace Modules\ProductComment\Http\Controllers\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Customer\Entities\Customer;
use Shetabit\Shopit\Modules\ProductComment\Http\Controllers\Customer\ProductCommentController as BaseProductCommentController;

class ProductCommentController extends BaseProductCommentController
{
    protected ?Customer $user;

    public function index(): JsonResponse
    {
        $comments = $this->user->productComments()->with('product','childs')->filters()->paginateOrAll();

        return response()->success('لیست دیدگاه ها', compact('comments'));
    }
}
