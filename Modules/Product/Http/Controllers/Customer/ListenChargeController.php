<?php

namespace Modules\Product\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Modules\Customer\Entities\Customer;
use Modules\Product\Entities\ListenCharge;
use Modules\Product\Http\Requests\Customer\ListenChargeStoreRequest;
use Shetabit\Shopit\Modules\Product\Http\Controllers\Customer\ListenChargeController as BaseListenChargeController;

class ListenChargeController extends Controller
{
    public function store(ListenChargeStoreRequest $request)
    {
        $variety = $request->variety_id;
        /**
         * @var $customer Customer
         */

        $customer = \Auth::user();
        $listenCharge = ListenCharge::storeListenCharge($customer, $variety);

        return response()
            ->success(
                'عملیات با موفقیت انجام شد. در صورت موجود شدن محصول به شما اطلاع داده خواهد شد',
                compact('listenCharge')
            );
    }

    public function destroy($variety_id)
    {
        /**
         * @var $customer Customer
         */
        $customer = \Auth::user();
        $listenCharge = $customer->listenCharges()->where('variety_id', $variety_id)->first();
        if ($listenCharge) {
            $listenCharge->delete();
        }

        return response()->success('عملیات لغو با موفقیت انجام شد');
    }

}
