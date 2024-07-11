<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ManagementReport\Exports\BuyersReportExport;
use Modules\ManagementReport\Http\Requests\BuyersReportRequest;

class BuyersReportController extends Controller
{
    public function makeReport(BuyersReportRequest $request)
    {
        $buyers = DB::table('customers as c')
            ->join('orders as o','o.customer_id','=','c.id')
            ->select(
                'c.id',
                'c.mobile',
                DB::raw("CONCAT(o.first_name, ' ', o.last_name) as full_name"),
                DB::raw('max(date(o.created_at)) as last_order_date'),
                DB::raw('o.total_amount'),
                DB::raw('o.items_count'),
                DB::raw('o.items_quantity'),
                'o.province',
                'o.city',
            )
//            ->whereRaw("date(o.created_at) > DATE_SUB(NOW(), INTERVAL {$request->days} DAY)")
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->when($request->type=='province',function($query) use ($request) {
                $query->where('o.province',$request->province);
            })
            ->when($request->type=='city',function($query) use ($request) {
                $query->where('o.province',$request->province);
                $query->where('o.city',$request->city);
            })
            ->groupBy('c.id')
            ->orderBy(DB::raw('last_order_date'),'desc');

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            foreach ($buyers->get() as $item){
                $final_list [] = [
                    $index,
                    $item->mobile,
                    $item->full_name,
                    (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($item->last_order_date),
                    $item->total_amount,
                    $item->items_count,
                    $item->items_quantity,
                    $item->province,
                    $item->city,
                ];
                $index++;
            }

            return Excel::download(new BuyersReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        $buyers = $buyers->paginate(100);

        return response()->success('خرید کرده ها',compact('buyers'));
    }
}
