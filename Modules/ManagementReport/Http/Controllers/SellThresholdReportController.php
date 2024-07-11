<?php

namespace Modules\ManagementReport\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Core\Helpers\Helpers;
use Modules\ManagementReport\Exports\SellThresholdReportExport;
use Modules\ManagementReport\Http\Requests\SellThresholdReportRequest;

class SellThresholdReportController extends Controller
{
    public function makeReport(SellThresholdReportRequest $request)
    {

        $product_thresholds = DB::table('products as p')
            ->join('varieties as v','v.product_id','=','p.id')
            ->join('stores as s','s.variety_id','=','v.id')
            ->select(
                'p.id',
                'p.title',
                DB::raw('SUM(balance) as product_balance'),
                'p.threshold_quantity',
                'p.threshold_date'
            )
            ->whereNotNull('p.threshold_date')
//            ->whereDate('p.threshold_date','>=', Carbon::today())
            ->whereDate('p.threshold_date','<=', Carbon::today()->addDays($request->days))
            ->havingRaw(DB::raw('product_balance >= p.threshold_quantity'))
            ->groupBy('p.id')
            ->get();

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            foreach ($product_thresholds as $item){
                $final_list [] = [
                    $index,
                    $item->title,
                    $item->product_balance,
                    $item->threshold_quantity,
                    (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($item->threshold_date),
                ];
                $index++;
            }

            return Excel::download(new SellThresholdReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        return response()->success('آستانه فروش محصولات', compact('product_thresholds'));
    }
}
