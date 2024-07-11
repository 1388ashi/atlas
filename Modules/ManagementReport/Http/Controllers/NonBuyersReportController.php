<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Core\Helpers\Helpers;
use Modules\ManagementReport\Exports\NonBuyersReportExport;
use Modules\ManagementReport\Http\Requests\NonBuyersReportRequest;

class NonBuyersReportController extends Controller
{
    public function makeReport(NonBuyersReportRequest $request)
    {
        $buyers = DB::table('customers as c')
            ->join('orders as o','o.customer_id','=','c.id')
            ->select('c.id')
            ->whereRaw("date(o.created_at) > DATE_SUB(NOW(), INTERVAL {$request->days} DAY)")
            ->groupBy('c.id')
            ->pluck('c.id');

        $non_buyers = DB::table('customers as c')
            ->join('orders as o','o.customer_id','=','c.id')
            ->select(
                'c.id',
                'c.mobile',
                DB::raw("CONCAT(o.first_name, ' ', o.last_name) as full_name"),
                DB::raw('max(date(o.created_at)) as last_order_date'),
                'o.province',
                'o.city',
            )
            ->whereNotIn('c.id',$buyers)
            ->groupBy('c.id')
            ->orderBy(DB::raw('last_order_date'),'desc');

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            foreach ($non_buyers->get() as $item){
                $final_list [] = [
                    $index,
                    $item->mobile,
                    $item->full_name,
                    (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($item->last_order_date),
                    $item->province,
                    $item->city,
                ];
                $index++;
            }

            return Excel::download(new NonBuyersReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        $non_buyers = $non_buyers->paginate(100);

        return response()->success('خرید نکرده ها',compact('non_buyers'));
    }
}
