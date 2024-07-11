<?php

namespace Modules\Order\Services\Validations\Customer;

use Illuminate\Support\Facades\Log;
use Modules\Core\Helpers\Helpers;
use Modules\Product\Entities\Variety;
use Modules\Shipping\Entities\Shipping;
use Shetabit\Shopit\Modules\Order\Services\Validations\Customer\OrderValidationService as BaseOrderValidationService;

class OrderValidationService extends BaseOrderValidationService
{
    public function checkAdminVarieties()
    {
        $varietyArray = $this->varieties ?: false;
        $varietyIds = collect($varietyArray)->pluck('id');
        $varieties = Variety::query()->whereIn('id', $varietyIds->toArray())->get();
        if (count($varietyIds) != count($varieties)){
            throw Helpers::makeValidationException('شناسه تنوع های ارسال شده نامعتبر است');
        }
        foreach($varietyArray as $cartItem){
            foreach ($varieties as $index => $v) {
                if ($v['id'] == $cartItem['id']){
                    // Log::info($cartItem['id'] . ' -> ' . $cartItem['quantity'] . " -> " . $varieties[$index]['quantity']);
                    if ($cartItem['quantity'] > $varieties[$index]['quantity']){
                        throw Helpers::makeValidationException(
                            'تعداد انتخاب شده محصول ' . $varieties[$index]->product->title . ' بیشتر از موجودی انبار است.' . "(تنوع: " . $varieties[$index]->store->variety_id . ")",
                            'variety_quantity'
                        );
                    }
                }
            }
            $this->totalQuantity += $cartItem['quantity'];
//            Log::info("CHECK QUANTITY -> " . $this->totalQuantity);
        }
//         foreach ($varietyArray as $key => $variety) {
//             $baseVariety = $varieties[$key];
//             if ($baseVariety->store->balance < $variety['quantity']) {
//                 throw Helpers::makeValidationException(
//                     'تعداد انتخاب شده محصول ' . $baseVariety->product->title . ' بیشتر از موجودی انبار است.' . "(تنوع: " . $baseVariety->store->variety_id . ")" . "(" . $variety['quantity'] . ")",
//                     'variety_quantity'
//                 );
//             }
//             $this->totalQuantity += $variety['quantity'];
//         }
    }
}
