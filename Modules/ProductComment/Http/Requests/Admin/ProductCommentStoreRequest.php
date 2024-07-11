<?php

namespace Modules\ProductComment\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Shetabit\Shopit\Modules\ProductComment\Entities\ProductComment;

class ProductCommentStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'nullable|string|min:5|max:195',
            'body' => 'required|string|min:10',
            'rate' => 'required|integer|digits_between:1,10',
            'show_customer_name' => 'required|boolean',
            'product_id' => 'required|integer|exists:products,id',
            'parent_id' => 'required|exists:product_comments,id',
        ];
    }
}
