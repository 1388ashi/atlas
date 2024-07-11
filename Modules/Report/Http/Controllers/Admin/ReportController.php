<?php

namespace Modules\Report\Http\Controllers\Admin;

use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Customer\Entities\Customer;
use Modules\Order\Entities\Order;
use Modules\Report\Entities\MiniOrderReport;
use Modules\Report\Entities\SellType;
use Modules\Report\Exports\CustomerReportExport;
use Modules\Report\Exports\CustomerReportSimpleExport;
use Modules\Report\Exports\WalletReportExport;
use Shetabit\Shopit\Modules\Report\Http\Controllers\Admin\ReportController as BaseReportController;

class ReportController extends BaseReportController
{
    public function customers(Request $request)
    {
        $reports = ($this->reportService->customerReport($request))->groupBy('customer_id')
            ->addSelect([
                DB::raw('Max(created_at) AS latest_order_date'),
                DB::raw('Min(created_at) AS first_order_date'),
            ]);

        $reports = $reports->take(8000)->paginateOrAll(\request('per_page', 10));
        foreach ($reports as $report) {
            $report->order_items_count = $report->_order_items_count;
            $report->attribute_ids = $report->_attribute_ids;
            $report->product_ids = $report->_product_ids;
            $report->customer_name = (isset($report->customer->addresses[0]?->first_name) || isset($report->customer->addresses[0]?->last_name)) ? $report->customer->addresses[0]?->first_name .' '.$report->customer->addresses[0]?->last_name : '';
            $report->city_name = $report->customer->addresses->count() ? $report->customer->addresses[0]->city->name : '';
            $report->first_order_date = (new Verta($report->first_order_date))->format('Y/n/j');
            $report->latest_order_date = (new Verta($report->latest_order_date))->format('Y/n/j   H:i');
            $report->append(['statuses_info', 'count', 'order_info']);
        }

        if (\request()->header('accept') == 'x-xlsx') {
            return Excel::download(new CustomerReportExport($reports),
                __FUNCTION__.'-' . now()->toDateString() . '.xlsx');
        }

        return response()->success('', compact(
            'reports',
        ));
    }

    public function customersIncomesDetail(Request $request)
    {
        $report = $this->reportService->customerReport($request);

        $total_income = (clone $report)->sum('total');
        $total_order_items_count = (clone $report)->sum('order_items_count');
        $total_discount_amount = (clone $report)->sum('discount_amount');
        $total_not_coupon_discount_amount = (clone $report)->sum('not_coupon_discount_amount');
        $total_shipping_amount = (clone $report)->sum('shipping_amount');
        $order_statuses = Order::getAllStatuses(clone $report);

        return response()->success('', compact(
            'total_income', 'total_order_items_count', 'total_discount_amount',
            'total_not_coupon_discount_amount',
            'total_shipping_amount', 'order_statuses'
        ));
    }

    public function varietiesReport(){
        $limit = \Request()->limit??20;
        $page = \Request()->page??1;
        $skip = ($page-1) * $limit;
        $report = DB::table('varieties as v')
            ->join('products as p' ,'v.product_id' , '=', 'p.id')
            ->join('stores as s' ,'s.variety_id' , '=', 'v.id')
            ->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id')
            ->select(
                'p.id as p_id',
//                'v.id as v_id',
                'p.title as p_title',
//                "c.name as color",
                DB::raw('sum(s.balance) as sum'),
                'v.price',
            )
            ->groupBy('p.id')
            ->skip($skip)
            ->paginate($limit);

        return view('report_varieties',['data' => $report, 'limit' => $limit]);
    }

    public function productsReport(){

        $status = null;

        $limit = \Request()->limit??20;
        $page = \Request()->page??1;
        $skip = ($page-1) * $limit;
        $report = DB::table('varieties as v')
            ->join('products as p' ,'v.product_id' , '=', 'p.id')
            ->join('stores as s' ,'s.variety_id' , '=', 'v.id')
            ->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id')
            ->select(
                'p.id as p_id',
//                'v.id as v_id',
                'p.title as p_title',
//                "c.name as color",
                DB::raw('sum(s.balance) as sum'),
                'p.unit_price',
                'p.purchase_price',
                'p.status',
            )
            ->when($status, function ($query) use ($status) {
                $query->where('p.status', $status);
            })
            ->groupBy('p.id')
            ->skip($skip)
            ->paginate($limit);

        return view('report_products',['data' => $report, 'limit' => $limit]);
    }

    public function productsBalance(){
        $status = request()->status;
        $date = request()->date;
        $currentDate = Carbon::now();

        #TODO گزارش موجودی انبار به ازای یک روز خاص

        $report = DB::table('varieties as v')
            ->join('products as p' ,'v.product_id' , '=', 'p.id')
            ->join('stores as s' ,'s.variety_id' , '=', 'v.id')
            ->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id')
            ->select(
                'p.id',
                'p.title',
                DB::raw('sum(s.balance) as sum'),
                'p.unit_price',
                'p.purchase_price',
                DB::raw('sum(s.balance * p.unit_price) as total_unit_price'),
                DB::raw('sum(s.balance * p.purchase_price) as total_purchase_price'),
                'p.status',
                'v.id as variety_id'
            )
            ->when($status, function ($query) use ($status) {
                $query->where('p.status', $status);
            })
            ->when($status == 'draft', function ($query) use ($status) {
                $query->having('sum','>',0);
            })
            ->whereNull('v.deleted_at')
            ->groupBy('p.id')
            ->get();

        if (isset($date)){
            $store_transactions = DB::table('store_transactions as st')
                ->select(['st.id','st.type','st.quantity','st.created_at'])
                ->whereBetween('st.created_at', [$date, now()])
                ->join('stores', 'st.store_id', '=', 'stores.id')
                ->select('st.*', 'stores.variety_id')
                ->get();

            foreach ($report as $reportItem){
                foreach ($store_transactions as $transaction){
                    if ($transaction->variety_id == $reportItem->variety_id){
                        if ($transaction->type == 'increment'){
                            $reportItem->sum += $transaction->quantity;
                        }
                        if ($transaction->type == 'decrement'){
                            $reportItem->sum -= $transaction->quantity;
                        }
                    }
                }
            }

        }

        $sum = 0;
        $sum_unit_price = 0;
        $sum_purchase_price = 0;
        foreach ($report as $r) {
            $sum += $r->sum;
            $sum_unit_price += $r->total_unit_price;
            $sum_purchase_price += $r->total_purchase_price;
            $r->status_title = match ($r->status){
                'draft' => 'پیش نویس',
                'available' => 'موجود',
                'soon' => 'به زودی',
                'out_of_stock' => 'ناموجود',
                'available_offline' => 'موجود آفلاین',
                default => 'نامشخص'
            };
        }

        return response()->success('لیست موجودی محصولات', [
            'data' => $report,
            'sum' => $sum,
            'sum_unit_price' => $sum_unit_price,
            'sum_purchase_price' => $sum_purchase_price,
        ]);
    }

    public function reportDiscountBetweenDatesHtml()
    {
        $start_date = \Request()->start_date;
        $end_date = \Request()->end_date;

        $startDate = Carbon::parse($start_date)->startOfDay();
        $endDate = Carbon::parse($end_date)->endOfDay();

        $statuses = ['delivered','in_progress'/*,'wait_for_payment'*/];

        $orders = DB::table('orders')
            ->whereBetween('created_at',[$startDate,$endDate])
            ->whereIn('status',$statuses)
            ->pluck('id');

        $order_items = DB::table('order_items as oi')
            ->join('products as p' ,'oi.product_id' , '=', 'p.id')
            ->join('varieties as v' ,'oi.variety_id' , '=', 'v.id')
//            ->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id')
            ->whereIn('order_id',$orders)
            ->where('discount_amount','>',0)
            ->select(
                'p.id as p_id',
                'v.id as v_id',
                'p.title as p_title',
                DB::raw('(amount + discount_amount) as original'),
                'discount_amount as discount',
                'amount as final',
                'quantity'
            )
            ->get();

        $html = "<style>body{direction: rtl} table,th,td{border: 1px solid black; text-align: center; font-family: Tahoma,serif} table{border-collapse: collapse} .inline-date{display: inline-block; direction: ltr}</style>";

        $html .= "<h3>فروش محصولات تخفیف دار بین دو تاریخ <div class='inline-date'>$start_date</div> و <div class='inline-date'>$end_date</div></h3>";

        $html .= "<table>";

        $html .= "<tr>";
        $html .= "<th>کد محصول</th>";
        $html .= "<th>کد تنوع</th>";
        $html .= "<th>عنوان محصول</th>";
        $html .= "<th>عنوان تنوع</th>";
        $html .= "<th>قیمت اصلی</th>";
        $html .= "<th>تخفیف</th>";
        $html .= "<th>قیمت پس از تخفیف</th>";
        $html .= "<th>تعداد فروش</th>";
        $html .= "</tr>";

        $total_with_discount = 0;
        $total_without_discount = 0;

        foreach($order_items as $order_item){

            $attributes_array = DB::table('attribute_variety')
                ->select('value')
                ->where('variety_id',$order_item->v_id)
                ->pluck('value')
                ->toArray();

            $attributes = implode('-',$attributes_array);

            $p_id = $order_item->p_id;
            $v_id = $order_item->v_id;
            $title = $order_item->p_title;
            $v_title = $attributes;
            $price = number_format($order_item->original, 0 , '.' , ',' ) . " تومان";
            $discount = number_format($order_item->discount, 0 , '.' , ',' ) . " تومان";
            $final = number_format($order_item->final, 0 , '.' , ',' ) . " تومان";
            $quantity = number_format($order_item->quantity, 0 , '.' , ',' );

            $total_with_discount += $order_item->quantity * $order_item->final;
            $total_without_discount += $order_item->quantity * $order_item->original;

            $html .= "<tr>";
            $html .= "<td>$p_id</td>";
            $html .= "<td>$v_id</td>";
            $html .= "<td>$title</td>";
            $html .= "<td>$v_title</td>";
            $html .= "<td>$price</td>";
            $html .= "<td>$discount</td>";
            $html .= "<td>$final</td>";
            $html .= "<td>$quantity</td>";
            $html .= "</tr>";
        }

        $html .= "</table>";

        $html .= "<hr>";

        $html .= "<table>";

        $html .= "<tr>";
        $html .= "<th>مجموع فروش بدون تخفیف</th>";
        $html .= "<th>مجموع فروش با تخفیف</th>";
        $html .= "<th>میزان اختلاف</th>";
        $html .= "</tr>";

        $total_difference = $total_without_discount - $total_with_discount;
        $total_difference = number_format($total_difference, 0 , '.' , ',' ) . " تومان";
        $total_with_discount = number_format($total_with_discount, 0 , '.' , ',' ) . " تومان";
        $total_without_discount = number_format($total_without_discount, 0 , '.' , ',' ) . " تومان";


        $html .= "<tr>";
        $html .= "<td>$total_without_discount</td>";
        $html .= "<td>$total_with_discount</td>";
        $html .= "<td>$total_difference</td>";
        $html .= "</tr>";

        echo $html;
    }

    public function reportDiscountBetweenDates()
    {
        $start_date = \Request()->start_date;
        $end_date = \Request()->end_date;

        $startDate = Carbon::parse($start_date)->startOfDay();
        $endDate = Carbon::parse($end_date)->endOfDay();

        $statuses = ['delivered','in_progress'/*,'wait_for_payment'*/];

        $orders = DB::table('orders')
            ->whereBetween('created_at',[$startDate,$endDate])
            ->whereIn('status',$statuses)
            ->pluck('id');

        $order_items = DB::table('order_items as oi')
            ->join('products as p' ,'oi.product_id' , '=', 'p.id')
            ->join('varieties as v' ,'oi.variety_id' , '=', 'v.id')
//            ->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id')
            ->whereIn('order_id',$orders)
            ->where('discount_amount','>',0)
            ->select(
                'p.id as p_id',
                'v.id as v_id',
                'p.title as p_title',
                DB::raw('(amount + discount_amount) as original'),
                'discount_amount as discount',
                'amount as final',
                'quantity'
            )
            ->get();

        $total_with_discount = 0;
        $total_without_discount = 0;

        $result = array();

        foreach($order_items as $order_item){

            $attributes_array = DB::table('attribute_variety')
                ->select('value')
                ->where('variety_id',$order_item->v_id)
                ->pluck('value')
                ->toArray();

            $attributes = implode('-',$attributes_array);

            $p_id = $order_item->p_id;
            $v_id = $order_item->v_id;
            $title = $order_item->p_title;
            $v_title = $attributes;
            $price = $order_item->original;
            $discount = $order_item->discount;
            $final = $order_item->final;
            $quantity = $order_item->quantity;

            $total_with_discount += $order_item->quantity * $order_item->final;
            $total_without_discount += $order_item->quantity * $order_item->original;

            $r = [
                'p_id' => $p_id,
                'v_id' => $v_id,
                'title' => $title,
                'v_title' => $v_title,
                'price' => $price,
                'discount' => $discount,
                'final' => $final,
                'quantity' => $quantity,
            ];

            $result['data'][] = $r;

        }

        $total_difference = $total_without_discount - $total_with_discount;

        $result['total_without_discount'] = $total_without_discount;
        $result['total_with_discount'] = $total_with_discount;
        $result['total_difference'] = $total_difference;

        return response()->json($result);
    }


    public function commonHtmlForReport($date=null)
    {
        $checked = isset($_GET['reCalculate'])?'checked':'';
        $html = "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'>";
        $html .= "<script src='https://code.jquery.com/jquery-3.7.1.min.js'></script>";
        $html .= "<style>
                        @font-face{
                            font-family:iransans;
                            src:url('/assets/font/IRANSansWeb(FaNum).ttf');
                        }
                        body{
                            direction: rtl;
                            font-family: iransans,Tahoma,serif;
                        }
                        .bg-warning{
                            background: #d2b16b;
                        }
                        table,th,td{
                            border: 1px solid black;
                            text-align: center;
                            font-family: iransans,Tahoma,serif;
                        }
                        table{
                            border-collapse: collapse;
                            font-size: 80%;
                        }
                        td,th{
                            padding: 3px 6px !important;
                            vertical-align: middle !important;
                        }
                        .inline-date{
                            display: inline-block;
                            direction: ltr;
                        }
                        .table thead th {
                            border-bottom: 2px solid #000000;
                        }
                        .table td, .table th {
                            border-top: 1px solid #000000;
                        }
                    </style>
                    <script>
                       function showReport(){
                            const btn = document.getElementById('show_report');
                            btn.click();
                       }
                       function nextDay(){
                            // Get the input date value
                            let inputDate = new Date(document.getElementById('inputDate').value);

                            // Add 1 day to the input date
                            inputDate.setDate(inputDate.getDate() + 1);

                            // Set the new date value to the input field
                            document.getElementById('inputDate').value = inputDate.toISOString().slice(0,10);

                            showReport();
                       }
                       function previousDay(){
                            // Get the input date value
                            let inputDate = new Date(document.getElementById('inputDate').value);

                            // Add 1 day to the input date
                            inputDate.setDate(inputDate.getDate() - 1);

                            // Set the new date value to the input field
                            document.getElementById('inputDate').value = inputDate.toISOString().slice(0,10);

                            showReport();
                       }
                    </script>
                    ";
        if ($date) {
            $html .= "<form>
                        <div class='row mt-3'>
                            <div class='col-4'> </div>
                            <div class='col-4 card p-2'>
                                <div class='container-fluid'>
                                    <div class='row mb-1'>
                                        <input class='form-control' id='inputDate' name='date' type='date' value='$date'>
                                    </div>

                                    <div class='row mb-1'>
                                        <label><input name='reCalculate' $checked type='checkbox' value='1'> محاسبه مجدد</label>
                                    </div>

                                    <div class='row mb-1 d-flex justify-content-around'>
                                        <button class='btn btn-outline-info' onclick='previousDay()'>👉 روز قبل</button>
                                        <button class='btn btn-primary' type='submit' id='show_report'>نمایش گزارش</button>
                                        <button class='btn btn-outline-info' onclick='nextDay()'>روز بعد 👈</button>
                                    </div>
                                </div>
                            </div>
                            <div class='col-4'> </div>
                        </div>
                    </form>";
        }
        return $html;
    }

    public function publicReportCustomer()
    {
        $date = \Request()->date;
        if (isset(\Request()->reCalculate)){
            DB::table('orders')
                ->whereDate('created_at',$date)
                ->update(['items_count' => null]);
        }
        (new \Modules\Core\Helpers\Helpers)->updateOrdersCalculateData();


        $orders = DB::table('orders')
            ->whereIn('status',['new','delivered','in_progress'])
            ->whereDate('created_at',$date)
            ->whereNull('reserved_id')
            ->get();


        $html = $this->commonHtmlForReport($date);

        $persian_date = convertMiladiToShamsiWithoutTime($date);
        $html .= "<h4 class='text-center'>گزارش مشتریان تعداد تنوع فروش رفته در تاریخ  <div class='inline-date'>$persian_date</div></h4>";

        $html1 = "<table class='table table-hover table-striped'>";

        $html1 .= "<tr>";
        $html1 .= "<th>شناسه سفارش</th>";
        $html1 .= "<th>استان</th>";
        $html1 .= "<th>شهرستان</th>";
        $html1 .= "<th>نام</th>";
        $html1 .= "<th>نام خانوادگی</th>";
        $html1 .= "<th>قیمت</th>";
        $html1 .= "<th>هزینه ارسال</th>";
        $html1 .= "<th>پرداخت از کیف پول</th>";
        $html1 .= "<th>تخفیف</th>";
        $html1 .= "<th>تخفیف با کوپن</th>";
        $html1 .= "<th>قابل پرداخت</th>";
        $html1 .= "<th>تعداد محصولات</th>";
        $html1 .= "<th>تعداد آیتم ها (تنوع)</th>";
        $html1 .= "<th>زمان</th>";
        $html1 .= "</tr>";

        $total_price = 0;
        $total_shipping = 0;
        $total_wallet = 0;
        $total_count = 0;
        $total_quantity = 0;
        $total_discount = 0;
        $total_payable = 0;

        foreach($orders as $item){

            $order_id = $item->id;
            $province = $item->province;
            $city = $item->city;
            $first_name = $item->first_name;
            $last_name = $item->last_name;
            $has_coupon = $item->coupon_id?"1":"0";

            $total_price += $item->total_amount;
            $total_shipping += $item->shipping_amount;
            $total_wallet += $item->used_wallet_amount;
            $total_payable += $item->total_payable_amount;
            $total_count += $item->items_count;
            $total_quantity += $item->items_quantity;
            $total_discount += $item->discount_amount;

            $price = number_format($item->total_amount, 0 , '.' , ',' );
            $shipping = number_format($item->shipping_amount, 0 , '.' , ',' );
            $wallet = number_format($item->used_wallet_amount, 0 , '.' , ',' );
            $count = number_format($item->items_count, 0 , '.' , ',' );
            $quantity = number_format($item->items_quantity, 0 , '.' , ',' );
            $discount = number_format($item->discount_amount, 0 , '.' , ',' );
            $payable = number_format($item->total_payable_amount, 0 , '.' , ',' );
            $time = explode(" ",$item->created_at)[1];

            $c = $count>$quantity?'bg-warning':'';
            $html1 .= "<tr class='$c'>";
            $html1 .= "<td>$order_id</td>";
            $html1 .= "<td>$province</td>";
            $html1 .= "<td>$city</td>";
            $html1 .= "<td>$first_name</td>";
            $html1 .= "<td>$last_name</td>";
            $html1 .= "<td>$price</td>";
            $html1 .= "<td>$shipping</td>";
            $html1 .= "<td>$wallet</td>";
            $html1 .= "<td>$discount</td>";
            $html1 .= "<td>$has_coupon</td>";
            $html1 .= "<td>$payable</td>";
            $html1 .= "<td>$count</td>";
            $html1 .= "<td>$quantity</td>";
            $html1 .= "<td>$time</td>";
            $html1 .= "</tr>";
        }

        $html1 .= "</table>";

        $html2 = "<table class='table table-hover table-striped'>";

        $html2 .= "<tr>";
        $html2 .= "<th>تعداد سفارشات</th>";
        $html2 .= "<th>مجموع فروش</th>";
        $html2 .= "<th>مجموع هزینه ارسال</th>";
        $html2 .= "<th>مجموع پرداخت از کیف پول</th>";
        $html2 .= "<th>تخفیف</th>";
        $html2 .= "<th>مجموع قابل پرداخت</th>";
        $html2 .= "<th>مجموع محصولات</th>";
        $html2 .= "<th>مجموع آیتم ها (تنوع)</th>";
        $html2 .= "</tr>";

        $total_orders_count = count($orders);

        $total_orders_count = number_format($total_orders_count, 0 , '.' , ',' );
        $total_price = number_format($total_price, 0 , '.' , ',' );
        $total_shipping = number_format($total_shipping, 0 , '.' , ',' );
        $total_wallet = number_format($total_wallet, 0 , '.' , ',' );
        $total_count = number_format($total_count, 0 , '.' , ',' );
        $total_quantity = number_format($total_quantity, 0 , '.' , ',' );
        $total_discount = number_format($total_discount, 0 , '.' , ',' );
        $total_payable = number_format($total_payable, 0 , '.' , ',' );

        $html2 .= "<tr>";
        $html2 .= "<td>$total_orders_count</td>";
        $html2 .= "<td>$total_price</td>";
        $html2 .= "<td>$total_shipping</td>";
        $html2 .= "<td>$total_wallet</td>";
        $html2 .= "<td>$total_discount</td>";
        $html2 .= "<td>$total_payable</td>";
        $html2 .= "<td>$total_count</td>";
        $html2 .= "<td>$total_quantity</td>";
        $html2 .= "</tr>";

        $html2 .= "</table>";

        $html .= "<hr>";
        $html .= $html2;
        $html .= "<hr>";
        $html .= $html1;

        echo "<div class='container'>";
        echo $html;
        echo "</div>";
    }
    public function publicReportVariety()
    {
        $date = \Request()->date;
        if (isset(\Request()->reCalculate)){
            DB::table('orders')
                ->whereDate('created_at',$date)
                ->update(['items_count' => null]);
        }
        (new \Modules\Core\Helpers\Helpers)->updateOrdersCalculateData();

        $orders = DB::table('orders')
            ->select('id')
            ->whereIn('status',['new','delivered','in_progress'])
            ->whereDate('created_at',$date)
            ->where('reserved_id',null)
            ->pluck('id')
            ->toArray();

        $sub_orders = DB::table('orders')
            ->select('id')
            ->whereIn('reserved_id',$orders)
            ->whereIn('status',['new','delivered','in_progress'])
            ->pluck('id')
            ->toArray();

        $mergedOrdersArray = array_merge($orders, $sub_orders);


        $report = DB::table('order_items as oi')
            ->join('products as p' ,'oi.product_id' , '=', 'p.id')
            ->leftJoin('varieties as v' ,'oi.variety_id' , '=', 'v.id')
            ->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id')
            ->select(
                "p.id",
                "oi.variety_id",
                "oi.quantity",
                "oi.amount",
                "oi.discount_amount",
                DB::raw("oi.amount + oi.discount_amount as real_price"),
//                DB::raw("oi.amount * sum(oi.quantity) as total"),
                DB::raw("(oi.amount + oi.discount_amount) * sum(oi.quantity) as total"),
                "p.title",
                DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].value')) as value"),
                DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].label')) as label"),
                "c.name as color",
                DB::raw("sum(oi.quantity) as sum")
            )
            ->whereIn('order_id', $mergedOrdersArray)
            ->where('oi.status', 1)
            ->groupBy('oi.variety_id')
            ->get();

        $final_report = [];
        foreach ($report as $item) {
//            $data['id'] = $item->id;

            $title = [];
            $title[] = $item->title;
            if($item->label) {$title[] = $item->label;}
            if($item->value) {$title[] = $item->value;}
            if($item->color) {$title[] = "رنگ " . $item->color;}

            $data['id'] = $item->id;
            $data['variety_id'] = $item->variety_id;
            $data['title'] = implode(" | ", $title);
            $data['sell_quantity'] = $item->sum;
            $data['price'] = $item->real_price;
            $data['total_sale'] = $item->total;

            $final_report[] = $data;
        }

        $html = $this->commonHtmlForReport($date);

        $persian_date = convertMiladiToShamsiWithoutTime($date);
        $html .= "<h4 class='text-center'>گزارش تعداد تنوع فروش رفته در تاریخ  <div class='inline-date'>$persian_date</div></h4>";

        $html1 = "<table class='table table-hover table-striped'>";
        $html1 .= "<th>شناسه</th>";
        $html1 .= "<th>شناسه تنوع</th>";
        $html1 .= "<th>عنوان</th>";
        $html1 .= "<th>تعداد فروش</th>";
        $html1 .= "<th>قیمت</th>";
        $html1 .= "<th>جمع فروش</th>";
        $html1 .= "</tr>";

        $total_sell_quantity = 0;
        $total_price = 0;

        foreach($final_report as $item){
            $item_id = $item['id'];
            $variety_id = $item['variety_id'];
            $item_title = $item['title'];
            $item_quantity = $item['sell_quantity'];
            $item_price = $item['price'];
            $item_total_price = $item['total_sale'];

            $total_sell_quantity += $item_quantity;
            $total_price += $item_total_price;

            $item_id = number_format($item_id, 0 , '.' , ',' );
            $item_quantity = number_format($item_quantity, 0 , '.' , ',' );
            $item_price = number_format($item_price, 0 , '.' , ',' );
            $item_total_price = number_format($item_total_price, 0 , '.' , ',' );

            $html1 .= "<tr>";
            $html1 .= "<td>$item_id</td>";
            $html1 .= "<td>$variety_id</td>";
            $html1 .= "<td>$item_title</td>";
            $html1 .= "<td>$item_quantity</td>";
            $html1 .= "<td>$item_price</td>";
            $html1 .= "<td>$item_total_price</td>";
            $html1 .= "</tr>";
        }

        $html1 .= "</table>";

        $html2 = "<table class='table table-hover table-striped'>";

        $html2 .= "<tr>";
        $html2 .= "<th>تعداد تنوع</th>";
        $html2 .= "<th>مجموع قیمت</th>";
        $html2 .= "</tr>";

        $total_sell_quantity = number_format($total_sell_quantity, 0 , '.' , ',' );
        $total_price = number_format($total_price, 0 , '.' , ',' );

        $html2 .= "<tr>";
        $html2 .= "<td>$total_sell_quantity</td>";
        $html2 .= "<td>$total_price</td>";
        $html2 .= "</tr>";

        $html2 .= "</table>";

        $html .= "<hr>";
        $html .= $html2;
        $html .= "<hr>";
        $html .= $html1;
        echo $html;
    }
    public function publicReportFull()
    {
        $date = \Request()->date;
        if (isset(\Request()->reCalculate)){
            DB::table('orders')
                ->whereDate('created_at',$date)
                ->update(['items_count' => null]);
        }
        (new \Modules\Core\Helpers\Helpers)->updateOrdersCalculateData();

        $orders = DB::table('orders')
            ->select('id')
            ->whereIn('status',['new','delivered','in_progress'])
            ->whereDate('created_at',$date)
            ->where('reserved_id',null)
            ->pluck('id')
            ->toArray();

        $sub_orders = DB::table('orders')
            ->select('id')
            ->whereIn('reserved_id',$orders)
            ->whereIn('status',['new','delivered','in_progress'])
            ->pluck('id')
            ->toArray();

        $mergedOrdersArray = array_merge($orders, $sub_orders);


        $report = DB::table('order_items as oi')
            ->join('products as p' ,'oi.product_id' , '=', 'p.id')
            ->leftJoin('varieties as v' ,'oi.variety_id' , '=', 'v.id')
            ->leftJoin('colors as c' ,'v.color_id' , '=', 'c.id')
            ->select(
                "p.id",
                "oi.variety_id",
                "oi.order_id",
                "oi.quantity",
                "oi.amount",
                "oi.discount_amount",
                DB::raw("oi.amount + oi.discount_amount as real_price"),
                DB::raw("(oi.amount + oi.discount_amount) * oi.quantity as total"),
                "p.title",
                DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].value')) as value"),
                DB::raw("json_unquote(JSON_EXTRACT(oi.extra, '$.attributes[0].label')) as label"),
                "c.name as color"
            )
            ->whereIn('order_id', $mergedOrdersArray)
            ->where('oi.status', 1)
            ->get();

        $final_report = [];
        foreach ($report as $item) {
//            $data['id'] = $item->id;

            $title = [];
            $title[] = $item->title;
            if($item->label) {$title[] = $item->label;}
            if($item->value) {$title[] = $item->value;}
            if($item->color) {$title[] = "رنگ " . $item->color;}

            $data['id'] = $item->id;
            $data['variety_id'] = $item->variety_id;
            $data['order_id'] = $item->order_id;
            $data['title'] = implode(" | ", $title);
            $data['sell_quantity'] = $item->quantity;
            $data['price'] = $item->real_price;
            $data['total_sale'] = $item->total;

            $data['order_check'] = Order::find($item->order_id)->reserved_id??'-';

            $final_report[] = $data;
        }

        $html = $this->commonHtmlForReport($date);

        $persian_date = convertMiladiToShamsiWithoutTime($date);
        $html .= "<h4 class='text-center'>گزارش تعداد تنوع فروش رفته در تاریخ  <div class='inline-date'>$persian_date</div></h4>";

        $html1 = "<table class='table table-hover table-striped'>";
        $html1 .= "<tr>";
        $html1 .= "<th>شناسه</th>";
        $html1 .= "<th>شماره سفارش</th>";
        $html1 .= "<th>پدر</th>";
        $html1 .= "<th>شناسه تنوع</th>";
        $html1 .= "<th>عنوان</th>";
        $html1 .= "<th>تعداد فروش</th>";
        $html1 .= "<th>قیمت</th>";
        $html1 .= "<th>جمع فروش</th>";
        $html1 .= "</tr>";

        $total_sell_quantity = 0;
        $total_price = 0;

        foreach($final_report as $item){

            $item_id = $item['id'];
            $variety_id = $item['variety_id'];
            $item_order_id = $item['order_id'];
            $item_order_check = $item['order_check'];
            $item_title = $item['title'];
            $item_quantity = $item['sell_quantity'];
            $item_price = $item['price'];
            $item_total_price = $item['total_sale'];

            $total_sell_quantity += $item_quantity;
            $total_price += $item_total_price;

            $item_quantity = number_format($item_quantity, 0 , '.' , ',' );
            $item_price = number_format($item_price, 0 , '.' , ',' );
            $item_total_price = number_format($item_total_price, 0 , '.' , ',' );

            $html1 .= "<tr>";
            $html1 .= "<td>$item_id</td>";
            $html1 .= "<td>$item_order_id</td>";
            $html1 .= "<td>$item_order_check</td>";
            $html1 .= "<td>$variety_id</td>";
            $html1 .= "<td>$item_title</td>";
            $html1 .= "<td>$item_quantity</td>";
            $html1 .= "<td>$item_price</td>";
            $html1 .= "<td>$item_total_price</td>";
            $html1 .= "</tr>";
        }

        $html1 .= "</table>";

        $html2 = "<table class='table table-hover table-striped'>";

        $html2 .= "<tr>";
        $html2 .= "<th>تعداد تنوع</th>";
        $html2 .= "<th>مجموع قیمت</th>";
        $html2 .= "</tr>";

        $total_sell_quantity = number_format($total_sell_quantity, 0 , '.' , ',' );
        $total_price = number_format($total_price, 0 , '.' , ',' );

        $html2 .= "<tr>";
        $html2 .= "<td>$total_sell_quantity</td>";
        $html2 .= "<td>$total_price</td>";
        $html2 .= "</tr>";

        $html2 .= "</table>";

        $html .= "<hr>";
        $html .= $html2;
        $html .= "<hr>";
        $html .= $html1;
        echo $html;
    }

    public function sellTypes()
    {
        $sellTypes = SellType::all();
        $options = [
            [
                'value' => null,
                'text' =>'همه'
            ]
        ];
        foreach ($sellTypes as $sellType) {
            $options[] = [
                'value' => $sellType->key,
                'text' =>$sellType->value
            ];
        }
        return response()->success('انواع فروش',compact('options'));
    }

    public function walletsExcel() {
        $customer_id = request('customer_id', false);
        $startDate = request('start_date', false);
        $endDate = request('end_date', false);
        $reports = Customer::query()
            ->leftjoin('wallets', 'customers.id', '=', 'wallets.holder_id')
            ->leftjoin('transactions as tr', 'wallets.id', '=', 'tr.wallet_id')
            ->addSelect(['customers.id', 'customers.first_name', 'customers.last_name', 'customers.mobile',
                'wallet_balance' => DB::raw("wallets.balance as wallet_balance"),
                'total_deposit' => DB::raw("SUM(IF(tr.type = 'deposit' , amount, 0)) as total_deposit"),
                'total_withdraw' => DB::raw("ABS(SUM(IF(tr.type = 'withdraw', amount, 0))) as total_withdraw"),
                'count_deposit' => DB::raw("sum(IF(tr.type = 'deposit' , 1, 0)) as deposit_count"),
                'count_withdraw' => DB::raw("sum(IF(tr.type = 'withdraw', 1, 0)) as withdraw_count"),
            ])
            ->where('wallets.holder_type', Customer::class)
            ->when($customer_id, fn($q) => $q->where(DB::raw('customers.id'), $customer_id))
            ->when($startDate, fn($q) => $q->where('tr.created_at', '>', $startDate))
            ->when($endDate, fn($q) => $q->where('tr.created_at', '<', $endDate))
            ->groupBy('id')
            ->sortFilter();

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            foreach ($reports->get() as $item){
                $final_list [] = [
                    $item->id,
                    $item->first_name.' '.$item->last_name,
                    $item->mobile,
                    $item->wallet_balance,
                    $item->total_deposit,
                    $item->total_withdraw,
                    $item->count_deposit,
                    $item->count_withdraw,
                ];
            }

            return Excel::download(new WalletReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        $report_for_sum = clone $reports;
        $reports = $reports->paginateOrAll();

        foreach ($reports as $report) {
            $report->makeHidden('wallet');
        }

//        $sum_balance = Customer::query()
//            ->leftjoin('wallets', 'customers.id', '=', 'wallets.holder_id')
//            ->leftjoin('transactions as tr', 'wallets.id', '=', 'tr.wallet_id')
//            ->addSelect(DB::raw("sum(wallets.balance) as sum_wallet_balance"))
//            ->where('wallets.holder_type', Customer::class)
//            ->when($customer_id, fn($q) => $q->where(DB::raw('customers.id'), $customer_id))
//            ->value(DB::raw('sum_wallet_balance'));

        $sum_balance = 0;
        foreach ($report_for_sum->get() as $item) {
            $sum_balance += $item->wallet_balance;
        }

//        $sum_balance = DB::select(DB::raw("SELECT sum(t.balance) as sum_balance
//                        FROM (SELECT balance FROM `wallet_reports_view` where 1 GROUP BY id) t where 1")
//        )[0]->sum_balance;

        return response()->success('', compact('reports', 'sum_balance'));
    }

    public function customersExcel()
    {
        $customers = Customer::query()
            ->latest('id')
//            ->take(10)
            ->select(
                'id',
                'first_name',
                'last_name',
                'mobile',
                'email',
                'created_at',
            )
            ->get();

        foreach ($customers as $customer) {
            $customer->makeHidden('wallet');
            $customer->makeHidden('role');
            $customer->makeHidden('image');
        }

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            foreach ($customers as $item) {
                $final_list [] = [
                    $item->id,
                    $item->full_name,
                    $item->mobile,
                    $item->email,
                    $item->persian_register_date,
                    $item->last_online_order_date,
                    $item->last_real_order_date,
                ];
            }

            return Excel::download(new CustomerReportSimpleExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        return response()->success('دریافت لیست همه مشتری ها', compact('customers'));
    }
}
