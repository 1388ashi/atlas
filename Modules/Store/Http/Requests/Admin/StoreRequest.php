<?php

namespace Modules\Store\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Modules\Store\Entities\Store;
use Shetabit\Shopit\Modules\Store\Http\Requests\Admin\StoreRequest as BaseStoreRequest;

class StoreRequest extends BaseStoreRequest
{
    public function rules()
    {
        return [
            'variety_id' => 'required|integer|min:1|exists:varieties,id',
            'description' => 'required|string|max:1000',
            'type' => ['required', Rule::in(Store::getAvailableTypes())],
            'quantity' => 'required|integer|min:1'
        ];
    }
}
