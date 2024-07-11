<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ManagementReport\Exports\HighestCustomerOrderAmountReportExport;
use Modules\ManagementReport\Http\Requests\HighestCustomerOrderAmountReportRequest;

class HighestCustomerOrderAmountReportController extends Controller
{
    public function makeReport(HighestCustomerOrderAmountReportRequest $request)
    {
        $report = DB::table('orders as o')
            ->join('customers as c','o.customer_id','=','c.id')
            ->select(
                'o.id as order_id',
                'c.id as customer_id',
                'c.mobile',
                DB::raw("CONCAT(o.first_name,' ',o.last_name) as full_name"),
                DB::raw('SUM(o.total_amount) as total_amount'),
                DB::raw('count(c.id) as count_orders'),
                DB::raw('SUM(o.items_count) as items_count'),
                DB::raw('SUM(o.items_quantity) as items_quantity'),
//                DB::raw('date(o.created_at) as order_date'),
                'o.province',
                'o.city'
            )
            ->whereIn('o.status', (new \Modules\Core\Helpers\Helpers)->getStatusesForReport())
            ->whereNull('o.reserved_id')
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->when($request->sort=='fee',function($query) use ($request) {
                $query->orderBy('total_amount','desc');
            })
            ->when($request->sort=='count',function($query) use ($request) {
                $query->orderBy('count_orders','desc');
            })
            ->groupBy('c.id');


        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            foreach ($report->get() as $item){
                $final_list [] = [
                    $index,
                    $item->customer_id,
                    $item->mobile,
                    $item->full_name,
                    $item->total_amount,
                    $item->count_orders,
                    $item->items_count,
                    $item->items_quantity,
//                    (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($item->order_date),
                    $item->province,
                    $item->city,
                ];
                $index++;
            }

            return Excel::download(new HighestCustomerOrderAmountReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        $report = $report->paginate(50);

        return response()->success('بیشترین مبلغ و تعداد سفارشات', compact('report'));
   }
}
