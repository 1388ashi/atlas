<?php

namespace Modules\ProductComment\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Core\Helpers\Helpers;
use Modules\ProductComment\Entities\ProductComment;
use Modules\ProductComment\Http\Requests\Admin\ProductCommentStoreRequest;
use Shetabit\Shopit\Modules\ProductComment\Http\Controllers\Admin\ProductCommentController as BaseProductCommentController;

class ProductCommentController extends BaseProductCommentController
{

    public function index(): JsonResponse
    {
        $status = \request('status', false);
        $comments = ProductComment::query()->whereNull('parent_id')->with('childs')->latest()->withCommonRelations();
        if ($status && Str::contains($status, ProductComment::getAvailableStatus())){
            $comments->status($status);
        };
        Helpers::applyFilters($comments);
        $comments = Helpers::paginateOrAll($comments);

        return response()->success('لیست دیدگاه ها', compact('comments'));
    }

    public function show($id): JsonResponse
    {
        $comment = ProductComment::query()->whereNull('parent_id')->with('childs')->withCommonRelations()->findOrFail($id);

        return response()->success('', compact('comment'));
    }


    public function answer(ProductCommentStoreRequest $request , ProductComment $productComment): JsonResponse
    {
        $productComment->fill($request->except('status'));
        $productComment->creator()->associate(auth()->user());
        $productComment->product()->associate($request->product_id);
        $productComment->status=ProductComment::STATUS_APPROVED;
        $productComment->save();

        return response()->success('جواب با موفقیت ثبت شد.', compact('productComment'));
    }

    public function destroy($id): JsonResponse
    {
        $comment = ProductComment::query()->findOrFail($id);

        foreach ($comment->childs as $child){
            $child->delete();
        }

        $comment->delete();

        return response()->success('دیدگاه با موفقیت حذف شد', compact('comment'));
    }

}
