<?php

namespace Modules\Store\Http\Controllers\Admin;

use Modules\Store\Entities\Store;
use Shetabit\Shopit\Modules\Store\Http\Controllers\Admin\StoreController as BaseStoreController;

class StoreController extends BaseStoreController
{
    public function storeWealthReport()
    {
        $sumAmounts = 0;
        $stores = Store::query()->where('balance','!=','0')->get();
        $sumStoreBalance = $stores->sum('balance');

        foreach ($stores as $store){
            if(isset($store->variety)){
                $sumAmounts += $store->balance * $store->variety->price;
            }

        }

        return response()->success('success',compact('sumStoreBalance','sumAmounts'));
    }
}
