<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ManagementReport\Exports\MostViewReportExport;
use Modules\ManagementReport\Http\Requests\MostViewReportRequest;

class MostViewReportController extends Controller
{
    public function makeReport(MostViewReportRequest $request)
    {
        $product_views = $this->productViews($request);

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            $count_view = 0;
            $count_unique_view = 0;
            foreach ($product_views as $item){
                $final_list [] = [
                    $index,
                    $item->product_code,
                    $item->title,
                    $item->view_count,
                    $item->unique_view_count,
                ];
                $index++;
                $count_view += $item->view_count;
                $count_unique_view += $item->unique_view_count;
            }

            $final_list[] = [
                'جمع کل',
                '-',
                '-',
                $count_view,
                $count_unique_view,
            ];

            return Excel::download(new MostViewReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        return response()->success('پر بازدید ترین محصولات', compact('product_views'));
    }

    public function productViews($request): array
    {
        // دریافت تعداد بازدیدهای محصول
        $result = DB::table('views as v')
            ->join('products as p','p.id','=','v.viewable_id')
            ->where('viewable_type','Modules\Product\Entities\Product')
            ->where('collection','product')
            ->whereDate('v.viewed_at','>=',$request->start_date)
            ->whereDate('v.viewed_at','<=',$request->end_date)
            ->groupBy('v.viewable_id')
            ->select(
                'p.id as product_code',
                'p.title',
                DB::raw('COUNT(*) AS view_count'),
                DB::raw('COUNT(DISTINCT ip) AS unique_view_count'),
            )
            ->orderBy(DB::raw('view_count'),'desc')
            ->get()
            ->toArray();

        foreach ($result as $item) {
            $item->total_sell = $this->getTotalSellsOfProduct($item->product_code, $request->start_date, $request->end_date);
        }

        return $result;
    }

    public function getTotalSellsOfProduct($product_id, $start_date, $end_date)
    {
        return DB::table('order_items')
            ->whereDate('created_at','>=',$start_date)
            ->whereDate('created_at','<=',$end_date)
            ->where('product_id', $product_id)
            ->sum('quantity');
    }
}
