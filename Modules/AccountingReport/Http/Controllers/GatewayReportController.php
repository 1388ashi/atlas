<?php

namespace Modules\AccountingReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AccountingReport\Helpers\Helpers;
use Modules\AccountingReport\Http\Requests\GatewayReportRequest;

class GatewayReportController extends Controller
{
    public function makeReport(GatewayReportRequest $request)
    {
        (new \Modules\Core\Helpers\Helpers)->updateOrdersCalculateData();
        $order_gateway_payments = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalGatewayPayments(null,\Request()); // مجموع ورودی ها به تفکیک درگاه
        $gateway_charges = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalGatewayCharges(null,\Request()); // مجموع شارژ ها به تفکیک درگاه

        // حذف کلید ها با مقدار 0
        $emptyKeys = array_keys($order_gateway_payments, "0");
        foreach ($emptyKeys as $key) {
            unset($order_gateway_payments[$key]);
        }

        $merged_array = $order_gateway_payments;
        foreach ($gateway_charges as $key => $ogp) {
            if (array_key_exists($key,$merged_array)){
                $merged_array[$key] += $ogp;
            } else {
                $merged_array[$key] = $ogp;
            }
        }

        $gateways = (new \Modules\AccountingReport\Helpers\Helpers)->getGateways();

        return response()->success('گزارش ورودی ها به تفکیک درگاه',compact('order_gateway_payments','gateway_charges', 'merged_array','gateways'));
    }
}
