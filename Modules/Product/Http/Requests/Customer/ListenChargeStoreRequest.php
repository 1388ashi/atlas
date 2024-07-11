<?php

namespace Modules\Product\Http\Requests\Customer;

use Modules\Product\Entities\Product;
use Modules\Product\Entities\Variety;
use Shetabit\Shopit\Modules\Product\Http\Requests\Customer\ListenChargeStoreRequest as BaseListenChargeStoreRequest;

class ListenChargeStoreRequest extends BaseListenChargeStoreRequest
{
    public ?Variety $variety;

    public function passedValidation()
    {
        $this->variety = Variety::findOrFail($this->route('variety_id'));
    }
}
