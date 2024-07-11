<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Core\Helpers\Helpers;
use Modules\ManagementReport\Exports\HighestOrderPriceReportExport;
use Modules\ManagementReport\Http\Requests\HighestOrderPriceReportRequest;

class HighestOrderPriceReportController extends Controller
{
    public function makeReport(HighestOrderPriceReportRequest $request)
    {
        $report = DB::table('orders as o')
            ->join('customers as c','o.customer_id','=','c.id')
            ->select(
                'o.id as order_id',
                'c.id as customer_id',
                'c.mobile',
                DB::raw("CONCAT(o.first_name,' ',o.last_name) as full_name"),
                'o.total_amount',
//                'o.total_payable_amount',
//                'o.discount_amount',
                'o.items_count',
                'o.items_quantity',
                DB::raw('date(o.created_at) as order_date'),
                'o.province',
                'o.city'
            )
            ->whereIn('o.status', (new \Modules\Core\Helpers\Helpers)->getStatusesForReport())
            ->whereNull('o.reserved_id')
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->orderBy('o.total_amount','desc');

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            foreach ($report->get() as $item){
                $final_list [] = [
                    $index,
                    $item->order_id,
                    $item->customer_id,
                    $item->mobile,
                    $item->full_name,
                    $item->total_amount,
                    $item->items_count,
                    $item->items_quantity,
                    (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($item->order_date),
                    $item->province,
                    $item->city,
                ];
                $index++;
            }

            return Excel::download(new HighestOrderPriceReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        $report = $report->paginate(50);

        return response()->success('بیشترین مبلغ سفارشات', compact('report'));
    }
}
