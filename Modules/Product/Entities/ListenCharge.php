<?php

namespace Modules\Product\Entities;

use Modules\Customer\Entities\Customer;
use Shetabit\Shopit\Modules\Product\Entities\ListenCharge as BaseListenCharge;

class ListenCharge extends BaseListenCharge
{
    protected $fillable = [
        'customer_id','variety_id',
    ];


    public static function storeListenCharge(Customer $customer, Variety $variety)
    {
        if ($listenCharge = static::where('customer_id', $customer->id)
            ->where('variety_id', $variety->id)->first()
        ) {
            return $listenCharge;
        }

        ListenCharge::create([
            'variety_id' => $variety->id,
            'customer_id' => $customer->id,
        ]);

        return $listenCharge;
    }


}
