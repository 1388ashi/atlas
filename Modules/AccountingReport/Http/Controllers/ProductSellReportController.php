<?php

namespace Modules\AccountingReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AccountingReport\Http\Requests\ProductSellReportRequest;
use Modules\AccountingReport\Exports\ProductSellReportExport;
use Modules\Report\Entities\SellType;

class ProductSellReportController extends Controller
{
    public function makeReport(ProductSellReportRequest $request)
    {
        $order_totals = $this->calculateOrders($request);
        $mini_order_totals = $this->calculateMiniOrders($request);
        $refund = $this->calculateMiniOrdersRefund($request);

        $merged_array = array_merge($order_totals, (array)$mini_order_totals);

//        return response()->success('گزارش فروش کالا',compact('order_totals','mini_order_totals', 'merged_array'));
//        return response()->success('گزارش فروش کالا',compact('mini_order_totals'));

        $report = [];
        if ($request->product_id){
            // در صورتی که محصول خاصی در گزارش درخواست شده باشد
            foreach ($merged_array as $ma) {
                if (array_key_exists($ma->variety_code,$report)){
                    // در صورتی که از قبل موجود باشد مقدار آن جمع زده می شود
                    $report[$ma->variety_code]['count_sell_total'] += $ma->count_sell_total??0;
                    $report[$ma->variety_code]['total_product_sell'] += $ma->total_product_sell??0;
                    $report[$ma->variety_code]['total_product_discount'] += $ma->total_product_discount??0;
                    foreach (SellType::all() as $sell_type) {
//                        if ($request->sell_type){
//                            if ($sell_type->key == $request->sell_type){
//                                $sell_type_key = "count_".$sell_type->key;
//                                $report[$ma->variety_code]["$sell_type_key"] += $ma->$sell_type_key??0;
//                            }
//                        } else {
                            $sell_type_key = "count_sell_".$sell_type->key;
                            $report[$ma->variety_code]["$sell_type_key"] += $ma->$sell_type_key??0;
//                        }
                    }
                } else {
                    // در صورتی که از قبل وجود نداشته باشد یه کلید جدید اضافه می شود
                    $report[$ma->variety_code] = (array)$ma;
                }
            }

            foreach ($report as $key => $rt) {
//                echo json_encode($rt['title']);
//                echo json_encode($report[$key]['title']);
                $title = [];
                $title[] = $rt['title'];
                if($rt['label']) {$title[] = $rt['label'];}
                if($rt['value']) {$title[] = $rt['value'];}
                if($rt['color']) {$title[] = "رنگ " . $rt['color'];}
                $report[$key]['title'] = implode(" | ", $title);
                unset($report[$key]['label']);
                unset($report[$key]['value']);
                unset($report[$key]['color']);
            }

        } else {
            // در صورتی محصول خاصی مدنظر نباشد
            foreach ($merged_array as $ma) {
                if (array_key_exists($ma->product_code,$report)){
                    // در صورتی که از قبل موجود باشد مقدار آن جمع زده می شود
                    $report[$ma->product_code]['count_sell_total'] += $ma->count_sell_total??0;
                    $report[$ma->product_code]['total_product_sell'] += $ma->total_product_sell??0;
                    $report[$ma->product_code]['total_product_discount'] += $ma->total_product_discount??0;
                    foreach (SellType::all() as $sell_type) {
//                        if ($request->sell_type){
//                            if ($sell_type->key == $request->sell_type){
//                                $sell_type_key = "count_".$sell_type->key;
//                                $report[$ma->product_code]["$sell_type_key"] += $ma->$sell_type_key??0;
//                            }
//                        } else {
                            $sell_type_key = "count_sell_".$sell_type->key;
                            $report[$ma->product_code]["$sell_type_key"] += $ma->$sell_type_key??0;
//                        }
                    }
                } else {
                    // در صورتی که از قبل وجود نداشته باشد یه کلید جدید اضافه می شود
                    $report[$ma->product_code] = (array)$ma;
                }
            }
        }

        // sell
        $keys = [
            'count_sell_total',
            'total_product_sell',
            'total_product_discount'
        ];
        foreach (SellType::all() as $sell_type) {
            $keys[] = "count_sell_".$sell_type->key;
        }
        $report_totals = [];
        foreach ($keys as $key) {
            $report_totals[$key] = 0;
        }
        foreach ($report as $report_item) {
            foreach ($keys as $key) {
                $report_totals[$key] += $report_item[$key];
            }
        }

        // refund
        $keys_refund = [
            'count_refund_total',
            'total_product_refund',
            'total_product_refund_discount'
        ];
        foreach (SellType::all() as $sell_type) {
            $keys_refund[] = "count_refund_".$sell_type->key;
        }
        $refund_totals = [];
        foreach ($keys_refund as $key) {
            $refund_totals[$key] = 0;
        }
        foreach ($refund as $refund_item) {
            foreach ($keys_refund as $key) {
                $refund_totals[$key] += $refund_item->$key;
            }
        }

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            foreach ($report as $item){
                $final_list [] = [
                    $index,
                    $request->product_id?$item['variety_code']:$item['product_code'],
                    $item['title'],
                    $item['count_sell_online'],
                    $item['count_sell_real'],
                    $item['count_sell_total'],
                    $item['total_product_sell'],
                    $item['total_product_discount'],
                ];
                $index++;
            }

            $final_list[] = [
                'جمع کل',
                '-',
                '-',
                $report_totals['count_sell_online'],
                $report_totals['count_sell_real'],
                $report_totals['count_sell_total'],
                $report_totals['total_product_sell'],
                $report_totals['total_product_discount'],
            ];

            return Excel::download(new ProductSellReportExport($final_list, $request->product_id?'تنوع':'محصول'),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        return response()->success('گزارش فروش کالا',compact(
//            'order_totals',
//            'mini_order_totals',
            'report_totals',
            'report',
            'refund_totals',
            'refund'
        ));
    }

    function calculateOrders($request){
        // در صورتی که گزارش مربوط به یک روز باشد اطلاعات محاسبه ای آن پاک شده و باعث می شود که دوباره محاسبه شوند
        if ($request->start_date == $request->end_date){
            DB::table('orders')
                ->whereDate('created_at',$request->start_date)
                ->update(['items_count' => null]);
        }

        // محاسبه موارد موردنیاز برای گزارشات
        (new \Modules\Core\Helpers\Helpers)->updateOrdersUsefulData();
        (new \Modules\Core\Helpers\Helpers)->updateOrdersCalculateData();
        (new \Modules\Core\Helpers\Helpers)->updateChargeTypeOfTransactions();

        // دریافت کالاهای مورد گزارش از جدول فروش اینترنتی
        $totals = DB::table('order_items as oi')
            ->join('orders as o','o.id','=','oi.order_id')
            ->join('products as p','p.id','=','oi.product_id')
            ->whereNull('o.reserved_id')
            ->where('oi.status',1)
            ->whereIn('o.status',(new \Modules\Core\Helpers\Helpers)->getStatusesForReport())
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->when($request->product_id,function($query) use ($request) {
                $query->where('p.id',$request->product_id);
            })
            ->when($request->product_id,function($query) {
                $query->join('varieties as v','v.id','=','oi.variety_id');
                $query->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id');
                $query->groupBy('v.id');
            })
            ->when(!$request->product_id,function($query) {
                $query->groupBy('p.id');
            })
            ->when($request->sell_type,function($query) use ($request) {
                $sell_type_id = $this->getSellTypeIdByKey($request->sell_type);
                $query->where('o.sell_type_id',$sell_type_id);
            })
        ;

        $select_array = [
            'p.id as product_code',
            'p.title',
            DB::raw('sum(oi.quantity) as count_sell_total'),
            DB::raw("sum((oi.amount + oi.discount_amount) * oi.quantity) as total_product_sell"),
            DB::raw("sum(oi.discount_amount * oi.quantity) as total_product_discount")
        ];

        if($request->product_id){
            $select_array[] = 'v.id as variety_code';
            $select_array[] = 'c.name as color';
            $select_array[] = DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].value')) as value");
            $select_array[] = DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].label')) as label");
        }

        foreach (SellType::all() as $sell_type) {
            $sell_type_id = $sell_type->id;
            $sell_type_key = $sell_type->key;
//            $sell_type_value = $sell_type->value;
            $select_array[] = DB::raw("sum(CASE WHEN o.sell_type_id = $sell_type_id THEN oi.quantity END) AS count_sell_$sell_type_key");
        }

        $totals = $totals->select($select_array);
       // Log::info('Product sell query: ' . $totals->toSql());
        $totals = $totals->get()->toArray();

        return $totals;
    }

    function calculateMiniOrders($request){
        // در صورتی که گزارش مربوط به یک روز باشد اطلاعات محاسبه ای آن پاک شده و باعث می شود که دوباره محاسبه شوند
        if ($request->start_date == $request->end_date){
            DB::table('mini_orders')
                ->whereDate('created_at',$request->start_date)
                ->update(['items_count' => null]);
        }

        // محاسبه موارد موردنیاز برای گزارشات
        (new \Modules\Core\Helpers\Helpers)->updateMiniOrdersCalculateData();

        // دریافت کالاهای مورد گزارش از جدول فروش حضوری
        $totals = DB::table('mini_order_items as oi')
            ->join('mini_orders as o','o.id','=','oi.mini_order_id')
            ->join('products as p','p.id','=','oi.product_id')
//            ->whereNull('o.reserved_id')
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->where('oi.type','sell')
            ->when($request->product_id,function($query) use ($request) {
                $query->where('p.id',$request->product_id);
            })
            ->when($request->product_id,function($query) {
                $query->join('varieties as v','v.id','=','oi.variety_id');
                $query->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id');
                $query->groupBy('v.id');
            })
            ->when(!$request->product_id,function($query) {
                $query->groupBy('p.id');
            })
            ->when($request->sell_type,function($query) use ($request) {
                $sell_type_id = $this->getSellTypeIdByKey($request->sell_type);
                $query->where('o.sell_type_id',$sell_type_id);
            })
        ;

        $select_array = [
            'p.id as product_code',
            'p.title',
            DB::raw('sum(oi.quantity) as count_sell_total'),
            DB::raw("sum((oi.amount + oi.discount_amount) * oi.quantity) as total_product_sell"),
            DB::raw("sum(oi.discount_amount * oi.quantity) as total_product_discount")
        ];

        if($request->product_id){
            $select_array[] = 'v.id as variety_code';
            $select_array[] = 'c.name as color';
            $select_array[] = DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].value')) as value");
            $select_array[] = DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].label')) as label");
        }

        foreach (SellType::all() as $sell_type) {
            $sell_type_id = $sell_type->id;
            $sell_type_key = $sell_type->key;
//            $sell_type_value = $sell_type->value;
            $select_array[] = DB::raw("sum(CASE WHEN o.sell_type_id = $sell_type_id THEN oi.quantity END) AS count_sell_$sell_type_key");
        }

//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'sell' THEN (oi.amount + oi.discount_amount) * oi.quantity END) AS total_product_sell");
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'sell' THEN oi.discount_amount * oi.quantity END) AS total_product_discount");
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'sell' THEN oi.quantity END) AS count_sell_real");
//
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'refund' THEN (oi.amount + oi.discount_amount) * oi.quantity END) AS total_product_refund");
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'refund' THEN oi.discount_amount * oi.quantity END) AS total_product_refund_discount");
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'refund' THEN oi.quantity END) AS count_refund_real");

        $totals = $totals->select($select_array);
//        Log::info($totals->toSql());
        $totals = $totals->get()->toArray();

        return $totals;
    }

    function calculateMiniOrdersRefund($request){
        // دریافت کالاهای مرجوعی از جدول فروش حضوری
        $totals = DB::table('mini_order_items as oi')
            ->join('mini_orders as o','o.id','=','oi.mini_order_id')
            ->join('products as p','p.id','=','oi.product_id')
//            ->whereNull('o.reserved_id')
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->where('oi.type','refund')
            ->when($request->product_id,function($query) use ($request) {
                $query->where('p.id',$request->product_id);
            })
            ->when($request->product_id,function($query) {
                $query->join('varieties as v','v.id','=','oi.variety_id');
                $query->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id');
                $query->groupBy('v.id');
            })
            ->when(!$request->product_id,function($query) {
                $query->groupBy('p.id');
            })
            ->when($request->sell_type,function($query) use ($request) {
                $sell_type_id = $this->getSellTypeIdByKey($request->sell_type);
                $query->where('o.sell_type_id',$sell_type_id);
            })
        ;

        $select_array = [
            'p.id as product_code',
            'p.title',
            DB::raw('sum(oi.quantity) as count_refund_total'),
            DB::raw("sum((oi.amount + oi.discount_amount) * oi.quantity) as total_product_refund"),
            DB::raw("sum(oi.discount_amount * oi.quantity) as total_product_refund_discount")
        ];

        if($request->product_id){
            $select_array[] = 'v.id as variety_code';
            $select_array[] = 'c.name as color';
            $select_array[] = DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].value')) as value");
            $select_array[] = DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].label')) as label");
        }

        foreach (SellType::all() as $sell_type) {
            $sell_type_id = $sell_type->id;
            $sell_type_key = $sell_type->key;
//            $sell_type_value = $sell_type->value;
            $select_array[] = DB::raw("sum(CASE WHEN o.sell_type_id = $sell_type_id THEN oi.quantity END) AS count_refund_$sell_type_key");
        }

//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'sell' THEN (oi.amount + oi.discount_amount) * oi.quantity END) AS total_product_sell");
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'sell' THEN oi.discount_amount * oi.quantity END) AS total_product_discount");
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'sell' THEN oi.quantity END) AS count_sell_real");
//
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'refund' THEN (oi.amount + oi.discount_amount) * oi.quantity END) AS total_product_refund");
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'refund' THEN oi.discount_amount * oi.quantity END) AS total_product_refund_discount");
//        $select_array[] = DB::raw("sum(CASE WHEN oi.type = 'refund' THEN oi.quantity END) AS count_refund_real");

        $totals = $totals->select($select_array);
//        Log::info($totals->toSql());
        $totals = $totals->get()->toArray();

        return $totals;
    }

    public function getSellTypeIdByKey($sell_type_key)
    {
        return SellType::where('key',$sell_type_key)->value('id');
    }
}
