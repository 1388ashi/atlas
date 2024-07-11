<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ManagementReport\Exports\MostSaleReportExport;
use Modules\ManagementReport\Http\Requests\MostSaleReportRequest;

class MostSaleReportController extends Controller
{
    public function makeReport(MostSaleReportRequest $request)
    {
        $order_product_totals = $this->mostSaleInOrders($request);
        $mini_order_product_totals = $this->mostSaleInMiniOrders($request);

        $total_list_merge = [];
        foreach ($order_product_totals as $order_total) {
            // افزودن لیست آنلاین به همراه کلید موردنیاز به لیست کل
            $total_list_merge[$order_total->product_code] = $order_total;
        }

        foreach ($mini_order_product_totals as $mini_order_total) {
            // افزودن لیست حضوری به همراه کلید موردنیاز به لیست کل
            // درصورت وجود داشتن در لیست، به مقدار آن افزوده می شود
            if (array_key_exists($mini_order_total->product_code,$total_list_merge)){
                // وجود دارد و افزوده می شود
                $total_list_merge[$mini_order_total->product_code]->count_sell_real = $mini_order_total->count_sell_real;
            } else {
                // وجود ندارد و جدید است
                $total_list_merge[$mini_order_total->product_code] = $mini_order_total;
            }
        }

        // چینش مجدد لیست مجموع
        $total_product_list = [];
        foreach ($total_list_merge as $item) {
            $total_product_list[] = [
                "product_code" => $item->product_code,
                "title" => $item->title,
                "count_sell_online" => (int)($item->count_sell_online??0),
                "count_sell_real" => (int)($item->count_sell_real??0),
                "count_sell_all" => (int)($item->count_sell_online??0) + (int)($item->count_sell_real??0),
            ];
        }

        // مرتب سازی بر اساس فیلد count_sell_all و به صورت نزولی
        usort($total_product_list, function($a, $b) {
            return $b['count_sell_all'] - $a['count_sell_all'];
        });

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            $count_sell_online = 0;
            $count_sell_real = 0;
            $count_sell_total = 0;
            foreach ($total_product_list as $item){
                $final_list [] = [
                    $index,
                    $item['product_code'],
                    $item['title'],
                    $item['count_sell_online'],
                    $item['count_sell_real'],
                    $item['count_sell_all'],
                ];
                $index++;
                $count_sell_online += $item['count_sell_online'];
                $count_sell_real += $item['count_sell_real'];
                $count_sell_total += $item['count_sell_all'];
            }

            $final_list[] = [
                'جمع کل',
                '-',
                '-',
                $count_sell_online,
                $count_sell_real,
                $count_sell_total,
            ];

            return Excel::download(new MostSaleReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        return response()->success('پرفروش ترین محصولات', compact('order_product_totals','mini_order_product_totals','total_product_list'));
    }

    public function mostSaleInOrders($request): array
    {
        // دریافت کالاهای مورد گزارش از جدول فروش اینترنتی
        return DB::table('order_items as oi')
            ->join('orders as o','o.id','=','oi.order_id')
            ->join('products as p','p.id','=','oi.product_id')
            ->whereNull('o.reserved_id')
            ->where('oi.status',1)
            ->whereIn('o.status',(new \Modules\Core\Helpers\Helpers)->getStatusesForReport())
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->groupBy('p.id')
            ->select(
                'p.id as product_code',
                'p.title',
                DB::raw('sum(oi.quantity) as count_sell_online'),
            )
            ->orderBy(DB::raw('count_sell_online'),'desc')
            ->get()
            ->toArray();
    }

    public function mostSaleInMiniOrders($request): array
    {
        // دریافت کالاهای مورد گزارش از جدول فروش حضوری
        return DB::table('mini_order_items as oi')
            ->join('mini_orders as o','o.id','=','oi.mini_order_id')
            ->join('products as p','p.id','=','oi.product_id')
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->where('oi.type','sell')
            ->groupBy('p.id')
            ->select(
                'p.id as product_code',
                'p.title',
                DB::raw('sum(oi.quantity) as count_sell_real'),
            )
            ->orderBy(DB::raw('count_sell_real'),'desc')
            ->get()
            ->toArray();
    }
}
