<?php

namespace Modules\Order\Http\Requests\User;

use Modules\GiftPackage\Entities\GiftPackage;
use Modules\Order\Services\Validations\Customer\OrderValidationService;
use Shetabit\Shopit\Modules\Order\Http\Requests\User\OrderStoreRequest as BaseOrderStoreRequest;

class OrderStoreRequest extends BaseOrderStoreRequest
{
    public function passedValidation()
    {
        $this->user()->removeEmptyCarts();
        $service = new OrderValidationService($this->all(), $this->user());
        $this->orderStoreProperties = $service->properties;
        $this->merge([
            'gift_package_price' => ($this->get('gift_package_id')) ? GiftPackage::findOrFail($this->get('gift_package_id'))->price : 0
        ]);
    }
}
