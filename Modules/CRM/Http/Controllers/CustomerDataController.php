<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Helpers\Helpers;
use Modules\CRM\Http\Requests\CustomerDataRequest;

class CustomerDataController extends Controller
{
    public function showData(CustomerDataRequest $request)
    {
        $customer = DB::table('customers')->where('mobile',$request->mobile)->first();
        $customer_data = [
            'profile_data' => (new \Modules\CRM\Helpers\Helpers)->getProfileData($customer->id),
        ];

        return response()->success('اطلاعات کاربر', compact('customer_data'));
    }
}
